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

                // 3. Parse Size (Try column first, then intelligent extraction from Name)
                $parsedSize = $this->parseSize($sizeRaw, $productName);

                // 4. Intelligent Manufacturer Detection
                $manufacturer = $this->getValue($row, $headerMap, ['manufacturer', 'brand', 'make']);
                if (!$manufacturer) {
                    $manufacturer = $this->detectManufacturer($productName . ' ' . $description);
                }

                // 5. Create/Update Inventory
                Inventory::updateOrCreate(
                    [
                        'product_name' => $productName,
                        'category' => $categoryName ?? 'Uncategorized', // Default if missing
                        'subcategory' => $subcategoryName,
                        'size' => $parsedSize['size'],
                        'size_unit' => $parsedSize['unit'],
                    ],
                    [
                        'price' => (float) str_replace(',', '', $price),
                        'quantity_in_stock' => (int) $quantity,
                        'description' => $description,
                        'manufacturer' => $manufacturer, // Assuming column exists or will be added
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
                $val = trim($row[$headerMap[$key]]);
                if ($val !== '') return $val;
            }
        }
        return $default;
    }

    private function parseSize($rawSize, $productName = '')
    {
        // Try precise size column first
        $parsed = $this->extractSize($rawSize);
        if ($parsed['size']) return $parsed;

        // Fallback: Try to extract from product name
        return $this->extractSize($productName);
    }

    private function extractSize($text)
    {
        if (empty($text)) return ['size' => null, 'unit' => null];
        
        $text = strtolower($text);

        // Unit-based sizes (e.g. 10mm, 5.5kg, 100ml)
        if (preg_match('/(\d+(\.\d+)?)\s*(mm|cm|m|kg|g|mg|ml|l|oz|lb|inch|inches|ft)\b/i', $text, $matches)) {
            return [
                'size' => $matches[1],
                'unit' => strtolower($matches[3])
            ];
        }

        // Numeric sizes with "size" prefix (e.g., "Size 10")
        if (preg_match('/size\s*(\d+(\.\d+)?)/i', $text, $matches)) {
            return ['size' => $matches[1], 'unit' => null];
        }

        // Common Hole sizes (e.g., "10 holes")
        if (preg_match('/(\d+)\s*holes?/i', $text, $matches)) {
            return ['size' => $matches[1], 'unit' => 'holes'];
        }

        // Standard apparel sizes (S, M, L, XL, XXL) - strictly checking isolated words to avoid partial matches
        if (preg_match('/\b(xs|s|m|l|xl|xxl|xxxl)\b/i', $text, $matches)) {
            return ['size' => strtoupper($matches[1]), 'unit' => null];
        }

        return ['size' => null, 'unit' => null];
    }
    
    private function detectManufacturer($text)
    {
        // List of common medical/hospital supply manufacturers could be passed here or queried
        // For now, valid heuristic: Assume first word if capitalized and > 3 chars could be brand? 
        // Or specific list. 
        // Let's look for "Brand: X" pattern or known brands.
        
        $knownBrands = ['J&J', 'Pfizer', '3M', 'Roche', 'Novartis', 'Merck', 'GSK', 'Sanofi', 'Abbott', 'Bayer', 'Stryker'];
        
        foreach ($knownBrands as $brand) {
            if (stripos($text, $brand) !== false) {
                return $brand;
            }
        }
        
        return null;
    }
}
