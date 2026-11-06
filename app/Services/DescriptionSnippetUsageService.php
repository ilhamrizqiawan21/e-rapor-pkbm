<?php

namespace App\Services;

use App\Enums\DescriptionSnippetVisibility;
use App\Models\DescriptionSnippet;
use App\Models\ReportCard;
use App\Models\ReportGrade;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use LogicException;

/**
 * Applies reusable descriptions without bypassing grade ownership or workflow
 * editability rules.
 */
final class DescriptionSnippetUsageService
{
    /**
     * Return active snippets the actor may use for a subject.
     *
     * A subject-less snippet is available for every subject in the actor's
     * institution. Subject-specific snippets are restricted to that subject.
     * Private snippets are owner-only; shared snippets are institution-wide.
     *
     * @return Collection<int, DescriptionSnippet>
     */
    public function listUsableSnippets(User $actor, ?Subject $subject = null): Collection
    {
        $this->assertActiveInstitutionUser($actor);

        if ($subject !== null && $subject->institution_id !== $actor->institution_id) {
            throw new AuthorizationException('Mata pelajaran bukan bagian dari institusi pengguna.');
        }

        return DescriptionSnippet::query()
            ->where('institution_id', $actor->institution_id)
            ->where('active', true)
            ->when($subject, function ($query) use ($subject): void {
                $query->where(function ($subjectQuery) use ($subject): void {
                    $subjectQuery
                        ->whereNull('subject_id')
                        ->orWhere('subject_id', $subject->getKey());
                });
            })
            ->where(function ($visibilityQuery) use ($actor): void {
                $visibilityQuery
                    ->where('visibility', DescriptionSnippetVisibility::Shared->value)
                    ->orWhere(function ($privateQuery) use ($actor): void {
                        $privateQuery
                            ->where('visibility', DescriptionSnippetVisibility::Private->value)
                            ->where('owner_user_id', $actor->getKey());
                    });
            })
            ->with(['subject', 'owner'])
            ->orderBy('title')
            ->orderBy('id')
            ->get();
    }

    /**
     * Copy snippet content into a grade while retaining the source reference.
     */
    public function applySnippet(User $actor, ReportGrade $grade, DescriptionSnippet $snippet): ReportGrade
    {
        return DB::transaction(function () use ($actor, $grade, $snippet): ReportGrade {
            $lockedGrade = $this->lockGrade($grade);
            $this->assertGradeAccess($actor, $lockedGrade->reportCard);
            $this->assertEditable($lockedGrade->reportCard);
            $this->assertSnippetUsable($actor, $snippet, $lockedGrade->subjectAssignment->subject_id);

            $lockedGrade->update([
                'description_snippet_id' => $snippet->getKey(),
                'description' => $snippet->content,
            ]);

            return $lockedGrade->fresh(['descriptionSnippet', 'subjectAssignment.subject']);
        });
    }

    /**
     * Update only the final text. The original snippet and its traceability
     * reference remain untouched.
     */
    public function updateFinalDescription(User $actor, ReportGrade $grade, ?string $description): ReportGrade
    {
        return DB::transaction(function () use ($actor, $grade, $description): ReportGrade {
            $lockedGrade = $this->lockGrade($grade);
            $this->assertGradeAccess($actor, $lockedGrade->reportCard);
            $this->assertEditable($lockedGrade->reportCard);
            $this->validateDescription($description);

            $lockedGrade->update(['description' => $description]);

            return $lockedGrade->fresh(['descriptionSnippet', 'subjectAssignment.subject']);
        });
    }

