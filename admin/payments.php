<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/layout.php';
requireAdmin();
$pdo=getPDO();
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['mark_paid'])) {
    $pdo->prepare("UPDATE payments SET status='Paid',payment_date=CURDATE(),admin_id=? WHERE id=?")->execute([$_SESSION['admin_id'],(int)$_POST['pay_id']]);
    flash('success','Payment marked as Paid.'); header('Location: payments.php'); exit;
}
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_payment'])) {
    $sid=(int)$_POST['student_id']; $amt=trim($_POST['amount']??''); $sem=trim($_POST['semester']??'');
    if ($sid&&$amt&&$sem) {
        try {
            $pdo->prepare('INSERT INTO payments(student_id,amount,semester) VALUES(?,?,?)')->execute([$sid,$amt,$sem]);
            flash('success','Payment record added.');
        } catch(PDOException $ex){ flash('error',$ex->getMessage()); }
    } else flash('error','Fill all fields.');
    header('Location: payments.php'); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_bulk_payments'])) {
    $session = trim($_POST['session'] ?? '');
    $amt     = trim($_POST['amount'] ?? '');
    $sem     = trim($_POST['semester'] ?? '');
    if ($session && $amt && $sem) {
        $patterns = [];
        $patterns[] = '%' . $session . '%';
        
        // Normalize typos (e.g. 5 to S)
        $normalized = str_replace('5', 'S', $session);
        if ($normalized !== $session) {
            $patterns[] = '%' . $normalized . '%';
        }
        
        // Extract year and semester e.g. F26, S25, 524
        if (preg_match('/([FSfs5])(\d{2})/', $session, $matches)) {
            $semChar = strtoupper($matches[1] === '5' ? 'S' : $matches[1]);
            $yr = $matches[2];
            $patterns[] = '%' . $yr . $semChar . '%';
            $patterns[] = '%B' . $yr . $semChar . '%';
        }
        
        // Extract year and semester e.g. B24F, B245, 24F, 245
        if (preg_match('/(\d{2})([FSfs5])/', $session, $matches)) {
            $semChar = strtoupper($matches[2] === '5' ? 'S' : $matches[2]);
            $yr = $matches[1];
            $patterns[] = '%' . $yr . $semChar . '%';
            $patterns[] = '%B' . $yr . $semChar . '%';
        }
        
        // Extract plain year e.g. B24, 24, 25
        if (preg_match('/(\d{2})/', $session, $matches)) {
            $yr = $matches[1];
            $patterns[] = '%' . $yr . 'F%';
            $patterns[] = '%' . $yr . 'S%';
        }
        
        $patterns = array_values(array_unique($patterns));
        
        try {
            $sqlConditions = [];
            $queryParams = [];
            foreach ($patterns as $pat) {
                $sqlConditions[] = 'roll_no LIKE ?';
                $queryParams[] = $pat;
            }
            $whereClause = implode(' OR ', $sqlConditions);
            $stmt = $pdo->prepare("SELECT id, name FROM students WHERE $whereClause");
            $stmt->execute($queryParams);
            $matchedStudents = $stmt->fetchAll();
            
            if ($matchedStudents) {
                $pdo->beginTransaction();
                $insertStmt = $pdo->prepare('INSERT INTO payments(student_id, amount, semester) VALUES(?, ?, ?)');
                $count = 0;
                foreach ($matchedStudents as $student) {
                    $check = $pdo->prepare('SELECT id FROM payments WHERE student_id = ? AND semester = ?');
                    $check->execute([$student['id'], $sem]);
                    if (!$check->fetch()) {
                        $insertStmt->execute([$student['id'], $amt, $sem]);
                        $count++;
                    }
                }
                $pdo->commit();
                if ($count > 0) {
                    flash('success', "Successfully generated {$count} fee vouchers for session '{$session}'.");
                } else {
                    flash('info', "Fee vouchers for '{$sem}' already exist for all students in session '{$session}'.");
                }
            } else {
                flash('error', "No students found matching session '{$session}'.");
            }
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('error', 'Bulk generation failed: ' . $ex->getMessage());
        }
    } else {
        flash('error', 'Please fill all bulk payment fields.');
    }
    header('Location: payments.php'); exit;
}
$filter=$_GET['filter']??'all';
$where=$filter!=='all'?"WHERE p.status=".getPDO()->quote($filter):'';
$pays=$pdo->query("SELECT p.*,s.name,s.roll_no,adm.username AS verified_by FROM payments p JOIN students s ON p.student_id=s.id LEFT JOIN admins adm ON p.admin_id=adm.id $where ORDER BY p.id DESC")->fetchAll();
$students=$pdo->query('SELECT id,name,roll_no FROM students ORDER BY name')->fetchAll();
pageHead('Payments'); adminSidebar('payments');
echo '<div class="main">'; topbar('Fee Management', 'Track and verify semester hostel fees');
echo flashHtml();
$total=array_sum(array_column($pays,'amount'));
$paid=array_sum(array_map(fn($p)=>$p['status']==='Paid'?$p['amount']:0,$pays));
$pending=array_sum(array_map(fn($p)=>$p['status']==='Pending'?$p['amount']:0,$pays));
?>
<div class="sg">
<div class="sc"><div class="lbl">Total Records</div><div class="val"><?=count($pays)?></div></div>
<div class="sc green"><div class="lbl">Total Paid</div><div class="val" style="font-size:1.55rem; white-space:nowrap;">Rs <?=number_format($paid)?></div></div>
<div class="sc red"><div class="lbl">Total Pending</div><div class="val" style="font-size:1.55rem; white-space:nowrap;">Rs <?=number_format($pending)?></div></div>
</div>

