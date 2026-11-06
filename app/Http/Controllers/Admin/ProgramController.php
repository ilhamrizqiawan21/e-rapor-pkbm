<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProgramRequest;
use App\Http\Requests\Admin\UpdateProgramRequest;
use App\Models\Institution;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProgramController extends Controller
{
    public function index(Request $request): View
    {
        $institution = $this->institution($request);
        Gate::authorize('viewAny', Program::class);

        $programs = $institution->programs()
            ->withCount('levels')
            ->orderBy('code')
            ->paginate(20);

        return view('admin.programs.index', compact('programs'));
    }

    public function create(Request $request): View
    {
        $this->institution($request);
        Gate::authorize('create', Program::class);

        return view('admin.programs.create', ['program' => new Program]);
    }

    public function store(StoreProgramRequest $request): RedirectResponse
    {
        $institution = $this->institution($request);
        Gate::authorize('create', Program::class);

        $program = $institution->programs()->create($request->validated());

        return redirect()
            ->route('admin.programs.show', $program)
            ->with('status', 'Program berhasil ditambahkan.');
    }

    public function show(Request $request, Program $program): View
    {
        $this->authorizeProgram($request, $program, 'view');

        $program->load(['levels' => fn ($query) => $query->orderBy('number')]);

        return view('admin.programs.show', compact('program'));
    }

    public function edit(Request $request, Program $program): View
    {
        $this->authorizeProgram($request, $program, 'update');

        return view('admin.programs.edit', compact('program'));
    }

    public function update(UpdateProgramRequest $request, Program $program): RedirectResponse
    {
        $this->authorizeProgram($request, $program, 'update');
        $program->update($request->validated());

        return redirect()
            ->route('admin.programs.show', $program)
            ->with('status', 'Program berhasil diperbarui.');
    }

    public function destroy(Request $request, Program $program): RedirectResponse
    {
        $this->authorizeProgram($request, $program, 'delete');
        $program->delete();

        return redirect()
            ->route('admin.programs.index')
            ->with('status', 'Program berhasil dihapus.');
    }

    private function institution(Request $request): Institution
    {
        $institution = $request->user()?->institution;

        abort_unless($institution !== null, 404);

        return $institution;
    }

    private function authorizeProgram(Request $request, Program $program, string $ability): void
    {
        $this->institution($request);
        Gate::authorize($ability, $program);
    }
}
