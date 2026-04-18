param(
    [string]$Profile = ''
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

. "$PSScriptRoot/_common.ps1"

$p = Select-MigrationProfile -ProfileName $Profile
$OldDb = [string]$p.old_db
$NewDb = [string]$p.new_db
if (-not $OldDb) { throw "Selected profile has no old_db configured." }
if (-not $NewDb) { throw "Selected profile has no new_db configured." }

$sqlFile = Join-Path (Split-Path $PSScriptRoot -Parent) 'TRUE_LEGACY_02_SITES_AND_RELATED_IMPORT.sql'
Invoke-MigrationSqlFile -SqlFilePath $sqlFile -OldDb $OldDb -NewDb $NewDb -Profile $p
Write-Host "Sites/timetables/internal_contacts import step completed."
