<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DescriptionSnippetVisibility;
use App\Http\Controllers\Controller;
use App\Models\DescriptionSnippet;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DescriptionSnippetPickerController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        Gate::authorize('viewAny', DescriptionSnippet::class);

        $validated = $request->validate([
            'subject_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $user = $request->user();
        $subject = null;

        if (isset($validated['subject_id'])) {
            $subject = Subject::query()
                ->where('institution_id', $user->institution_id)
                ->find($validated['subject_id']);

            abort_unless($subject, 404);
        }

        $search = trim((string) ($validated['search'] ?? ''));
        $perPage = (int) ($validated['per_page'] ?? 12);

        $snippets = DescriptionSnippet::query()
            ->with('subject:id,name,short_name')
            ->visibleTo($user)
            ->where('active', true)
            ->when($subject, fn ($query) => $query->where('subject_id', $subject->getKey()))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%");
                });
            })
            ->orderBy('title')
            ->orderByDesc('updated_at')
            ->paginate($perPage)
            ->withQueryString();

        if ($this->wantsJson($request)) {
            return response()->json([
                'data' => $snippets->getCollection()->map(
                    fn (DescriptionSnippet $snippet): array => $this->selectionTarget($snippet),
                )->values(),
                'meta' => [
                    'current_page' => $snippets->currentPage(),
                    'last_page' => $snippets->lastPage(),
                    'per_page' => $snippets->perPage(),
                    'total' => $snippets->total(),
                ],
                'filters' => [
                    'subject_id' => $subject?->getKey(),
                    'search' => $search,
                ],
            ]);
        }

        return view('description-snippets.picker', [
            'snippets' => $snippets,
            'subject' => $subject,
            'search' => $search,
            'pickerUrl' => $request->url(),
            'selectionTargets' => $snippets->getCollection()->mapWithKeys(
                fn (DescriptionSnippet $snippet): array => [$snippet->getKey() => $this->selectionTarget($snippet)],
            ),
        ]);
    }

    /**
     * Build the stable payload consumed by a future nilai input form.
     *
     * @return array<string, mixed>
     */
    public function selectionTarget(DescriptionSnippet $snippet): array
    {
        return [
            'id' => $snippet->getKey(),
            'title' => $snippet->title,
            'content' => $snippet->content,
            'subject_id' => $snippet->subject_id,
            'subject_name' => $snippet->subject?->name,
            'subject_short_name' => $snippet->subject?->short_name,
            'visibility' => $snippet->visibility instanceof DescriptionSnippetVisibility
                ? $snippet->visibility->value
                : $snippet->visibility,
        ];
    }

    private function wantsJson(Request $request): bool
    {
        return $request->expectsJson() || $request->query('format') === 'json';
    }
}
