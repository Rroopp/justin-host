# MinIO Setup Commands

# 1. Start MinIO with Docker Compose
docker-compose -f docker-compose.minio.yml up -d

# 2. Check MinIO is running
docker ps | findstr minio

# 3. View MinIO logs
docker logs hospital-pos-minio

# 4. Access MinIO Console
# Open browser: http://localhost:9001
# Login: admin / StrongPass123!

# 5. After creating access keys in console, update .env:
# MINIO_ENDPOINT=http://127.0.0.1:9000
# MINIO_ACCESS_KEY_ID=<your-access-key>
# MINIO_SECRET_ACCESS_KEY=<your-secret-key>
# MINIO_BUCKET=hospital-pos
# MINIO_REGION=us-east-1
# MINIO_USE_PATH_STYLE_ENDPOINT=true

# 6. Clear Laravel cache
php artisan config:clear
php artisan cache:clear

# 7. Test connection
# Visit: http://localhost:8000/test/minio

# 8. Stop MinIO
docker-compose -f docker-compose.minio.yml down

# 9. Stop and remove data (CAUTION: Deletes all files)
docker-compose -f docker-compose.minio.yml down -v
