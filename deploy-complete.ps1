# Complete 11Seconds Deployment with Build
# Version 2.3.0 - Full automation
param([string]$Component = "web")

Write-Host "=== 11Seconds Complete Deployment v2.3.0 ===" -ForegroundColor Cyan
Write-Host "Testing change: Login page version indicator" -ForegroundColor Yellow

# FTP Settings
$FTP_HOST = "ftp.11seconds.de"
$FTP_USER = "k302164_11s"  
$FTP_PASS = "hallo.411S"

try {
    # Step 1: Check prerequisites
    Write-Host "[1/6] Checking prerequisites..." -ForegroundColor Green
    
    if (-not (Test-Path "web\package.json")) {
        throw "web\package.json not found. Please run from project root."
    }
    
    # Check if npm is available
    try {
        $npmVersion = cmd /c "npm --version" 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-Host "  npm version: $npmVersion" -ForegroundColor Gray
        } else {
            throw "npm not found"
        }
    } catch {
        Write-Host "  ERROR: npm not found or not working" -ForegroundColor Red
        Write-Host "  Please install Node.js and npm first" -ForegroundColor Yellow
        exit 1
    }
    
    # Step 2: Test FTP first
    Write-Host "[2/6] Testing FTP connection..." -ForegroundColor Green
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
        throw "FTP test failed"
    }

    # Step 3: Build React using cmd
    Write-Host "[3/6] Building React app..." -ForegroundColor Green
    
    # Change to web directory and run build
    $originalLocation = Get-Location
    Set-Location "web"
    
    try {
        Write-Host "  Running npm run build..." -ForegroundColor Gray
        
        # Use cmd to run npm (more reliable than PowerShell invocation)
        $buildProcess = Start-Process -FilePath "cmd" -ArgumentList "/c", "npm run build" -Wait -PassThru -NoNewWindow
        
        if ($buildProcess.ExitCode -ne 0) {
            throw "npm run build failed with exit code $($buildProcess.ExitCode)"
        }
        
        # Check if build folder was created
        if (-not (Test-Path "build")) {
            throw "Build folder was not created"
        }
        
        Write-Host "  React build completed successfully" -ForegroundColor Green
        
    } finally {
        Set-Location $originalLocation
    }

    # Step 4: Copy build to httpdocs
    Write-Host "[4/6] Preparing deployment files..." -ForegroundColor Green
    
    if (Test-Path "httpdocs") {
        Remove-Item "httpdocs" -Recurse -Force
        Write-Host "  Removed old httpdocs" -ForegroundColor Gray
    }
    
    Copy-Item "web\build" "httpdocs" -Recurse
    Write-Host "  Build files copied to httpdocs" -ForegroundColor Green

    # Step 5: Update Service Worker
    Write-Host "[5/6] Updating Service Worker..." -ForegroundColor Green
    $timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
    $version = "v2.3.0-$timestamp"

    # Find actual JS/CSS files
    $jsFiles = Get-ChildItem "httpdocs\static\js\main.*.js" -ErrorAction SilentlyContinue
    $cssFiles = Get-ChildItem "httpdocs\static\css\main.*.css" -ErrorAction SilentlyContinue

    $mainJs = if ($jsFiles) { "static/js/$($jsFiles[0].Name)" } else { "static/js/main.js" }
    $mainCss = if ($cssFiles) { "static/css/$($cssFiles[0].Name)" } else { "static/css/main.css" }

    Write-Host "  JS: $mainJs" -ForegroundColor Gray
    Write-Host "  CSS: $mainCss" -ForegroundColor Gray

$swContent = @"
// 11Seconds Service Worker - Complete Deployment Test
const CACHE_NAME = '11seconds-$version';
const urlsToCache = [
  '/',
  '/$mainJs',
  '/$mainCss',
  '/manifest.json',
  '/favicon.ico'
];

console.log('11Seconds SW: Loading version $version');

self.addEventListener('install', event => {
  console.log('11Seconds SW: Installing...');
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      console.log('11Seconds SW: Caching files');
      return cache.addAll(urlsToCache);
    })
  );
});

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

