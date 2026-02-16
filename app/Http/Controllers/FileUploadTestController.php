<?php

namespace App\Http\Controllers;

use App\Services\FileStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileUploadTestController extends Controller
{
    protected $fileStorage;

    public function __construct(FileStorageService $fileStorage)
    {
        $this->fileStorage = $fileStorage;
    }

    /**
     * Show the file upload test page
     */
    public function index()
    {
        return view('test.file-upload');
    }

    /**
     * Test file upload to MinIO
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'type' => 'required|in:invoice,receipt,product,document'
        ]);

        try {
            $file = $request->file('file');
            $type = $request->input('type');

            // Upload based on type
            switch ($type) {
                case 'invoice':
                    $path = $this->fileStorage->uploadInvoice($file, 'TEST-INV-' . time());
                    break;
                case 'receipt':
                    $path = $this->fileStorage->uploadReceipt($file, rand(1000, 9999));
                    break;
                case 'product':
                    $path = $this->fileStorage->uploadProductImage($file, rand(1, 100));
                    break;
                default:
                    $path = $this->fileStorage->uploadDocument($file, 'test');
            }

            // Get URL
            $url = $this->fileStorage->getUrl($path);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'path' => $path,
                'url' => $url,
                'size' => $this->fileStorage->size($path),
                'exists' => $this->fileStorage->exists($path)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test connection to MinIO
     */
    public function testConnection()
    {
        try {
            // Try to list files in the bucket
            $files = Storage::disk('minio')->files();
            
            return response()->json([
                'success' => true,
                'message' => 'MinIO connection successful',
                'bucket' => config('filesystems.disks.minio.bucket'),
                'endpoint' => config('filesystems.disks.minio.endpoint'),
                'files_count' => count($files)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
                'config' => [
                    'bucket' => config('filesystems.disks.minio.bucket'),
                    'endpoint' => config('filesystems.disks.minio.endpoint'),
                    'region' => config('filesystems.disks.minio.region')
                ]
            ], 500);
        }
    }
}
