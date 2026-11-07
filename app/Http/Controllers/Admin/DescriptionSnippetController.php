<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DescriptionSnippetVisibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDescriptionSnippetRequest;
use App\Http\Requests\Admin\UpdateDescriptionSnippetRequest;
use App\Models\DescriptionSnippet;
use App\Models\Institution;
use App\Models\PredicateRange;
use App\Models\User;
use App\Services\DescriptionSnippetTemplateService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DescriptionSnippetController extends Controller
{
    public function index(Request $request, DescriptionSnippetTemplateService $templates): View
    {
        $institution = $this->institution($request);
        $user = $request->user();
        Gate::authorize('viewAny', DescriptionSnippet::class);
        $predicateRanges = $templates->activeRanges($institution);

        $snippets = DescriptionSnippet::query()
            ->visibleTo($user)
            ->with(['subject:id,name,short_name', 'owner:id,name'])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = trim((string) $request->string('search'));
                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('material', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('subject_id'), fn ($query) => $query->where('subject_id', (int) $request->input('subject_id')))
            ->when($request->input('ownership') === 'mine', fn ($query) => $query->private()->ownedBy($user))
            ->when($request->input('ownership') === 'shared', fn ($query) => $query->shared())
            ->when($request->filled('active'), fn ($query) => $query->where('active', $request->boolean('active')))
            ->orderByDesc('active')
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.description-snippets.index', [
            'snippets' => $snippets,
            'subjects' => $this->subjects($institution),
            'teachers' => User::query()
                ->where('institution_id', $institution->getKey())
                ->whereHas('roles', fn ($query) => $query->where('code', 'GURU'))
                ->where('status', 'ACTIVE')
                ->orderBy('name')
                ->get(['id', 'name']),
            'modalSnippet' => new DescriptionSnippet([
                'visibility' => $user->hasRole('ADMIN') ? DescriptionSnippetVisibility::Shared : DescriptionSnippetVisibility::Private,
                'active' => true,
            ]),
            'predicateRanges' => $predicateRanges,
            'templateDefaults' => $this->templateDefaults($predicateRanges, $templates),
        ]);
    }

    public function create(Request $request, DescriptionSnippetTemplateService $templates): View
    {
        $institution = $this->institution($request);
        Gate::authorize('create', DescriptionSnippet::class);

        return view('admin.description-snippets.create', $this->formData(
            $institution,
            new DescriptionSnippet([
                'visibility' => $request->user()->hasRole('ADMIN')
                    ? DescriptionSnippetVisibility::Shared
                    : DescriptionSnippetVisibility::Private,
                'active' => true,
            ]),
            $templates,
        ));
    }

    public function store(StoreDescriptionSnippetRequest $request, DescriptionSnippetTemplateService $templates): RedirectResponse
    {
        $institution = $this->institution($request);
        Gate::authorize('create', DescriptionSnippet::class);

        $data = $this->roleScopedData($request->validated(), $request->user());
        $snippet = $templates->save($institution, $data);

        return redirect()
            ->route('admin.description-snippets.show', $snippet)
            ->with('status', 'Snippet deskripsi berhasil ditambahkan.');
    }

    public function show(Request $request, DescriptionSnippet $descriptionSnippet): View
    {
        $this->authorizeSnippet($request, $descriptionSnippet, 'view');
        $descriptionSnippet->load(['subject', 'owner', 'templates']);

        return view('admin.description-snippets.show', ['snippet' => $descriptionSnippet]);
    }

    public function edit(Request $request, DescriptionSnippet $descriptionSnippet, DescriptionSnippetTemplateService $templates): View
    {
        $institution = $this->institution($request);
        $this->authorizeSnippet($request, $descriptionSnippet, 'update');

        return view('admin.description-snippets.edit', $this->formData($institution, $descriptionSnippet, $templates));
    }

    public function update(UpdateDescriptionSnippetRequest $request, DescriptionSnippet $descriptionSnippet, DescriptionSnippetTemplateService $templates): RedirectResponse
    {
        $this->authorizeSnippet($request, $descriptionSnippet, 'update');
        $institution = $this->institution($request);
        $templates->save($institution, $this->roleScopedData($request->validated(), $request->user(), $descriptionSnippet), $descriptionSnippet);

        return redirect()
            ->route('admin.description-snippets.show', $descriptionSnippet)
            ->with('status', 'Snippet deskripsi berhasil diperbarui.');
    }

    public function destroy(Request $request, DescriptionSnippet $descriptionSnippet): RedirectResponse
    {
        $this->authorizeSnippet($request, $descriptionSnippet, 'delete');
        $descriptionSnippet->delete();

        return redirect()
            ->route('admin.description-snippets.index')
            ->with('status', 'Snippet deskripsi berhasil dihapus.');
    }

    /** @return array<string, mixed> */
    private function formData(Institution $institution, DescriptionSnippet $snippet, DescriptionSnippetTemplateService $templates): array
    {
        $predicateRanges = $templates->activeRanges($institution);

        return [
            'snippet' => $snippet,
            'subjects' => $this->subjects($institution),
            'teachers' => User::query()
                ->where('institution_id', $institution->getKey())
                ->whereHas('roles', fn ($query) => $query->where('code', 'GURU'))
                ->where('status', 'ACTIVE')
                ->orderBy('name')
                ->get(['id', 'name']),
            'predicateRanges' => $predicateRanges,
            'templateDefaults' => $this->templateDefaults($predicateRanges, $templates),
        ];
    }

    /** @param Collection<int, PredicateRange> $ranges */
    private function templateDefaults($ranges, DescriptionSnippetTemplateService $templates): array
    {
        return $ranges->mapWithKeys(fn ($range): array => [$range->code => $templates->defaultTemplate($range)])->all();
    }

    private function subjects(Institution $institution)
    {
        return $institution->subjects()
            ->where('active', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get(['id', 'name', 'short_name']);
    }

    /** @param array<string, mixed> $data */
    private function roleScopedData(array $data, User $user, ?DescriptionSnippet $snippet = null): array
    {
        if ($user->hasRole('ADMIN')) {
            $data['visibility'] = DescriptionSnippetVisibility::Shared->value;
        } elseif ($user->hasRole('GURU')) {
            $data['owner_user_id'] = $snippet?->owner_user_id ?? $user->getKey();
            $data['visibility'] = DescriptionSnippetVisibility::Private->value;
        }

        return $data;
    }

    private function institution(Request $request): Institution
    {
        $institution = $request->user()?->institution;
        abort_unless($institution instanceof Institution, 403, 'Akun belum memiliki institusi.');

        return $institution;
    }

    private function authorizeSnippet(Request $request, DescriptionSnippet $snippet, string $ability): void
    {
        $this->institution($request);
        Gate::authorize($ability, $snippet);
    }
}
