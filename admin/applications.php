<?php
// admin/applications.php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/layout.php';
requireAdmin();

$pdo = getPDO();

// ── APPROVE / REJECT (with full PDO Transaction) ──────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $id     = (int)$_POST['app_id'];
    $action = $_POST['action'];
    $aid    = (int)$_SESSION['admin_id'];

    if (in_array($action, ['approved', 'rejected'], true)) {

        // Fetch application details
        $appStmt = $pdo->prepare(
            'SELECT a.*, s.gender AS student_gender, r.gender AS room_gender,
                    r.capacity, ro.current_occupants
             FROM   applications a
             JOIN   students      s  ON a.student_id = s.id
             JOIN   rooms         r  ON a.room_id    = r.id
             LEFT   JOIN room_occupancy ro ON ro.room_id = r.id
             WHERE  a.id = ? AND a.status = "pending"'
        );
        $appStmt->execute([$id]);
        $app = $appStmt->fetch();

        if (!$app) {
            flash('error', 'Application not found or already processed.');
            header('Location: applications.php'); exit;
        }

        if ($action === 'approved') {
            // Gender-match guard
            if ($app['student_gender'] !== $app['room_gender']) {
                flash('error', 'Cannot approve: Student gender does not match room gender.');
                header('Location: applications.php'); exit;
            }
            // Capacity guard
            if ($app['current_occupants'] >= $app['capacity']) {
                flash('error', 'Cannot approve: Room is already at full capacity.');
                header('Location: applications.php'); exit;
            }
        }

        // ── ATOMIC TRANSACTION ─────────────────────────────
        try {
            $pdo->beginTransaction();

            // Step 1: Update the application status
            $pdo->prepare(
                'UPDATE applications SET status = ?, admin_id = ? WHERE id = ?'
            )->execute([$action, $aid, $id]);

            if ($action === 'approved') {
                // Step 2: Assign the room to the student
                $pdo->prepare(
                    'UPDATE students SET assigned_room = ? WHERE id = ?'
                )->execute([$app['room_id'], $app['student_id']]);

                // NOTE: No is_occupied update needed —
                //       room_occupancy VIEW derives it automatically
                //       from the student assignment above.
            }

            $pdo->commit();
            flash('success', 'Application ' . $action . ' successfully. All changes saved atomically.');

        } catch (PDOException $e) {
            $pdo->rollBack();
            flash('error', 'Transaction failed and was rolled back: ' . $e->getMessage());
        }
    }
    header('Location: applications.php'); exit;
}

// ── FETCH WITH FILTER ─────────────────────────────────────
$filter = $_GET['filter'] ?? 'all';
$where  = '';
$params = [];
if ($filter !== 'all') {
    $where  = 'WHERE a.status = ?';
    $params = [$filter];
}

$stmt = $pdo->prepare(
    "SELECT a.*, s.name, s.roll_no, s.gender AS student_gender,
            r.room_number, r.gender AS room_gender,
            ro.current_occupants, ro.capacity AS room_cap,
            adm.username AS reviewed_by
     FROM   applications a
     JOIN   students       s   ON a.student_id = s.id
     JOIN   rooms          r   ON a.room_id    = r.id
     LEFT   JOIN room_occupancy ro ON ro.room_id = r.id
     LEFT   JOIN admins        adm ON a.admin_id  = adm.id
     $where
     ORDER  BY a.applied_at DESC"
);
$stmt->execute($params);
$apps = $stmt->fetchAll();

pageHead('Applications'); adminSidebar('applications');
echo '<div class="main">'; topbar('Applications', 'Approve or reject room requests — all changes are atomic');
echo flashHtml();
?>

<div class="ph">
    <div><h1>Room Applications</h1><p><?= count($apps) ?> application(s)</p></div>
    <div class="flex-c" style="gap:8px">
    <?php foreach (['all','pending','approved','rejected'] as $f):
        $ac = $filter === $f ? 'btn-navy' : 'btn-ghost'; ?>
        <a href="?filter=<?= $f ?>" class="btn <?= $ac ?> btn-sm"><?= ucfirst($f) ?></a>
    <?php endforeach; ?>
    </div>
</div>

<div class="card"><div class="tw"><table>
<thead><tr>
    <th>#</th><th>Student</th><th>Gender</th><th>Room</th>
    <th>Room Gender</th><th>Occupancy</th><th>Status</th>
    <th>Applied</th><th>Reviewed By</th><th>Actions</th>
</tr></thead>
<tbody>
<?php foreach ($apps as $a):
    $bc = ['pending'=>'bg-gold','approved'=>'bg-green','rejected'=>'bg-red'][$a['status']] ?? 'bg-gray';
    $genderMatch = $a['student_gender'] === $a['room_gender'];
    $full = $a['current_occupants'] >= $a['room_cap'];
?>
<tr>
    <td class="mono"><?= $a['id'] ?></td>
    <td><strong><?= htmlspecialchars($a['name']) ?></strong><br>
        <span class="mono text-muted"><?= htmlspecialchars($a['roll_no']) ?></span></td>
    <td><span class="badge <?= $a['student_gender']==='Male'?'bg-blue':'bg-gold' ?>">
        <?= $a['student_gender'] ?></span></td>
    <td><?= htmlspecialchars($a['room_number']) ?></td>
    <td><span class="badge <?= $a['room_gender']==='Male'?'bg-blue':'bg-gold' ?>">
        <?= $a['room_gender'] ?></span></td>
    <td>
        <?= $a['current_occupants'] ?>/<?= $a['room_cap'] ?>
        <?php if ($full): ?>
            <span class="badge bg-red" style="margin-left:4px">Full</span>
        <?php elseif (!$genderMatch): ?>
            <span class="badge bg-red" style="margin-left:4px">Gender ✗</span>
        <?php endif; ?>
    </td>
    <td><span class="badge <?= $bc ?>"><?= $a['status'] ?></span></td>
    <td style="font-size:.78rem"><?= date('d M Y', strtotime($a['applied_at'])) ?></td>
    <td><?= $a['reviewed_by'] ? htmlspecialchars($a['reviewed_by']) : '<span class="text-muted">—</span>' ?></td>
    <td>
    <?php if ($a['status'] === 'pending'): ?>
        <?php if (!$genderMatch): ?>
            <span class="text-muted" style="font-size:.78rem" title="Gender mismatch">⚠️ Gender mismatch</span>
        <?php elseif ($full): ?>
            <span class="text-muted" style="font-size:.78rem">Room full</span>
        <?php else: ?>
            <form method="POST" style="display:inline">
                <input type="hidden" name="app_id" value="<?= $a['id'] ?>">
                <input type="hidden" name="action"  value="approved">
                <button class="btn btn-success btn-xs">✓ Approve</button>
            </form>
        <?php endif; ?>
        <form method="POST" style="display:inline;margin-left:4px">
            <input type="hidden" name="app_id" value="<?= $a['id'] ?>">
            <input type="hidden" name="action"  value="rejected">
            <button class="btn btn-danger btn-xs">✕ Reject</button>
        </form>
    <?php else: ?>
        <span class="text-muted" style="font-size:.78rem">Done</span>
    <?php endif; ?>
    </td>
</tr>
<?php endforeach;
if (!$apps) echo '<tr><td colspan="10"><div class="empty-state"><div class="ei">📋</div>No applications found</div></td></tr>';
?>
</tbody></table></div></div>
<?php pageClose(); ?>
