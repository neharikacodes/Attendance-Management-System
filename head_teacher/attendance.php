<?php
/**
 * head_teacher/attendance.php
 * Mark Attendance — Nothing OS Design
 */

ob_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/flash.php';

requireRole('head_teacher', '../index.php');

$teacherId = currentTeacherId();
$today     = date('Y-m-d');
$date      = isset($_GET['date']) ? $_GET['date'] : $today;

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = $today;
}

if ($date > $today) {
    setFlash('warning', 'Cannot mark attendance for future dates.');
    $date = $today;
}

// Handle POST: Save Attendance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    $postedDate = $_POST['attendance_date'] ?? $today;
    $studentIds = $_POST['student_id']    ?? [];
    $statuses   = $_POST['status']        ?? [];

    if (empty($studentIds)) {
        setFlash('error', 'No students found to mark attendance.');
        header("Location: attendance.php?date=" . urlencode($postedDate));
        exit;
    }

    $saved   = 0;
    $errors  = 0;

    foreach ($studentIds as $idx => $sid) {
        $sid    = (int)$sid;
        $status = $statuses[$idx] ?? 'Absent';

        if (!in_array($status, ['Present', 'Absent', 'Late'])) {
            $status = 'Absent';
        }

        $verify = $pdo->prepare("SELECT id FROM students WHERE id = ? AND assigned_teacher_id = ?");
        $verify->execute([$sid, $teacherId]);
        if (!$verify->fetch()) {
            $errors++;
            continue;
        }

        $upsert = $pdo->prepare("
            INSERT INTO attendance (student_id, teacher_id, date, status)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE status = VALUES(status), teacher_id = VALUES(teacher_id)
        ");

        try {
            $upsert->execute([$sid, $teacherId, $postedDate, $status]);
            $saved++;
        } catch (PDOException $e) {
            $errors++;
        }
    }

    if ($errors === 0) {
        setFlash('success', "Attendance saved for " . date('d M Y', strtotime($postedDate)) . " ({$saved} records).");
    } else {
        setFlash('warning', "Attendance saved with {$errors} issue(s).");
    }

    header("Location: attendance.php?date=" . urlencode($postedDate));
    exit;
}

// Fetch Students
$stmtStudents = $pdo->prepare("
    SELECT s.id, s.name, s.class, s.roll_number,
           a.status AS existing_status
    FROM students s
    LEFT JOIN attendance a ON (a.student_id = s.id AND a.date = ?)
    WHERE s.assigned_teacher_id = ?
    ORDER BY s.name ASC
");
$stmtStudents->execute([$date, $teacherId]);
$students = $stmtStudents->fetchAll();

$alreadyMarked = array_filter($students, fn($s) => $s['existing_status'] !== null);
$isAlreadyMarked = count($alreadyMarked) === count($students) && count($students) > 0;

$pageTitle = 'Mark Attendance';
require_once '../includes/sidebar_teacher.php';
?>

<?= renderFlash() ?>

<!-- ── Page Header ─────────────────────────────────────── -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">
  <div>
    <h1 style="font-family:var(--font-matrix);font-size:1.4rem;font-weight:400;text-transform:uppercase;color:#ffffff;">MARK ATTENDANCE</h1>
    <p class="text-muted text-small">DATE: <strong><?= date('l, d F Y', strtotime($date)) ?></strong></p>
  </div>
  <?php if ($isAlreadyMarked): ?>
    <span class="badge badge-warning">
      ✏️ EDITING EXISTING RECORDS
    </span>
  <?php endif; ?>
</div>

<!-- ── Date Selector ───────────────────────────────────── -->
<div class="card mb-3">
  <div class="card-body" style="padding:0.85rem 1.25rem;">
    <form method="GET" action="attendance.php" style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
      <label class="form-label mb-0" style="white-space:nowrap;">SELECT DATE:</label>
      <input type="date" name="date" class="form-control" value="<?= e($date) ?>" max="<?= $today ?>" style="width:auto;">
      <button type="submit" class="btn btn-secondary">LOAD</button>
      <?php if ($date !== $today): ?>
        <a href="attendance.php" class="btn btn-outline">TODAY</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<?php if (empty($students)): ?>
  <div class="card">
    <div class="empty-state">
      <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" viewBox="0 0 16 16">
        <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
      </svg>
      <h4>NO STUDENTS ASSIGNED</h4>
      <p>Add students first to start recording attendance.</p>
      <a href="students.php" class="btn btn-primary btn-sm" style="margin-top:0.75rem;">+ ADD STUDENTS</a>
    </div>
  </div>
<?php else: ?>

<!-- ── Quick Controls ──────────────────────────────────── -->
<div style="display:flex;gap:0.75rem;margin-bottom:1rem;flex-wrap:wrap;align-items:center;">
  <button type="button" class="btn btn-success btn-sm" id="markAllPresent">SELECT ALL PRESENT</button>
  <button type="button" class="btn btn-danger btn-sm"  id="markAllAbsent">SELECT ALL ABSENT</button>
  <span class="text-muted text-small" style="margin-left:auto;"><?= count($students) ?> STUDENT(S)</span>
</div>

<!-- ── Form ────────────────────────────────────────────── -->
<div class="card">
  <div class="card-header">
    <h3>RECORD — <?= date('d M Y', strtotime($date)) ?></h3>
  </div>

  <form method="POST" action="attendance.php" data-loading="true">
    <input type="hidden" name="save_attendance" value="1">
    <input type="hidden" name="attendance_date" value="<?= e($date) ?>">

    <div>
      <?php foreach ($students as $i => $s):
        $existing = $s['existing_status'] ?? 'Absent';
      ?>
      <div class="attendance-row">
        <input type="hidden" name="student_id[]" value="<?= $s['id'] ?>">

        <div class="student-avatar"><?= strtoupper(substr($s['name'],0,2)) ?></div>

        <div class="student-info">
          <h4><?= e($s['name']) ?></h4>
          <span><?= e($s['class']) ?><?= $s['roll_number'] ? ' · ' . e($s['roll_number']) : '' ?></span>
        </div>

        <div class="radio-group">
          <!-- Present -->
          <input type="radio" class="radio-option" name="status[<?= $i ?>]"
                 id="present_<?= $i ?>" value="Present"
                 <?= $existing === 'Present' ? 'checked' : '' ?>>
          <label class="radio-label" for="present_<?= $i ?>">PRESENT</label>

          <!-- Late -->
          <input type="radio" class="radio-option" name="status[<?= $i ?>]"
                 id="late_<?= $i ?>" value="Late"
                 <?= $existing === 'Late' ? 'checked' : '' ?>>
          <label class="radio-label" for="late_<?= $i ?>">LATE</label>

          <!-- Absent -->
          <input type="radio" class="radio-option" name="status[<?= $i ?>]"
                 id="absent_<?= $i ?>" value="Absent"
                 <?= ($existing === 'Absent' || $s['existing_status'] === null) ? 'checked' : '' ?>>
          <label class="radio-label" for="absent_<?= $i ?>">ABSENT</label>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="card-footer">
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.75rem;">
        <p class="text-muted text-small mb-0">
          <?= $isAlreadyMarked ? '⚠️ OVERWRITING EXISTING RECORD' : 'NEW RECORD WILL BE SAVED' ?>
        </p>
        <button type="submit" class="btn btn-primary btn-lg">
          SAVE ATTENDANCE →
        </button>
      </div>
    </div>
  </form>
</div>

<?php endif; ?>

<?php require_once '../includes/page_footer.php'; ?>
