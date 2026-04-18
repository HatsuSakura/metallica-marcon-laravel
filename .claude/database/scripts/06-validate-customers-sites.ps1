param(
    [string]$Profile = ''
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

. "$PSScriptRoot/_common.ps1"

$p = Select-MigrationProfile -ProfileName $Profile
$newDb = [string]$p.new_db
if (-not $newDb) { throw "Selected profile has no new_db configured." }

$sql = @"
SELECT 'customers_total' AS check_name, COUNT(*) AS check_value FROM $newDb.customers
UNION ALL
SELECT 'sites_total', COUNT(*) FROM $newDb.sites
UNION ALL
SELECT 'timetables_total', COUNT(*) FROM $newDb.timetables
UNION ALL
SELECT 'internal_contacts_total', COUNT(*) FROM $newDb.internal_contacts;

SELECT 'orphan_sites' AS check_name, COUNT(*) AS check_value
FROM $newDb.sites s
LEFT JOIN $newDb.customers c ON c.id = s.customer_id
WHERE c.id IS NULL;

SELECT 'duplicate_customers_vat_number' AS check_name, COUNT(*) AS check_value
FROM (
    SELECT vat_number
    FROM $newDb.customers
    WHERE vat_number IS NOT NULL AND TRIM(vat_number) <> ''
    GROUP BY vat_number
    HAVING COUNT(*) > 1
) t;

SELECT 'duplicate_customers_tax_code' AS check_name, COUNT(*) AS check_value
FROM (
    SELECT tax_code
    FROM $newDb.customers
    WHERE tax_code IS NOT NULL AND TRIM(tax_code) <> ''
    GROUP BY tax_code
    HAVING COUNT(*) > 1
) t;

SELECT 'sites_invalid_site_type' AS check_name, COUNT(*) AS check_value
FROM $newDb.sites
WHERE site_type IS NOT NULL
  AND site_type NOT IN ('fully_operative', 'only_legal', 'only_stock');

SELECT 'sites_missing_coordinates' AS check_name, COUNT(*) AS check_value
FROM $newDb.sites
WHERE latitude IS NULL OR longitude IS NULL;
"@

Write-Host "Running domain validation checks on schema: $newDb"
Invoke-QuerySql -Sql $sql -Profile $p
Write-Host "Validation query batch completed."
