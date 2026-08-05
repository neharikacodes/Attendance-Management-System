<?php
/**
 * student/dashboard.php
 * Student Dashboard — Nothing OS Design
 */

ob_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/flash.php';

requireRole('student', '../index.php');

$studentId = currentStudentId();

// ── Attendance Stats ──────────────────────────────────────
$stmtStats = $pdo->prepare("
    SELECT
        COUNT(*)                                                                AS total,
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END)                   AS present,
        SUM(CASE WHEN status = 'Absent'  THEN 1 ELSE 0 END)                   AS absent,
        SUM(CASE WHEN status = 'Late'    THEN 1 ELSE 0 END)                   AS late,
        SUM(CASE WHEN status = 'Present' OR status = 'Late' THEN 1 ELSE 0 END) AS attended
    FROM attendance
    WHERE student_id = ?
");
$stmtStats->execute([$studentId]);
$stats = $stmtStats->fetch();

$total    = (int)($stats['total']   ?? 0);
$present  = (int)($stats['present'] ?? 0);
$absent   = (int)($stats['absent']  ?? 0);
$late     = (int)($stats['late']    ?? 0);
$attended = (int)($stats['attended']?? 0);

$pct = $total > 0 ? round(($attended / $total) * 100, 1) : 0;

$circumference = 339.3;
$dashOffset    = $circumference - ($pct / 100) * $circumference;

$gaugeColor = $pct >= 75 ? '#00ff66' : ($pct >= 50 ? '#ffaa00' : '#ff0037');

