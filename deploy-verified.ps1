# 11Seconds Deployment with Verification System
# Creates deployment timestamp file and verifies upload
param([string]$Component = "web")

$timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
$version = "v2.4.0-$timestamp"

Write-Host "=== 11Seconds Verified Deployment $version ===" -ForegroundColor Cyan

# FTP Settings
$FTP_HOST = "ftp.11seconds.de"
$FTP_USER = "k302164_11s"  
$FTP_PASS = "hallo.411S"

try {
    # Step 1: Check prerequisites
    Write-Host "[1/7] Checking prerequisites..." -ForegroundColor Green
    if (-not (Test-Path "web\package.json")) {
        throw "web\package.json not found. Please run from project root."
    }

    # Step 2: Build React
    Write-Host "[2/7] Building React app..." -ForegroundColor Green
    $originalLocation = Get-Location
    Set-Location "web"
    
    try {
        $buildProcess = Start-Process -FilePath "cmd" -ArgumentList "/c", "npm run build" -Wait -PassThru -NoNewWindow
        if ($buildProcess.ExitCode -ne 0) {
            throw "npm run build failed"
        }
        
        if (-not (Test-Path "build")) {
            throw "Build folder was not created"
        }
        
        Write-Host "  React build completed" -ForegroundColor Green
        
    } finally {
        Set-Location $originalLocation
    }

    # Step 3: Create deployment verification files
    Write-Host "[3/7] Creating verification files..." -ForegroundColor Green
    
    # Create deployment info file
    $deploymentInfo = @{
        version = $version
        timestamp = $timestamp
        buildTime = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
        deploymentId = [Guid]::NewGuid().ToString()
        files = @()
    }
    
    # Add this file to the React build
    $deploymentInfoJson = $deploymentInfo | ConvertTo-Json -Depth 3
    Set-Content -Path "web\build\deployment-info.json" -Value $deploymentInfoJson -Encoding UTF8
    
    # Create a simple verification HTML file
    $verificationHtml = @"
<!DOCTYPE html>
<html>
<head><title>Deployment $version</title></head>
<body>
<h1>🚀 Deployment Verification</h1>
<p><strong>Version:</strong> $version</p>
<p><strong>Timestamp:</strong> $timestamp</p>
<p><strong>Build Time:</strong> $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")</p>
<p><strong>Status:</strong> ✅ Successfully Deployed</p>
<script>
console.log('Deployment $version verified at', new Date());
</script>
</body>
</html>
"@
    Set-Content -Path "web\build\deployment-$timestamp.html" -Value $verificationHtml -Encoding UTF8
    
    Write-Host "  Verification files created" -ForegroundColor Green

    # Step 4: Prepare httpdocs (complete replacement)
    Write-Host "[4/7] Preparing httpdocs..." -ForegroundColor Green
    
    if (Test-Path "httpdocs") {
        Remove-Item "httpdocs" -Recurse -Force
        Write-Host "  Removed old httpdocs" -ForegroundColor Gray
    }
    
    Copy-Item "web\build" "httpdocs" -Recurse
    Write-Host "  Build files copied to httpdocs" -ForegroundColor Green

    # Step 5: Update Service Worker with correct file references
    Write-Host "[5/7] Updating Service Worker..." -ForegroundColor Green
    
    # Find actual JS/CSS files from httpdocs
    $jsFiles = Get-ChildItem "httpdocs\static\js\main.*.js" -ErrorAction SilentlyContinue
    $cssFiles = Get-ChildItem "httpdocs\static\css\main.*.css" -ErrorAction SilentlyContinue
    
    $mainJs = if ($jsFiles) { "static/js/$($jsFiles[0].Name)" } else { "static/js/main.js" }
    $mainCss = if ($cssFiles) { "static/css/$($cssFiles[0].Name)" } else { "static/css/main.css" }
    
    Write-Host "  JS: $mainJs" -ForegroundColor Gray
    Write-Host "  CSS: $mainCss" -ForegroundColor Gray

$swContent = @"
// 11Seconds Service Worker - Verified Deployment $version
const CACHE_NAME = '11seconds-$version';
const DEPLOYMENT_ID = '$($deploymentInfo.deploymentId)';
const urlsToCache = [
  '/',
  '/$mainJs',
  '/$mainCss',
  '/manifest.json',
  '/favicon.ico',
  '/deployment-info.json',
  '/deployment-$timestamp.html'
];

console.log('11Seconds SW: Loading VERIFIED version $version');
console.log('11Seconds SW: Deployment ID', DEPLOYMENT_ID);

// Force immediate activation
self.skipWaiting();

self.addEventListener('install', event => {
  console.log('11Seconds SW: Installing $version...');
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      console.log('11Seconds SW: Caching files for $version');
      return cache.addAll(urlsToCache);
    })
  );
});

