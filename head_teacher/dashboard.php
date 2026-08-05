<?php
/**
 * head_teacher/dashboard.php
 * Head Teacher Dashboard — Nothing OS Design
 */

ob_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/flash.php';

requireRole('head_teacher', '../index.php');

$teacherId = currentTeacherId();

// ── Stats ────────────────────────────────────────────────
// Total assigned students
$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM students WHERE assigned_teacher_id = ?");
$stmtTotal->execute([$teacherId]);
$totalStudents = (int)$stmtTotal->fetchColumn();

// Today's attendance
$today = date('Y-m-d');
$stmtToday = $pdo->prepare("
    SELECT
        SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) AS present_count,
        SUM(CASE WHEN a.status = 'Absent'  THEN 1 ELSE 0 END) AS absent_count,
        SUM(CASE WHEN a.status = 'Late'    THEN 1 ELSE 0 END) AS late_count,
        COUNT(*) AS marked_count
    FROM attendance a
    JOIN students s ON a.student_id = s.id
    WHERE a.teacher_id = ? AND a.date = ? AND s.assigned_teacher_id = ?
");
$stmtToday->execute([$teacherId, $today, $teacherId]);
$todayStats = $stmtToday->fetch();

$presentToday = (int)($todayStats['present_count'] ?? 0);
$absentToday  = (int)($todayStats['absent_count']  ?? 0);
$lateToday    = (int)($todayStats['late_count']    ?? 0);
$markedToday  = (int)($todayStats['marked_count']  ?? 0);

// Overall attendance percentage (all time)
$stmtOverall = $pdo->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN a.status = 'Present' OR a.status = 'Late' THEN 1 ELSE 0 END) AS attended
    FROM attendance a
    JOIN students s ON a.student_id = s.id
    WHERE a.teacher_id = ? AND s.assigned_teacher_id = ?
");
$stmtOverall->execute([$teacherId, $teacherId]);
$overall = $stmtOverall->fetch();
$overallPct = ($overall['total'] > 0) ? round(($overall['attended'] / $overall['total']) * 100, 1) : 0;

// Recent 7 days attendance summary
$stmtRecent = $pdo->prepare("
    SELECT a.date,
           SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) AS present_count,
           SUM(CASE WHEN a.status = 'Absent'  THEN 1 ELSE 0 END) AS absent_count,
           COUNT(*) AS total
    FROM attendance a
    JOIN students s ON a.student_id = s.id
    WHERE a.teacher_id = ? AND s.assigned_teacher_id = ?
      AND a.date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY a.date
    ORDER BY a.date DESC
");
$stmtRecent->execute([$teacherId, $teacherId]);
$recentDays = $stmtRecent->fetchAll();

