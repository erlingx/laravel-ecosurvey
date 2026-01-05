<?php

namespace Tests\Feature\Livewire;

use Livewire\Volt\Volt;
use Tests\TestCase;

class DatapointcaptureTest extends TestCase
{
    public function test_it_can_render(): void
    {
        $component = Volt::test('datapointcapture');

        $component->assertSee('');
    }
}