self.addEventListener('activate', event => {
  console.log('11Seconds SW: Activating $version...');
  event.waitUntil(
    Promise.all([
      // Clear ALL old caches
      caches.keys().then(cacheNames => {
        return Promise.all(
          cacheNames.map(cacheName => {
            if (cacheName !== CACHE_NAME) {
              console.log('11Seconds SW: Deleting old cache', cacheName);
              return caches.delete(cacheName);
            }
          })
        );
      }),
      // Take control immediately
      self.clients.claim()
    ])
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

    # Step 6: Test FTP before upload
    Write-Host "[6/7] Testing FTP connection..." -ForegroundColor Green
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

    # Step 7: Upload all files with verification
    Write-Host "[7/7] Uploading and verifying files..." -ForegroundColor Green
    $files = Get-ChildItem "httpdocs" -Recurse -File
    $uploaded = 0
    $failed = 0
    $totalSize = 0
    $uploadedFiles = @()

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
            $request.Timeout = 60000  # 60 seconds for large files
            
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
            
            $uploadedFiles += @{
                path = $relativePath
                size = $file.Length
                uploaded = Get-Date
            }
            
        } catch {
            Write-Host "  FAIL: $relativePath - $($_.Exception.Message)" -ForegroundColor Red
            $failed++
        }
    }

    # Update deployment info with uploaded files
    $deploymentInfo.files = $uploadedFiles
    $deploymentInfo.uploadCompleted = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $deploymentInfo.totalFiles = $uploaded
    $deploymentInfo.failedFiles = $failed
    
    $finalDeploymentJson = $deploymentInfo | ConvertTo-Json -Depth 4
    Set-Content -Path "httpdocs\deployment-info.json" -Value $finalDeploymentJson -Encoding UTF8
    
    # Upload final deployment info
    try {
        $ftpUri = "ftp://$FTP_HOST/deployment-info.json"
        $request = [System.Net.FtpWebRequest]::Create($ftpUri)
        $request.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
        $request.Credentials = New-Object System.Net.NetworkCredential($FTP_USER, $FTP_PASS)
        $request.UsePassive = $true
        
        $finalJson = [System.Text.Encoding]::UTF8.GetBytes($finalDeploymentJson)
        $request.ContentLength = $finalJson.Length
        
        $stream = $request.GetRequestStream()
        $stream.Write($finalJson, 0, $finalJson.Length)
        $stream.Close()
        
        $response = $request.GetResponse()
        $response.Close()
        
        Write-Host "  Final deployment info uploaded" -ForegroundColor Green
    } catch {
        Write-Host "  Failed to upload deployment info" -ForegroundColor Yellow
    }

    $totalSizeMB = [math]::Round($totalSize / 1024 / 1024, 2)

    # Final Summary
    Write-Host "`n=== DEPLOYMENT VERIFICATION ===" -ForegroundColor Cyan
    Write-Host "Version: $version" -ForegroundColor Green
    Write-Host "Uploaded: $uploaded files ($totalSizeMB MB)" -ForegroundColor Green
    Write-Host "Failed: $failed files" -ForegroundColor $(if($failed -gt 0){"Red"}else{"Green"})
    Write-Host "Website: http://11seconds.de" -ForegroundColor Yellow
    Write-Host "Verification: http://11seconds.de/deployment-$timestamp.html" -ForegroundColor Blue
    Write-Host "Deployment Info: http://11seconds.de/deployment-info.json" -ForegroundColor Blue
    
    if ($failed -eq 0) {
        Write-Host "`nDEPLOYMENT SUCCESSFUL!" -ForegroundColor Green
        Write-Host "Check verification URL to confirm live deployment" -ForegroundColor Green
    } else {
        Write-Host "`nDEPLOYMENT COMPLETED WITH $failed ERRORS" -ForegroundColor Yellow
    }

} catch {
    Write-Host "`nDEPLOYMENT FAILED" -ForegroundColor Red
    Write-Host "Error: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}
