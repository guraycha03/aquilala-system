Add-Type -AssemblyName System.Drawing

$fixtureDir = Join-Path $PSScriptRoot "tests\Fixtures"
New-Item -ItemType Directory -Force -Path $fixtureDir | Out-Null
$path = Join-Path $fixtureDir "ocr-member-list.png"

$lines = @(
    "JUAN DELA CRZ",
    "AQINO",
    "0917 123 4567",
    "juan@example.com"
)

$font = New-Object System.Drawing.Font("Arial", 44, [System.Drawing.FontStyle]::Bold)
$bmp = New-Object System.Drawing.Bitmap(1500, 520)
$g = [System.Drawing.Graphics]::FromImage($bmp)
$g.Clear([System.Drawing.Color]::White)
$g.TextRenderingHint = [System.Drawing.Text.TextRenderingHint]::SingleBitPerPixelGridFit
$brush = [System.Drawing.Brushes]::Black
$y = 60
foreach ($line in $lines) {
    $g.DrawString($line, $font, $brush, 40.0, [float]$y)
    $y += 100
}
$g.Dispose()
$bmp.Save($path, [System.Drawing.Imaging.ImageFormat]::Png)
$bmp.Dispose()
Write-Output "Wrote $path"