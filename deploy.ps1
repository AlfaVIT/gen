#requires -Version 5.1
<#
.SYNOPSIS
    Деплой сайта на хостинг по FTP: заливает только изменённые файлы.

.DESCRIPTION
    Скрипт сравнивает текущее состояние git с последним успешным деплоем
    (файл .deploy_state хранит SHA коммита) и заливает на FTP только
    изменения. Список файлов берётся из git, поэтому на хостинг никогда
    не попадут неотслеживаемые данные (ftp.txt, storage/app.sqlite и т.п.).

.PARAMETER All
    Залить все отслеживаемые файлы (полный деплой). Полезно при первом
    запуске или если состояние на хостинге испорчено.

.PARAMETER From
    Коммит/тег, начиная с которого считать изменения (вместо .deploy_state).

.PARAMETER DryRun
    Только показать, что будет залито, ничего не меняя.

.PARAMETER Delete
    Удалять на хостинге файлы, удалённые в git.

.PARAMETER Verify
    Не заливать, а сверить размеры всех отслеживаемых файлов с FTP.

.EXAMPLE
    .\deploy.ps1 -DryRun
    .\deploy.ps1
    .\deploy.ps1 -All
    .\deploy.ps1 -Verify
#>
[CmdletBinding()]
param(
    [switch]$All,
    [string]$From,
    [switch]$DryRun,
    [switch]$Delete,
    [switch]$Verify
)

$ErrorActionPreference = 'Stop'

# ---------------------------------------------------------------- настройки
$FtpHost = 'opensite.myjino.ru'
$FtpUser = 'opensite_gen'
$FtpPort = 21

$RepoRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $RepoRoot

$PwdFile   = Join-Path $RepoRoot 'ftp.txt'
$StateFile = Join-Path $RepoRoot '.deploy_state'
$IgnoreFile = Join-Path $RepoRoot '.deployignore'

function Write-Step([string]$msg) { Write-Host $msg -ForegroundColor Cyan }
function Write-Ok  ([string]$msg) { Write-Host "  OK   $msg" -ForegroundColor Green }
function Write-Warn2([string]$msg) { Write-Host "  WARN $msg" -ForegroundColor Yellow }

# ------------------------------------------------------------ FTP-хелперы
if (-not (Test-Path $PwdFile)) { throw "Не найден $PwdFile с паролем FTP" }
$FtpPassword = (Get-Content $PwdFile -Raw).Trim()
$Cred = New-Object System.Net.NetworkCredential($FtpUser, $FtpPassword)

function New-FtpRequest([string]$remotePath, [string]$method) {
    $uri = "ftp://${FtpHost}:$FtpPort$remotePath"
    $r = [System.Net.FtpWebRequest]::Create($uri)
    $r.Credentials = $Cred
    $r.Method = $method
    $r.UsePassive = $true
    $r.UseBinary = $true
    $r.Timeout = 60000
    $r.ReadWriteTimeout = 60000
    $r.KeepAlive = $false
    return $r
}

function Ensure-RemoteDir([string]$remoteDir) {
    # remoteDir: '/app/inc' — создаём по одному уровню
    $parts = $remoteDir.Trim('/').Split('/') | Where-Object { $_ }
    $path = ''
    foreach ($p in $parts) {
        $path += "/$p"
        try {
            $r = New-FtpRequest $path ([System.Net.WebRequestMethods+Ftp]::MakeDirectory)
            $resp = $r.GetResponse(); $resp.Close()
        } catch [System.Net.WebException] {
            # 550 — каталог уже существует, это нормально
            if ($_.Exception.Response -and $_.Exception.Response.StatusCode -ne [System.Net.FtpStatusCode]::ActionNotTakenFileUnavailable) {
                throw
            }
        }
    }
}

