<?php

namespace Tests\Feature\Modules;

use App\Jobs\BuildTailwindAssets;
use App\Support\Modules\TailwindBuild\ModuleTailwindSourceDumper;
use App\Support\Modules\TailwindBuild\TailwindBuildStatus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class BuildTailwindAssetsJobTest extends TestCase
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
     * Test it marks success when build command completes.
     *
     * @return void
     */
    public function test_it_marks_success_when_build_command_completes(): void
    {
        config([
            'modules.tailwind_build.command' => 'npm run build',
            'modules.tailwind_build.timeout_seconds' => 120,
        ]);

        Process::fake([
            '*' => Process::result('build finished', '', 0),
        ]);

        $job = new BuildTailwindAssets();
        $job->handle(
            app(TailwindBuildStatus::class),
            new class extends ModuleTailwindSourceDumper {
                /**
                 * Dump the generated output.
                 *
                 * @return void
                 */
                public function dump(): void
                {
                    // Avoid touching filesystem/db in this unit-level job test.
                }
            }
        );

        Process::assertRan('npm run build');

        $status = app(TailwindBuildStatus::class)->get();
        $this->assertSame('success', $status['state']);
        $this->assertNull($status['error']);
        $this->assertNotNull($status['finished_at']);
    }

    /**
     * Test it marks failed when build command returns non zero exit code.
     *
     * @return void
     */
    public function test_it_marks_failed_when_build_command_returns_non_zero_exit_code(): void
    {
        config([
            'modules.tailwind_build.command' => 'npm run build',
            'modules.tailwind_build.timeout_seconds' => 120,
        ]);

        Process::fake([
            '*' => Process::result('', 'vite build failed', 1),
        ]);

        $job = new BuildTailwindAssets();
        $job->handle(
            app(TailwindBuildStatus::class),
            new class extends ModuleTailwindSourceDumper {
                /**
                 * Dump the generated output.
                 *
                 * @return void
                 */
                public function dump(): void
                {
                    // Avoid touching filesystem/db in this unit-level job test.
                }
            }
        );

        Process::assertRan('npm run build');

        $status = app(TailwindBuildStatus::class)->get();
        $this->assertSame('failed', $status['state']);
        $this->assertStringContainsString('vite build failed', (string) $status['error']);
    }
}

