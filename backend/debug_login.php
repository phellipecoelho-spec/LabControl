<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$u = App\Models\User::where('email', 'admin@labcontrol.com')->first();
if (!$u) {
    echo "ADMIN NOT FOUND\n";
    echo "Users: " . App\Models\User::count() . "\n";
    exit(1);
}

echo "User: {$u->name}\n";
echo "Email: {$u->email}\n";
echo "Active: " . ($u->is_active ? 'yes' : 'no') . "\n";

$check = Illuminate\Support\Facades\Hash::check('@dmin123', $u->password);
echo "Hash check (@dmin123): " . ($check ? 'MATCH' : 'MISMATCH') . "\n";

$check2 = Illuminate\Support\Facades\Hash::check('dmin123', $u->password);
echo "Hash check (dmin123): " . ($check2 ? 'MATCH' : 'MISMATCH') . "\n";

echo "Password hash: {$u->password}\n";

// Test Auth::attempt
echo "\n--- Auth::attempt test ---\n";
try {
    $result = Illuminate\Support\Facades\Auth::attempt([
        'email' => 'admin@labcontrol.com',
        'password' => '@dmin123'
    ]);
    echo "Auth::attempt result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
    $user = Illuminate\Support\Facades\Auth::user();
    echo "Auth::user after attempt: " . ($user ? $user->name : 'null') . "\n";
} catch (\Exception $e) {
    echo "Auth exception: " . $e->getMessage() . "\n";
}

echo "\nDefault guard: " . config('auth.defaults.guard') . "\n";
echo "Provider: " . config('auth.guards.web.provider') . "\n";
