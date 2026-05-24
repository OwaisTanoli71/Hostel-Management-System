<?php
// student/application.php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/layout.php';
requireStudent();

$pdo     = getPDO();
$sid     = $_SESSION['student_id'];

$stmtS   = $pdo->prepare('SELECT * FROM students WHERE id = ?');
$stmtS->execute([$sid]);
$student = $stmtS->fetch();

// ── SUBMIT APPLICATION ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply'])) {
    $rid = (int)$_POST['room_id'];

    // Check for existing pending application
    $chk = $pdo->prepare("SELECT id FROM applications WHERE student_id = ? AND status = 'pending'");
    $chk->execute([$sid]);
    if ($chk->fetch()) {
        flash('error', 'You already have a pending application. Wait for admin to review it.');
        header('Location: application.php'); exit;
    }

    // Fetch room — enforce gender match via SQL (not just JS)
    $rStmt = $pdo->prepare(
        'SELECT r.*, ro.current_occupants, ro.is_occupied, ro.available_slots
         FROM   rooms r
         JOIN   room_occupancy ro ON ro.room_id = r.id
         WHERE  r.id = ? AND r.gender = ?'
    );
    $rStmt->execute([$rid, $student['gender']]);
    $room = $rStmt->fetch();

    if (!$room) {
        flash('error', 'Invalid room or gender mismatch. You can only apply for '
              . $student['gender'] . ' rooms.');
    } elseif ($room['is_occupied']) {
        flash('error', 'That room is already at full capacity. Please choose another room.');
    } else {
        $pdo->prepare('INSERT INTO applications(student_id, room_id) VALUES(?,?)')
            ->execute([$sid, $rid]);
        flash('success', 'Application submitted successfully! Waiting for admin approval.');
    }
    header('Location: application.php'); exit;
}

// ── FETCH AVAILABLE ROOMS (gender-filtered via SQL) ───────
$availStmt = $pdo->prepare(
    'SELECT r.*, ro.current_occupants, ro.available_slots
     FROM   rooms r
     JOIN   room_occupancy ro ON ro.room_id = r.id
     WHERE  r.gender = ? AND ro.is_occupied = 0
     ORDER  BY r.room_number'
);
$availStmt->execute([$student['gender']]);
$availRooms = $availStmt->fetchAll();

// ── MY APPLICATION HISTORY ────────────────────────────────
$myApps = $pdo->prepare(
    'SELECT a.*, r.room_number, r.gender AS rg
     FROM   applications a
     JOIN   rooms r ON a.room_id = r.id
     WHERE  a.student_id = ? ORDER BY a.applied_at DESC'
);
$myApps->execute([$sid]);
$myApps = $myApps->fetchAll();

pageHead('Application'); studentSidebar('application');
echo '<div class="main">'; topbar('Room Application', 'Apply for a hostel room — gender-matched rooms only');
echo flashHtml();
?>

<?php if (!$student['assigned_room']): ?>
<div class="card mb-2">
<div class="card-hd"><h2>Apply for a Room</h2>
    <span class="text-muted" style="font-size:.78rem">
        Only <?= $student['gender'] ?> rooms shown
    </span>
</div>
<div class="card-bd">
<?php if ($availRooms): ?>
<form method="POST">
<input type="hidden" name="apply" value="1">
<div class="fg fg2">
    <div class="fgroup">
        <label>Select Available Room (<?= $student['gender'] ?> only) *</label>
        <select name="room_id" class="fc" required>
            <option value="">Choose a room...</option>
            <?php foreach ($availRooms as $r): ?>
                <option value="<?= $r['id'] ?>">
                    <?= htmlspecialchars($r['room_number']) ?>
                    — Capacity: <?= $r['capacity'] ?>
                    (<?= $r['current_occupants'] ?> occupied,
                     <?= $r['available_slots'] ?> free)
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</div>
<div class="mt-2">
    <button type="submit" class="btn btn-primary">Submit Application</button>
</div>
</form>
<?php else: ?>
    <div class="empty-state">
        <div class="ei">🚪</div>
        <p>No available <?= $student['gender'] ?> rooms at the moment.</p>
        <p class="text-muted" style="font-size:.82rem;margin-top:6px">
            Check back later or contact the admin.
        </p>
    </div>
<?php endif; ?>
</div></div>

<?php else: ?>
<div class="alert alert-success">
    <span class="alert-icon">✓</span>
    You are already assigned to a room. No new application needed.
</div>
<?php endif; ?>

<div class="card">
<div class="card-hd"><h2>My Application History</h2></div>
<div class="tw"><table>
<thead><tr><th>#</th><th>Room</th><th>Gender</th><th>Status</th><th>Applied On</th></tr></thead>
<tbody>
<?php foreach ($myApps as $a):
    $bc = ['pending'=>'bg-gold','approved'=>'bg-green','rejected'=>'bg-red'][$a['status']] ?? 'bg-gray'; ?>
<tr>
    <td class="mono"><?= $a['id'] ?></td>
    <td><?= htmlspecialchars($a['room_number']) ?></td>
    <td><?= htmlspecialchars($a['rg']) ?></td>
    <td><span class="badge <?= $bc ?>"><?= $a['status'] ?></span></td>
    <td><?= date('d M Y', strtotime($a['applied_at'])) ?></td>
</tr>
<?php endforeach;
if (!$myApps) echo '<tr><td colspan="5"><div class="empty-state"><div class="ei">📋</div>No applications yet</div></td></tr>';
?>
</tbody></table></div></div>
<?php pageClose(); ?>
