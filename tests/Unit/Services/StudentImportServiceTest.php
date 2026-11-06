<?php

namespace Tests\Unit\Services;

use App\Models\Institution;
use App\Models\Student;
use App\Services\StudentImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

class StudentImportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_template_is_a_valid_xlsx_download(): void
    {
        $response = app(StudentImportService::class)->templateResponse();
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertSame('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
        $path = tempnam(sys_get_temp_dir(), 'template-test-');
        file_put_contents($path, $content);
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true);
        $this->assertStringContainsString('NIPD', (string) $zip->getFromName('xl/worksheets/sheet1.xml'));
        $zip->close();
        unlink($path);
    }

    public function test_invalid_header_is_rejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Header file tidak valid');

        app(StudentImportService::class)->preview(
            $this->csv("Salah,Nama Lengkap\n1,Ani\n"),
            Institution::query()->create(['name' => 'PKBM Header']),
        );
    }

    public function test_xlsx_rows_are_parsed(): void
    {
        $institution = Institution::query()->create(['name' => 'PKBM XLSX']);
        $preview = app(StudentImportService::class)->preview(
            $this->xlsx('<row r="1"><c r="A1" t="inlineStr"><is><t>NIPD</t></is></c><c r="B1" t="inlineStr"><is><t>Nama Lengkap</t></is></c></row><row r="2"><c r="A2" t="inlineStr"><is><t>XLSX-001</t></is></c><c r="B2" t="inlineStr"><is><t>Siswa XLSX</t></is></c></row>'),
            $institution,
        );

        $this->assertSame(1, $preview['valid_count']);
        $this->assertSame('XLSX-001', $preview['rows'][0]['values']['student_number']);
    }

    public function test_preview_reports_duplicate_and_invalid_rows_then_imports_only_valid_rows(): void
    {
        $institution = Institution::query()->create(['name' => 'PKBM Import']);
        Student::query()->create(['institution_id' => $institution->id, 'student_number' => 'DB-001', 'full_name' => 'Sudah Ada']);
        $csv = "NIPD,NISN,Nama Lengkap,Jenis Kelamin,Tempat Lahir,Tanggal Lahir,Alamat,Nama Wali,Status\n"
            ."DB-001,1234567890,Duplikat,L,,2000-01-01,,,,ACTIVE\n"
            ."NEW-001,1234567891,Siswa Valid,P,Jakarta,2005-02-03,Alamat,Wali,ACTIVE\n"
            ."NEW-002,1234567892,Siswa Salah,X,Jakarta,not-a-date,,,,ACTIVE\n";

        $preview = app(StudentImportService::class)->preview($this->csv($csv), $institution);

        $this->assertSame(1, $preview['valid_count']);
        $this->assertSame(2, $preview['invalid_count']);
        $this->assertStringContainsString('duplikat', strtolower(implode(' ', $preview['rows'][0]['errors'])));
        $this->assertStringContainsString('Jenis kelamin', implode(' ', $preview['rows'][2]['errors']));

        $result = app(StudentImportService::class)->import($preview, $institution);
        $this->assertSame(['imported' => 1, 'skipped' => 2], $result);
        $this->assertDatabaseHas('students', ['institution_id' => $institution->id, 'student_number' => 'NEW-001', 'gender' => 'P']);
        $this->assertDatabaseCount('students', 2);
    }

    public function test_error_report_is_downloadable_csv(): void
    {
        $institution = Institution::query()->create(['name' => 'PKBM Error']);
        $preview = app(StudentImportService::class)->preview(
            $this->csv("NIPD,Nama Lengkap,Jenis Kelamin\nBAD,Bad,X\n"),
            $institution,
        );
        $response = app(StudentImportService::class)->errorReportResponse($preview);
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertSame('text/csv; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Kesalahan', $content);
        $this->assertStringContainsString('Jenis kelamin', $content);
    }

    private function csv(string $content): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('peserta.csv', $content);
    }

    private function xlsx(string $sheetRows): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx-test-');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
        $zip->addFromString('xl/workbook.xml', '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Peserta Didik" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Target="worksheets/sheet1.xml"/></Relationships>');
        $zip->addFromString('xl/worksheets/sheet1.xml', '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'.$sheetRows.'</sheetData></worksheet>');
        $zip->close();
        $content = file_get_contents($path);
        unlink($path);

        return UploadedFile::fake()->createWithContent('peserta.xlsx', $content);
    }
}
