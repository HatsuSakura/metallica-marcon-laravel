param(
    [string]$Profile = '',
    [switch]$IncludeWithdraws
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

. "$PSScriptRoot/_common.ps1"

$p = Select-MigrationProfile -ProfileName $Profile
$NewDb = [string]$p.new_db
if (-not $NewDb) { throw "Selected profile has no new_db configured." }

$truncateWithdraws = if ($IncludeWithdraws) { "TRUNCATE TABLE $NewDb.withdraws;" } else { "-- TRUNCATE TABLE $NewDb.withdraws;" }

$sql = @"
SET FOREIGN_KEY_CHECKS = 0;
$truncateWithdraws
TRUNCATE TABLE $NewDb.internal_contacts;
TRUNCATE TABLE $NewDb.timetables;
TRUNCATE TABLE $NewDb.sites;
TRUNCATE TABLE $NewDb.customers;
DROP TABLE IF EXISTS $NewDb.legacy_customers_stage;
SET FOREIGN_KEY_CHECKS = 1;
"@

Write-Host "Truncating target domain tables in schema: $NewDb"
Invoke-QuerySql -Sql $sql -Profile $p
Write-Host "Done."
