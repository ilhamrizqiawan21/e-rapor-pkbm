<?php

namespace App\Policies;

use App\Models\ReportCard;
use App\Models\User;

/**
 * Encodes report-card workflow permissions and the immutable LOCKED boundary.
 */
class ReportCardPolicy
{
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
        return $user->isActive() && $user->institution_id !== null && $user->hasAnyRole(['ADMIN', 'GURU']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ReportCard $reportCard): bool
    {
        // Locked cards are final output; no role may edit them through normal
        // entry endpoints, including Admin.
        if (! $this->belongsToInstitution($user, $reportCard) || $reportCard->status === 'LOCKED') {
            return false;
        }

        if ($user->hasRole('ADMIN')) {
            return true;
        }

        return $user->hasRole('GURU')
            && $reportCard->status !== 'LOCKED'
            && $this->hasClassAccess($user, $reportCard);
    }

    public function updateAttitude(User $user, ReportCard $reportCard): bool
    {
        return $this->isDataEntryStatus($user, $reportCard)
            && $this->hasHomeroomSurfaceAccess($user, $reportCard);
    }

    public function updateAttendance(User $user, ReportCard $reportCard): bool
    {
        return $this->isDataEntryStatus($user, $reportCard)
            && $this->hasHomeroomSurfaceAccess($user, $reportCard);
    }

    public function manageCompleteness(User $user, ReportCard $reportCard): bool
    {
        if (! $this->belongsToInstitution($user, $reportCard) || $reportCard->status === 'LOCKED') {
            return false;
        }

        if ($user->hasRole('ADMIN')) {
            return true;
        }

        return $user->hasRole('GURU')
            && $reportCard->status !== 'LOCKED'
            && $reportCard->enrollment?->academicClass?->homeroom_teacher_id === $user->getKey();
    }

    /**
     * Determine whether the user can export or print the report card.
     *
     * Report output belongs to the homeroom workflow. Subject teachers may
     * still enter grades, but they do not publish a class report.
     */
    public function export(User $user, ReportCard $reportCard): bool
    {
        if (! $this->belongsToInstitution($user, $reportCard)) {
            return false;
        }

        if ($user->hasAnyRole(['ADMIN', 'KEPSEK'])) {
            return true;
        }

        return $user->hasRole('GURU')
            && $reportCard->enrollment?->academicClass?->homeroom_teacher_id === $user->getKey();
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
        return $reportCard->status === 'SUBMITTED'
            && $user->hasRole('GURU')
            && $this->hasHomeroomSurfaceAccess($user, $reportCard);
    }

    public function submit(User $user, ReportCard $reportCard): bool
    {
        return in_array($reportCard->status, ['DRAFT', 'NEEDS_REVISION'], true)
            && $this->hasClassAccess($user, $reportCard)
            && $user->hasRole('GURU');
    }

    public function reject(User $user, ReportCard $reportCard): bool
    {
        return $reportCard->status === 'IN_REVIEW'
            && $this->hasClassAccess($user, $reportCard)
            && $user->hasRole('KEPSEK');
    }

    public function approve(User $user, ReportCard $reportCard): bool
    {
        return $reportCard->status === 'IN_REVIEW'
            && $this->hasClassAccess($user, $reportCard)
            && $user->hasRole('KEPSEK');
    }

    public function lock(User $user, ReportCard $reportCard): bool
    {
        // Locking is an optional archival action; it is not an approval step.
        return $reportCard->status !== 'LOCKED'
            && $this->belongsToInstitution($user, $reportCard)
            && $user->hasRole('ADMIN');
    }

    public function unlock(User $user, ReportCard $reportCard): bool
    {
        // Reopening final output is restricted to Admin and remains auditable by
        // the workflow service.
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

    private function hasHomeroomSurfaceAccess(User $user, ReportCard $reportCard): bool
    {
        if (! $this->belongsToInstitution($user, $reportCard)) {
            return false;
        }

        if ($user->hasRole('ADMIN')) {
            return true;
        }

        $academicClass = $reportCard->enrollment?->academicClass;

        return $user->hasRole('GURU')
            && $academicClass !== null
            && $academicClass->homeroom_teacher_id === $user->getKey();
    }

    private function isDataEntryStatus(User $user, ReportCard $reportCard): bool
    {
        if (! $this->belongsToInstitution($user, $reportCard) || $reportCard->status === 'LOCKED') {
            return false;
        }

        return $user->hasRole('ADMIN')
            || ($user->hasRole('GURU') && $reportCard->status !== 'LOCKED');
    }

    private function belongsToInstitution(User $user, ReportCard $reportCard): bool
    {
        return $user->isActive()
            && $reportCard->enrollment?->academicClass?->program?->institution_id === $user->institution_id;
    }
}
