<?php
/**
 * admin/reports.php
 * School-wide Attendance Reports — Super Admin — Nothing OS Design
 */

ob_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/flash.php';

requireRole('admin', '../index.php');

$filterDate    = trim($_GET['date']       ?? '');
$filterTeacher = (int)($_GET['teacher_id'] ?? 0);
$filterClass   = trim($_GET['class']      ?? '');
$filterStatus  = trim($_GET['status']     ?? '');

$where = ["1=1"];
$params = [];

if ($filterDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterDate)) {
    $where[]  = "a.date = ?";
    $params[] = $filterDate;
}

if ($filterTeacher > 0) {
    $where[]  = "a.teacher_id = ?";
    $params[] = $filterTeacher;
}

if (!empty($filterClass)) {
    $where[]  = "s.class = ?";
    $params[] = $filterClass;
}

if (in_array($filterStatus, ['Present', 'Absent', 'Late'])) {
    $where[]  = "a.status = ?";
    $params[] = $filterStatus;
}

$whereSQL = implode(' AND ', $where);

// Fetch records
$stmtRecs = $pdo->prepare("
    SELECT a.id, a.date, a.status, a.created_at,
           s.name AS student_name, s.class, s.roll_number,
           t.name AS teacher_name
    FROM attendance a
    JOIN students s ON a.student_id = s.id
    JOIN teachers t ON a.teacher_id = t.id
    WHERE {$whereSQL}
    ORDER BY a.date DESC, s.name ASC
    LIMIT 300
");
$stmtRecs->execute($params);
$records = $stmtRecs->fetchAll();

$allTeachers = $pdo->query("SELECT id, name, assigned_class FROM teachers ORDER BY name")->fetchAll();

$pageTitle = 'School Attendance Reports';
require_once '../includes/sidebar_admin.php';
?>

<?= renderFlash() ?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">
  <div>
    <h1 style="font-family:var(--font-matrix);font-size:1.4rem;font-weight:400;text-transform:uppercase;color:#ffffff;">SCHOOL-WIDE ATTENDANCE REPORTS</h1>
    <p class="text-muted text-small"><?= count($records) ?> RECORD(S) FOUND</p>
  </div>
</div>

<!-- Filter Bar -->
<div class="card mb-3">
  <div class="card-body" style="padding:0.85rem 1.25rem;">
    <form method="GET" action="reports.php">
      <div style="display:flex;gap:0.75rem;flex-wrap:wrap;align-items:flex-end;">
        <div>
          <label class="form-label">DATE</label>
          <input type="date" name="date" class="form-control" value="<?= e($filterDate) ?>" style="width:auto;">
        </div>
        <div>
          <label class="form-label">HEAD TEACHER</label>
          <select name="teacher_id" class="form-control">
            <option value="0">ALL TEACHERS</option>
            <?php foreach ($allTeachers as $t): ?>
              <option value="<?= $t['id'] ?>" <?= $filterTeacher === (int)$t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?> (<?= e($t['assigned_class'] ?? 'General') ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="form-label">STATUS</label>
          <select name="status" class="form-control">
            <option value="">ALL STATUSES</option>
            <option value="Present" <?= $filterStatus === 'Present' ? 'selected' : '' ?>>Present</option>
            <option value="Absent"  <?= $filterStatus === 'Absent'  ? 'selected' : '' ?>>Absent</option>
            <option value="Late"    <?= $filterStatus === 'Late'    ? 'selected' : '' ?>>Late</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary">FILTER</button>
        <?php if ($filterDate || $filterTeacher || $filterStatus): ?>
          <a href="reports.php" class="btn btn-secondary">CLEAR</a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<!-- Table -->
<div class="card">
  <?php if (empty($records)): ?>
    <div class="empty-state">
      <h4>NO RECORDS FOUND</h4>
      <p>No attendance data matches your query filters.</p>
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Date</th>
            <th>Student</th>
            <th>Class</th>
            <th>Marked By (Head Teacher)</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($records as $i => $rec): ?>
          <tr>
            <td class="text-muted"><?= $i + 1 ?></td>
            <td><strong><?= date('d M Y', strtotime($rec['date'])) ?></strong></td>
            <td><strong style="color:#ffffff;"><?= e($rec['student_name']) ?></strong></td>
            <td><span class="badge badge-primary"><?= e($rec['class']) ?></span></td>
            <td><?= e($rec['teacher_name']) ?></td>
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
