<?php
// student/register.php
require_once '../includes/db.php';
require_once '../includes/auth.php';
if (isStudent()) { header('Location: dashboard.php'); exit; }

$err = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name   = trim($_POST['name']     ?? '');
    $email  = trim($_POST['email']    ?? '');
    $roll   = trim($_POST['roll_no']  ?? '');
    $gender = $_POST['gender']        ?? '';
    $pw     = trim($_POST['password'] ?? '');
    $pw2    = trim($_POST['password2']?? '');

    if (!$name || !$email || !$roll || !$gender || !$pw || !$pw2) {
        $err = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = 'Please enter a valid email address.';
    } elseif (strlen($pw) < 6) {
        $err = 'Password must be at least 6 characters.';
    } elseif (!preg_match('/[A-Z]/', $pw)) {
        $err = 'Password must contain at least one uppercase letter (A-Z).';
    } elseif (!preg_match('/[a-z]/', $pw)) {
        $err = 'Password must contain at least one lowercase letter (a-z).';
    } elseif (!preg_match('/[0-9]/', $pw)) {
        $err = 'Password must contain at least one number (0-9).';
    } elseif (!preg_match('/[^A-Za-z0-9]/', $pw)) {
        $err = 'Password must contain at least one special character (e.g. !@#$%^&*).';
    } elseif ($pw !== $pw2) {
        $err = 'Passwords do not match.';
    } else {
        $pdo = getPDO();
        $chkRoll = $pdo->prepare('SELECT id FROM students WHERE roll_no = ?');
        $chkRoll->execute([$roll]);
        if ($chkRoll->fetch()) {
            $err = 'This Roll Number is already registered. Please login instead.';
        } else {
            $chkEmail = $pdo->prepare('SELECT id FROM students WHERE email = ?');
            $chkEmail->execute([$email]);
            if ($chkEmail->fetch()) {
                $err = 'This email is already registered.';
            } else {
                $hashed = password_hash($pw, PASSWORD_BCRYPT);
                $pdo->prepare('INSERT INTO students (name, email, password, roll_no, gender) VALUES (?, ?, ?, ?, ?)')->execute([$name, $email, $hashed, $roll, $gender]);
                $newId = $pdo->lastInsertId();
                $_SESSION['student_id']   = $newId;
                $_SESSION['student_name'] = $name;
                $_SESSION['student_roll'] = $roll;
                $_SESSION['student_room'] = null;
                flash('success', 'Welcome, ' . $name . '! Your account has been created successfully.');
                header('Location: dashboard.php'); exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Student Register — Smart Hostel</title>
<link rel="stylesheet" href="../includes/style.css">
<style>
html, body { height: 100%; overflow: hidden; font-family: 'DM Sans', sans-serif; }

.login-page {
  height: 100vh; width: 100%;
  display: flex; align-items: center; justify-content: center;
  position: relative;
  background: url('../flowerspicbridge.jpeg') center center / cover no-repeat fixed;
}
.login-page::before {
  content: ''; position: absolute; inset: 0;
  background: linear-gradient(135deg, rgba(5,12,35,0.65) 0%, rgba(8,20,55,0.50) 50%, rgba(5,12,35,0.65) 100%);
  z-index: 0;
}

.orb { position:absolute; border-radius:50%; pointer-events:none; z-index:0; animation:orb-float 8s ease-in-out infinite alternate; }
.orb-1 { width:280px; height:280px; top:-60px; left:-60px; background:radial-gradient(circle,rgba(46,117,182,0.20) 0%,transparent 70%); }
.orb-2 { width:220px; height:220px; bottom:-50px; right:-50px; background:radial-gradient(circle,rgba(74,144,217,0.18) 0%,transparent 70%); animation-delay:3s; }
@keyframes orb-float { from{transform:translate(0,0) scale(1);} to{transform:translate(18px,18px) scale(1.07);} }

.sparkle { position:absolute; width:3px; height:3px; border-radius:50%; background:rgba(255,255,255,0.75); animation:twinkle 3s ease-in-out infinite alternate; z-index:0; pointer-events:none; }
.sparkle:nth-child(1){ top:10%; left:20%; animation-delay:0.0s; }
.sparkle:nth-child(2){ top:25%; left:78%; animation-delay:0.5s; }
.sparkle:nth-child(3){ top:60%; left:8%;  animation-delay:1.0s; }
.sparkle:nth-child(4){ top:75%; left:85%; animation-delay:1.5s; }
.sparkle:nth-child(5){ top:40%; left:90%; animation-delay:2.0s; }
@keyframes twinkle { 0%{opacity:.1;transform:scale(.8);} 50%{opacity:1;transform:scale(1.5);} 100%{opacity:.2;transform:scale(1);} }

.login-wrap { position:relative; z-index:10; animation:emerge .65s cubic-bezier(.22,1,.36,1) both; }
@keyframes emerge { from{opacity:0;transform:translateY(36px) scale(.96);} to{opacity:1;transform:translateY(0) scale(1);} }

.login-box {
  width: 500px; padding: 18px 30px 16px; border-radius: 22px;
  position: relative; overflow: hidden;
  background: rgba(8, 18, 50, 0.55);
  border: 1px solid rgba(255,255,255,0.22);
  box-shadow: 0 0 0 1px rgba(255,255,255,0.06) inset, 0 2px 0 rgba(255,255,255,0.15) inset,
             0 24px 64px rgba(0,0,0,0.55), 0 4px 20px rgba(46,117,182,0.25);
  backdrop-filter: blur(14px) saturate(1.3);
  -webkit-backdrop-filter: blur(14px) saturate(1.3);
}
.login-box::before {
  content:''; position:absolute; top:0; left:12%; right:12%; height:1px;
  background:linear-gradient(90deg,transparent,rgba(255,255,255,0.50),transparent);
}

.login-logo { text-align:center; margin-bottom:6px; }
.logo-ring {
  display:inline-flex; align-items:center; justify-content:center;
  width:38px; height:38px; border-radius:12px;
  background:linear-gradient(145deg,rgba(46,117,182,0.90),rgba(15,35,80,0.95));
  border:1px solid rgba(255,255,255,0.28);
  box-shadow:0 0 0 4px rgba(46,117,182,0.12),0 4px 16px rgba(46,117,182,0.55);
  font-size:1.1rem; margin-bottom:5px;
  animation:pulse-ring 3s ease-in-out infinite;
}
@keyframes pulse-ring {
  0%,100%{box-shadow:0 0 0 5px rgba(46,117,182,0.12),0 6px 22px rgba(46,117,182,0.55);}
  50%    {box-shadow:0 0 0 10px rgba(46,117,182,0.07),0 8px 30px rgba(46,117,182,0.70);}
}
.login-logo h1 { font-size:1.05rem; font-weight:800; color:#fff; text-shadow:0 2px 10px rgba(0,0,0,0.6); }
.login-logo p  { font-size:.70rem; color:rgba(255,255,255,0.60); margin-top:2px; }

.role-badge { text-align:center; margin-bottom:8px; }
.role-badge span {
  display:inline-flex; align-items:center; gap:5px;
  font-size:.68rem; font-weight:700; letter-spacing:.13em; text-transform:uppercase;
  color:rgba(255,255,255,0.92);
  background:linear-gradient(135deg,rgba(46,117,182,0.45),rgba(74,144,217,0.28));
  border:1px solid rgba(74,144,217,0.55);
  padding:4px 16px; border-radius:30px;
  box-shadow:0 0 14px rgba(46,117,182,0.25);
}

.alert-error {
  background:rgba(180,0,0,0.30); border:1px solid rgba(255,100,100,0.45); color:#ffcccc;
  border-radius:10px; padding:9px 13px; margin-bottom:14px;
  display:flex; align-items:center; gap:8px; font-size:.83rem;
  animation:shake .4s ease;
}
@keyframes shake { 0%,100%{transform:translateX(0)} 20%,60%{transform:translateX(-5px)} 40%,80%{transform:translateX(5px)} }

.fg  { display:grid; gap:6px; }
.fg2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.fgroup { display:flex; flex-direction:column; gap:2px; }
.fgroup label { font-size:.64rem; font-weight:700; color:rgba(255,255,255,0.85); letter-spacing:.07em; text-transform:uppercase; text-shadow:0 1px 4px rgba(0,0,0,0.5); }
.fgroup small { font-size:.62rem; color:rgba(255,255,255,0.45); margin-top:1px; }

@media (max-width: 600px) {
  html, body {
    overflow-y: auto;
  }
  .login-page {
    height: auto;
    min-height: 100vh;
    padding: 30px 15px;
  }
  .login-box {
    width: 100%;
    max-width: 500px;
    padding: 24px 20px 20px;
    border-radius: 16px;
  }
  .fg2 {
    grid-template-columns: 1fr;
    gap: 8px;
  }
}

.fc {
  width:100%; padding:11px 15px !important; border-radius:11px !important;
  font-family:'DM Sans',sans-serif; font-size:.9rem !important;
  color:#fff !important; background:rgba(255,255,255,0.10) !important;
  border:1px solid rgba(255,255,255,0.22) !important; outline:none;
  transition:all .22s ease;
}
.fc::placeholder { color:rgba(255,255,255,0.32) !important; }
.fc:focus { background:rgba(255,255,255,0.16) !important; border-color:rgba(74,144,217,0.75) !important; box-shadow:0 0 0 3px rgba(46,117,182,0.22) !important; }
select.fc option { background:#0f2350; color:#fff; }

.pw-wrap { position:relative; }
.pw-wrap .fc { padding-right:42px !important; }
.pw-toggle { position:absolute; right:11px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:rgba(255,255,255,0.45); font-size:.95rem; padding:0; line-height:1; transition:color .2s; }
.pw-toggle:hover { color:rgba(255,255,255,0.90); }

/* Strength bar */
.strength-bar { height:3px; border-radius:3px; background:rgba(255,255,255,0.12); margin-top:3px; overflow:hidden; }
.strength-bar div { height:100%; border-radius:3px; width:0; transition:.3s; }
.strength-label { font-size:.64rem; margin-top:2px; }
.match-msg { font-size:.66rem; margin-top:2px; }

.btn-login {
  display:flex; align-items:center; justify-content:center; gap:8px;
  width:100%; margin-top:2px; padding:9px 20px;
  font-family:'DM Sans',sans-serif; font-size:.92rem; font-weight:700;
  letter-spacing:.04em; color:#fff; border:none; border-radius:13px;
  cursor:pointer; position:relative; overflow:hidden;
  background:linear-gradient(135deg,#2e75b6 0%,#0f2350 60%,#2e75b6 100%);
  background-size:200% auto;
  box-shadow:0 4px 18px rgba(46,117,182,0.55),0 1px 0 rgba(255,255,255,0.15) inset;
  transition:all .3s ease;
}
.btn-login::before { content:''; position:absolute; top:0; left:-100%; width:100%; height:100%; background:linear-gradient(90deg,transparent,rgba(255,255,255,0.14),transparent); transition:left .5s ease; }
.btn-login:hover { background-position:right center; box-shadow:0 8px 28px rgba(46,117,182,0.70); transform:translateY(-2px); }
.btn-login:hover::before { left:100%; }
.btn-arrow { transition:transform .2s; }
.btn-login:hover .btn-arrow { transform:translateX(4px); }

.divider { display:flex; align-items:center; gap:10px; margin:8px 0 6px; }
.divider::before,.divider::after { content:''; flex:1; height:1px; background:linear-gradient(90deg,transparent,rgba(255,255,255,0.18),transparent); }
.divider span { font-size:.68rem; color:rgba(255,255,255,0.38); letter-spacing:.06em; text-transform:uppercase; }

.login-footer { text-align:center; font-size:.78rem; color:rgba(255,255,255,0.50); margin-bottom:3px; }
.login-footer a { color:rgba(100,170,240,0.90); font-weight:600; text-decoration:none; transition:color .2s; }
.login-footer a:hover { color:#fff; }
</style>
</head>
<body>
<div class="login-page">
  <div class="orb orb-1"></div>
  <div class="orb orb-2"></div>
  <div class="sparkle"></div><div class="sparkle"></div><div class="sparkle"></div>
  <div class="sparkle"></div><div class="sparkle"></div>

  <div class="login-wrap">
    <div class="login-box">

      <div class="login-logo">
        <div class="logo-ring">🎓</div>
        <h1>Student Registration</h1>
        <p>PAF-IAST Hostel Management System</p>
      </div>
      <div class="role-badge"><span>✏️ &nbsp;New Student Account</span></div>

      <?php if ($err): ?>
      <div class="alert alert-error"><span>✕</span><?= htmlspecialchars($err) ?></div>
      <?php endif; ?>

      <form method="POST" autocomplete="off">
        <div class="fg">

          <div class="fg fg2">
            <div class="fgroup">
              <label>Full Name *</label>
              <input type="text" name="name" class="fc" placeholder="Muhammad Owais"
                     value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
            </div>
            <div class="fgroup">
              <label>Gender *</label>
              <select name="gender" class="fc" required>
                <option value="">Select gender</option>
                <option value="Male"   <?= ($_POST['gender']??'')==='Male'   ?'selected':'' ?>>Male</option>
                <option value="Female" <?= ($_POST['gender']??'')==='Female' ?'selected':'' ?>>Female</option>
              </select>
            </div>
          </div>

          <div class="fg fg2">
            <div class="fgroup">
              <label>Roll Number *</label>
              <input type="text" name="roll_no" class="fc" placeholder="B24F0445AI321"
                     value="<?= htmlspecialchars($_POST['roll_no'] ?? '') ?>" required>
              <small>⭐ Your login username</small>
            </div>
            <div class="fgroup">
              <label>Email Address *</label>
              <input type="email" name="email" class="fc" placeholder="owais@pafiast.edu"
                     value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
          </div>

          <div class="fg fg2">
            <div class="fgroup">
              <label>Password * <span style="font-weight:400;text-transform:none;letter-spacing:0;color:rgba(255,255,255,0.45)">(min 6)</span></label>
              <div class="pw-wrap">
                <input type="password" name="password" id="pw1" class="fc"
                       placeholder="Create a strong password"
                       oninput="checkStrength(this.value)" required>
                <button type="button" class="pw-toggle" onclick="togglePw('pw1',this)">👁</button>
              </div>
              <div class="strength-bar"><div id="strengthBar"></div></div>
              <div id="strengthLabel" class="strength-label"></div>
            </div>
            <div class="fgroup">
              <label>Confirm Password *</label>
              <div class="pw-wrap">
                <input type="password" name="password2" id="pw2" class="fc"
                       placeholder="Repeat your password"
                       oninput="checkMatch()" required>
                <button type="button" class="pw-toggle" onclick="togglePw('pw2',this)">👁</button>
              </div>
              <div id="matchMsg" class="match-msg"></div>
            </div>
          </div>

          <button type="submit" class="btn-login">
            Create Account &nbsp;<span class="btn-arrow">→</span>
          </button>

        </div>
      </form>

      <div class="divider"><span>or</span></div>
      <p class="login-footer">Already have an account? &nbsp;<a href="login.php">Login here →</a></p>
      <p class="login-footer">Admin? &nbsp;<a href="../admin/login.php">Admin Panel →</a></p>

    </div>
  </div>
</div>

<script>
function togglePw(id, btn) {
  const el = document.getElementById(id);
  if (el.type==='password') { el.type='text'; btn.textContent='🙈'; }
  else { el.type='password'; btn.textContent='👁'; }
}
function checkStrength(val) {
  const bar=document.getElementById('strengthBar'), label=document.getElementById('strengthLabel');
  let s=0;
  if(val.length>=6)s++; if(val.length>=10)s++;
  if(/[A-Z]/.test(val))s++; if(/[0-9]/.test(val))s++; if(/[^A-Za-z0-9]/.test(val))s++;
  const lvl=[
    {w:'0%',c:'',t:''},
    {w:'25%',c:'#e74c3c',t:'Weak'},
    {w:'50%',c:'#f39c12',t:'Fair'},
    {w:'75%',c:'#3498db',t:'Good'},
    {w:'100%',c:'#27ae60',t:'Strong ✓'},
  ][Math.min(s,4)];
  bar.style.width=lvl.w; bar.style.background=lvl.c;
  label.textContent=lvl.t; label.style.color=lvl.c;
}
function checkMatch() {
  const p1=document.getElementById('pw1').value, p2=document.getElementById('pw2').value;
  const msg=document.getElementById('matchMsg');
  if(!p2){msg.textContent='';return;}
  if(p1===p2){msg.textContent='✓ Passwords match';msg.style.color='#27ae60';}
  else{msg.textContent='✕ Do not match';msg.style.color='#e74c3c';}
}
</script>
</body>
</html>
