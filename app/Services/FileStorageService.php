<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class FileStorageService
{
    /**
     * Upload an invoice PDF
     */
    public function uploadInvoice(UploadedFile $file, string $invoiceNumber): string
    {
        $filename = Str::slug($invoiceNumber) . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path = 'invoices/' . date('Y/m') . '/' . $filename;
        
        Storage::disk('minio')->put($path, file_get_contents($file));
        
        return $path;
    }

    /**
     * Upload a receipt image/PDF
     */
    public function uploadReceipt(UploadedFile $file, int $saleId): string
    {
        $filename = 'receipt_' . $saleId . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path = 'receipts/' . date('Y/m') . '/' . $filename;
        
        Storage::disk('minio')->put($path, file_get_contents($file));
        
        return $path;
    }

    /**
     * Upload a product image
     */
    public function uploadProductImage(UploadedFile $file, int $productId): string
    {
        $filename = 'product_' . $productId . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path = 'products/' . $filename;
        
        Storage::disk('minio')->put($path, file_get_contents($file));
        
        return $path;
    }

    /**
     * Upload a generic document
     */
    public function uploadDocument(UploadedFile $file, string $category = 'general'): string
    {
        $filename = Str::slug($file->getClientOriginalName(), '_') . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path = 'documents/' . $category . '/' . date('Y/m') . '/' . $filename;
        
        Storage::disk('minio')->put($path, file_get_contents($file));
        
        return $path;
    }

    /**
     * Get public URL for a file
     */
    public function getUrl(string $path): string
    {
        return Storage::disk('minio')->url($path);
    }

    /**
     * Get temporary URL (signed, expires in 1 hour)
     */
    public function getTemporaryUrl(string $path, int $expiresInMinutes = 60): string
    {
        return Storage::disk('minio')->temporaryUrl($path, now()->addMinutes($expiresInMinutes));
    }

    /**
     * Delete a file
     */
    public function delete(string $path): bool
    {
        return Storage::disk('minio')->delete($path);
    }

    /**
     * Check if file exists
     */
    public function exists(string $path): bool
    {
        return Storage::disk('minio')->exists($path);
    }

    /**
     * Get file size in bytes
     */
    public function size(string $path): int
    {
        return Storage::disk('minio')->size($path);
    }
}
