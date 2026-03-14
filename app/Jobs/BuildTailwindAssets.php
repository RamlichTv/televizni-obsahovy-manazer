<?php

namespace App\Jobs;

use App\Support\Events\EventLogger;
use App\Support\Modules\TailwindBuild\ModuleTailwindSourceDumper;
use App\Support\Modules\TailwindBuild\TailwindBuildStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Process;
use Throwable;

/**
 * Executes the queued Tailwind build and mirrors build outcomes into the event stream.
 */
class BuildTailwindAssets implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;
    public int $timeout;

    /**
     * Initialize queue settings from module Tailwind build configuration.
     */
    public function __construct()
    {
        $this->timeout = max(10, (int) config('modules.tailwind_build.timeout_seconds', 600));
        $this->onQueue((string) config('modules.tailwind_build.queue', 'module-builds'));
    }

    /**
     * Keep concurrent dispatches coalesced into one build job.
     */
    public function uniqueId(): string
    {
        return 'tailwind-assets-build';
    }

    /**
     * Retain uniqueness long enough for the queued build and retry window.
     */
    public function uniqueFor(): int
    {
        return max(60, $this->timeout + 60);
    }

    /**
     * Run the build command, update cached status, and emit success/failure events.
     */
    public function handle(TailwindBuildStatus $status, ModuleTailwindSourceDumper $sourceDumper): void
    {
        $status->markRunning();
        // Refresh the generated scan file before each build so latest module classes are included.
        $sourceDumper->dump();

        $command = trim((string) config('modules.tailwind_build.command', 'npm run build'));
        if ($command === '') {
            $status->markFailed('Tailwind build command is empty.');
            // Emit an audit entry even for configuration mistakes so build issues stay visible.
            EventLogger::record(
                typeName: 'module_tailwind_build_failed',
                message: 'Tailwind build failed because the configured command is empty.',
                context: [
                    'action' => 'modules.tailwind.build',
                    'reason' => 'empty_command',
                    'timeout_seconds' => $this->timeout,
                ],
            );

            return;
        }

        $result = Process::path(base_path())
            ->timeout($this->timeout)
            ->run($command);

        if ($result->successful()) {
            $status->markSucceeded();
            // Successful rebuilds help correlate module edits with generated assets.
            EventLogger::record(
                typeName: 'module_tailwind_build_succeeded',
                message: 'Tailwind build completed successfully.',
                context: [
                    'action' => 'modules.tailwind.build',
                    'command' => $command,
                    'timeout_seconds' => $this->timeout,
                ],
            );

            return;
        }

        $errorOutput = trim((string) $result->errorOutput());
        $output = trim((string) $result->output());

        $message = $errorOutput !== '' ? $errorOutput : $output;
        if ($message === '') {
            $message = 'Tailwind build failed without output.';
        }

        $status->markFailed($message);
        // Persist the most useful available failure output into both cache state and events.
        EventLogger::record(
            typeName: 'module_tailwind_build_failed',
            message: 'Tailwind build failed.',
            context: [
                'action' => 'modules.tailwind.build',
                'command' => $command,
                'timeout_seconds' => $this->timeout,
                'error' => $message,
            ],
        );
    }

    /**
     * Capture worker-level failures that bypass the normal `handle()` failure path.
     */
    public function failed(?Throwable $exception): void
    {
        // Persist failure in cache even when the worker marks the job as failed unexpectedly.
        $message = $exception?->getMessage() ?? 'Tailwind build job failed.';
        app(TailwindBuildStatus::class)->markFailed($message);
        EventLogger::record(
            typeName: 'module_tailwind_build_failed',
            message: 'Tailwind build job failed unexpectedly.',
            context: [
                'action' => 'modules.tailwind.build',
                'error' => $message,
                'timeout_seconds' => $this->timeout,
            ],
        );
    }
}
