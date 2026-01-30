# ============================
# CONFIGURAZIONE
# ============================

# Directory locale del progetto Laravel (sul tuo PC)
$LocalDir = "C:\Users\Matteo\Documents\Progetti\Metallica Marcon\metallica-marcon-laravel"

# Directory remota su SiteGround (root del progetto Laravel)
$RemoteDir = "/home/u1872-gi4pyk5u8xvn/www/testgestionalelogistica.metallicamarcon.it/public_html"

# Dati SSH di SiteGround (per i comandi Laravel via plink)
$RemoteUser = "u1872-gi4pyk5u8xvn"
$RemoteHost = "gnldm1096.siteground.biz"
$RemotePort = 18765

# WinSCP console
$WinScpExe = "C:\Program Files (x86)\WinSCP\winscp.com"

# Log WinSCP
$WinScpLog = "C:\Users\Matteo\Documents\Progetti\Metallica Marcon\winscp-deploy.log"

# Percorso plink (PuTTY)
$PlinkExe = "C:\Program Files\PuTTY\plink.exe"

# Chiave privata PuTTY (.ppk) per plink
$SshKeyPpk = "C:\Users\Matteo\Documents\Progetti\Metallica Marcon\PUTTY\putty.ppk"

# Vuoi eseguire anche npm run build prima del deploy?
$RunNpmBuild = $false  # metti $true se vuoi farlo fare allo script

# Vuoi usare la modalità "preview" (dry-run) di WinSCP? # true = simula | false = fai il deploy reale
$WinScpPreview = $false  # metti $false per fare il deploy reale

# Vuoi sincronizzare anche vendor/ ?
# false = escludi vendor (più veloce se non hai toccato composer)
# true  = includi vendor (dopo composer install/update, nuove dipendenze, ecc.)
$SyncVendor = $false

# ============================
# BUILD FRONTEND (opzionale)
# ============================

if ($RunNpmBuild -eq $true) {
    Write-Host "==> Eseguo 'npm run build' in locale..." -ForegroundColor Cyan
    Push-Location $LocalDir
    npm run build
    if ($LASTEXITCODE -ne 0) {
        Write-Host "!! ERRORE: 'npm run build' fallito con codice $LASTEXITCODE" -ForegroundColor Red
        Pop-Location
        exit 1
    }
    Pop-Location
    Write-Host "==> Build frontend completata." -ForegroundColor Green
} else {
    Write-Host "==> Salto 'npm run build' (RunNpmBuild = false)." -ForegroundColor Yellow
}

# ============================
# SYNC FILE CON WINSCP
# ============================

Write-Host "==> Inizio sync con WinSCP..." -ForegroundColor Cyan

# Costruisco script temporaneo per WinSCP
# NOTE:
# - synchronize remote: locale -> remoto
# - -delete: elimina sul server i file rimossi in locale
# - filemask con esclusioni (prefisso '|' significa "escludi")
$previewFlag = ""
if ($WinScpPreview -eq $true) {
    $previewFlag = " -preview"
    Write-Host "==> Modalità PREVIEW attiva: niente verrà realmente modificato sul server." -ForegroundColor Yellow
}

# ============================
# COSTRUZIONE EXCLUDE WINSCP
# ============================

# Esclusioni per la sync root (progetto intero)
$excludeRoot = ".git/; node_modules/; .env; .htaccess; *.ps1; public/; storage/app/; storage/logs/; storage/debugbar/; storage/framework/cache/; storage/framework/sessions/; storage/framework/testing/; tests/; *.txt; *.bat; *.zip"

if (-not $SyncVendor) {
    # Se NON vogliamo toc care vendor, lo aggiungiamo agli exclude
    $excludeRoot = "vendor/; " + $excludeRoot
}

# Esclusioni per la sync di public/ (vendor non c'entra qui)
$excludePublic = "storage/; hot; *.txt; *.bat; *.zip"


if ($SyncVendor) {
    Write-Host "==> vendor/ INCLUSO nella sincronizzazione." -ForegroundColor Yellow
} else {
    Write-Host "==> vendor/ ESCLUSO dalla sincronizzazione." -ForegroundColor Yellow
}


$WinScpScript = @"
option batch abort
option confirm off

open sftp://${RemoteUser}@${RemoteHost}:${RemotePort}/ -hostkey="ssh-ed25519 255 BsnglgFnFsOxBGTxAtn6qeQmu5DZzGfHV5/fRTSEt9A"

# === 1) SYNC ROOT (senza public/) ===
option exclude "$excludeRoot"
synchronize remote "$LocalDir" "$RemoteDir" -delete$previewFlag

# === 2) SYNC SOLO public/ (escludendo storage/) ===
option exclude "$excludePublic"
synchronize remote "$LocalDir/public" "$RemoteDir/public" -delete$previewFlag

exit
"@



# Salvo lo script WinSCP in un file temporaneo
$TempScriptPath = [System.IO.Path]::GetTempFileName()
Set-Content -Path $TempScriptPath -Value $WinScpScript -Encoding ASCII

Write-Host "==> Eseguo WinSCP con script di sincronizzazione..." -ForegroundColor Cyan

& "$WinScpExe" /log="$WinScpLog" /loglevel=1 /script="$TempScriptPath"
$winScpExitCode = $LASTEXITCODE

# Cancello lo script temporaneo
Remove-Item $TempScriptPath -ErrorAction SilentlyContinue

if ($winScpExitCode -ne 0) {
    Write-Host "!! ERRORE: WinSCP ha restituito codice $winScpExitCode" -ForegroundColor Red
    exit 1
}

Write-Host "==> Sync completato (o simulato, se in preview)." -ForegroundColor Green

# Se siamo in preview, fermiamoci qui (nessuna azione Laravel remota)
if ($WinScpPreview -eq $true) {
    Write-Host "==> Modalità preview: salto i comandi Laravel sul server." -ForegroundColor Yellow
    exit 0
}

# ============================
# COMANDI LARAVEL IN REMOTO
# ============================

Write-Host "==> Eseguo comandi Laravel sul server..." -ForegroundColor Cyan

$remoteCommands = @(
    "cd $RemoteDir",
    "php artisan migrate --force",
    "php artisan config:clear",
    "php artisan config:cache",
    "php artisan route:clear",
    "php artisan route:cache",
    "php artisan view:clear",
    "php artisan view:cache"
)

$remoteCommandString = $remoteCommands -join " && "

# plink usa la .ppk
$plinkArgs = @(
    "-ssh",
    "-P", $RemotePort
)

if ($SshKeyPpk -ne "") {
    $plinkArgs += @("-i", $SshKeyPpk)
}

$plinkArgs += @(
    "$RemoteUser@$RemoteHost",
    $remoteCommandString
)

& "$PlinkExe" @plinkArgs
$plinkExitCode = $LASTEXITCODE

if ($plinkExitCode -ne 0) {
    Write-Host "!! ERRORE: comandi Laravel falliti con codice $plinkExitCode" -ForegroundColor Red
    exit 1
}

Write-Host "==> DEPLOY COMPLETATO con successo." -ForegroundColor Green
