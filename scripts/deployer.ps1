# Deploiement en un clic du CMS CIDST depuis Windows : build local, envoi, installation ou mise a jour.
# Lance par scripts\deployer.bat. Configuration : scripts\deployer.config.json (jamais commite).
# Usage direct : powershell -File scripts\deployer.ps1 [-Tag stable-...]   [non verifie en reel]
param([string]$Tag = '')

$ErrorActionPreference = 'Stop'
Set-Location (Split-Path -Parent $PSScriptRoot)
$enc = New-Object System.Text.UTF8Encoding($false)

# --- Configuration locale
$cfgPath = Join-Path $PSScriptRoot 'deployer.config.json'
if (-not (Test-Path $cfgPath)) {
    $template = '{' + "`r`n" + '  "Hote": "",' + "`r`n" + '  "Utilisateur": "root",' + "`r`n" + '  "Port": 22,' + "`r`n" + '  "Domaine": "",' + "`r`n" + '  "EmailAdmin": "",' + "`r`n" + '  "Cle": ""' + "`r`n" + '}'
    [System.IO.File]::WriteAllText($cfgPath, $template, $enc)
    throw "Fichier cree : $cfgPath. Remplissez Hote, Domaine et EmailAdmin, puis relancez."
}
$cfg = [System.IO.File]::ReadAllText($cfgPath, $enc) | ConvertFrom-Json
foreach ($key in @('Hote', 'Utilisateur', 'Domaine', 'EmailAdmin')) {
    $value = [string]$cfg.$key
    if ($value -notmatch '^[A-Za-z0-9._@+-]+$') { throw "deployer.config.json : '$key' est vide ou contient un caractere non permis." }
}
$port = if ($cfg.Port) { [int]$cfg.Port } else { 22 }
$target = "$($cfg.Utilisateur)@$($cfg.Hote)"
$sshOpt = @('-p', "$port", '-o', 'StrictHostKeyChecking=accept-new')
$scpOpt = @('-P', "$port", '-o', 'StrictHostKeyChecking=accept-new')
if ($cfg.Cle) {
    $sshOpt += @('-i', [string]$cfg.Cle)
    $scpOpt += @('-i', [string]$cfg.Cle)
}

# --- Controles Git : on deploie un etat propre, publie et etiquete
if ((git rev-parse --abbrev-ref HEAD).Trim() -ne 'master') { throw 'Il faut etre sur master' }
git fetch origin --tags
if ($LASTEXITCODE -ne 0) { throw 'git fetch a echoue' }
if ((git rev-parse master).Trim() -ne (git rev-parse origin/master).Trim()) { throw 'master different de origin/master' }
if ((git status --porcelain).Count -ne 0) { throw 'Arbre non propre' }
if ($Tag -eq '') { $Tag = [string](@(git tag -l 'stable-*' --sort=-creatordate) | Select-Object -First 1) }
if ($Tag -eq '') { throw 'Aucun tag stable trouve' }
if (@(git ls-remote --tags origin "refs/tags/$Tag").Count -eq 0) { throw "Le tag $Tag n est pas sur GitHub" }
$ahead = [int](git rev-list --count "$Tag..HEAD")

Write-Host ''
Write-Host "Serveur  : $target (port $port)"
Write-Host "Domaine  : $($cfg.Domaine)"
Write-Host "Version  : $Tag"
if ($ahead -gt 0) { Write-Host "Attention : master a $ahead commit(s) apres ce tag ; le build sera celui de master, le code serveur celui du tag (sans importance si ce sont des commits de documentation)." }
$answer = Read-Host 'Deployer maintenant ? (o/N)'
if ($answer -notmatch '^[oOyY]') { throw 'Deploiement annule' }

# --- Build local
npm run build
if ($LASTEXITCODE -ne 0) { throw 'npm run build a echoue' }
if (-not (Test-Path 'public\build')) { throw 'public\build absent apres le build' }
$tgz = Join-Path $env:TEMP 'cidst-build.tgz'
if (Test-Path $tgz) { Remove-Item $tgz }
tar -czf $tgz -C public build
if ($LASTEXITCODE -ne 0) { throw 'Archive du build echouee' }

# --- Envoi
scp @scpOpt $tgz "${target}:/tmp/cidst-build.tgz"
if ($LASTEXITCODE -ne 0) { throw 'Envoi du build echoue (acces SSH ?)' }
scp @scpOpt (Join-Path $PSScriptRoot 'install-serveur.sh') "${target}:/tmp/install-serveur.sh"
if ($LASTEXITCODE -ne 0) { throw 'Envoi du script serveur echoue' }
Remove-Item $tgz

# --- Installation ou mise a jour sur le serveur
$sudo = if ($cfg.Utilisateur -eq 'root') { '' } else { 'sudo ' }
$remote = "${sudo}DOMAINE='$($cfg.Domaine)' EMAIL_ADMIN='$($cfg.EmailAdmin)' TAG='$Tag' bash /tmp/install-serveur.sh"
ssh @sshOpt $target $remote
if ($LASTEXITCODE -ne 0) { throw 'Le script serveur a echoue : lisez les dernieres lignes ci-dessus' }

# --- Controle (non bloquant : un avertissement ne doit pas faire croire a un echec)
Write-Host ''
Write-Host '--- app:check-environment ---'
ssh @sshOpt $target 'sudo -u www-data php /var/www/cidst/artisan app:check-environment'

Write-Host ''
Write-Host "Termine : $Tag deploye sur $($cfg.Domaine)."
Write-Host 'Premier deploiement : voir docs\installation-serveur.md (courrier, premier administrateur).'
