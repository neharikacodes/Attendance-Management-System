<?php
/**
 * setup_db.php — Fresh Database Installer (No Demo Data)
 * Creates empty database tables with ONLY the Super Admin account.
 * Run via browser: http://localhost/Attendance-Management-System-master/setup_db.php
 * Or via CLI: C:\xampp\php\php.exe setup_db.php
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'attendance_system');

header('Content-Type: text/html; charset=utf-8');
echo "<h2 style='font-family:sans-serif;'>Attendance Management System — Fresh Database Setup</h2>";

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `" . DB_NAME . "`");
    echo "<p style='color:green;'>✅ Database `attendance_system` ready.</p>";

    // Recreate clean empty tables
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("TRUNCATE TABLE `attendance`");
    $pdo->exec("TRUNCATE TABLE `students`");
    $pdo->exec("TRUNCATE TABLE `teachers`");
    $pdo->exec("TRUNCATE TABLE `users`");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    // Create Super Admin Account
    $passHash = password_hash('password123', PASSWORD_BCRYPT, ['cost' => 12]);
    $insAdmin = $pdo->prepare("INSERT INTO `users` (`username`, `password`, `role`) VALUES (?, ?, 'admin')");
    $insAdmin->execute(['admin', $passHash]);

    echo "<p style='color:green;'>✅ All previous demo data, teachers, students, and attendance records deleted!</p>";
    echo "<p style='color:green;'>✅ Super Admin account created.</p>";

    echo "<hr><h3>🎉 DATABASE CLEARED & READY FOR PRODUCTION</h3>";
    echo "<p>You can now log in to the <a href='index.php'><strong>Login Page</strong></a> as Super Admin:</p>";
    echo "<ul>
            <li><strong>Role</strong>: Select <code>ADMIN</code></li>
            <li><strong>User ID</strong>: <code>admin</code></li>
            <li><strong>Passcode</strong>: <code>password123</code></li>
          </ul>";
    echo "<p>Once logged in, go to <strong>HEAD TEACHERS</strong> to add your teachers, assign their classes, and let them add their students!</p>";

} catch (PDOException $e) {
    echo "<p style='color:red;'>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
