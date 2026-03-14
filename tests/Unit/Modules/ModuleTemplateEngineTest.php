<?php

namespace Tests\Unit\Modules;

use App\Support\Modules\Template\ModuleTemplateEngine;
use PHPUnit\Framework\TestCase;

class ModuleTemplateEngineTest extends TestCase
{
    /**
     * Test Mustache rendering keeps legacy each aliases working.
     *
     * @return void
     */
    public function test_it_renders_each_aliases_through_mustache(): void
    {
        $engine = new ModuleTemplateEngine();

        $html = $engine->render(
            '<section>{{#each items}}<p>{{ name }}</p>{{/each}}</section>',
            [
                'items' => [
                    ['name' => 'First'],
                    ['name' => 'Second'],
                ],
            ],
        );

        $this->assertSame('<section><p>First</p><p>Second</p></section>', $html);
    }

    /**
     * Test Mustache rendering still escapes bound values.
     *
     * @return void
     */
    public function test_it_escapes_bound_values(): void
    {
        $engine = new ModuleTemplateEngine();

        $html = $engine->render(
            '<section>{{ module.name }}</section>',
            [
                'module' => [
                    'name' => '<script>alert(1)</script>',
                ],
            ],
        );

        $this->assertSame('<section>&lt;script&gt;alert(1)&lt;/script&gt;</section>', $html);
    }
}
