<?php

namespace App\Services\Reports;

use App\Contracts\PdfEngine;
use App\Exceptions\ReportCardPdfException;
use App\Models\AcademicClass;
use App\Models\Institution;
use App\Models\InstitutionFormatSetting;
use App\Models\ReportCard;
use App\Models\Semester;
use App\Models\Student;
use App\Services\AcademicDecisionService;
use App\Services\InstitutionFormatSettingsService;
use App\Services\ReportCardCompletenessService;
use App\Services\ReportCardIdentityService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Prepares printable report-card data for complete cards and keeps
 * institution-specific identity/signatory snapshots in the output context.
 */
final class ReportCardPdfDataService
{
    public const PRINTABLE_STATUSES = ['DRAFT', 'SUBMITTED', 'IN_REVIEW', 'NEEDS_REVISION', 'APPROVED', 'LOCKED'];

    public function __construct(
        private readonly PdfEngine $engine,
        private readonly ReportCardCompletenessService $completeness,
        private readonly ReportCardIdentityService $identityService,
        private readonly InstitutionFormatSettingsService $formatSettings,
        private readonly AcademicDecisionService $decisions,
    ) {}

    /** @return array<string, mixed> */
    public function data(ReportCard $reportCard, bool $requireComplete = true): array
    {
        // Completeness is checked for the complete report only. Identity and grade
        // extracts remain useful while a wali kelas is still finishing the report.
        $this->assertPrintable($reportCard);
        if ($requireComplete) {
            $this->completeness->assertComplete($reportCard);
        }

        $reportCard->load([
            'enrollment.student',
            'enrollment.academicClass.program.institution',
            'enrollment.academicClass.homeroomTeacher.staffProfile',
            'semester.academicYear',
            'grades' => fn ($query) => $query->orderBy('display_order_snapshot')->orderBy('id'),
            'attitudeAssessments',
            'attendanceSummary',
            'extracurriculars',
            'homeroomNote',
            'signatures',
        ]);

        $institution = $this->institution($reportCard);
        $settings = $this->formatSettings->forInstitution($institution);
        $identity = $this->identityService->context($reportCard);
        $archivedOutput = $identity['archived_output'] ?? null;
        if (is_array($archivedOutput)) {
            $settings = (new InstitutionFormatSetting)->forceFill($archivedOutput['settings']);
        }
        $student = $reportCard->enrollment?->student;
        $studentName = $reportCard->status === 'LOCKED'
            ? (string) ($identity['student']['name'] ?? '')
            : ($student instanceof Student
                ? $this->formatSettings->formatStudentName($student, $settings)
                : (string) ($identity['student']['name'] ?? ''));
        $semesterName = (string) ($identity['semester']['name'] ?? $identity['semester']['code'] ?? '');
        $academicYearName = (string) ($identity['academic_year']['name'] ?? '');
        $signatures = $this->signatureRows($reportCard, $settings);
        $teacher = $reportCard->enrollment?->academicClass?->homeroomTeacher;
        $headSetting = $institution->signatorySetting;
        $headIsManual = $headSetting?->headmaster_source?->value === 'MANUAL';
        $headUser = $headSetting?->headmasterUser;
        $headName = $headIsManual ? $headSetting?->headmaster_name : $headUser?->name;
        $headNumber = $headIsManual ? $headSetting?->headmaster_employee_number : $headUser?->staffProfile?->employee_number;
        $signatureSlots = $archivedOutput['signatureSlots'] ?? ($signatures->isNotEmpty()
            ? $signatures
            : $this->emptySignatureSlots($settings, $reportCard, $institution));

        $parentSignature = collect($signatureSlots)->first(function ($s): bool {
            $type = ($s['type'] ?? null) instanceof \BackedEnum ? $s['type']->value : (string) ($s['type'] ?? '');

            return $type === 'PARENT_GUARDIAN' || str_contains(strtolower((string) ($s['label'] ?? '')), 'orang tua');
        }) ?? [
            'type' => 'PARENT_GUARDIAN',
            'label' => 'Orang Tua/Wali',
            'name' => $identity['student']['guardian_name'] ?? $student?->guardian_name ?? null,
            'employee_number' => null,
            'imageDataUri' => null,
            'signed_at' => null,
        ];

        $homeroomSignature = collect($signatureSlots)->first(function ($s): bool {
            $type = ($s['type'] ?? null) instanceof \BackedEnum ? $s['type']->value : (string) ($s['type'] ?? '');

            return in_array($type, ['HOMEROOM_TEACHER', 'GURU'], true) || str_contains(strtolower((string) ($s['label'] ?? '')), 'wali');
        }) ?? [
            'type' => 'HOMEROOM_TEACHER',
            'label' => $settings->signature_left_label ?: 'Wali Kelas',
            'name' => $teacher?->name,
            'employee_number' => $teacher?->staffProfile?->employee_number,
            'imageDataUri' => null,
            'signed_at' => null,
        ];

        $headmasterSignature = collect($signatureSlots)->first(function ($s): bool {
            $type = ($s['type'] ?? null) instanceof \BackedEnum ? $s['type']->value : (string) ($s['type'] ?? '');

            return in_array($type, ['HEAD_OF_PKBM', 'KEPSEK'], true) || str_contains(strtolower((string) ($s['label'] ?? '')), 'kepala');
        }) ?? [
            'type' => 'HEAD_OF_PKBM',
            'label' => $settings->signature_right_label ?: 'Kepala PKBM',
            'name' => $headName,
            'employee_number' => $headNumber,
            'imageDataUri' => null,
            'signed_at' => null,
        ];

        $issuedPlace = $archivedOutput['issuedPlace'] ?? $this->resolveIssuedPlace($institution, $identity);
        $rawDate = $reportCard->issued_date ?? $reportCard->locked_at;
        $signatureDate = $archivedOutput['signatureDate'] ?? ($rawDate !== null
            ? Carbon::parse($rawDate)->locale('id')->translatedFormat('j F Y')
            : null);

        $studentIdentifier = trim(implode(' / ', array_filter([
            $identity['student']['student_number'] ?? null,
            $identity['student']['nisn'] ?? null,
        ])));

        return [
            'reportCard' => $reportCard,
            'decisionLabel' => is_array($archivedOutput) ? ($archivedOutput['decisionLabel'] ?? null) : $this->decisions->forReport($reportCard)?->label_snapshot,
            'identity' => $identity,
            'studentName' => $archivedOutput['studentName'] ?? $studentName,
            'studentIdentifier' => $studentIdentifier,
            'settings' => $settings,
            'grades' => $reportCard->grades,
            'attitudes' => $reportCard->attitudeAssessments->keyBy(fn ($assessment): string => strtoupper($assessment->attitude_type)),
            'attendance' => $reportCard->attendanceSummary,
            'extracurriculars' => $reportCard->extracurriculars,
            'homeroomNote' => $reportCard->homeroomNote,
            'signatures' => $signatures,
            'signatureSlots' => $signatureSlots,
            'parentSignature' => $parentSignature,
            'homeroomSignature' => $homeroomSignature,
            'headmasterSignature' => $headmasterSignature,
            'issuedPlace' => $issuedPlace,
            'signatureDate' => $signatureDate,
            'documentNumber' => $this->formatSettings->formatDocumentNumber(
                (string) ($reportCard->document_number ?? ''),
                $settings,
                [
                    'student_name' => $studentName,
                    'full_name' => $identity['student']['name'] ?? '',
                    'student_number' => $identity['student']['student_number'] ?? '',
                    'national_student_number' => $identity['student']['nisn'] ?? '',
                    'academic_year' => $academicYearName,
                    'semester' => $semesterName,
                ],
            ),
            'issuedDate' => $archivedOutput['issuedDate'] ?? $this->formatSettings->formatDate($reportCard->issued_date ?? $reportCard->locked_at, $settings),
            'logoDataUri' => is_array($archivedOutput) ? $archivedOutput['logoDataUri'] : $this->logoDataUri($institution, $settings->show_logo),
            'footerText' => $archivedOutput['footerText'] ?? ($settings->footer_text ?: ''),
        ];
    }

