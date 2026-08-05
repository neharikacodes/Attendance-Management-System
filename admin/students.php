<?php
/**
 * admin/students.php
 * All Students Management — Super Admin — Nothing OS Design
 */

ob_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/flash.php';

requireRole('admin', '../index.php');

$filterClass   = trim($_GET['class']      ?? '');
$filterTeacher = (int)($_GET['teacher_id'] ?? 0);

$where = ["1=1"];
$params = [];

if (!empty($filterClass)) {
    $where[] = "s.class = ?";
    $params[] = $filterClass;
}

if ($filterTeacher > 0) {
    $where[] = "s.assigned_teacher_id = ?";
    $params[] = $filterTeacher;
}

$whereSQL = implode(' AND ', $where);

// Handle Edit / Reassign
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_student'])) {
    $sid    = (int)($_POST['student_id'] ?? 0);
    $name   = trim($_POST['name'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $class  = trim($_POST['class'] ?? '');
    $roll   = trim($_POST['roll_number'] ?? '');
    $assign = (int)($_POST['assigned_teacher_id'] ?? 0);

    if (empty($name) || empty($email) || empty($class)) {
        setFlash('error', 'Name, email, and class are required.');
    } else {
        $upd = $pdo->prepare("UPDATE students SET name = ?, email = ?, class = ?, roll_number = ?, assigned_teacher_id = ? WHERE id = ?");
        $upd->execute([$name, $email, $class, empty($roll) ? null : $roll, $assign > 0 ? $assign : null, $sid]);
        setFlash('success', 'Student record updated.');
    }

    header('Location: students.php');
    exit;
}

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_student'])) {
    $sid = (int)($_POST['student_id'] ?? 0);

    $get = $pdo->prepare("SELECT user_id FROM students WHERE id = ?");
    $get->execute([$sid]);
    $row = $get->fetch();

    $pdo->beginTransaction();
    try {
        $del = $pdo->prepare("DELETE FROM students WHERE id = ?");
        $del->execute([$sid]);

        if ($row && $row['user_id']) {
            $delU = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $delU->execute([$row['user_id']]);
        }

        $pdo->commit();
        setFlash('success', 'Student deleted.');
    } catch (Exception $e) {
        $pdo->rollBack();
        setFlash('error', 'Could not delete student: ' . $e->getMessage());
    }

    header('Location: students.php');
    exit;
}

