<?php
/**
 * student/profile.php
 * Student Profile Page — Nothing OS Design
 */

ob_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/flash.php';

requireRole('student', '../index.php');

$userId    = currentUserId();
$studentId = currentStudentId();

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password']     ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

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

// Fetch Profile Details
$stmtS = $pdo->prepare("
    SELECT s.*, u.username, t.name AS teacher_name, t.email AS teacher_email
    FROM students s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN teachers t ON s.assigned_teacher_id = t.id
    WHERE s.id = ?
");
$stmtS->execute([$studentId]);
$student = $stmtS->fetch();

// Attendance Summary
$stmtAtt = $pdo->prepare("
    SELECT COUNT(*) AS total,
           SUM(CASE WHEN status = 'Present' OR status = 'Late' THEN 1 ELSE 0 END) AS attended
    FROM attendance WHERE student_id = ?
");
$stmtAtt->execute([$studentId]);
$attSummary = $stmtAtt->fetch();
$totalDays = (int)($attSummary['total'] ?? 0);
$attendedDays = (int)($attSummary['attended'] ?? 0);
$pct = $totalDays > 0 ? round(($attendedDays / $totalDays) * 100, 1) : 0;

$initials = implode('', array_map(fn($w) => strtoupper($w[0]), array_slice(explode(' ', $student['name'] ?? 'Student'), 0, 2)));

$pageTitle = 'My Profile';
require_once '../includes/sidebar_student.php';
?>

<?= renderFlash() ?>

<div style="display:grid;grid-template-columns:1fr 2fr;gap:1.5rem;align-items:start;">

  <!-- Left: Profile Card -->
  <div>
    <div class="card">
      <div class="card-body text-center" style="padding:2rem 1.5rem;">
        <div style="width:72px;height:72px;border-radius:var(--radius-sm);background:#171717;border:1px solid var(--border-light);display:flex;align-items:center;justify-content:center;font-family:var(--font-tech);font-size:1.6rem;font-weight:700;color:#ffffff;margin:0 auto 1rem;position:relative;">
          <?= e($initials) ?>
          <span style="position:absolute;top:4px;right:4px;width:6px;height:6px;background:var(--accent-red);border-radius:50%;box-shadow:0 0 6px var(--accent-red);"></span>
        </div>
        <h3 style="font-family:var(--font-matrix);font-size:1.2rem;font-weight:400;text-transform:uppercase;color:#ffffff;"><?= e($student['name']) ?></h3>
        <p class="text-muted text-small"><?= e($student['class']) ?> <?= $student['roll_number'] ? '· ' . e($student['roll_number']) : '' ?></p>
        <p class="text-muted text-small"><?= e($student['email']) ?></p>

        <div class="divider"></div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;text-align:center;">
          <div>
            <div style="font-family:var(--font-matrix);font-size:1.6rem;color:#ffffff;"><?= $totalDays ?></div>
            <div class="text-muted text-small">Total Days</div>
          </div>
          <div>
            <div style="font-family:var(--font-matrix);font-size:1.6rem;color:var(--led-green);"><?= $pct ?>%</div>
            <div class="text-muted text-small">Attendance</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Right: Account Details & Password Change -->
  <div style="display:flex;flex-direction:column;gap:1.5rem;">

    <!-- Student Details -->
    <div class="card">
      <div class="card-header">
        <h3>STUDENT INFORMATION</h3>
      </div>
      <div class="card-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Full Name</label>
            <input type="text" class="form-control" value="<?= e($student['name']) ?>" disabled style="background:#0d0d0d;color:var(--text-dim);">
          </div>
          <div class="form-group">
            <label class="form-label">Email</label>
            <input type="text" class="form-control" value="<?= e($student['email']) ?>" disabled style="background:#0d0d0d;color:var(--text-dim);">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Class</label>
            <input type="text" class="form-control" value="<?= e($student['class']) ?>" disabled style="background:#0d0d0d;color:var(--text-dim);">
          </div>
          <div class="form-group">
            <label class="form-label">Roll Number</label>
            <input type="text" class="form-control" value="<?= e($student['roll_number'] ?? 'N/A') ?>" disabled style="background:#0d0d0d;color:var(--text-dim);">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Login Username</label>
            <input type="text" class="form-control" value="<?= e($student['username']) ?>" disabled style="background:#0d0d0d;color:var(--text-dim);">
          </div>
          <div class="form-group">
            <label class="form-label">Assigned Head Teacher</label>
            <input type="text" class="form-control" value="<?= e($student['teacher_name'] ?? 'Not assigned') ?>" disabled style="background:#0d0d0d;color:var(--text-dim);">
          </div>
        </div>
      </div>
    </div>

    <!-- Security: Change Password -->
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