    public function html(ReportCard $reportCard): string
    {
        return $this->htmlFromData($this->data($reportCard));
    }

    public function pdf(ReportCard $reportCard): string
    {
        return $this->engine->render($this->html($reportCard));
    }

    public function identityPdf(ReportCard $reportCard): string
    {
        return $this->engine->render(view('reports.report-card.identity', $this->data($reportCard, false))->render());
    }

    public function gradesPdf(ReportCard $reportCard): string
    {
        return $this->engine->render(view('reports.report-card.grades', $this->data($reportCard, false))->render());
    }

    /** @param array<string, mixed> $data */
    public function htmlFromData(array $data): string
    {
        return view('reports.report-card.pdf', $data)->render();
    }

    public function filename(ReportCard $reportCard): string
    {
        $reportCard->loadMissing([
            'enrollment.student',
            'semester',
        ]);

        $yearName = $reportCard->semester?->academicYear?->name ?? 'tahun-pelajaran';
        $semester = $reportCard->semester?->code?->value ?? $reportCard->semester?->code ?? $reportCard->semester?->name ?? 'semester';
        $studentNumber = $reportCard->enrollment?->student?->student_number ?? 'siswa';

        return sprintf(
            'rapor-%s-%s-%s-%d.pdf',
            $this->segment($yearName),
            Str::slug((string) $semester),
            Str::slug($studentNumber),
            $reportCard->getKey(),
        );
    }

