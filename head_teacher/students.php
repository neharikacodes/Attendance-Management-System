<?php
/**
 * head_teacher/students.php
 * Manage Students — Nothing OS Design
 */

ob_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/flash.php';

requireRole('head_teacher', '../index.php');

$teacherId = currentTeacherId();
$errors    = [];

// ── Handle POST Actions ───────────────────────────────────
$action = $_POST['action'] ?? '';

// ADD STUDENT
if ($action === 'add') {
    $name        = trim($_POST['name'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $class       = trim($_POST['class'] ?? '');
    $roll        = trim($_POST['roll_number'] ?? '');
    $assignTo    = (int)($_POST['assigned_teacher_id'] ?? 0);
    $username    = trim($_POST['username'] ?? '');
    $password    = $_POST['password'] ?? '';

    if (empty($name))    $errors[] = 'Student name is required.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if (empty($class))   $errors[] = 'Class is required.';
    if (empty($username)) $errors[] = 'Login username is required.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';

    if (empty($errors)) {
        $chk = $pdo->prepare("SELECT id FROM students WHERE email = ?");
        $chk->execute([$email]);
        if ($chk->fetch()) $errors[] = 'A student with this email already exists.';

        $chkU = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $chkU->execute([$username]);
        if ($chkU->fetch()) $errors[] = 'This username is already taken.';
    }

    if (empty($errors)) {
        $pdo->beginTransaction();
        try {
            $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $ins = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'student')");
            $ins->execute([$username, $hashed]);
            $userId = (int)$pdo->lastInsertId();

            $insSt = $pdo->prepare("
                INSERT INTO students (user_id, name, email, class, roll_number, assigned_teacher_id)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $insSt->execute([
                $userId, $name, $email, $class,
                empty($roll) ? null : $roll,
                $assignTo > 0 ? $assignTo : $teacherId
            ]);

            $pdo->commit();
            setFlash('success', "Student '{$name}' created.");
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }

    if (!empty($errors)) {
        setFlash('error', implode(' ', $errors));
    }

    header('Location: students.php');
    exit;
}

// EDIT STUDENT
if ($action === 'edit') {
    $sid   = (int)($_POST['student_id'] ?? 0);
    $name  = trim($_POST['name']  ?? '');
    $email = trim($_POST['email'] ?? '');
    $class = trim($_POST['class'] ?? '');
    $roll  = trim($_POST['roll_number'] ?? '');
    $assign = (int)($_POST['assigned_teacher_id'] ?? 0);

    if (empty($name) || empty($email) || empty($class)) {
        setFlash('error', 'Name, email and class are required.');
        header('Location: students.php');
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlash('error', 'Please provide a valid email address.');
        header('Location: students.php');
        exit;
    }

    $upd = $pdo->prepare("
        UPDATE students
        SET name = ?, email = ?, class = ?, roll_number = ?, assigned_teacher_id = ?
        WHERE id = ?
    ");
    $upd->execute([
        $name, $email, $class,
        empty($roll) ? null : $roll,
        $assign > 0 ? $assign : $teacherId,
        $sid
    ]);

    setFlash('success', "Student updated.");
    header('Location: students.php');
    exit;
}

// DELETE STUDENT
if ($action === 'delete') {
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

// Fetch Data
$allTeachers = $pdo->query("SELECT id, name FROM teachers ORDER BY name")->fetchAll();

$stmtStudents = $pdo->prepare("
    SELECT s.*,
           COUNT(a.id)                                                         AS total_days,
           SUM(CASE WHEN a.status = 'Present' OR a.status = 'Late' THEN 1 ELSE 0 END) AS attended
    FROM students s
    LEFT JOIN attendance a ON s.id = a.student_id
    WHERE s.assigned_teacher_id = ?
    GROUP BY s.id, s.name, s.user_id, s.email, s.class, s.roll_number, s.assigned_teacher_id, s.created_at
    ORDER BY s.name ASC
");
$stmtStudents->execute([$teacherId]);
$students = $stmtStudents->fetchAll();

$pageTitle = 'Manage Students';
require_once '../includes/sidebar_teacher.php';
?>

<?= renderFlash() ?>

<!-- ── Header Row ──────────────────────────────────────── -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">
  <div>
    <h1 style="font-family:var(--font-matrix);font-size:1.4rem;font-weight:400;text-transform:uppercase;color:#ffffff;">STUDENTS DIRECTORY</h1>
    <p class="text-muted text-small"><?= count($students) ?> STUDENT(S) ASSIGNED TO YOU</p>
  </div>
  <button class="btn btn-primary" onclick="openModal('addStudentModal')">
    + ADD STUDENT
  </button>
</div>

<!-- ── Search ──────────────────────────────────────────── -->
<div class="card mb-3">
  <div class="card-body" style="padding:0.85rem 1.25rem;">
    <div class="search-wrap">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
        <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.099zm-5.44 1.398a5.5 5.5 0 1 1 0-11 5.5 5.5 0 0 1 0 11z"/>
      </svg>
      <input type="text" id="tableSearch" class="form-control" placeholder="SEARCH BY NAME, CLASS, ROLL NO..." style="padding-left:2.5rem;">
    </div>
  </div>
</div>

<!-- ── Students Table ──────────────────────────────────── -->
<div class="card">
  <div class="table-responsive">
    <?php if (empty($students)): ?>
      <div class="empty-state">
        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" viewBox="0 0 16 16">
          <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
        </svg>
        <h4>NO STUDENTS FOUND</h4>
        <p>Click "Add Student" to create a new record.</p>
      </div>
    <?php else: ?>
      <table class="data-table">
        <thead>
          <tr>
            <th>Student</th>
            <th>Roll No.</th>
            <th>Class</th>
            <th>Email</th>
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
            <td>
              <div style="display:flex;align-items:center;gap:0.75rem;">
                <div class="student-avatar"><?= strtoupper(substr($s['name'],0,2)) ?></div>
                <div>
                  <div class="fw-semibold" style="color:#ffffff;"><?= e($s['name']) ?></div>
                  <div class="text-muted text-small"><?= date('d M Y', strtotime($s['created_at'])) ?></div>
                </div>
              </div>
            </td>
            <td><?= $s['roll_number'] ? e($s['roll_number']) : '<span class="text-muted">—</span>' ?></td>
            <td><?= e($s['class']) ?></td>
            <td><?= e($s['email']) ?></td>
            <td>
              <?php if ($s['total_days'] > 0): ?>
                <div style="display:flex;align-items:center;gap:0.5rem;">
                  <span style="font-family:var(--font-matrix);font-size:1.1rem;color:<?= $pctColor ?>;"><?= $pct ?>%</span>
                </div>
                <div class="progress-bar-wrap" style="width:100px;">
                  <div class="progress-bar-fill" style="width:<?= $pct ?>%;background:<?= $pctColor ?>;"></div>
                </div>
              <?php else: ?>
                <span class="text-muted text-small">NO DATA</span>
              <?php endif; ?>
            </td>
            <td>
              <div style="display:flex;gap:0.4rem;">
                <button class="btn btn-secondary btn-sm btn-icon"
                  title="Edit Student"
                  onclick="openEditModal(<?= htmlspecialchars(json_encode($s), ENT_QUOTES) ?>)">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10z"/>
                  </svg>
                </button>
                <button class="btn btn-danger btn-sm btn-icon"
                  title="Delete Student"
                  onclick="confirmDeleteStudent(<?= $s['id'] ?>, '<?= e($s['name']) ?>')">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                    <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1z"/>
                  </svg>
                </button>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div id="noSearchResult" style="display:none;" class="empty-state">
        <h4>NO RESULTS MATCHED</h4>
        <p>Try searching for a different term.</p>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- ─────────── ADD STUDENT MODAL ─────────── -->
<div class="modal-overlay" id="addStudentModal">
  <div class="modal-box">
    <div class="modal-header">
      <h4>ADD NEW STUDENT</h4>
      <button class="modal-close" onclick="closeModal('addStudentModal')">&times;</button>
    </div>
    <form method="POST" action="students.php">
      <input type="hidden" name="action" value="add">
      <div class="modal-body">
        <div class="form-section-title">STUDENT DETAILS</div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Full Name *</label>
            <input type="text" name="name" class="form-control" placeholder="Alice Smith" required>
          </div>
          <div class="form-group">
            <label class="form-label">Email *</label>
            <input type="email" name="email" class="form-control" placeholder="alice@student.edu" required>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Class *</label>
            <input type="text" name="class" class="form-control" placeholder="Class 10-A" required>
          </div>
          <div class="form-group">
            <label class="form-label">Roll Number</label>
            <input type="text" name="roll_number" class="form-control" placeholder="STU-001">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Assign to Teacher</label>
          <select name="assigned_teacher_id" class="form-control">
            <?php foreach ($allTeachers as $t): ?>
              <option value="<?= $t['id'] ?>" <?= $t['id'] == $teacherId ? 'selected' : '' ?>><?= e($t['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="divider"></div>
        <div class="form-section-title">SYSTEM CREDENTIALS</div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Username *</label>
            <input type="text" name="username" class="form-control" placeholder="username" required>
          </div>
          <div class="form-group">
            <label class="form-label">Password *</label>
            <input type="password" name="password" class="form-control" placeholder="Min 6 chars" required minlength="6">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('addStudentModal')">CANCEL</button>
        <button type="submit" class="btn btn-primary">ADD STUDENT</button>
      </div>
    </form>
  </div>
</div>

<!-- ─────────── EDIT STUDENT MODAL ─────────── -->
<div class="modal-overlay" id="editStudentModal">
  <div class="modal-box">
    <div class="modal-header">
      <h4>EDIT STUDENT</h4>
      <button class="modal-close" onclick="closeModal('editStudentModal')">&times;</button>
    </div>
    <form method="POST" action="students.php">
      <input type="hidden" name="action" value="edit">
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
          <label class="form-label">Assign to Teacher</label>
          <select name="assigned_teacher_id" id="editTeacher" class="form-control">
            <?php foreach ($allTeachers as $t): ?>
              <option value="<?= $t['id'] ?>"><?= e($t['name']) ?></option>
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

<!-- ─────────── DELETE CONFIRM MODAL ─────────── -->
<div class="modal-overlay" id="deleteConfirmModal">
  <div class="modal-box">
    <div class="modal-header">
      <h4>CONFIRM DELETE</h4>
      <button class="modal-close" onclick="closeModal('deleteConfirmModal')">&times;</button>
    </div>
    <div class="modal-body">
      <div class="confirm-dialog">
        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" viewBox="0 0 16 16">
          <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
        </svg>
        <h4>DELETE STUDENT RECORD?</h4>
        <p id="deleteConfirmText">This will permanently remove the student and all attendance data.</p>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" onclick="closeModal('deleteConfirmModal')">CANCEL</button>
      <form id="deleteStudentForm" method="POST" action="students.php" style="display:inline;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="student_id" id="deleteStudentId">
        <button type="submit" class="btn btn-danger">DELETE</button>
      </form>
    </div>
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

function confirmDeleteStudent(id, name) {
  document.getElementById('deleteStudentId').value = id;
  document.getElementById('deleteConfirmText').textContent =
    'Delete student "' + name + '"? This will remove all associated attendance data.';
  openModal('deleteConfirmModal');
}
</script>
JS;

require_once '../includes/page_footer.php';
?>
