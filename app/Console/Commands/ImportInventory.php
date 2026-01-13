<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ImportInventory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:import {file : The path to the CSV file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import inventory from a CSV file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Handled called");
        $importService = new \App\Services\InventoryImportService();
        $file = $this->argument('file');
        
        $this->info("Starting import from: $file");

        try {
            $realPath = realpath($file);
            if (!$realPath) {
                $this->error("File does not exist: $file");
                return 1;
            }
            $result = $importService->import($realPath);
            
            $this->info("Import completed successfully!");
            $this->info("Total Rows: " . $result['total']);
            $this->info("Imported: " . $result['success']);
            
            if (!empty($result['errors'])) {
                $this->error("Errors encountered:");
                foreach ($result['errors'] as $error) {
                    $this->error("- $error");
                }
            }

        } catch (\Throwable $e) {
            $this->error("Import failed: " . $e->getMessage());
            $this->error("File: " . $e->getFile());
            $this->error("Line: " . $e->getLine());
            return 1;
        }

        return 0;
    }
}
