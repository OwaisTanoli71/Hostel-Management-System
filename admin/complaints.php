<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/layout.php';
requireAdmin();
$pdo=getPDO();
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_status'])) {
    $pdo->prepare('UPDATE complaints SET status=?,admin_id=? WHERE id=?')->execute([$_POST['status'],$_SESSION['admin_id'],(int)$_POST['cid']]);
    flash('success','Complaint status updated.'); header('Location: complaints.php'); exit;
}
$filter=$_GET['filter']??'all';
$where=$filter!=='all'?"WHERE c.status=".getPDO()->quote($filter):'';
$comps=$pdo->query("SELECT c.*,s.name,s.roll_no,r.room_number,adm.username AS handled_by FROM complaints c JOIN students s ON c.student_id=s.id JOIN rooms r ON c.room_id=r.id LEFT JOIN admins adm ON c.admin_id=adm.id $where ORDER BY c.reported_at DESC")->fetchAll();
pageHead('Complaints'); adminSidebar('complaints');
echo '<div class="main">'; topbar('Complaints','Maintenance issue tracker');
echo flashHtml();
?>
<div class="ph"><div><h1>Complaints</h1><p><?=count($comps)?> record(s)</p></div>
<div class="flex-c" style="gap:8px">
<?php foreach(['all'=>'All','Open'=>'Open','In Progress'=>'In Progress','Resolved'=>'Resolved'] as $f=>$l):$ac=$filter===$f?'btn-navy':'btn-ghost';?>
<a href="?filter=<?=urlencode($f)?>" class="btn <?=$ac?> btn-sm"><?=$l?></a>
<?php endforeach;?>
</div></div>
<div class="card"><div class="tw"><table>
<thead><tr><th>#</th><th>Student</th><th>Room</th><th>Category</th><th>Description</th><th>Status</th><th>Reported</th><th>Handled By</th><th>Update</th></tr></thead>
<tbody>
<?php foreach($comps as $c):$bc=['Open'=>'bg-red','In Progress'=>'bg-gold','Resolved'=>'bg-green'][$c['status']]??'bg-gray';?>
<tr>
<td class="mono"><?=$c['id']?></td>
<td><strong><?=htmlspecialchars($c['name'])?></strong><br><span class="mono text-muted"><?=htmlspecialchars($c['roll_no'])?></span></td>
<td><?=htmlspecialchars($c['room_number'])?></td>
<td><?=htmlspecialchars($c['category'])?></td>
<td style="max-width:200px;font-size:.82rem"><?=htmlspecialchars($c['description'])?></td>
<td><span class="badge <?=$bc?>"><?=$c['status']?></span></td>
<td style="font-size:.78rem"><?=date('d M Y',strtotime($c['reported_at']))?></td>
<td><?=$c['handled_by']?htmlspecialchars($c['handled_by']):'<span class="text-muted">—</span>'?></td>
<td>
<form method="POST" style="display:inline-flex;gap:4px;align-items:center">
<input type="hidden" name="cid" value="<?=$c['id']?>">
<select name="status" class="fc" style="padding:4px 8px;font-size:.78rem;width:auto">
<option <?=$c['status']==='Open'?'selected':''?>>Open</option>
<option <?=$c['status']==='In Progress'?'selected':''?>>In Progress</option>
<option <?=$c['status']==='Resolved'?'selected':''?>>Resolved</option>
</select>
<button name="update_status" value="1" class="btn btn-primary btn-xs">Save</button>
</form>
</td>
</tr>
<?php endforeach; if(!$comps) echo '<tr><td colspan="9"><div class="empty-state"><div class="ei">🔧</div>No complaints found</div></td></tr>'; ?>
</tbody></table></div></div>
<?php pageClose();?>
