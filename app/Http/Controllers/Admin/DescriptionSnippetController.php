<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DescriptionSnippetVisibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDescriptionSnippetRequest;
use App\Http\Requests\Admin\UpdateDescriptionSnippetRequest;
use App\Models\DescriptionSnippet;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DescriptionSnippetController extends Controller
{
    public function index(Request $request): View
    {
        $institution = $this->institution($request);
        $user = $request->user();
        Gate::authorize('viewAny', DescriptionSnippet::class);

        $snippets = DescriptionSnippet::query()
            ->visibleTo($user)
            ->with(['subject:id,name,short_name', 'owner:id,name'])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = trim((string) $request->string('search'));
                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery
                        ->where('title', 'like', "%{$search}%")
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
        ]);
    }

    public function create(Request $request): View
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
        ));
    }

    public function store(StoreDescriptionSnippetRequest $request): RedirectResponse
    {
        $institution = $this->institution($request);
        Gate::authorize('create', DescriptionSnippet::class);

        $data = $this->roleScopedData($request->validated(), $request->user());
        $snippet = $institution->descriptionSnippets()->create($data);

        return redirect()
            ->route('admin.description-snippets.show', $snippet)
            ->with('status', 'Snippet deskripsi berhasil ditambahkan.');
    }

    public function show(Request $request, DescriptionSnippet $descriptionSnippet): View
    {
        $this->authorizeSnippet($request, $descriptionSnippet, 'view');
        $descriptionSnippet->load(['subject', 'owner']);

        return view('admin.description-snippets.show', ['snippet' => $descriptionSnippet]);
    }

    public function edit(Request $request, DescriptionSnippet $descriptionSnippet): View
    {
        $institution = $this->institution($request);
        $this->authorizeSnippet($request, $descriptionSnippet, 'update');

        return view('admin.description-snippets.edit', $this->formData($institution, $descriptionSnippet));
    }

    public function update(UpdateDescriptionSnippetRequest $request, DescriptionSnippet $descriptionSnippet): RedirectResponse
    {
        $this->authorizeSnippet($request, $descriptionSnippet, 'update');
        $descriptionSnippet->update($this->roleScopedData($request->validated(), $request->user(), $descriptionSnippet));

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
    private function formData(Institution $institution, DescriptionSnippet $snippet): array
    {
        return [
            'snippet' => $snippet,
            'subjects' => $this->subjects($institution),
            'teachers' => User::query()
                ->where('institution_id', $institution->getKey())
                ->whereHas('roles', fn ($query) => $query->where('code', 'GURU'))
                ->where('status', 'ACTIVE')
                ->orderBy('name')
                ->get(['id', 'name']),
        ];
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
