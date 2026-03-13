param(
    [string]$Profile = '',
    [string]$DumpFile = ''
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

. "$PSScriptRoot/_common.ps1"

function Resolve-DumpPath {
    param(
        [Parameter(Mandatory = $true)][string]$InputPath
    )

    if ([System.IO.Path]::IsPathRooted($InputPath)) {
        if (Test-Path $InputPath) { return (Resolve-Path $InputPath).Path }
        return ''
    }

    $repoRoot = Split-Path (Split-Path (Split-Path $PSScriptRoot -Parent) -Parent) -Parent
    $candidates = @(
        $InputPath,
        (Join-Path $PSScriptRoot $InputPath),
        (Join-Path (Split-Path $PSScriptRoot -Parent) $InputPath),
        (Join-Path $repoRoot $InputPath)
    )

    foreach ($candidate in $candidates) {
        if (Test-Path $candidate) {
            return (Resolve-Path $candidate).Path
        }
    }

    return ''
}

$p = Select-MigrationProfile -ProfileName $Profile
$oldDb = [string]$p.old_db
if (-not $oldDb) { throw "Selected profile has no old_db configured (temp legacy schema name)." }

$configuredDump = if ($DumpFile) { $DumpFile } elseif ($p.legacy_dump_file) { [string]$p.legacy_dump_file } else { '' }
if (-not $configuredDump) { throw "No dump file specified. Set -DumpFile or profile.legacy_dump_file." }

$resolvedDump = Resolve-DumpPath -InputPath $configuredDump
if (-not $resolvedDump) { throw "Dump file not found: $configuredDump" }

$tmpSql = [System.IO.Path]::GetTempFileName()
try {
    $header = @"
DROP DATABASE IF EXISTS $oldDb;
CREATE DATABASE $oldDb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE $oldDb;
"@

    Set-Content -Path $tmpSql -Value $header -NoNewline -Encoding UTF8
    Add-Content -Path $tmpSql -Value "`r`n"
    Add-Content -Path $tmpSql -Value (Get-Content -Path $resolvedDump -Raw)

    Invoke-MySqlFile -SqlFilePath $tmpSql -Profile $p
    Write-Host "Legacy dump loaded into temp schema: $oldDb"
}
finally {
    if (Test-Path $tmpSql) {
        Remove-Item $tmpSql -Force
    }
}
