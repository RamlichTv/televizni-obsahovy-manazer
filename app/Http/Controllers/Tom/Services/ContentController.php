<?php

namespace App\Http\Controllers\Tom\Services;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tom\Services\ContentObjectRequest;
use App\Models\ContentObject;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;

class ContentController extends Controller
{
    /**
     * Display the index listing.
     *
     * @return View
     */
    public function index() : View {
        return view('services.content.index', [
            'tags' => \App\Models\Tag::all(),
            'contentTypes' => \App\Models\ContentType::all(),
        ]);
    }

    /**
     * Manage.
     *
     * @param Request $request
     * @return View
     */
    public function manage(Request $request) : View {
        $content_objects = ContentObject::where('author_id', $request->user()->id);
        
        $this->applyFilters($content_objects);

        return view('services.content.list', [
            'content_objects' => $content_objects->latest()->paginate(20)
        ]);
    }

    /**
     * Unpublished.
     *
     * @return View
     */
    public function unpublished() : View {
        $content_objects = ContentObject::where('status_id', 1);
        
        $this->applyFilters($content_objects);

        return view('services.content.list', [
            'content_objects' => $content_objects->latest()->paginate(20)
        ]);
    }

    /**
     * Library.
     *
     * @return View
     */
    public function library() : View {
        $content_objects = ContentObject::where('status_id', 2);
        
        $this->applyFilters($content_objects);

        return view('services.content.list', [
            'content_objects' => $content_objects->latest()->paginate(20)
        ]);
    }

    /**
     * Archived.
     *
     * @return View
     */
    public function archived() : View {
        $content_objects = ContentObject::where('status_id', 3);
        
        $this->applyFilters($content_objects);

        return view('services.content.list', [
            'content_objects' => $content_objects->latest()->paginate(20)
        ]);
    }

    /**
     * Deleted.
     *
     * @return View
     */
    public function deleted() : View {
        $content_objects = ContentObject::onlyTrashed();
        
        $this->applyFilters($content_objects);

        return view('services.content.list', [
            'content_objects' => $content_objects->latest()->paginate(20)
        ]);
    }

    /**
     * Display the requested resource.
     *
     * @param ContentObject $content_object
     * @return View
     */
    public function show(ContentObject $content_object) : View {
        $content_object->load(['author', 'tags', 'type']);
        return view('services.content.show', ['content' => $content_object]);
    }

    /**
     * Create a new resource.
     *
     * @return View
     */
    public function create() : View {
        return view('services.content.create');
    }

    /**
     * Store a newly created resource.
     *
     * @param ContentObjectRequest $request
     * @return mixed
     */
    function store(ContentObjectRequest $request) {
        $data = $request->safe()->except(['file_upload', 'tags']);
        $user = $request->user();

        do {
            $data['id'] = (string) \Illuminate\Support\Str::uuid();
        } while (ContentObject::where('id', $data['id'])->exists());

        $data['author_id'] = $user->id;
        $data['status_id'] = 1; 

        $contentType = \App\Models\ContentType::find($data['type_id']);

        if ($contentType?->is_file && !$request->hasFile('file_upload')) {
            $this->logFailure(
                message: 'Content creation failed because file upload was missing.',
                context: [
                    'action' => 'content.store',
                    'content_name' => $data['name'] ?? null,
                    'type_id' => $data['type_id'] ?? null,
                    'type_name' => $contentType?->name,
                ],
                userId: $user->id,
            );

            return redirect()->back()->withErrors(['file_upload' => 'File upload is required for this content type.']);
        }

        $content = ContentObject::create($data);

        if ($request->hasFile('file_upload')) {
            $file = $request->file('file_upload');
            
            // public/storage/content/{uuid}.ext
            $path = $file->storeAs(
                'users/' . $user->username . '/content', 
                $content->id . '.' . $file->getClientOriginalExtension(), 
                'public'
            );

            $meta = [
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'path' => '/storage/' . $path // storage/content/uuid.jpg
            ];
            
            $content->update(['meta' => $meta]);
        }

        if ($request->has('tags')) {
            $content->tags()->sync($request->input('tags'));

            $tagIds = collect((array) $request->input('tags'))
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            if ($tagIds !== []) {
                $tagNames = \App\Models\Tag::query()
                    ->whereIn('id', $tagIds)
                    ->orderBy('name')
                    ->pluck('name')
                    ->values()
                    ->all();

                $this->logEvent(
                    typeName: 'tag_attached',
                    message: 'Tags were attached to content "' . $content->name . '".',
                    context: [
                        'action' => 'content.store.tags.sync',
                        'content_id' => $content->id,
                        'tag_ids' => $tagIds,
                        'tag_names' => $tagNames,
                    ],
                    userId: $user->id,
                    relatedContentId: $content->id,
                );
            }
        }

        $this->logEvent(
            typeName: 'content_created',
            message: 'Content "' . $content->name . '" was created.',
            context: [
                'action' => 'content.store',
                'content_id' => $content->id,
                'type_id' => $content->type_id,
                'type_name' => $contentType?->name,
                'has_file' => $request->hasFile('file_upload'),
            ],
            userId: $user->id,
            relatedContentId: $content->id,
        );
        
        return redirect()->route('content.show', ['content_object' => $content])
                         ->with('success', 'Content created successfully.');
    }

