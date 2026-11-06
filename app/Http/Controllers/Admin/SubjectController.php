<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSubjectRequest;
use App\Http\Requests\Admin\UpdateSubjectRequest;
use App\Models\Institution;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function index(Request $request): View
    {
        $institution = $this->institution($request);
        Gate::authorize('viewAny', Subject::class);

        $subjects = $institution->subjects()
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = trim((string) $request->string('search'));
                $query->where(function ($query) use ($search): void {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('short_name', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('active'), fn ($query) => $query->where('active', $request->boolean('active')))
            ->orderBy('display_order')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.subjects.index', [
            'subjects' => $subjects,
            'modalSubject' => new Subject(['display_order' => 1, 'active' => true]),
        ]);
    }

    public function create(Request $request): View
    {
        $institution = $this->institution($request);
        Gate::authorize('create', Subject::class);

        return view('admin.subjects.create', [
            'subject' => new Subject([
                'display_order' => ($institution->subjects()->max('display_order') ?? 0) + 1,
                'active' => true,
            ]),
        ]);
    }

    public function store(StoreSubjectRequest $request): RedirectResponse
    {
        $institution = $this->institution($request);
        Gate::authorize('create', Subject::class);

        $subject = $institution->subjects()->create($request->validated());

        return redirect()
            ->route('admin.subjects.show', $subject)
            ->with('status', 'Mata pelajaran berhasil ditambahkan.');
    }

    public function show(Request $request, Subject $subject): View
    {
        $this->authorizeSubject($request, $subject, 'view');

        $subject->loadCount('assignments');

        return view('admin.subjects.show', compact('subject'));
    }

    public function edit(Request $request, Subject $subject): View
    {
        $this->authorizeSubject($request, $subject, 'update');

        return view('admin.subjects.edit', compact('subject'));
    }

    public function update(UpdateSubjectRequest $request, Subject $subject): RedirectResponse
    {
        $this->authorizeSubject($request, $subject, 'update');
        $subject->update($request->validated());

        return redirect()
            ->route('admin.subjects.show', $subject)
            ->with('status', 'Mata pelajaran berhasil diperbarui.');
    }

    public function destroy(Request $request, Subject $subject): RedirectResponse
    {
        $this->authorizeSubject($request, $subject, 'delete');
        $subject->delete();

        return redirect()
            ->route('admin.subjects.index')
            ->with('status', 'Mata pelajaran berhasil dihapus.');
    }

    private function institution(Request $request): Institution
    {
        $institution = $request->user()?->institution;

        abort_unless($institution !== null, 404);

        return $institution;
    }

    private function authorizeSubject(Request $request, Subject $subject, string $ability): void
    {
        $this->institution($request);
        Gate::authorize($ability, $subject);
    }
}
