# FTP Test mit Live-Logging in Datei
$logFile = "ftp-test-results.log"
$timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"

function Write-Log($message, $level = "INFO") {
    $logEntry = "[$timestamp] [$level] $message"
    Add-Content -Path $logFile -Value $logEntry
    Write-Host $logEntry
}

# Clear previous log
if (Test-Path $logFile) { Remove-Item $logFile }

Write-Log "=== FTP VERBINDUNGSTEST GESTARTET ===" "INFO"

# Updated credentials
$ftpHost = "ftp.11seconds.de"
$ftpUser = "k302164_11s"  # Updated from hk302164_11s
$ftpPass = "hallo.411S"

Write-Log "Host: $ftpHost" "INFO"
Write-Log "User: $ftpUser" "INFO"

# Test 1: DNS Resolution
Write-Log "Testing DNS resolution..." "INFO"
try {
    $ip = [System.Net.Dns]::GetHostAddresses($ftpHost)[0].IPAddressToString
    Write-Log "DNS OK - IP: $ip" "SUCCESS"
} catch {
    Write-Log "DNS FAILED: $($_.Exception.Message)" "ERROR"
    Write-Log "Possible causes: Wrong hostname, no internet connection" "ERROR"
}

# Test 2: FTP Connection
Write-Log "Testing FTP login..." "INFO"
try {
    $uri = "ftp://$ftpHost/"
    $request = [System.Net.FtpWebRequest]::Create($uri)
    $request.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectory
    $request.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
    $request.Timeout = 15000
    
    $response = $request.GetResponse()
    $stream = $response.GetResponseStream()
    $reader = New-Object System.IO.StreamReader($stream)
    $listing = $reader.ReadToEnd()
    
    $reader.Close()
    $response.Close()
    
    Write-Log "FTP LOGIN SUCCESSFUL!" "SUCCESS"
    Write-Log "Directory listing:" "INFO"
    
    $files = $listing.Split("`n") | Where-Object { $_.Trim() }
    foreach ($file in $files) {
        Write-Log "  $file" "INFO"
    }
    
} catch {
    $error = $_.Exception.Message
    Write-Log "FTP LOGIN FAILED: $error" "ERROR"
    
    # Detailed error analysis
    if ($error -like "*530*") {
        Write-Log "ERROR CODE 530: Login incorrect" "ERROR"
        Write-Log "CHECK: Username '$ftpUser' and password are correct" "ERROR"
        Write-Log "CHECK: FTP access is enabled in Netcup control panel" "ERROR"
    } elseif ($error -like "*timeout*") {
        Write-Log "TIMEOUT ERROR: Connection timed out" "ERROR"
        Write-Log "CHECK: Firewall settings" "ERROR"
        Write-Log "CHECK: Server availability" "ERROR"
    } elseif ($error -like "*421*") {
        Write-Log "ERROR CODE 421: Service not available" "ERROR"
        Write-Log "CHECK: Too many connections, try again later" "ERROR"
    } else {
        Write-Log "UNKNOWN ERROR: $error" "ERROR"
    }
}

Write-Log "=== FTP TEST COMPLETED ===" "INFO"
Write-Log "Check file: $logFile for detailed results" "INFO"