<div class="ph">
    <div>
        <h1>Payment Records</h1>
        <p><?= count($pays) ?> total record(s) matching filter</p>
    </div>
    <div class="flex-c" style="gap:10px">
        <button class="btn btn-navy btn-sm" onclick="document.getElementById('abm').classList.add('open')">📢 Send Bulk Vouchers</button>
        <button class="btn btn-primary btn-sm" onclick="document.getElementById('apm').classList.add('open')">+ Add Single Record</button>
    </div>
</div>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; padding: 0 4px;">
    <div style="font-size:.85rem; color:var(--g600); font-weight:500;">
        Filter: <span class="badge <?= $filter==='all'?'bg-gray':($filter==='Paid'?'bg-green':'bg-red') ?>"><?= ucfirst($filter) ?></span>
    </div>
    <div class="flex-c" style="gap:6px">
        <?php foreach(['all'=>'All','Paid'=>'Paid','Pending'=>'Pending'] as $f=>$l):
            $ac = $filter===$f ? 'btn-navy' : 'btn-ghost'; ?>
            <a href="?filter=<?=$f?>" class="btn <?=$ac?> btn-xs" style="padding:4px 12px; border-radius:15px; font-weight:600; font-size:.76rem;"><?=$l?></a>
        <?php endforeach;?>
    </div>
</div>
<div class="card"><div class="tw"><table>
<thead><tr><th>#</th><th>Student</th><th>Amount</th><th>Semester</th><th>Status</th><th>Payment Date</th><th>Verified By</th><th>Action</th></tr></thead>
<tbody>
<?php foreach($pays as $p):$bc=$p['status']==='Paid'?'bg-green':'bg-red';?>
<tr>
<td class="mono"><?=$p['id']?></td>
<td><strong><?=htmlspecialchars($p['name'])?></strong><br><span class="mono text-muted"><?=htmlspecialchars($p['roll_no'])?></span></td>
<td><strong>Rs <?=number_format($p['amount'],2)?></strong></td>
<td><?=htmlspecialchars($p['semester'])?></td>
<td><span class="badge <?=$bc?>"><?=$p['status']?></span></td>
<td><?=$p['payment_date']?date('d M Y',strtotime($p['payment_date'])):'<span class="text-muted">—</span>'?></td>
<td><?=$p['verified_by']?htmlspecialchars($p['verified_by']):'<span class="text-muted">—</span>'?></td>
<td><?php if($p['status']==='Pending'):?>
<form method="POST" style="display:inline"><input type="hidden" name="mark_paid" value="1"><input type="hidden" name="pay_id" value="<?=$p['id']?>"><button class="btn btn-success btn-xs">✓ Mark Paid</button></form>
<?php else: echo '<span class="text-muted" style="font-size:.8rem">Cleared</span>'; endif;?></td>
</tr>
<?php endforeach; if(!$pays) echo '<tr><td colspan="8"><div class="empty-state"><div class="ei">💳</div>No payment records</div></td></tr>'; ?>
</tbody></table></div></div>

<div id="apm" class="modal-overlay" onclick="if(event.target===this)this.classList.remove('open')">
<div class="modal-box">
<div class="modal-hd"><h2>Add Payment Record</h2><button class="modal-close" onclick="document.getElementById('apm').classList.remove('open')">✕</button></div>
<form method="POST"><input type="hidden" name="add_payment" value="1">
<div class="fg" style="gap:13px">
<div class="fgroup"><label>Student *</label><select name="student_id" class="fc" required><option value="">Select student</option>
<?php foreach($students as $st) echo "<option value=\"{$st['id']}\">{$st['name']} ({$st['roll_no']})</option>"; ?></select></div>
<div class="fgroup"><label>Amount (Rs) *</label><input type="number" name="amount" class="fc" step="0.01" min="1" required placeholder="15000"></div>
<div class="fgroup"><label>Semester *</label><input type="text" name="semester" class="fc" required placeholder="Spring 2026"></div>
</div>
<div class="flex-c mt-2" style="justify-content:flex-end;gap:10px">
<button type="button" class="btn btn-ghost" onclick="document.getElementById('apm').classList.remove('open')">Cancel</button>
<button type="submit" class="btn btn-primary">Add Record</button>
</div></form></div></div>

<div id="abm" class="modal-overlay">
<div class="modal-box">
<div class="modal-hd"><h2>Generate Bulk Fee Vouchers</h2><button class="modal-close" onclick="document.getElementById('abm').classList.remove('open')">✕</button></div>
<form method="POST"><input type="hidden" name="add_bulk_payments" value="1">
<div class="fg" style="gap:13px">
<div class="fgroup"><label>Semester / Fee Title *</label><input type="text" name="semester" class="fc" required placeholder="Spring 2026 Fee"></div>
<div class="fgroup"><label>Amount (Rs) *</label><input type="number" name="amount" class="fc" step="0.01" min="1" required placeholder="15000"></div>
<div class="fgroup"><label>Session / Roll Number Term *</label>
    <input type="text" name="session" class="fc" required placeholder="e.g. F26, S25, B24F">
    <small class="text-muted" style="margin-top: 4px;">
        ⓘ Enter e.g. F26, S25, or B24F to select matching students by roll number.
    </small>
</div>
</div>
<div class="flex-c mt-2" style="justify-content:flex-end;gap:10px">
<button type="button" class="btn btn-ghost" onclick="document.getElementById('abm').classList.remove('open')">Cancel</button>
<button type="submit" class="btn btn-primary">Generate Vouchers</button>
</div></form></div></div>
<?php pageClose();?>
