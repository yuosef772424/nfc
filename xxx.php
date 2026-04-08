<?php
/**
 * Script to add 'array $with = []' parameter to methods that contain '->with($with)'
 * but lack the parameter in their signature.
 * Uses brace counting for accurate method body detection.
 */

$projectRoot = __DIR__;
$directories = [
    $projectRoot . '/app/Repositories',
    $projectRoot . '/app/Contracts',
];

$totalFiles = 0;
$totalMethodsFixed = 0;

function addMissingWithParameterInFile(string $filePath): bool {
    global $totalMethodsFixed;
    $code = file_get_contents($filePath);
    $tokens = token_get_all($code);
    $newCode = '';
    $i = 0;
    $len = count($tokens);
    $modified = false;

    while ($i < $len) {
        $token = $tokens[$i];
        if (is_array($token) && $token[0] === T_PUBLIC) {
            // Found public keyword
            $startPos = $i;
            // Look for function keyword
            $i++;
            while ($i < $len && !(is_array($tokens[$i]) && $tokens[$i][0] === T_FUNCTION)) {
                $i++;
            }
            if ($i >= $len) break;
            $i++; // skip function
            // Skip whitespace
            while ($i < $len && (is_array($tokens[$i]) && $tokens[$i][0] === T_WHITESPACE)) {
                $i++;
            }
            // Get function name
            if ($i < $len && is_array($tokens[$i]) && $tokens[$i][0] === T_STRING) {
                $funcName = $tokens[$i][1];
                $i++;
                // Skip whitespace before '('
                while ($i < $len && (is_array($tokens[$i]) && $tokens[$i][0] === T_WHITESPACE)) {
                    $i++;
                }
                // Now at '('
                if ($i < $len && $tokens[$i] === '(') {
                    $paramStart = $i;
                    $i++;
                    $paramContent = '';
                    $parenLevel = 1;
                    while ($i < $len && $parenLevel > 0) {
                        if ($tokens[$i] === '(') $parenLevel++;
                        elseif ($tokens[$i] === ')') $parenLevel--;
                        if ($parenLevel > 0) {
                            $paramContent .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
                        }
                        $i++;
                    }
                    // Now $i points to after ')'
                    // Now find the opening brace of the method
                    while ($i < $len && (is_array($tokens[$i]) && ($tokens[$i][0] === T_WHITESPACE))) {
                        $i++;
                    }
                    if ($i < $len && $tokens[$i] === '{') {
                        $braceStart = $i;
                        $braceLevel = 1;
                        $i++;
                        $body = '';
                        while ($i < $len && $braceLevel > 0) {
                            if ($tokens[$i] === '{') $braceLevel++;
                            elseif ($tokens[$i] === '}') $braceLevel--;
                            if ($braceLevel > 0) {
                                $body .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
                            }
                            $i++;
                        }
                        $braceEnd = $i; // points to '}'
                        // Check if body contains ->with($with)
                        if (strpos($body, '->with($with)') !== false) {
                            // Check if parameter list already contains '$with'
                            if (strpos($paramContent, '$with') === false) {
                                // Add , array $with = [] to parameters
                                if (trim($paramContent) === '') {
                                    $newParamContent = 'array $with = []';
                                } else {
                                    $newParamContent = $paramContent . ', array $with = []';
                                }
                                // Replace in original code segment
                                $originalSegment = '';
                                for ($j = $startPos; $j <= $braceEnd; $j++) {
                                    $originalSegment .= is_array($tokens[$j]) ? $tokens[$j][1] : $tokens[$j];
                                }
                                $newSegment = substr($originalSegment, 0, $paramStart - $startPos) . '(' . $newParamContent . ')' . substr($originalSegment, $paramStart - $startPos + strlen($paramContent) + 2);
                                $code = substr_replace($code, $newSegment, $startPos, $braceEnd - $startPos + 1);
                                $modified = true;
                                $totalMethodsFixed++;
                                echo "  - Fixed: $funcName in " . basename($filePath) . "\n";
                                // Adjust token array length and reset parsing because code changed
                                $tokens = token_get_all($code);
                                $len = count($tokens);
                                $i = $startPos + strlen($newSegment); // continue after fixed method
                                continue;
                            }
                        }
                    }
                }
            }
        }
        $i++;
    }

    if ($modified) {
        file_put_contents($filePath, $code);
        return true;
    }
    return false;
}

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        echo "Directory not found: $dir\n";
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->getExtension() !== 'php') continue;

        $path = $file->getRealPath();
        echo "Processing: " . $path . "\n";
        if (addMissingWithParameterInFile($path)) {
            echo "  => Updated\n";
            $totalFiles++;
        } else {
            echo "  => No changes\n";
        }
    }
}

echo "\n=== Done ===\n";
echo "Files modified: $totalFiles\n";
echo "Methods fixed: $totalMethodsFixed\n";
echo "Please review changes. No backup created automatically; please ensure you have version control.\n";