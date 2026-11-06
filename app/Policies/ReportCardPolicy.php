<?php

namespace App\Policies;

use App\Models\ReportCard;
use App\Models\User;

class ReportCardPolicy
{
    private const EDITABLE_STATUSES = ['DRAFT', 'SUBMITTED', 'IN_REVIEW', 'NEEDS_REVISION'];

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isActive() && $user->institution_id !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ReportCard $reportCard): bool
    {
        return $this->hasClassAccess($user, $reportCard);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isActive() && $user->hasAnyRole(['ADMIN', 'GURU']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ReportCard $reportCard): bool
    {
        return in_array($reportCard->status, self::EDITABLE_STATUSES, true)
            && $this->hasClassAccess($user, $reportCard);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ReportCard $reportCard): bool
    {
        return $reportCard->status !== 'LOCKED'
            && $this->hasClassAccess($user, $reportCard)
            && $user->hasRole('ADMIN');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ReportCard $reportCard): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ReportCard $reportCard): bool
    {
        return false;
    }

    public function review(User $user, ReportCard $reportCard): bool
    {
        return $this->hasClassAccess($user, $reportCard) && $user->hasRole('KEPSEK');
    }

    public function approve(User $user, ReportCard $reportCard): bool
    {
        return in_array($reportCard->status, ['SUBMITTED', 'IN_REVIEW'], true)
            && $this->hasClassAccess($user, $reportCard)
            && $user->hasRole('KEPSEK');
    }

    public function lock(User $user, ReportCard $reportCard): bool
    {
        return $reportCard->status === 'APPROVED'
            && $this->belongsToInstitution($user, $reportCard)
            && $user->hasRole('ADMIN');
    }

    public function unlock(User $user, ReportCard $reportCard): bool
    {
        return $reportCard->status === 'LOCKED'
            && $this->belongsToInstitution($user, $reportCard)
            && $user->hasRole('ADMIN');
    }

    private function hasClassAccess(User $user, ReportCard $reportCard): bool
    {
        if (! $this->belongsToInstitution($user, $reportCard)) {
            return false;
        }

        if ($user->hasAnyRole(['ADMIN', 'KEPSEK'])) {
            return true;
        }

        $academicClass = $reportCard->enrollment?->academicClass;

        return $academicClass !== null
            && ($academicClass->homeroom_teacher_id === $user->getKey()
                || $academicClass->subjectAssignments()->where('teacher_id', $user->getKey())->exists());
    }

    private function belongsToInstitution(User $user, ReportCard $reportCard): bool
    {
        return $user->isActive()
            && $reportCard->enrollment?->academicClass?->program?->institution_id === $user->institution_id;
    }
}
