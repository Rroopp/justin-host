<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MinIO Connection Test</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 800px;
            width: 100%;
            padding: 40px;
        }

        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .config-info {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-bottom: 30px;
            border-radius: 5px;
        }

        .config-info h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .config-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .config-item:last-child {
            border-bottom: none;
        }

        .config-label {
            font-weight: 600;
            color: #555;
        }

        .config-value {
            color: #667eea;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }

        .test-section {
            margin-bottom: 25px;
        }

        .test-section h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 18px;
        }

        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-right: 10px;
            margin-bottom: 10px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn-danger {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .result {
            margin-top: 15px;
            padding: 15px;
            border-radius: 8px;
            display: none;
        }

        .result.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .result.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .result pre {
            margin-top: 10px;
            background: rgba(0, 0, 0, 0.05);
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 12px;
        }

        .files-list {
            max-height: 300px;
            overflow-y: auto;
            margin-top: 15px;
        }

        .file-item {
            background: #f8f9fa;
            padding: 12px;
            margin-bottom: 8px;
            border-radius: 5px;
            border-left: 3px solid #667eea;
        }

        .file-path {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .file-meta {
            font-size: 12px;
            color: #666;
        }

        .loading {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.8s linear infinite;
            margin-left: 8px;
            vertical-align: middle;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗄️ MinIO Connection Test</h1>
        <p class="subtitle">Hospital POS System - Document Storage Testing</p>

        <div class="config-info">
            <h3>Current Configuration</h3>
            <div class="config-item">
                <span class="config-label">Endpoint:</span>
                <span class="config-value">{{ config('filesystems.disks.minio.endpoint', 'Not configured') }}</span>
            </div>
            <div class="config-item">
                <span class="config-label">Bucket:</span>
                <span class="config-value">{{ config('filesystems.disks.minio.bucket', 'Not configured') }}</span>
            </div>
            <div class="config-item">
                <span class="config-label">Region:</span>
                <span class="config-value">{{ config('filesystems.disks.minio.region', 'Not configured') }}</span>
            </div>
            <div class="config-item">
                <span class="config-label">Access Key:</span>
                <span class="config-value">{{ Str::mask(config('filesystems.disks.minio.key', 'Not configured'), '*', 3) }}</span>
            </div>
        </div>

        <div class="test-section">
            <h3>Connection Test</h3>
            <button class="btn" onclick="testConnection()">
                Test Connection
            </button>
            <div id="connection-result" class="result"></div>
        </div>

        <div class="test-section">
            <h3>File Upload Test</h3>
            <button class="btn" onclick="testUpload()">
                Upload Test File
            </button>
            <div id="upload-result" class="result"></div>
        </div>

        <div class="test-section">
            <h3>List Files</h3>
            <button class="btn" onclick="listFiles()">
                List All Files
            </button>
            <button class="btn btn-danger" onclick="cleanupTestFiles()">
                Cleanup Test Files
            </button>
            <div id="files-result" class="result"></div>
        </div>
    </div>

    <script>
        function showResult(elementId, success, message, data = null) {
            const element = document.getElementById(elementId);
            element.className = 'result ' + (success ? 'success' : 'error');
            element.style.display = 'block';
            
            let html = '<strong>' + (success ? '✅ Success' : '❌ Error') + '</strong><br>' + message;
            
            if (data) {
                html += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
            }
            
            element.innerHTML = html;
        }

        function showLoading(button) {
            button.disabled = true;
            button.innerHTML += '<span class="loading"></span>';
        }

        function hideLoading(button, originalText) {
            button.disabled = false;
            button.innerHTML = originalText;
        }

        async function testConnection() {
            const btn = event.target;
            const originalText = btn.innerHTML;
            showLoading(btn);

            try {
                const response = await fetch('/test/minio/connection');
                const data = await response.json();
                
                if (data.success) {
                    showResult('connection-result', true, data.message, {
                        bucket: data.bucket,
                        endpoint: data.endpoint,
                        files_count: data.files_count
                    });
                } else {
                    showResult('connection-result', false, data.message, data.error);
                }
            } catch (error) {
                showResult('connection-result', false, 'Request failed: ' + error.message);
            } finally {
                hideLoading(btn, originalText);
            }
        }

        async function testUpload() {
            const btn = event.target;
            const originalText = btn.innerHTML;
            showLoading(btn);

            try {
                const response = await fetch('/test/minio/upload', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                
                if (data.success) {
                    showResult('upload-result', true, data.message, {
                        path: data.path,
                        size: data.size + ' bytes',
                        url: data.url
                    });
                } else {
                    showResult('upload-result', false, data.message, data.error);
                }
            } catch (error) {
                showResult('upload-result', false, 'Request failed: ' + error.message);
            } finally {
                hideLoading(btn, originalText);
            }
        }

        async function listFiles() {
            const btn = event.target;
            const originalText = btn.innerHTML;
            showLoading(btn);

            try {
                const response = await fetch('/test/minio/files');
                const data = await response.json();
                
                if (data.success) {
                    let html = '<strong>✅ Success</strong><br>Found ' + data.count + ' files';
                    
                    if (data.files.length > 0) {
                        html += '<div class="files-list">';
                        data.files.forEach(file => {
                            const date = new Date(file.last_modified * 1000).toLocaleString();
                            html += `
                                <div class="file-item">
                                    <div class="file-path">${file.path}</div>
                                    <div class="file-meta">
                                        Size: ${file.size} bytes | Modified: ${date}
                                    </div>
                                </div>
                            `;
                        });
                        html += '</div>';
                    }
                    
                    document.getElementById('files-result').className = 'result success';
                    document.getElementById('files-result').style.display = 'block';
                    document.getElementById('files-result').innerHTML = html;
                } else {
                    showResult('files-result', false, data.message, data.error);
                }
            } catch (error) {
                showResult('files-result', false, 'Request failed: ' + error.message);
            } finally {
                hideLoading(btn, originalText);
            }
        }

        async function cleanupTestFiles() {
            if (!confirm('Delete all test files?')) return;

            const btn = event.target;
            const originalText = btn.innerHTML;
            showLoading(btn);

            try {
                const response = await fetch('/test/minio/cleanup', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                
                if (data.success) {
                    showResult('files-result', true, data.message);
                } else {
                    showResult('files-result', false, data.message, data.error);
                }
            } catch (error) {
                showResult('files-result', false, 'Request failed: ' + error.message);
            } finally {
                hideLoading(btn, originalText);
            }
        }
    </script>
</body>
</html>
