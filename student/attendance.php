<?php
/**
 * student/attendance.php
 * Student Own Attendance Records — Nothing OS Design
 */

ob_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/flash.php';

requireRole('student', '../index.php');

$studentId = currentStudentId();

// Filters
$startDate = trim($_GET['start_date'] ?? '');
$endDate   = trim($_GET['end_date']   ?? '');
$status    = trim($_GET['status']     ?? '');

$where = ["a.student_id = ?"];
$params = [$studentId];

if (!empty($startDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
    $where[] = "a.date >= ?";
    $params[] = $startDate;
}

if (!empty($endDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
    $where[] = "a.date <= ?";
    $params[] = $endDate;
}

if (in_array($status, ['Present', 'Absent', 'Late'])) {
    $where[] = "a.status = ?";
    $params[] = $status;
}

$whereSQL = implode(' AND ', $where);

// Fetch records
$stmtRecs = $pdo->prepare("
    SELECT a.id, a.date, a.status, a.created_at,
           t.name AS teacher_name
    FROM attendance a
    LEFT JOIN teachers t ON a.teacher_id = t.id
    WHERE {$whereSQL}
    ORDER BY a.date DESC
");
$stmtRecs->execute($params);
$records = $stmtRecs->fetchAll();

// Overall Stats
$stmtStats = $pdo->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) AS present,
        SUM(CASE WHEN status = 'Absent'  THEN 1 ELSE 0 END) AS absent,
        SUM(CASE WHEN status = 'Late'    THEN 1 ELSE 0 END) AS late,
        SUM(CASE WHEN status = 'Present' OR status = 'Late' THEN 1 ELSE 0 END) AS attended
    FROM attendance
    WHERE student_id = ?
");
$stmtStats->execute([$studentId]);
$stats = $stmtStats->fetch();

$total    = (int)($stats['total']    ?? 0);
$present  = (int)($stats['present']  ?? 0);
$absent   = (int)($stats['absent']   ?? 0);
$late     = (int)($stats['late']     ?? 0);
$attended = (int)($stats['attended'] ?? 0);

$pct = $total > 0 ? round(($attended / $total) * 100, 1) : 0;
$pctColor = $pct >= 75 ? 'var(--led-green)' : ($pct >= 50 ? 'var(--led-amber)' : 'var(--accent-red)');

$pageTitle = 'My Attendance History';
require_once '../includes/sidebar_student.php';
?>

<?= renderFlash() ?>

<!-- ── Summary Bar ──────────────────────────────────────── -->
<div class="stat-cards" style="margin-bottom:1.5rem;">
  <div class="stat-card blue">
    <div class="stat-icon blue">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
        <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5z"/>
      </svg>
    </div>
    <div class="stat-info">
      <div class="stat-value"><?= $total ?></div>
      <div class="stat-label">Total Days</div>
    </div>
  </div>

  <div class="stat-card green">
    <div class="stat-icon green">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
      </svg>
    </div>
    <div class="stat-info">
      <div class="stat-value"><?= $present ?></div>
      <div class="stat-label">Present</div>
    </div>
  </div>

  <div class="stat-card red">
    <div class="stat-icon red">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM5.354 4.646a.5.5 0 1 0-.708.708L7.293 8l-2.647 2.646a.5.5 0 0 0 .708.708L8 8.707l2.646 2.647a.5.5 0 0 0 .708-.708L8.707 8l2.647-2.646a.5.5 0 0 0-.708-.708L8 7.293 5.354 4.646z"/>
      </svg>
    </div>
    <div class="stat-info">
      <div class="stat-value"><?= $absent ?></div>
      <div class="stat-label">Absent</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon" style="color:<?= $pctColor ?>;border-color:<?= $pctColor ?>;">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
      </svg>
    </div>
    <div class="stat-info">
      <div class="stat-value" style="color:<?= $pctColor ?>;"><?= $pct ?>%</div>
      <div class="stat-label">Percentage</div>
    </div>
  </div>
</div>

<!-- ── Filter Bar ──────────────────────────────────────── -->
<div class="card mb-3">
  <div class="card-body" style="padding:0.85rem 1.25rem;">
    <form method="GET" action="attendance.php">
      <div style="display:flex;gap:0.75rem;flex-wrap:wrap;align-items:flex-end;">
        <div>
          <label class="form-label">FROM DATE</label>
          <input type="date" name="start_date" class="form-control" value="<?= e($startDate) ?>" style="width:auto;">
        </div>
        <div>
          <label class="form-label">TO DATE</label>
          <input type="date" name="end_date" class="form-control" value="<?= e($endDate) ?>" style="width:auto;">
        </div>
        <div>
          <label class="form-label">STATUS</label>
          <select name="status" class="form-control">
            <option value="">ALL STATUSES</option>
            <option value="Present" <?= $status === 'Present' ? 'selected' : '' ?>>Present</option>
            <option value="Absent"  <?= $status === 'Absent'  ? 'selected' : '' ?>>Absent</option>
            <option value="Late"    <?= $status === 'Late'    ? 'selected' : '' ?>>Late</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary">FILTER</button>
        <?php if ($startDate || $endDate || $status): ?>
          <a href="attendance.php" class="btn btn-secondary">CLEAR</a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<!-- ── Records Table ───────────────────────────────────── -->
<div class="card">
  <div class="card-header">
    <h3>DATE-WISE ATTENDANCE LOG</h3>
    <span class="text-muted text-small"><?= count($records) ?> ENTRY(IES)</span>
  </div>

  <?php if (empty($records)): ?>
    <div class="empty-state">
      <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" viewBox="0 0 16 16">
        <path d="M14 2H2a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2z"/>
      </svg>
      <h4>NO RECORDS FOUND</h4>
      <p>No attendance entries match your filters.</p>
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Date</th>
            <th>Day</th>
            <th>Marked By</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($records as $i => $rec): ?>
          <tr>
            <td class="text-muted"><?= $i + 1 ?></td>
            <td><strong><?= date('d M Y', strtotime($rec['date'])) ?></strong></td>
            <td class="text-muted"><?= date('l', strtotime($rec['date'])) ?></td>
            <td><?= e($rec['teacher_name'] ?? 'Head Teacher') ?></td>
            <td>
              <span class="badge badge-<?= strtolower($rec['status']) ?>">
                <?= e($rec['status']) ?>
              </span>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require_once '../includes/page_footer.php'; ?>
