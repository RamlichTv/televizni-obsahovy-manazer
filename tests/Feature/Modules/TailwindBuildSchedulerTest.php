<?php

namespace Tests\Feature\Modules;

use App\Jobs\BuildTailwindAssets;
use App\Support\Modules\TailwindBuild\TailwindBuildScheduler;
use App\Support\Modules\TailwindBuild\TailwindBuildStatus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TailwindBuildSchedulerTest extends TestCase
{
    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);
        Cache::flush();
    }

    /**
     * Test it dispatches tailwind build job when enabled.
     *
     * @return void
     */
    public function test_it_dispatches_tailwind_build_job_when_enabled(): void
    {
        Queue::fake();

        config([
            'modules.tailwind_build.enabled' => true,
            'modules.tailwind_build.queue' => 'module-builds',
            'modules.tailwind_build.delay_seconds' => 15,
        ]);

        app(TailwindBuildScheduler::class)->queueBuild();

        Queue::assertPushed(BuildTailwindAssets::class, function (BuildTailwindAssets $job): bool {
            return $job->queue === 'module-builds';
        });

        $status = app(TailwindBuildStatus::class)->get();
        $this->assertSame('queued', $status['state']);
        $this->assertNotNull($status['queued_at']);
    }

    /**
     * Test it does not dispatch when auto build is disabled.
     *
     * @return void
     */
    public function test_it_does_not_dispatch_when_auto_build_is_disabled(): void
    {
        Queue::fake();

        config(['modules.tailwind_build.enabled' => false]);

        app(TailwindBuildScheduler::class)->queueBuild();

        Queue::assertNothingPushed();

        $status = app(TailwindBuildStatus::class)->get();
        $this->assertSame('idle', $status['state']);
    }
}

