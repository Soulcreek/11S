# 11Seconds Deployment with Lock System
# Prevents parallel deployment conflicts
param(
    [Parameter(Mandatory = $false)]
    [ValidateSet("web", "all")]
    [string]$Component = "web"
)

# Lock file to prevent parallel deployments
$lockFile = "$env:TEMP\11s-deployment.lock"
$lockTimeout = 300  # 5 minutes timeout

function Test-DeploymentLock {
    if (Test-Path $lockFile) {
        $lockInfo = Get-Content $lockFile | ConvertFrom-Json
        $lockTime = [DateTime]$lockInfo.StartTime
        $timeDiff = (Get-Date) - $lockTime
        
        if ($timeDiff.TotalSeconds -lt $lockTimeout) {
            Write-Host "⚠️  DEPLOYMENT BEREITS AKTIV!" -ForegroundColor Yellow
            Write-Host "   Projekt: $($lockInfo.Project)" -ForegroundColor Gray
            Write-Host "   Gestartet: $($lockInfo.StartTime)" -ForegroundColor Gray
            Write-Host "   PID: $($lockInfo.ProcessId)" -ForegroundColor Gray
            Write-Host "" -ForegroundColor Gray
            Write-Host "🛑 Bitte warten Sie bis das andere Deployment abgeschlossen ist!" -ForegroundColor Red
            Write-Host "   Oder beenden Sie das andere Deployment falls es hängt." -ForegroundColor Yellow
            return $false
        } else {
            # Lock expired, remove it
            Remove-Item $lockFile -Force -ErrorAction SilentlyContinue
        }
    }
    return $true
}

function Set-DeploymentLock {
    $lockInfo = @{
        Project = "11Seconds"
        StartTime = (Get-Date).ToString("yyyy-MM-dd HH:mm:ss")
        ProcessId = $PID
        Component = $Component
    }
    $lockInfo | ConvertTo-Json | Out-File $lockFile -Encoding UTF8
}

function Remove-DeploymentLock {
    Remove-Item $lockFile -Force -ErrorAction SilentlyContinue
}

# Check for parallel deployment
Write-Host "=== 11Seconds Deployment Lock Check ===" -ForegroundColor Cyan
if (-not (Test-DeploymentLock)) {
    Write-Host ""
    Write-Host "💡 Tipps zum Vermeiden von Konflikten:" -ForegroundColor Blue
    Write-Host "   • Warten Sie bis andere Deployments abgeschlossen sind" -ForegroundColor Gray
    Write-Host "   • Verwenden Sie verschiedene Terminals für verschiedene Projekte" -ForegroundColor Gray
    Write-Host "   • Prüfen Sie mit 'Get-Process PowerShell' aktive Deployments" -ForegroundColor Gray
    exit 1
}

# Set lock
Set-DeploymentLock
Write-Host "🔒 Deployment-Lock gesetzt für 11Seconds" -ForegroundColor Green

