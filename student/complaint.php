<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/layout.php';
requireStudent();
$pdo=getPDO(); $sid=$_SESSION['student_id'];
$student=$pdo->prepare('SELECT * FROM students WHERE id=?'); $student->execute([$sid]); $student=$student->fetch();
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['submit'])) {
    $cat=$_POST['category']??''; $desc=trim($_POST['description']??''); $rid=$student['assigned_room'];
    if ($cat&&$desc&&$rid) {
        $pdo->prepare('INSERT INTO complaints(student_id,room_id,category,description) VALUES(?,?,?,?)')->execute([$sid,$rid,$cat,$desc]);
        flash('success','Complaint submitted successfully. Admin will review it.'); header('Location: complaint.php'); exit;
    } else flash('error',$rid?'Fill all fields.':'You must be assigned a room to submit a complaint.');
    header('Location: complaint.php'); exit;
}
$myComps=$pdo->prepare('SELECT c.*,r.room_number,adm.username AS handled_by FROM complaints c JOIN rooms r ON c.room_id=r.id LEFT JOIN admins adm ON c.admin_id=adm.id WHERE c.student_id=? ORDER BY c.reported_at DESC');
$myComps->execute([$sid]); $myComps=$myComps->fetchAll();
pageHead('Complaint'); studentSidebar('complaint');
echo '<div class="main">'; topbar('Submit Complaint','Report a maintenance issue in your room');
echo flashHtml();
?>
<?php if($student['assigned_room']): ?>
<div class="card mb-2"><div class="card-hd"><h2>New Complaint</h2></div><div class="card-bd">
<form method="POST">
<input type="hidden" name="submit" value="1">
<div class="fg fg2">
<div class="fgroup"><label>Category *</label>
<select name="category" class="fc" required>
<option value="">Select category</option>
<option>Electrical</option><option>Plumbing</option><option>Furniture</option><option>Cleanliness</option><option>Other</option>
</select></div>
<div class="fgroup"><label>Your Room</label><input type="text" class="fc" value="<?=htmlspecialchars($student['assigned_room']??'')?>" disabled></div>
</div>
<div class="fgroup mt-1"><label>Description *</label><textarea name="description" class="fc" rows="4" required placeholder="Describe the issue in detail..."></textarea></div>
<div class="mt-2"><button type="submit" class="btn btn-primary">Submit Complaint</button></div>
</form></div></div>
<?php else: ?>
<div class="alert alert-error"><span class="alert-icon">✕</span>You must be assigned a room before submitting a complaint.</div>
<?php endif; ?>
<div class="card"><div class="card-hd"><h2>My Complaints</h2></div>
<div class="tw"><table>
<thead><tr><th>#</th><th>Category</th><th>Description</th><th>Status</th><th>Reported</th><th>Handled By</th></tr></thead>
<tbody>
<?php foreach($myComps as $c):$bc=['Open'=>'bg-red','In Progress'=>'bg-gold','Resolved'=>'bg-green'][$c['status']]??'bg-gray';?>
<tr>
<td class="mono"><?=$c['id']?></td>
<td><?=htmlspecialchars($c['category'])?></td>
<td style="max-width:220px;font-size:.82rem"><?=htmlspecialchars($c['description'])?></td>
<td><span class="badge <?=$bc?>"><?=$c['status']?></span></td>
<td style="font-size:.78rem"><?=date('d M Y',strtotime($c['reported_at']))?></td>
<td><?=$c['handled_by']?htmlspecialchars($c['handled_by']):'<span class="text-muted">—</span>'?></td>
</tr>
<?php endforeach; if(!$myComps) echo '<tr><td colspan="6"><div class="empty-state"><div class="ei">🔧</div>No complaints submitted</div></td></tr>'; ?>
</tbody></table></div></div>
<?php pageClose();?>
