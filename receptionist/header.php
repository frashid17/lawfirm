<?php
/**
 * RECEPTIONIST HEADER
 * Common header for all receptionist pages
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receptionist - Law Firm Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>Law Firm Management System - Receptionist Panel</h1>
            <div class="header-user">
                <span><i class="fas fa-user-circle"></i> Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="../logout.php" class="btn btn-secondary btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>
    
    <div class="nav">
        <div class="nav-content">
            <ul class="nav-menu">
                <li><a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="clients.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'clients.php' ? 'active' : ''; ?>"><i class="fas fa-users"></i> Clients</a></li>
                <li><a href="users.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>"><i class="fas fa-user-shield"></i> Manage Users</a></li>
                <li><a href="appointments.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'appointments.php' ? 'active' : ''; ?>"><i class="fas fa-calendar-check"></i> Appointments</a></li>
                <li><a href="billing.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'billing.php' ? 'active' : ''; ?>"><i class="fas fa-dollar-sign"></i> Billing</a></li>
                <li><a href="profile.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>"><i class="fas fa-user"></i> Profile</a></li>
            </ul>
        </div>
    </div>
    
    <div class="container">
