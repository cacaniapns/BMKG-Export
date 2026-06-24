<?php
// create_dummy_user.php – menambahkan dummy user ke database
require __DIR__ . '/service/database_login.php';   // $db sudah terhubung

$email    = 'demo@example.com';
$password = 'demo123';               // <-- Ganti password bila diinginkan
$hash     = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = $db->prepare('INSERT INTO users (email, password) VALUES (?, ?)');
    $stmt->execute([$email, $hash]);
    echo "✅ Dummy user berhasil ditambahkan: $email / $password\n";
} catch (Throwable $e) {
    echo "❌ Gagal menambahkan user: " . $e->getMessage() . "\n";
}
