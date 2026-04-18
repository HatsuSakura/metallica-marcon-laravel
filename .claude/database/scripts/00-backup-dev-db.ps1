param(
    [string]$Profile = '',
    [string]$OutputDir = '',
    [string]$OutputFile = ''
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

. "$PSScriptRoot/_common.ps1"

$p = Select-MigrationProfile -ProfileName $Profile
$newDb = [string]$p.new_db
if (-not $newDb) { throw "Selected profile has no new_db configured." }

$mode = if ($p.execution_mode) { [string]$p.execution_mode } else { 'cli' }
$dbHost = if ($p.mysql_host) { [string]$p.mysql_host } else { '127.0.0.1' }
$port = if ($p.mysql_port) { [int]$p.mysql_port } else { 3306 }
$user = [string]$p.mysql_user
$pass = if ($p.mysql_password) { [string]$p.mysql_password } else { '' }
if (-not $user) { throw "Profile mysql_user is required." }

$resolvedOutputDir = if ($OutputDir) { $OutputDir } else { Join-Path (Split-Path $PSScriptRoot -Parent) 'backups' }
if (-not (Test-Path $resolvedOutputDir)) {
    New-Item -ItemType Directory -Path $resolvedOutputDir | Out-Null
}

$resolvedOutputFile = if ($OutputFile) {
    $OutputFile
} else {
    "{0}_backup_{1}.sql" -f $newDb, (Get-Date -Format 'yyyyMMdd_HHmmss')
}

$backupPath = if ([System.IO.Path]::IsPathRooted($resolvedOutputFile)) {
    $resolvedOutputFile
} else {
    Join-Path $resolvedOutputDir $resolvedOutputFile
}

Write-Host "Creating backup of schema '$newDb' to: $backupPath"

if ($mode -eq 'docker') {
    $container = [string]$p.docker_container
    if (-not $container) {
        throw "Profile docker_container is required for execution_mode=docker."
    }

    $args = @(
        'exec', '-i', $container, 'mysqldump',
        "--host=$dbHost",
        "--port=$port",
        "--user=$user",
        '--default-character-set=utf8mb4',
        '--single-transaction',
        '--routines',
        '--triggers',
        '--events',
        $newDb
    )
    if ($pass) { $args += "-p$pass" }

    & docker @args | Out-File -FilePath $backupPath -Encoding utf8
    if ($LASTEXITCODE -ne 0) {
        throw "docker/mysqldump exited with code $LASTEXITCODE"
    }
}
else {
    $dumpExe = if ($p.PSObject.Properties.Name -contains 'mysqldump_exe' -and $p.mysqldump_exe) { [string]$p.mysqldump_exe } else { 'mysqldump' }
    $args = @(
        "--host=$dbHost",
        "--port=$port",
        "--user=$user",
        '--default-character-set=utf8mb4',
        '--single-transaction',
        '--routines',
        '--triggers',
        '--events',
        $newDb
    )

    if ($pass) { $env:MYSQL_PWD = $pass }
    try {
        & $dumpExe @args | Out-File -FilePath $backupPath -Encoding utf8
        if ($LASTEXITCODE -ne 0) {
            throw "mysqldump exited with code $LASTEXITCODE"
        }
    }
    finally {
        if ($pass) {
            Remove-Item Env:MYSQL_PWD -ErrorAction SilentlyContinue
        }
    }
}

if (-not (Test-Path $backupPath)) {
    throw "Backup file not generated: $backupPath"
}

$size = (Get-Item $backupPath).Length
if ($size -le 0) {
    throw "Backup file is empty: $backupPath"
}

Write-Host ("Backup completed: {0} ({1} bytes)" -f $backupPath, $size)
