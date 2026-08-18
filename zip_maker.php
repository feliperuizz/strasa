<?php
$zip = new ZipArchive();
$zipFile = __DIR__ . '/strasa_cpanel.zip';
if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
    $dir = new RecursiveDirectoryIterator(__DIR__, RecursiveDirectoryIterator::SKIP_DOTS);
    $iterator = new RecursiveIteratorIterator($dir);
    foreach ($iterator as $file) {
        $path = str_replace(__DIR__ . DIRECTORY_SEPARATOR, '', $file->getPathname());
        
        if (strpos($path, '.git' . DIRECTORY_SEPARATOR) === 0) continue;
        if (strpos($path, 'strasa_cpanel.zip') === 0) continue;
        if (strpos($path, 'zip_maker.php') === 0) continue;
        
        if (!$file->isDir()) {
            $zip->addFile($file->getPathname(), $path);
        }
    }
    $zip->close();
    echo "Zip created successfully: " . $zipFile . "\n";
} else {
    echo "Failed to create zip.\n";
}
