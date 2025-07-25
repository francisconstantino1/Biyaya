<?php
// Dashboard page
session_start();
require_once 'config.php';
require_once 'user_functions.php';

// Get church logo
$church_logo = getChurchLogo($conn);

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
            header("Location: index.php");
    exit;
}
// Restrict access to Administrator only
if ($_SESSION["user_role"] !== "Administrator") {
    header("Location: index.php");
    exit;
}

// Get user profile from database
$user_profile = getUserProfile($conn, $_SESSION["user"]);

// Site configuration
$site_settings = getSiteSettings($conn);
$church_name = $site_settings['church_name'];
$current_page = basename($_SERVER['PHP_SELF']);

// Initialize default session data for profile
if (!isset($_SESSION["user_email"])) {
    $_SESSION["user_email"] = "admin@example.com";
}

// Add random Bible verse for the day (copied from superadmin_dashboard.php)
$bible_verses = [
    [
        'ref' => 'Philippians 4:13',
        'text' => 'I can do all things through Christ who strengthens me.'
    ],
    [
        'ref' => 'Jeremiah 29:11',
        'text' => 'For I know the plans I have for you, declares the Lord, plans to prosper you and not to harm you, plans to give you hope and a future.'
    ],
    [
        'ref' => 'Psalm 23:1',
        'text' => 'The Lord is my shepherd; I shall not want.'
    ],
    [
        'ref' => 'Romans 8:28',
        'text' => 'And we know that in all things God works for the good of those who love him, who have been called according to his purpose.'
    ],
    [
        'ref' => 'Proverbs 3:5-6',
        'text' => 'Trust in the Lord with all your heart and lean not on your own understanding; in all your ways submit to him, and he will make your paths straight.'
    ],
    [
        'ref' => 'Isaiah 41:10',
        'text' => 'So do not fear, for I am with you; do not be dismayed, for I am your God. I will strengthen you and help you; I will uphold you with my righteous right hand.'
    ],
];
$verse_index = intval(date('z')) % count($bible_verses);
$verse_of_the_day = $bible_verses[$verse_index];

// Get total members count from database
try {
    $result = $conn->query("SELECT COUNT(*) as total FROM membership_records");
    $row = $result->fetch_assoc();
    $total_members = $row['total'];

    // Get gender statistics
    $gender_stats = $conn->query("SELECT sex, COUNT(*) as count FROM membership_records GROUP BY sex");
    $male_count = 0;
    $female_count = 0;
    while ($row = $gender_stats->fetch_assoc()) {
        if ($row['sex'] === 'Male') {
            $male_count = $row['count'];
        } else if ($row['sex'] === 'Female') {
            $female_count = $row['count'];
        }
    }
} catch(Exception $e) {
    $total_members = 0;
    $male_count = 0;
    $female_count = 0;
}

// Get total events count from database
try {
    $result = $conn->query("SELECT COUNT(*) as total FROM events");
    $row = $result->fetch_assoc();
    $total_events = $row['total'];
} catch(Exception $e) {
    $total_events = 0;
}

// Get total prayer requests count from database
try {
    $result = $conn->query("SELECT COUNT(*) as total FROM prayer_requests");
    $row = $result->fetch_assoc();
    $total_prayers = $row['total'];
} catch(Exception $e) {
    $total_prayers = 0;
}

