#!/usr/bin/env php
<?php

/**
 * Скрипт для удаления избыточных комментариев //end и @inheritDoc
 */

function removeRedundantComments(string $directory): void
{
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    $phpFiles = new RegexIterator($iterator, '/\.php$/');
    
    foreach ($phpFiles as $file) {
        $filePath = $file->getPathname();
        $content = file_get_contents($filePath);
        
        // Удаляем все комментарии //end ...
        $content = preg_replace('/\s*\/\/end\s+[^\/\n]*$/m', '', $content);
        
        // Удаляем @inheritDoc комментарии
        $content = preg_replace('/\s*\/\*\*\s*\*\s*@inheritDoc\s*\*\/\s*\n/m', '', $content);
        
        // Удаляем пустые PHPDoc блоки только с @inheritDoc
        $content = preg_replace('/\s*\/\*\*\s*\*\s*@inheritDoc\s*\*\/\s*/m', '', $content);
        
        // Удаляем множественные пустые строки (заменяем на максимум 2)
        $content = preg_replace('/\n{3,}/', "\n\n", $content);
        
        // Удаляем пустые строки в конце файла
        $content = rtrim($content) . "\n";
        
        file_put_contents($filePath, $content);
        echo "Processed: $filePath\n";
    }
}

// Обрабатываем директорию src
echo "Удаляем избыточные комментарии из src/...\n";
removeRedundantComments(__DIR__ . '/../src');

echo "Готово!\n"; 