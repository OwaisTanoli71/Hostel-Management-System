<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/layout.php';
requireStudent();
$pdo=getPDO(); $sid=$_SESSION['student_id'];
$student=$pdo->prepare('SELECT s.*,COALESCE(r.room_number,"Not Assigned") AS rn,r.capacity,r.gender AS rg FROM students s LEFT JOIN rooms r ON s.assigned_room=r.id WHERE s.id=?');
$student->execute([$sid]); $student=$student->fetch();
$pays=$pdo->prepare("SELECT COUNT(*) FROM payments WHERE student_id=? AND status='Pending'"); $pays->execute([$sid]); $pendPay=$pays->fetchColumn();
$apps=$pdo->prepare("SELECT a.status,r.room_number FROM applications a JOIN rooms r ON a.room_id=r.id WHERE a.student_id=? ORDER BY a.applied_at DESC LIMIT 1"); $apps->execute([$sid]); $latestApp=$apps->fetch();
$unread=$pdo->prepare('SELECT COUNT(*) FROM student_notifications WHERE student_id=? AND is_read=0'); $unread->execute([$sid]); $unread=$unread->fetchColumn();
$myComps=$pdo->prepare('SELECT COUNT(*) FROM complaints WHERE student_id=?'); $myComps->execute([$sid]); $myComps=$myComps->fetchColumn();
pageHead('Dashboard'); studentSidebar('dashboard');
echo '<div class="main">'; topbar('My Dashboard','Welcome back, '.($_SESSION['student_name']??'Student'));
echo flashHtml();
?>
<div class="sg">
<div class="sc <?=$student['assigned_room']?'green':'red'?>">
<div class="lbl">My Room</div>
<div class="val" style="font-size:1.4rem"><?=htmlspecialchars($student['rn'])?></div>
<div class="sub"><?=$student['assigned_room']?htmlspecialchars($student['rg']).' Room':'No room assigned'?></div>
</div>
<div class="sc <?=$pendPay?'red':'green'?>">
<div class="lbl">Pending Fees</div><div class="val"><?=$pendPay?></div>
<div class="sub"><?=$pendPay?'Payment due':'All clear'?></div>
</div>
<div class="sc <?=$unread?'gold':'green'?>">
<div class="lbl">Unread Notifications</div><div class="val"><?=$unread?></div>
<div class="sub"><a href="notifications.php">View all</a></div>
</div>
<div class="sc"><div class="lbl">My Complaints</div><div class="val"><?=$myComps?></div><div class="sub">Submitted total</div></div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
<div class="card"><div class="card-hd"><h2>My Profile</h2></div><div class="card-bd">
<table style="width:100%"><tbody>
<tr><td class="text-muted" style="padding:7px 0;font-size:.85rem;width:120px">Name</td><td><strong><?=htmlspecialchars($student['name'])?></strong></td></tr>
<tr><td class="text-muted" style="padding:7px 0;font-size:.85rem">Roll No</td><td><span class="mono"><?=htmlspecialchars($student['roll_no'])?></span></td></tr>
<tr><td class="text-muted" style="padding:7px 0;font-size:.85rem">Email</td><td><?=htmlspecialchars($student['email'])?></td></tr>
<tr><td class="text-muted" style="padding:7px 0;font-size:.85rem">Gender</td><td><?=htmlspecialchars($student['gender'])?></td></tr>
<tr><td class="text-muted" style="padding:7px 0;font-size:.85rem">Room</td><td><strong><?=htmlspecialchars($student['rn'])?></strong></td></tr>
</tbody></table></div></div>

<div class="card"><div class="card-hd"><h2>Latest Application</h2><a href="application.php" class="btn btn-ghost btn-sm">Apply</a></div>
<div class="card-bd">
<?php if($latestApp):$bc=['pending'=>'bg-gold','approved'=>'bg-green','rejected'=>'bg-red'][$latestApp['status']]??'bg-gray';?>
<p style="margin-bottom:10px">Room: <strong><?=htmlspecialchars($latestApp['room_number'])?></strong></p>
<p>Status: <span class="badge <?=$bc?>"><?=$latestApp['status']?></span></p>
<?php else: echo '<div class="empty-state"><div class="ei">📋</div><p>No application submitted yet.</p><a href="application.php" class="btn btn-primary btn-sm mt-1">Apply for Room</a></div>'; endif; ?>
</div></div>
</div>
<?php pageClose();?>
