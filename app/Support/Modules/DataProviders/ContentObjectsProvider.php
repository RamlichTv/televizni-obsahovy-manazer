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

        $statusName = isset($filters['status_name']) ? trim((string) $filters['status_name']) : '';
        if ($statusName !== '') {
            $statusId = ContentStatus::query()
                ->whereRaw('LOWER(name) = ?', [strtolower($statusName)])
                ->value('id');

            if ($statusId !== null) {
                $query->where('status_id', $statusId);
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

        $typeName = isset($filters['type_name']) ? trim((string) $filters['type_name']) : '';
        if ($typeName === '') {
            return;
        }

        $query->whereHas('type', function (Builder $builder) use ($typeName): void {
            $builder->whereRaw('LOWER(name) = ?', [strtolower($typeName)]);
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
        $rawTagIds = Arr::get($filters, 'tag_ids', []);
        if (!is_array($rawTagIds)) {
            return;
        }

        $tagIds = collect($rawTagIds)
            ->map(fn (mixed $value) => (int) $value)
            ->filter(fn (int $value) => $value > 0)
            ->unique()
            ->values();

        if ($tagIds->isEmpty()) {
            return;
        }

        $query->whereHas('tags', function (Builder $builder) use ($tagIds): void {
            $builder->whereIn('tags.id', $tagIds->all());
        });
    }
}

