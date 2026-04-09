# =============================================================
# deploy.ps1 — Metallica Marcon | Deploy QA → Siteground
#
# Strategia: legge git diff rispetto all'ultimo commit deployato
# e trasferisce SOLO i file modificati/aggiunti/eliminati.
# Il commit base viene salvato in .last-qa-deploy (gitignored).
#
# Primo deploy (nessun .last-qa-deploy): carica tutti i file
# tracciati da git (git ls-files).
#
# Uso: .\deploy.ps1
# =============================================================

# ============================
# CONFIGURAZIONE
# ============================

$LocalDir       = "C:\Users\Matteo\Documents\Progetti\Metallica Marcon\metallica-marcon-laravel"
$RemoteDir      = "/home/u1872-gi4pyk5u8xvn/www/testgestionalelogistica.metallicamarcon.it/public_html"
$RemoteUser     = "u1872-gi4pyk5u8xvn"
$RemoteHost     = "gnldm1096.siteground.biz"
$RemotePort     = 18765
$WinScpExe      = "C:\Program Files (x86)\WinSCP\winscp.com"
$WinScpLog      = "C:\Users\Matteo\Documents\Progetti\Metallica Marcon\winscp-deploy.log"
$PlinkExe       = "C:\Program Files\PuTTY\plink.exe"
$SshKeyPpk      = "C:\Users\Matteo\Documents\Progetti\Metallica Marcon\PUTTY\putty.ppk"
$LastCommitFile = Join-Path $LocalDir ".last-qa-deploy"

# ============================
# OPZIONI
# ============================

# $true = esegue npm run build prima del deploy e sincronizza public/build/
# (public/build/ è gitignored, viene gestito separatamente con synchronize)
$RunNpmBuild = $false

# $true = sincronizza vendor/ via WinSCP synchronize
# (vendor/ è gitignored — solo se hai aggiunto/rimosso dipendenze)
$SyncVendor = $false

# $true = simula il deploy, non tocca niente sul server
$WinScpPreview = $false

# ============================
# PATTERN DI ESCLUSIONE
# File mai da caricare in QA, anche se presenti nel git diff.
# (Regex su path con forward slash, es. "app/Http/..." )
# ============================

$ExcludePatterns = @(
    "^\.env",               # variabili d'ambiente (già sul server)
    "^\.git/",              # cartella git
    "^node_modules/",       # dipendenze npm
    "^vendor/",             # dipendenze composer (gestita da $SyncVendor)
    "^storage/logs/",       # log applicazione
    "^storage/debugbar/",   # debug bar dump
    "^storage/framework/",  # cache, sessioni, view compilate
    "^bootstrap/cache/",    # cache Laravel
    "^public/storage",      # symlink storage
    "^public/build/",       # asset compilati (gestita da $RunNpmBuild)
    "^public/hot$",         # vite hot-reload
    "^tests/",              # test suite
    "^\.claude/",           # config agentic dev team
    "^CLAUDE\.md$",         # istruzioni Claude
    "^Dockerfile$",         # solo per PROD (VPS Docker)
    "^docker-compose",      # solo per PROD
    "^deploy\.sh$",         # deploy PROD
    "^deploy\.ps1$",        # questo script stesso
    "^\.last-qa-deploy$",   # tracking locale
    "^appunti",
    "^TO_DO_LIST",
    "\.ps1$",
    "\.ppk$",
    "\.zip$",
    "\.bat$"
)

# ============================
# HELPERS
# ============================

function Should-Exclude {
    param([string]$Path)
    foreach ($pattern in $ExcludePatterns) {
        if ($Path -match $pattern) { return $true }
    }
    return $false
}

function Write-Step {
    param([string]$Msg, [string]$Color = "Cyan")
    Write-Host "==> $Msg" -ForegroundColor $Color
}

# ============================
# INIZIO
# ============================

Push-Location $LocalDir

# ============================
# 1. VERIFICA WORKING TREE
# ============================

$dirtyFiles = git status --porcelain
if ($dirtyFiles) {
    Write-Host ""
    Write-Host "!! WARNING: ci sono modifiche non committate:" -ForegroundColor Yellow
    git status --short
    Write-Host ""
    $confirm = Read-Host "Procedere comunque? Solo i file committati verranno deployati [y/N]"
    if ($confirm -ne 'y' -and $confirm -ne 'Y') {
        Write-Host "Deploy annullato." -ForegroundColor Red
        Pop-Location
        exit 1
    }
}

# ============================
# 2. DETERMINA RANGE GIT
# ============================

$HeadCommit = (git rev-parse HEAD).Trim()

