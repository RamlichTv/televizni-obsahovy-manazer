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
    public function index() : View {
        return view('services.content.index', [
            'tags' => \App\Models\Tag::all(),
            'contentTypes' => \App\Models\ContentType::all(),
        ]);
    }

    public function manage(Request $request) : View {
        $content_objects = ContentObject::where('author_id', $request->user()->id);
        
        $this->applyFilters($content_objects);

        return view('services.content.list', [
            'content_objects' => $content_objects->latest()->paginate(20)
        ]);
    }

    public function unpublished() : View {
        $content_objects = ContentObject::where('status_id', 1);
        
        $this->applyFilters($content_objects);

        return view('services.content.list', [
            'content_objects' => $content_objects->latest()->paginate(20)
        ]);
    }

    public function library() : View {
        $content_objects = ContentObject::where('status_id', 2);
        
        $this->applyFilters($content_objects);

        return view('services.content.list', [
            'content_objects' => $content_objects->latest()->paginate(20)
        ]);
    }

    public function archived() : View {
        $content_objects = ContentObject::where('status_id', 3);
        
        $this->applyFilters($content_objects);

        return view('services.content.list', [
            'content_objects' => $content_objects->latest()->paginate(20)
        ]);
    }

    public function deleted() : View {
        $content_objects = ContentObject::onlyTrashed();
        
        $this->applyFilters($content_objects);

        return view('services.content.list', [
            'content_objects' => $content_objects->latest()->paginate(20)
        ]);
    }

    public function show(ContentObject $content_object) : View {
        $content_object->load(['author', 'tags', 'type']);
        return view('services.content.show', ['content' => $content_object]);
    }

    public function create() : View {
        return view('services.content.create');
    }

    function store(ContentObjectRequest $request) {
        $data = $request->safe()->except(['file_upload', 'tags']);
        $user = $request->user();

        do {
            $data['id'] = (string) \Illuminate\Support\Str::uuid();
        } while (ContentObject::where('id', $data['id'])->exists());

        $data['author_id'] = $user->id;
        $data['status_id'] = 1; 

        if (\App\Models\ContentType::find($data['type_id'])->is_file && !$request->hasFile('file_upload'))
            return redirect()->back()->withErrors(['file_upload' => 'File upload is required for this content type.']);

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
                'path' => $path // storage/content/uuid.jpg
            ];
            
            $content->update(['meta' => json_encode($meta)]);
        }

        if ($request->has('tags')) {
            $content->tags()->sync($request->input('tags'));
        }
        
        return redirect()->route('content.show', ['content_object' => $content])
                         ->with('success', 'Content created successfully.');
    }

    function edit(ContentObject $content_object) : View {
        $content_object->load(['tags']);
        
        return view('services.content.edit', [
            'content' => $content_object,
            'types' => \App\Models\ContentType::all(),
            'tags' => \App\Models\Tag::all(),
        ]);
    }

    function update(Request $request, ContentObject $content_object) {
        $contentType = $content_object->type;

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

        return redirect()->route('content.show', $content_object)
            ->with('success', 'Content updated successfully.');
    }

    function publish(ContentObject $content_object) {
        $content_object->update(['status_id' => 2, 'published_at' => date('Y-m-d H:i:s')]);
        return back()->with('success', 'Content published successfully.');
    }

    function unpublish(ContentObject $content_object) {
        $content_object->update(['status_id' => 1, 'published_at' => null]);
        return back()->with('success', 'Content unpublished successfully.');
    }

    function archive(ContentObject $content_object) {
        $content_object->update(['status_id' => 3, 'archived_at' => date('Y-m-d H:i:s')]);
        return back()->with('success', 'Content archived successfully.');
    }

    function unarchive(ContentObject $content_object) {
        $content_object->update(['status_id' => 2, 'archived_at' => null, 'expires_at' => null]);
        return back()->with('success', 'Content unarchived successfully.');
    }

    function destroy(ContentObject $content_object) {
        $content_object->update(['status_id' => 4]);
        $content_object->delete();
        return back()->with('success', 'Content deleted successfully.');
    }

    private function applyFilters($query) {
        if (request('type')) {
            $query->where('type_id', request('type'));
        }
        if (request('author')) {
            $query->where('author_id', request('author'));
        }
        if (request('tag')) {
            $query->whereHas('tags', function($q) {
                $q->where('tags.id', request('tag'));
            });
        }
        return $query;
    }

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
