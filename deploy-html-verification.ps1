# HTML-based verification deployment
# JSON files might cause 500 errors - use HTML instead
param([string]$Component = "web")

$timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
$version = "v2.4.2-$timestamp"
$verificationId = [System.Guid]::NewGuid().ToString().Substring(0,8)
$verificationFilename = "verify-$timestamp-$verificationId.html"

Write-Host "=== 11Seconds HTML Verification Deployment ===" -ForegroundColor Cyan
Write-Host "Version: $version" -ForegroundColor Yellow
Write-Host "HTML Verification: $verificationFilename" -ForegroundColor Yellow

# FTP Settings
$FTP_HOST = "ftp.11seconds.de"
$FTP_USER = "k302164_11s"  
$FTP_PASS = "hallo.411S"

try {
    # Step 1: Create HTML verification file in React public
    Write-Host "[1/6] Creating HTML verification file..." -ForegroundColor Green
    
    $verificationHtml = @"
<!DOCTYPE html>
<html>
<head>
    <title>Deployment Verification $version</title>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f0f8ff; }
        .success { background: #4caf50; color: white; padding: 20px; border-radius: 10px; text-align: center; }
        .info { background: white; padding: 20px; border-radius: 10px; margin: 20px 0; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .code { background: #f5f5f5; padding: 10px; border-radius: 5px; font-family: monospace; }
        .timestamp { color: #666; font-size: 14px; }
    </style>
</head>
<body>
    <div class="success">
        <h1>🎉 DEPLOYMENT VERIFICATION SUCCESS!</h1>
        <p>Build was successfully deployed and is live!</p>
    </div>
    
    <div class="info">
        <h3>Deployment Details</h3>
        <div class="code">
            Version: $version<br>
            Timestamp: $timestamp<br>
            Verification ID: $verificationId<br>
            Filename: $verificationFilename<br>
            Deploy Time: $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")<br>
            Status: ✅ VERIFIED LIVE
        </div>
    </div>
    
    <div class="info">
        <h3>Test Results</h3>
        <p>✅ React build completed successfully</p>
        <p>✅ Files copied to httpdocs</p>
        <p>✅ FTP upload completed</p>
        <p>✅ This page is accessible = Deployment is LIVE!</p>
        <p><strong>🚀 The deployment system is working correctly!</strong></p>
    </div>
    
    <div class="info">
        <p><a href="/">← Back to 11Seconds Quiz</a></p>
        <div class="timestamp">Generated: $(Get-Date)</div>
    </div>
    
    <script>
        console.log('Verification page loaded successfully!');
        console.log('Version: $version');
        console.log('Timestamp: $timestamp');
        console.log('ID: $verificationId');
        
        // Auto-redirect to main app after 10 seconds
        setTimeout(function() {
            if (confirm('Verification successful! Go to main app?')) {
                window.location.href = '/';
            }
        }, 10000);
    </script>
</body>
</html>
"@
    
    $publicVerificationPath = "web\public\$verificationFilename"
    Set-Content -Path $publicVerificationPath -Value $verificationHtml -Encoding UTF8
    Write-Host "  HTML verification created: $publicVerificationPath" -ForegroundColor Green

    # Step 2: Build React
    Write-Host "[2/6] Building React..." -ForegroundColor Green
    $originalLocation = Get-Location
    Set-Location "web"
    
    try {
        $buildProcess = Start-Process -FilePath "cmd" -ArgumentList "/c", "npm run build" -Wait -PassThru -NoNewWindow
        if ($buildProcess.ExitCode -ne 0) {
            throw "npm run build failed"
        }
        
        # Verify HTML file is in build
        $builtHtmlPath = "build\$verificationFilename"
        if (-not (Test-Path $builtHtmlPath)) {
            throw "HTML verification file NOT in build: $builtHtmlPath"
        }
        
        Write-Host "  Build completed - HTML file verified in build" -ForegroundColor Green
        
    } finally {
        Set-Location $originalLocation
    }

    # Step 3: Copy to httpdocs
    Write-Host "[3/6] Copying to httpdocs..." -ForegroundColor Green
    if (Test-Path "httpdocs") {
        Remove-Item "httpdocs" -Recurse -Force
    }
    Copy-Item "web\build" "httpdocs" -Recurse
    
    # Verify HTML file is in httpdocs
    $httpdocsHtmlPath = "httpdocs\$verificationFilename"
    if (-not (Test-Path $httpdocsHtmlPath)) {
        throw "HTML verification file NOT in httpdocs: $httpdocsHtmlPath"
    }
    Write-Host "  HTML file verified in httpdocs" -ForegroundColor Green

    # Step 4: Update Service Worker
    Write-Host "[4/6] Updating Service Worker..." -ForegroundColor Green
    $jsFiles = Get-ChildItem "httpdocs\static\js\main.*.js" -ErrorAction SilentlyContinue
    $cssFiles = Get-ChildItem "httpdocs\static\css\main.*.css" -ErrorAction SilentlyContinue
    
    $mainJs = if ($jsFiles) { "static/js/$($jsFiles[0].Name)" } else { "static/js/main.js" }
    $mainCss = if ($cssFiles) { "static/css/$($cssFiles[0].Name)" } else { "static/css/main.css" }

$swContent = @"
// 11Seconds Service Worker - HTML Verification $version  
const CACHE_NAME = '11seconds-$version';
const VERIFICATION_URL = '/$verificationFilename';
const urlsToCache = [
  '/',
  '/$mainJs', 
  '/$mainCss',
  '/manifest.json',
  '/favicon.ico',
  VERIFICATION_URL
];

console.log('11Seconds SW: HTML verification $version');
console.log('11Seconds SW: Verification URL', VERIFICATION_URL);

self.skipWaiting();

self.addEventListener('install', event => {
  console.log('11Seconds SW: Installing $version...');
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(urlsToCache))
  );
});

self.addEventListener('activate', event => {
  console.log('11Seconds SW: Activating $version...');
  event.waitUntil(
    Promise.all([
      caches.keys().then(cacheNames => {
        return Promise.all(
          cacheNames.map(cacheName => {
            if (cacheName !== CACHE_NAME) {
              return caches.delete(cacheName);
            }
          })
        );
      }),
      self.clients.claim()
    ])
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

    # Step 5: Test FTP
    Write-Host "[5/6] Testing FTP..." -ForegroundColor Green
    $testRequest = [System.Net.FtpWebRequest]::Create("ftp://$FTP_HOST/")
    $testRequest.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectory
    $testRequest.Credentials = New-Object System.Net.NetworkCredential($FTP_USER, $FTP_PASS)
    $testRequest.UsePassive = $true
    
    $testResponse = $testRequest.GetResponse()
    $testResponse.Close()
    Write-Host "  FTP OK" -ForegroundColor Green

    # Step 6: Upload with HTML verification
    Write-Host "[6/6] Uploading with HTML verification..." -ForegroundColor Green
    $files = Get-ChildItem "httpdocs" -Recurse -File
    $uploaded = 0
    $failed = 0
    $htmlUploaded = $false

    foreach ($file in $files) {
        $relativePath = $file.FullName.Substring((Resolve-Path "httpdocs").Path.Length + 1).Replace('\', '/')
        $isHtmlVerification = ($relativePath -eq $verificationFilename)
        
        try {
            $ftpUri = "ftp://$FTP_HOST/$relativePath"
            $request = [System.Net.FtpWebRequest]::Create($ftpUri)
            $request.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
            $request.Credentials = New-Object System.Net.NetworkCredential($FTP_USER, $FTP_PASS)
            $request.UsePassive = $true
            $request.UseBinary = $true
            $request.Timeout = 60000
            
            $content = [System.IO.File]::ReadAllBytes($file.FullName)
            $request.ContentLength = $content.Length
            
            $stream = $request.GetRequestStream()
            $stream.Write($content, 0, $content.Length)
            $stream.Close()
            
            $response = $request.GetResponse()
            $response.Close()
            
            if ($isHtmlVerification) {
                Write-Host "  >>> HTML VERIFICATION UPLOADED! <<<" -ForegroundColor Green
                $htmlUploaded = $true
            }
            
            Write-Host "  OK: $relativePath" -ForegroundColor Green
            $uploaded++
            
        } catch {
            Write-Host "  FAIL: $relativePath - $($_.Exception.Message)" -ForegroundColor Red
            $failed++
        }
    }

    # Final verification
    Write-Host "`n=== HTML VERIFICATION RESULTS ===" -ForegroundColor Cyan
    Write-Host "Version: $version" -ForegroundColor Green  
    
    if ($htmlUploaded) {
        Write-Host "HTML Verification: SUCCESS" -ForegroundColor Green
    } else {
        Write-Host "HTML Verification: FAILED" -ForegroundColor Red
    }
    
    if ($failed -eq 0) {
        Write-Host "Files: Uploaded $uploaded, Failed $failed" -ForegroundColor Green
    } else {
        Write-Host "Files: Uploaded $uploaded, Failed $failed" -ForegroundColor Red
    }
    
    Write-Host "`n🌐 LIVE TEST URL:" -ForegroundColor Blue
    Write-Host "   http://11seconds.de/$verificationFilename" -ForegroundColor Yellow
    
    if ($htmlUploaded -and $failed -eq 0) {
        Write-Host "`n🎉 HTML VERIFICATION DEPLOYMENT SUCCESS!" -ForegroundColor Green
        Write-Host "Open the URL above to verify the deployment is live!" -ForegroundColor Green
    }

    # Cleanup
    if (Test-Path $publicVerificationPath) {
        Remove-Item $publicVerificationPath -Force
    }

} catch {
    Write-Host "`nDEPLOYMENT FAILED: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}
