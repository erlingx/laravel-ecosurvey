# PowerShell configuration for DDEV output fix
# Source this in your PowerShell profile or run manually

# Fix output encoding to prevent buffering
$OutputEncoding = [System.Text.Encoding]::UTF8

# Disable progress bars that cause buffering
$ProgressPreference = 'SilentlyContinue'

# Helper function for DDEV commands with unbuffered output
function ddev-exec {
    param(
        [Parameter(Mandatory=$true, Position=0, ValueFromRemainingArguments=$true)]
        [string[]]$Command
    )

    $cmd = $Command -join ' '
    # Force line buffering and UTF-8
    & ddev exec bash -c "stdbuf -oL -eL $cmd 2>&1"
}

# Helper for running tests with immediate output
function ddev-test {
    param(
        [Parameter(ValueFromRemainingArguments=$true)]
        [string[]]$TestArgs
    )

    $args = $TestArgs -join ' '
    if ($args) {
        ddev exec bash -c "stdbuf -oL php artisan test $args 2>&1"
    } else {
        ddev exec bash -c "stdbuf -oL php artisan test 2>&1"
    }
}

Write-Host "✅ DDEV PowerShell fixes loaded!" -ForegroundColor Green
Write-Host "Use 'ddev-exec' for unbuffered command output" -ForegroundColor Cyan
Write-Host "Use 'ddev-test' for unbuffered test output" -ForegroundColor Cyan
Write-Host ""
Write-Host "Example: ddev-test --filter=DataExportServiceTest" -ForegroundColor Yellow

