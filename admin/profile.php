<?php
/**
 * admin/profile.php
 * Super Admin Profile & Security — Nothing OS Design
 */

ob_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/flash.php';

requireRole('admin', '../index.php');

$userId = currentUserId();

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password']     ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!password_verify($current, $user['password'])) {
        setFlash('error', 'Current passcode is incorrect.');
    } elseif (strlen($new) < 6) {
        setFlash('error', 'New passcode must be at least 6 characters.');
    } elseif ($new !== $confirm) {
        setFlash('error', 'New passcodes do not match.');
    } else {
        $hash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
        $upd  = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $upd->execute([$hash, $userId]);
        setFlash('success', 'Admin passcode updated successfully.');
    }

    header('Location: profile.php');
    exit;
}

$pageTitle = 'Admin Profile';
require_once '../includes/sidebar_admin.php';
?>

<?= renderFlash() ?>

<div style="display:grid;grid-template-columns:1fr 2fr;gap:1.5rem;align-items:start;">

  <!-- Left Card -->
  <div>
    <div class="card">
      <div class="card-body text-center" style="padding:2rem 1.5rem;">
        <div style="width:72px;height:72px;border-radius:var(--radius-sm);background:#171717;border:1px solid var(--border-light);display:flex;align-items:center;justify-content:center;font-family:var(--font-tech);font-size:1.4rem;font-weight:700;color:#ffffff;margin:0 auto 1rem;position:relative;">
          ADM
          <span style="position:absolute;top:4px;right:4px;width:6px;height:6px;background:var(--accent-red);border-radius:50%;box-shadow:0 0 6px var(--accent-red);"></span>
        </div>
        <h3 style="font-family:var(--font-matrix);font-size:1.2rem;font-weight:400;text-transform:uppercase;color:#ffffff;"><?= e(currentUserName()) ?></h3>
        <p class="text-muted text-small">SYSTEM ADMINISTRATOR</p>
      </div>
    </div>
  </div>

  <!-- Security Card -->
  <div>
    <div class="card">
      <div class="card-header">
        <h3>CHANGE ADMIN PASSCODE</h3>
      </div>
      <form method="POST" action="profile.php">
        <input type="hidden" name="change_password" value="1">
        <div class="card-body">
          <div class="form-group">
            <label class="form-label">CURRENT PASSCODE *</label>
            <input type="password" name="current_password" class="form-control" required autocomplete="current-password">
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">NEW PASSCODE *</label>
              <input type="password" name="new_password" class="form-control" required minlength="6" autocomplete="new-password">
              <p class="form-hint">Minimum 6 characters.</p>
            </div>
            <div class="form-group">
              <label class="form-label">CONFIRM NEW PASSCODE *</label>
              <input type="password" name="confirm_password" class="form-control" required autocomplete="new-password">
            </div>
          </div>
        </div>
        <div class="card-footer" style="display:flex;justify-content:flex-end;">
          <button type="submit" class="btn btn-warning">UPDATE PASSCODE</button>
        </div>
      </form>
    </div>
  </div>

</div>

<?php require_once '../includes/page_footer.php'; ?>
