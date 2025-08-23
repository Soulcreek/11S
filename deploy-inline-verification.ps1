# 11Seconds Deployment with INLINE Build Verification
# Creates verification file IN React source, builds it, uploads it, verifies it
param([string]$Component = "web")

$timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
$version = "v2.4.1-$timestamp"
$verificationId = [System.Guid]::NewGuid().ToString().Substring(0,8)
$verificationFilename = "build-verification-$timestamp-$verificationId.json"

Write-Host "=== 11Seconds INLINE Verification Deployment ===" -ForegroundColor Cyan
Write-Host "Version: $version" -ForegroundColor Yellow
Write-Host "Verification File: $verificationFilename" -ForegroundColor Yellow

# FTP Settings
$FTP_HOST = "ftp.11seconds.de"
$FTP_USER = "k302164_11s"  
$FTP_PASS = "hallo.411S"

try {
    # Step 1: Create verification file IN React public folder BEFORE build
    Write-Host "[1/6] Creating verification file in React source..." -ForegroundColor Green
    
    $buildVerification = @{
        timestamp = $timestamp
        version = $version
        verificationId = $verificationId
        buildStart = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
        filename = $verificationFilename
        purpose = "Verify this exact build was uploaded successfully"
    }
    
    $verificationJson = $buildVerification | ConvertTo-Json -Depth 3
    
    # Place verification file in React PUBLIC folder so it gets built
    $publicVerificationPath = "web\public\$verificationFilename"
    Set-Content -Path $publicVerificationPath -Value $verificationJson -Encoding UTF8
    
    Write-Host "  Verification file created: $publicVerificationPath" -ForegroundColor Green
    Write-Host "  Content preview: $($verificationJson.Substring(0, [Math]::Min(100, $verificationJson.Length)))..." -ForegroundColor Gray

    # Step 2: Build React (verification file will be included)
    Write-Host "[2/6] Building React with verification file..." -ForegroundColor Green
    
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
        
        # CRITICAL: Verify the file is in the build output
        $builtVerificationPath = "build\$verificationFilename"
        if (-not (Test-Path $builtVerificationPath)) {
            throw "Verification file NOT found in build output: $builtVerificationPath"
        }
        
        $builtContent = Get-Content $builtVerificationPath -Raw
        Write-Host "  VERIFIED: File exists in build: $builtVerificationPath" -ForegroundColor Green
        Write-Host "  Build content preview: $($builtContent.Substring(0, [Math]::Min(80, $builtContent.Length)))..." -ForegroundColor Gray
        
    } finally {
        Set-Location $originalLocation
    }

    # Step 3: Prepare httpdocs
    Write-Host "[3/6] Copying build to httpdocs..." -ForegroundColor Green
    
    if (Test-Path "httpdocs") {
        Remove-Item "httpdocs" -Recurse -Force
    }
    
    Copy-Item "web\build" "httpdocs" -Recurse
    Write-Host "  Build copied to httpdocs" -ForegroundColor Green
    
    # CRITICAL: Verify the file is in httpdocs
    $httpdocsVerificationPath = "httpdocs\$verificationFilename"
    if (-not (Test-Path $httpdocsVerificationPath)) {
        throw "Verification file NOT found in httpdocs: $httpdocsVerificationPath"
    }
    
    $httpdocsContent = Get-Content $httpdocsVerificationPath -Raw
    Write-Host "  VERIFIED: File exists in httpdocs: $httpdocsVerificationPath" -ForegroundColor Green

    # Step 4: Update Service Worker
    Write-Host "[4/6] Updating Service Worker..." -ForegroundColor Green
    
    $jsFiles = Get-ChildItem "httpdocs\static\js\main.*.js" -ErrorAction SilentlyContinue
    $cssFiles = Get-ChildItem "httpdocs\static\css\main.*.css" -ErrorAction SilentlyContinue
    
    $mainJs = if ($jsFiles) { "static/js/$($jsFiles[0].Name)" } else { "static/js/main.js" }
    $mainCss = if ($cssFiles) { "static/css/$($cssFiles[0].Name)" } else { "static/css/main.css" }

$swContent = @"
// 11Seconds Service Worker - INLINE Verification $version
const CACHE_NAME = '11seconds-$version';
const VERIFICATION_FILE = '/$verificationFilename';
const urlsToCache = [
  '/',
  '/$mainJs',
  '/$mainCss',
  '/manifest.json',
  '/favicon.ico',
  VERIFICATION_FILE
];

console.log('11Seconds SW: INLINE verification $version');
console.log('11Seconds SW: Verification file', VERIFICATION_FILE);

self.skipWaiting();

self.addEventListener('install', event => {
  console.log('11Seconds SW: Installing with verification...');
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(urlsToCache))
  );
});

