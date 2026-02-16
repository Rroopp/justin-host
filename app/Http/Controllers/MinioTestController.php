<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MinioTestController extends Controller
{
    /**
     * Display MinIO test page
     */
    public function index()
    {
        return view('test.minio');
    }

    /**
     * Test MinIO connection
     */
    public function testConnection()
    {
        try {
            // Try to get the disk instance
            $disk = Storage::disk('minio');
            
            // Test if we can list files (even if empty)
            $files = $disk->files();
            
            return response()->json([
                'success' => true,
                'message' => 'MinIO connection successful!',
                'bucket' => config('filesystems.disks.minio.bucket'),
                'endpoint' => config('filesystems.disks.minio.endpoint'),
                'files_count' => count($files)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'MinIO connection failed',
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Test file upload to MinIO
     */
    public function testUpload(Request $request)
    {
        try {
            $disk = Storage::disk('minio');
            
            // Create a test file
            $filename = 'test-' . Str::random(8) . '.txt';
            $content = 'MinIO test file created at ' . now()->toDateTimeString();
            
            // Upload to MinIO
            $path = 'test-uploads/' . $filename;
            $disk->put($path, $content);
            
            // Verify it exists
            $exists = $disk->exists($path);
            
            // Get file URL
            $url = $disk->url($path);
            
            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully!',
                'path' => $path,
                'exists' => $exists,
                'url' => $url,
                'size' => $disk->size($path)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'File upload failed',
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * List all files in MinIO bucket
     */
    public function listFiles()
    {
        try {
            $disk = Storage::disk('minio');
            $files = $disk->allFiles();
            
            $fileDetails = [];
            foreach ($files as $file) {
                $fileDetails[] = [
                    'path' => $file,
                    'size' => $disk->size($file),
                    'last_modified' => $disk->lastModified($file),
                    'url' => $disk->url($file)
                ];
            }
            
            return response()->json([
                'success' => true,
                'count' => count($fileDetails),
                'files' => $fileDetails
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to list files',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test file download from MinIO
     */
    public function testDownload($path)
    {
        try {
            $disk = Storage::disk('minio');
            
            if (!$disk->exists($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found'
                ], 404);
            }
            
            return $disk->download($path);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Download failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete test files
     */
    public function cleanupTestFiles()
    {
        try {
            $disk = Storage::disk('minio');
            $testFiles = $disk->allFiles('test-uploads');
            
            foreach ($testFiles as $file) {
                $disk->delete($file);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Cleaned up ' . count($testFiles) . ' test files'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cleanup failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