function Send-File([string]$localPath, [string]$remotePath) {
    $bytes = [System.IO.File]::ReadAllBytes($localPath)
    $attempt = 0
    while ($true) {
        $attempt++
        try {
            $r = New-FtpRequest $remotePath ([System.Net.WebRequestMethods+Ftp]::UploadFile)
            $r.ContentLength = $bytes.Length
            $s = $r.GetRequestStream()
            $s.Write($bytes, 0, $bytes.Length)
            $s.Close()
            $resp = $r.GetResponse()
            $status = $resp.StatusDescription.Trim()
            $resp.Close()
            return $status
        } catch {
            if ($attempt -ge 3) { throw "Не удалось залить $remotePath : $($_.Exception.Message)" }
            Start-Sleep -Seconds 2
        }
    }
}

function Get-RemoteSize([string]$remotePath) {
    $r = New-FtpRequest $remotePath ([System.Net.WebRequestMethods+Ftp]::GetFileSize)
    $resp = $r.GetResponse()
    $size = $resp.ContentLength
    $resp.Close()
    return $size
}

function Remove-RemoteFile([string]$remotePath) {
    $r = New-FtpRequest $remotePath ([System.Net.WebRequestMethods+Ftp]::DeleteFile)
    $resp = $r.GetResponse(); $resp.Close()
}

# ------------------------------------------------------- список исключений
$ignorePatterns = @()
if (Test-Path $IgnoreFile) {
    $ignorePatterns = Get-Content $IgnoreFile -Encoding UTF8 |
        ForEach-Object { $_.Trim() } |
        Where-Object { $_ -and -not $_.StartsWith('#') }
}

function Test-Ignored([string]$relPath) {
    foreach ($pat in $ignorePatterns) {
        if ($relPath -like $pat) { return $true }
        if ($relPath -like "$pat/*") { return $true }
    }
    return $false
}

# ------------------------------------------------------------ git-хелперы
function Invoke-Git([string[]]$gitArgs) {
    $out = & git @gitArgs 2>$null
    if ($LASTEXITCODE -ne 0 -and $LASTEXITCODE -ne 1) {
        throw "git $($gitArgs -join ' ') завершился с кодом $LASTEXITCODE"
    }
    return $out
}

$headSha = (Invoke-Git @('rev-parse','HEAD') | Select-Object -First 1)
if (-not $headSha) { throw 'В репозитории нет коммитов — деплоить нечего' }