    /**
     * Save the current final description as an independent reusable snippet.
     * The report grade is never changed by this operation.
     */
    public function saveAsSnippet(
        User $actor,
        ReportGrade $grade,
        string $title,
        string|DescriptionSnippetVisibility $visibility = DescriptionSnippetVisibility::Private,
    ): DescriptionSnippet {
        $visibility = $visibility instanceof DescriptionSnippetVisibility ? $visibility->value : $visibility;

        return DB::transaction(function () use ($actor, $grade, $title, $visibility): DescriptionSnippet {
            $lockedGrade = $this->lockGrade($grade);
            $this->assertGradeAccess($actor, $lockedGrade->reportCard);
            $this->assertEditable($lockedGrade->reportCard);
            $this->validateSnippetInput($title, $lockedGrade->description, $visibility, $actor);

            $lockedGrade->loadMissing('subjectAssignment.academicClass.program');

            return DescriptionSnippet::query()->create([
                'institution_id' => $actor->institution_id,
                'owner_user_id' => $actor->getKey(),
                'subject_id' => $lockedGrade->subjectAssignment->subject_id,
                'title' => $title,
                'content' => $lockedGrade->description,
                'visibility' => $visibility,
                'active' => true,
            ]);
        });
    }

    private function lockGrade(ReportGrade $grade): ReportGrade
    {
        // Locking the row for the complete read-modify-write sequence prevents
        // two tutors from silently overwriting a description update.
        return ReportGrade::query()
            ->whereKey($grade->getKey())
            ->with([
                'reportCard.enrollment.academicClass.program',
                'subjectAssignment.subject',
            ])
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function assertGradeAccess(User $actor, ReportCard $reportCard): void
    {
        Gate::forUser($actor)->authorize('view', $reportCard);
    }

    private function assertEditable(ReportCard $reportCard): void
    {
        if (! in_array($reportCard->status, ['DRAFT', 'NEEDS_REVISION'], true)) {
            throw new LogicException('Deskripsi nilai hanya dapat diubah saat rapor berstatus Draft atau Perlu Perbaikan.');
        }
    }

    private function assertActiveInstitutionUser(User $actor): void
    {
        if (! $actor->isActive() || $actor->institution_id === null) {
            throw new AuthorizationException('Pengguna tidak aktif atau belum memiliki institusi.');
        }
    }

    private function assertSnippetUsable(User $actor, DescriptionSnippet $snippet, int $subjectId): void
    {
        $this->assertActiveInstitutionUser($actor);

        if ($snippet->institution_id !== $actor->institution_id || ! $snippet->active) {
            throw new AuthorizationException('Snippet tidak tersedia untuk pengguna ini.');
        }

        if ($snippet->subject_id !== null && $snippet->subject_id !== $subjectId) {
            throw new AuthorizationException('Snippet tidak sesuai dengan mata pelajaran nilai.');
        }

        $isShared = $snippet->visibility === DescriptionSnippetVisibility::Shared;
        $isOwnedPrivate = $snippet->visibility === DescriptionSnippetVisibility::Private
            && $snippet->owner_user_id === $actor->getKey();

        if (! $isShared && ! $isOwnedPrivate) {
            throw new AuthorizationException('Snippet privat hanya dapat digunakan oleh pemiliknya.');
        }
    }

    private function validateDescription(?string $description): void
    {
        Validator::make(
            ['description' => $description],
            ['description' => ['nullable', 'string', 'max:10000']],
        )->validate();
    }

    private function validateSnippetInput(string $title, ?string $description, string $visibility, User $actor): void
    {
        Validator::make(
            compact('title', 'description', 'visibility'),
            [
                'title' => ['required', 'string', 'max:255'],
                'description' => ['required', 'string', 'max:10000'],
                'visibility' => ['required', 'in:'.DescriptionSnippetVisibility::Private->value.','.DescriptionSnippetVisibility::Shared->value],
            ],
        )->validate();

        if (trim($description ?? '') === '') {
            throw ValidationException::withMessages([
                'description' => 'Deskripsi final harus diisi sebelum disimpan sebagai snippet.',
            ]);
        }

        if ($visibility === DescriptionSnippetVisibility::Shared->value && ! $actor->hasRole('ADMIN')) {
            throw new AuthorizationException('Hanya Admin yang dapat menyimpan snippet shared.');
        }
    }
}
