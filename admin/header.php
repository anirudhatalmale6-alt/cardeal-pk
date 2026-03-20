<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

$pdo = getDB();

// Get counts for sidebar badges
$pendingCount = $pdo->query("SELECT COUNT(*) FROM cars WHERE status='pending'")->fetchColumn();
$unreadCount = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_read=0")->fetchColumn();
$totalCars = $pdo->query("SELECT COUNT(*) FROM cars")->fetchColumn();

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Admin Panel' ?> - Cardeal.pk</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800&family=Open+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --navy: #1a3a5c;
            --navy-deep: #0f2540;
            --navy-light: #2a5580;
            --amber: #e8850c;
            --amber-light: #f5a623;
            --sidebar-w: 240px;
            --white: #ffffff;
            --gray-50: #f5f6f8;
            --gray-100: #eaecf0;
            --gray-200: #d0d5dd;
            --gray-400: #8e95a2;
            --gray-600: #555d6e;
            --gray-800: #2d3340;
            --green: #28a745;
            --red: #dc3545;
            --blue: #007bff;
        }
        body {
            font-family: 'Open Sans', sans-serif;
            background: var(--gray-50);
            color: var(--gray-800);
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0; top: 0; bottom: 0;
            width: var(--sidebar-w);
            background: var(--navy-deep);
            z-index: 100;
            overflow-y: auto;
            transition: 0.3s;
        }
        .sidebar-logo {
            padding: 20px;
            font-family: 'Montserrat', sans-serif;
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--white);
            border-bottom: 2px solid rgba(255,255,255,0.1);
        }
        .sidebar-logo span { color: var(--amber); }
        .sidebar-label {
            padding: 16px 20px 6px;
            font-size: 0.7rem;
            font-weight: 700;
            color: rgba(255,255,255,0.35);
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }
        .sidebar-nav { list-style: none; padding: 8px 0; }
        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 20px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 500;
            transition: 0.2s;
            border-left: 3px solid transparent;
        }
        .sidebar-nav a:hover {
            background: rgba(255,255,255,0.05);
            color: var(--white);
        }
        .sidebar-nav a.active {
            background: rgba(232,133,12,0.1);
            color: var(--amber);
            border-left-color: var(--amber);
        }
        .sidebar-nav a i { width: 18px; text-align: center; font-size: 0.9rem; }
        .badge {
            margin-left: auto;
            background: var(--red);
            color: var(--white);
            font-size: 0.7rem;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 10px;
            min-width: 20px;
            text-align: center;
        }
        .badge-amber { background: var(--amber); }

        /* Main content */
        .main-content {
            margin-left: var(--sidebar-w);
            min-height: 100vh;
        }
        .topbar {
            background: var(--white);
            padding: 16px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--gray-100);
            position: sticky;
            top: 0;
            z-index: 50;
        }
        .topbar h1 {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--navy);
        }
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .topbar-user {
            font-size: 0.85rem;
            color: var(--gray-600);
        }
        .topbar-user strong { color: var(--navy); }
        .btn-logout {
            padding: 6px 16px;
            background: transparent;
            border: 1px solid var(--gray-200);
            border-radius: 6px;
            color: var(--gray-600);
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: 0.2s;
        }
        .btn-logout:hover { border-color: var(--red); color: var(--red); }

        .page-content { padding: 28px; }

        /* Common components */
        .card {
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
            overflow: hidden;
        }
        .card-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--gray-100);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .card-header h2 {
            font-family: 'Montserrat', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            color: var(--navy);
        }
        .card-body { padding: 20px; }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: var(--white);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .stat-icon {
            width: 48px; height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: var(--white);
        }
        .stat-icon.navy { background: var(--navy); }
        .stat-icon.amber { background: var(--amber); }
        .stat-icon.green { background: var(--green); }
        .stat-icon.blue { background: var(--blue); }
        .stat-info h3 {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--navy);
            line-height: 1;
        }
        .stat-info p { font-size: 0.8rem; color: var(--gray-400); margin-top: 2px; }

        /* Table */
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th {
            text-align: left;
            padding: 12px 16px;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--gray-400);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            background: var(--gray-50);
            border-bottom: 1px solid var(--gray-100);
        }
        td {
            padding: 12px 16px;
            font-size: 0.88rem;
            border-bottom: 1px solid var(--gray-100);
            vertical-align: middle;
        }
        tr:hover { background: rgba(232,133,12,0.02); }
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .status-sold { background: #d1ecf1; color: #0c5460; }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-family: inherit;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: 0.2s;
        }
        .btn-primary { background: var(--amber); color: var(--white); }
        .btn-primary:hover { background: #d4780a; }
        .btn-success { background: var(--green); color: var(--white); }
        .btn-success:hover { background: #218838; }
        .btn-danger { background: var(--red); color: var(--white); }
        .btn-danger:hover { background: #c82333; }
        .btn-outline {
            background: transparent;
            border: 1px solid var(--gray-200);
            color: var(--gray-600);
        }
        .btn-outline:hover { border-color: var(--navy); color: var(--navy); }
        .btn-sm { padding: 5px 10px; font-size: 0.78rem; }

        .action-btns { display: flex; gap: 6px; }

        /* Forms */
        .form-group { margin-bottom: 16px; }
        .form-group label {
            display: block;
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--gray-600);
            margin-bottom: 5px;
        }
        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid var(--gray-100);
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.9rem;
            transition: 0.2s;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--amber);
            box-shadow: 0 0 0 3px rgba(232,133,12,0.1);
        }
        select.form-control { cursor: pointer; }
        textarea.form-control { min-height: 100px; resize: vertical; }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .form-row-3 { grid-template-columns: 1fr 1fr 1fr; }

        /* Alerts */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 0.88rem;
            margin-bottom: 16px;
        }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }

        /* Car thumbnail in table */
        .car-thumb {
            width: 60px; height: 45px;
            border-radius: 6px;
            object-fit: cover;
            background: var(--gray-100);
        }
        .car-info-cell { display: flex; align-items: center; gap: 12px; }
        .car-info-text h4 { font-size: 0.88rem; font-weight: 600; color: var(--navy); margin-bottom: 2px; }
        .car-info-text span { font-size: 0.78rem; color: var(--gray-400); }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .form-row, .form-row-3 { grid-template-columns: 1fr; }
            .stat-grid { grid-template-columns: 1fr 1fr; }
        }

        .img-preview-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        .img-preview-item {
            position: relative;
            width: 100px;
            height: 75px;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid var(--gray-100);
        }
        .img-preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .img-preview-item .remove-img {
            position: absolute;
            top: 2px; right: 2px;
            width: 20px; height: 20px;
            background: var(--red);
            color: var(--white);
            border: none;
            border-radius: 50%;
            font-size: 0.7rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--gray-400);
        }
        .empty-state i { font-size: 2rem; margin-bottom: 10px; }
        .empty-state p { font-size: 0.9rem; }
    </style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar">
    <div class="sidebar-logo">Cardeal<span>.pk</span></div>
    <div class="sidebar-label">Main</div>
    <ul class="sidebar-nav">
        <li><a href="index.php" class="<?= $currentPage === 'index' ? 'active' : '' ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="cars.php" class="<?= $currentPage === 'cars' ? 'active' : '' ?>"><i class="fas fa-car"></i> All Cars <span class="badge badge-amber"><?= $totalCars ?></span></a></li>
        <li><a href="pending.php" class="<?= $currentPage === 'pending' ? 'active' : '' ?>"><i class="fas fa-clock"></i> Pending Approval <?php if ($pendingCount > 0): ?><span class="badge"><?= $pendingCount ?></span><?php endif; ?></a></li>
        <li><a href="car-add.php" class="<?= $currentPage === 'car-add' ? 'active' : '' ?>"><i class="fas fa-plus-circle"></i> Add New Car</a></li>
    </ul>
    <div class="sidebar-label">Communication</div>
    <ul class="sidebar-nav">
        <li><a href="messages.php" class="<?= $currentPage === 'messages' ? 'active' : '' ?>"><i class="fas fa-envelope"></i> Messages <?php if ($unreadCount > 0): ?><span class="badge"><?= $unreadCount ?></span><?php endif; ?></a></li>
    </ul>
    <div class="sidebar-label">Settings</div>
    <ul class="sidebar-nav">
        <li><a href="settings.php" class="<?= $currentPage === 'settings' ? 'active' : '' ?>"><i class="fas fa-cog"></i> Site Settings</a></li>
        <li><a href="../index.html" target="_blank"><i class="fas fa-external-link-alt"></i> View Website</a></li>
    </ul>
</aside>

<!-- Main Content -->
<div class="main-content">
    <div class="topbar">
        <h1><?= $pageTitle ?? 'Dashboard' ?></h1>
        <div class="topbar-right">
            <span class="topbar-user">Welcome, <strong><?= sanitize($_SESSION['admin_username'] ?? 'Admin') ?></strong></span>
            <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
    <div class="page-content">
