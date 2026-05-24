<?php
// admin/students.php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/layout.php';
requireAdmin();

$pdo = getPDO();

$modalOpen = false;
$modalError = '';
$postData = [
    'name' => '',
    'email' => '',
    'roll_no' => '',
    'gender' => '',
    'room' => '',
];

// ── DELETE ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $pdo->prepare('DELETE FROM students WHERE id = ?')->execute([(int)$_POST['delete_id']]);
    flash('success', 'Student deleted.');
    header('Location: students.php'); exit;
}

// ── ADD STUDENT ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $n  = trim($_POST['name']     ?? '');
    $e  = trim($_POST['email']    ?? '');
    $pw = trim($_POST['password'] ?? '');
    $r  = trim($_POST['roll_no']  ?? '');
    $g  = $_POST['gender']        ?? '';
    $rm = $_POST['room']          ?? null;

    $postData = [
        'name' => $n,
        'email' => $e,
        'roll_no' => $r,
        'gender' => $g,
        'room' => $rm,
    ];

    if ($n && $e && $pw && $r && $g) {
        // Gender & Capacity guard: verify chosen room matches student gender and has vacancy
        if ($rm) {
            $roomCheck = $pdo->prepare('SELECT room_number, gender, capacity, current_occupants FROM room_occupancy WHERE room_id = ?');
            $roomCheck->execute([$rm]);
            $rc = $roomCheck->fetch();
            if ($rc) {
                if ($rc['gender'] !== $g) {
                    $modalError = "Cannot assign: Room {$rc['room_number']} is for {$rc['gender']} students but student is {$g}.";
                    $modalOpen = true;
                } elseif ($rc['current_occupants'] >= $rc['capacity']) {
                    $modalError = "Cannot assign: Room {$rc['room_number']} is already at full capacity ({$rc['current_occupants']}/{$rc['capacity']} occupied).";
                    $modalOpen = true;
                }
            }
        }

        if (!$modalOpen) {
            // Check for duplicate roll number
            $chkRoll = $pdo->prepare('SELECT id FROM students WHERE roll_no = ?');
            $chkRoll->execute([$r]);
            if ($chkRoll->fetch()) {
                $modalError = 'A student with this Roll Number is already registered.';
                $modalOpen = true;
            }
        }

        if (!$modalOpen) {
            // Check for duplicate email
            $chkEmail = $pdo->prepare('SELECT id FROM students WHERE email = ?');
            $chkEmail->execute([$e]);
            if ($chkEmail->fetch()) {
                $modalError = 'A student with this Email Address is already registered.';
                $modalOpen = true;
            }
        }

        if (!$modalOpen) {
            try {
                // Hash password before storing
                $hashedPw = password_hash($pw, PASSWORD_BCRYPT);
                $pdo->prepare(
                    'INSERT INTO students(name,email,password,roll_no,gender,assigned_room)
                     VALUES(?,?,?,?,?,?)'
                )->execute([$n, $e, $hashedPw, $r, $g, $rm ?: null]);
                flash('success', "Student {$n} added successfully.");
                header('Location: students.php'); exit;
            } catch (PDOException $ex) {
                if ($ex->getCode() == 23000) {
                    $modalError = 'Database constraint violation. Roll number or email already exists.';
                } else {
                    $modalError = 'An error occurred: ' . $ex->getMessage();
                }
                $modalOpen = true;
            }
        }
    } else {
        $modalError = 'Please fill all required fields.';
        $modalOpen = true;
    }
}

// ── SEARCH ────────────────────────────────────────────────
$q      = trim($_GET['q'] ?? '');
$params = [];
$where  = '';
if ($q) {
    $where  = 'WHERE s.name LIKE ? OR s.roll_no LIKE ? OR s.email LIKE ?';
    $params = ["%$q%", "%$q%", "%$q%"];
}

$stmt = $pdo->prepare(
    "SELECT s.*, COALESCE(r.room_number, 'Not Assigned') AS rn
     FROM   students s LEFT JOIN rooms r ON s.assigned_room = r.id
     $where ORDER BY s.id DESC"
);
$stmt->execute($params);
$students = $stmt->fetchAll();

