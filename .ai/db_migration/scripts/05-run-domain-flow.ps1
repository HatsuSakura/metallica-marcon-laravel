param(
    [string]$Profile = '',
    [switch]$IncludeWithdraws,
    [switch]$SkipTruncate,
    [switch]$SkipLoadLegacyDump
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$scriptRoot = $PSScriptRoot

if (-not $SkipLoadLegacyDump) {
    & (Join-Path $scriptRoot '00-load-legacy-dump-into-tempdb.ps1') `
        -Profile $Profile
}

if (-not $SkipTruncate) {
    & (Join-Path $scriptRoot '00-truncate-customers-sites.ps1') `
        -Profile $Profile `
        -IncludeWithdraws:$IncludeWithdraws
}

& (Join-Path $scriptRoot '01-import-customers.ps1') `
    -Profile $Profile

& (Join-Path $scriptRoot '02-show-customer-exceptions.ps1') `
    -Profile $Profile

Write-Host "Resolve customer exceptions before continuing with step 03."
Write-Host "When ready, run: 03-import-sites-timetables-contacts.ps1"
if ($IncludeWithdraws) {
    Write-Host "Then optionally run: 04-import-withdraws-optional.ps1"
}
