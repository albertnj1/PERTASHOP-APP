<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $user = \App\Models\User::where('email', 'super-admin@pertashop.com')->first();
    auth()->login($user);

    $controller = new \App\Http\Controllers\DashboardController();
    $request = \Illuminate\Http\Request::create('/', 'GET', ['ajax' => 'true']);
    $request->headers->set('X-Requested-With', 'XMLHttpRequest'); // <--- FIX
    $request->setUserResolver(function () use ($user) {
        return $user;
    });

    $response = $controller->index($request);
    echo "Response OK: \n";
    echo $response->getContent();
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " on line " . $e->getLine() . "\n";
}