// All rooms with occupancy and capacity — gender will be used in JS to filter dropdown
$rooms = $pdo->query("SELECT room_id AS id, room_number, gender, capacity, current_occupants, available_slots FROM room_occupancy ORDER BY room_number")->fetchAll();

pageHead('Students'); adminSidebar('students');
echo '<div class="main">'; topbar('Student Management', 'Add, search, and remove students');
echo flashHtml();
?>

<div class="ph">
    <div><h1>Students</h1><p><?= count($students) ?> found</p></div>
    <button class="btn btn-primary"
            onclick="openAddStudentModal()">+ Add Student</button>
</div>

<form method="GET" class="flex-c mb-2" style="gap:8px">
    <input type="text" name="q" class="fc" style="max-width:300px"
           placeholder="Search name / roll no / email..."
           value="<?= htmlspecialchars($q) ?>">
    <button type="submit" class="btn btn-primary btn-sm">Search</button>
    <?php if ($q): ?><a href="students.php" class="btn btn-ghost btn-sm">Clear</a><?php endif; ?>
</form>

<div class="card"><div class="tw"><table>
<thead><tr>
    <th>#</th><th>Name</th><th>Roll No</th><th>Email</th><th>Gender</th><th>Room</th><th>Actions</th>
</tr></thead>
<tbody>
<?php foreach ($students as $s): $gc = $s['gender'] === 'Male' ? 'bg-blue' : 'bg-gold'; ?>
<tr>
    <td class="mono"><?= $s['id'] ?></td>
    <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
    <td><span class="mono"><?= htmlspecialchars($s['roll_no']) ?></span></td>
    <td><?= htmlspecialchars($s['email']) ?></td>
    <td><span class="badge <?= $gc ?>"><?= $s['gender'] ?></span></td>
    <td><?= htmlspecialchars($s['rn']) ?></td>
    <td>
        <form method="POST" style="display:inline"
              onsubmit="return confirm('Delete <?= htmlspecialchars(addslashes($s['name'])) ?>?')">
            <input type="hidden" name="delete_id" value="<?= $s['id'] ?>">
            <button class="btn btn-danger btn-xs">Delete</button>
        </form>
    </td>
</tr>
<?php endforeach;
if (!$students) echo '<tr><td colspan="7"><div class="empty-state"><div class="ei">👤</div>No students found</div></td></tr>';
?>
</tbody></table></div></div>

<!-- Add Student Modal -->
<div id="am" class="modal-overlay <?= $modalOpen ? 'open' : '' ?>">
<div class="modal-box">
<div class="modal-hd">
    <h2>Add New Student</h2>
    <button class="modal-close" onclick="document.getElementById('am').classList.remove('open')">✕</button>
</div>
<?php if ($modalError): ?>
    <div class="alert alert-error" style="margin-bottom:15px; padding:9px 13px; font-size:.83rem">
        <span class="alert-icon">✕</span>
        <?= htmlspecialchars($modalError) ?>
    </div>
