param(
    [string]$Name = "c-procurement",
    [string]$Output = "dist",
    [switch]$WithVendor,
    [switch]$WithNodeModules
)

$ErrorActionPreference = "Stop"

$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot "..")).Path
$timestamp = Get-Date -Format "yyyyMMdd-HHmmss"

$outputDir = Join-Path $projectRoot $Output
if (-not (Test-Path $outputDir)) {
    New-Item -ItemType Directory -Path $outputDir | Out-Null
}

$zipPath = Join-Path $outputDir ("{0}-{1}.zip" -f $Name, $timestamp)
if (Test-Path $zipPath) {
    Remove-Item -Force $zipPath
}

$excludeRoots = @(
    ".git",
    ".github",
    ".vscode",
    "dist",
    "storage/logs",
    "storage/framework/cache",
    "storage/framework/sessions",
    "storage/framework/testing",
    "storage/framework/views",
    "storage/app/private"
)

if (-not $WithNodeModules) {
    $excludeRoots += "node_modules"
}

if (-not $WithVendor) {
    $excludeRoots += "vendor"
}

$excludePrefix = $excludeRoots | ForEach-Object { (Join-Path $projectRoot $_) }

$allFiles = Get-ChildItem -Path $projectRoot -Recurse -File -Force |
    Where-Object {
        $full = $_.FullName

        if ($_.Name -eq ".env") { return $false }
        if ($_.Name -eq "hot" -and $_.DirectoryName -eq (Join-Path $projectRoot "public")) { return $false }
        if ($_.Name.EndsWith('.hot')) { return $false }
        if ($_.Extension -eq ".zip") { return $false }

        foreach ($prefix in $excludePrefix) {
            if ($full.StartsWith($prefix, [System.StringComparison]::OrdinalIgnoreCase)) {
                return $false
            }
        }

        return $true
    }

if ($allFiles.Count -eq 0) {
    throw "Tidak ada file yang bisa dipaketkan."
}

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$zip = [System.IO.Compression.ZipFile]::Open($zipPath, [System.IO.Compression.ZipArchiveMode]::Create)
try {
    foreach ($file in $allFiles) {
        $relative = $file.FullName.Substring($projectRoot.Length).TrimStart([char[]]"\\/")
        [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $file.FullName, $relative, [System.IO.Compression.CompressionLevel]::Optimal) | Out-Null
    }
}
finally {
    $zip.Dispose()
}

Write-Host "Paket deploy berhasil dibuat: $zipPath"
Write-Host "Catatan: .git dan file sensitif (.env) tidak ikut di dalam zip."