    /**
     * Show the edit form for the resource.
     *
     * @param ContentObject $content_object
     * @return View
     */
    function edit(ContentObject $content_object) : View {
        $content_object->load(['tags']);
        
        return view('services.content.edit', [
            'content' => $content_object,
            'types' => \App\Models\ContentType::all(),
            'tags' => \App\Models\Tag::all(),
        ]);
    }

    /**
     * Update the current resource.
     *
     * @param Request $request
     * @param ContentObject $content_object
     * @return mixed
     */
    function update(Request $request, ContentObject $content_object) {
        $contentType = $content_object->type;
        $actorId = $request->user()?->id;
        $previousTagIds = $content_object->tags()->pluck('tags.id')->map(fn ($id) => (int) $id)->all();
        $beforeSnapshot = [
            'name' => $content_object->name,
            'description' => $content_object->description,
            'priority' => $content_object->priority,
            'published_at' => optional($content_object->published_at)->toDateTimeString(),
            'expires_at' => optional($content_object->expires_at)->toDateTimeString(),
            'status_id' => $content_object->status_id,
        ];

        // Validation (similar to create, but file is nullable)
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|integer|min:0|max:20',
            'expires_at' => ['nullable', 'date', 'after:date'],
            'published_at' => ['nullable', 'date', 'after_or_equal:date'],
            'tags' => 'array',
            'tags.*' => 'exists:tags,id',
            'file_upload' => [
                'nullable',
                'file',
                function ($attribute, $value, $fail) use ($request, $contentType) {
                    if (!$contentType || !$contentType->is_file) {
                        return;
                    }

                    $allowed = collect((array) ($contentType->extension_whitelist ?? []))
                        ->map(fn($rule) => strtolower(trim((string) $rule)))
                        ->filter()
                        ->values()
                        ->all();

                    if (!empty($allowed) && !$this->fileMatchesAnyRule($value, $allowed)) {
                        $fail("The {$attribute} must match one of: " . implode(', ', $allowed));
                    }

                    $maxSizeBytes = (int) ($contentType->max_size ?? 0);
                    if ($maxSizeBytes > 0 && $value->getSize() > $maxSizeBytes && $request->user()->cannot('bypass_max_size')) {
                        $fail("The file is too large. Max size allowed is " . round($maxSizeBytes / 1024 / 1024, 2) . " MB.");
                    }
                },
            ],
        ]);

        // Update base data
        $content_object->update($request->except(['tags', 'file_upload']));
        $rawChanges = collect($content_object->getChanges())
            ->except(['updated_at', 'meta'])
            ->all();
        $changeContext = [];
        foreach ($rawChanges as $field => $newValue) {
            $changeContext['old_' . $field] = $beforeSnapshot[$field] ?? $content_object->getOriginal($field);
            $changeContext['new_' . $field] = $newValue;
        }

        // Update file (if a new one was uploaded)
        if ($request->hasFile('file_upload')) {
            $file = $request->file('file_upload');
            $path = $file->storeAs(
                'content', 
                $content_object->id . '.' . $file->getClientOriginalExtension(), 
                'public'
            );
            
            $meta = is_array($content_object->meta) ? $content_object->meta : json_decode($content_object->meta, true) ?? [];
            $meta['original_filename'] = $file->getClientOriginalName();
            $meta['mime_type'] = $file->getMimeType();
            $meta['size'] = $file->getSize();
            $meta['path'] = $path;

            $content_object->update(['meta' => $meta]);
        }

        // Sync tags
        if ($request->has('tags')) {
            $content_object->tags()->sync($request->input('tags'));
        } else {
            $content_object->tags()->detach();
        }

        $currentTagIds = $content_object->tags()->pluck('tags.id')->map(fn ($id) => (int) $id)->all();
        $addedTagIds = array_values(array_diff($currentTagIds, $previousTagIds));
        $removedTagIds = array_values(array_diff($previousTagIds, $currentTagIds));

        if ($addedTagIds !== []) {
            $this->logEvent(
                typeName: 'tag_attached',
                message: 'Tags were attached to content "' . $content_object->name . '".',
                context: [
                    'action' => 'content.update.tags.attach',
                    'content_id' => $content_object->id,
                    'tag_ids' => $addedTagIds,
                ],
                userId: $actorId,
                relatedContentId: $content_object->id,
            );
        }

        if ($removedTagIds !== []) {
            $this->logEvent(
                typeName: 'tag_detached',
                message: 'Tags were removed from content "' . $content_object->name . '".',
                context: [
                    'action' => 'content.update.tags.detach',
                    'content_id' => $content_object->id,
                    'tag_ids' => $removedTagIds,
                ],
                userId: $actorId,
                relatedContentId: $content_object->id,
            );
        }

        $this->logEvent(
            typeName: 'content_modified',
            message: 'Content "' . $content_object->name . '" was updated.',
            context: array_merge([
                'action' => 'content.update',
                'content_id' => $content_object->id,
                'type_id' => $content_object->type_id,
                'type_name' => $contentType?->name,
                'uploaded_new_file' => $request->hasFile('file_upload'),
                'added_tag_ids' => $addedTagIds,
                'removed_tag_ids' => $removedTagIds,
            ], $changeContext),
            userId: $actorId,
            relatedContentId: $content_object->id,
        );

        return redirect()->route('content.show', $content_object)
            ->with('success', 'Content updated successfully.');
    }

    /**
     * Publish.
     *
     * @param ContentObject $content_object
     * @return mixed
     */
    function publish(ContentObject $content_object) {
        $content_object->update(['status_id' => 2, 'published_at' => date('Y-m-d H:i:s')]);

        $this->logEvent(
            typeName: 'content_published',
            message: 'Content "' . $content_object->name . '" was published.',
            context: [
                'action' => 'content.publish',
                'content_id' => $content_object->id,
            ],
            userId: request()->user()?->id,
            relatedContentId: $content_object->id,
        );

        return back()->with('success', 'Content published successfully.');
    }

    /**
     * Unpublish.
     *
     * @param ContentObject $content_object
     * @return mixed
     */
    function unpublish(ContentObject $content_object) {
        $content_object->update(['status_id' => 1, 'published_at' => null]);

        $this->logEvent(
            typeName: 'content_unpublished',
            message: 'Content "' . $content_object->name . '" was unpublished.',
            context: [
                'action' => 'content.unpublish',
                'content_id' => $content_object->id,
            ],
            userId: request()->user()?->id,
            relatedContentId: $content_object->id,
        );

        return back()->with('success', 'Content unpublished successfully.');
    }

    /**
     * Archive.
     *
     * @param ContentObject $content_object
     * @return mixed
     */
    function archive(ContentObject $content_object) {
        $content_object->update(['status_id' => 3, 'archived_at' => date('Y-m-d H:i:s')]);

        $this->logEvent(
            typeName: 'content_archived',
            message: 'Content "' . $content_object->name . '" was archived.',
            context: [
                'action' => 'content.archive',
                'content_id' => $content_object->id,
            ],
            userId: request()->user()?->id,
            relatedContentId: $content_object->id,
        );

        return back()->with('success', 'Content archived successfully.');
    }

    /**
     * Unarchive.
     *
     * @param ContentObject $content_object
     * @return mixed
     */
    function unarchive(ContentObject $content_object) {
        $content_object->update(['status_id' => 2, 'archived_at' => null, 'expires_at' => null]);

        $this->logEvent(
            typeName: 'content_unarchived',
            message: 'Content "' . $content_object->name . '" was unarchived.',
            context: [
                'action' => 'content.unarchive',
                'content_id' => $content_object->id,
            ],
            userId: request()->user()?->id,
            relatedContentId: $content_object->id,
        );

        return back()->with('success', 'Content unarchived successfully.');
    }

    /**
     * Delete the current resource.
     *
     * @param ContentObject $content_object
     * @return mixed
     */
    function destroy(ContentObject $content_object) {
        $contentName = $content_object->name;
        $contentId = $content_object->id;

        $content_object->update(['status_id' => 4]);
        $content_object->delete();

        $this->logEvent(
            typeName: 'content_deleted',
            message: 'Content "' . $contentName . '" was deleted.',
            context: [
                'action' => 'content.destroy',
                'content_id' => $contentId,
            ],
            userId: request()->user()?->id,
            relatedContentId: $contentId,
        );

        return back()->with('success', 'Content deleted successfully.');
    }

    /**
     * Apply filters.
     *
     * @param mixed $query
     * @return mixed
     */
    private function applyFilters($query) {
        $search = trim((string) request('search', ''));
        if ($search !== '') {
            $query->where(function ($nested) use ($search) {
                $nested
                    ->where('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        $typeIds = $this->normalizeFilterIds(request('type_ids', request('type')));
        if (!empty($typeIds)) {
            $query->whereIn('type_id', $typeIds);
        }

        $authorIds = $this->normalizeFilterIds(request('author_ids', request('author')));
        if (!empty($authorIds)) {
            $query->whereIn('author_id', $authorIds);
        }

        $tagIds = $this->normalizeFilterIds(request('tag_ids', request('tag')));
        if (!empty($tagIds)) {
            $query->whereHas('tags', function($q) use ($tagIds) {
                $q->whereIn('tags.id', $tagIds);
            });
        }

        return $query;
    }

    /**
     * Normalize filter ids.
     *
     * @param mixed $raw
     * @return array
     */
    private function normalizeFilterIds(mixed $raw): array
    {
        return collect(is_array($raw) ? $raw : [$raw])
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => (int) $value)
            ->filter(fn ($value) => $value > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * File matches any rule.
     *
     * @param UploadedFile $file
     * @param array $rules
     * @return bool
     */
    private function fileMatchesAnyRule(UploadedFile $file, array $rules): bool
    {
        $mime = strtolower((string) $file->getMimeType());
        $clientExtension = strtolower((string) $file->getClientOriginalExtension());
        $guessedExtension = strtolower((string) $file->extension());

        foreach ($rules as $rule) {
            if (str_contains($rule, '/')) {
                if ($this->mimeMatchesRule($mime, $rule)) {
                    return true;
                }

                continue;
            }

            $normalizedExtension = ltrim($rule, '.');
            if ($normalizedExtension !== '' &&
                ($normalizedExtension === $clientExtension || $normalizedExtension === $guessedExtension)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Mime matches rule.
     *
     * @param string $mime
     * @param string $rule
     * @return bool
     */
    private function mimeMatchesRule(string $mime, string $rule): bool
    {
        if ($mime === '') {
            return false;
        }

        if (str_ends_with($rule, '/*')) {
            return str_starts_with($mime, rtrim($rule, '*'));
        }

        return $mime === $rule;
    }
}
