<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Enrollment;
use App\Models\Institution;
use App\Models\ReportCard;
use App\Models\ReportGrade;
use App\Models\Student;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;

/**
 * Provides tenant-scoped, read-only audit history with bounded pagination.
 */
final class AuditHistoryService
{
    /** @var array<string, class-string|string> */
    public const ENTITY_TYPES = [
        'report_card' => ReportCard::class,
        'report_grade' => ReportGrade::class,
        'student' => Student::class,
        'enrollment' => Enrollment::class,
        'institution' => Institution::class,
        'user' => User::class,
        'ledger' => 'ledger',
        'backup' => 'backup',
    ];

    /** @return array{auditLogs: LengthAwarePaginator, actors: Collection<int, User>, actions: Collection<int, string>, entityTypes: array<string, class-string|string>, filters: array<string, mixed>} */
    public function page(User $actor, array $filters = []): array
    {
        // The actor's institution is the mandatory base filter; request filters
        // can narrow history but never broaden it.
        Gate::forUser($actor)->authorize('viewAny', AuditLog::class);

        $base = AuditLog::query()->where('institution_id', $actor->institution_id);

        return [
            'auditLogs' => $this->query($base, $filters)
                ->with(['user', 'reportCard.enrollment.student'])
                ->latest('created_at')
                ->latest('id')
                ->paginate(25)
                ->withQueryString(),
            'actors' => User::query()
                ->where('institution_id', $actor->institution_id)
                ->orderBy('name')
                ->orderBy('id')
                ->get(['id', 'name', 'username']),
            'actions' => (clone $base)
                ->whereNotNull('action')
                ->distinct()
                ->orderBy('action')
                ->pluck('action'),
            'entityTypes' => self::ENTITY_TYPES,
            'filters' => $filters,
        ];
    }

    /** @param Builder<AuditLog> $query */
    private function query(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['actor_id'] ?? null, fn (Builder $builder, mixed $actorId) => $builder->where('user_id', $actorId))
            ->when($filters['action'] ?? null, fn (Builder $builder, mixed $action) => $builder->where('action', $action))
            ->when($filters['date_from'] ?? null, fn (Builder $builder, mixed $date) => $builder->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $builder, mixed $date) => $builder->whereDate('created_at', '<=', $date))
            ->when($filters['entity_type'] ?? null, function (Builder $builder, mixed $entityType): void {
                if (isset(self::ENTITY_TYPES[$entityType])) {
                    $builder->where('entity_type', self::ENTITY_TYPES[$entityType]);
                }
            })
            ->when($filters['entity_id'] ?? null, fn (Builder $builder, mixed $entityId) => $builder->where('entity_id', $entityId));
    }
}
