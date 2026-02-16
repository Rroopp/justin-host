@extends('layouts.app')

@section('content')
<div class="px-4 py-6 sm:px-0">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">MinIO Storage Test</h1>

        <!-- Connection Test -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Connection Test</h2>
            <button 
                onclick="testConnection()" 
                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                Test MinIO Connection
            </button>
            <div id="connectionResult" class="mt-4"></div>
        </div>

        <!-- File Upload Test -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-6">
            <h2 class="text-lg font-semibold mb-4">File Upload Test</h2>
            
            <form id="uploadForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Upload Type</label>
                    <select name="type" class="w-full border border-gray-300 rounded px-3 py-2">
                        <option value="invoice">Invoice</option>
                        <option value="receipt">Receipt</option>
                        <option value="product">Product Image</option>
                        <option value="document">Document</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select File</label>
                    <input 
                        type="file" 
                        name="file" 
                        class="w-full border border-gray-300 rounded px-3 py-2"
                        required>
                </div>

                <button 
                    type="submit" 
                    class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                    Upload File
                </button>
            </form>

            <div id="uploadResult" class="mt-4"></div>
        </div>
    </div>
</div>

<script>
async function testConnection() {
    const resultDiv = document.getElementById('connectionResult');
    resultDiv.innerHTML = '<p class="text-gray-600">Testing connection...</p>';

    try {
        const response = await fetch('/test/minio/connection');
        const data = await response.json();

        if (data.success) {
            resultDiv.innerHTML = `
                <div class="p-4 bg-green-50 border border-green-200 rounded">
                    <p class="text-green-800 font-semibold">✓ ${data.message}</p>
                    <p class="text-sm text-green-700 mt-2">Bucket: ${data.bucket}</p>
                    <p class="text-sm text-green-700">Endpoint: ${data.endpoint}</p>
                    <p class="text-sm text-green-700">Files in bucket: ${data.files_count}</p>
                </div>
            `;
        } else {
            resultDiv.innerHTML = `
                <div class="p-4 bg-red-50 border border-red-200 rounded">
                    <p class="text-red-800 font-semibold">✗ ${data.message}</p>
                    <pre class="text-xs text-red-700 mt-2">${JSON.stringify(data.config, null, 2)}</pre>
                </div>
            `;
        }
    } catch (error) {
        resultDiv.innerHTML = `
            <div class="p-4 bg-red-50 border border-red-200 rounded">
                <p class="text-red-800">Error: ${error.message}</p>
            </div>
        `;
    }
}

document.getElementById('uploadForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const resultDiv = document.getElementById('uploadResult');
    const formData = new FormData(e.target);
    
    resultDiv.innerHTML = '<p class="text-gray-600">Uploading...</p>';

    try {
        const response = await fetch('/test/minio/upload', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        const data = await response.json();

        if (data.success) {
            resultDiv.innerHTML = `
                <div class="p-4 bg-green-50 border border-green-200 rounded">
                    <p class="text-green-800 font-semibold">✓ ${data.message}</p>
                    <p class="text-sm text-green-700 mt-2">Path: ${data.path}</p>
                    <p class="text-sm text-green-700">Size: ${data.size} bytes</p>
                    <p class="text-sm text-green-700">URL: <a href="${data.url}" target="_blank" class="underline">${data.url}</a></p>
                </div>
            `;
        } else {
            resultDiv.innerHTML = `
                <div class="p-4 bg-red-50 border border-red-200 rounded">
                    <p class="text-red-800">✗ ${data.message}</p>
                </div>
            `;
        }
    } catch (error) {
        resultDiv.innerHTML = `
            <div class="p-4 bg-red-50 border border-red-200 rounded">
                <p class="text-red-800">Error: ${error.message}</p>
            </div>
        `;
    }
});
</script>
@endsection