// Fetch Students
$stmtSt = $pdo->prepare("
    SELECT s.*, t.name AS teacher_name, t.assigned_class AS teacher_class,
           COUNT(a.id) AS total_days,
           SUM(CASE WHEN a.status = 'Present' OR a.status = 'Late' THEN 1 ELSE 0 END) AS attended
    FROM students s
    LEFT JOIN teachers t ON s.assigned_teacher_id = t.id
    LEFT JOIN attendance a ON a.student_id = s.id
    WHERE {$whereSQL}
    GROUP BY s.id, s.name, s.user_id, s.email, s.class, s.roll_number, s.assigned_teacher_id, s.created_at, t.name, t.assigned_class
    ORDER BY s.class ASC, s.name ASC
");
$stmtSt->execute($params);
$students = $stmtSt->fetchAll();

// Teachers list for dropdown
$allTeachers = $pdo->query("SELECT id, name, assigned_class FROM teachers ORDER BY name")->fetchAll();

$pageTitle = 'All Students Directory';
require_once '../includes/sidebar_admin.php';
?>

<?= renderFlash() ?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">
  <div>
    <h1 style="font-family:var(--font-matrix);font-size:1.4rem;font-weight:400;text-transform:uppercase;color:#ffffff;">ALL STUDENTS DIRECTORY</h1>
    <p class="text-muted text-small"><?= count($students) ?> STUDENT(S) FOUND</p>
  </div>
</div>

<!-- Filter Bar -->
<div class="card mb-3">
  <div class="card-body" style="padding:0.85rem 1.25rem;">
    <form method="GET" action="students.php">
      <div style="display:flex;gap:0.75rem;flex-wrap:wrap;align-items:flex-end;">
        <div>
          <label class="form-label">HEAD TEACHER</label>
          <select name="teacher_id" class="form-control">
            <option value="0">ALL TEACHERS</option>
            <?php foreach ($allTeachers as $t): ?>
              <option value="<?= $t['id'] ?>" <?= $filterTeacher === (int)$t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?> (<?= e($t['assigned_class'] ?? 'General') ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div style="flex:1;min-width:200px;">
          <label class="form-label">SEARCH</label>
          <input type="text" id="tableSearch" class="form-control" placeholder="SEARCH STUDENT NAME, CLASS, ROLL NO...">
        </div>
        <button type="submit" class="btn btn-primary">FILTER</button>
        <?php if ($filterClass || $filterTeacher): ?>
          <a href="students.php" class="btn btn-secondary">CLEAR</a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<!-- Table -->
<div class="card">
  <?php if (empty($students)): ?>
    <div class="empty-state">
      <h4>NO STUDENTS FOUND</h4>
      <p>No students match your filter criteria.</p>
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Student</th>
            <th>Class</th>
            <th>Roll No.</th>
            <th>Email</th>
            <th>Assigned Head Teacher</th>
            <th>Attendance Rate</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($students as $s):
            $pct = $s['total_days'] > 0 ? round(($s['attended'] / $s['total_days']) * 100, 1) : 0;
            $pctColor = $pct >= 75 ? 'var(--led-green)' : ($pct >= 50 ? 'var(--led-amber)' : 'var(--accent-red)');
          ?>
          <tr>
            <td><strong style="color:#ffffff;"><?= e($s['name']) ?></strong></td>
            <td><span class="badge badge-primary"><?= e($s['class']) ?></span></td>
            <td><?= $s['roll_number'] ? e($s['roll_number']) : '<span class="text-muted">—</span>' ?></td>
            <td><?= e($s['email']) ?></td>
            <td><?= e($s['teacher_name'] ?? 'Unassigned') ?></td>
            <td>
              <?php if ($s['total_days'] > 0): ?>
                <span style="font-family:var(--font-matrix);font-size:1.1rem;color:<?= $pctColor ?>;"><?= $pct ?>%</span>
              <?php else: ?>
                <span class="text-muted text-small">NO DATA</span>
              <?php endif; ?>
            </td>
            <td>
              <div style="display:flex;gap:0.4rem;">
                <button class="btn btn-secondary btn-sm btn-icon"
                  onclick="openEditModal(<?= htmlspecialchars(json_encode($s), ENT_QUOTES) ?>)">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10z"/>
                  </svg>
                </button>
                <form method="POST" action="students.php" style="display:inline;" onsubmit="return confirm('Delete student <?= e($s['name']) ?>?')">
                  <input type="hidden" name="delete_student" value="1">
                  <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm btn-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
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

<!-- ─────────── EDIT STUDENT MODAL ─────────── -->
<div class="modal-overlay" id="editStudentModal">
  <div class="modal-box">
    <div class="modal-header">
      <h4>EDIT STUDENT RECORD</h4>
      <button class="modal-close" onclick="closeModal('editStudentModal')">&times;</button>
    </div>
    <form method="POST" action="students.php">
      <input type="hidden" name="update_student" value="1">
      <input type="hidden" name="student_id" id="editStudentId">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Full Name *</label>
            <input type="text" name="name" id="editName" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">Email *</label>
            <input type="email" name="email" id="editEmail" class="form-control" required>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Class *</label>
            <input type="text" name="class" id="editClass" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">Roll Number</label>
            <input type="text" name="roll_number" id="editRoll" class="form-control">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Assign to Head Teacher</label>
          <select name="assigned_teacher_id" id="editTeacher" class="form-control">
            <?php foreach ($allTeachers as $t): ?>
              <option value="<?= $t['id'] ?>"><?= e($t['name']) ?> (<?= e($t['assigned_class'] ?? 'General') ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('editStudentModal')">CANCEL</button>
        <button type="submit" class="btn btn-primary">SAVE CHANGES</button>
      </div>
    </form>
  </div>
</div>

<?php
$jsExtra = <<<JS
<script>
function openEditModal(student) {
  document.getElementById('editStudentId').value = student.id;
  document.getElementById('editName').value  = student.name;
  document.getElementById('editEmail').value = student.email;
  document.getElementById('editClass').value = student['class'];
  document.getElementById('editRoll').value  = student.roll_number || '';
  const sel = document.getElementById('editTeacher');
  if (sel) sel.value = student.assigned_teacher_id || '';
  openModal('editStudentModal');
}
</script>
JS;
require_once '../includes/page_footer.php';
?>