// Students with low attendance (< 75%)
$stmtLow = $pdo->prepare("
    SELECT s.name, s.class, s.roll_number,
           COUNT(a.id) AS total_days,
           SUM(CASE WHEN a.status = 'Present' OR a.status = 'Late' THEN 1 ELSE 0 END) AS attended
    FROM students s
    LEFT JOIN attendance a ON s.id = a.student_id AND a.teacher_id = ?
    WHERE s.assigned_teacher_id = ?
    GROUP BY s.id, s.name, s.class, s.roll_number
    HAVING COUNT(a.id) > 0 AND (SUM(CASE WHEN a.status = 'Present' OR a.status = 'Late' THEN 1 ELSE 0 END) / COUNT(a.id) * 100) < 75
    ORDER BY (SUM(CASE WHEN a.status = 'Present' OR a.status = 'Late' THEN 1 ELSE 0 END) / COUNT(a.id)) ASC
    LIMIT 5
");
$stmtLow->execute([$teacherId, $teacherId]);
$lowAttendance = $stmtLow->fetchAll();

// Today attendance not marked yet?
$attendanceMarkedToday = ($markedToday > 0);

// Page render
$pageTitle = 'Dashboard';
require_once '../includes/sidebar_teacher.php';
?>

<?= renderFlash() ?>

<!-- ── Quick Stats ─────────────────────────────────────── -->
<div class="stat-cards">

  <div class="stat-card blue">
    <div class="stat-icon blue">
      <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16">
        <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm-5.784 6A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"/>
      </svg>
    </div>
    <div class="stat-info">
      <div class="stat-value" data-count="<?= $totalStudents ?>"><?= $totalStudents ?></div>
      <div class="stat-label">Total Students</div>
      <div class="stat-sub">Assigned</div>
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

  <div class="stat-card yellow">
    <div class="stat-icon yellow">
      <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16">
        <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
        <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
      </svg>
    </div>
    <div class="stat-info">
      <div class="stat-value"><?= $overallPct ?>%</div>
      <div class="stat-label">Overall Rate</div>
      <div class="stat-sub">Average</div>
    </div>
  </div>

</div>

<!-- ── Action Banner (if attendance not marked today) ──── -->
<?php if (!$attendanceMarkedToday && $totalStudents > 0): ?>
<div style="background:#0a0a0a;border:1px solid var(--accent-red);border-radius:var(--radius-md);padding:1.25rem 1.5rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:1.75rem;box-shadow:var(--glow-red);">
  <div style="color:var(--text-main);">
    <div style="font-family:var(--font-matrix);font-size:1.1rem;text-transform:uppercase;color:var(--accent-red);margin-bottom:0.2rem;">
      🔴 ATTENDANCE NOT MARKED YET
    </div>
    <div style="font-family:var(--font-tech);font-size:0.8rem;color:var(--text-muted);">
      Today's attendance for your students has not been recorded.
    </div>
  </div>
  <a href="attendance.php" class="btn btn-danger btn-lg">
    MARK NOW →
  </a>
</div>
<?php endif; ?>

<!-- ── Bottom Grid ─────────────────────────────────────── -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">

  <!-- Recent Attendance Days -->
  <div class="card">
    <div class="card-header">
      <h3>
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="var(--text-main)" viewBox="0 0 16 16">
          <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/>
        </svg>
        LAST 7 DAYS
      </h3>
      <a href="view_attendance.php" class="btn btn-secondary btn-sm">VIEW ALL</a>
    </div>
    <?php if (empty($recentDays)): ?>
      <div class="empty-state">
        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" viewBox="0 0 16 16">
          <path d="M1.5 1a.5.5 0 0 0-.5.5v3a.5.5 0 0 1-1 0v-3A1.5 1.5 0 0 1 1.5 0h3a.5.5 0 0 1 0 1h-3z"/>
        </svg>
        <h4>NO RECORDS YET</h4>
        <p>Mark attendance to see history here.</p>
        <a href="attendance.php" class="btn btn-primary btn-sm" style="margin-top:0.75rem;">MARK ATTENDANCE</a>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Present</th>
              <th>Absent</th>
              <th>Rate</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentDays as $day):
              $rate = $day['total'] > 0 ? round(($day['present_count'] / $day['total']) * 100) : 0;
              $rateColor = $rate >= 75 ? 'green' : ($rate >= 50 ? 'yellow' : 'red');
            ?>
            <tr>
              <td><strong><?= date('d M', strtotime($day['date'])) ?></strong> <span class="text-muted text-small"><?= date('D', strtotime($day['date'])) ?></span></td>
              <td><span class="badge badge-present"><?= $day['present_count'] ?></span></td>
              <td><span class="badge badge-absent"><?= $day['absent_count'] ?></span></td>
              <td>
                <span style="font-family:var(--font-matrix);font-size:1.1rem;color:<?= $rateColor === 'green' ? 'var(--led-green)' : ($rateColor === 'yellow' ? 'var(--led-amber)' : 'var(--accent-red)') ?>;"><?= $rate ?>%</span>
                <div class="progress-bar-wrap">
                  <div class="progress-bar-fill <?= $rateColor ?>" data-width="<?= $rate ?>" style="width:0"></div>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <!-- Low Attendance Students -->
  <div class="card">
    <div class="card-header">
      <h3>
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="var(--accent-red)" viewBox="0 0 16 16">
          <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
        </svg>
        LOW ATTENDANCE (&lt;75%)
      </h3>
    </div>
    <?php if (empty($lowAttendance)): ?>
      <div class="empty-state">
        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" viewBox="0 0 16 16">
          <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
        </svg>
        <h4>ALL GOOD</h4>
        <p>No students below 75% threshold.</p>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Student</th>
              <th>Class</th>
              <th>Rate</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($lowAttendance as $s):
              $pct = $s['total_days'] > 0 ? round(($s['attended'] / $s['total_days']) * 100, 1) : 0;
            ?>
            <tr>
              <td>
                <div style="display:flex;align-items:center;gap:0.5rem;">
                  <div class="student-avatar" style="width:28px;height:28px;font-size:0.7rem;">
                    <?= strtoupper(substr($s['name'], 0, 2)) ?>
                  </div>
                  <span class="fw-semibold"><?= e($s['name']) ?></span>
                </div>
              </td>
              <td><?= e($s['class']) ?></td>
              <td>
                <span style="font-family:var(--font-matrix);font-size:1.1rem;color:var(--accent-red);"><?= $pct ?>%</span>
                <div class="progress-bar-wrap">
                  <div class="progress-bar-fill red" data-width="<?= $pct ?>" style="width:0"></div>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

</div>

<?php require_once '../includes/page_footer.php'; ?>
