[CmdletBinding()]
param(
    [ValidateSet('Validate', 'IsolationCheck', 'Full')]
    [string] $Mode = 'Validate',

    [string] $AckDisposableDatabase = '',

    [ValidateNotNullOrEmpty()]
    [ValidateLength(1, 200)]
    [ValidatePattern('\A[A-Za-z_][A-Za-z0-9_]*(?:::[A-Za-z_][A-Za-z0-9_]*)?\z')]
    [string] $TestFilter
)

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

if ($PSBoundParameters.ContainsKey('TestFilter') -and $Mode -ne 'Full') {
    throw '-TestFilter is only supported with -Mode Full.'
}

$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$composeFile = Join-Path $projectRoot 'docker-compose.full-test.yml'
$dockerfile = Join-Path $projectRoot 'Dockerfile.full-test'
$dockerIgnore = Join-Path $projectRoot '.dockerignore'
$composeEnvironment = Join-Path $PSScriptRoot 'full-test.compose.env'
$phpunitProfile = Join-Path $projectRoot 'phpunit.full-test.xml'

foreach ($requiredFile in @($composeFile, $dockerfile, $dockerIgnore, $composeEnvironment, $phpunitProfile)) {
    if (-not (Test-Path -LiteralPath $requiredFile -PathType Leaf)) {
        throw "Required full-test file is missing: $requiredFile"
    }
}

# Preserve native exit codes explicitly, including when the caller enables PS 7
# native-command error promotion. Cleanup must run before returning that code.
$PSNativeCommandUseErrorActionPreference = $false
$runExitCode = 0

# Best-effort, allowlisted metadata only. Never serialize exception text or output.
function Write-TerminationEvent {
    param([string] $Event, [string] $Status, $Code = $null)
    try {
        if (-not $terminationLog) { return }
        if ($Event -notin @('run_started', 'config_started', 'config_finished',
            'compose_up_started', 'compose_up_finished', 'harness_exception',
            'compose_cleanup_started', 'compose_cleanup_finished',
            'secret_cleanup_started', 'secret_cleanup_finished', 'harness_finished')) { return }
        if ($Status -notin @('started', 'success', 'failed', 'unknown', 'not_attempted')) { return }
        $record = [ordered]@{
            utc = [DateTime]::UtcNow.ToString('o')
            project = $composeProject
            mode = $Mode
            event = $Event
            status = $Status
            code = $(if ($Code -is [int]) { $Code } else { 'unknown' })
            stage = $terminationStage
        }
        [IO.File]::AppendAllText($terminationLog, ($record | ConvertTo-Json -Compress) + [Environment]::NewLine,
            [Text.UTF8Encoding]::new($false))
    } catch {
        # Logging must never replace the original failure or prevent cleanup.
        Write-Warning 'Could not write termination diagnostics.' -WarningAction Continue
    }
}

$docker = Get-Command docker -ErrorAction Stop
$composeVersion = & $docker.Source compose version 2>&1
if ($LASTEXITCODE -ne 0) {
    throw "Docker Compose is unavailable: $composeVersion"
}

