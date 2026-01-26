<?php

namespace Tests\Feature\Livewire\DataCollection;

use App\Models\Campaign;
use App\Models\User;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ReadingFormTest extends TestCase
{
    public function test_it_can_render(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $component = Volt::test('data-collection.reading-form', [
            'campaignId' => $campaign->id,
        ]);

        $component->assertOk();
    }
}
