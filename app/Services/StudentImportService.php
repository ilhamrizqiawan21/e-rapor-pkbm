<?php

namespace App\Services;

use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use DateTimeImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use XMLReader;
use ZipArchive;

/**
 * Parses bounded CSV/XLSX uploads into a tenant-scoped preview before any
 * student rows are persisted.
 */
class StudentImportService
{
    private const MAX_ROWS = 50000;

    private const PREVIEW_TTL_MINUTES = 30;

    /** @var array<string, string> */
    private const HEADER_ALIASES = [
        'nipd' => 'student_number',
        'nomor induk peserta didik' => 'student_number',
        'nomor induk' => 'student_number',
        'nisn' => 'national_student_number',
        'nomor induk siswa nasional' => 'national_student_number',
        'nama' => 'full_name',
        'nama lengkap' => 'full_name',
        'full name' => 'full_name',
        'jenis kelamin' => 'gender',
        'jk' => 'gender',
        'gender' => 'gender',
        'tempat lahir' => 'birth_place',
        'birth place' => 'birth_place',
        'tanggal lahir' => 'birth_date',
        'tgl lahir' => 'birth_date',
        'birth date' => 'birth_date',
        'alamat' => 'address',
        'address' => 'address',
        'nama wali' => 'guardian_name',
        'wali' => 'guardian_name',
        'guardian name' => 'guardian_name',
        'status' => 'status',
    ];

    /** @var array<int, string> */
    private const TEMPLATE_HEADERS = [
        'NIPD',
        'NISN',
        'Nama Lengkap',
        'Jenis Kelamin',
        'Tempat Lahir',
        'Tanggal Lahir',
        'Alamat',
        'Nama Wali',
        'Status',
    ];

    /** @var array<int, string> */
    private const REQUIRED_COLUMNS = ['student_number', 'full_name'];

    /**
     * @return array{headers: array<int, string>, rows: array<int, array<string, mixed>>, valid_count: int, invalid_count: int, blank_count: int, total_count: int, errors: array<int, array{row: int, errors: array<int, string>, values: array<string, string>}}
     */
    public function preview(UploadedFile $file, Institution $institution): array
    {
        // Preview is intentionally separate from import so operators can review
        // row-level errors without partially writing personal data.
        $this->assertSafeFile($file);

        $rawRows = $this->readRows($file);
        if ($rawRows === []) {
            throw new RuntimeException('File import tidak memiliki data.');
        }

        $headers = $this->mapHeaders(array_shift($rawRows));
        $rows = [];
        $errors = [];
        $blankCount = 0;

        foreach ($rawRows as $index => $rawRow) {
            $rowNumber = $index + 2;
            $values = $this->normalizeRow($rawRow, $headers);
            $isBlank = $this->isBlankRow($values);
            $rowErrors = $isBlank ? ['Baris kosong.'] : $this->validateRow($values);

            if ($isBlank) {
                $blankCount++;
            }

            $row = [
                'row' => $rowNumber,
                'values' => $values,
                'errors' => $rowErrors,
                'valid' => $rowErrors === [],
            ];
            $rows[] = $row;
        }

        $this->validateDuplicates($rows, $institution);

        $validCount = 0;
        foreach ($rows as $row) {
            if ($row['errors'] === []) {
                $validCount++;
            }
            $errors[] = [
                'row' => $row['row'],
                'errors' => $row['errors'],
                'values' => $row['values'],
            ];
        }

        return [
            'headers' => self::TEMPLATE_HEADERS,
            'rows' => $rows,
            'valid_count' => $validCount,
            'invalid_count' => count($rows) - $validCount,
            'blank_count' => $blankCount,
            'total_count' => count($rows),
            'errors' => array_values(array_filter($errors, static fn (array $row): bool => $row['errors'] !== [])),
        ];
    }

