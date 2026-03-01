<?php

namespace App\Services;

use SplFileObject;

class CsvInboundProcessor
{
    public function processFile(string $filePath)
    {
        $file = new SplFileObject($filePath, 'r');
        $file->setFlags(SplFileObject::READ_CSV);
        
        $childColumnIndex = null;
        $results = [];
        
        foreach ($file as $lineNumber => $row) {
            if ($lineNumber === 0) {
                $childColumnIndex = array_search('Child', $row, true);
                continue;
            }
            
            if ($childColumnIndex !== null && isset($row[$childColumnIndex]) && str_contains($row[$childColumnIndex], 'gtin')) {
                // Split by ':' and get last index
                $parts = explode(':', $row[$childColumnIndex]);
                $lastPart = end($parts);
                
                // Split by '.' and get last value
                $dotParts = explode('.', $lastPart);
                
                // Remove first character from $dotParts[1]
                $secondPart = isset($dotParts[1]) ? substr($dotParts[1], 1) : '';
                
                $results[] =  "0" . ($dotParts[0] ?? '') . $secondPart;
            }
        }
        
        return $results;
    }
}
