<?php
/**
 * includes/sidebar_admin.php
 * Reusable sidebar for Super Admin pages — Nothing OS Design
 */

$currentPage = basename($_SERVER['PHP_SELF']);
$adminName   = currentUserName();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle ?? 'Admin') ?> — Attendance Management</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DotGothic16&family=Space+Grotesk:wght@300;400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="app-layout">

  <!-- Sidebar Overlay (mobile) -->
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <!-- ── Sidebar ─────────────────────────────────── -->
  <nav class="sidebar" id="sidebar" role="navigation" aria-label="Admin Navigation">

    <!-- Brand -->
    <div class="sidebar-brand">
      <div class="sidebar-brand-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="white" viewBox="0 0 16 16">
          <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/>
        </svg>
      </div>
      <div class="sidebar-brand-text">
        <h3>ATTENDANCE OS</h3>
        <span>SUPER ADMIN PORTAL</span>
      </div>
    </div>

    <!-- Navigation -->
    <div class="sidebar-nav">
      <div class="sidebar-section-label">SYSTEM OVERVIEW</div>

      <a href="dashboard.php" class="sidebar-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
          <path d="M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L2 8.207V13.5A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V8.207l.646.647a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.707 1.5z"/>
        </svg>
        DASHBOARD
      </a>

      <div class="sidebar-section-label" style="margin-top:0.5rem;">SCHOOL SETUP</div>

      <a href="teachers.php" class="sidebar-link <?= $currentPage === 'teachers.php' ? 'active' : '' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
          <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4zm-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664h10z"/>
        </svg>
        HEAD TEACHERS
      </a>

      <a href="students.php" class="sidebar-link <?= $currentPage === 'students.php' ? 'active' : '' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
          <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm-5.784 6A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"/>
        </svg>
        ALL STUDENTS
      </a>

      <div class="sidebar-section-label" style="margin-top:0.5rem;">ANALYTICS</div>

      <a href="reports.php" class="sidebar-link <?= $currentPage === 'reports.php' ? 'active' : '' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
          <path d="M14 2H2a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2zM1 4a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V4z"/>
        </svg>
        SCHOOL REPORTS
      </a>

      <div class="sidebar-section-label" style="margin-top:0.5rem;">SETTINGS</div>

      <a href="profile.php" class="sidebar-link <?= $currentPage === 'profile.php' ? 'active' : '' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
          <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
          <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z"/>
        </svg>
        ADMIN ACCOUNT
      </a>
    </div>

    <!-- User Footer -->
    <div class="sidebar-footer">
      <div class="sidebar-user">
        <div class="sidebar-user-avatar">ADM</div>
        <div class="sidebar-user-info">
          <h4><?= e($adminName) ?></h4>
          <span>SUPER ADMIN</span>
        </div>
      </div>
      <form action="../logout.php" method="POST" style="margin-top:0.5rem;">
        <button type="submit" class="sidebar-logout">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0v2z"/>
          </svg>
          LOGOUT
        </button>
      </form>
    </div>

  </nav>

  <!-- ── Main Content ────────────────────────────── -->
  <div class="main-content">

    <!-- Top Header -->
    <header class="top-header">
      <button class="header-menu-btn" id="menuBtn" aria-label="Open menu">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
          <path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z"/>
        </svg>
      </button>
      <div class="header-title">
        <h2><?= e(strtoupper($pageTitle ?? 'ADMIN DASHBOARD')) ?></h2>
        <p><?= date('l, d F Y') ?></p>
      </div>
      <div class="header-actions">
        <span class="date-badge">
          SYSTEM ONLINE
        </span>
      </div>
    </header>

    <!-- Page Content -->
    <main class="page-content">
