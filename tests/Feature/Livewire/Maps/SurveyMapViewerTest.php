<?php

namespace Tests\Feature\Livewire\Maps;

use Livewire\Volt\Volt;
use Tests\TestCase;

class SurveyMapViewerTest extends TestCase
{
    public function test_it_can_render(): void
    {
        $component = Volt::test('maps.survey-map-viewer');

        $component->assertSee('');
    }
}
