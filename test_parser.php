<?php
    function parseFlexibleNumber($val) {
        if (empty($val)) return 0;
        if (is_numeric($val)) return floatval($val);
        $val = trim($val);
        $val = str_replace(" ", "", $val);
        
        $lastComma = strrpos($val, ",");
        $lastDot = strrpos($val, ".");
        
        if ($lastComma !== false && $lastDot !== false) {
            if ($lastComma > $lastDot) {
                // Comma is decimal
                $val = str_replace(".", "", $val);
                $val = str_replace(",", ".", $val);
            } else {
                // Dot is decimal
                $val = str_replace(",", "", $val);
            }
        } elseif ($lastComma !== false) {
            $parts = explode(",", $val);
            if (strlen(end($parts)) !== 3) {
                $val = str_replace(",", ".", $val);
            } else {
                $val = str_replace(",", "", $val);
            }
        } elseif ($lastDot !== false) {
            $parts = explode(".", $val);
            if (strlen(end($parts)) === 3 && count($parts) > 1) {
                $val = str_replace(".", "", $val);
            } else {
                // Otherwise it is decimal (e.g. 67.5)
            }
        }
        
        return floatval($val);
    }

echo parseFlexibleNumber("67.000.000") . "\n";
echo parseFlexibleNumber("24.000") . "\n";
echo parseFlexibleNumber("780.000") . "\n";
echo parseFlexibleNumber("11.376,29") . "\n";
echo parseFlexibleNumber("11,376.29") . "\n";
