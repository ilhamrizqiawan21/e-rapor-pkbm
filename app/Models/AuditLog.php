<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable(['institution_id', 'report_card_id', 'user_id', 'action', 'entity_type', 'entity_id', 'old_value_json', 'new_value_json', 'reason'])]
class AuditLog extends Model
{
    protected function casts(): array
    {
        return ['old_value_json' => 'array', 'new_value_json' => 'array'];
    }

    protected static function booted(): void
    {
        static::updating(static function (): never {
            throw new LogicException('Riwayat audit bersifat immutable.');
        });

        static::deleting(static function (): never {
            throw new LogicException('Riwayat audit tidak dapat dihapus.');
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reportCard(): BelongsTo
    {
        return $this->belongsTo(ReportCard::class);
    }
}
