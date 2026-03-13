param(
    [string]$Profile = '',
    [string]$InputFile = '',
    [switch]$TruncateBeforeImport
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

. "$PSScriptRoot/_common.ps1"

if (-not $InputFile) {
    throw "InputFile is required. Example: -InputFile .ai/db_migration/exports/marconinertia_customers_sites_data_20260313_120000.sql"
}

$p = Select-MigrationProfile -ProfileName $Profile
$newDb = [string]$p.new_db
if (-not $newDb) { throw "Selected profile has no new_db configured." }

$resolvedInputFile = if ([System.IO.Path]::IsPathRooted($InputFile)) {
    $InputFile
} else {
    try {
        (Resolve-Path -Path $InputFile -ErrorAction Stop).Path
    }
    catch {
        throw "Unable to resolve InputFile from current directory. Provide absolute path or run from repo root. Input: $InputFile"
    }
}

if (-not (Test-Path $resolvedInputFile)) {
    throw "Input file not found: $resolvedInputFile"
}

if ($TruncateBeforeImport) {
    $truncateSql = @"
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE $newDb.internal_contacts;
TRUNCATE TABLE $newDb.timetables;
TRUNCATE TABLE $newDb.sites;
TRUNCATE TABLE $newDb.customers;
SET FOREIGN_KEY_CHECKS = 1;
"@

    Write-Host "Truncating domain tables in schema: $newDb"
    Invoke-QuerySql -Sql $truncateSql -Profile $p
}

Write-Host "Importing domain data from file: $resolvedInputFile"
Invoke-MySqlFile -SqlFilePath $resolvedInputFile -Profile $p

$verifySql = @"
SELECT 'customers_total' AS check_name, COUNT(*) AS check_value FROM $newDb.customers
UNION ALL
SELECT 'sites_total', COUNT(*) FROM $newDb.sites
UNION ALL
SELECT 'orphan_sites', COUNT(*)
FROM $newDb.sites s
LEFT JOIN $newDb.customers c ON c.id = s.customer_id
WHERE c.id IS NULL;
"@

Write-Host "Running post-import verification checks."
Invoke-QuerySql -Sql $verifySql -Profile $p
Write-Host "Import completed."
