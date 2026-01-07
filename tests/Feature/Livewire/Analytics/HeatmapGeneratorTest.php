<?php

namespace Tests\Feature\Livewire\Analytics;

use Livewire\Volt\Volt;
use Tests\TestCase;

class HeatmapGeneratorTest extends TestCase
{
    public function test_it_can_render(): void
    {
        $component = Volt::test('analytics.heatmap-generator');

        $component->assertSee('');
    }
}
