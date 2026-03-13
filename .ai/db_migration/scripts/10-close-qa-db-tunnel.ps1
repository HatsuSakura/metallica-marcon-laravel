param(
    [int]$TunnelPid = 0,
    [string]$PidFile = ''
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$resolvedPidFile = if ($PidFile) { $PidFile } else { Join-Path $PSScriptRoot '.qa-db-tunnel.pid' }

$resolvedPid = $TunnelPid
if ($resolvedPid -le 0) {
    if (-not (Test-Path $resolvedPidFile)) {
        Write-Host "No PID provided and PID file not found: $resolvedPidFile"
        exit 0
    }

    $rawPid = (Get-Content -Path $resolvedPidFile -Raw).Trim()
    if (-not [int]::TryParse($rawPid, [ref]$resolvedPid)) {
        throw "Invalid PID content in file: $resolvedPidFile"
    }
}

$proc = Get-Process -Id $resolvedPid -ErrorAction SilentlyContinue
if (-not $proc) {
    Write-Host "Process $resolvedPid is not running."
    if (Test-Path $resolvedPidFile) {
        Remove-Item $resolvedPidFile -ErrorAction SilentlyContinue
    }
    exit 0
}

if ($proc.ProcessName -notlike 'plink*') {
    throw "Refusing to stop process $resolvedPid because it is '$($proc.ProcessName)', not plink."
}

Stop-Process -Id $resolvedPid -Force
if (Test-Path $resolvedPidFile) {
    Remove-Item $resolvedPidFile -ErrorAction SilentlyContinue
}

Write-Host "Tunnel process stopped. PID=$resolvedPid"
