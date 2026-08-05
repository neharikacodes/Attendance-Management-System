<?php
/**
 * head_teacher/view_attendance.php
 * View, Filter, Edit, Delete Attendance Records — Nothing OS Design
 */

ob_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/flash.php';

requireRole('head_teacher', '../index.php');

$teacherId = currentTeacherId();

// Handle DELETE single record
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_record'])) {
    $attId = (int)($_POST['att_id'] ?? 0);

    $check = $pdo->prepare("
        SELECT a.id FROM attendance a
        JOIN students s ON a.student_id = s.id
        WHERE a.id = ? AND a.teacher_id = ? AND s.assigned_teacher_id = ?
    ");
    $check->execute([$attId, $teacherId, $teacherId]);

    if ($check->fetch()) {
        $del = $pdo->prepare("DELETE FROM attendance WHERE id = ?");
        $del->execute([$attId]);
        setFlash('success', 'Record deleted.');
    } else {
        setFlash('error', 'Record not found or unauthorized.');
    }

    header('Location: view_attendance.php?' . http_build_query($_GET));
    exit;
}

// Handle EDIT single record
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_record'])) {
    $attId     = (int)($_POST['att_id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';

    if (!in_array($newStatus, ['Present', 'Absent', 'Late'])) {
        setFlash('error', 'Invalid status.');
        header('Location: view_attendance.php');
        exit;
    }

    $check = $pdo->prepare("
        SELECT a.id FROM attendance a
        JOIN students s ON a.student_id = s.id
        WHERE a.id = ? AND a.teacher_id = ? AND s.assigned_teacher_id = ?
    ");
    $check->execute([$attId, $teacherId, $teacherId]);

    if ($check->fetch()) {
        $upd = $pdo->prepare("UPDATE attendance SET status = ? WHERE id = ?");
        $upd->execute([$newStatus, $attId]);
        setFlash('success', 'Status updated.');
    } else {
        setFlash('error', 'Record not found or unauthorized.');
    }

    header('Location: view_attendance.php?' . http_build_query($_GET));
    exit;
}

// Filters
$filterDate    = trim($_GET['date']       ?? '');
$filterStudent = (int)($_GET['student_id'] ?? 0);
$filterStatus  = trim($_GET['status']     ?? '');

$validStatuses = ['Present', 'Absent', 'Late'];

$where  = ["a.teacher_id = ?", "s.assigned_teacher_id = ?"];
$params = [$teacherId, $teacherId];

if ($filterDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterDate)) {
    $where[]  = "a.date = ?";
    $params[] = $filterDate;
}

if ($filterStudent > 0) {
    $where[]  = "a.student_id = ?";
    $params[] = $filterStudent;
}

if (in_array($filterStatus, $validStatuses)) {
    $where[]  = "a.status = ?";
    $params[] = $filterStatus;
}

$whereSQL = implode(' AND ', $where);

$stmtRecs = $pdo->prepare("
    SELECT a.id, a.date, a.status, a.created_at,
           s.id AS student_id, s.name AS student_name, s.class, s.roll_number
    FROM attendance a
    JOIN students s ON a.student_id = s.id
    WHERE {$whereSQL}
    ORDER BY a.date DESC, s.name ASC
    LIMIT 200
");
$stmtRecs->execute($params);
$records = $stmtRecs->fetchAll();

$stmtSt = $pdo->prepare("SELECT id, name FROM students WHERE assigned_teacher_id = ? ORDER BY name");
$stmtSt->execute([$teacherId]);
$myStudents = $stmtSt->fetchAll();

$pageTitle = 'View Attendance Records';
require_once '../includes/sidebar_teacher.php';
?>

<?= renderFlash() ?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">
  <div>
    <h1 style="font-family:var(--font-matrix);font-size:1.4rem;font-weight:400;text-transform:uppercase;color:#ffffff;">ATTENDANCE RECORDS</h1>
    <p class="text-muted text-small"><?= count($records) ?> RECORD(S) FOUND</p>
  </div>
  <a href="attendance.php" class="btn btn-primary">
    + MARK TODAY
  </a>
</div>

<!-- ── Filter Bar ──────────────────────────────────────── -->
<div class="card mb-3">
  <div class="card-body" style="padding:0.85rem 1.25rem;">
    <form method="GET" action="view_attendance.php">
      <div style="display:flex;gap:0.75rem;flex-wrap:wrap;align-items:flex-end;">
        <div>
          <label class="form-label">DATE</label>
          <input type="date" name="date" class="form-control" value="<?= e($filterDate) ?>" style="width:auto;">
        </div>
        <div>
          <label class="form-label">STUDENT</label>
          <select name="student_id" class="form-control">
            <option value="0">ALL STUDENTS</option>
            <?php foreach ($myStudents as $st): ?>
              <option value="<?= $st['id'] ?>" <?= $filterStudent === (int)$st['id'] ? 'selected' : '' ?>><?= e($st['name']) ?></option>
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
        <?php if ($filterDate || $filterStudent || $filterStatus): ?>
          <a href="view_attendance.php" class="btn btn-secondary">CLEAR</a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<!-- ── Records Table ───────────────────────────────────── -->
<div class="card">
  <?php if (empty($records)): ?>
    <div class="empty-state">
      <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" viewBox="0 0 16 16">
        <path d="M14 2H2a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2z"/>
      </svg>
      <h4>NO RECORDS FOUND</h4>
      <p>Adjust your filter parameters or mark attendance first.</p>
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
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($records as $i => $rec): ?>
          <tr>
            <td class="text-muted"><?= $i + 1 ?></td>
            <td>
              <strong><?= date('d M Y', strtotime($rec['date'])) ?></strong>
              <div class="text-muted text-small"><?= date('l', strtotime($rec['date'])) ?></div>
            </td>
            <td>
              <div style="display:flex;align-items:center;gap:0.5rem;">
                <div class="student-avatar" style="width:28px;height:28px;font-size:0.7rem;">
                  <?= strtoupper(substr($rec['student_name'],0,2)) ?>
                </div>
                <div>
                  <div class="fw-semibold" style="color:#ffffff;"><?= e($rec['student_name']) ?></div>
                  <?php if ($rec['roll_number']): ?>
                    <div class="text-muted text-small"><?= e($rec['roll_number']) ?></div>
                  <?php endif; ?>
                </div>
              </div>
            </td>
            <td><?= e($rec['class']) ?></td>
            <td>
              <span class="badge badge-<?= strtolower($rec['status']) ?>">
                <?= e($rec['status']) ?>
              </span>
            </td>
            <td>
              <div style="display:flex;gap:0.4rem;">
                <button class="btn btn-secondary btn-sm btn-icon"
                  title="Edit Status"
                  onclick="openEditRecordModal(<?= $rec['id'] ?>, '<?= e($rec['status']) ?>', '<?= e($rec['student_name']) ?>', '<?= date('d M Y', strtotime($rec['date'])) ?>')">
                  <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10z"/>
                  </svg>
                </button>
                <form method="POST" action="view_attendance.php?<?= http_build_query(array_filter($_GET)) ?>" style="display:inline;"
                  onsubmit="return confirm('Delete this attendance record?')">
                  <input type="hidden" name="delete_record" value="1">
                  <input type="hidden" name="att_id" value="<?= $rec['id'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm btn-icon" title="Delete">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" viewBox="0 0 16 16">
                      <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                      <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1z"/>
                    </svg>
                  </button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<!-- ── Edit Record Modal ─────────────────────────────── -->
<div class="modal-overlay" id="editRecordModal">
  <div class="modal-box" style="max-width:380px;">
    <div class="modal-header">
      <h4>UPDATE STATUS</h4>
      <button class="modal-close" onclick="closeModal('editRecordModal')">&times;</button>
    </div>
    <form method="POST" action="view_attendance.php?<?= http_build_query(array_filter($_GET)) ?>">
      <input type="hidden" name="update_record" value="1">
      <input type="hidden" name="att_id" id="editRecordId">
      <div class="modal-body">
        <p class="text-muted text-small mb-2" id="editRecordInfo"></p>
        <div class="form-group">
          <label class="form-label">STATUS</label>
          <select name="status" id="editRecordStatus" class="form-control">
            <option value="Present">Present</option>
            <option value="Late">Late</option>
            <option value="Absent">Absent</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('editRecordModal')">CANCEL</button>
        <button type="submit" class="btn btn-primary">UPDATE</button>
      </div>
    </form>
  </div>
</div>

<?php
$jsExtra = <<<JS
<script>
function openEditRecordModal(id, status, studentName, date) {
  document.getElementById('editRecordId').value = id;
  document.getElementById('editRecordStatus').value = status;
  document.getElementById('editRecordInfo').textContent = studentName + ' — ' + date;
  openModal('editRecordModal');
}
</script>
JS;
require_once '../includes/page_footer.php';
?>
