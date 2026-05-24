<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/layout.php';
requireAdmin();
$pdo=getPDO();
$s=[
  'students'   =>$pdo->query('SELECT COUNT(*) FROM students')->fetchColumn(),
  'rooms'      =>$pdo->query('SELECT COUNT(*) FROM rooms')->fetchColumn(),
  'occupied'   =>$pdo->query('SELECT COUNT(*) FROM room_occupancy WHERE is_occupied=1')->fetchColumn(),
  'pend_app'   =>$pdo->query("SELECT COUNT(*) FROM applications WHERE status='pending'")->fetchColumn(),
  'pend_pay'   =>$pdo->query("SELECT COUNT(*) FROM payments WHERE status='Pending'")->fetchColumn(),
  'open_comp'  =>$pdo->query("SELECT COUNT(*) FROM complaints WHERE status='Open'")->fetchColumn(),
];
$apps=$pdo->query("SELECT a.id,s.name,s.roll_no,r.room_number,a.status FROM applications a JOIN students s ON a.student_id=s.id JOIN rooms r ON a.room_id=r.id ORDER BY a.applied_at DESC LIMIT 5")->fetchAll();
$comps=$pdo->query("SELECT c.id,s.name,r.room_number,c.category,c.status FROM complaints c JOIN students s ON c.student_id=s.id JOIN rooms r ON c.room_id=r.id ORDER BY c.reported_at DESC LIMIT 5")->fetchAll();
pageHead('Dashboard'); adminSidebar('dashboard');
echo '<div class="main">'; topbar('Dashboard','Welcome back, '.($_SESSION['admin_username']??'Admin'));
echo flashHtml();
?>
<div class="sg">
<div class="sc"><div class="lbl">Total Students</div><div class="val"><?=$s['students']?></div><div class="sub">Registered</div></div>
<div class="sc"><div class="lbl">Total Rooms</div><div class="val"><?=$s['rooms']?></div><div class="sub"><?=$s['occupied']?> occupied</div></div>
<div class="sc gold"><div class="lbl">Pending Applications</div><div class="val"><?=$s['pend_app']?></div><div class="sub">Awaiting review</div></div>
<div class="sc red"><div class="lbl">Pending Payments</div><div class="val"><?=$s['pend_pay']?></div><div class="sub">Fee not cleared</div></div>
<div class="sc red"><div class="lbl">Open Complaints</div><div class="val"><?=$s['open_comp']?></div><div class="sub">Needs attention</div></div>
<div class="sc green"><div class="lbl">Available Rooms</div><div class="val"><?=$s['rooms']-$s['occupied']?></div><div class="sub">Ready to assign</div></div>
</div>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
<div class="card"><div class="card-hd"><h2>Recent Applications</h2><a href="applications.php" class="btn btn-ghost btn-sm">View All</a></div>
<div class="tw"><table><thead><tr><th>Student</th><th>Room</th><th>Status</th></tr></thead><tbody>
<?php foreach($apps as $a):$bc=['pending'=>'bg-gold','approved'=>'bg-green','rejected'=>'bg-red'][$a['status']]??'bg-gray';?>
<tr><td><strong><?=htmlspecialchars($a['name'])?></strong><br><span class="mono text-muted"><?=htmlspecialchars($a['roll_no'])?></span></td>
<td><?=htmlspecialchars($a['room_number'])?></td><td><span class="badge <?=$bc?>"><?=$a['status']?></span></td></tr>
<?php endforeach; if(!$apps) echo '<tr><td colspan="3"><div class="empty-state"><div class="ei">📋</div>No applications</div></td></tr>'; ?>
</tbody></table></div></div>
<div class="card"><div class="card-hd"><h2>Recent Complaints</h2><a href="complaints.php" class="btn btn-ghost btn-sm">View All</a></div>
<div class="tw"><table><thead><tr><th>Student</th><th>Room</th><th>Category</th><th>Status</th></tr></thead><tbody>
<?php foreach($comps as $c):$bc=['Open'=>'bg-red','In Progress'=>'bg-gold','Resolved'=>'bg-green'][$c['status']]??'bg-gray';?>
<tr><td><strong><?=htmlspecialchars($c['name'])?></strong></td><td><?=htmlspecialchars($c['room_number'])?></td>
<td><?=htmlspecialchars($c['category'])?></td><td><span class="badge <?=$bc?>"><?=$c['status']?></span></td></tr>
<?php endforeach; if(!$comps) echo '<tr><td colspan="4"><div class="empty-state"><div class="ei">🔧</div>No complaints</div></td></tr>'; ?>
</tbody></table></div></div>
</div>
<?php pageClose(); ?>
