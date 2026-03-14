<?php

namespace App\Support\Modules\DataProviders;

use App\Models\ContentObject;
use App\Models\ContentStatus;
use App\Support\Modules\Contracts\ModuleDataProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class ContentObjectsProvider implements ModuleDataProvider
{
    /**
     * Resolve provider payload for content modules.
     *
     * @param array $source
     * @param array $config
     * @param Authenticatable|null $viewer
     * @return array
     */
    public function resolve(array $source, array $config, ?Authenticatable $viewer = null): array
    {
        // Start from a narrow projection so modules do not over-fetch data.
        $query = ContentObject::query()
            ->select([
                'id',
                'name',
                'type_id',
                'author_id',
                'description',
                'priority',
                'status_id',
                'meta',
                'published_at',
                'expires_at',
                'archived_at',
                'created_at',
                'updated_at',
            ])
            ->with([
                'author:id,name,username,avatar',
                'status:id,name',
                'tags:id,name',
                'type:id,name',
            ]);

        $filters = Arr::get($source, 'filters', []);
        if (!is_array($filters)) {
            $filters = [];
        }

        $this->applyStatusFilter($query, $filters);
        $this->applyTypeFilter($query, $filters);
        $this->applyTagFilter($query, $filters);
        $this->applyAuthorFilter($query, $filters, $viewer);

        // Ordering is configurable but constrained to a safe allowlist.
        $orderBy = (string) Arr::get($source, 'order_by', 'priority');
        if (!in_array($orderBy, ['priority', 'published_at', 'created_at', 'name'], true)) {
            $orderBy = 'priority';
        }

        $direction = strtolower((string) Arr::get($source, 'order_direction', 'desc'));
        if (!in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'desc';
        }

        if ($orderBy === 'priority') {
            $query->orderByDesc('priority')
                ->orderByDesc('published_at')
                ->orderByDesc('created_at');
        } else {
            $query->orderBy($orderBy, $direction)
                ->orderByDesc('priority');
        }

        $limit = (int) Arr::get($source, 'limit', 12);
        $limit = max(1, min($limit, (int) config('modules.max_items_per_module', 30)));

        $items = $query->limit($limit)->get();

        return [
            'items' => $items,
            'meta' => [
                'count' => $items->count(),
                'provider' => 'content_objects',
            ],
        ];
    }

    /**
     * Apply status filters with a safe published-only default.
     *
     * @param Builder $query
     * @param array $filters
     * @return void
     */
    private function applyStatusFilter(Builder $query, array $filters): void
    {
        $statusId = isset($filters['status_id']) ? (int) $filters['status_id'] : null;
        if ($statusId !== null && $statusId > 0) {
            $query->where('status_id', $statusId);
            return;
        }

        $statusIds = collect(Arr::get($filters, 'status_ids', []))
            ->map(fn (mixed $value): int => (int) $value)
            ->filter(fn (int $value): bool => $value > 0)
            ->unique()
            ->values();
        if ($statusIds->isNotEmpty()) {
            $query->whereIn('status_id', $statusIds->all());
            return;
        }

        $statusNames = $this->normalizeFilterNames(Arr::get($filters, 'status_names', []));
        if ($statusNames === []) {
            $statusName = isset($filters['status_name']) ? trim((string) $filters['status_name']) : '';
            if ($statusName !== '') {
                $statusNames = [mb_strtolower($statusName)];
            }
        }

        if ($statusNames !== []) {
            $resolvedStatusIds = ContentStatus::query()
                ->where(function (Builder $builder) use ($statusNames): void {
                    foreach ($statusNames as $statusName) {
                        $builder->orWhereRaw('LOWER(name) = ?', [$statusName]);
                    }
                })
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->unique()
                ->values();

            if ($resolvedStatusIds->isNotEmpty()) {
                $query->whereIn('status_id', $resolvedStatusIds->all());
                return;
            }
        }

        // Safe default for front-facing modules: only published content.
        $publishedStatusId = ContentStatus::query()
            ->whereRaw('LOWER(name) = ?', ['published'])
            ->value('id');

        if ($publishedStatusId !== null) {
            $query->where('status_id', $publishedStatusId);
            return;
        }

        // Preserve safe behavior when the Published status is not available.
        $query->whereRaw('1 = 0');
    }

    /**
     * Apply content type filter.
     *
     * @param Builder $query
     * @param array $filters
     * @return void
     */
    private function applyTypeFilter(Builder $query, array $filters): void
    {
        $typeId = isset($filters['type_id']) ? (int) $filters['type_id'] : null;
        if ($typeId !== null && $typeId > 0) {
            $query->where('type_id', $typeId);
            return;
        }

        $typeIds = collect(Arr::get($filters, 'type_ids', []))
            ->map(fn (mixed $value): int => (int) $value)
            ->filter(fn (int $value): bool => $value > 0)
            ->unique()
            ->values();
        if ($typeIds->isNotEmpty()) {
            $query->whereIn('type_id', $typeIds->all());
            return;
        }

        $typeNames = $this->normalizeFilterNames(Arr::get($filters, 'type_names', []));
        if ($typeNames === []) {
            $typeName = isset($filters['type_name']) ? trim((string) $filters['type_name']) : '';
            if ($typeName !== '') {
                $typeNames = [mb_strtolower($typeName)];
            }
        }

        if ($typeNames === []) {
            return;
        }

        $query->whereHas('type', function (Builder $builder) use ($typeNames): void {
            $builder->where(function (Builder $nested) use ($typeNames): void {
                foreach ($typeNames as $typeName) {
                    $nested->orWhereRaw('LOWER(name) = ?', [$typeName]);
                }
            });
        });
    }

    /**
     * Apply tag filters.
     *
     * @param Builder $query
     * @param array $filters
     * @return void
     */
    private function applyTagFilter(Builder $query, array $filters): void
    {
        $tagId = isset($filters['tag_id']) ? (int) $filters['tag_id'] : null;
        if ($tagId !== null && $tagId > 0) {
            $query->whereHas('tags', function (Builder $builder) use ($tagId): void {
                $builder->where('tags.id', $tagId);
            });

            return;
        }

        $rawTagIds = Arr::get($filters, 'tag_ids', []);
        $tagIds = collect(is_array($rawTagIds) ? $rawTagIds : [])
            ->map(fn (mixed $value): int => (int) $value)
            ->filter(fn (int $value): bool => $value > 0)
            ->unique()
            ->values();

        if ($tagIds->isNotEmpty()) {
            $query->whereHas('tags', function (Builder $builder) use ($tagIds): void {
                $builder->whereIn('tags.id', $tagIds->all());
            });

            return;
        }

        $tagNames = $this->normalizeFilterNames(Arr::get($filters, 'tag_names', []));
        if ($tagNames === []) {
            $tagName = isset($filters['tag_name']) ? trim((string) $filters['tag_name']) : '';
            if ($tagName !== '') {
                $tagNames = [mb_strtolower($tagName)];
            }
        }

        if ($tagNames === []) {
            return;
        }

        $query->whereHas('tags', function (Builder $builder) use ($tagNames): void {
            $builder->where(function (Builder $nested) use ($tagNames): void {
                foreach ($tagNames as $tagName) {
                    $nested->orWhereRaw('LOWER(name) = ?', [$tagName]);
                }
            });
        });
    }

    /**
     * Apply author filters.
     *
     * The guided editor exposes a simple "current viewer" scope for dashboard
     * widgets like personal content lists, while raw JSON can still target
     * explicit author IDs when needed.
     *
     * @param Builder $query
     * @param array $filters
     * @param Authenticatable|null $viewer
     * @return void
     */
    private function applyAuthorFilter(Builder $query, array $filters, ?Authenticatable $viewer = null): void
    {
        $authoredBy = strtolower(trim((string) Arr::get($filters, 'authored_by', '')));
        if (in_array($authoredBy, ['viewer', 'current_user'], true)) {
            $viewerId = (string) ($viewer?->getAuthIdentifier() ?? '');
            if ($viewerId === '') {
                $query->whereRaw('1 = 0');
                return;
            }

            $query->where('author_id', $viewerId);
            return;
        }

        $authorId = trim((string) Arr::get($filters, 'author_id', ''));
        if ($authorId !== '') {
            $query->where('author_id', $authorId);
            return;
        }

        $authorIds = collect(Arr::get($filters, 'author_ids', []))
            ->map(static fn (mixed $value): string => trim((string) $value))
            ->filter(static fn (string $value): bool => $value !== '')
            ->unique()
            ->values();

        if ($authorIds->isNotEmpty()) {
            $query->whereIn('author_id', $authorIds->all());
        }
    }

    /**
     * Normalize mixed filter input to lowercase name values.
     *
     * @param mixed $value
     * @return array<int, string>
     */
    private function normalizeFilterNames(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return collect($value)
            ->map(fn (mixed $name): string => is_string($name) ? mb_strtolower(trim($name)) : '')
            ->filter(fn (string $name): bool => $name !== '')
            ->unique()
            ->values()
            ->all();
    }
}
