<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    // Fake login as super-admin
    $user = \App\Models\User::where('email', 'super-admin@pertashop.com')->first();
    auth()->login($user);

    $controller = new \App\Http\Controllers\DashboardController();
    $request = \Illuminate\Http\Request::create('/', 'GET', ['ajax' => 'true']);
    $request->setUserResolver(function () use ($user) {
        return $user;
    });

    $response = $controller->index($request);
    echo "Response OK: \n";
    echo substr(json_encode($response->getData()), 0, 500);
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " on line " . $e->getLine() . "\n";
}
