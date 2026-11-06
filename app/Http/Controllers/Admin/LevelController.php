<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLevelRequest;
use App\Http\Requests\Admin\UpdateLevelRequest;
use App\Models\Level;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LevelController extends Controller
{
    public function index(Request $request, Program $program): View
    {
        $this->authorizeProgram($request, $program, 'view');

        $levels = $program->levels()
            ->orderBy('number')
            ->paginate(20);

        return view('admin.programs.levels.index', compact('program', 'levels'));
    }

    public function create(Request $request, Program $program): View
    {
        $this->authorizeProgram($request, $program, 'update');

        return view('admin.programs.levels.create', [
            'program' => $program,
            'level' => new Level(['program_id' => $program->getKey()]),
        ]);
    }

    public function store(StoreLevelRequest $request, Program $program): RedirectResponse
    {
        $this->authorizeProgram($request, $program, 'update');

        $program->levels()->create($request->validated());

        return redirect()
            ->route('admin.programs.show', $program)
            ->with('status', 'Tingkat berhasil ditambahkan.');
    }

    public function show(Request $request, Program $program, Level $level): View
    {
        $this->authorizeLevel($request, $program, $level, 'view');

        return view('admin.programs.levels.show', compact('program', 'level'));
    }

    public function edit(Request $request, Program $program, Level $level): View
    {
        $this->authorizeLevel($request, $program, $level, 'update');

        return view('admin.programs.levels.edit', compact('program', 'level'));
    }

    public function update(
        UpdateLevelRequest $request,
        Program $program,
        Level $level,
    ): RedirectResponse {
        $this->authorizeLevel($request, $program, $level, 'update');
        $level->update($request->validated());

        return redirect()
            ->route('admin.programs.show', $program)
            ->with('status', 'Tingkat berhasil diperbarui.');
    }

    public function destroy(Request $request, Program $program, Level $level): RedirectResponse
    {
        $this->authorizeLevel($request, $program, $level, 'delete');
        $level->delete();

        return redirect()
            ->route('admin.programs.show', $program)
            ->with('status', 'Tingkat berhasil dihapus.');
    }

    private function authorizeProgram(Request $request, Program $program, string $ability): void
    {
        abort_unless($request->user()?->institution !== null, 404);
        Gate::authorize($ability, $program);
    }

    private function authorizeLevel(
        Request $request,
        Program $program,
        Level $level,
        string $ability,
    ): void {
        abort_unless($level->program_id === $program->getKey(), 404);
        $this->authorizeProgram($request, $program, 'view');
        Gate::authorize($ability, $level);
    }
}
