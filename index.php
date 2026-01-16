<?php
/**
 * WELCOME DASHBOARD / LANDING PAGE
 * Main entry point - Welcome page for the Law Firm Management System
 */

session_start();

// If already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
    $role = $_SESSION['user_role'];
    if ($role === 'admin') {
        header("Location: admin/dashboard.php");
    } elseif ($role === 'advocate') {
        header("Location: advocate/dashboard.php");
    } elseif ($role === 'receptionist') {
        header("Location: receptionist/dashboard.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - Law Firm Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .top-navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px) saturate(180%);
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            padding: 15px 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .nav-content-wrapper {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .nav-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .nav-brand:hover {
            color: #8b5cf6;
        }
        
        .nav-brand i {
            color: #8b5cf6;
            font-size: 24px;
        }
        
        .nav-links {
            display: flex;
            align-items: center;
            gap: 30px;
            list-style: none;
        }
        
        .nav-links a {
            color: #475569;
            text-decoration: none;
            font-weight: 500;
            font-size: 15px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 8px;
        }
        
        .nav-links a:hover {
            background: rgba(139, 92, 246, 0.1);
        }
        
        .nav-links a:hover {
            color: #8b5cf6;
        }
        
        .nav-links .btn-client-login {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
            transition: all 0.3s ease;
        }
        
        .nav-links .btn-client-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
            color: white;
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
        }
        
        .nav-links .btn-signin {
            background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
            color: white;
            padding: 10px 24px;
            border-radius: 10px;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
            transition: all 0.3s ease;
        }
        
        .nav-links .btn-signin:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(139, 92, 246, 0.4);
            color: white;
        }
        
        .welcome-page {
            min-height: 100vh;
            background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 50%, #ec4899 100%);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            position: relative;
            overflow-y: auto;
            overflow-x: hidden;
            padding-top: 70px;
        }
        
        .welcome-page::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.15) 1px, transparent 1px);
            background-size: 60px 60px;
            animation: move 25s linear infinite;
            opacity: 0.4;
        }
        
        .welcome-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 20px;
            position: relative;
            z-index: 1;
        }
        
        .hero-section {
            text-align: center;
            padding: 80px 20px;
            color: #ffffff;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }
        
        .hero-icon {
            width: 120px;
            height: 120px;
            margin: 0 auto 30px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            animation: pulse 2s ease-in-out infinite;
        }
        
        .hero-icon i {
            font-size: 56px;
            color: white;
        }
        
        .hero-section h1 {
            font-size: 56px;
            font-weight: 700;
            margin-bottom: 20px;
            color: #ffffff;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
            animation: fadeInUp 0.8s ease;
        }
        
        .hero-section h2 {
            font-size: 24px;
            font-weight: 500;
            margin-bottom: 40px;
            color: #ffffff;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
            animation: fadeInUp 1s ease;
        }
        
        .hero-section p {
            font-size: 18px;
            max-width: 700px;
            margin: 0 auto 50px;
            line-height: 1.8;
            color: #ffffff;
            text-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
            animation: fadeInUp 1.2s ease;
        }
        
        .cta-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
            animation: fadeInUp 1.4s ease;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 80px;
            padding: 0 20px;
        }
        
        .feature-card {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(20px) saturate(180%);
            border: 2px solid rgba(255, 255, 255, 0.4);
            border-radius: 24px;
            padding: 40px 30px;
            text-align: center;
            color: #ffffff;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }
        
        .feature-card:hover {
            transform: translateY(-10px) scale(1.02);
            background: rgba(255, 255, 255, 0.35);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
        }
        
        .feature-card h3 {
            color: #ffffff;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }
        
        .feature-card p {
            color: #ffffff;
            text-shadow: 0 1px 4px rgba(0, 0, 0, 0.3);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 24px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .feature-icon i {
            font-size: 36px;
            color: white;
        }
        
        .feature-card h3 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 16px;
        }
        
        .feature-card p {
            font-size: 15px;
            line-height: 1.6;
            opacity: 0.9;
        }
        
        .stats-section {
            margin-top: 80px;
            padding: 60px 20px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .stat-item {
            text-align: center;
            color: #ffffff;
        }
        
        .stat-number {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 10px;
            color: #ffffff;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.4);
        }
        
        .stat-label {
            font-size: 16px;
            color: #ffffff;
            font-weight: 500;
            text-shadow: 0 1px 4px rgba(0, 0, 0, 0.3);
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @media (max-width: 768px) {
            .nav-content-wrapper {
                flex-direction: column;
                gap: 15px;
                padding: 0 20px;
            }
            
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
                gap: 15px;
            }
            
            .hero-section h1 {
                font-size: 36px;
            }
            
            .hero-section h2 {
                font-size: 20px;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
            }
            
            .top-navbar {
                padding: 12px 0;
            }
        }
    </style>
</head>
<body class="welcome-page">
    <!-- Top Navigation Bar -->
    <nav class="top-navbar">
        <div class="nav-content-wrapper">
            <a href="index.php" class="nav-brand">
                <i class="fas fa-gavel"></i>
                <span>Law Firm System</span>
            </a>
            <ul class="nav-links">
                <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="#features"><i class="fas fa-star"></i> Features</a></li>
                <li><a href="client/login.php" class="btn-client-login"><i class="fas fa-user-circle"></i> Client Login</a></li>
                <li><a href="login.php" class="btn-signin"><i class="fas fa-sign-in-alt"></i> Staff Sign In</a></li>
            </ul>
        </div>
    </nav>
    
    <div class="welcome-container">
        <div class="hero-section">
            <div class="hero-icon">
                <i class="fas fa-gavel"></i>
            </div>
            <h1>Law Firm Management System</h1>
            <h2>Munyoki Maheli and Company Advocates</h2>
            <p>Streamline your law firm operations with our comprehensive management system. Manage cases, clients, advocates, appointments, and billing all in one place.</p>
            
            <div class="cta-buttons">
                <a href="#features" class="btn btn-secondary" style="padding: 18px 40px; font-size: 18px; min-width: 200px; background: rgba(255, 255, 255, 0.2); border: 2px solid rgba(255, 255, 255, 0.3);">
                    <i class="fas fa-info-circle"></i> Learn More
                </a>
            </div>
        </div>
        
        <div id="features" class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3>Client Management</h3>
                <p>Efficiently manage client information, contact details, and case history. Keep all client data organized and easily accessible.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-folder-open"></i>
                </div>
                <h3>Case Tracking</h3>
                <p>Track all legal cases with detailed information including case type, court, status, and assigned advocates. Never lose track of important cases.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-briefcase"></i>
                </div>
                <h3>Advocate Management</h3>
                <p>Manage advocate profiles and assign cases to the right legal professionals. Track case assignments and workload distribution.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <h3>Event Scheduling</h3>
                <p>Schedule and manage court hearings, meetings, consultations, and other important events. Never miss an important appointment.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <h3>Billing & Payments</h3>
                <p>Track billing information, deposits, installments, and payment status. Keep financial records organized and up-to-date.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <h3>Reports & Analytics</h3>
                <p>Generate comprehensive reports on cases, billing, and system statistics. Make data-driven decisions for your law firm.</p>
            </div>
        </div>
        
        <div class="stats-section">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number">3</div>
                    <div class="stat-label">User Roles</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">9</div>
                    <div class="stat-label">Core Modules</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">100%</div>
                    <div class="stat-label">Secure & Reliable</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">24/7</div>
                    <div class="stat-label">Access Available</div>
                </div>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 60px; padding: 40px 20px; color: #ffffff;">
            <h3 style="font-size: 28px; margin-bottom: 20px; font-weight: 700; color: #ffffff; text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);">Ready to Get Started?</h3>
            <p style="font-size: 16px; margin-bottom: 30px; color: #ffffff; text-shadow: 0 1px 4px rgba(0, 0, 0, 0.3);">Use the Sign In button at the top to access your dashboard and start managing your law firm efficiently.</p>
        </div>
    </div>
    
    <!-- Footer -->
    <footer style="background: rgba(15, 23, 42, 0.9); color: #ffffff; padding: 40px 20px; text-align: center; margin-top: 80px;">
        <div style="max-width: 1400px; margin: 0 auto;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 40px; margin-bottom: 30px; text-align: left;">
                <div>
                    <h4 style="color: #a78bfa; margin-bottom: 15px; font-size: 18px;"><i class="fas fa-gavel"></i> Law Firm System</h4>
                    <p style="color: #cbd5e1; line-height: 1.8; font-size: 14px;">Comprehensive management system for modern law firms. Streamline operations and improve efficiency.</p>
                </div>
                <div>
                    <h4 style="color: #a78bfa; margin-bottom: 15px; font-size: 18px;">Quick Links</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 10px;"><a href="index.php" style="color: #cbd5e1; text-decoration: none; transition: color 0.3s;"><i class="fas fa-home"></i> Home</a></li>
                        <li style="margin-bottom: 10px;"><a href="login.php" style="color: #cbd5e1; text-decoration: none; transition: color 0.3s;"><i class="fas fa-sign-in-alt"></i> Sign In</a></li>
                        <li style="margin-bottom: 10px;"><a href="database/setup_users.php" style="color: #cbd5e1; text-decoration: none; transition: color 0.3s;"><i class="fas fa-cog"></i> Setup</a></li>
                        <li style="margin-bottom: 10px;"><a href="database/check_users.php" style="color: #cbd5e1; text-decoration: none; transition: color 0.3s;"><i class="fas fa-users"></i> Check Users</a></li>
                    </ul>
                    <style>
                        footer a:hover {
                            color: #a78bfa !important;
                        }
                    </style>
                </div>
                <div>
                    <h4 style="color: #a78bfa; margin-bottom: 15px; font-size: 18px;">Contact</h4>
                    <p style="color: #cbd5e1; line-height: 1.8; font-size: 14px;">
                        <i class="fas fa-building"></i> Munyoki Maheli and Company Advocates<br>
                        <i class="fas fa-envelope"></i> info@lawfirm.com<br>
                        <i class="fas fa-phone"></i> +254 700 000 000
                    </p>
                </div>
            </div>
            <div style="border-top: 1px solid rgba(255, 255, 255, 0.1); padding-top: 20px; color: #94a3b8; font-size: 14px;">
                <p>&copy; <?php echo date('Y'); ?> Law Firm Management System. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>
