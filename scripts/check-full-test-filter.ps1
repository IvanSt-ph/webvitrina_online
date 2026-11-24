$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

# Offline regression checks: parse the harness, execute ONLY its parameter block
# and the standalone PHP validator. Never execute Docker, PHPUnit or Laravel.
$root = Split-Path $PSScriptRoot -Parent
$tokens = $null
$errors = $null
$ast = [System.Management.Automation.Language.Parser]::ParseFile(
    (Join-Path $PSScriptRoot 'run-full-tests.ps1'), [ref]$tokens, [ref]$errors)
if ($errors.Count) { throw 'Harness syntax error.' }
$parametersOnly = [scriptblock]::Create($ast.ParamBlock.Extent.Text + "`n 'accepted'")
$accepted = @('DisposableDatabaseModeTest', 'test_connected_query_keeps_original_pdo',
    'DisposableDatabaseModeTest::test_connected_query_keeps_original_pdo', '_Test1')
$rejected = @('', ' ', '--help', 'A B', 'A;id', 'A|B', '$(id)', 'A`id',
    '../Test', 'A/B', 'A\B', 'A.*', 'A::', 'A::b::c', "A`n", "A`r`n", 'Тест', ('A' * 201))
$previous = [Environment]::GetEnvironmentVariable('WV_FULL_TEST_FILTER', 'Process')
try {
    foreach ($case in @($accepted + $rejected)) {
        $expected = $accepted -ccontains $case
        $valid = $true
        try { $null = & $parametersOnly -Mode Full -TestFilter $case }
        catch { $valid = $false }
        if ($valid -ne $expected) { throw 'PowerShell filter validation regression.' }
        [Environment]::SetEnvironmentVariable('WV_FULL_TEST_FILTER', $case, 'Process')
        $null = & php (Join-Path $PSScriptRoot 'validate-full-test-filter.php') 2>&1
        if (($LASTEXITCODE -eq 0) -ne $expected) { throw 'PHP filter validation regression.' }
    }
    $null = & $parametersOnly -Mode Full
} finally {
    [Environment]::SetEnvironmentVariable('WV_FULL_TEST_FILTER', $previous, 'Process')
}

$runner = Get-Content (Join-Path $PSScriptRoot 'run-full-tests.ps1') -Raw
$entry = Get-Content (Join-Path $PSScriptRoot 'full-test-entrypoint.sh') -Raw
$compose = Get-Content (Join-Path $root 'docker-compose.full-test.yml') -Raw
foreach ($literal in @('set -- --filter "$WV_FULL_TEST_FILTER"', '--colors=never "$@"',
    'php /app/scripts/validate-full-test-filter.php')) {
    if (-not $entry.Contains($literal)) { throw 'Entrypoint argument transport regression.' }
}
if ($entry.IndexOf('php /app/scripts/full-test-preflight.php') -lt 0 -or
    $entry.IndexOf('php /app/scripts/full-test-preflight.php') -gt $entry.IndexOf('set -- --filter')) {
    throw 'Preflight ordering regression.'
}
if (-not $compose.Contains('WV_FULL_TEST_FILTER: "${WV_FULL_TEST_FILTER:-}"') -or
    -not $runner.Contains('$env:WV_FULL_TEST_FILTER = if ($PSBoundParameters.ContainsKey(''TestFilter'')) { $TestFilter } else { '''' }') -or
    -not $runner.Contains('--exit-code-from full-test') -or
    -not $runner.Contains('$runExitCode = $LASTEXITCODE')) {
    throw 'Compose transport, unfiltered default or exit-code regression.'
}
'Offline filter regression checks passed.'
