<?php

namespace App\Http\Controllers;

use App\Services\InventoryImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InventoryImportController extends Controller
{
    protected $importService;

    public function __construct(InventoryImportService $importService)
    {
        $this->importService = $importService;
    }

    public function __invoke(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt'
        ]);

        $file = $request->file('file');
        
        try {
            // Save to temp or read directly
            $path = $file->getRealPath();
            
            $result = $this->importService->import($path);

            return response()->json([
                'message' => 'Import executed.',
                'details' => $result
            ]);

        } catch (\Exception $e) {
            Log::error("Import failed: " . $e->getMessage());
            return response()->json([
                'message' => 'Import failed.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