self.addEventListener('fetch', event => {
  if (event.request.method !== 'GET') return;
  if (!event.request.url.startsWith(self.location.origin)) return;
  
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        if (response) {
          console.log('11Seconds SW: Serving from cache:', event.request.url);
          return response;
        }
        console.log('11Seconds SW: Fetching from network:', event.request.url);
        return fetch(event.request);
      })
      .catch(() => {
        if (event.request.mode === 'navigate') {
          console.log('11Seconds SW: Serving index.html for navigation');
          return caches.match('/');
        }
      })
  );
});
"@

    [System.IO.File]::WriteAllText("httpdocs\sw.js", $swContent, [System.Text.Encoding]::UTF8)
    Write-Host "  Service Worker updated with version $version" -ForegroundColor Green

    # Step 6: Upload all files
    Write-Host "[6/6] Uploading files to FTP server..." -ForegroundColor Green
    $files = Get-ChildItem "httpdocs" -Recurse -File
    $uploaded = 0
    $failed = 0
    $totalSize = 0

    Write-Host "  Found $($files.Count) files to upload" -ForegroundColor Gray

    foreach ($file in $files) {
        $relativePath = $file.FullName.Substring((Resolve-Path "httpdocs").Path.Length + 1).Replace('\', '/')
        $totalSize += $file.Length
        
        try {
            $ftpUri = "ftp://$FTP_HOST/$relativePath"
            $request = [System.Net.FtpWebRequest]::Create($ftpUri)
            $request.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
            $request.Credentials = New-Object System.Net.NetworkCredential($FTP_USER, $FTP_PASS)
            $request.UsePassive = $true
            $request.UseBinary = $true
            $request.Timeout = 45000  # 45 seconds for large files
            
            $content = [System.IO.File]::ReadAllBytes($file.FullName)
            $request.ContentLength = $content.Length
            
            $stream = $request.GetRequestStream()
            $stream.Write($content, 0, $content.Length)
            $stream.Close()
            
            $response = $request.GetResponse()
            $response.Close()
            
            $sizeKB = [math]::Round($file.Length / 1024, 1)
            Write-Host "  OK: $relativePath ($sizeKB KB)" -ForegroundColor Green
            $uploaded++
            
        } catch {
            Write-Host "  FAIL: $relativePath - $($_.Exception.Message)" -ForegroundColor Red
            $failed++
        }
    }

    $totalSizeMB = [math]::Round($totalSize / 1024 / 1024, 2)

    # Final Summary
    Write-Host "`n=== DEPLOYMENT COMPLETE ===" -ForegroundColor Cyan
    Write-Host "Uploaded: $uploaded files ($totalSizeMB MB)" -ForegroundColor Green
    Write-Host "Failed: $failed files" -ForegroundColor $(if($failed -gt 0){"Red"}else{"Green"})
    Write-Host "Website: http://11seconds.de" -ForegroundColor Yellow
    Write-Host "Test Login: test / test123" -ForegroundColor Yellow
    Write-Host "Version: $version" -ForegroundColor Gray
    Write-Host "Change: Login page shows version indicator" -ForegroundColor Blue
    
    if ($failed -eq 0) {
        Write-Host "`nDEPLOYMENT SUCCESSFUL!" -ForegroundColor Green
        Write-Host "The website is now live with your changes!" -ForegroundColor Green
    } else {
        Write-Host "`nDEPLOYMENT COMPLETED WITH ERRORS" -ForegroundColor Yellow
        Write-Host "$failed files failed to upload" -ForegroundColor Red
    }

} catch {
    Write-Host "`nDEPLOYMENT FAILED" -ForegroundColor Red
    Write-Host "Error: $($_.Exception.Message)" -ForegroundColor Red
    
    # Cleanup on failure
    if (Test-Path "httpdocs") {
        Write-Host "Cleaning up httpdocs folder..." -ForegroundColor Gray
        Remove-Item "httpdocs" -Recurse -Force -ErrorAction SilentlyContinue
    }
    
    exit 1
}