try {
    Write-Host "=== 11Seconds Deployment v2.1.1 ===" -ForegroundColor Cyan
    Write-Host "Component: $Component" -ForegroundColor Yellow
    Write-Host "Date: $(Get-Date)" -ForegroundColor Yellow
    Write-Host "Lock: Aktiv (PID: $PID)" -ForegroundColor Green

    # FTP Credentials
    $FTP_HOST = "ftp.11seconds.de"
    $FTP_USER = "k302164_11s"
    $FTP_PASS = "hallo.411S"

    # Test FTP Connection first
    Write-Host "`n[TEST] Testing FTP Connection..." -ForegroundColor Blue
    try {
        $testUri = "ftp://$FTP_HOST/"
        $testRequest = [System.Net.FtpWebRequest]::Create($testUri)
        $testRequest.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectory
        $testRequest.Credentials = New-Object System.Net.NetworkCredential($FTP_USER, $FTP_PASS)
        $testRequest.UsePassive = $true
        $testRequest.Timeout = 10000  # 10 seconds
        
        $testResponse = $testRequest.GetResponse()
        $testResponse.Close()
        
        Write-Host "✓ FTP Connection successful" -ForegroundColor Green
    } catch {
        Write-Host "✗ FTP Connection failed: $($_.Exception.Message)" -ForegroundColor Red
        Write-Host "   Host: $FTP_HOST" -ForegroundColor Gray
        Write-Host "   User: $FTP_USER" -ForegroundColor Gray
        throw "FTP credentials test failed"
    }

    # Step 1: Build React App
    Write-Host "`n[1/4] Building React App..." -ForegroundColor Green
    if (Test-Path "web") {
        Set-Location "web"
    } else {
        throw "Web directory not found. Please run from project root."
    }
    
    $buildResult = npm run build 2>&1
    if ($LASTEXITCODE -ne 0) {
        Write-Host "Build failed!" -ForegroundColor Red
        Write-Host $buildResult -ForegroundColor Red
        throw "React build failed"
    }
    Write-Host "✓ React build completed" -ForegroundColor Green
    
    # Step 2: Update Service Worker
    Write-Host "`n[2/4] Updating Service Worker..." -ForegroundColor Green
    Set-Location ".."
    
    $timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
    $version = "v2.1.1-$timestamp"
    
    # Get main JS and CSS files
    if (Test-Path "httpdocs/asset-manifest.json") {
        $manifest = Get-Content "httpdocs/asset-manifest.json" | ConvertFrom-Json
        $mainJs = $manifest.files."main.js" -replace "^/", ""
        $mainCss = $manifest.files."main.css" -replace "^/", ""
    } else {
        # Find actual files
        $jsFiles = Get-ChildItem "httpdocs/static/js/" -Filter "main.*.js" | Sort-Object LastWriteTime -Descending
        $cssFiles = Get-ChildItem "httpdocs/static/css/" -Filter "main.*.css" | Sort-Object LastWriteTime -Descending
        $mainJs = if ($jsFiles) { "static/js/$($jsFiles[0].Name)" } else { "static/js/main.js" }
        $mainCss = if ($cssFiles) { "static/css/$($cssFiles[0].Name)" } else { "static/css/main.css" }
    }
    
    Write-Host "  JS: $mainJs" -ForegroundColor Gray
    Write-Host "  CSS: $mainCss" -ForegroundColor Gray
    
    # Update Service Worker with actual file names
    $swContent = @"
// Service Worker for 11Seconds Quiz Game - Lock System Enabled
const CACHE_NAME = '11seconds-$version';
const urlsToCache = [
  '/',
  '/$mainJs',
  '/$mainCss',
  '/manifest.json',
  '/favicon.ico'
];

self.addEventListener('install', event => {
  console.log('11Seconds SW: Installing $version...');
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(urlsToCache))
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

    Set-Content -Path "httpdocs/sw.js" -Value $swContent -Encoding UTF8
    Write-Host "✓ Service Worker updated" -ForegroundColor Green
    
    # Step 3: Upload Files
    Write-Host "`n[3/4] Uploading files..." -ForegroundColor Green
    
    $files = Get-ChildItem -Path "httpdocs" -Recurse -File
    $uploadCount = 0
    $errorCount = 0
    
    foreach ($file in $files) {
        $relativePath = $file.FullName.Substring((Resolve-Path "httpdocs").Path.Length + 1).Replace('\', '/')
        
        try {
            $ftpUri = "ftp://$FTP_HOST/$relativePath"
            $request = [System.Net.FtpWebRequest]::Create($ftpUri)
            $request.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
            $request.Credentials = New-Object System.Net.NetworkCredential($FTP_USER, $FTP_PASS)
            $request.UsePassive = $true
            $request.UseBinary = $true
            
            $fileContent = [System.IO.File]::ReadAllBytes($file.FullName)
            $request.ContentLength = $fileContent.Length
            
            $requestStream = $request.GetRequestStream()
            $requestStream.Write($fileContent, 0, $fileContent.Length)
            $requestStream.Close()
            
            $response = $request.GetResponse()
            $response.Close()
            
            Write-Host "  ✓ $relativePath" -ForegroundColor Green
            $uploadCount++
            
        } catch {
            Write-Host "  ✗ $relativePath - $($_.Exception.Message)" -ForegroundColor Red
            $errorCount++
        }
    }
    
    # Summary
    Write-Host "`n=== Deployment Summary ===" -ForegroundColor Cyan
    Write-Host "✓ Files uploaded: $uploadCount" -ForegroundColor Green
    Write-Host "✗ Files failed: $errorCount" -ForegroundColor $(if($errorCount -gt 0){"Red"}else{"Green"})
    Write-Host "🌐 Website: http://11seconds.de" -ForegroundColor Yellow
    Write-Host "📝 Version: $version" -ForegroundColor Gray
    
    if ($errorCount -eq 0) {
        Write-Host "`n🎉 Deployment completed successfully!" -ForegroundColor Green
    } else {
        Write-Host "`n⚠ Deployment completed with $errorCount errors" -ForegroundColor Yellow
    }

} catch {
    Write-Host "`nDeployment failed: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
} finally {
    # Always remove lock
    Remove-DeploymentLock
    Write-Host "`n🔓 Deployment-Lock entfernt" -ForegroundColor Green
    
    # Return to original directory
    if (Get-Location | Where-Object {$_.Path -like "*web"}) {
        Set-Location ".."
    }
}
