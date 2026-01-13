<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Subcategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InventoryImportService
{
    public function import(string $filePath)
    {
        if (!file_exists($filePath)) {
            throw new \Exception("File not found: " . $filePath);
        }

        $file = fopen($filePath, 'r');
        $header = fgetcsv($file); // Assume first row is header
        
        // Normalize headers to lowercase for easier matching
        $header = array_map('strtolower', $header);
        $headerMap = array_flip($header);

        $rowCount = 0;
        $successCount = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            while (($row = fgetcsv($file)) !== false) {
                $rowCount++;
                
                // Extract data using header map
                $productName = $this->getValue($row, $headerMap, ['product name', 'name', 'title']);
                $categoryName = $this->getValue($row, $headerMap, ['category', 'cat']);
                $subcategoryName = $this->getValue($row, $headerMap, ['subcategory', 'sub cat']);
                $sizeRaw = $this->getValue($row, $headerMap, ['size', 'dimensions']);
                $price = $this->getValue($row, $headerMap, ['price', 'buying price', 'cost'], 0);
                $quantity = $this->getValue($row, $headerMap, ['quantity', 'qty', 'stock'], 0);
                $description = $this->getValue($row, $headerMap, ['description', 'desc'], '');

                if (!$productName) {
                    $errors[] = "Row $rowCount: Product Name is required.";
                    continue;
                }

                // 1. Handle Category
                $category = null;
                if ($categoryName) {
                    $category = Category::firstOrCreate(
                        ['name' => $categoryName],
                        ['description' => 'Imported category']
                    );
                }

                // 2. Handle Subcategory
                if ($subcategoryName && $category) {
                    Subcategory::firstOrCreate(
                        [
                            'name' => $subcategoryName, 
                            'category_id' => $category->id
                        ],
                        ['description' => 'Imported subcategory']
                    );
                }

                // 3. Parse Size
                $parsedSize = $this->parseSize($sizeRaw);

                // 4. Create/Update Inventory
                Inventory::updateOrCreate(
                    [
                        'product_name' => $productName,
                        'category' => $categoryName, // Storing name as per schema
                        'subcategory' => $subcategoryName,
                        'size' => $parsedSize['size'],
                        'size_unit' => $parsedSize['unit'],
                    ],
                    [
                        'price' => (float) str_replace(',', '', $price),
                        'quantity_in_stock' => (int) $quantity,
                        'description' => $description,
                        'unit' => 'pcs', // Default
                    ]
                );

                $successCount++;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        } finally {
            fclose($file);
        }

        return [
            'total' => $rowCount,
            'success' => $successCount,
            'errors' => $errors
        ];
    }

    private function getValue($row, $headerMap, $possibleKeys, $default = null)
    {
        foreach ($possibleKeys as $key) {
            if (isset($headerMap[$key]) && isset($row[$headerMap[$key]])) {
                return trim($row[$headerMap[$key]]);
            }
        }
        return $default;
    }

    private function parseSize($rawSize)
    {
        if (empty($rawSize)) {
            return ['size' => null, 'unit' => null];
        }

        $rawSize = trim($rawSize);

        // Check for "10mm", "3.5 mm", etc.
        if (preg_match('/^([\d\.]+)\s*([a-zA-Z]+)$/', $rawSize, $matches)) {
            return [
                'size' => $matches[1],
                'unit' => strtolower($matches[2])
            ];
        }

        // Check for "10 holes"
        if (preg_match('/^(\d+)\s*holes?$/i', $rawSize, $matches)) {
            return [
                'size' => $matches[1],
                'unit' => 'holes'
            ];
        }

        // Check for S, M, L, XL (simple letters)
        if (preg_match('/^[a-zA-Z]+$/', $rawSize)) {
            return [
                'size' => strtoupper($rawSize),
                'unit' => null 
            ];
        }

        // Fallback: return as size, no unit
        return [
            'size' => $rawSize,
            'unit' => null
        ];
    }
}
