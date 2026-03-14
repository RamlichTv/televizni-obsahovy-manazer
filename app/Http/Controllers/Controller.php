<?php

namespace App\Http\Controllers;

use App\Support\Events\EventLogger;
use Illuminate\Support\Facades\Log;

/**
 * Base controller with shared helpers for audit/event logging.
 */
abstract class Controller
{
    /**
     * Record an application event and mirror it to the standard log output.
     *
     * @param string $typeName
     * @param string $message
     * @param array<string, mixed> $context
     * @param string|null $userId
     * @param string|null $screenId
     * @param string|null $relatedContentId
     * @return void
     */
    protected function logEvent(
        string $typeName,
        string $message,
        array $context = [],
        ?string $userId = null,
        ?string $screenId = null,
        ?string $relatedContentId = null
    ): void {
        EventLogger::record(
            typeName: $typeName,
            message: $message,
            context: $context,
            userId: $userId,
            screenId: $screenId,
            relatedContentId: $relatedContentId,
        );

        Log::info($message, [
            'event_type' => $typeName,
            'context' => $context,
            'user_id' => $userId,
            'screen_id' => $screenId,
            'related_content_id' => $relatedContentId,
        ]);
    }

    /**
     * Convenience wrapper for logging a generic failed-operation event.
     *
     * @param string $message
     * @param array<string, mixed> $context
     * @param string|null $userId
     * @param string|null $screenId
     * @param string|null $relatedContentId
     * @return void
     */
    protected function logFailure(
        string $message,
        array $context = [],
        ?string $userId = null,
        ?string $screenId = null,
        ?string $relatedContentId = null
    ): void {
        $this->logEvent(
            typeName: 'operation_failed',
            message: $message,
            context: $context,
            userId: $userId,
            screenId: $screenId,
            relatedContentId: $relatedContentId,
        );
    }
}
