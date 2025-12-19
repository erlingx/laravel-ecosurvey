<?php

namespace Tests\Feature\Livewire\DataCollection;

use Livewire\Volt\Volt;
use Tests\TestCase;

class ReadingFormTest extends TestCase
{
    public function test_it_can_render(): void
    {
        $component = Volt::test('data-collection.reading-form');

        $component->assertSee('');
    }
}
