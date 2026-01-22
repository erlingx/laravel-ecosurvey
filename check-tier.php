<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== All Users and Their Subscription Tiers ===" . PHP_EOL . PHP_EOL;

$users = App\Models\User::all();

foreach ($users as $user) {
    echo "User: {$user->email} (ID: {$user->id})" . PHP_EOL;
    echo "  Tier: " . $user->subscriptionTier() . PHP_EOL;
    echo "  Subscribed: " . ($user->subscribed('default') ? 'YES' : 'NO') . PHP_EOL;

    if ($user->subscribed('default')) {
        $subscription = $user->subscription('default');
        $item = $subscription->items()->first();
        if ($item) {
            echo "  Price ID: " . $item->stripe_price . PHP_EOL;
        }

        $limits = $user->getUsageLimit('satellite_analyses');
        echo "  Satellite Analyses Limit: " . ($limits === PHP_INT_MAX ? 'Unlimited' : $limits) . PHP_EOL;
    }
    echo PHP_EOL;
}