<?php endif; ?>
<form method="POST" id="addStudentForm">
<input type="hidden" name="add" value="1">
<div class="fg fg2">
    <div class="fgroup"><label>Full Name *</label>
        <input type="text" name="name" class="fc" required placeholder="Muhammad Owais" value="<?= htmlspecialchars($postData['name'] ?? '') ?>"></div>
    <div class="fgroup"><label>Roll No *</label>
        <input type="text" name="roll_no" class="fc" required placeholder="B24F0445AI321" value="<?= htmlspecialchars($postData['roll_no'] ?? '') ?>"></div>
    <div class="fgroup"><label>Email *</label>
        <input type="email" name="email" class="fc" required placeholder="B24f0445ai231@pafiast.edu.pk" value="<?= htmlspecialchars($postData['email'] ?? '') ?>"></div>
    <div class="fgroup"><label>Password *</label>
        <div style="position:relative; display:flex; align-items:center;">
            <input type="password" name="password" id="studentPassword" class="fc" required placeholder="Initial password" style="padding-right: 40px;" value="<?= htmlspecialchars($pw ?? '') ?>">
            <button type="button" onclick="togglePasswordVisibility()" style="position:absolute; right:10px; background:none; border:none; cursor:pointer; color:var(--g600); display:flex; align-items:center; justify-content:center; padding: 4px;">
                <svg id="eyeIconOpen" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <svg id="eyeIconClosed" style="display:none" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                </svg>
            </button>
        </div>
    </div>
    <div class="fgroup"><label>Gender *</label>
        <select name="gender" id="studentGender" class="fc" required onchange="filterRooms()">
            <option value="">Select gender</option>
            <option value="Male" <?= ($postData['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
            <option value="Female" <?= ($postData['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
        </select>
    </div>
    <div class="fgroup"><label>Assign Room <small class="text-muted">(gender-filtered)</small></label>
        <select name="room" id="roomSelect" class="fc">
            <option value="">Not Assigned</option>
            <?php foreach ($rooms as $r): ?>
                <?php 
                $statusText = "{$r['current_occupants']}/{$r['capacity']} filled ({$r['available_slots']} free)";
                if ($r['current_occupants'] >= $r['capacity']) {
                    $statusText = "FULL — {$r['capacity']}/{$r['capacity']}";
                }
                ?>
                <option value="<?= $r['id'] ?>" data-gender="<?= $r['gender'] ?>" <?= ($postData['room'] ?? '') == $r['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($r['room_number']) ?> (<?= $r['gender'] ?>) — <?= $statusText ?>
                </option>
            <?php endforeach; ?>
        </select>
        <small class="text-muted" style="margin-top:4px">
            ⓘ Select gender first — only matching rooms will be shown
        </small>
    </div>
</div>
<div class="flex-c mt-2" style="justify-content:flex-end;gap:10px">
    <button type="button" class="btn btn-ghost"
            onclick="document.getElementById('am').classList.remove('open')">Cancel</button>
    <button type="submit" class="btn btn-primary">Add Student</button>
</div>
</form>
</div></div>

<script>
// Gender-match filter: only show rooms matching selected student gender
function filterRooms() {
    const gender = document.getElementById('studentGender').value;
    const sel    = document.getElementById('roomSelect');
    const opts   = sel.querySelectorAll('option');
    opts.forEach(opt => {
        if (!opt.value) return; // keep "Not Assigned"
        opt.hidden = !gender || opt.dataset.gender !== gender;
    });
    // Reset selection if current choice is now hidden
    if (sel.selectedOptions[0] && sel.selectedOptions[0].hidden) sel.value = '';
}

// Toggle student password field visibility (show/hide)
function togglePasswordVisibility() {
    const pwdInput = document.getElementById('studentPassword');
    const eyeOpen = document.getElementById('eyeIconOpen');
    const eyeClosed = document.getElementById('eyeIconClosed');
    
    if (pwdInput.type === 'password') {
        pwdInput.type = 'text';
        eyeOpen.style.display = 'none';
        eyeClosed.style.display = 'block';
    } else {
        pwdInput.type = 'password';
        eyeOpen.style.display = 'block';
        eyeClosed.style.display = 'none';
    }
}

// Run filterRooms on initial load to handle pre-populated gender
document.addEventListener("DOMContentLoaded", function() {
    filterRooms();
});

// Clear, reset form elements and open the modal fresh
function openAddStudentModal() {
    const form = document.getElementById('addStudentForm');
    if (form) {
        form.reset();
        // Clear all inputs explicitly to override the HTML default values pre-populated by PHP
        form.querySelectorAll('input:not([type="hidden"])').forEach(input => {
            input.value = '';
        });
        const genderSelect = document.getElementById('studentGender');
        if (genderSelect) genderSelect.value = '';
        const roomSelect = document.getElementById('roomSelect');
        if (roomSelect) roomSelect.value = '';
    }
    
    // Hide the error banner if it exists
    const errorBanner = document.querySelector('#am .alert-error');
    if (errorBanner) {
        errorBanner.style.display = 'none';
    }
    
    // Re-filter rooms dropdown (hides all rooms since gender is cleared)
    filterRooms();
    
    // Open the modal
    document.getElementById('am').classList.add('open');
}
</script>
<?php pageClose(); ?>
