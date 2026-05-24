<?php
// admin/rooms.php — Room Occupancy (READ-ONLY derived from VIEW)
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/layout.php';
requireAdmin();

$pdo = getPDO();

// NOTE: No POST handler — is_occupied is now fully DERIVED.
// The manual "Mark Occupied" button has been removed.
// Occupancy is always accurate because it counts assigned students.

$rooms = $pdo->query(
    'SELECT * FROM room_occupancy ORDER BY room_number'
)->fetchAll();

$byRoom = [];
foreach ($pdo->query(
    'SELECT assigned_room, name FROM students WHERE assigned_room IS NOT NULL ORDER BY name'
)->fetchAll() as $row) {
    $byRoom[$row['assigned_room']][] = $row['name'];
}

// Summary
$total    = count($rooms);
$occupied = count(array_filter($rooms, fn($r) => $r['is_occupied']));
$cap      = array_sum(array_column($rooms, 'capacity'));
$cur      = array_sum(array_column($rooms, 'current_occupants'));

pageHead('Rooms'); adminSidebar('rooms');
echo '<div class="main">'; topbar('Rooms & Occupancy');
echo flashHtml();
?>

<div class="sg">
<div class="sc"><div class="lbl">Total Rooms</div><div class="val"><?= $total ?></div></div>
<div class="sc red"><div class="lbl">Occupied</div><div class="val"><?= $occupied ?></div></div>
<div class="sc green"><div class="lbl">Available</div><div class="val"><?= $total - $occupied ?></div></div>
<div class="sc"><div class="lbl">Total Capacity</div><div class="val"><?= $cap ?></div>
    <div class="sub"><?= $cur ?> filled</div></div>
<div class="sc"><div class="lbl">Male Rooms</div>
    <div class="val"><?= count(array_filter($rooms, fn($r) => $r['gender'] === 'Male')) ?></div></div>
<div class="sc gold"><div class="lbl">Female Rooms</div>
    <div class="val"><?= count(array_filter($rooms, fn($r) => $r['gender'] === 'Female')) ?></div></div>
</div>

<div class="card">
<div class="card-hd">
    <h2>All Rooms</h2>
</div>
<div class="tw"><table>
<thead><tr>
    <th>Room</th><th>Gender</th><th>Capacity</th>
    <th>Current Occupants</th>
    <th>Available Slots</th>
    <th>Fill %</th>
    <th>Status</th>
    <th>Students Inside</th>
</tr></thead>
<tbody>
<?php foreach ($rooms as $r):
    $pct    = $r['capacity'] > 0 ? round($r['current_occupants'] / $r['capacity'] * 100) : 0;
    $barClr = $pct >= 100 ? 'var(--red)' : ($pct >= 60 ? 'var(--gold)' : 'var(--green)');
    $names  = implode(', ', $byRoom[$r['room_id']] ?? []);
    $oc     = $r['is_occupied'];
?>
<tr>
    <td><strong><?= htmlspecialchars($r['room_number']) ?></strong></td>
    <td><span class="badge <?= $r['gender'] === 'Male' ? 'bg-blue' : 'bg-gold' ?>">
        <?= $r['gender'] ?></span></td>
    <td><?= $r['capacity'] ?></td>
    <td><strong><?= $r['current_occupants'] ?></strong>
        <span class="text-muted"> / <?= $r['capacity'] ?></span></td>
    <td><?= $r['available_slots'] ?></td>
    <td>
        <div style="display:flex;align-items:center;gap:7px">
            <div style="width:70px;height:7px;background:var(--g200);border-radius:4px;overflow:hidden">
                <div style="width:<?= $pct ?>%;height:100%;background:<?= $barClr ?>;border-radius:4px"></div>
            </div>
            <span style="font-size:.78rem"><?= $pct ?>%</span>
        </div>
    </td>
    <td><span class="badge <?= $oc ? 'bg-red' : 'bg-green' ?>">
        <?= $oc ? 'Full' : 'Available' ?></span></td>
    <td style="font-size:.78rem;max-width:200px;color:var(--g600)">
        <?= $names ?: '<em class="text-muted">—</em>' ?></td>
</tr>
<?php endforeach; ?>
</tbody></table></div></div>
<?php pageClose(); ?>
