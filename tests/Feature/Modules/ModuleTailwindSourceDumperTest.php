<?php

namespace Tests\Feature\Modules;

use App\Support\Modules\TailwindBuild\ModuleTailwindSourceDumper;
use ReflectionMethod;
use Tests\TestCase;

class ModuleTailwindSourceDumperTest extends TestCase
{
    /**
     * Test tokenizer keeps arbitrary value classes with spaces.
     *
     * @return void
     */
    public function test_tokenizer_keeps_arbitrary_value_classes_with_spaces(): void
    {
        $dumper = new ModuleTailwindSourceDumper();
        $method = new ReflectionMethod(ModuleTailwindSourceDumper::class, 'tokenizeClassAttribute');
        $method->setAccessible(true);

        $tokens = $method->invoke(
            $dumper,
            "before:content-['Hello world'] after:bg-rose-500 hover:after:scale-110"
        );

        $this->assertContains("before:content-['Hello world']", $tokens);
        $this->assertContains('after:bg-rose-500', $tokens);
        $this->assertContains('hover:after:scale-110', $tokens);
        $this->assertCount(3, $tokens);
    }

    /**
     * Test extract tokens decodes html entities inside class attribute.
     *
     * @return void
     */
    public function test_extract_tokens_decodes_html_entities_inside_class_attribute(): void
    {
        $dumper = new ModuleTailwindSourceDumper();
        $method = new ReflectionMethod(ModuleTailwindSourceDumper::class, 'extractClassTokens');
        $method->setAccessible(true);

        $tokens = $method->invoke(
            $dumper,
            '<div class="before:content-[&quot;Hello world&quot;] after:absolute text-sm"></div>'
        );

        $this->assertContains('before:content-["Hello world"]', $tokens);
        $this->assertContains('after:absolute', $tokens);
        $this->assertContains('text-sm', $tokens);
    }
}

