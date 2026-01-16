<?php
/**
 * Database Configuration File
 * This file contains the connection settings to your MySQL database
 * 
 * IMPORTANT: Make sure XAMPP MySQL is running before using this system
 */

// Database connection parameters
$host = "localhost";        // Server name (localhost for XAMPP)
$dbname = "lawfirm_db";     // Database name (we'll create this in phpMyAdmin)
$username = "root";          // Default XAMPP username
$password = "";              // Default XAMPP password (empty)

// Create connection
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Set error mode to exception (helps catch errors)
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Optional: Display success message (remove in production)
    // echo "Database connected successfully!";
    
} catch(PDOException $e) {
    // If connection fails, show error message
    die("Connection failed: " . $e->getMessage() . 
        "<br><br><strong>Make sure:</strong><br>" .
        "1. XAMPP MySQL is running<br>" .
        "2. Database 'lawfirm_db' exists in phpMyAdmin<br>" .
        "3. Check database.php settings");
}
?>
