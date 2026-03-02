<?php

namespace App\Support\Modules\TailwindBuild;

use App\Jobs\BuildTailwindAssets;

class TailwindBuildScheduler
{
    /**
     * Create a scheduler for module-triggered Tailwind builds.
     */
    public function __construct(
        private readonly TailwindBuildStatus $status,
    ) {
    }

    /**
     * Queue one delayed build so quick consecutive edits coalesce into a single job.
     */
    public function queueBuild(): void
    {
        if (!config('modules.tailwind_build.enabled', false)) {
            return;
        }

        $queue = (string) config('modules.tailwind_build.queue', 'module-builds');
        $delaySeconds = max(0, (int) config('modules.tailwind_build.delay_seconds', 15));

        $this->status->markQueued();

        // The job itself is unique; this dispatch updates status and schedules execution.
        BuildTailwindAssets::dispatch()
            ->onQueue($queue)
            ->delay(now()->addSeconds($delaySeconds));
    }
}
