<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/layout.php';
requireAdmin();
$pdo=getPDO();
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['broadcast'])) {
    $msg=trim($_POST['message']??'');
    if ($msg) {
        $pdo->prepare('INSERT INTO notifications(admin_id,message) VALUES(?,?)')->execute([$_SESSION['admin_id'],$msg]);
        $nid=$pdo->lastInsertId();
        $pdo->prepare('INSERT INTO student_notifications(student_id,notification_id,is_read) SELECT id,?,0 FROM students')->execute([$nid]);
        $cnt=$pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
        flash('success',"Notification broadcast to {$cnt} students.");
    } else flash('error','Message cannot be empty.');
    header('Location: notifications.php'); exit;
}
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete_notif'])) {
    $pdo->prepare('DELETE FROM notifications WHERE id=?')->execute([(int)$_POST['nid']]);
    flash('success','Notification deleted.'); header('Location: notifications.php'); exit;
}
$notifs=$pdo->query("SELECT n.*,adm.username AS sent_by,(SELECT COUNT(*) FROM student_notifications sn WHERE sn.notification_id=n.id) AS recipients,(SELECT COUNT(*) FROM student_notifications sn WHERE sn.notification_id=n.id AND sn.is_read=1) AS read_cnt FROM notifications n LEFT JOIN admins adm ON n.admin_id=adm.id ORDER BY n.created_at DESC")->fetchAll();
pageHead('Notifications'); adminSidebar('notifications');
echo '<div class="main">'; topbar('Notifications','Broadcast messages to all students');
echo flashHtml();
?>
<div class="ph"><div><h1>Notifications</h1><p><?=count($notifs)?> sent</p></div>
<button class="btn btn-primary" onclick="document.getElementById('nm').classList.add('open')">📢 Broadcast New</button></div>

<div id="nm" class="modal-overlay" onclick="if(event.target===this)this.classList.remove('open')">
<div class="modal-box">
<div class="modal-hd"><h2>Broadcast Notification</h2><button class="modal-close" onclick="document.getElementById('nm').classList.remove('open')">✕</button></div>
<div class="alert alert-info"><span class="alert-icon">ℹ</span>This message will be sent to <strong>all registered students</strong> immediately.</div>
<form method="POST"><input type="hidden" name="broadcast" value="1">
<div class="fgroup"><label>Message *</label><textarea name="message" class="fc" rows="4" required placeholder="Enter your announcement here..."></textarea></div>
<div class="flex-c mt-2" style="justify-content:flex-end;gap:10px">
<button type="button" class="btn btn-ghost" onclick="document.getElementById('nm').classList.remove('open')">Cancel</button>
<button type="submit" class="btn btn-primary">📢 Send to All Students</button>
</div></form></div></div>

<div class="card"><div class="tw"><table>
<thead><tr><th>#</th><th>Message</th><th>Sent By</th><th>Recipients</th><th>Read</th><th>Date</th><th>Delete</th></tr></thead>
<tbody>
<?php foreach($notifs as $n):$pct=$n['recipients']>0?round($n['read_cnt']/$n['recipients']*100):0;?>
<tr>
<td class="mono"><?=$n['id']?></td>
<td style="max-width:320px"><?=htmlspecialchars($n['message'])?></td>
<td><?=htmlspecialchars($n['sent_by']??'—')?></td>
<td><?=$n['recipients']?> students</td>
<td>
<div style="display:flex;align-items:center;gap:7px">
<div style="width:60px;height:6px;background:var(--g200);border-radius:3px;overflow:hidden"><div style="width:<?=$pct?>%;height:100%;background:var(--green);border-radius:3px"></div></div>
<span style="font-size:.78rem"><?=$n['read_cnt']?>/<?=$n['recipients']?></span>
</div>
</td>
<td style="font-size:.78rem"><?=date('d M Y, H:i',strtotime($n['created_at']))?></td>
<td><form method="POST" style="display:inline" onsubmit="return confirm('Delete this notification?')">
<input type="hidden" name="delete_notif" value="1"><input type="hidden" name="nid" value="<?=$n['id']?>">
<button class="btn btn-danger btn-xs">Delete</button></form></td>
</tr>
<?php endforeach; if(!$notifs) echo '<tr><td colspan="7"><div class="empty-state"><div class="ei">📢</div>No notifications sent yet</div></td></tr>'; ?>
</tbody></table></div></div>
<?php pageClose();?>
