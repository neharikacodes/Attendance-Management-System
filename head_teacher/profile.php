<?php
/**
 * head_teacher/profile.php
 * Head Teacher Profile Page — Nothing OS Design
 */

ob_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/flash.php';

requireRole('head_teacher', '../index.php');

$userId    = currentUserId();
$teacherId = currentTeacherId();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name  = trim($_POST['name']  ?? '');
    $email = trim($_POST['email'] ?? '');
    $dept  = trim($_POST['department'] ?? '');

    if (empty($name) || empty($email)) {
        setFlash('error', 'Name and email are required.');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlash('error', 'Please enter a valid email address.');
    } else {
        $upd = $pdo->prepare("UPDATE teachers SET name = ?, email = ?, department = ? WHERE id = ?");
        $upd->execute([$name, $email, $dept, $teacherId]);
        $_SESSION['display_name'] = $name;
        $_SESSION['department']   = $dept;
        setFlash('success', 'Profile updated successfully.');
    }

    header('Location: profile.php');
    exit;
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password']     ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    // Get current hash
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!password_verify($current, $user['password'])) {
        setFlash('error', 'Current password is incorrect.');
    } elseif (strlen($new) < 6) {
        setFlash('error', 'New password must be at least 6 characters.');
    } elseif ($new !== $confirm) {
        setFlash('error', 'New passwords do not match.');
    } else {
        $hash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
        $upd  = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $upd->execute([$hash, $userId]);
        setFlash('success', 'Password changed successfully.');
    }

    header('Location: profile.php');
    exit;
}

// Fetch profile
$stmtT = $pdo->prepare("SELECT t.*, u.username FROM teachers t JOIN users u ON t.user_id = u.id WHERE t.id = ?");
$stmtT->execute([$teacherId]);
$teacher = $stmtT->fetch();

// Stats
$stmtSt  = $pdo->prepare("SELECT COUNT(*) FROM students WHERE assigned_teacher_id = ?");
$stmtSt->execute([$teacherId]);
$totalStudents = (int)$stmtSt->fetchColumn();

$stmtAtt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE teacher_id = ?");
$stmtAtt->execute([$teacherId]);
$totalAttendance = (int)$stmtAtt->fetchColumn();

$initials = implode('', array_map(fn($w) => strtoupper($w[0]), array_slice(explode(' ', $teacher['name']), 0, 2)));

$pageTitle = 'My Profile';
require_once '../includes/sidebar_teacher.php';
?>

<?= renderFlash() ?>

<div style="display:grid;grid-template-columns:1fr 2fr;gap:1.5rem;align-items:start;">

  <!-- Left: Profile card -->
  <div>
    <div class="card">
      <div class="card-body text-center" style="padding:2rem 1.5rem;">
        <div style="width:72px;height:72px;border-radius:var(--radius-sm);background:#171717;border:1px solid var(--border-light);display:flex;align-items:center;justify-content:center;font-family:var(--font-tech);font-size:1.6rem;font-weight:700;color:#ffffff;margin:0 auto 1rem;position:relative;">
          <?= e($initials) ?>
          <span style="position:absolute;top:4px;right:4px;width:6px;height:6px;background:var(--accent-red);border-radius:50%;box-shadow:0 0 6px var(--accent-red);"></span>
        </div>
        <h3 style="font-family:var(--font-matrix);font-size:1.2rem;font-weight:400;text-transform:uppercase;color:#ffffff;"><?= e($teacher['name']) ?></h3>
        <p class="text-muted text-small"><?= e($teacher['department'] ?? 'HEAD TEACHER') ?></p>
        <p class="text-muted text-small"><?= e($teacher['email']) ?></p>
        <div class="divider"></div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;text-align:center;">
          <div>
            <div style="font-family:var(--font-matrix);font-size:1.6rem;color:#ffffff;"><?= $totalStudents ?></div>
            <div class="text-muted text-small">Students</div>
          </div>
          <div>
            <div style="font-family:var(--font-matrix);font-size:1.6rem;color:var(--led-green);"><?= $totalAttendance ?></div>
            <div class="text-muted text-small">Records</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Right: Edit forms -->
  <div style="display:flex;flex-direction:column;gap:1.5rem;">

    <!-- Edit Profile -->
    <div class="card">
      <div class="card-header">
        <h3>EDIT PROFILE</h3>
      </div>
      <form method="POST" action="profile.php">
        <input type="hidden" name="update_profile" value="1">
        <div class="card-body">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Full Name *</label>
              <input type="text" name="name" class="form-control" value="<?= e($teacher['name']) ?>" required>
            </div>
            <div class="form-group">
              <label class="form-label">Email *</label>
              <input type="email" name="email" class="form-control" value="<?= e($teacher['email']) ?>" required>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Department</label>
              <input type="text" name="department" class="form-control" value="<?= e($teacher['department'] ?? '') ?>" placeholder="e.g. Computer Science">
            </div>
            <div class="form-group">
              <label class="form-label">Username</label>
              <input type="text" class="form-control" value="<?= e($teacher['username']) ?>" disabled style="background:#0d0d0d;color:var(--text-dim);">
              <p class="form-hint">Username cannot be changed.</p>
            </div>
          </div>
        </div>
        <div class="card-footer" style="display:flex;justify-content:flex-end;">
          <button type="submit" class="btn btn-primary">SAVE PROFILE</button>
        </div>
      </form>
    </div>

    <!-- Change Password -->
    <div class="card">
      <div class="card-header">
        <h3>CHANGE PASSWORD</h3>
      </div>
      <form method="POST" action="profile.php">
        <input type="hidden" name="change_password" value="1">
        <div class="card-body">
          <div class="form-group">
            <label class="form-label">Current Password *</label>
            <input type="password" name="current_password" class="form-control" required autocomplete="current-password">
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">New Password *</label>
              <input type="password" name="new_password" class="form-control" required minlength="6" autocomplete="new-password">
              <p class="form-hint">Minimum 6 characters.</p>
            </div>
            <div class="form-group">
              <label class="form-label">Confirm New Password *</label>
              <input type="password" name="confirm_password" class="form-control" required autocomplete="new-password">
            </div>
          </div>
        </div>
        <div class="card-footer" style="display:flex;justify-content:flex-end;">
          <button type="submit" class="btn btn-warning">CHANGE PASSWORD</button>
        </div>
      </form>
    </div>

  </div>
</div>

<?php require_once '../includes/page_footer.php'; ?>