    /** @return array{path: string, filename: string, count: int} */
    public function batchArchive(AcademicClass $academicClass, Semester $semester): array
    {
        $reportCards = ReportCard::query()
            ->with([
                'enrollment.student',
                'enrollment.academicClass.program.institution',
                'semester.academicYear',
            ])
            ->where('semester_id', $semester->getKey())
            ->whereHas('enrollment', fn ($query) => $query->where('class_id', $academicClass->getKey()))
            ->join('enrollments', 'enrollments.id', '=', 'report_cards.enrollment_id')
            ->join('students', 'students.id', '=', 'enrollments.student_id')
            ->orderBy('students.full_name')
            ->orderBy('report_cards.id')
            ->select('report_cards.*')
            ->get();

        if ($reportCards->isEmpty()) {
            throw new ReportCardPdfException('Belum ada rapor untuk kelas dan semester yang dipilih.');
        }

        // Validate every report before creating the archive so a batch never becomes partial.
        $payloads = $reportCards->mapWithKeys(fn (ReportCard $reportCard): array => [
            $reportCard->getKey() => [
                'reportCard' => $reportCard,
                'data' => $this->data($reportCard),
            ],
        ]);

        $archivePath = tempnam(sys_get_temp_dir(), 'erapor-batch-');
        if ($archivePath === false) {
            throw new ReportCardPdfException('Folder sementara untuk batch PDF tidak dapat dibuat.');
        }

        $zip = new \ZipArchive;
        if ($zip->open($archivePath, \ZipArchive::OVERWRITE) !== true) {
            @unlink($archivePath);
            throw new ReportCardPdfException('Arsip batch PDF tidak dapat dibuat.');
        }

        try {
            foreach ($payloads as $payload) {
                /** @var ReportCard $reportCard */
                $reportCard = $payload['reportCard'];
                $pdf = $this->engine->render($this->htmlFromData($payload['data']));
                $zip->addFromString($this->filename($reportCard), $pdf);
            }

            $zip->close();
        } catch (\Throwable $exception) {
            $zip->close();
            @unlink($archivePath);
            throw $exception;
        }

        return [
            'path' => $archivePath,
            'filename' => sprintf(
                'rapor-%s-%s.zip',
                $this->segment($academicClass->code ?: $academicClass->name),
                Str::slug((string) ($semester->code?->value ?? $semester->code ?? $semester->name)),
            ),
            'count' => $payloads->count(),
        ];
    }

    private function assertPrintable(ReportCard $reportCard): void
    {
        if (! in_array($reportCard->status, self::PRINTABLE_STATUSES, true)) {
            throw new ReportCardPdfException('Status rapor tidak mendukung pembuatan PDF.');
        }

        if ($reportCard->status !== 'LOCKED') {
            return;
        }

        $snapshot = $reportCard->getAttribute('identity_snapshot');
        if (is_string($snapshot)) {
            $snapshot = json_decode($snapshot, true);
        }

        if (! is_array($snapshot) || blank($snapshot['student']['name'] ?? null)) {
            throw new ReportCardPdfException('Snapshot identitas rapor terkunci belum tersedia.');
        }
    }

    private function institution(ReportCard $reportCard): Institution
    {
        $institution = $reportCard->enrollment?->academicClass?->program?->institution;

        if (! $institution instanceof Institution) {
            throw new ReportCardPdfException('PKBM pada rapor tidak ditemukan.');
        }

        return $institution;
    }