    /**
     * @param  array{rows: array<int, array<string, mixed>>}  $preview
     * @return array{imported: int, skipped: int, conflicted: int}
     */
    public function import(array $preview, Institution $institution): array
    {
        // Only rows marked valid by the preview are accepted; the preview token
        // is not a substitute for the final institution-scoped duplicate checks.
        $validRows = array_values(array_filter(
            $preview['rows'],
            static fn (array $row): bool => $row['errors'] === [],
        ));

        $now = now();
        $payload = array_map(static function (array $row) use ($institution, $now): array {
            $values = $row['values'];

            return [
                'institution_id' => $institution->getKey(),
                'student_number' => $values['student_number'],
                'national_student_number' => $values['national_student_number'] ?: null,
                'full_name' => $values['full_name'],
                'gender' => $values['gender'] ?: null,
                'birth_place' => $values['birth_place'] ?: null,
                'birth_date' => $values['birth_date'] ?: null,
                'address' => $values['address'] ?: null,
                'guardian_name' => $values['guardian_name'] ?: null,
                'status' => $values['status'] ?: 'ACTIVE',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $validRows);

        $conflicted = 0;
        DB::transaction(function () use (&$payload, &$conflicted, $institution): void {
            // The preview can be older than the final submit. Re-check inside
            // the same transaction so a second import cannot create a duplicate
            // after the operator has reviewed the file.
            $conflicts = $this->findImportConflicts($payload, $institution);
            $conflicted = count($conflicts);
            $payload = array_values(array_filter(
                $payload,
                static fn (array $row, int $index): bool => ! isset($conflicts[$index]),
                ARRAY_FILTER_USE_BOTH,
            ));

            foreach (array_chunk($payload, 500) as $chunk) {
                Student::query()->insert($chunk);
            }
        });

        return [
            'imported' => count($payload),
            'skipped' => count($preview['rows']) - count($payload),
            'conflicted' => $conflicted,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $payload
     * @return array<int, true>
     */
    private function findImportConflicts(array $payload, Institution $institution): array
    {
        $numbers = array_values(array_filter(array_column($payload, 'student_number')));
        $nisns = array_values(array_filter(array_column($payload, 'national_student_number')));
        $existingNumbers = Student::query()
            ->where('institution_id', $institution->getKey())
            ->whereIn('student_number', array_values(array_unique($numbers)))
            ->pluck('student_number')
            ->all();
        $existingNisns = Student::query()
            ->where('institution_id', $institution->getKey())
            ->whereIn('national_student_number', array_values(array_unique($nisns)))
            ->pluck('national_student_number')
            ->all();
        $numberCounts = array_count_values($numbers);
        $nisnCounts = array_count_values($nisns);
        $conflicts = [];

        foreach ($payload as $index => $row) {
            $number = (string) ($row['student_number'] ?? '');
            $nisn = (string) ($row['national_student_number'] ?? '');
            if ($number !== '' && (($numberCounts[$number] ?? 0) > 1 || in_array($number, $existingNumbers, true))) {
                $conflicts[$index] = true;
            }
            if ($nisn !== '' && (($nisnCounts[$nisn] ?? 0) > 1 || in_array($nisn, $existingNisns, true))) {
                $conflicts[$index] = true;
            }
        }

        return $conflicts;
    }

    /**
     * @param  array<string, mixed>  $preview
     */
    public function storePreview(array $preview, User $user, Institution $institution): string
    {
        $token = bin2hex(random_bytes(32));
        $payload = [
            'token' => $token,
            'user_id' => $user->getKey(),
            'institution_id' => $institution->getKey(),
            'expires_at' => now()->addMinutes(self::PREVIEW_TTL_MINUTES)->toIso8601String(),
            'preview' => $preview,
        ];

        Storage::disk('local')->put(
            $this->previewPath($token),
            json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        );

        return $token;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function loadPreview(string $token, User $user, Institution $institution): ?array
    {
        if (! preg_match('/\A[a-f0-9]{64}\z/', $token)) {
            return null;
        }

        $path = $this->previewPath($token);
        if (! Storage::disk('local')->exists($path)) {
            return null;
        }

        try {
            /** @var array<string, mixed> $payload */
            $payload = json_decode(Storage::disk('local')->get($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (($payload['user_id'] ?? null) !== $user->getKey()
            || ($payload['institution_id'] ?? null) !== $institution->getKey()
            || now()->greaterThan(new DateTimeImmutable((string) ($payload['expires_at'] ?? '1970-01-01')))) {
            return null;
        }

        return is_array($payload['preview'] ?? null) ? $payload['preview'] : null;
    }

    public function forgetPreview(string $token): void
    {
        if (preg_match('/\A[a-f0-9]{64}\z/', $token)) {
            Storage::disk('local')->delete($this->previewPath($token));
        }
    }

    public function templateResponse(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $path = tempnam(sys_get_temp_dir(), 'student-template-');
            if ($path === false) {
                throw new RuntimeException('Template tidak dapat dibuat.');
            }

            try {
                $this->writeTemplate($path);
                readfile($path);
            } finally {
                unlink($path);
            }
        }, 'template-peserta-didik.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  array{errors: array<int, array{row: int, errors: array<int, string>, values: array<string, string>}>}  $preview
     */
    public function errorReportResponse(array $preview): StreamedResponse
    {
        return response()->streamDownload(function () use ($preview): void {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Baris', ...self::TEMPLATE_HEADERS, 'Kesalahan']);

            foreach ($preview['errors'] as $error) {
                fputcsv($output, [
                    $error['row'],
                    ...array_values($error['values']),
                    implode(' ', $error['errors']),
                ]);
            }

            fclose($output);
        }, 'error-import-peserta-didik.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param  array<int, string>  $headers
     * @return array<string, string>
     */
    private function mapHeaders(array $headers): array
    {
        $mapped = [];
        $unknown = [];
        $duplicates = [];

        foreach ($headers as $header) {
            $key = $this->normalizeHeader($header);
            $column = self::HEADER_ALIASES[$key] ?? null;

            if ($column === null) {
                $unknown[] = trim((string) $header);

                continue;
            }
            if (in_array($column, $mapped, true)) {
                $duplicates[] = trim((string) $header);

                continue;
            }

            $mapped[] = $column;
        }

        $missing = array_values(array_diff(self::REQUIRED_COLUMNS, array_values($mapped)));
        if ($unknown !== [] || $duplicates !== [] || $missing !== []) {
            $details = [];
            if ($missing !== []) {
                $details[] = 'kolom wajib hilang: '.implode(', ', $missing);
            }
            if ($unknown !== []) {
                $details[] = 'kolom tidak dikenal: '.implode(', ', $unknown);
            }
            if ($duplicates !== []) {
                $details[] = 'kolom duplikat: '.implode(', ', $duplicates);
            }

            throw new RuntimeException('Header file tidak valid ('.implode('; ', $details).').');
        }

        return $mapped;
    }

    /**
     * @param  array<int, string>  $row
     * @param  array<string, string>  $headers
     * @return array<string, string>
     */
    private function normalizeRow(array $row, array $headers): array
    {
        $values = array_fill_keys(array_values(array_unique(self::HEADER_ALIASES)), '');
        foreach ($headers as $position => $column) {
            $values[$column] = $this->cleanValue($row[$position] ?? '');
        }

        return $values;
    }

    /**
     * @param  array<string, string>  $values
     * @return array<int, string>
     */
    private function validateRow(array &$values): array
    {
        $errors = [];

        if ($values['student_number'] === '') {
            $errors[] = 'NIPD wajib diisi.';
        } elseif (! preg_match('/\A[A-Za-z0-9._\/-]{1,100}\z/', $values['student_number'])) {
            $errors[] = 'NIPD hanya boleh berisi huruf, angka, titik, garis bawah, garis miring, atau tanda hubung.';
        }

        if ($values['full_name'] === '') {
            $errors[] = 'Nama lengkap wajib diisi.';
        } elseif (mb_strlen($values['full_name']) > 255) {
            $errors[] = 'Nama lengkap maksimal 255 karakter.';
        }

        if ($values['national_student_number'] !== '' && ! preg_match('/\A\d{10}\z/', $values['national_student_number'])) {
            $errors[] = 'NISN harus terdiri dari 10 digit angka.';
        }

        if ($values['gender'] !== '') {
            $gender = strtoupper(str_replace(['-', '_'], ' ', $values['gender']));
            $gender = match ($gender) {
                'L', 'LAKI LAKI', 'LAKI-LAKI', 'MALE' => 'L',
                'P', 'PEREMPUAN', 'FEMALE' => 'P',
                default => null,
            };
            if ($gender === null) {
                $errors[] = 'Jenis kelamin harus L atau P.';
            } else {
                $values['gender'] = $gender;
            }
        }

        if ($values['birth_date'] !== '') {
            $date = $this->parseDate($values['birth_date']);
            if ($date === null) {
                $errors[] = 'Tanggal lahir tidak valid.';
            } else {
                $values['birth_date'] = $date;
            }
        }

        if ($values['status'] === '') {
            $values['status'] = 'ACTIVE';
        } elseif (! in_array(strtoupper($values['status']), ['ACTIVE', 'INACTIVE', 'GRADUATED', 'TRANSFERRED'], true)) {
            $errors[] = 'Status harus ACTIVE, INACTIVE, GRADUATED, atau TRANSFERRED.';
        } else {
            $values['status'] = strtoupper($values['status']);
        }

        return $errors;
    }

    /**
     * @param  array<int, array{row: int, values: array<string, string>, errors: array<int, string>, valid: bool}>  $rows
     */
    private function validateDuplicates(array &$rows, Institution $institution): void
    {
        $numbers = [];
        $nisns = [];
        foreach ($rows as $row) {
            $numbers[] = $row['values']['student_number'];
            if ($row['values']['national_student_number'] !== '') {
                $nisns[] = $row['values']['national_student_number'];
            }
        }

        $existingNumbers = Student::query()
            ->where('institution_id', $institution->getKey())
            ->whereIn('student_number', array_values(array_unique(array_filter($numbers))))
            ->pluck('student_number')
            ->all();
        $existingNisns = Student::query()
            ->where('institution_id', $institution->getKey())
            ->whereIn('national_student_number', array_values(array_unique(array_filter($nisns))))
            ->pluck('national_student_number')
            ->all();

        $numberCounts = array_count_values(array_filter($numbers));
        $nisnCounts = array_count_values(array_filter($nisns));
        foreach ($rows as &$row) {
            $number = $row['values']['student_number'];
            $nisn = $row['values']['national_student_number'];

            if ($number !== '' && (($numberCounts[$number] ?? 0) > 1 || in_array($number, $existingNumbers, true))) {
                $row['errors'][] = 'NIPD duplikat pada file atau sudah terdaftar di database.';
            }
            if ($nisn !== '' && (($nisnCounts[$nisn] ?? 0) > 1 || in_array($nisn, $existingNisns, true))) {
                $row['errors'][] = 'NISN duplikat pada file atau sudah terdaftar di database.';
            }
            $row['valid'] = $row['errors'] === [];
        }
        unset($row);
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function readRows(UploadedFile $file): array
    {
        $extension = strtolower((string) $file->extension());

        return $extension === 'csv' || $extension === 'txt'
            ? $this->readCsv($file->getRealPath())
            : $this->readXlsx($file->getRealPath());
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function readCsv(?string $path): array
    {
        if ($path === null || ! is_readable($path)) {
            throw new RuntimeException('File CSV tidak dapat dibaca.');
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('File CSV tidak dapat dibuka.');
        }

        try {
            $first = fgets($handle);
            if ($first === false) {
                return [];
            }
            $delimiter = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';
            rewind($handle);
            $rows = [];
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) ($row[0] ?? ''));
                $rows[] = array_map(fn ($value): string => (string) $value, $row);
                $this->guardRowCount(count($rows));
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function readXlsx(?string $path): array
    {
        if ($path === null || ! is_readable($path)) {
            throw new RuntimeException('File XLSX tidak dapat dibaca.');
        }

        $zip = new ZipArchive;
        if ($zip->open($path) !== true || $zip->locateName('[Content_Types].xml') === false) {
            $zip->close();
            throw new RuntimeException('File XLSX tidak valid.');
        }

        try {
            $sharedStrings = $this->readSharedStrings($zip->getFromName('xl/sharedStrings.xml') ?: null);
            $sheetPath = $this->findFirstSheet($zip);
            if ($sheetPath === null) {
                throw new RuntimeException('Worksheet XLSX tidak ditemukan.');
            }

            $rows = $this->readSheet($zip->getFromName($sheetPath), $sharedStrings);

            return $rows;
        } finally {
            $zip->close();
        }
    }

    /**
     * @param  array<int, string>  $sharedStrings
     * @return array<int, array<int, string>>
     */
    private function readSheet(string|false $xml, array $sharedStrings): array
    {
        if ($xml === false) {
            throw new RuntimeException('Worksheet XLSX tidak dapat dibaca.');
        }

        $reader = new XMLReader;
        if (! $reader->XML($xml, null, LIBXML_NONET | LIBXML_COMPACT)) {
            throw new RuntimeException('Worksheet XLSX memiliki XML tidak valid.');
        }

        $rows = [];
        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'row') {
                continue;
            }
            $rows[] = $this->parseSheetRow($reader->readOuterXml(), $sharedStrings);
            $this->guardRowCount(count($rows));
        }
        $reader->close();

        return $rows;
    }

    /**
     * @param  array<int, string>  $sharedStrings
     * @return array<int, string>
     */
    private function parseSheetRow(string $xml, array $sharedStrings): array
    {
        $row = [];
        $reader = new XMLReader;
        $reader->XML($xml, null, LIBXML_NONET | LIBXML_COMPACT);
        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'c') {
                continue;
            }
            $cellXml = $reader->readOuterXml();
            $cell = simplexml_load_string($cellXml, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
            if ($cell === false) {
                continue;
            }
            $reference = (string) ($cell['r'] ?? '');
            $column = $this->columnNumber($reference);
            $type = (string) ($cell['t'] ?? '');
            $value = (string) ($cell->v ?? '');
            if ($type === 's') {
                $value = $sharedStrings[(int) $value] ?? '';
            } elseif ($type === 'inlineStr') {
                $value = implode('', array_map(static fn ($text): string => (string) $text, $cell->xpath('//*[local-name()="t"]') ?: []));
            }
            $row[$column] = $value;
        }
        $reader->close();

        if ($row === []) {
            return [];
        }

        ksort($row);
        $lastColumn = max(array_keys($row));
        $values = array_fill(0, $lastColumn + 1, '');
        foreach ($row as $column => $value) {
            $values[$column] = $value;
        }

        return $values;
    }

    /**
     * @return array<int, string>
     */
    private function readSharedStrings(?string $xml): array
    {
        if ($xml === null || $xml === '') {
            return [];
        }
        $reader = new XMLReader;
        $reader->XML($xml, null, LIBXML_NONET | LIBXML_COMPACT);
        $values = [];
        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'si') {
                continue;
            }
            $item = simplexml_load_string($reader->readOuterXml(), 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
            $values[] = $item === false
                ? ''
                : implode('', array_map(static fn ($text): string => (string) $text, $item->xpath('//*[local-name()="t"]') ?: []));
        }
        $reader->close();

        return $values;
    }

    private function findFirstSheet(ZipArchive $zip): ?string
    {
        $workbook = $zip->getFromName('xl/workbook.xml');
        $relationships = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($workbook === false || $relationships === false) {
            return null;
        }

        $book = simplexml_load_string($workbook, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        $rels = simplexml_load_string($relationships, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        if ($book === false || $rels === false) {
            return null;
        }

        $sheets = $book->xpath('//*[local-name()="sheet"]');
        $sheetAttributes = $sheets[0]->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $relationshipId = $sheetAttributes['id'] ?? null;
        if ($relationshipId === null) {
            return null;
        }
        foreach ($rels->Relationship as $relationship) {
            if ((string) $relationship['Id'] === (string) $relationshipId) {
                $target = ltrim((string) $relationship['Target'], '/');

                return str_starts_with($target, 'xl/') ? $target : 'xl/'.$target;
            }
        }

        return null;
    }

    private function assertSafeFile(UploadedFile $file): void
    {
        if (! $file->isValid() || $file->getSize() === false || $file->getSize() > 10 * 1024 * 1024) {
            throw new RuntimeException('File import tidak valid atau melebihi batas 10 MB.');
        }

        $extension = strtolower((string) $file->extension());
        $mime = strtolower((string) $file->getMimeType());
        $allowed = $extension === 'xlsx'
            ? ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip']
            : ['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'];
        if (! in_array($extension, ['xlsx', 'csv', 'txt'], true) || ! in_array($mime, $allowed, true)) {
            throw new RuntimeException('Format file harus XLSX atau CSV yang valid.');
        }
    }

    private function writeTemplate(string $path): void
    {
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Template tidak dapat dibuat.');
        }
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Peserta Didik" sheetId="1" r:id="rId1"/><sheet name="Petunjuk" sheetId="2" r:id="rId2"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="10"/><name val="Arial"/></font><font><b/><sz val="10"/><name val="Arial"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FFDDEBF7"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"/><right style="thin"/><top style="thin"/><bottom style="thin"/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="4"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf><xf numFmtId="49" fontId="0" fillId="0" borderId="1" applyAlignment="1"><alignment vertical="top"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="0" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>');
        $cells = [];
        foreach (self::TEMPLATE_HEADERS as $index => $header) {
            $column = $this->columnName($index);
            $cells[] = '<c r="'.$column.'1" s="1" t="inlineStr"><is><t>'.htmlspecialchars($header, ENT_XML1 | ENT_COMPAT, 'UTF-8').'</t></is></c>';
        }
        $dataSheet = '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><cols><col min="1" max="2" width="20" style="2" customWidth="1"/><col min="3" max="9" width="22" customWidth="1"/></cols><sheetData><row r="1">'.implode('', $cells).'</row></sheetData><autoFilter ref="A1:I1"/><dataValidations count="2"><dataValidation type="list" allowBlank="1" sqref="D2:D50001"><formula1>"L,P"</formula1></dataValidation><dataValidation type="list" allowBlank="1" sqref="I2:I50001"><formula1>"ACTIVE,INACTIVE,GRADUATED,TRANSFERRED"</formula1></dataValidation></dataValidations><pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.3" footer="0.3"/></worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $dataSheet);
        $instructionRows = [
            ['PETUNJUK IMPORT PESERTA DIDIK', ''],
            ['1', 'Isi data hanya pada sheet Peserta Didik dan jangan mengubah nama header.'],
            ['2', 'NIPD dan NISN disimpan sebagai teks agar angka nol di depan tidak hilang.'],
            ['3', 'Jenis Kelamin menggunakan L atau P.'],
            ['4', 'Tanggal lahir menggunakan format YYYY-MM-DD atau DD/MM/YYYY.'],
            ['5', 'Status menggunakan ACTIVE, INACTIVE, GRADUATED, atau TRANSFERRED.'],
            ['6', 'Hapus baris contoh bila ada sebelum mengunggah file. Template ini sengaja tidak menyediakan data contoh.'],
        ];
        $instructionXml = '';
        foreach ($instructionRows as $rowIndex => $row) {
            $number = $rowIndex + 1;
            $instructionXml .= '<row r="'.$number.'"><c r="A'.$number.'" s="'.($rowIndex === 0 ? 1 : 3).'" t="inlineStr"><is><t>'.htmlspecialchars($row[0], ENT_XML1 | ENT_COMPAT, 'UTF-8').'</t></is></c><c r="B'.$number.'" s="3" t="inlineStr"><is><t xml:space="preserve">'.htmlspecialchars($row[1], ENT_XML1 | ENT_COMPAT, 'UTF-8').'</t></is></c></row>';
        }
        $zip->addFromString('xl/worksheets/sheet2.xml', '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><cols><col min="1" max="1" width="8" customWidth="1"/><col min="2" max="2" width="100" customWidth="1"/></cols><sheetData>'.$instructionXml.'</sheetData><pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.3" footer="0.3"/></worksheet>');
        $zip->close();
    }

    private function previewPath(string $token): string
    {
        return 'student-imports/'.hash('sha256', $token).'.json';
    }

    private function normalizeHeader(mixed $header): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', str_replace(['_', '-'], ' ', (string) $header))));
    }

    private function cleanValue(mixed $value): string
    {
        return trim((string) $value);
    }

    /**
     * @param  array<string, string>  $values
     */
    private function isBlankRow(array $values): bool
    {
        return count(array_filter($values, static fn (string $value): bool => $value !== '')) === 0;
    }

    private function parseDate(string $value): ?string
    {
        if (is_numeric($value) && (float) $value > 0) {
            $serial = (int) floor((float) $value);
            if ($serial >= 1 && $serial <= 2958465) {
                return (new DateTimeImmutable('1899-12-30'))->modify('+'.$serial.' days')->format('Y-m-d');
            }
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'd.m.Y', 'Y/m/d'] as $format) {
            $date = DateTimeImmutable::createFromFormat('!'.$format, $value);
            if ($date !== false && $date->format($format) === $value) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }

    private function columnNumber(string $reference): int
    {
        preg_match('/\A([A-Z]+)/i', $reference, $matches);
        $letters = strtoupper($matches[1] ?? 'A');
        $number = 0;
        foreach (str_split($letters) as $letter) {
            $number = ($number * 26) + ord($letter) - 64;
        }

        return max(0, $number - 1);
    }

    private function columnName(int $index): string
    {
        $name = '';
        do {
            $name = chr($index % 26 + 65).$name;
            $index = intdiv($index, 26) - 1;
        } while ($index >= 0);

        return $name;
    }

    private function guardRowCount(int $count): void
    {
        if ($count > self::MAX_ROWS + 1) {
            throw new RuntimeException('File melebihi batas 50.000 baris data.');
        }
    }
}
