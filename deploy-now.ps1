Write-Host "🤖 AI FTP Deployment Starting..." -ForegroundColor Magenta

# New Netcup FTP credentials
$host = "ftp.11seconds.de"
$user = "hk302164_11s"
$pass = "hallo.411S"

Write-Host "📡 Host: $host" -ForegroundColor Cyan
Write-Host "👤 User: $user" -ForegroundColor Cyan

# Test connection
Write-Host "`n🔄 Testing FTP connection..." -ForegroundColor Yellow
try {
    $uri = "ftp://$host/"
    $req = [System.Net.FtpWebRequest]::Create($uri)
    $req.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectory
    $req.Credentials = New-Object System.Net.NetworkCredential($user, $pass)
    $resp = $req.GetResponse()
    $resp.Close()
    Write-Host "✅ Connection successful!" -ForegroundColor Green
} catch {
    Write-Host "❌ Connection failed: $($_.Exception.Message)" -ForegroundColor Red
    exit
}

# Upload function
function Upload($local, $remote) {
    try {
        if (Test-Path $local) {
            $uri = "ftp://$host/$remote"
            $req = [System.Net.FtpWebRequest]::Create($uri)
            $req.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
            $req.Credentials = New-Object System.Net.NetworkCredential($user, $pass)
            
            $bytes = [System.IO.File]::ReadAllBytes($local)
            $req.ContentLength = $bytes.Length
            $stream = $req.GetRequestStream()
            $stream.Write($bytes, 0, $bytes.Length)
            $stream.Close()
            $resp = $req.GetResponse()
            $resp.Close()
            
            Write-Host "  ✅ $remote" -ForegroundColor Green
            return $true
        } else {
            Write-Host "  ❌ File not found: $local" -ForegroundColor Red
            return $false
        }
    } catch {
        Write-Host "  ❌ Upload failed: $remote - $($_.Exception.Message)" -ForegroundColor Red
        return $false
    }
}

# Create directory function
function CreateDir($path) {
    try {
        $uri = "ftp://$host/$path"
        $req = [System.Net.FtpWebRequest]::Create($uri)
        $req.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
        $req.Credentials = New-Object System.Net.NetworkCredential($user, $pass)
        $resp = $req.GetResponse()
        $resp.Close()
    } catch {
        # Directory might exist
    }
}

Write-Host "`n📁 Creating directories..." -ForegroundColor Yellow
CreateDir("api")
CreateDir("api/middleware")
CreateDir("api/routes")
CreateDir("httpdocs")

Write-Host "`n📤 Uploading files..." -ForegroundColor Yellow
$success = 0
$total = 0

$deployDir = ".\deploy-netcup-auto"

# Upload files
$total++; if (Upload "$deployDir\app.js" "app.js") { $success++ }
$total++; if (Upload "$deployDir\package.json" "package.json") { $success++ }
$total++; if (Upload "$deployDir\.env" ".env") { $success++ }
$total++; if (Upload "$deployDir\api\db-switcher.js" "api/db-switcher.js") { $success++ }
$total++; if (Upload "$deployDir\api\middleware\auth.js" "api/middleware/auth.js") { $success++ }
$total++; if (Upload "$deployDir\api\routes\auth.js" "api/routes/auth.js") { $success++ }
$total++; if (Upload "$deployDir\api\routes\game.js" "api/routes/game.js") { $success++ }
$total++; if (Upload "$deployDir\httpdocs\index.html" "httpdocs/index.html") { $success++ }

Write-Host "`n📊 DEPLOYMENT SUMMARY" -ForegroundColor Green
Write-Host "✅ Success: $success/$total files" -ForegroundColor Green

if ($success -eq $total) {
    Write-Host "`n🎉 DEPLOYMENT SUCCESSFUL!" -ForegroundColor Green
    Write-Host "🌐 App available at: https://11seconds.de:3011" -ForegroundColor Cyan
} else {
    Write-Host "`n⚠️ Some files failed to upload" -ForegroundColor Yellow
}
