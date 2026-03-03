<?php

namespace App\Support\Events;

use App\Models\Event;
use App\Models\EventType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class EventLogger
{
    /**
     * Persist an event log entry.
     *
     * @param string $typeName
     * @param string $message
     * @param array $context
     * @param string|null $userId
     * @param string|null $screenId
     * @param string|null $relatedContentId
     * @return Event|null
     */
    public static function record(
        string $typeName,
        string $message,
        array $context = [],
        ?string $userId = null,
        ?string $screenId = null,
        ?string $relatedContentId = null
    ): ?Event {
        try {
            $typeId = self::resolveTypeId($typeName);

            if ($typeId === null) {
                Log::warning('Event type does not exist, event skipped.', ['event_type' => $typeName]);
                return null;
            }

            $payload = [
                'message' => $message,
                'type_id' => $typeId,
                'user_id' => $userId,
            ];

            $relatedModelPayload = [
                'screen_id' => $screenId,
                'related_content' => $relatedContentId,
                'related_profile' => self::contextString($context, ['profile_id', 'new_profile_id', 'old_profile_id']),
                'related_module' => self::contextString($context, ['module_id']),
                'related_role' => self::contextInt($context, ['role_id'], ['role_ids']),
                'related_tag' => self::contextInt($context, ['tag_id'], ['tag_ids']),
                'related_content_type' => self::contextInt($context, ['type_id']),
                'related_module_category' => self::contextInt($context, ['category_id']),
                'related_page' => self::contextInt($context, ['page_id']),
                'related_page_module' => self::contextInt($context, ['placement_id']),
                'related_user' => self::contextString($context, ['target_user_id']),
            ];

            $columnModelMap = [
                'screen_id' => \App\Models\Screen::class,
                'related_content' => \App\Models\ContentObject::class,
                'related_profile' => \App\Models\Profile::class,
                'related_module' => \App\Models\Module::class,
                'related_role' => \App\Models\Role::class,
                'related_tag' => \App\Models\Tag::class,
                'related_content_type' => \App\Models\ContentType::class,
                'related_module_category' => \App\Models\ModuleCategory::class,
                'related_page' => \App\Models\Page::class,
                'related_page_module' => \App\Models\PageModule::class,
                'related_user' => \App\Models\User::class,
            ];

            foreach ($relatedModelPayload as $column => $value) {
                if ($value === null || !self::eventsTableHasColumn($column)) {
                    continue;
                }

                if (isset($columnModelMap[$column]) && !self::modelExists($columnModelMap[$column], $value)) {
                    continue;
                }

                $payload[$column] = $value;
            }

            if (self::eventsTableHasColumn('context')) {
                $payload['context'] = $context === [] ? null : $context;
            }

            return Event::create($payload);
        } catch (Throwable $exception) {
            Log::warning('Unable to record event.', [
                'event_type' => $typeName,
                'message' => $message,
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Resolve event type id by lowercase name.
     *
     * @param string $typeName
     * @return int|null
     */
    private static function resolveTypeId(string $typeName): ?int
    {
        $normalized = trim(strtolower($typeName));
        if ($normalized === '') {
            return null;
        }

        $id = EventType::query()
            ->whereRaw('LOWER(name) = ?', [$normalized])
            ->value('id');

        return is_numeric($id) ? (int) $id : null;
    }

    /**
     * Determine whether the events table contains a column.
     *
     * @param string $column
     * @return bool
     */
    private static function eventsTableHasColumn(string $column): bool
    {
        static $cache = [];

        if (!array_key_exists($column, $cache)) {
            $cache[$column] = Schema::hasColumn('events', $column);
        }

        return (bool) $cache[$column];
    }

    /**
     * Check whether a related model exists.
     *
     * @param string $modelClass
     * @param int|string $key
     * @return bool
     */
    private static function modelExists(string $modelClass, int|string $key): bool
    {
        if (!class_exists($modelClass) || !method_exists($modelClass, 'query')) {
            return false;
        }

        return (bool) $modelClass::query()->whereKey($key)->exists();
    }

    /**
     * Extract first non-empty string value from context keys.
     *
     * @param array $context
     * @param array $keys
     * @return string|null
     */
    private static function contextString(array $context, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $context)) {
                continue;
            }

            $value = $context[$key];
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    /**
     * Extract first numeric scalar or array value from context keys.
     *
     * @param array $context
     * @param array $scalarKeys
     * @param array $arrayKeys
     * @return int|null
     */
    private static function contextInt(array $context, array $scalarKeys, array $arrayKeys = []): ?int
    {
        foreach ($scalarKeys as $key) {
            if (!array_key_exists($key, $context)) {
                continue;
            }

            $value = $context[$key];
            if (is_numeric($value)) {
                return (int) $value;
            }
        }

        foreach ($arrayKeys as $key) {
            if (!array_key_exists($key, $context) || !is_array($context[$key])) {
                continue;
            }

            foreach ($context[$key] as $value) {
                if (is_numeric($value)) {
                    return (int) $value;
                }
            }
        }

        return null;
    }
}

