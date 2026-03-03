<?php

namespace App\Http\Controllers;

use App\Support\Events\EventLogger;
use Illuminate\Support\Facades\Log;

abstract class Controller
{
    /**
     * Log event.
     *
     * @param string $typeName
     * @param string $message
     * @param array $context
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
     * Log failure.
     *
     * @param string $message
     * @param array $context
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
