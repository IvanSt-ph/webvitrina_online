param(
    [Parameter(Mandatory)][ValidateSet('Worker', 'Scheduler')][string]$Mode,
    [string]$PhpPath
)

$ErrorActionPreference = 'Stop'
$utf8 = [System.Text.UTF8Encoding]::new($false)
[Console]::OutputEncoding = $utf8
$OutputEncoding = $utf8
$projectRoot = Split-Path -Parent $PSScriptRoot
Set-Location -LiteralPath $projectRoot
if ([string]::IsNullOrWhiteSpace($PhpPath)) {
    $PhpPath = (Get-Command php -ErrorAction Stop).Source
}
if (Test-Path -LiteralPath $PhpPath -PathType Leaf) {
    $PhpPath = (Resolve-Path -LiteralPath $PhpPath).Path
}
if (-not (Test-Path -LiteralPath $PhpPath -PathType Leaf)) { throw "PHP not found: $PhpPath" }
$taskMutex = [System.Threading.Mutex]::new($false, "Local\WebVitrina-$Mode")
$ownsMutex = $false
try {
    try { $ownsMutex = $taskMutex.WaitOne(0) }
    catch [System.Threading.AbandonedMutexException] { $ownsMutex = $true }
    if (-not $ownsMutex) { exit 0 }

    $logFile = Join-Path $projectRoot "storage\logs\local-$($Mode.ToLower())-$(Get-Date -Format yyyy-MM-dd).log"
    if ($Mode -eq 'Worker') {
        while ($true) {
            & $PhpPath artisan queue:work database --sleep=3 --tries=3 --timeout=60 --max-time=3600 >> $logFile 2>&1
            "$(Get-Date -Format o) Worker exited: $LASTEXITCODE; restarting in 5 seconds." | Add-Content -LiteralPath $logFile
            Start-Sleep -Seconds 5
        }
    }

    & $PhpPath artisan schedule:run >> $logFile 2>&1
    $scheduleExit = $LASTEXITCODE

    # Recover a missed overnight backup when the development PC was asleep/off.
    $marker = Join-Path $projectRoot 'storage\app\local-backup-check.timestamp'
    $lastCheck = if (Test-Path -LiteralPath $marker) { (Get-Item -LiteralPath $marker).LastWriteTimeUtc } else { [DateTime]::MinValue }
    if ([DateTime]::UtcNow.Subtract($lastCheck).TotalHours -ge 1) {
        & $PhpPath artisan backup:health-check >> $logFile 2>&1
        if ($LASTEXITCODE -ne 0) {
            & $PhpPath artisan backup:run >> $logFile 2>&1
            if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
            & $PhpPath artisan backup:health-check >> $logFile 2>&1
            if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
        }
        [DateTime]::UtcNow.ToString('o') | Set-Content -LiteralPath $marker
    }
    exit $scheduleExit
}
finally {
    if ($ownsMutex) { $taskMutex.ReleaseMutex() }
    $taskMutex.Dispose()
}