# ------------------------------------------------------------ режим Verify
if ($Verify) {
    Write-Step 'Сверка всех отслеживаемых файлов с FTP'
    $tracked = Invoke-Git @('ls-files')
    $bad = 0; $missing = 0; $checked = 0
    foreach ($rel in $tracked) {
        if (Test-Ignored $rel) { continue }
        $checked++
        $local = Join-Path $RepoRoot ($rel -replace '/','\')
        if (-not (Test-Path $local)) { Write-Warn2 "нет локально: $rel"; $missing++; continue }
        $lsize = (Get-Item $local).Length
        try {
            $rsize = Get-RemoteSize "/$rel"
        } catch {
            Write-Warn2 "нет на FTP: $rel"
            $missing++
            continue
        }
        if ($lsize -ne $rsize) {
            Write-Warn2 "размер: $rel ftp=$rsize local=$lsize"
            $bad++
        }
    }
    Write-Step ("Проверено: $checked, расхождений по размеру: $bad, отсутствует: $missing")
    if ($bad -eq 0 -and $missing -eq 0) { Write-Host 'Состояние совпадает.' -ForegroundColor Green }
    else { Write-Host 'Есть расхождения — запустите .\deploy.ps1 -All' -ForegroundColor Yellow }
    exit
}

# ------------------------------------------------------ что нужно залить
$toUpload = @()
$toDelete = @()

if ($All) {
    Write-Step 'Полный деплой: все отслеживаемые файлы'
    $toUpload = Invoke-Git @('ls-files')
}
elseif ($From) {
    Write-Step "Деплой изменений с $From до HEAD"
    $lines = Invoke-Git @('diff','--name-status','--no-renames',$From,$headSha)
    foreach ($l in $lines) {
        $f = $l -split "`t"
        if ($f.Count -lt 2) { continue }
        switch ($f[0]) {
            'A' { $toUpload += $f[1] }
            'M' { $toUpload += $f[1] }
            'T' { $toUpload += $f[1] }
            'D' { $toDelete += $f[1] }
        }
    }
}
elseif (Test-Path $StateFile) {
    $last = (Get-Content $StateFile -Raw).Trim()
    Write-Step "Деплой изменений с $($last.Substring(0,7)) до $($headSha.Substring(0,7))"
    $lines = Invoke-Git @('diff','--name-status','--no-renames',$last,$headSha)
    foreach ($l in $lines) {
        $f = $l -split "`t"
        if ($f.Count -lt 2) { continue }
        switch ($f[0]) {
            'A' { $toUpload += $f[1] }
            'M' { $toUpload += $f[1] }
            'T' { $toUpload += $f[1] }
            'D' { $toDelete += $f[1] }
        }
    }
}
else {
    Write-Warn2 '.deploy_state не найден — выполняю полный деплой'
    $toUpload = Invoke-Git @('ls-files')
}

$toUpload = @($toUpload | Where-Object { $_ } | Where-Object { -not (Test-Ignored $_) } | Sort-Object -Unique)
$toDelete = @($toDelete | Where-Object { $_ } | Where-Object { -not (Test-Ignored $_) } | Sort-Object -Unique)

if (-not $toUpload -and -not $toDelete) {
    Write-Host 'Изменений для деплоя нет.' -ForegroundColor Green
}

# ----------------------------------------------------------- предупреждения
$dirty = Invoke-Git @('status','--porcelain','--untracked-files=no')
if ($dirty) {
    Write-Warn2 'В рабочем дереве есть незакоммиченные изменения — заливается состояние с диска, не из коммита'
}

# ------------------------------------------------------------------ деплой
$uploaded = 0; $failed = 0; $deleted = 0

foreach ($rel in $toUpload) {
    $local = Join-Path $RepoRoot ($rel -replace '/','\')
    if (-not (Test-Path $local)) { Write-Warn2 "пропуск (нет файла): $rel"; continue }
    $remote = "/$rel"
    if ($DryRun) { Write-Host "  would upload $rel" ; $uploaded++; continue }
    try {
        $remoteDir = ($remote -replace '/[^/]+$','')
        if ($remoteDir -and $remoteDir -ne '') { Ensure-RemoteDir $remoteDir }
        $status = Send-File $local $remote
        Write-Ok "$rel ($status)"
        $uploaded++
    } catch {
        Write-Host "  FAIL $rel : $($_.Exception.Message)" -ForegroundColor Red
        $failed++
    }
}

if ($Delete) {
    foreach ($rel in $toDelete) {
        if ($DryRun) { Write-Host "  would delete $rel"; $deleted++; continue }
        try {
            Remove-RemoteFile "/$rel"
            Write-Ok "удалён $rel"
            $deleted++
        } catch {
            Write-Warn2 "не удалось удалить $rel : $($_.Exception.Message)"
        }
    }
}
elseif ($toDelete.Count -gt 0) {
    Write-Warn2 "в git удалены файлы ($($toDelete.Count)) — на FTP оставлены, используйте -Delete:"
    $toDelete | ForEach-Object { Write-Host "    $_" }
}

# ------------------------------------------------------------------- итог
Write-Step ("Итого: залито $uploaded, удалено $deleted, ошибок $failed")
if ($failed -gt 0) {
    Write-Host 'Есть ошибки — состояние деплоя не сохранено, повторите запуск.' -ForegroundColor Red
    exit 1
}

if (-not $DryRun) {
    Set-Content -Path $StateFile -Value $headSha -Encoding ASCII -NoNewline
    Write-Host "Состояние деплоя сохранено: $($headSha.Substring(0,7))" -ForegroundColor Green
}