Push-Location $projectRoot
try {
    $composeText = Get-Content -LiteralPath $composeFile -Raw
    $forbiddenPatterns = @(
        '(?m)^\s*ports\s*:',
        '(?m)^\s*networks\s*:',
        '/var/run/docker\.sock',
        '(?m)^\s*-\s*\.\s*:/app',
        '(?m)^\s*-\s*[A-Za-z]:\\'
    )
    foreach ($pattern in $forbiddenPatterns) {
        if ($composeText -match $pattern) {
            throw "Forbidden Docker Compose configuration matched: $pattern"
        }
    }
    if (([regex]::Matches($composeText, '(?m)^\s*network_mode\s*:\s*none\s*$')).Count -ne 2) {
        throw 'Both full-test services must use network_mode: none.'
    }
    foreach ($requiredText in @('DB_DATABASE: webv3_full_testing', 'DB_HOST: localhost', 'DB_SOCKET: /var/run/mysqld/mysqld.sock')) {
        if (-not $composeText.Contains($requiredText)) {
            throw "Required isolation setting is missing: $requiredText"
        }
    }

    Write-Host 'Static full-test isolation checks passed.' -ForegroundColor Green

    if ($Mode -eq 'Validate') {
        Write-Host 'No image was built and no container was started.'
        return
    }

    if ($AckDisposableDatabase -cne 'webv3_full_testing') {
        throw "$Mode requires -AckDisposableDatabase webv3_full_testing."
    }

    $runId = [guid]::NewGuid().ToString('N')
    $composeProject = 'webvitrina-full-test-' + $runId
    $systemTemp = [System.IO.Path]::GetFullPath([System.IO.Path]::GetTempPath())
    $secretRoot = [System.IO.Path]::GetFullPath((Join-Path $systemTemp ('webvitrina-full-test-' + $runId)))
    if (-not $secretRoot.StartsWith($systemTemp, [StringComparison]::OrdinalIgnoreCase) -or
        [System.IO.Path]::GetFileName($secretRoot) -notmatch '^webvitrina-full-test-[a-f0-9]{32}$') {
        throw 'Refusing an unsafe temporary secret path.'
    }
    $passwordFile = Join-Path $secretRoot 'mysql-password.txt'
    $rootPasswordFile = Join-Path $secretRoot 'mysql-root-password.txt'
    $secretRootCreated = $false
    $composeConfigured = $false
    $primaryFailure = $false
    $logWriter = $null
    $runLog = $null
    $firstFailureFile = $null
    $firstFailureSaved = $false
    $failureTailRemaining = 0
    $recentOutput = [System.Collections.Generic.Queue[string]]::new()
    $lastTest = ''
    $previousTestFilter = [Environment]::GetEnvironmentVariable('WV_FULL_TEST_FILTER', 'Process')
    $terminationLog = $null
    $terminationStage = 'setup'
    $composeUpCode = $null
    $composeCleanupCode = $null

    try {
        # Same directory safety checks as run.log, before secret creation/config.
        $logRoot = Join-Path $projectRoot 'test-logs'
        if (Test-Path -LiteralPath $logRoot) {
            $logDirectory = Get-Item -LiteralPath $logRoot -Force
            if (-not $logDirectory.PSIsContainer -or
                ($logDirectory.Attributes -band [IO.FileAttributes]::ReparsePoint)) {
                throw 'Refusing an unsafe test log directory.'
            }
        } else {
            New-Item -ItemType Directory -Path $logRoot | Out-Null
        }
        $reportRoot = Join-Path $logRoot ('full-test-' + $runId)
        New-Item -ItemType Directory -Path $reportRoot | Out-Null
        $terminationLog = Join-Path $reportRoot 'termination.log'
        Write-TerminationEvent 'run_started' 'started'
        New-Item -ItemType Directory -Path $secretRoot -ErrorAction Stop | Out-Null
        $secretRootCreated = $true
        # Replace the entire DACL before writing any secrets. Children inherit only
        # these two entries; FullControl includes reading and deleting the files.
        $currentIdentity = [System.Security.Principal.WindowsIdentity]::GetCurrent().User
        $systemIdentity = [System.Security.Principal.SecurityIdentifier]::new('S-1-5-18')
        $secretAcl = [System.Security.AccessControl.DirectorySecurity]::new()
        $secretAcl.SetAccessRuleProtection($true, $false)
        foreach ($identity in @($currentIdentity, $systemIdentity)) {
            $rule = [System.Security.AccessControl.FileSystemAccessRule]::new(
                $identity, 'FullControl', 'ContainerInherit, ObjectInherit', 'None', 'Allow')
            $secretAcl.AddAccessRule($rule)
        }
        Set-Acl -LiteralPath $secretRoot -AclObject $secretAcl
        foreach ($secretFile in @($passwordFile, $rootPasswordFile)) {
            $bytes = New-Object byte[] 48
            [System.Security.Cryptography.RandomNumberGenerator]::Fill($bytes)
            [System.IO.File]::WriteAllText($secretFile, [Convert]::ToBase64String($bytes), [Text.UTF8Encoding]::new($false))
        }

        $env:WV_FULL_TEST_DB_PASSWORD_FILE = $passwordFile
        $env:WV_FULL_TEST_DB_ROOT_PASSWORD_FILE = $rootPasswordFile
        $env:WV_FULL_TEST_EXECUTE = if ($Mode -eq 'Full') { '1' } else { '0' }
        # Explicitly clear inherited filters for an unfiltered run.
        $env:WV_FULL_TEST_FILTER = if ($PSBoundParameters.ContainsKey('TestFilter')) { $TestFilter } else { '' }
        $composeConfigured = $true

        $terminationStage = 'config'
        Write-TerminationEvent 'config_started' 'started'
        & $docker.Source compose --env-file $composeEnvironment --project-name $composeProject --file $composeFile config --quiet
        $configCode = $LASTEXITCODE
        Write-TerminationEvent 'config_finished' $(if ($configCode -eq 0) { 'success' } else { 'failed' }) $configCode
        if ($configCode -ne 0) {
            throw 'Docker Compose configuration validation failed.'
        }

        # Persist diagnostics before containers are removed. No host bind mount.
        $terminationStage = 'run_log_setup'
        $runLog = Join-Path $reportRoot 'run.log'
        $firstFailureFile = Join-Path $reportRoot 'first-failure.log'
        $logWriter = [IO.StreamWriter]::new($runLog, $false, [Text.UTF8Encoding]::new($false))
        $logWriter.AutoFlush = $true
        $logWriter.WriteLine(('PHPUnit selection: ' + $(if ($TestFilter) { $TestFilter } else { 'all tests' })))
        $secretValues = @([IO.File]::ReadAllText($passwordFile), [IO.File]::ReadAllText($rootPasswordFile))
        Write-Host "Disposable run log: $runLog"
        $terminationStage = 'compose_up'
        Write-TerminationEvent 'compose_up_started' 'started'
        & $docker.Source compose --env-file $composeEnvironment --project-name $composeProject --file $composeFile up --build --abort-on-container-exit --exit-code-from full-test 2>&1 | ForEach-Object {
            $line = [string] $_
            foreach ($secret in $secretValues) {
                if ($secret.Length -gt 0) { $line = $line.Replace($secret, '[REDACTED]') }
            }
            # Drop terminal colour sequences before matching or saving diagnostics.
            $line = $line -replace '\x1B\[[0-?]*[ -/]*[@-~]', ''
            if ($line -match 'Test (Prepared|Started) \(') { $lastTest = $line }
            $recentOutput.Enqueue($line)
            while ($recentOutput.Count -gt 30) { $null = $recentOutput.Dequeue() }
            $logWriter.WriteLine($line)
            if ($firstFailureSaved -and $failureTailRemaining -gt 0) {
                [IO.File]::AppendAllText($firstFailureFile, $line + [Environment]::NewLine, [Text.UTF8Encoding]::new($false))
                $failureTailRemaining--
            }
            if (-not $firstFailureSaved -and $line -match '(?i)Allowed memory size|Fatal error|Uncaught |Test (Errored|Failed) \(|FULL-TEST PREFLIGHT FAILED|^.*\bERROR:') {
                $snapshot = @('First diagnostic failure (not overwritten by later failures).', $lastTest) + $recentOutput.ToArray()
                [IO.File]::WriteAllLines($firstFailureFile, $snapshot, [Text.UTF8Encoding]::new($false))
                $firstFailureSaved = $true
                $failureTailRemaining = 20
            }
            Write-Host $line
        }
        $runExitCode = $LASTEXITCODE
        $composeUpCode = $runExitCode
        Write-TerminationEvent 'compose_up_finished' $(if ($runExitCode -eq 0) { 'success' } else { 'failed' }) $composeUpCode
        if ($runExitCode -ne 0) {
            $primaryFailure = $true
            if (-not $firstFailureSaved) {
                $snapshot = @("Run exited with code $runExitCode; no recognized failure marker.", $lastTest) + $recentOutput.ToArray()
                [IO.File]::WriteAllLines($firstFailureFile, $snapshot, [Text.UTF8Encoding]::new($false))
                $firstFailureSaved = $true
            }
            Write-Host "First failure diagnostic: $firstFailureFile"
            Write-Warning "Disposable $Mode failed with exit code $runExitCode." -WarningAction Continue
        }
    }
    catch {
        $primaryFailure = $true
        Write-TerminationEvent 'harness_exception' 'failed'
        if ($terminationStage -eq 'compose_up' -and $null -eq $composeUpCode) {
            Write-TerminationEvent 'compose_up_finished' 'unknown'
        }
        throw
    }
    finally {
        if ($null -ne $logWriter) {
            try { $logWriter.Dispose() }
            catch { Write-Warning 'Could not close the disposable run log.' -WarningAction Continue }
        }
        $cleanupFailed = $false
        $terminationStage = 'compose_cleanup'
        try {
            if ($composeConfigured) {
                Write-TerminationEvent 'compose_cleanup_started' 'started'
                & $docker.Source compose --env-file $composeEnvironment --project-name $composeProject --file $composeFile down --volumes --remove-orphans 2>$null
                $composeCleanupCode = $LASTEXITCODE
                if ($composeCleanupCode -ne 0) {
                    throw 'Docker Compose cleanup failed.'
                }
                Write-TerminationEvent 'compose_cleanup_finished' 'success' $composeCleanupCode
            } else {
                Write-TerminationEvent 'compose_cleanup_finished' 'not_attempted'
            }
        }
        catch {
            $cleanupFailed = $true
            Write-TerminationEvent 'compose_cleanup_finished' 'failed' $composeCleanupCode
            Write-Warning 'Docker Compose cleanup failed; disposable resources may remain.' -WarningAction Continue
        }
        Remove-Item Env:WV_FULL_TEST_DB_PASSWORD_FILE -ErrorAction SilentlyContinue
        Remove-Item Env:WV_FULL_TEST_DB_ROOT_PASSWORD_FILE -ErrorAction SilentlyContinue
        Remove-Item Env:WV_FULL_TEST_EXECUTE -ErrorAction SilentlyContinue
        [Environment]::SetEnvironmentVariable('WV_FULL_TEST_FILTER', $previousTestFilter, 'Process')
        $terminationStage = 'secret_cleanup'
        Write-TerminationEvent 'secret_cleanup_started' 'started'
        try {
            if ($secretRootCreated -and (Test-Path -LiteralPath $secretRoot)) {
                $directory = Get-Item -LiteralPath $secretRoot -Force
                if ($directory.FullName -ne $secretRoot -or
                    $directory.Parent.FullName -ne $systemTemp.TrimEnd('\', '/') -or
                    $directory.Name -cne ('webvitrina-full-test-' + $runId) -or
                    ($directory.Attributes -band [IO.FileAttributes]::ReparsePoint)) {
                    throw 'Refusing an unsafe temporary secret directory.'
                }
                $children = @(Get-ChildItem -LiteralPath $secretRoot -Force)
                foreach ($child in $children) {
                    if ($child.PSIsContainer -or
                        ($child.Attributes -band [IO.FileAttributes]::ReparsePoint) -or
                        $child.FullName -notin @($passwordFile, $rootPasswordFile)) {
                        throw 'Refusing unexpected temporary secret directory contents.'
                    }
                }
                foreach ($child in $children) {
                    Remove-Item -LiteralPath $child.FullName -Force
                }
                # Non-recursive deletion cannot traverse an unexpected subdirectory.
                [System.IO.Directory]::Delete($secretRoot, $false)
            }
            Write-TerminationEvent 'secret_cleanup_finished' $(if ($secretRootCreated) { 'success' } else { 'not_attempted' })
        }
        catch {
            $cleanupFailed = $true
            Write-TerminationEvent 'secret_cleanup_finished' 'failed'
            Write-Warning 'Temporary secret cleanup failed; protected files may remain.' -WarningAction Continue
        }
        $terminationStage = 'finished'
        Write-TerminationEvent 'harness_finished' $(if ($primaryFailure -or $cleanupFailed) { 'failed' } else { 'success' }) $composeUpCode
        if ($cleanupFailed -and -not $primaryFailure) {
            throw 'Disposable isolation cleanup failed. See cleanup warnings.'
        }
    }
}
finally {
    Pop-Location
}

exit $runExitCode
