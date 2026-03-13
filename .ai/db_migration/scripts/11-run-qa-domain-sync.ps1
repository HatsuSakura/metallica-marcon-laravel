param(
    [string]$LocalProfile = 'docker_dev_marconinertia',
    [string]$QaProfile = 'qa_siteground_tunnel',
    [string]$ExportFile = '',
    [switch]$SkipTunnel,
    [switch]$SkipBackup,
    [switch]$SkipLocalValidation,
    [switch]$SkipQaValidation,
    [switch]$SkipTruncateBeforeImport,
    [string]$SshUser = '',
    [string]$SshHost = '',
    [int]$SshPort = 22,
    [int]$TunnelLocalPort = 13306,
    [string]$TunnelRemoteDbHost = '127.0.0.1',
    [int]$TunnelRemoteDbPort = 3306,
    [string]$PlinkExe = "C:\Program Files\PuTTY\plink.exe",
    [string]$HostKey = '',
    [string]$TunnelPidFile = ''
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$scriptRoot = $PSScriptRoot
$openedTunnel = $false
$resolvedTunnelPidFile = if ($TunnelPidFile) { $TunnelPidFile } else { Join-Path $scriptRoot '.qa-db-tunnel.pid' }

try {
    if (-not $SkipTunnel) {
        if (-not $SshUser) { throw "SshUser is required unless -SkipTunnel is used." }
        if (-not $SshHost) { throw "SshHost is required unless -SkipTunnel is used." }

        & (Join-Path $scriptRoot '09-open-qa-db-tunnel.ps1') `
            -SshUser $SshUser `
            -SshHost $SshHost `
            -SshPort $SshPort `
            -LocalPort $TunnelLocalPort `
            -RemoteDbHost $TunnelRemoteDbHost `
            -RemoteDbPort $TunnelRemoteDbPort `
            -PlinkExe $PlinkExe `
            -HostKey $HostKey `
            -PidFile $resolvedTunnelPidFile | Out-Null
        $openedTunnel = $true
    }

    if (-not $SkipLocalValidation) {
        & (Join-Path $scriptRoot '06-validate-customers-sites.ps1') -Profile $LocalProfile
    }

    $resolvedExportFile = $ExportFile
    if (-not $resolvedExportFile) {
        $resolvedExportFile = & (Join-Path $scriptRoot '07-export-customers-sites.ps1') -Profile $LocalProfile
        if (-not $resolvedExportFile) {
            throw "Export script did not return output file path."
        }
    }

    if (-not $SkipBackup) {
        & (Join-Path $scriptRoot '00-backup-dev-db.ps1') -Profile $QaProfile
    }

    $importArgs = @(
        '-Profile', $QaProfile,
        '-InputFile', $resolvedExportFile
    )
    if (-not $SkipTruncateBeforeImport) {
        $importArgs += '-TruncateBeforeImport'
    }
    & (Join-Path $scriptRoot '08-import-customers-sites.ps1') @importArgs

    if (-not $SkipQaValidation) {
        & (Join-Path $scriptRoot '06-validate-customers-sites.ps1') -Profile $QaProfile
    }

    Write-Host "QA domain sync completed successfully."
}
finally {
    if ($openedTunnel) {
        & (Join-Path $scriptRoot '10-close-qa-db-tunnel.ps1') -PidFile $resolvedTunnelPidFile
    }
}
