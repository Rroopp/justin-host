# MinIO Docker Startup Script
# Run this after Docker Desktop is started

Write-Host "🐳 Starting MinIO Container..." -ForegroundColor Cyan
Write-Host ""

# Check if MinIO container already exists
$existingContainer = docker ps -a --filter "name=minio" --format "{{.Names}}"

if ($existingContainer -eq "minio") {
    Write-Host "⚠️  MinIO container already exists. Starting it..." -ForegroundColor Yellow
    docker start minio
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ MinIO container started successfully!" -ForegroundColor Green
    } else {
        Write-Host "❌ Failed to start existing container. Removing and recreating..." -ForegroundColor Red
        docker rm -f minio
        $existingContainer = $null
    }
}

if (-not $existingContainer) {
    Write-Host "📦 Creating new MinIO container..." -ForegroundColor Cyan
    
    docker run -d `
        --name minio `
        -p 9000:9000 `
        -p 9001:9001 `
        -e MINIO_ROOT_USER=admin `
        -e MINIO_ROOT_PASSWORD=StrongPass123 `
        -v minio-data:/data `
        minio/minio server /data --console-address ":9001"
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ MinIO container created and started successfully!" -ForegroundColor Green
    } else {
        Write-Host "❌ Failed to create MinIO container" -ForegroundColor Red
        exit 1
    }
}

Write-Host ""
Write-Host "📊 Container Status:" -ForegroundColor Cyan
docker ps --filter "name=minio" --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"

Write-Host ""
Write-Host "🌐 Access MinIO Console:" -ForegroundColor Green
Write-Host "   URL:      http://localhost:9001" -ForegroundColor White
Write-Host "   Username: admin" -ForegroundColor White
Write-Host "   Password: StrongPass123" -ForegroundColor White
Write-Host ""
Write-Host "🔧 S3 API Endpoint:" -ForegroundColor Green
Write-Host "   URL: http://localhost:9000" -ForegroundColor White
Write-Host ""
Write-Host "📝 Next Steps:" -ForegroundColor Yellow
Write-Host "   1. Open http://localhost:9001 in your browser" -ForegroundColor White
Write-Host "   2. Login with admin/StrongPass123" -ForegroundColor White
Write-Host "   3. Go to Settings → Subnet → Register" -ForegroundColor White
Write-Host "   4. Paste your JWT token to activate enterprise features" -ForegroundColor White
Write-Host "   5. Go to Access Keys → Create Access Key" -ForegroundColor White
Write-Host "   6. Copy the credentials to your .env file" -ForegroundColor White
Write-Host "   7. Create bucket: hospital-pos with versioning enabled" -ForegroundColor White
Write-Host ""
