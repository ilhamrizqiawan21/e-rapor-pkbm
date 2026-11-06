<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PreviewPredicateRequest;
use App\Http\Requests\Admin\StorePredicateRangeRequest;
use App\Http\Requests\Admin\UpdatePredicateRangeRequest;
use App\Models\Institution;
use App\Models\PredicateRange;
use App\Services\PredicateRangeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PredicateRangeController extends Controller
{
    public function __construct(private readonly PredicateRangeService $ranges) {}

    public function index(Request $request): View
    {
        $this->authorizeAdmin($request);
        $institution = $this->institution($request);
        $this->ranges->ensureDefaults($institution);

        return view('admin.predicate-ranges.index', [
            'ranges' => $this->ranges->query($institution)->get(),
            'institution' => $institution,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorizeAdmin($request);

        return view('admin.predicate-ranges.create', [
            'range' => new PredicateRange(['minimum_score' => 0, 'maximum_score' => 100, 'display_order' => 1, 'active' => true]),
        ]);
    }

    public function store(StorePredicateRangeRequest $request): RedirectResponse
    {
        $this->ranges->create($this->institution($request), $request->validated());

        return redirect()->route('admin.predicate-ranges.index')->with('status', 'Rentang predikat berhasil ditambahkan.');
    }

    public function edit(Request $request, PredicateRange $predicateRange): View
    {
        $this->authorizeRange($request, $predicateRange);

        return view('admin.predicate-ranges.edit', ['range' => $predicateRange]);
    }

    public function update(UpdatePredicateRangeRequest $request, PredicateRange $predicateRange): RedirectResponse
    {
        $this->authorizeRange($request, $predicateRange);
        $this->ranges->update($predicateRange, $request->validated());

        return redirect()->route('admin.predicate-ranges.index')->with('status', 'Rentang predikat berhasil diperbarui.');
    }

    public function destroy(Request $request, PredicateRange $predicateRange): RedirectResponse
    {
        $this->authorizeRange($request, $predicateRange);
        $predicateRange->delete();

        return redirect()->route('admin.predicate-ranges.index')->with('status', 'Rentang predikat berhasil dihapus.');
    }

    public function preview(PreviewPredicateRequest $request): View
    {
        $institution = $this->institution($request);
        $this->ranges->ensureDefaults($institution);
        $score = $request->filled('score') ? (int) $request->validated('score') : null;

        return view('admin.predicate-ranges.preview', [
            'score' => $score,
            'result' => $score === null ? null : $this->ranges->predicateFor($institution, $score),
            'ranges' => $this->ranges->query($institution, true)->get(),
        ]);
    }

    private function institution(Request $request): Institution
    {
        return $request->user()->institution;
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->isActive() && $request->user()->hasRole('ADMIN') && $request->user()->institution_id !== null, 403);
    }

    private function authorizeRange(Request $request, PredicateRange $range): void
    {
        $this->authorizeAdmin($request);
        abort_unless($range->institution_id === $request->user()->institution_id, 404);
    }
}
