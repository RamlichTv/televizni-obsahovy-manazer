<?php

use App\Models\ContentObject;
use App\Support\Events\EventLogger;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

// Keep the stock demo command available for local sanity checks.
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Archive expired published content and mirror scheduler activity into the event stream.
Artisan::command('content:archive', function () {
    try {
        $now = now();
        $archived = ContentObject::query()
            ->where('status_id', 2)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $now)
            ->update([
                'status_id' => 3,
                'archived_at' => $now,
            ]);

        if ($archived > 0) {
            Log::info("Scheduler: {$archived} content objects have been automatically archived.");
            EventLogger::record(
                typeName: 'content_auto_archived',
                message: $archived . ' content objects were automatically archived by scheduler.',
                context: [
                    'action' => 'scheduler.content.archive',
                    'count' => $archived,
                    'executed_at' => $now->toIso8601String(),
                ],
            );
        }
    } catch (Throwable $exception) {
        // Failed scheduler runs should show up in both app logs and audit events.
        Log::error('Scheduler: automatic content archiving failed.', [
            'exception' => $exception->getMessage(),
        ]);
        EventLogger::record(
            typeName: 'operation_failed',
            message: 'Automatic content archiving failed.',
            context: [
                'action' => 'scheduler.content.archive',
                'error' => $exception->getMessage(),
            ],
        );

        throw $exception;
    }
})->purpose('Archives all expired content objects');

// Publish content whose scheduled publish time has been reached.
Artisan::command('content:publish', function () {
    try {
        $now = now();
        $published = ContentObject::query()
            ->where('status_id', 1)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', $now)
            ->update(['status_id' => 2]);

        if ($published > 0) {
            Log::info("Scheduler: {$published} content objects have been automatically published.");
            EventLogger::record(
                typeName: 'content_auto_published',
                message: $published . ' content objects were automatically published by scheduler.',
                context: [
                    'action' => 'scheduler.content.publish',
                    'count' => $published,
                    'executed_at' => $now->toIso8601String(),
                ],
            );
        }
    } catch (Throwable $exception) {
        Log::error('Scheduler: automatic content publishing failed.', [
            'exception' => $exception->getMessage(),
        ]);
        EventLogger::record(
            typeName: 'operation_failed',
            message: 'Automatic content publishing failed.',
            context: [
                'action' => 'scheduler.content.publish',
                'error' => $exception->getMessage(),
            ],
        );

        throw $exception;
    }
})->purpose('Publishes all content objects scheduled for publishing');


// Run the lightweight publish/archive maintenance loop once per minute.
Schedule::call(function () {
    Artisan::call('content:publish');
    Artisan::call('content:archive');
})->everyMinute();
