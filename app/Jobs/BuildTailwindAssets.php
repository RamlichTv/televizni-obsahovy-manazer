<?php

namespace App\Jobs;

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

class BuildTailwindAssets implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;
    public int $timeout;

    /**
     * Create a new instance.
     */
    public function __construct()
    {
        $this->timeout = max(10, (int) config('modules.tailwind_build.timeout_seconds', 600));
        $this->onQueue((string) config('modules.tailwind_build.queue', 'module-builds'));
    }

    /**
     * Unique id.
     *
     * @return string
     */
    public function uniqueId(): string
    {
        return 'tailwind-assets-build';
    }

    /**
     * Unique for.
     *
     * @return int
     */
    public function uniqueFor(): int
    {
        return max(60, $this->timeout + 60);
    }

    /**
     * Handle.
     *
     * @param TailwindBuildStatus $status
     * @param ModuleTailwindSourceDumper $sourceDumper
     * @return void
     */
    public function handle(TailwindBuildStatus $status, ModuleTailwindSourceDumper $sourceDumper): void
    {
        $status->markRunning();
        // Refresh the generated scan file before each build so latest module classes are included.
        $sourceDumper->dump();

        $command = trim((string) config('modules.tailwind_build.command', 'npm run build'));
        if ($command === '') {
            $status->markFailed('Tailwind build command is empty.');

            return;
        }

        $result = Process::path(base_path())
            ->timeout($this->timeout)
            ->run($command);

        if ($result->successful()) {
            $status->markSucceeded();

            return;
        }

        $errorOutput = trim((string) $result->errorOutput());
        $output = trim((string) $result->output());

        $message = $errorOutput !== '' ? $errorOutput : $output;
        if ($message === '') {
            $message = 'Tailwind build failed without output.';
        }

        $status->markFailed($message);
    }

    /**
     * Failed.
     *
     * @param Throwable|null $exception
     * @return void
     */
    public function failed(?Throwable $exception): void
    {
        // Persist failure in cache even when the worker marks the job as failed unexpectedly.
        app(TailwindBuildStatus::class)->markFailed($exception?->getMessage() ?? 'Tailwind build job failed.');
    }
}
