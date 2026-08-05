<?php
/**
 * admin/teachers.php
 * Manage Head Teachers & Assign Homeroom Classes — Nothing OS Design
 */

ob_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/flash.php';

requireRole('admin', '../index.php');

$errors = [];
$action = $_POST['action'] ?? '';

// ADD TEACHER
if ($action === 'add') {
    $name       = trim($_POST['name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $assignedCls= trim($_POST['assigned_class'] ?? '');
    $username   = trim($_POST['username'] ?? '');
    $password   = $_POST['password'] ?? '';

    if (empty($name))       $errors[] = 'Teacher name is required.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if (empty($username))  $errors[] = 'Username is required.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';

    if (empty($errors)) {
        $chkE = $pdo->prepare("SELECT id FROM teachers WHERE email = ?");
        $chkE->execute([$email]);
        if ($chkE->fetch()) $errors[] = 'A teacher with this email already exists.';

        $chkU = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $chkU->execute([$username]);
        if ($chkU->fetch()) $errors[] = 'This username is already taken.';
    }

    if (empty($errors)) {
        $pdo->beginTransaction();
        try {
            $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $insU = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'head_teacher')");
            $insU->execute([$username, $hashed]);
            $userId = (int)$pdo->lastInsertId();

            $insT = $pdo->prepare("INSERT INTO teachers (user_id, name, email, department, assigned_class) VALUES (?, ?, ?, ?, ?)");
            $insT->execute([$userId, $name, $email, $department, $assignedCls]);

            $pdo->commit();
            setFlash('success', "Head Teacher '{$name}' created and assigned to {$assignedCls}.");
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }

    if (!empty($errors)) {
        setFlash('error', implode(' ', $errors));
    }

    header('Location: teachers.php');
    exit;
}

// EDIT TEACHER
if ($action === 'edit') {
    $tid        = (int)($_POST['teacher_id'] ?? 0);
    $name       = trim($_POST['name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $assignedCls= trim($_POST['assigned_class'] ?? '');

    if (empty($name) || empty($email)) {
        setFlash('error', 'Name and email are required.');
        header('Location: teachers.php');
        exit;
    }

    $upd = $pdo->prepare("UPDATE teachers SET name = ?, email = ?, department = ?, assigned_class = ? WHERE id = ?");
    $upd->execute([$name, $email, $department, $assignedCls, $tid]);

    setFlash('success', 'Teacher record updated.');
    header('Location: teachers.php');
    exit;
}

// DELETE TEACHER
if ($action === 'delete') {
    $tid = (int)($_POST['teacher_id'] ?? 0);

    $get = $pdo->prepare("SELECT user_id FROM teachers WHERE id = ?");
    $get->execute([$tid]);
    $row = $get->fetch();

    $pdo->beginTransaction();
    try {
        $del = $pdo->prepare("DELETE FROM teachers WHERE id = ?");
        $del->execute([$tid]);

        if ($row && $row['user_id']) {
            $delU = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $delU->execute([$row['user_id']]);
        }

        $pdo->commit();
        setFlash('success', 'Teacher deleted.');
    } catch (Exception $e) {
        $pdo->rollBack();
        setFlash('error', 'Could not delete teacher: ' . $e->getMessage());
    }

    header('Location: teachers.php');
    exit;
}

// Fetch All Teachers
$stmt = $pdo->query("
    SELECT t.*, u.username,
           COUNT(s.id) AS student_count
    FROM teachers t
    JOIN users u ON t.user_id = u.id
    LEFT JOIN students s ON s.assigned_teacher_id = t.id
    GROUP BY t.id, t.name, t.email, t.department, t.assigned_class, t.user_id, u.username
    ORDER BY t.name ASC
");
$teachers = $stmt->fetchAll();

$pageTitle = 'Head Teachers';
require_once '../includes/sidebar_admin.php';
?>

<?= renderFlash() ?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">
  <div>
    <h1 style="font-family:var(--font-matrix);font-size:1.4rem;font-weight:400;text-transform:uppercase;color:#ffffff;">HEAD TEACHERS DIRECTORY</h1>
    <p class="text-muted text-small"><?= count($teachers) ?> HEAD TEACHER(S) CONFIGURED</p>
  </div>
  <button class="btn btn-primary" onclick="openModal('addTeacherModal')">
    + ADD HEAD TEACHER
  </button>
</div>

<!-- Teachers Table -->
<div class="card">
  <?php if (empty($teachers)): ?>
    <div class="empty-state">
      <h4>NO TEACHERS FOUND</h4>
      <p>Click "Add Head Teacher" to register your first teacher.</p>
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Assigned Class</th>
            <th>Department</th>
            <th>Email</th>
            <th>Username</th>
            <th>Students</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($teachers as $t): ?>
          <tr>
            <td><strong style="color:#ffffff;"><?= e($t['name']) ?></strong></td>
            <td><span class="badge badge-primary"><?= e($t['assigned_class'] ?? 'Unassigned') ?></span></td>
            <td><?= e($t['department'] ?? 'General') ?></td>
            <td><?= e($t['email']) ?></td>
            <td><code><?= e($t['username']) ?></code></td>
            <td><?= $t['student_count'] ?></td>
            <td>
              <div style="display:flex;gap:0.4rem;">
                <button class="btn btn-secondary btn-sm btn-icon"
                  onclick="openEditTeacherModal(<?= htmlspecialchars(json_encode($t), ENT_QUOTES) ?>)">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10z"/>
                  </svg>
                </button>
                <form method="POST" action="teachers.php" style="display:inline;" onsubmit="return confirm('Delete teacher <?= e($t['name']) ?>?')">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="teacher_id" value="<?= $t['id'] ?>">
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

<!-- ─────────── ADD TEACHER MODAL ─────────── -->
<div class="modal-overlay" id="addTeacherModal">
  <div class="modal-box">
    <div class="modal-header">
      <h4>ADD HEAD TEACHER</h4>
      <button class="modal-close" onclick="closeModal('addTeacherModal')">&times;</button>
    </div>
    <form method="POST" action="teachers.php">
      <input type="hidden" name="action" value="add">
      <div class="modal-body">
        <div class="form-section-title">TEACHER DETAILS</div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Full Name *</label>
            <input type="text" name="name" class="form-control" placeholder="Mr. David Smith" required>
          </div>
          <div class="form-group">
            <label class="form-label">Email *</label>
            <input type="email" name="email" class="form-control" placeholder="david@school.edu" required>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Department</label>
            <input type="text" name="department" class="form-control" placeholder="Computer Science">
          </div>
          <div class="form-group">
            <label class="form-label">Assigned Homeroom Class *</label>
            <input type="text" name="assigned_class" class="form-control" placeholder="e.g. Class 10-A" required>
          </div>
        </div>
        <div class="divider"></div>
        <div class="form-section-title">LOGIN PASSCODE</div>
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
        <button type="button" class="btn btn-secondary" onclick="closeModal('addTeacherModal')">CANCEL</button>
        <button type="submit" class="btn btn-primary">CREATE TEACHER</button>
      </div>
    </form>
  </div>
</div>

<!-- ─────────── EDIT TEACHER MODAL ─────────── -->
<div class="modal-overlay" id="editTeacherModal">
  <div class="modal-box">
    <div class="modal-header">
      <h4>EDIT HEAD TEACHER</h4>
      <button class="modal-close" onclick="closeModal('editTeacherModal')">&times;</button>
    </div>
    <form method="POST" action="teachers.php">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="teacher_id" id="editTeacherId">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Full Name *</label>
            <input type="text" name="name" id="editTeacherName" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">Email *</label>
            <input type="email" name="email" id="editTeacherEmail" class="form-control" required>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Department</label>
            <input type="text" name="department" id="editTeacherDept" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Assigned Class *</label>
            <input type="text" name="assigned_class" id="editTeacherClass" class="form-control" required>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('editTeacherModal')">CANCEL</button>
        <button type="submit" class="btn btn-primary">SAVE CHANGES</button>
      </div>
    </form>
  </div>
</div>

<?php
$jsExtra = <<<JS
<script>
function openEditTeacherModal(teacher) {
  document.getElementById('editTeacherId').value = teacher.id;
  document.getElementById('editTeacherName').value = teacher.name;
  document.getElementById('editTeacherEmail').value = teacher.email;
  document.getElementById('editTeacherDept').value = teacher.department || '';
  document.getElementById('editTeacherClass').value = teacher.assigned_class || '';
  openModal('editTeacherModal');
}
</script>
JS;
require_once '../includes/page_footer.php';
?>
