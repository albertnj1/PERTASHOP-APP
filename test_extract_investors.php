<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

try {
    $spreadsheet = IOFactory::load('storage/app/temp/last_upload.xlsx');
    if ($spreadsheet->getSheetCount() > 3) {
        $sheet3 = $spreadsheet->getSheet(3);
        $rows3 = $sheet3->toArray();
        $investors = [];
        $isParsingInvestors = false;
        
        foreach ($rows3 as $r3) {
            $col0 = trim(strtolower((string)($r3[0] ?? '')));
            
            if (str_contains($col0, 'pembagian laba bersih')) {
                $isParsingInvestors = true;
                continue;
            }
            
            if ($isParsingInvestors) {
                // If we reach "Catatan", stop
                if (str_contains($col0, 'catatan')) {
                    break;
                }
                
                $name = trim((string)($r3[1] ?? ''));
                $percent = trim((string)($r3[6] ?? ''));
                
                if (!empty($name) && str_contains($percent, '%')) {
                    $name = str_replace('\ *.', '', $name); // Clean up
                    $name = trim($name, " \t\n\r\0\x0B*.");
                    $percentValue = (float) str_replace('%', '', $percent);
                    
                    $investors[] = [
                        'nama' => $name,
                        'persen' => $percentValue
                    ];
                }
            }
        }
        
        print_r($investors);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
