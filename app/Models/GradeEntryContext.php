<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['institution_id', 'semester_id', 'subject_assignment_id', 'description_snippet_id', 'material'])]
class GradeEntryContext extends Model
{
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function subjectAssignment(): BelongsTo
    {
        return $this->belongsTo(SubjectAssignment::class);
    }

    public function descriptionSnippet(): BelongsTo
    {
        return $this->belongsTo(DescriptionSnippet::class);
    }
}
