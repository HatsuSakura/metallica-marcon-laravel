param(
    [string]$Profile = ''
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

. "$PSScriptRoot/_common.ps1"

$p = Select-MigrationProfile -ProfileName $Profile
$NewDb = [string]$p.new_db
if (-not $NewDb) { throw "Selected profile has no new_db configured." }

$sql = @"
SELECT COUNT(*) AS total_stage_rows FROM $NewDb.legacy_customers_stage;
SELECT COUNT(*) AS rows_with_errors FROM $NewDb.legacy_customers_stage WHERE import_error IS NOT NULL;
SELECT * 
FROM $NewDb.legacy_customers_stage
WHERE import_error IS NOT NULL
ORDER BY id;
"@

Invoke-QuerySql -Sql $sql -Profile $p
