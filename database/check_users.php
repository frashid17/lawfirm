<?php
/**
 * CHECK USERS SCRIPT
 * Debug script to check if users exist in database
 */

require_once '../config/database.php';

echo "<h2>Database User Check</h2>";
echo "<style>body { font-family: Arial; padding: 20px; } table { border-collapse: collapse; width: 100%; margin: 20px 0; } th, td { border: 1px solid #ddd; padding: 12px; text-align: left; } th { background: #6366f1; color: white; }</style>";

try {
    // Check Admin users
    echo "<h3>Admin Users:</h3>";
    $stmt = $conn->query("SELECT AdminId, FirstName, LastName, Username, Email FROM ADMIN");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($admins) > 0) {
        echo "<table><tr><th>ID</th><th>Name</th><th>Username</th><th>Email</th></tr>";
        foreach ($admins as $admin) {
            echo "<tr><td>{$admin['AdminId']}</td><td>{$admin['FirstName']} {$admin['LastName']}</td><td>{$admin['Username']}</td><td>{$admin['Email']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>No admin users found!</p>";
    }
    
    // Check Advocate users
    echo "<h3>Advocate Users:</h3>";
    $stmt = $conn->query("SELECT AdvtId, FirstName, LastName, Username, Email, Status FROM ADVOCATE");
    $advocates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($advocates) > 0) {
        echo "<table><tr><th>ID</th><th>Name</th><th>Username</th><th>Email</th><th>Status</th></tr>";
        foreach ($advocates as $advocate) {
            echo "<tr><td>{$advocate['AdvtId']}</td><td>{$advocate['FirstName']} {$advocate['LastName']}</td><td>{$advocate['Username']}</td><td>{$advocate['Email']}</td><td>{$advocate['Status']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>No advocate users found!</p>";
    }
    
    // Check Receptionist users
    echo "<h3>Receptionist Users:</h3>";
    $stmt = $conn->query("SELECT RecId, FirstName, LastName, Username, Email FROM RECEPTIONIST");
    $receptionists = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($receptionists) > 0) {
        echo "<table><tr><th>ID</th><th>Name</th><th>Username</th><th>Email</th></tr>";
        foreach ($receptionists as $receptionist) {
            echo "<tr><td>{$receptionist['RecId']}</td><td>{$receptionist['FirstName']} {$receptionist['LastName']}</td><td>{$receptionist['Username']}</td><td>{$receptionist['Email']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>No receptionist users found!</p>";
    }
    
    echo "<br><a href='setup_users.php' style='background: #6366f1; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Run Setup Users</a>";
    echo " <a href='../login.php' style='background: #10b981; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login</a>";
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
