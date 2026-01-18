<?php
/**
 * ADMIN HEADER
 * Common header for all admin pages
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Law Firm Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>Law Firm Management System - Admin Panel</h1>
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
                <li><a href="cases.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'cases.php' ? 'active' : ''; ?>"><i class="fas fa-folder-open"></i> Cases</a></li>
                <li><a href="advocates.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'advocates.php' ? 'active' : ''; ?>"><i class="fas fa-briefcase"></i> Advocates</a></li>
                <li><a href="assignments.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'assignments.php' ? 'active' : ''; ?>"><i class="fas fa-user-check"></i> Assignments</a></li>
                <li><a href="events.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'events.php' ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Events</a></li>
                <li><a href="billing.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'billing.php' ? 'active' : ''; ?>"><i class="fas fa-dollar-sign"></i> Billing</a></li>
                <li><a href="documents.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'documents.php' ? 'active' : ''; ?>"><i class="fas fa-file"></i> Documents</a></li>
                <li><a href="tasks.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'tasks.php' ? 'active' : ''; ?>"><i class="fas fa-tasks"></i> Tasks</a></li>
                <li><a href="calendar.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'calendar.php' ? 'active' : ''; ?>"><i class="fas fa-calendar"></i> Calendar</a></li>
                <li><a href="users.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>"><i class="fas fa-user-cog"></i> Users</a></li>
                <li><a href="create_client_account.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'create_client_account.php' ? 'active' : ''; ?>"><i class="fas fa-user-plus"></i> Client Accounts</a></li>
                <li><a href="locked_accounts.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'locked_accounts.php' ? 'active' : ''; ?>"><i class="fas fa-lock"></i> Locked Accounts</a></li>
                <li><a href="reports.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="profile.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>"><i class="fas fa-user"></i> Profile</a></li>
            </ul>
        </div>
    </div>
    
    <div class="container">
