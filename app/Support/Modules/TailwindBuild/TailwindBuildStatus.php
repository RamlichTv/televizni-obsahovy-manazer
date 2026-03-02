<?php

namespace App\Support\Modules\TailwindBuild;

use Illuminate\Support\Facades\Cache;

class TailwindBuildStatus
{
    private const CACHE_KEY = 'modules.tailwind_build.status';

    /**
     * Get the current build status snapshot.
     *
     * @return array
     */
    public function get(): array
    {
        $value = Cache::get(self::CACHE_KEY, []);
        if (!is_array($value)) {
            $value = [];
        }

        return array_merge($this->defaultStatus(), $value);
    }

    /**
     * Mark the build as queued.
     *
     * @return void
     */
    public function markQueued(): void
    {
        $this->put([
            'state' => 'queued',
            'queued_at' => now()->toIso8601String(),
            'started_at' => null,
            'finished_at' => null,
            'error' => null,
        ]);
    }

    /**
     * Mark the build as running.
     *
     * @return void
     */
    public function markRunning(): void
    {
        $this->put([
            'state' => 'running',
            'started_at' => now()->toIso8601String(),
            'finished_at' => null,
            'error' => null,
        ]);
    }

    /**
     * Mark the build as successful.
     *
     * @return void
     */
    public function markSucceeded(): void
    {
        $this->put([
            'state' => 'success',
            'finished_at' => now()->toIso8601String(),
            'error' => null,
        ]);
    }

    /**
     * Mark the build as failed.
     *
     * @param string $message
     * @return void
     */
    public function markFailed(string $message): void
    {
        $this->put([
            'state' => 'failed',
            'finished_at' => now()->toIso8601String(),
            'error' => trim($message) !== '' ? $message : 'Unknown Tailwind build failure.',
        ]);
    }

    /**
     * Persist status updates.
     *
     * @param array $values
     * @return void
     */
    private function put(array $values): void
    {
        Cache::forever(self::CACHE_KEY, array_merge($this->get(), $values, [
            'updated_at' => now()->toIso8601String(),
        ]));
    }

    /**
     * Return the default empty status structure.
     *
     * @return array
     */
    private function defaultStatus(): array
    {
        return [
            'state' => 'idle',
            'queued_at' => null,
            'started_at' => null,
            'finished_at' => null,
            'updated_at' => null,
            'error' => null,
        ];
    }
}
