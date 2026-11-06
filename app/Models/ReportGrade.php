<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable(['report_card_id', 'subject_assignment_id', 'description_snippet_id', 'subject_name_snapshot', 'subject_short_name_snapshot', 'minimum_score_snapshot', 'predicate_snapshot', 'predicate_ranges_snapshot', 'display_order_snapshot', 'score', 'predicate', 'description', 'status'])]
class ReportGrade extends Model
{
    protected static function booted(): void
    {
        static::saving(function (self $grade): void {
            if ($grade->exists && $grade->reportCard()->where(function ($query): void {
                $query->where('status', 'LOCKED')->orWhereNotNull('locked_at');
            })->exists()) {
                throw new LogicException('Nilai pada rapor terkunci tidak dapat diubah.');
            }
        });

        static::deleting(function (self $grade): void {
            if ($grade->reportCard()->where(function ($query): void {
                $query->where('status', 'LOCKED')->orWhereNotNull('locked_at');
            })->exists()) {
                throw new LogicException('Nilai pada rapor terkunci tidak dapat dihapus.');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'minimum_score_snapshot' => 'integer',
            'display_order_snapshot' => 'integer',
            'predicate_ranges_snapshot' => 'array',
        ];
    }

    public function reportCard(): BelongsTo
    {
        return $this->belongsTo(ReportCard::class);
    }
}
