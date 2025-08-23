# Test both URLs
Write-Host "=== TESTING BOTH POSSIBLE URLS ===" -ForegroundColor Yellow

$verificationFile = "build-verification-20250823-140525-c02ddfe7.json"

# Test 1: Root level
Write-Host "Testing ROOT level: http://11seconds.de/$verificationFile" -ForegroundColor Blue
try {
    $response1 = Invoke-WebRequest -Uri "http://11seconds.de/$verificationFile" -TimeoutSec 10
    Write-Host "ROOT LEVEL - SUCCESS!" -ForegroundColor Green
    Write-Host "Content: $($response1.Content)" -ForegroundColor Green
} catch {
    Write-Host "ROOT LEVEL - FAILED: $($_.Exception.Message)" -ForegroundColor Red
}

# Test 2: httpdocs subfolder
Write-Host "`nTesting HTTPDOCS level: http://11seconds.de/httpdocs/$verificationFile" -ForegroundColor Blue
try {
    $response2 = Invoke-WebRequest -Uri "http://11seconds.de/httpdocs/$verificationFile" -TimeoutSec 10
    Write-Host "HTTPDOCS LEVEL - SUCCESS!" -ForegroundColor Green
    Write-Host "Content: $($response2.Content)" -ForegroundColor Green
} catch {
    Write-Host "HTTPDOCS LEVEL - FAILED: $($_.Exception.Message)" -ForegroundColor Red
}

# Test 3: Main website
Write-Host "`nTesting main website: http://11seconds.de/" -ForegroundColor Blue
try {
    $response3 = Invoke-WebRequest -Uri "http://11seconds.de/" -TimeoutSec 10
    Write-Host "MAIN WEBSITE - SUCCESS!" -ForegroundColor Green
    $title = ($response3.Content | Select-String '<title>(.*?)</title>').Matches[0].Groups[1].Value
    Write-Host "Page Title: $title" -ForegroundColor Gray
} catch {
    Write-Host "MAIN WEBSITE - FAILED: $($_.Exception.Message)" -ForegroundColor Red
}
