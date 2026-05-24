<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/layout.php';
requireStudent();
$pdo=getPDO(); $sid=$_SESSION['student_id'];
$pays=$pdo->prepare('SELECT p.*,adm.username AS verified_by FROM payments p LEFT JOIN admins adm ON p.admin_id=adm.id WHERE p.student_id=? ORDER BY p.id DESC');
$pays->execute([$sid]); $pays=$pays->fetchAll();
$total=array_sum(array_column($pays,'amount'));
$paid=array_sum(array_map(fn($p)=>$p['status']==='Paid'?$p['amount']:0,$pays));
$pending=$total-$paid;
pageHead('My Payments'); studentSidebar('payment');
echo '<div class="main">'; topbar('My Payments','Semester fee payment records');
echo flashHtml();
?>
<div class="sg">
<div class="sc"><div class="lbl">Total Records</div><div class="val"><?=count($pays)?></div></div>
<div class="sc green"><div class="lbl">Total Paid</div><div class="val">Rs <?=number_format($paid)?></div></div>
<div class="sc <?=$pending?'red':'green'?>"><div class="lbl">Outstanding</div><div class="val">Rs <?=number_format($pending)?></div></div>
</div>
<div class="card"><div class="card-hd"><h2>Payment History</h2></div>
<div class="tw"><table>
<thead><tr><th>#</th><th>Amount</th><th>Semester</th><th>Status</th><th>Payment Date</th><th>Verified By</th></tr></thead>
<tbody>
<?php foreach($pays as $p):$bc=$p['status']==='Paid'?'bg-green':'bg-red';?>
<tr>
<td class="mono"><?=$p['id']?></td>
<td><strong>Rs <?=number_format($p['amount'],2)?></strong></td>
<td><?=htmlspecialchars($p['semester'])?></td>
<td><span class="badge <?=$bc?>"><?=$p['status']?></span></td>
<td><?=$p['payment_date']?date('d M Y',strtotime($p['payment_date'])):'<span class="text-muted">Not paid yet</span>'?></td>
<td><?=$p['verified_by']?htmlspecialchars($p['verified_by']):'<span class="text-muted">—</span>'?></td>
</tr>
<?php endforeach; if(!$pays) echo '<tr><td colspan="6"><div class="empty-state"><div class="ei">💳</div>No payment records yet</div></td></tr>'; ?>
</tbody></table></div></div>
<?php pageClose();?>
