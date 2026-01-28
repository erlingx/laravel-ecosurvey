<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\User;

test('policy allows owner to view their campaign', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['user_id' => $owner->id]);

    dump([
        'owner_id' => $owner->id,
        'owner_id_type' => gettype($owner->id),
        'campaign_user_id' => $campaign->user_id,
        'campaign_user_id_type' => gettype($campaign->user_id),
        'are_equal' => $owner->id === $campaign->user_id,
        'are_loose_equal' => $owner->id == $campaign->user_id,
    ]);

    expect($owner->can('view', $campaign))->toBeTrue();
});

test('policy denies non-owner from viewing campaign', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $campaign = Campaign::factory()->create(['user_id' => $owner->id]);

    expect($otherUser->can('view', $campaign))->toBeFalse();
});
