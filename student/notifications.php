<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/layout.php';
requireStudent();
$pdo=getPDO(); $sid=$_SESSION['student_id'];
// Mark all as read
$pdo->prepare('UPDATE student_notifications SET is_read=1 WHERE student_id=?')->execute([$sid]);
$notifs=$pdo->prepare('SELECT n.id, n.message, n.created_at, sn.is_read, sn.received_at FROM student_notifications sn JOIN notifications n ON sn.notification_id=n.id WHERE sn.student_id=? ORDER BY n.created_at DESC');
$notifs->execute([$sid]); $notifs=$notifs->fetchAll();
pageHead('Notifications'); studentSidebar('notifications');
echo '<div class="main">'; topbar('Notifications','Messages from hostel admin');
echo flashHtml();
?>
<div class="ph"><div><h1>My Notifications</h1><p><?=count($notifs)?> message(s)</p></div></div>
<div class="card"><div class="tw"><table>
<thead><tr><th>#</th><th>Message</th><th>Received</th></tr></thead>
<tbody>
<?php foreach($notifs as $n):?>
<tr>
<td class="mono"><?=$n['id']?></td>
<td style="max-width:500px"><?=htmlspecialchars($n['message'])?></td>
<td style="font-size:.78rem"><?=date('d M Y, H:i',strtotime($n['received_at']))?></td>
</tr>
<?php endforeach; if(!$notifs) echo '<tr><td colspan="3"><div class="empty-state"><div class="ei">📢</div>No notifications yet</div></td></tr>'; ?>
</tbody></table></div></div>
<?php pageClose();?>