    /** @return Collection<int, array<string, mixed>> */
    private function signatureRows(ReportCard $reportCard, object $settings): Collection
    {
        $signatures = $reportCard->signatures->map(fn ($signature): array => [
            'type' => $signature->signer_type,
            'label' => $signature->signer_title_snapshot ?: $this->signatureLabel($signature->signer_type),
            'name' => $signature->signer_name_snapshot,
            'employee_number' => $signature->signer_employee_number_snapshot,
            'imageDataUri' => $this->signatureImageDataUri($signature->signature_image_path),
            'signed_at' => $signature->signed_at,
        ])->values();

        if ($reportCard->status === 'LOCKED' && $signatures->isEmpty()) {
            throw new ReportCardPdfException('Snapshot penandatangan rapor terkunci belum tersedia.');
        }

        return $signatures;
    }

    /** @return Collection<int, array<string, mixed>> */
    private function emptySignatureSlots(object $settings, ReportCard $reportCard, Institution $institution): Collection
    {
        $teacher = $reportCard->enrollment?->academicClass?->homeroomTeacher;
        $headSetting = $institution->signatorySetting;
        $headUser = $headSetting?->headmasterUser;
        $headIsManual = $headSetting?->headmaster_source?->value === 'MANUAL';
        $headName = $headIsManual ? $headSetting?->headmaster_name : $headUser?->name;
        $headNumber = $headIsManual ? $headSetting?->headmaster_employee_number : $headUser?->staffProfile?->employee_number;
        $slots = collect([
            ['type' => 'PARENT_GUARDIAN', 'label' => 'Orang Tua/Wali', 'name' => null, 'employee_number' => null, 'imageDataUri' => null, 'signed_at' => null],
            ...collect([[$settings->signature_left_role, $settings->signature_left_label], [$settings->signature_right_role, $settings->signature_right_label]])->map(fn ($slot) => [
                'type' => $slot[0], 'label' => $slot[1],
                'name' => match ($slot[0]) {
                    'GURU' => $teacher?->name, 'KEPSEK' => $headName, default => null
                },
                'employee_number' => match ($slot[0]) {
                    'GURU' => $teacher?->staffProfile?->employee_number, 'KEPSEK' => $headNumber, default => null
                },
                'imageDataUri' => null, 'signed_at' => null,
            ])->all(),
        ]);

        return $slots->unique('type')->values();
    }

    private function signatureLabel(string $type): string
    {
        return match ($type) {
            'HEAD_OF_PKBM' => 'Kepala PKBM',
            'HOMEROOM_TEACHER' => 'Wali Kelas',
            'PARENT_GUARDIAN' => 'Orang Tua/Wali',
            default => 'Penandatangan',
        };
    }

    private function logoDataUri(Institution $institution, bool $showLogo): ?string
    {
        if (! $showLogo || blank($institution->logo_path)) {
            return null;
        }

        $path = Storage::disk('public')->path((string) $institution->logo_path);
        if (! is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            return null;
        }

        return 'data:'.(mime_content_type($path) ?: 'image/png').';base64,'.base64_encode($contents);
    }

    private function signatureImageDataUri(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        $absolute = public_path('storage/'.ltrim($path, '/'));
        if (! is_file($absolute)) {
            return null;
        }

        $contents = file_get_contents($absolute);
        if ($contents === false) {
            return null;
        }

        return 'data:'.(mime_content_type($absolute) ?: 'image/png').';base64,'.base64_encode($contents);
    }

    private function segment(string $value): string
    {
        return Str::slug(str_replace(['/', '\\'], '-', $value));
    }

    /**
     * @param  array<string, mixed>  $identity
     */
    private function resolveIssuedPlace(Institution $institution, array $identity): ?string
    {
        $address = (string) ($identity['institution']['address'] ?? $institution->address ?? '');
        if (blank($address)) {
            return null;
        }

        if (preg_match('/(?:Kecamatan|Kec\.)\s+([^,]+)/i', $address, $matches)) {
            return trim($matches[1]);
        }

        if (preg_match('/(?:Kota|Kabupaten|Kab\.)\s+([^,]+)/i', $address, $matches)) {
            return trim($matches[1]);
        }

        $segments = array_values(array_filter(array_map('trim', explode(',', $address))));
        $filtered = array_values(array_filter($segments, fn ($seg) => ! preg_match('/^\d{5}$/', (string) $seg)));

        if (count($filtered) >= 2) {
            return $filtered[count($filtered) - 2];
        }

        if (count($filtered) === 1) {
            $only = $filtered[0];
            if (preg_match('/^(?:Jl\.|Jalan|Alamat)\b/i', $only)) {
                return null;
            }

            return $only;
        }

        return null;
    }
}