self.addEventListener('activate', event => {
  console.log('11Seconds SW: Activating and clearing old caches...');
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
    Write-Host "[5/6] Testing FTP connection..." -ForegroundColor Green
    try {
        $testRequest = [System.Net.FtpWebRequest]::Create("ftp://$FTP_HOST/")
        $testRequest.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectory
        $testRequest.Credentials = New-Object System.Net.NetworkCredential($FTP_USER, $FTP_PASS)
        $testRequest.UsePassive = $true
        $testRequest.Timeout = 10000
        
        $testResponse = $testRequest.GetResponse()
        $testResponse.Close()
        Write-Host "  FTP connection OK" -ForegroundColor Green
    } catch {
        throw "FTP test failed: $($_.Exception.Message)"
    }

    # Step 6: Upload with INLINE verification
    Write-Host "[6/6] Uploading with INLINE verification..." -ForegroundColor Green
    
    $files = Get-ChildItem "httpdocs" -Recurse -File
    $uploaded = 0
    $failed = 0
    $verificationUploaded = $false
    $verificationUploadResult = $null

    Write-Host "  Files to upload: $($files.Count)" -ForegroundColor Gray

    foreach ($file in $files) {
        $relativePath = $file.FullName.Substring((Resolve-Path "httpdocs").Path.Length + 1).Replace('\', '/')
        $isVerificationFile = ($relativePath -eq $verificationFilename)
        
        if ($isVerificationFile) {
            Write-Host "  >>> UPLOADING VERIFICATION FILE: $relativePath <<<" -ForegroundColor Yellow
        }
        
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
            
            $sizeKB = [math]::Round($file.Length / 1024, 1)
            
            if ($isVerificationFile) {
                Write-Host "  >>> VERIFICATION FILE UPLOADED SUCCESSFULLY! <<<" -ForegroundColor Green
                $verificationUploaded = $true
                $verificationUploadResult = "SUCCESS"
                
                # Immediately test if we can download it back
                try {
                    $downloadRequest = [System.Net.FtpWebRequest]::Create($ftpUri)
                    $downloadRequest.Method = [System.Net.WebRequestMethods+Ftp]::DownloadFile
                    $downloadRequest.Credentials = New-Object System.Net.NetworkCredential($FTP_USER, $FTP_PASS)
                    $downloadRequest.UsePassive = $true
                    
                    $downloadResponse = $downloadRequest.GetResponse()
                    $downloadStream = $downloadResponse.GetResponseStream()
                    $downloadReader = New-Object System.IO.StreamReader($downloadStream)
                    $downloadedContent = $downloadReader.ReadToEnd()
                    $downloadReader.Close()
                    $downloadResponse.Close()
                    
                    $downloadedJson = $downloadedContent | ConvertFrom-Json
                    if ($downloadedJson.verificationId -eq $verificationId) {
                        Write-Host "  >>> VERIFICATION DOWNLOAD TEST: SUCCESS! <<<" -ForegroundColor Green
                        $verificationUploadResult = "SUCCESS_VERIFIED"
                    } else {
                        Write-Host "  >>> VERIFICATION DOWNLOAD TEST: ID MISMATCH! <<<" -ForegroundColor Red
                        $verificationUploadResult = "ID_MISMATCH"
                    }
                    
                } catch {
                    Write-Host "  >>> VERIFICATION DOWNLOAD TEST: FAILED - $($_.Exception.Message) <<<" -ForegroundColor Red
                    $verificationUploadResult = "DOWNLOAD_FAILED"
                }
            }
            
            Write-Host "  OK: $relativePath ($sizeKB KB)" -ForegroundColor Green
            $uploaded++
            
        } catch {
            Write-Host "  FAIL: $relativePath - $($_.Exception.Message)" -ForegroundColor Red
            $failed++
            
            if ($isVerificationFile) {
                $verificationUploaded = $false
                $verificationUploadResult = "UPLOAD_FAILED: $($_.Exception.Message)"
            }
        }
    }

    # Final verification summary
    Write-Host "`n=== INLINE VERIFICATION RESULTS ===" -ForegroundColor Cyan
    Write-Host "Version: $version" -ForegroundColor Green
    Write-Host "Verification File: $verificationFilename" -ForegroundColor Yellow
    Write-Host "Verification Upload: $verificationUploadResult" -ForegroundColor $(if($verificationUploadResult -eq "SUCCESS_VERIFIED"){"Green"}else{"Red"})
    Write-Host "Total Files: Uploaded $uploaded, Failed $failed" -ForegroundColor $(if($failed -eq 0){"Green"}else{"Red"})
    
    Write-Host "`n=== TEST URLS ===" -ForegroundColor Blue
    Write-Host "Website: http://11seconds.de" -ForegroundColor Yellow
    Write-Host "Verification: http://11seconds.de/$verificationFilename" -ForegroundColor Yellow
    
    if ($verificationUploadResult -eq "SUCCESS_VERIFIED" -and $failed -eq 0) {
        Write-Host "`n🎉 DEPLOYMENT WITH INLINE VERIFICATION SUCCESSFUL!" -ForegroundColor Green
        Write-Host "The verification file can be accessed directly via the URL above." -ForegroundColor Green
    } else {
  Write-Host "`nDEPLOYMENT VERIFICATION FAILED!" -ForegroundColor Red
        Write-Host "Verification Result: $verificationUploadResult" -ForegroundColor Red
    }

    # Cleanup: Remove verification file from React public folder
    if (Test-Path $publicVerificationPath) {
        Remove-Item $publicVerificationPath -Force
        Write-Host "Cleaned up verification file from React public folder" -ForegroundColor Gray
    }

} catch {
  Write-Host "`nDEPLOYMENT FAILED" -ForegroundColor Red
    Write-Host "Error: $($_.Exception.Message)" -ForegroundColor Red
    
    # Cleanup on failure
    $publicVerificationPath = "web\public\$verificationFilename"
    if (Test-Path $publicVerificationPath) {
        Remove-Item $publicVerificationPath -Force -ErrorAction SilentlyContinue
    }
    
    exit 1
}