// Recent Attendance
$stmtRecent = $pdo->prepare("
    SELECT date, status
    FROM attendance
    WHERE student_id = ?
    ORDER BY date DESC
    LIMIT 10
");
$stmtRecent->execute([$studentId]);
$recentRecords = $stmtRecent->fetchAll();

// Streak calculation
$stmtStreak = $pdo->prepare("
    SELECT date, status FROM attendance
    WHERE student_id = ?
    ORDER BY date DESC
    LIMIT 30
");
$stmtStreak->execute([$studentId]);
$streakRows = $stmtStreak->fetchAll();
$streak = 0;
$prevDate = null;
foreach ($streakRows as $row) {
    if ($row['status'] !== 'Present' && $row['status'] !== 'Late') break;
    if ($prevDate !== null) {
        $diff = (new DateTime($prevDate))->diff(new DateTime($row['date']))->days;
        if ($diff > 1) break;
    }
    $prevDate = $row['date'];
    $streak++;
}

// Student Profile
$stmtProfile = $pdo->prepare("SELECT s.*, t.name AS teacher_name FROM students s LEFT JOIN teachers t ON s.assigned_teacher_id = t.id WHERE s.id = ?");
$stmtProfile->execute([$studentId]);
$profile = $stmtProfile->fetch();

$pageTitle = 'My Dashboard';
require_once '../includes/sidebar_student.php';
?>

<?= renderFlash() ?>

<!-- ── Welcome Hardware Card ───────────────────────────── -->
<div style="background:#0a0a0a;border:1px solid var(--border-light);border-radius:var(--radius-md);padding:1.5rem;margin-bottom:1.75rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;position:relative;overflow:hidden;">
  <div style="position:absolute;top:0;left:0;bottom:0;width:3px;background:var(--accent-red);box-shadow:var(--glow-red);"></div>
  <div style="padding-left:0.5rem;">
    <div style="font-family:var(--font-matrix);font-size:1.3rem;letter-spacing:0.04em;text-transform:uppercase;color:#ffffff;margin-bottom:0.2rem;">
      HELLO, <?= e(strtoupper(explode(' ', currentUserName())[0])) ?>
    </div>
    <div style="font-family:var(--font-tech);font-size:0.78rem;color:var(--text-muted);letter-spacing:0.05em;text-transform:uppercase;">
      <?= date('l, d F Y') ?> &nbsp;·&nbsp; <?= e($profile['class'] ?? '') ?>
    </div>
  </div>
  <a href="attendance.php" class="btn btn-secondary">
    HISTORY →
  </a>
</div>

<!-- ── Stat Cards ──────────────────────────────────────── -->
<div class="stat-cards" style="margin-bottom:1.75rem;">
  <div class="stat-card blue">
    <div class="stat-icon blue">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
        <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5z"/>
      </svg>
    </div>
    <div class="stat-info">
      <div class="stat-value" data-count="<?= $total ?>"><?= $total ?></div>
      <div class="stat-label">Total Days</div>
      <div class="stat-sub">Recorded</div>
    </div>
  </div>

  <div class="stat-card green">
    <div class="stat-icon green">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
      </svg>
    </div>
    <div class="stat-info">
      <div class="stat-value" data-count="<?= $present ?>"><?= $present ?></div>
      <div class="stat-label">Present</div>
      <div class="stat-sub"><?= $late > 0 ? "+ {$late} late" : 'Attended' ?></div>
    </div>
  </div>

  <div class="stat-card red">
    <div class="stat-icon red">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM5.354 4.646a.5.5 0 1 0-.708.708L7.293 8l-2.647 2.646a.5.5 0 0 0 .708.708L8 8.707l2.646 2.647a.5.5 0 0 0 .708-.708L8.707 8l2.647-2.646a.5.5 0 0 0-.708-.708L8 7.293 5.354 4.646z"/>
      </svg>
    </div>
    <div class="stat-info">
      <div class="stat-value" data-count="<?= $absent ?>"><?= $absent ?></div>
      <div class="stat-label">Absent</div>
      <div class="stat-sub">Missed</div>
    </div>
  </div>

  <div class="stat-card purple">
    <div class="stat-icon purple">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
        <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
        <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
      </svg>
    </div>
    <div class="stat-info">
      <div class="stat-value"><?= $streak ?></div>
      <div class="stat-label">Streak</div>
      <div class="stat-sub">Consecutive</div>
    </div>
  </div>
</div>

<!-- ── Bottom Grid ─────────────────────────────────────── -->
<div style="display:grid;grid-template-columns:auto 1fr;gap:1.5rem;align-items:start;">

  <!-- Attendance Gauge -->
  <div class="card" style="min-width:220px;">
    <div class="card-header"><h3>ATTENDANCE %</h3></div>
    <div class="card-body">
      <div class="gauge-container">
        <svg class="gauge-svg" width="140" height="140" viewBox="0 0 140 140">
          <circle class="gauge-track" cx="70" cy="70" r="54"/>
          <circle class="gauge-fill" id="gaugeFill" cx="70" cy="70" r="54"
            stroke="<?= $gaugeColor ?>"
            stroke-dasharray="<?= $circumference ?>"
            stroke-dashoffset="<?= $circumference ?>"
            data-pct="<?= $pct ?>"
            data-circ="<?= $circumference ?>"/>
        </svg>
        <div class="gauge-label">
          <div class="gauge-percent" style="color:<?= $gaugeColor ?>;"><?= $pct ?>%</div>
          <div class="gauge-sub">RATE</div>
        </div>

        <?php
          $status = 'EXCELLENT';
          $statusColor = 'var(--led-green)';
          if ($pct < 75) { $status = 'BELOW THRESHOLD'; $statusColor = 'var(--accent-red)'; }
          elseif ($pct < 85) { $status = 'NEEDS IMPROVEMENT'; $statusColor = 'var(--led-amber)'; }
          elseif ($pct < 95) { $status = 'GOOD'; $statusColor = 'var(--led-green)'; }
        ?>
        <span class="badge" style="margin-top:0.75rem;border-color:<?= $statusColor ?>;color:<?= $statusColor ?>;">
          <?= $status ?>
        </span>

        <?php if ($pct < 75): ?>
          <div style="margin-top:0.75rem;padding:0.6rem;background:var(--led-red-bg);border:1px solid var(--accent-red);border-radius:4px;text-align:center;font-family:var(--font-tech);font-size:0.72rem;color:var(--accent-red);">
            ⚠️ Below 75% threshold
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Recent Records -->
  <div class="card">
    <div class="card-header">
      <h3>
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="var(--text-main)" viewBox="0 0 16 16">
          <path d="M14 2H2a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2z"/>
        </svg>
        RECENT ATTENDANCE
      </h3>
      <a href="attendance.php" class="btn btn-secondary btn-sm">VIEW ALL</a>
    </div>

    <?php if (empty($recentRecords)): ?>
      <div class="empty-state">
        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" viewBox="0 0 16 16">
          <path d="M14 2H2a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2z"/>
        </svg>
        <h4>NO RECORDS YET</h4>
        <p>Your attendance hasn't been recorded yet.</p>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Day</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentRecords as $rec): ?>
            <tr>
              <td><strong><?= date('d M Y', strtotime($rec['date'])) ?></strong></td>
              <td class="text-muted"><?= date('l', strtotime($rec['date'])) ?></td>
              <td><span class="badge badge-<?= strtolower($rec['status']) ?>"><?= e($rec['status']) ?></span></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

</div>

<?php require_once '../includes/page_footer.php'; ?>
