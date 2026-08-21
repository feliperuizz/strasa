<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$disk = config('filesystems.attachments_disk');
echo "Disk: " . $disk . "\n";
$file = \Illuminate\Http\UploadedFile::fake()->image('avatar.jpg');
try {
    $result = $file->storeAs('test', 'avatar.jpg', $disk);
    echo 'Result: ' . ($result ? $result : 'false') . "\n";
} catch (\Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
