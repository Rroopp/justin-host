<?php

namespace App\Traits;

use Symfony\Component\HttpFoundation\StreamedResponse;

trait CsvExportable
{
    /**
     * Stream a CSV download.
     *
     * @param string $filename
     * @param array $headers Column headers
     * @param iterable $rows Data rows
     * @param string|null $reportTitle Optional title for the report
     * @return StreamedResponse
     */
    protected function streamCsv(string $filename, array $headers, iterable $rows, ?string $reportTitle = null): StreamedResponse
    {
        // Get company name from settings (mocked for now or fetched if Settings model exists)
        $companyName = config('app.name', 'Justine Medical Limited');
        
        // Try to fetch real company name if settings exist
        if (class_exists(\App\Models\Setting::class)) {
             $setting = \App\Models\Setting::first();
             if ($setting && $setting->company_name) {
                 $companyName = $setting->company_name; // Assuming 'company_name' column
             }
        }

        return response()->streamDownload(function () use ($headers, $rows, $reportTitle, $companyName) {
            $handle = fopen('php://output', 'w');
            
            // Add BOM for Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Company Header
            fputcsv($handle, [$companyName]);
            if ($reportTitle) {
                fputcsv($handle, [$reportTitle]);
            }
            fputcsv($handle, ['Generated: ' . now()->toDateTimeString()]);
            fputcsv($handle, []); // Blank line

            // Column Headers
            fputcsv($handle, $headers);

            // Data Rows
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename);
    }
}