if (Test-Path $LastCommitFile) {
    $FromCommit = (Get-Content $LastCommitFile -Raw).Trim()

    # Verifica che il commit base esista nel repo locale
    git cat-file -e "$FromCommit^{commit}" 2>$null
    if ($LASTEXITCODE -ne 0) {
        Write-Host "!! Commit base $FromCommit non trovato. Forzo full deploy." -ForegroundColor Yellow
        $FromCommit = $null
    } else {
        $shortFrom = $FromCommit.Substring(0, 8)
        $shortHead = $HeadCommit.Substring(0, 8)
        Write-Step "Deploy incrementale: $shortFrom → $shortHead"
    }
} else {
    Write-Step "Primo deploy: carico tutti i file tracciati da git." "Yellow"
    $FromCommit = $null
}

# ============================
# 3. CLEANUP LOCALE
# ============================

$dirsToClean = @(
    "storage\debugbar",
    "storage\framework\cache\data",
    "storage\framework\sessions",
    "storage\framework\views",
    "bootstrap\cache"
)

foreach ($dir in $dirsToClean) {
    $fullPath = Join-Path $LocalDir $dir
    if (Test-Path $fullPath) {
        Get-ChildItem $fullPath -Force |
            Where-Object { $_.Name -ne ".gitignore" } |
            Remove-Item -Recurse -Force -ErrorAction SilentlyContinue
    }
}

Write-Step "Cache locale pulita." "Green"

# ============================
# 4. BUILD FRONTEND (opzionale)
# ============================

if ($RunNpmBuild) {
    Write-Step "Eseguo 'npm run build'..."
    npm run build
    if ($LASTEXITCODE -ne 0) {
        Write-Host "!! ERRORE: npm run build fallito." -ForegroundColor Red
        Pop-Location
        exit 1
    }
    Write-Step "Build frontend completata." "Green"
}

# ============================
# 5. LISTA FILE DA GIT
# ============================

$changedFiles = @()
$deletedFiles = @()

if ($null -eq $FromCommit) {
    # Full deploy: tutti i file tracciati da git
    $changedFiles = (git ls-files) |
        ForEach-Object { $_.Trim() } |
        Where-Object { $_ -ne "" -and -not (Should-Exclude $_) }
} else {
    # Incrementale: file modificati/aggiunti
    $rawChanged = git diff --name-only --diff-filter=ACM $FromCommit HEAD
    $changedFiles = $rawChanged |
        ForEach-Object { $_.Trim() } |
        Where-Object { $_ -ne "" -and -not (Should-Exclude $_) }

    # File eliminati
    $rawDeleted = git diff --name-only --diff-filter=D $FromCommit HEAD
    $deletedFiles = $rawDeleted |
        ForEach-Object { $_.Trim() } |
        Where-Object { $_ -ne "" -and -not (Should-Exclude $_) }

    # File rinominati: carica nuovo nome, elimina vecchio
    $rawRenamed = git diff --name-status --diff-filter=R $FromCommit HEAD
    foreach ($line in $rawRenamed) {
        $parts = $line -split "`t"
        if ($parts.Length -ge 3) {
            $oldName = $parts[1].Trim()
            $newName = $parts[2].Trim()
            if (-not (Should-Exclude $newName)) { $changedFiles += $newName }
            if (-not (Should-Exclude $oldName)) { $deletedFiles += $oldName }
        }
    }
}

Write-Step "File da caricare: $($changedFiles.Count) | Da eliminare: $($deletedFiles.Count)"

if ($changedFiles.Count -gt 0) {
    Write-Host ""
    Write-Host "  File da caricare:" -ForegroundColor DarkGray
    $changedFiles | ForEach-Object { Write-Host "    + $_" -ForegroundColor DarkGray }
}
if ($deletedFiles.Count -gt 0) {
    Write-Host ""
    Write-Host "  File da eliminare:" -ForegroundColor DarkGray
    $deletedFiles | ForEach-Object { Write-Host "    - $_" -ForegroundColor DarkGray }
}

if ($changedFiles.Count -eq 0 -and $deletedFiles.Count -eq 0 -and -not $SyncVendor -and -not $RunNpmBuild) {
    Write-Step "Nessuna modifica da deployare." "Green"
    Pop-Location
    exit 0
}

Write-Host ""

# ============================
# 6. COSTRUZIONE SCRIPT WINSCP
# ============================

$previewFlag = if ($WinScpPreview) { " -preview" } else { "" }

