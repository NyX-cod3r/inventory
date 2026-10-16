<#
.SYNOPSIS
    Starts the Inventory Manager PHP dev server on every address "localhost" resolves to.

.DESCRIPTION
    On Windows, "localhost" resolves to both ::1 (IPv6) and 127.0.0.1 (IPv4), and a
    browser tries ::1 first. `php -S localhost:8000` binds only ONE of those addresses,
    so the other stays free and can be grabbed by an unrelated process. That is what
    causes http://localhost:8000/ to serve the wrong directory while http://127.0.0.1:8000/
    serves the correct one.

    This script starts one PHP server per address family, both rooted at this folder,
    after reclaiming port 8000 from any stale listener. Either URL then serves the app.

.EXAMPLE
    .\start-server.ps1              # start on port 8000
    .\start-server.ps1 -Port 8080   # start on a different port
    .\start-server.ps1 -Stop        # stop the servers this script manages
#>
[CmdletBinding()]
param(
    [int]$Port = 8000,
    [string]$PhpPath,
    [switch]$Stop
)

$ErrorActionPreference = 'Stop'
$docRoot = $PSScriptRoot
$logDir  = Join-Path $docRoot '.server-logs'
$netstat = Join-Path $env:SystemRoot 'System32\netstat.exe'

# --- Find PHP -------------------------------------------------------------
if (-not $PhpPath) {
    $phpCommand = Get-Command php -ErrorAction SilentlyContinue
    if ($phpCommand) { $PhpPath = $phpCommand.Source }
}
if (-not $PhpPath -or -not (Test-Path $PhpPath)) {
    throw 'php.exe was not found. Install PHP 8 or pass -PhpPath "C:\path\to\php.exe".'
}

# --- Helpers --------------------------------------------------------------
function Get-DevServerProcesses {
    # Finds PHP built-in servers bound to this port by inspecting their command
    # line, which covers 127.0.0.1, [::1], and localhost forms. netstat alone is
    # unreliable here: it under-reports IPv6 listeners, which let a stale
    # [::1] server survive an earlier cleanup and shadow this app.
    Get-CimInstance Win32_Process -Filter "Name = 'php.exe'" -ErrorAction SilentlyContinue |
        Where-Object { $_.CommandLine -match ('-S\s+(\[[^\]]+\]|[^:\s]+):' + $Port + '\b') }
}

function Stop-PortListeners {
    $servers = @(Get-DevServerProcesses)
    foreach ($server in $servers) {
        $bound = if ($server.CommandLine -match '-S\s+(\[[^\]]+\]|[^:\s]+):' + $Port) { $Matches[1] } else { '?' }
        Write-Host "Stopping stale PHP server on ${bound}:$Port (PID $($server.ProcessId))..."
        Stop-Process -Id $server.ProcessId -Force -ErrorAction SilentlyContinue
    }
    if ($servers.Count -gt 0) { Start-Sleep -Seconds 1 }

    # Anything else still holding the port is not ours to kill.
    $foreign = & $netstat -ano -p tcp |
        Where-Object { $_ -match 'LISTENING' -and $_ -match ":\s*$Port\s" } |
        ForEach-Object { if ($_ -match '(\d+)\s*$') { [int]$Matches[1] } } |
        Sort-Object -Unique
    foreach ($procId in $foreign) {
        $owner = Get-Process -Id $procId -ErrorAction SilentlyContinue
        if ($owner) {
            Write-Warning "Port $Port is held by non-PHP process '$($owner.ProcessName)' (PID $procId); leaving it alone."
        }
    }
}

function Test-HttpOk {
    param([string]$Url)
    try {
        (Invoke-WebRequest -Uri $Url -UseBasicParsing -TimeoutSec 5 -MaximumRedirection 5).StatusCode -eq 200
    } catch { $false }
}

# --- Stop mode ------------------------------------------------------------
if ($Stop) {
    Stop-PortListeners
    Write-Host "Done. Port $Port is free."
    return
}

# --- Start ----------------------------------------------------------------
Stop-PortListeners

if (-not (Test-Path $logDir)) { New-Item -ItemType Directory -Path $logDir | Out-Null }

# One server per address family; both share this folder as document root.
$listeners = @('127.0.0.1', '[::1]')
foreach ($listener in $listeners) {
    $tag = if ($listener -eq '[::1]') { 'ipv6' } else { 'ipv4' }
    Start-Process -FilePath $PhpPath `
        -ArgumentList @('-S', "${listener}:$Port", '-t', $docRoot) `
        -RedirectStandardOutput (Join-Path $logDir "$tag.out.log") `
        -RedirectStandardError  (Join-Path $logDir "$tag.err.log") `
        -WindowStyle Hidden | Out-Null
    Write-Host "Started PHP on ${listener}:$Port (docroot $docRoot)"
}

# --- Verify both addresses ------------------------------------------------
$targets = @("http://127.0.0.1:$Port/", "http://[::1]:$Port/")
$deadline = (Get-Date).AddSeconds(20)
$pending = @{}
foreach ($t in $targets) { $pending[$t] = $true }

while ($pending.Count -gt 0 -and (Get-Date) -lt $deadline) {
    Start-Sleep -Milliseconds 500
    foreach ($t in @($pending.Keys)) {
        if (Test-HttpOk -Url $t) { $pending.Remove($t) }
    }
}

if ($pending.Count -gt 0) {
    throw "These URLs did not answer HTTP 200: $($pending.Keys -join ', '). Check $logDir for details."
}

Write-Host ''
Write-Host "Both listeners verified HTTP 200. Open http://localhost:$Port/" -ForegroundColor Green
