# 11Seconds Deployment Script - Corrected Version
# Updated with correct FTP credentials
param(
    [Parameter(Mandatory = $false)]
    [ValidateSet("web", "all")]
    [string]$Component = "web"
)

Write-Host "=== 11Seconds Deployment v2.1.0 ===" -ForegroundColor Cyan
Write-Host "Component: $Component" -ForegroundColor Yellow
Write-Host "Date: $(Get-Date)" -ForegroundColor Yellow

# Corrected FTP Credentials
$FTP_HOST = "ftp.11seconds.de"
$FTP_USER = "k302164_11s"
$FTP_PASS = "hallo.411S"
$REMOTE_PATH = "/"

try {
    # Step 1: Build React App
    Write-Host "`n[1/4] Building React App..." -ForegroundColor Green
    Set-Location "web"
    
    # Run npm build
    $buildResult = npm run build 2>&1
    if ($LASTEXITCODE -ne 0) {
        Write-Host "Build failed!" -ForegroundColor Red
        Write-Host $buildResult -ForegroundColor Red
        exit 1
    }
    Write-Host "✓ React build completed" -ForegroundColor Green
    
    # Step 2: Update Service Worker
    Write-Host "`n[2/4] Updating Service Worker..." -ForegroundColor Green
    Set-Location ".."
    
    $timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
    $version = "v2.1.0-$timestamp"
    
    # Get main JS and CSS files from asset-manifest.json
    if (Test-Path "httpdocs/asset-manifest.json") {
        $manifest = Get-Content "httpdocs/asset-manifest.json" | ConvertFrom-Json
        $mainJs = $manifest.files."main.js" -replace "^/", ""
        $mainCss = $manifest.files."main.css" -replace "^/", ""
    } else {
        Write-Host "⚠ asset-manifest.json not found, using fallback" -ForegroundColor Yellow
        $mainJs = "static/js/main.js"
        $mainCss = "static/css/main.css"
    }
    
    # Update Service Worker
    $swContent = @"
// Service Worker for 11Seconds Quiz Game
// Enables offline functionality and caching

const CACHE_NAME = '11seconds-$version';
const urlsToCache = [
  '/',
  '/$mainJs',
  '/$mainCss',
  '/manifest.json',
  '/favicon.ico'
];

// Install event - cache important files
self.addEventListener('install', event => {
  console.log('11Seconds SW: Installing...');
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('11Seconds SW: Caching app files');
        return cache.addAll(urlsToCache.map(url => new Request(url, { credentials: 'same-origin' })));
      })
      .catch(err => {
        console.log('11Seconds SW: Cache failed', err);
      })
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
  console.log('11Seconds SW: Activating...');
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            console.log('11Seconds SW: Deleting old cache', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});

// Fetch event - serve from cache when offline
self.addEventListener('fetch', event => {
  // Skip non-GET requests
  if (event.request.method !== 'GET') {
    return;
  }
  
  // Skip external requests
  if (!event.request.url.startsWith(self.location.origin)) {
    return;
  }
  
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        // Return cached version or fetch from network
        return response || fetch(event.request);
      })
      .catch(() => {
        // Fallback for navigation requests
        if (event.request.mode === 'navigate') {
          return caches.match('/');
        }
      })
  );
});
"@

    Set-Content -Path "httpdocs/sw.js" -Value $swContent -Encoding UTF8
    Write-Host "✓ Service Worker updated with version $version" -ForegroundColor Green
    
    # Step 3: Prepare files for upload
    Write-Host "`n[3/4] Preparing files..." -ForegroundColor Green
    $filesToUpload = @()
    
    # Get all files from httpdocs
    $allFiles = Get-ChildItem -Path "httpdocs" -Recurse -File
    foreach ($file in $allFiles) {
        $relativePath = $file.FullName.Substring((Resolve-Path "httpdocs").Path.Length + 1).Replace('\', '/')
        $filesToUpload += @{
            LocalPath = $file.FullName
            RemotePath = $relativePath
        }
    }
    
    Write-Host "✓ Found $($filesToUpload.Count) files to upload" -ForegroundColor Green
    
    # Step 4: Upload via FTP
    Write-Host "`n[4/4] Uploading to FTP server..." -ForegroundColor Green
    Write-Host "Host: $FTP_HOST" -ForegroundColor Gray
    Write-Host "User: $FTP_USER" -ForegroundColor Gray
    
    $uploadedCount = 0
    $failedCount = 0
    
    foreach ($fileInfo in $filesToUpload) {
        try {
            # Create FTP request
            $ftpUri = "ftp://$FTP_HOST/$($fileInfo.RemotePath)"
            $request = [System.Net.FtpWebRequest]::Create($ftpUri)
            $request.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
            $request.Credentials = New-Object System.Net.NetworkCredential($FTP_USER, $FTP_PASS)
            $request.UsePassive = $true
            $request.UseBinary = $true
            
            # Read file content
            $fileContent = [System.IO.File]::ReadAllBytes($fileInfo.LocalPath)
            $request.ContentLength = $fileContent.Length
            
            # Upload file
            $requestStream = $request.GetRequestStream()
            $requestStream.Write($fileContent, 0, $fileContent.Length)
            $requestStream.Close()
            
            # Get response
            $response = $request.GetResponse()
            $response.Close()
            
            Write-Host "  ✓ $($fileInfo.RemotePath)" -ForegroundColor Green
            $uploadedCount++
            
        } catch {
            Write-Host "  ✗ $($fileInfo.RemotePath) - $($_.Exception.Message)" -ForegroundColor Red
            $failedCount++
        }
    }
    
    # Summary
    Write-Host "`n=== Deployment Summary ===" -ForegroundColor Cyan
    Write-Host "✓ Files uploaded: $uploadedCount" -ForegroundColor Green
    if ($failedCount -gt 0) {
        Write-Host "✗ Files failed: $failedCount" -ForegroundColor Red
    }
    Write-Host "🌐 Website: http://11seconds.de" -ForegroundColor Yellow
    Write-Host "📝 Service Worker Version: $version" -ForegroundColor Gray
    
    if ($failedCount -eq 0) {
        Write-Host "`n🎉 Deployment completed successfully!" -ForegroundColor Green
    } else {
        Write-Host "`n⚠ Deployment completed with errors" -ForegroundColor Yellow
    }

} catch {
  Write-Host "`nDeployment failed: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
} finally {
    # Return to original directory
    Set-Location $PSScriptRoot
}
