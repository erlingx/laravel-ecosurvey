<?php

echo PHP_EOL;
echo '✅ DDEV Configuration'.PHP_EOL;
echo '📍 App: https://ecosurvey.ddev.site'.PHP_EOL;
echo '🔧 Admin: https://ecosurvey.ddev.site/admin'.PHP_EOL;
echo PHP_EOL;

// Check if queue worker is running
exec('bash -c "ps aux | grep -E \'queue:work\' | grep -v grep"', $output, $returnCode);
if ($returnCode === 0 && ! empty($output)) {
    echo '✓ Queue worker: RUNNING (auto-started)'.PHP_EOL;
} else {
    echo '✗ Queue worker: NOT RUNNING (should auto-start with ddev)'.PHP_EOL;
}

// Check if Vite is running
exec('bash -c "ps aux | grep -E \'vite.*--host\' | grep -v grep"', $viteOutput, $viteReturnCode);
if ($viteReturnCode === 0 && ! empty($viteOutput)) {
    echo '✓ Vite dev server: RUNNING'.PHP_EOL;
} else {
    echo '⚠ Vite dev server: NOT RUNNING'.PHP_EOL;
    echo '  Run in separate terminal: ddev npm run dev -- --host'.PHP_EOL;
}

echo PHP_EOL;
echo 'Commands:'.PHP_EOL;
echo '  Restart queue: ddev artisan queue:restart'.PHP_EOL;
echo '  Start Vite: ddev npm run dev -- --host'.PHP_EOL;
echo PHP_EOL;
