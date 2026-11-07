<?php

namespace App\Services;

use App\Models\DescriptionSnippet;
use App\Models\Institution;
use App\Models\PredicateRange;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Owns the material-to-predicate description rules used by grade entry.
 */
final class DescriptionSnippetTemplateService
{
    public function __construct(private readonly PredicateRangeService $predicateRanges) {}

    /** @return Collection<int, PredicateRange> */
    public function activeRanges(Institution $institution): Collection
    {
        return $this->predicateRanges->ensureDefaults($institution)
            ->where('active', true)
            ->values();
    }

    public function defaultTemplate(PredicateRange $range): string
    {
        return 'Siswa '.Str::lower($range->label).' dalam {material}.';
    }

    /**
     * Persist a new or existing material bank and replace its predicate rules
     * atomically so a partial set of rules can never be exposed to teachers.
     *
     * @param  array<string, mixed>  $data
     */
    public function save(Institution $institution, array $data, ?DescriptionSnippet $snippet = null): DescriptionSnippet
    {
        $ranges = $this->activeRanges($institution);
        $templates = collect(is_array($data['templates'] ?? null) ? $data['templates'] : [])
            ->mapWithKeys(fn ($template, $code): array => [strtoupper((string) $code) => trim((string) $template)])
            ->filter(fn (string $template): bool => $template !== '');
        $allowedCodes = $ranges->pluck('code')->map(fn ($code): string => strtoupper((string) $code));

        $unknownCodes = $templates->keys()->diff($allowedCodes);
        if ($unknownCodes->isNotEmpty()) {
            throw ValidationException::withMessages(['templates' => 'Template predikat tidak sesuai dengan konfigurasi institusi.']);
        }

        if (blank($data['material'] ?? null) && $templates->isNotEmpty()) {
            throw ValidationException::withMessages(['material' => 'Materi wajib diisi untuk bank deskripsi baru.']);
        }

        return DB::transaction(function () use ($institution, $data, $snippet, $ranges, $templates): DescriptionSnippet {
            $firstTemplate = $templates->first();
            $material = trim((string) ($data['material'] ?? '')) ?: null;
            $legacyContent = trim((string) ($data['content'] ?? ''));
            $content = $legacyContent !== ''
                ? $legacyContent
                : ($firstTemplate !== null ? $this->renderText($firstTemplate, $material) : null);
            $attributes = [
                ...$data,
                'institution_id' => $institution->getKey(),
                'material' => $material,
                'content' => $content,
            ];
            unset($attributes['templates']);

            $snippet ??= new DescriptionSnippet;
            $snippet->fill($attributes);
            $snippet->institution_id = $institution->getKey();
            $snippet->save();
            $snippet->templates()->delete();

            foreach ($ranges as $range) {
                $code = strtoupper((string) $range->code);
                $template = $templates->get($code);
                if ($template === null) {
                    continue;
                }
                $snippet->templates()->create([
                    'predicate_code' => $code,
                    'predicate_label' => $range->label,
                    'template' => $template,
                    'active' => true,
                ]);
            }

            return $snippet->fresh(['templates', 'subject', 'owner']);
        });
    }

    public function render(DescriptionSnippet $snippet, ?int $score = null, ?string $predicateCode = null): string
    {
        $snippet->loadMissing('templates');
        $code = $predicateCode;
        if ($code === null && $score !== null) {
            $snippet->loadMissing('institution');
            $code = $this->predicateRanges->predicateFor($snippet->institution, $score)?->code;
        }

        $template = $code === null
            ? null
            : $snippet->templates->firstWhere('predicate_code', strtoupper($code));

        return $template === null
            ? (string) ($snippet->content ?? '')
            : $this->renderText($template->template, $snippet->material);
    }

    /** @return array<string, string> */
    public function templatesFor(DescriptionSnippet $snippet): array
    {
        $snippet->loadMissing('templates');

        return $snippet->templates
            ->where('active', true)
            ->mapWithKeys(fn ($template): array => [$template->predicate_code => $template->template])
            ->all();
    }

    private function renderText(string $template, ?string $material): string
    {
        return trim(str_replace('{material}', trim((string) $material), $template));
    }
}
