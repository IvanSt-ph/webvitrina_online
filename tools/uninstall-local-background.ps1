param()

$ErrorActionPreference = 'Stop'
$runner = Join-Path $PSScriptRoot 'run-local-background.ps1'

foreach ($mode in @('Worker', 'Scheduler')) {
    $taskName = "WebVitrina-Local-$mode"
    $task = Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue

    if (-not $task) {
        Write-Output "$taskName is not installed."
        continue
    }

    $arguments = ($task.Actions | ForEach-Object Arguments) -join ' '
    if ($arguments -notlike "*$runner*") {
        throw "Task $taskName points to another command; refusing to remove it."
    }

    Stop-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue
    Unregister-ScheduledTask -TaskName $taskName -Confirm:$false
    Write-Output "$taskName removed."
}