$winScpLines = @(
    "option batch abort",
    "option confirm off",
    "option transfer binary",
    "",
    "open sftp://${RemoteUser}@${RemoteHost}:${RemotePort}/ -hostkey=`"ssh-ed25519 255 BsnglgFnFsOxBGTxAtn6qeQmu5DZzGfHV5/fRTSEt9A`"",
    ""
)

# Upload file modificati/aggiunti
if ($changedFiles.Count -gt 0) {
    $winScpLines += "# === File modificati / aggiunti ==="
    foreach ($file in $changedFiles) {
        $localPath = Join-Path $LocalDir ($file.Replace('/', '\'))
        $remotePath = "$RemoteDir/$file"
        if (Test-Path $localPath) {
            $winScpLines += "put `"$localPath`" `"$remotePath`""
        } else {
            Write-Host "!! File non trovato localmente, salto: $file" -ForegroundColor Yellow
        }
    }
    $winScpLines += ""
}

# Elimina file rimossi
# batch continue: se il file non esiste sul server, non blocca
if ($deletedFiles.Count -gt 0) {
    $winScpLines += "# === File eliminati ==="
    $winScpLines += "option batch continue"
    foreach ($file in $deletedFiles) {
        $remotePath = "$RemoteDir/$file"
        $winScpLines += "rm `"$remotePath`""
    }
    $winScpLines += "option batch abort"
    $winScpLines += ""
}

# Sync vendor/ (gitignored, gestito via synchronize)
if ($SyncVendor) {
    $vendorLocal = Join-Path $LocalDir "vendor"
    $winScpLines += "# === Sync vendor/ (gitignored) ==="
    $winScpLines += "synchronize remote `"$vendorLocal`" `"$RemoteDir/vendor`" -delete$previewFlag"
    $winScpLines += ""
    Write-Step "vendor/ incluso nella sincronizzazione." "Yellow"
}

# Sync public/build/ (gitignored, gestito via synchronize)
if ($RunNpmBuild) {
    $buildLocal = Join-Path $LocalDir "public\build"
    $winScpLines += "# === Sync public/build/ (gitignored) ==="
    $winScpLines += "synchronize remote `"$buildLocal`" `"$RemoteDir/public/build`" -delete$previewFlag"
    $winScpLines += ""
}

$winScpLines += "exit"

$TempScriptPath = [System.IO.Path]::GetTempFileName()
Set-Content -Path $TempScriptPath -Value ($winScpLines -join "`n") -Encoding ASCII

# ============================
# 7. ESEGUI WINSCP
# ============================

if ($WinScpPreview) {
    Write-Step "Modalità PREVIEW — niente verrà modificato sul server." "Yellow"
}

Write-Step "Eseguo WinSCP..."
& "$WinScpExe" /log="$WinScpLog" /loglevel=1 /script="$TempScriptPath"
$winScpExitCode = $LASTEXITCODE

Remove-Item $TempScriptPath -ErrorAction SilentlyContinue

if ($winScpExitCode -ne 0) {
    Write-Host "!! ERRORE WinSCP: codice $winScpExitCode — vedi $WinScpLog" -ForegroundColor Red
    Pop-Location
    exit 1
}

Write-Step "File sincronizzati." "Green"

# Se preview, fermati qui
if ($WinScpPreview) {
    Write-Step "Preview completato — nessun comando Laravel eseguito." "Yellow"
    Pop-Location
    exit 0
}

# ============================
# 8. COMANDI LARAVEL SUL SERVER
# ============================

Write-Step "Eseguo comandi Laravel sul server..."

$remoteCommands = @(
    "cd $RemoteDir",
    "composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction",
    "php artisan optimize:clear",
    "php artisan migrate --force",
    "php artisan config:cache",
    "php artisan route:cache",
    "php artisan view:cache"
)

$remoteCommandString = $remoteCommands -join " && "

$plinkArgs = @("-ssh", "-P", $RemotePort)
if ($SshKeyPpk -ne "") { $plinkArgs += @("-i", $SshKeyPpk) }
$plinkArgs += @("$RemoteUser@$RemoteHost", $remoteCommandString)

& "$PlinkExe" @plinkArgs
$plinkExitCode = $LASTEXITCODE

if ($plinkExitCode -ne 0) {
    Write-Host "!! ERRORE: comandi Laravel falliti con codice $plinkExitCode" -ForegroundColor Red
    Pop-Location
    exit 1
}

# ============================
# 9. SALVA COMMIT DEPLOYATO
# ============================

Set-Content -Path $LastCommitFile -Value $HeadCommit -Encoding ASCII
Write-Step "Commit $($HeadCommit.Substring(0,8)) salvato in .last-qa-deploy." "Green"

Write-Host ""
Write-Step "DEPLOY QA COMPLETATO." "Green"

Pop-Location