// Dashboard statistics
$dashboard_stats = [
    "total_members" => $total_members,
    "upcoming_events" => $total_events,
    "pending_prayers" => $total_prayers
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | <?php echo $church_name; ?></title>
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($church_logo); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <style>
        :root {
            --primary-color: #3a3a3a;
            --accent-color: rgb(0, 139, 30);
            --light-gray: #d0d0d0;
            --white: #ffffff;
            --sidebar-width: 250px;
            --success-color: #4caf50;
            --warning-color: #ff9800;
            --danger-color: #f44336;
            --info-color: #2196f3;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f5f5;
            color: var(--primary-color);
            line-height: 1.6;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--primary-color);
            color: var(--white);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            overflow: hidden;
        }

        .sidebar-header img {
            height: 60px;
            margin-bottom: 10px;
            transition: 0.3s;
        }

        .sidebar-header h3 {
            font-size: 18px;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .sidebar-menu ul {
            list-style: none;
        }

        .sidebar-menu li {
            margin-bottom: 5px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: var(--white);
            text-decoration: none;
            transition: all 0.3s;
            font-size: 16px;
        }

        .sidebar-menu a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .sidebar-menu a.active {
            background-color: var(--accent-color);
        }

        .sidebar-menu i {
            margin-right: 15px;
            width: 20px;
            text-align: center;
            font-size: 20px;
        }

        .sidebar-menu span {
            margin-left: 10px;
        }

        .content-area {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 20px;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: var(--white);
            padding: 15px 20px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            margin-top: 60px;
        }

        .top-bar h2 {
            font-size: 24px;
        }

        .user-profile {
            display: flex;
            align-items: center;
        }

        .user-profile .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--accent-color);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 10px;
            overflow: hidden;
        }

        .user-profile .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .user-info {
            margin-right: 15px;
        }

        .user-info h4 {
            font-size: 14px;
            margin: 0;
        }

        .user-info p {
            font-size: 12px;
            margin: 0;
            color: #666;
        }

        .logout-btn {
            background-color: #f0f0f0;
            color: var(--primary-color);
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .logout-btn:hover {
            background-color: #e0e0e0;
        }

        .dashboard-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .card {
            background-color: var(--white);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }

        .card:hover {
            transform: translateY(-10px);
        }

        .card h3 {
            margin-bottom: 15px;
            font-size: 18px;
        }

        .card p {
            font-size: 24px;
            font-weight: bold;
            color: var(--accent-color);
        }

        .card i {
            font-size: 30px;
            margin-bottom: 10px;
            color: var(--accent-color);
        }

        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
            }
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                padding-top: 10px;
            }
            .sidebar-menu {
                display: flex;
                padding: 0;
                overflow-x: auto;
            }
            .sidebar-menu ul {
                display: flex;
                width: 100%;
            }
            .sidebar-menu li {
                margin-bottom: 0;
                flex: 1;
            }
            .sidebar-menu a {
                padding: 10px;
                justify-content: center;
            }
            .sidebar-menu i {
                margin-right: 0;
            }
            
            .content-area {
                margin-left: 0;
            }
            .top-bar {
                flex-direction: column;
                align-items: flex-start;
            }
            .user-profile {
                margin-top: 10px;
            }
        }

        /* --- Drawer Navigation Styles (copied from superadmin_dashboard.php) --- */
        .nav-toggle-container {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 50;
        }
        .nav-toggle-btn {
            background-color: #3b82f6;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.3s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .nav-toggle-btn:hover {
            background-color: #2563eb;
        }
        .custom-drawer {
            position: fixed;
            top: 0;
            left: -300px;
            width: 300px;
            height: 100vh;
            background: linear-gradient(135deg, #f8fafc 0%, #e0e7ef 100%);
            color: #3a3a3a;
            z-index: 1000;
            transition: left 0.3s ease;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .custom-drawer.open {
            left: 0;
        }
        .drawer-header {
            padding: 20px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            min-height: 120px;
        }
        .drawer-logo-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            min-height: 100px;
            justify-content: center;
            flex: 1;
        }
        .drawer-logo {
            height: 60px;
            width: auto;
            max-width: 200px;
            object-fit: contain;
            flex-shrink: 0;
        }
        .drawer-title {
            font-size: 16px;
            font-weight: bold;
            margin: 0;
            text-align: center;
            color: #3a3a3a;
            max-width: 200px;
            word-wrap: break-word;
            line-height: 1.2;
            min-height: 20px;
        }
        .drawer-close {
            background: none;
            border: none;
            color: #3a3a3a;
            font-size: 20px;
            cursor: pointer;
            padding: 5px;
        }
        .drawer-close:hover {
            color: #666;
        }
        .drawer-content {
            padding: 20px 0 0 0;
            flex: 1;
        }
        .drawer-menu {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .drawer-menu li {
            margin: 0;
        }
        .drawer-link {
            display: flex;
            align-items: center;
            padding: 12px 18px;
            color: #3a3a3a;
            text-decoration: none;
            font-size: 15px;
            font-weight: 500;
            gap: 10px;
            border-left: 4px solid transparent;
            transition: background 0.2s, border-color 0.2s, color 0.2s;
            position: relative;
        }
        .drawer-link i {
            font-size: 18px;
            min-width: 22px;
            text-align: center;
        }
        .drawer-link.active {
            background: linear-gradient(90deg, #e0ffe7 0%, #f5f5f5 100%);
            border-left: 4px solid var(--accent-color);
            color: var(--accent-color);
        }
        .drawer-link.active i {
            color: var(--accent-color);
        }
        .drawer-link:hover {
            background: rgba(0, 139, 30, 0.07);
            color: var(--accent-color);
        }
        .drawer-link:hover i {
            color: var(--accent-color);
        }
        .drawer-profile {
            padding: 24px 20px 20px 20px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 14px;
            background: rgba(255,255,255,0.85);
        }
        .drawer-profile .avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--accent-color);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            font-weight: bold;
            overflow: hidden;
        }
        .drawer-profile .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .drawer-profile .profile-info {
            flex: 1;
        }
        .drawer-profile .name {
            font-size: 16px;
            font-weight: 600;
            color: #222;
        }
        .drawer-profile .role {
            font-size: 13px;
            color: var(--accent-color);
            font-weight: 500;
            margin-top: 2px;
        }
        .drawer-profile .logout-btn {
            background: #f44336;
            color: #fff;
            border: none;
            padding: 7px 16px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            margin-left: 10px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .drawer-profile .logout-btn:hover {
            background: #d32f2f;
        }
        .drawer-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }
        .drawer-overlay.open {
            opacity: 1;
            visibility: visible;
        }
        /* Ensure content doesn't overlap with the button */
        .content-area {
            padding-top: 80px;
            margin-left: 0;
            padding: 20px;
        }
        /* --- SUMMARY CARDS (MATCH SUPERADMIN DASHBOARD) --- */
        .dashboard-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .summary-card {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
            position: relative;
            border: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            margin-bottom: 0;
            min-width: 0;
            width: 100%;
            max-width: none;
        }
        .summary-card:hover {
            transform: translateY(-10px);
        }
        .summary-card.full-width {
            grid-column: 1 / -1;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }
        .summary-card .card-icon {
            background: #fff;
            border-radius: 50%;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
        }
        .summary-card .card-content {
            flex: 1;
            text-align: left;
        }
        .summary-card h3 {
            font-size: 16px;
            color: #666;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .summary-card .card-number {
            font-size: 36px;
            font-weight: 700;
            color: #333;
            margin-bottom: 6px;
            line-height: 1;
            animation: countUp 2s ease-out;
        }
        @keyframes countUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .summary-card .card-subtitle {
            font-size: 13px;
            color: #888;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .summary-card .card-decoration {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.8), transparent);
            transform: translateX(-100%);
            transition: transform 0.6s ease;
            border-radius: 0 0 16px 16px;
        }
        .summary-card:hover .card-decoration {
            transform: translateX(100%);
        }
        /* Individual Card Themes */
        .members-card,
        .events-card,
        .prayers-card,
        .gender-card {
            background: var(--white);
            border-left: 4px solid #ffffff;
        }
        .members-card .card-icon,
        .events-card .card-icon,
        .prayers-card .card-icon,
        .gender-card .card-icon {
            background: #ffffff;
        }
        .summary-card .card-icon i,
        .gender-icon i {
            color: #008b1e !important;
            font-size: 24px;
        }
        /* Gender Card Styling */
        .gender-stats {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin: 15px 0;
        }
        .gender-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            border-radius: 8px;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
            flex: 1;
        }
        .gender-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .gender-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: white;
            transition: all 0.3s ease;
        }
        .gender-item.male .gender-icon,
        .gender-item.female .gender-icon {
            background: #ffffff;
        }
        .gender-item:hover .gender-icon {
            transform: scale(1.05);
        }
        .gender-info {
            flex: 1;
        }
        .gender-number {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            line-height: 1;
            margin-bottom: 2px;
            animation: countUp 2s ease-out;
        }
        .gender-label {
            font-size: 12px;
            font-weight: 600;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        /* Remove chart, prediction, and unused code below */
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="nav-toggle-container">
           <button class="nav-toggle-btn" type="button" id="nav-toggle">
           <i class="fas fa-bars"></i> Menu
           </button>
        </div>
        <!-- Custom Drawer Navigation -->
        <div id="drawer-navigation" class="custom-drawer">
            <div class="drawer-header">
                <div class="drawer-logo-section">
                    <img src="<?php echo htmlspecialchars($church_logo); ?>" alt="Church Logo" class="drawer-logo">
                    <h5 class="drawer-title"><?php echo $church_name; ?></h5>
                </div>
                <button type="button" class="drawer-close" id="drawer-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="drawer-content">
                <ul class="drawer-menu">
                    <li>
                        <a href="dashboard.php" class="drawer-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                            <i class="fas fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_events.php" class="drawer-link <?php echo $current_page == 'admin_events.php' ? 'active' : ''; ?>">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Events</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_prayers.php" class="drawer-link <?php echo $current_page == 'admin_prayers.php' ? 'active' : ''; ?>">
                            <i class="fas fa-hands-praying"></i>
                            <span>Prayer Requests</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_messages.php" class="drawer-link <?php echo $current_page == 'admin_messages.php' ? 'active' : ''; ?>">
                            <i class="fas fa-video"></i>
                            <span>Messages</span>
                        </a>
                    </li>
                    <li>
                        <a href="financialreport.php" class="drawer-link <?php echo $current_page == 'financialreport.php' ? 'active' : ''; ?>">
                            <i class="fas fa-chart-line"></i>
                            <span>Financial Reports</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_expenses.php" class="drawer-link <?php echo $current_page == 'admin_expenses.php' ? 'active' : ''; ?>">
                            <i class="fas fa-receipt"></i>
                            <span>Monthly Expenses</span>
                        </a>
                    </li>
                    <li>
                        <a href="member_contributions.php" class="drawer-link <?php echo $current_page == 'member_contributions.php' ? 'active' : ''; ?>">
                            <i class="fas fa-list-alt"></i>
                            <span>Stewardship Report</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_settings.php" class="drawer-link <?php echo $current_page == 'admin_settings.php' ? 'active' : ''; ?>">
                            <i class="fas fa-cog"></i>
                            <span>Settings</span>
                        </a>
                    </li>
                </ul>
            </div>
            <div class="drawer-profile">
                <div class="avatar">
                    <?php if (!empty($user_profile['profile_picture'])): ?>
                        <img src="<?php echo htmlspecialchars($user_profile['profile_picture']); ?>" alt="Profile Picture">
                    <?php else: ?>
                        <?php echo strtoupper(substr($user_profile['username'] ?? 'U', 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <div class="profile-info">
                    <div class="name"><?php echo htmlspecialchars($user_profile['username'] ?? 'Unknown User'); ?></div>
                    <div class="role"><?php echo htmlspecialchars($_SESSION['user_role']); ?></div>
                </div>
                <form action="logout.php" method="post" style="margin:0;">
                    <button type="submit" class="logout-btn">Logout</button>
                </form>
            </div>
        </div>
        <!-- Drawer Overlay -->
        <div id="drawer-overlay" class="drawer-overlay"></div>
        <main class="content-area">
            <div class="top-bar">
                <div>
                    <h2>Dashboard</h2>
                    <p style="margin-top: 5px; color: #666; font-size: 16px; font-weight: 400;">
                        Welcome, <?php echo htmlspecialchars($user_profile['full_name'] ?? $user_profile['username']); ?>
                    </p>
                </div>
            </div>
            <div class="dashboard-content">
                <div class="summary-card members-card">
                    <div class="card-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="card-content">
                        <h3>Total Members</h3>
                        <div class="card-number"><?php echo $dashboard_stats["total_members"]; ?></div>
                        <div class="card-subtitle">Active Congregation</div>
                    </div>
                    <div class="card-decoration"></div>
                </div>
                <div class="summary-card events-card">
                    <div class="card-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="card-content">
                        <h3>Upcoming Events</h3>
                        <div class="card-number"><?php echo $dashboard_stats["upcoming_events"]; ?></div>
                        <div class="card-subtitle">Scheduled Activities</div>
                    </div>
                    <div class="card-decoration"></div>
                </div>
                <div class="summary-card prayers-card">
                    <div class="card-icon">
                        <i class="fas fa-hands-praying"></i>
                    </div>
                    <div class="card-content">
                        <h3>Prayer Requests</h3>
                        <div class="card-number"><?php echo $dashboard_stats["pending_prayers"]; ?></div>
                        <div class="card-subtitle">Needs Prayer</div>
                    </div>
                    <div class="card-decoration"></div>
                </div>
                <div class="summary-card gender-card">
                    <div class="card-icon">
                        <i class="fas fa-venus-mars"></i>
                    </div>
                    <div class="card-content">
                        <h3>Gender Distribution</h3>
                        <div class="gender-stats">
                            <div class="gender-item male">
                                <div class="gender-icon">
                                    <i class="fas fa-mars"></i>
                                </div>
                                <div class="gender-info">
                                    <div class="gender-number"><?php echo $male_count; ?></div>
                                    <div class="gender-label">Male</div>
                                </div>
                            </div>
                            <div class="gender-item female">
                                <div class="gender-icon">
                                    <i class="fas fa-venus"></i>
                                </div>
                                <div class="gender-info">
                                    <div class="gender-number"><?php echo $female_count; ?></div>
                                    <div class="gender-label">Female</div>
                                </div>
                            </div>
                        </div>
                        <div class="card-subtitle">Congregational Split</div>
                    </div>
                    <div class="card-decoration"></div>
                </div>
                <div class="summary-card full-width">
                    <div class="card-icon">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div class="card-content">
                        <h3>Bible Verse of the Day</h3>
                        <p style="font-size:16px; color:#333; font-style:italic; margin-bottom:8px;">"<?php echo $verse_of_the_day['text']; ?>"</p>
                        <p style="font-size:14px; color:#008b1e; text-align:right; margin:0;"><b><?php echo $verse_of_the_day['ref']; ?></b></p>
                    </div>
                    <div class="card-decoration"></div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Custom Drawer Navigation JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            const navToggle = document.getElementById('nav-toggle');
            const drawer = document.getElementById('drawer-navigation');
            const drawerClose = document.getElementById('drawer-close');
            const overlay = document.getElementById('drawer-overlay');

            // Open drawer
            navToggle.addEventListener('click', function() {
                drawer.classList.add('open');
                overlay.classList.add('open');
                document.body.style.overflow = 'hidden';
            });

            // Close drawer
            function closeDrawer() {
                drawer.classList.remove('open');
                overlay.classList.remove('open');
                document.body.style.overflow = '';
            }

            drawerClose.addEventListener('click', closeDrawer);
            overlay.addEventListener('click', closeDrawer);

            // Close drawer on escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeDrawer();
                }
            });
        });

        // Gender Distribution Chart
        const ctxGender = document.getElementById('genderChart').getContext('2d');
        new Chart(ctxGender, {
            type: 'pie',
            data: {
                labels: ['Male', 'Female'],
                datasets: [{
                    data: [<?php echo $male_count; ?>, <?php echo $female_count; ?>],
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 99, 132, 0.8)'
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 99, 132, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                }
            }
        });
    </script>
</body>
</html>