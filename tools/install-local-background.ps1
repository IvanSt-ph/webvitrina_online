param([string]$PhpPath)

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$runner = Join-Path $PSScriptRoot 'run-local-background.ps1'
$powershellPath = Join-Path $PSHOME 'pwsh.exe'
if (-not (Test-Path -LiteralPath $powershellPath)) { $powershellPath = Join-Path $PSHOME 'powershell.exe' }
if ([string]::IsNullOrWhiteSpace($PhpPath)) {
    $PhpPath = (Get-Command php -ErrorAction Stop).Source
}
if (Test-Path -LiteralPath $PhpPath -PathType Leaf) {
    $PhpPath = (Resolve-Path -LiteralPath $PhpPath).Path
}
if (-not (Test-Path -LiteralPath $PhpPath -PathType Leaf)) { throw "PHP not found: $PhpPath" }
$userId = [System.Security.Principal.WindowsIdentity]::GetCurrent().Name
$principal = New-ScheduledTaskPrincipal -UserId $userId -LogonType Interactive -RunLevel Limited

foreach ($mode in @('Worker', 'Scheduler')) {
    $taskName = "WebVitrina-Local-$mode"
    $arguments = "-NoProfile -NonInteractive -WindowStyle Hidden -ExecutionPolicy Bypass -File `"$runner`" -Mode $mode -PhpPath `"$PhpPath`""
    $action = New-ScheduledTaskAction -Execute $powershellPath -Argument $arguments -WorkingDirectory $projectRoot
    $triggers = @(New-ScheduledTaskTrigger -AtLogOn -User $userId)
    if ($mode -eq 'Scheduler') {
        $triggers += New-ScheduledTaskTrigger -Once -At (Get-Date).AddMinutes(1) -RepetitionInterval (New-TimeSpan -Minutes 1)
    }
    $settings = New-ScheduledTaskSettingsSet -MultipleInstances IgnoreNew -StartWhenAvailable -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -ExecutionTimeLimit ([TimeSpan]::Zero) -RestartCount 3 -RestartInterval (New-TimeSpan -Minutes 1)
    $existing = Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue
    if ($existing -and ($existing.Actions.Arguments -notlike "*$runner*")) {
        throw "Task $taskName already exists with another command; refusing to overwrite it."
    }
    if ($existing -and $existing.State -eq 'Running') {
        Stop-ScheduledTask -TaskName $taskName
    }
    Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $triggers -Settings $settings -Principal $principal -Description "WebVitrina local $mode; active while $userId is logged in." -Force | Out-Null
    Start-ScheduledTask -TaskName $taskName
    Get-ScheduledTask -TaskName $taskName | Select-Object TaskName,State
}
