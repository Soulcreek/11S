# Ultra Simple Deployment - Manual Build Version
# Build manually, then this script uploads

param([string]$Component = "web")

Write-Host "=== 11Seconds Upload v2.3.0 ===" -ForegroundColor Cyan

# FTP Settings
$FTP_HOST = "ftp.11seconds.de"
$FTP_USER = "k302164_11s"  
$FTP_PASS = "hallo.411S"

# Check if build exists
if (-not (Test-Path "web\build")) {
    Write-Host "ERROR: web\build folder not found!" -ForegroundColor Red
    Write-Host "Please run: cd web; npm run build; cd .." -ForegroundColor Yellow
    exit 1
}

# Copy build to httpdocs
Write-Host "[1/4] Preparing files..." -ForegroundColor Green
if (Test-Path "httpdocs") {
    Remove-Item "httpdocs" -Recurse -Force
}
Copy-Item "web\build" "httpdocs" -Recurse
Write-Host "  Build files copied to httpdocs" -ForegroundColor Green

# Update Service Worker
Write-Host "[2/4] Updating Service Worker..." -ForegroundColor Green
$timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
$version = "v2.3.0-$timestamp"

# Find JS/CSS files
$jsFiles = Get-ChildItem "httpdocs\static\js\main.*.js" -ErrorAction SilentlyContinue
$cssFiles = Get-ChildItem "httpdocs\static\css\main.*.css" -ErrorAction SilentlyContinue

$mainJs = if ($jsFiles) { "static/js/$($jsFiles[0].Name)" } else { "static/js/main.js" }
$mainCss = if ($cssFiles) { "static/css/$($cssFiles[0].Name)" } else { "static/css/main.css" }

Write-Host "  JS: $mainJs" -ForegroundColor Gray
Write-Host "  CSS: $mainCss" -ForegroundColor Gray

$swContent = @"
const CACHE_NAME = '11seconds-$version';
const urlsToCache = [
  '/',
  '/$mainJs',
  '/$mainCss',
  '/manifest.json',
  '/favicon.ico'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(urlsToCache))
  );
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});

self.addEventListener('fetch', event => {
  if (event.request.method !== 'GET') return;
  if (!event.request.url.startsWith(self.location.origin)) return;
  
  event.respondWith(
    caches.match(event.request)
      .then(response => response || fetch(event.request))
      .catch(() => {
        if (event.request.mode === 'navigate') {
          return caches.match('/');
        }
      })
  );
});
"@

[System.IO.File]::WriteAllText("httpdocs\sw.js", $swContent, [System.Text.Encoding]::UTF8)
Write-Host "  Service Worker updated" -ForegroundColor Green

# Test FTP
Write-Host "[3/4] Testing FTP..." -ForegroundColor Green
try {
    $testUri = "ftp://$FTP_HOST/"
    $request = [System.Net.FtpWebRequest]::Create($testUri)
    $request.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectory
    $request.Credentials = New-Object System.Net.NetworkCredential($FTP_USER, $FTP_PASS)
    $request.UsePassive = $true
    $request.Timeout = 10000
    
    $response = $request.GetResponse()
    $response.Close()
    Write-Host "  FTP connection OK" -ForegroundColor Green
} catch {
    Write-Host "  FTP connection failed: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

# Upload files
Write-Host "[4/4] Uploading files..." -ForegroundColor Green
$files = Get-ChildItem "httpdocs" -Recurse -File
$uploaded = 0
$failed = 0

foreach ($file in $files) {
    $relativePath = $file.FullName.Substring((Resolve-Path "httpdocs").Path.Length + 1).Replace('\', '/')
    
    try {
        $ftpUri = "ftp://$FTP_HOST/$relativePath"
        $request = [System.Net.FtpWebRequest]::Create($ftpUri)
        $request.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
        $request.Credentials = New-Object System.Net.NetworkCredential($FTP_USER, $FTP_PASS)
        $request.UsePassive = $true
        $request.UseBinary = $true
        $request.Timeout = 30000
        
        $content = [System.IO.File]::ReadAllBytes($file.FullName)
        $request.ContentLength = $content.Length
        
        $stream = $request.GetRequestStream()
        $stream.Write($content, 0, $content.Length)
        $stream.Close()
        
        $response = $request.GetResponse()
        $response.Close()
        
        Write-Host "  OK: $relativePath" -ForegroundColor Green
        $uploaded++
        
    } catch {
        Write-Host "  FAIL: $relativePath - $($_.Exception.Message)" -ForegroundColor Red
        $failed++
    }
}

# Summary
Write-Host "`n=== DEPLOYMENT COMPLETE ===" -ForegroundColor Cyan
Write-Host "Uploaded: $uploaded files" -ForegroundColor Green
Write-Host "Failed: $failed files" -ForegroundColor $(if($failed -gt 0){"Red"}else{"Green"})
Write-Host "Website: http://11seconds.de" -ForegroundColor Yellow
Write-Host "Version: $version" -ForegroundColor Gray

if ($failed -eq 0) {
    Write-Host "`nSUCCESS: All files uploaded!" -ForegroundColor Green
} else {
    Write-Host "`nWARNING: $failed files failed" -ForegroundColor Yellow
}
