<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$req = Illuminate\Http\Request::create('/tasks/1', 'GET');
$req->headers->set('X-Requested-With', 'XMLHttpRequest');

try {
    $response = $kernel->handle($req);
    echo "STATUS CODE: " . $response->getStatusCode() . "\n";
    if ($response->getStatusCode() == 500) {
        echo "Exception Output:\n";
        echo strip_tags($response->getContent());
    }
} catch (\Throwable $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
