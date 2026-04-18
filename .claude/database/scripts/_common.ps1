Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

function Get-MigrationProfiles {
    $configPath = Join-Path $PSScriptRoot 'db_profiles.json'
    if (-not (Test-Path $configPath)) {
        throw "Config file not found: $configPath"
    }

    $cfg = Get-Content -Path $configPath -Raw | ConvertFrom-Json
    $profiles = @($cfg.profiles)
    if ($profiles.Length -eq 0) {
        throw "No profiles found in: $configPath"
    }
    return $profiles
}

function Select-MigrationProfile {
    param(
        [string]$ProfileName = ''
    )

    $profiles = @(Get-MigrationProfiles)

    if ($ProfileName) {
        $match = $profiles | Where-Object { $_.name -eq $ProfileName } | Select-Object -First 1
        if (-not $match) {
            throw "Profile '$ProfileName' not found in db_profiles.json"
        }
        return $match
    }

    Write-Host "Available DB profiles:"
    for ($i = 0; $i -lt $profiles.Length; $i++) {
        $p = $profiles[$i]
        $oldDb = if ($p.old_db) { $p.old_db } else { '-' }
        Write-Host ("[{0}] {1} (new_db={2}, old_db={3}, mode={4})" -f ($i + 1), $p.name, $p.new_db, $oldDb, $p.execution_mode)
    }

    $choice = Read-Host "Select profile number"
    if (-not [int]::TryParse($choice, [ref]$null)) {
        throw "Invalid choice: $choice"
    }

    $idx = [int]$choice - 1
    if ($idx -lt 0 -or $idx -ge $profiles.Length) {
        throw "Choice out of range: $choice"
    }

    return $profiles[$idx]
}

function Invoke-MySql {
    param(
        [Parameter(Mandatory = $true)][string]$SqlText,
        [Parameter(Mandatory = $true)]$Profile
    )

    $mode = if ($Profile.execution_mode) { [string]$Profile.execution_mode } else { 'cli' }
    $dbHost = if ($Profile.mysql_host) { [string]$Profile.mysql_host } else { '127.0.0.1' }
    $port = if ($Profile.mysql_port) { [int]$Profile.mysql_port } else { 3306 }
    $user = [string]$Profile.mysql_user
    $pass = if ($Profile.mysql_password) { [string]$Profile.mysql_password } else { '' }

    if (-not $user) {
        throw "Profile mysql_user is required"
    }

    if ($mode -eq 'docker') {
        $container = [string]$Profile.docker_container
        if (-not $container) {
            throw "Profile docker_container is required for execution_mode=docker"
        }

        $args = @('exec', '-i', $container, 'mysql', "--host=$dbHost", "--port=$port", "--user=$user", '--default-character-set=utf8mb4')
        if ($pass) { $args += "-p$pass" }

        $SqlText | & docker @args
        if ($LASTEXITCODE -ne 0) {
            throw "docker/mysql exited with code $LASTEXITCODE"
        }
        return
    }

    $mysqlExe = if ($Profile.PSObject.Properties.Name -contains 'mysql_exe' -and $Profile.mysql_exe) { [string]$Profile.mysql_exe } else { 'mysql' }
    $args = @("--host=$dbHost", "--port=$port", "--user=$user", '--default-character-set=utf8mb4')
    if ($pass) { $env:MYSQL_PWD = $pass }
    try {
        $SqlText | & $mysqlExe @args
        if ($LASTEXITCODE -ne 0) {
            throw "mysql exited with code $LASTEXITCODE"
        }
    }
    finally {
        if ($pass) {
            Remove-Item Env:MYSQL_PWD -ErrorAction SilentlyContinue
        }
    }
}

function Invoke-MySqlFile {
    param(
        [Parameter(Mandatory = $true)][string]$SqlFilePath,
        [Parameter(Mandatory = $true)]$Profile
    )

    if (-not (Test-Path $SqlFilePath)) {
        throw "SQL file not found: $SqlFilePath"
    }

    $mode = if ($Profile.execution_mode) { [string]$Profile.execution_mode } else { 'cli' }
    $dbHost = if ($Profile.mysql_host) { [string]$Profile.mysql_host } else { '127.0.0.1' }
    $port = if ($Profile.mysql_port) { [int]$Profile.mysql_port } else { 3306 }
    $user = [string]$Profile.mysql_user
    $pass = if ($Profile.mysql_password) { [string]$Profile.mysql_password } else { '' }

    if ($mode -eq 'docker') {
        $container = [string]$Profile.docker_container
        if (-not $container) {
            throw "Profile docker_container is required for execution_mode=docker"
        }

        $args = @('exec', '-i', $container, 'mysql', "--host=$dbHost", "--port=$port", "--user=$user", '--default-character-set=utf8mb4')
        if ($pass) { $args += "-p$pass" }

        Write-Host "Executing SQL file via docker: $SqlFilePath"
        Get-Content -Path $SqlFilePath -Raw | & docker @args
        if ($LASTEXITCODE -ne 0) {
            throw "docker/mysql exited with code $LASTEXITCODE"
        }
        return
    }

    $mysqlExe = if ($Profile.PSObject.Properties.Name -contains 'mysql_exe' -and $Profile.mysql_exe) { [string]$Profile.mysql_exe } else { 'mysql' }
    $args = @("--host=$dbHost", "--port=$port", "--user=$user", '--default-character-set=utf8mb4')
    if ($pass) { $env:MYSQL_PWD = $pass }
    try {
        Write-Host "Executing SQL file via cli: $SqlFilePath"
        Get-Content -Path $SqlFilePath -Raw | & $mysqlExe @args
        if ($LASTEXITCODE -ne 0) {
            throw "mysql exited with code $LASTEXITCODE"
        }
    }
    finally {
        if ($pass) {
            Remove-Item Env:MYSQL_PWD -ErrorAction SilentlyContinue
        }
    }
}

function Invoke-MigrationSqlFile {
    param(
        [Parameter(Mandatory = $true)][string]$SqlFilePath,
        [Parameter(Mandatory = $true)][string]$OldDb,
        [Parameter(Mandatory = $true)][string]$NewDb,
        [Parameter(Mandatory = $true)]$Profile
    )

    if (-not (Test-Path $SqlFilePath)) {
        throw "SQL file not found: $SqlFilePath"
    }

    $rawSql = Get-Content -Path $SqlFilePath -Raw
    $resolvedSql = $rawSql.Replace('OLD_DB', $OldDb).Replace('NEW_DB', $NewDb)

    $tmpFile = [System.IO.Path]::GetTempFileName()
    try {
        Set-Content -Path $tmpFile -Value $resolvedSql -NoNewline -Encoding UTF8

        Write-Host "Executing SQL: $SqlFilePath"
        $sqlText = Get-Content -Path $tmpFile -Raw
        Invoke-MySql -SqlText $sqlText -Profile $Profile
    }
    finally {
        if (Test-Path $tmpFile) {
            Remove-Item $tmpFile -Force
        }
    }
}

function Invoke-QuerySql {
    param(
        [Parameter(Mandatory = $true)][string]$Sql,
        [Parameter(Mandatory = $true)]$Profile
    )
    Invoke-MySql -SqlText $Sql -Profile $Profile
}
