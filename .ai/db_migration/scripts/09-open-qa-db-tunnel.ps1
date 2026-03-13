param(
    [string]$SshUser = '',
    [string]$SshHost = '',
    [int]$SshPort = 22,
    [int]$LocalPort = 13306,
    [string]$RemoteDbHost = '127.0.0.1',
    [int]$RemoteDbPort = 3306,
    [string]$PlinkExe = "C:\Program Files\PuTTY\plink.exe",
    [string]$HostKey = '',
    [int]$TimeoutSeconds = 15,
    [string]$PidFile = ''
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

if (-not $SshUser) { throw "SshUser is required." }
if (-not $SshHost) { throw "SshHost is required." }
if (-not (Test-Path $PlinkExe)) { throw "Plink executable not found: $PlinkExe" }

$resolvedPidFile = if ($PidFile) { $PidFile } else { Join-Path $PSScriptRoot '.qa-db-tunnel.pid' }

$forwardSpec = "{0}:{1}:{2}" -f $LocalPort, $RemoteDbHost, $RemoteDbPort

$plinkArgs = @(
    '-ssh',
    '-agent',
    '-batch',
    '-P', $SshPort,
    '-N',
    '-L', $forwardSpec
)

if ($HostKey) {
    $plinkArgs += @('-hostkey', $HostKey)
}

$plinkArgs += @("{0}@{1}" -f $SshUser, $SshHost)

Write-Host ("Opening SSH tunnel: localhost:{0} -> {1}:{2} via {3}@{4}:{5}" -f $LocalPort, $RemoteDbHost, $RemoteDbPort, $SshUser, $SshHost, $SshPort)
$proc = Start-Process -FilePath $PlinkExe -ArgumentList $plinkArgs -PassThru -WindowStyle Hidden

$deadline = (Get-Date).AddSeconds($TimeoutSeconds)
$isReady = $false
while ((Get-Date) -lt $deadline) {
    if ($proc.HasExited) {
        throw "Plink exited early with code $($proc.ExitCode). Check Pageant/key/host settings."
    }

    try {
        $client = New-Object System.Net.Sockets.TcpClient
        $async = $client.BeginConnect('127.0.0.1', $LocalPort, $null, $null)
        $connected = $async.AsyncWaitHandle.WaitOne(300)
        if ($connected -and $client.Connected) {
            $isReady = $true
            $client.EndConnect($async) | Out-Null
            $client.Close()
            break
        }
        $client.Close()
    }
    catch {
    }

    Start-Sleep -Milliseconds 300
}

if (-not $isReady) {
    try { Stop-Process -Id $proc.Id -Force -ErrorAction SilentlyContinue } catch {}
    throw "Tunnel did not become ready on localhost:$LocalPort within $TimeoutSeconds seconds."
}

Set-Content -Path $resolvedPidFile -Value $proc.Id -Encoding ASCII
Write-Host "Tunnel opened. PID=$($proc.Id). PID file: $resolvedPidFile"
Write-Output $proc.Id
