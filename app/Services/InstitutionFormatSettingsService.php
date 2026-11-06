<?php

namespace App\Services;

use App\Models\Institution;
use App\Models\InstitutionFormatSetting;
use App\Models\Student;
use DateTimeInterface;

class InstitutionFormatSettingsService
{
    /**
     * @var array<string, bool|int|string|null>
     */
    public const DEFAULTS = [
        'student_name_format' => '{full_name}',
        'document_number_format' => '{document_number}',
        'date_format' => 'd/m/Y',
        'signature_left_role' => 'GURU',
        'signature_left_label' => 'Wali Kelas',
        'signature_right_role' => 'KEPSEK',
        'signature_right_label' => 'Kepala PKBM',
        'show_logo' => true,
        'logo_position' => 'center',
        'logo_width_mm' => 25,
        'margin_top_mm' => 15,
        'margin_right_mm' => 15,
        'margin_bottom_mm' => 15,
        'margin_left_mm' => 15,
        'header_text' => null,
        'footer_text' => null,
        'show_kkm' => true,
        'show_predicate' => true,
    ];

    public function forInstitution(Institution|int $institution): InstitutionFormatSetting
    {
        $institutionId = $institution instanceof Institution ? $institution->getKey() : $institution;

        return InstitutionFormatSetting::query()->firstOrCreate(
            ['institution_id' => $institutionId],
            self::DEFAULTS,
        );
    }

    /** @param array<string, mixed> $attributes */
    public function updateForInstitution(Institution|int $institution, array $attributes): InstitutionFormatSetting
    {
        $settings = $this->forInstitution($institution);
        $settings->fill($attributes);
        $settings->save();

        return $settings->refresh();
    }

    /** @param array<string, scalar|null> $values */
    public function renderTemplate(string $template, array $values): string
    {
        $rendered = preg_replace_callback(
            '/\{([a-z][a-z0-9_]*)\}/i',
            static fn (array $matches): string => (string) ($values[strtolower($matches[1])] ?? ''),
            $template,
        ) ?? $template;

        return trim((string) preg_replace('/\s{2,}/', ' ', $rendered));
    }

    public function formatStudentName(Student $student, ?InstitutionFormatSetting $settings = null): string
    {
        $settings ??= $student->institution
            ? $this->forInstitution($student->institution)
            : null;

        return $this->renderTemplate(
            $settings?->student_name_format ?? self::DEFAULTS['student_name_format'],
            [
                'full_name' => $student->full_name,
                'student_number' => $student->student_number,
                'national_student_number' => $student->national_student_number,
            ],
        );
    }

    /** @param array<string, scalar|null> $context */
    public function formatDocumentNumber(
        string $documentNumber,
        ?InstitutionFormatSetting $settings = null,
        array $context = [],
    ): string {
        return $this->renderTemplate(
            $settings?->document_number_format ?? self::DEFAULTS['document_number_format'],
            array_merge($context, ['document_number' => $documentNumber]),
        );
    }

    public function formatDate(
        DateTimeInterface|string|null $date,
        ?InstitutionFormatSetting $settings = null,
    ): string {
        if ($date === null || $date === '') {
            return '';
        }

        $format = $settings?->date_format ?? self::DEFAULTS['date_format'];

        return $date instanceof DateTimeInterface
            ? $date->format($format)
            : date($format, strtotime($date));
    }
}
