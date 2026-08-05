<?php
/**
 * admin/dashboard.php
 * Super Admin Dashboard — Attendance OS Design
 */

ob_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/flash.php';

requireRole('admin', '../index.php');

$today = date('Y-m-d');

// ── Overall Stats ─────────────────────────────────────────
$totalTeachers = (int)$pdo->query("SELECT COUNT(*) FROM teachers")->fetchColumn();
$totalStudents = (int)$pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();

// Today's School-wide Attendance Stats
$stmtToday = $pdo->prepare("
    SELECT
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) AS present_count,
        SUM(CASE WHEN status = 'Absent'  THEN 1 ELSE 0 END) AS absent_count,
        SUM(CASE WHEN status = 'Late'    THEN 1 ELSE 0 END) AS late_count,
        COUNT(*) AS total_marked
    FROM attendance
    WHERE date = ?
");
$stmtToday->execute([$today]);
$todayStats = $stmtToday->fetch();

$presentToday = (int)($todayStats['present_count'] ?? 0);
$absentToday  = (int)($todayStats['absent_count']  ?? 0);
$lateToday    = (int)($todayStats['late_count']    ?? 0);
$markedToday  = (int)($todayStats['total_marked']  ?? 0);

$todayPct = ($totalStudents > 0) ? round((($presentToday + $lateToday) / $totalStudents) * 100, 1) : 0;

// Class-by-Class Attendance Summary Today
$stmtClasses = $pdo->prepare("
    SELECT t.name AS teacher_name, t.assigned_class,
           COUNT(s.id) AS student_count,
           SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) AS present_count,
           SUM(CASE WHEN a.status = 'Absent'  THEN 1 ELSE 0 END) AS absent_count,
           SUM(CASE WHEN a.status = 'Late'    THEN 1 ELSE 0 END) AS late_count
    FROM teachers t
    LEFT JOIN students s ON s.assigned_teacher_id = t.id
    LEFT JOIN attendance a ON a.student_id = s.id AND a.date = ?
    GROUP BY t.id, t.name, t.assigned_class
    ORDER BY t.assigned_class ASC
");
$stmtClasses->execute([$today]);
$classSummaries = $stmtClasses->fetchAll();

// Recent 5 System Attendance Logs
$recentLogs = $pdo->query("
    SELECT a.date, a.status, s.name AS student_name, s.class, t.name AS teacher_name
    FROM attendance a
    JOIN students s ON a.student_id = s.id
    JOIN teachers t ON a.teacher_id = t.id
    ORDER BY a.created_at DESC
    LIMIT 5
")->fetchAll();

$pageTitle = 'Super Admin Dashboard';
require_once '../includes/sidebar_admin.php';
?>

<?= renderFlash() ?>

<!-- ── Quick Stat Cards ────────────────────────────────── -->
<div class="stat-cards">

  <div class="stat-card blue">
    <div class="stat-icon blue">
      <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16">
        <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4zm-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664h10z"/>
      </svg>
    </div>
    <div class="stat-info">
      <div class="stat-value" data-count="<?= $totalTeachers ?>"><?= $totalTeachers ?></div>
      <div class="stat-label">Head Teachers</div>
      <div class="stat-sub">Assigned Homerooms</div>
    </div>
  </div>

  <div class="stat-card green">
    <div class="stat-icon green">
      <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16">
        <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm-5.784 6A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"/>
      </svg>
    </div>
    <div class="stat-info">
      <div class="stat-value" data-count="<?= $totalStudents ?>"><?= $totalStudents ?></div>
      <div class="stat-label">Total Students</div>
      <div class="stat-sub">School-wide</div>
    </div>
  </div>

  <div class="stat-card green">
    <div class="stat-icon green">
      <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16">
        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
      </svg>
    </div>
    <div class="stat-info">
      <div class="stat-value" data-count="<?= $presentToday ?>"><?= $presentToday ?></div>
      <div class="stat-label">Present Today</div>
      <div class="stat-sub"><?= date('d M Y') ?></div>
    </div>
  </div>

  <div class="stat-card red">
    <div class="stat-icon red">
      <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16">
        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM5.354 4.646a.5.5 0 1 0-.708.708L7.293 8l-2.647 2.646a.5.5 0 0 0 .708.708L8 8.707l2.646 2.647a.5.5 0 0 0 .708-.708L8.707 8l2.647-2.646a.5.5 0 0 0-.708-.708L8 7.293 5.354 4.646z"/>
      </svg>
    </div>
    <div class="stat-info">
      <div class="stat-value" data-count="<?= $absentToday ?>"><?= $absentToday ?></div>
      <div class="stat-label">Absent Today</div>
      <div class="stat-sub"><?= date('d M Y') ?></div>
    </div>
  </div>

</div>

<!-- ── Main Grid ───────────────────────────────────────── -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;align-items:start;">

  <!-- Class-by-Class Morning Attendance Summary -->
  <div class="card">
    <div class="card-header">
      <h3>CLASS MORNING ATTENDANCE STATUS (TODAY)</h3>
      <a href="reports.php" class="btn btn-secondary btn-sm">FULL REPORT</a>
    </div>
    <?php if (empty($classSummaries)): ?>
      <div class="empty-state">
        <h4>NO CLASSES CONFIGURED</h4>
        <p>Go to Head Teachers to assign homeroom classes.</p>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Class</th>
              <th>Head Teacher</th>
              <th>Students</th>
              <th>Present</th>
              <th>Absent</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($classSummaries as $cls):
              $marked = ($cls['present_count'] + $cls['absent_count'] + $cls['late_count']) > 0;
            ?>
            <tr>
              <td><strong style="color:#ffffff;"><?= e($cls['assigned_class'] ?? 'Unassigned') ?></strong></td>
              <td><?= e($cls['teacher_name']) ?></td>
              <td><?= $cls['student_count'] ?></td>
              <td><span class="badge badge-present"><?= $cls['present_count'] ?></span></td>
              <td><span class="badge badge-absent"><?= $cls['absent_count'] ?></span></td>
              <td>
                <?php if ($marked): ?>
                  <span class="badge badge-present">RECORDED</span>
                <?php else: ?>
                  <span class="badge badge-absent">PENDING</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <!-- Recent System Activity -->
  <div class="card">
    <div class="card-header">
      <h3>RECENT ACTIVITY</h3>
    </div>
    <?php if (empty($recentLogs)): ?>
      <div class="empty-state">
        <p>No activity logged yet.</p>
      </div>
    <?php else: ?>
      <div style="padding:0.75rem 1.25rem;">
        <?php foreach ($recentLogs as $log): ?>
          <div style="padding:0.6rem 0;border-bottom:1px solid #171717;">
            <div style="display:flex;align-items:center;justify-content:space-between;">
              <span style="font-weight:600;font-size:0.85rem;color:#ffffff;"><?= e($log['student_name']) ?></span>
              <span class="badge badge-<?= strtolower($log['status']) ?>"><?= e($log['status']) ?></span>
            </div>
            <div style="font-family:var(--font-tech);font-size:0.72rem;color:var(--text-muted);margin-top:0.15rem;">
              <?= e($log['class']) ?> · Marked by <?= e($log['teacher_name']) ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

</div>

<?php require_once '../includes/page_footer.php'; ?>
