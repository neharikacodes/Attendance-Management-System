<?php
/**
 * index.php — Login Page
 * 3 Roles: Admin, Head Teacher, Student — Nothing OS Design
 */

ob_start();
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/flash.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (currentRole() === 'admin') {
        redirect('admin/dashboard.php');
    } elseif (currentRole() === 'head_teacher') {
        redirect('head_teacher/dashboard.php');
    } else {
        redirect('student/dashboard.php');
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? '';

    if (empty($username)) $errors[] = 'Username is required.';
    if (empty($password)) $errors[] = 'Password is required.';
    if (!in_array($role, ['admin', 'head_teacher', 'student'])) $errors[] = 'Please select a valid role.';

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = ? LIMIT 1");
        $stmt->execute([$username, $role]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];

            if ($role === 'admin') {
                $_SESSION['display_name'] = 'System Administrator';
                setFlash('success', 'Admin Access Granted.');
                redirect('admin/dashboard.php');

            } elseif ($role === 'head_teacher') {
                $t = $pdo->prepare("SELECT * FROM teachers WHERE user_id = ? LIMIT 1");
                $t->execute([$user['id']]);
                $teacher = $t->fetch();

                if ($teacher) {
                    $_SESSION['teacher_id']     = $teacher['id'];
                    $_SESSION['display_name']   = $teacher['name'];
                    $_SESSION['department']     = $teacher['department'];
                    $_SESSION['assigned_class'] = $teacher['assigned_class'];
                }

                setFlash('success', 'Welcome, ' . ($teacher['name'] ?? $username));
                redirect('head_teacher/dashboard.php');

            } else {
                $s = $pdo->prepare("SELECT * FROM students WHERE user_id = ? LIMIT 1");
                $s->execute([$user['id']]);
                $student = $s->fetch();

                if ($student) {
                    $_SESSION['student_id']   = $student['id'];
                    $_SESSION['display_name'] = $student['name'];
                    $_SESSION['class']        = $student['class'];
                }

                setFlash('success', 'Welcome, ' . ($student['name'] ?? $username));
                redirect('student/dashboard.php');
            }
        } else {
            $errors[] = 'Invalid username, password, or role.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>System Access — Attendance OS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DotGothic16&family=Space+Grotesk:wght@300;400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="login-page">

  <div class="login-card">

    <div class="login-logo">
      <div class="login-logo-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="white" viewBox="0 0 16 16">
          <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
        </svg>
      </div>
    </div>

    <div class="login-title">
      <h1>SYSTEM ACCESS</h1>
      <p>Attendance Management OS</p>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="flash-message flash-error">
        <span class="flash-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
            <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM5.354 4.646a.5.5 0 1 0-.708.708L7.293 8l-2.647 2.646a.5.5 0 0 0 .708.708L8 8.707l2.646 2.647a.5.5 0 0 0 .708-.708L8.707 8l2.647-2.646a.5.5 0 0 0-.708-.708L8 7.293 5.354 4.646z"/>
          </svg>
        </span>
        <span class="flash-text"><?= e(implode(' ', $errors)) ?></span>
      </div>
    <?php endif; ?>

    <form method="POST" action="" id="loginForm" data-loading="true">
      <input type="hidden" name="login" value="1">

      <div class="form-group">
        <label class="form-label" for="username">USER ID</label>
        <div class="input-icon-wrap">
          <span class="input-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
              <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
            </svg>
          </span>
          <input
            type="text"
            name="username"
            id="username"
            class="form-control"
            placeholder="username"
            value="<?= e($_POST['username'] ?? '') ?>"
            required
            autocomplete="username"
          >
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="password">PASSCODE</label>
        <div class="input-icon-wrap">
          <span class="input-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
              <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/>
            </svg>
          </span>
          <input
            type="password"
            name="password"
            id="password"
            class="form-control"
            placeholder="••••••••"
            required
            autocomplete="current-password"
          >
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">ROLE</label>
        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:0.4rem;">
          <!-- Admin -->
          <label style="display:flex;flex-direction:column;align-items:center;padding:0.6rem 0.3rem;background:#121212;border:1px solid var(--border-color);border-radius:4px;cursor:pointer;transition:all 0.15s;" id="roleAdminLabel">
            <input type="radio" name="role" value="admin" id="roleAdmin"
              <?= (($_POST['role'] ?? '') === 'admin') ? 'checked' : '' ?>
              style="accent-color:var(--accent-red);width:14px;height:14px;">
            <span style="font-family:var(--font-tech);font-size:0.68rem;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;color:var(--text-muted);margin-top:0.25rem;" id="roleAdminText">
              ADMIN
            </span>
          </label>

          <!-- Head Teacher -->
          <label style="display:flex;flex-direction:column;align-items:center;padding:0.6rem 0.3rem;background:#121212;border:1px solid var(--border-color);border-radius:4px;cursor:pointer;transition:all 0.15s;" id="roleTeacherLabel">
            <input type="radio" name="role" value="head_teacher" id="roleTeacher"
              <?= (($_POST['role'] ?? '') === 'head_teacher') ? 'checked' : '' ?>
              style="accent-color:var(--accent-red);width:14px;height:14px;">
            <span style="font-family:var(--font-tech);font-size:0.68rem;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;color:var(--text-muted);margin-top:0.25rem;" id="roleTeacherText">
              TEACHER
            </span>
          </label>

          <!-- Student -->
          <label style="display:flex;flex-direction:column;align-items:center;padding:0.6rem 0.3rem;background:#121212;border:1px solid var(--border-color);border-radius:4px;cursor:pointer;transition:all 0.15s;" id="roleStudentLabel">
            <input type="radio" name="role" value="student" id="roleStudent"
              <?= (($_POST['role'] ?? 'student') === 'student') ? 'checked' : '' ?>
              style="accent-color:var(--accent-red);width:14px;height:14px;">
            <span style="font-family:var(--font-tech);font-size:0.68rem;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;color:var(--text-muted);margin-top:0.25rem;" id="roleStudentText">
              STUDENT
            </span>
          </label>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:0.75rem;">
        AUTHENTICATE →
      </button>
    </form>

    <!-- Demo Credentials -->
    <div style="margin-top:1.25rem;padding:0.75rem;background:#0d0d0d;border:1px solid var(--border-color);border-radius:4px;">
      <p style="font-family:var(--font-tech);font-size:0.62rem;font-weight:700;color:var(--text-dim);text-transform:uppercase;letter-spacing:0.1em;margin-bottom:0.35rem;">
        DEMO PASSCODES
      </p>
      <div style="font-family:var(--font-tech);font-size:0.72rem;color:var(--text-muted);display:grid;gap:0.15rem;">
        <span>ADMIN: <strong style="color:#ffffff;">admin</strong> / <strong>password123</strong></span>
        <span>TEACHER: <strong style="color:#ffffff;">headteacher</strong> / <strong>password123</strong></span>
        <span>STUDENT: <strong style="color:#ffffff;">student1</strong> / <strong>password123</strong></span>
      </div>
    </div>

  </div>
</div>

<script src="assets/js/app.js"></script>
<script>
  function updateRoleHighlight() {
    const roles = ['Admin', 'Teacher', 'Student'];
    roles.forEach(role => {
      const input = document.getElementById('role' + role);
      const label = document.getElementById('role' + role + 'Label');
      const text  = document.getElementById('role' + role + 'Text');
      if (input && input.checked) {
        label.style.borderColor = '#ffffff';
        label.style.background  = '#1a1a1a';
        text.style.color        = '#ffffff';
      } else if (label && text) {
        label.style.borderColor = 'var(--border-color)';
        label.style.background  = '#121212';
        text.style.color        = 'var(--text-muted)';
      }
    });
  }

  document.querySelectorAll('input[name="role"]').forEach(radio => {
    radio.addEventListener('change', updateRoleHighlight);
  });
  updateRoleHighlight();
</script>
</body>
</html>