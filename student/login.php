<?php
// student/login.php
require_once '../includes/db.php';
require_once '../includes/auth.php';
if (isStudent()) { header('Location: dashboard.php'); exit; }

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $r = trim($_POST['roll_no']  ?? '');
    $p = trim($_POST['password'] ?? '');
    if ($r && $p) {
        $pdo  = getPDO();
        $stmt = $pdo->prepare('SELECT * FROM students WHERE roll_no = ?');
        $stmt->execute([$r]);
        $s = $stmt->fetch();
        if ($s && password_verify($p, $s['password'])) {
            $_SESSION['student_id']   = $s['id'];
            $_SESSION['student_name'] = $s['name'];
            $_SESSION['student_roll'] = $s['roll_no'];
            $_SESSION['student_room'] = $s['assigned_room'];
            flash('success', 'Welcome, ' . $s['name'] . '!');
            header('Location: dashboard.php'); exit;
        }
        $err = 'Invalid roll number or password.';
    } else {
        $err = 'Please fill all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Student Login — Smart Hostel</title>
<link rel="stylesheet" href="../includes/style.css">
<style>
body { font-family: 'DM Sans', sans-serif; min-height: 100vh; overflow: hidden; }

.login-page {
  min-height: 100vh; width: 100%;
  display: flex; align-items: center; justify-content: center;
  position: relative;
  background: url('../flowerspicbridge.jpeg') center center / cover no-repeat fixed;
}
.login-page::before {
  content: ''; position: absolute; inset: 0;
  background: linear-gradient(135deg, rgba(5,12,35,0.65) 0%, rgba(8,20,55,0.50) 50%, rgba(5,12,35,0.65) 100%);
  z-index: 0;
}

/* Orbs — green toned */
.orb { position:absolute; border-radius:50%; pointer-events:none; z-index:0; animation:orb-float 8s ease-in-out infinite alternate; }
.orb-1 { width:280px; height:280px; top:-60px; left:-60px;
  background:radial-gradient(circle,rgba(46,117,182,0.20) 0%,transparent 70%); }
.orb-2 { width:220px; height:220px; bottom:-50px; right:-50px;
  background:radial-gradient(circle,rgba(74,144,217,0.18) 0%,transparent 70%); animation-delay:3s; }
@keyframes orb-float { from{transform:translate(0,0) scale(1);} to{transform:translate(18px,18px) scale(1.07);} }

/* Sparkles */
.sparkle { position:absolute; width:3px; height:3px; border-radius:50%; background:rgba(255,255,255,0.75); animation:twinkle 3s ease-in-out infinite alternate; z-index:0; pointer-events:none; }
.sparkle:nth-child(1){ top:10%; left:20%; animation-delay:0.0s; }
.sparkle:nth-child(2){ top:25%; left:78%; animation-delay:0.5s; }
.sparkle:nth-child(3){ top:60%; left:8%;  animation-delay:1.0s; }
.sparkle:nth-child(4){ top:75%; left:85%; animation-delay:1.5s; }
.sparkle:nth-child(5){ top:40%; left:90%; animation-delay:2.0s; }
.sparkle:nth-child(6){ top:88%; left:35%; animation-delay:0.8s; }
@keyframes twinkle { 0%{opacity:.1;transform:scale(.8);} 50%{opacity:1;transform:scale(1.5);} 100%{opacity:.2;transform:scale(1);} }

/* Crystal Panel */
.login-wrap { position:relative; z-index:10; animation:emerge .65s cubic-bezier(.22,1,.36,1) both; }
@keyframes emerge { from{opacity:0;transform:translateY(36px) scale(.96);} to{opacity:1;transform:translateY(0) scale(1);} }

.login-box {
  width: 400px; padding: 36px 38px 30px; border-radius: 22px;
  position: relative; overflow: hidden;
  background: rgba(8, 18, 50, 0.55);
  border: 1px solid rgba(255,255,255,0.22);
  box-shadow: 0 0 0 1px rgba(255,255,255,0.06) inset, 0 2px 0 rgba(255,255,255,0.15) inset,
             0 24px 64px rgba(0,0,0,0.55), 0 4px 20px rgba(46,117,182,0.25);
  backdrop-filter: blur(14px) saturate(1.3);
  -webkit-backdrop-filter: blur(14px) saturate(1.3);
}

@media (max-width: 480px) {
  body {
    overflow-y: auto;
  }
  .login-page {
    height: auto;
    min-height: 100vh;
    padding: 30px 15px;
  }
  .login-box {
    width: 100%;
    max-width: 400px;
    padding: 28px 24px 24px;
    border-radius: 16px;
  }
}
.login-box::before {
  content:''; position:absolute; top:0; left:12%; right:12%; height:1px;
  background:linear-gradient(90deg,transparent,rgba(255,255,255,0.50),transparent);
}

/* Logo */
.login-logo { text-align:center; margin-bottom:18px; }
.logo-ring {
  display:inline-flex; align-items:center; justify-content:center;
  width:60px; height:60px; border-radius:18px;
  background:linear-gradient(145deg,rgba(46,117,182,0.90),rgba(15,35,80,0.95));
  border:1px solid rgba(255,255,255,0.28);
  box-shadow:0 0 0 5px rgba(46,117,182,0.12),0 6px 22px rgba(46,117,182,0.55);
  font-size:1.7rem; margin-bottom:12px;
  animation:pulse-ring 3s ease-in-out infinite;
}
@keyframes pulse-ring {
  0%,100%{box-shadow:0 0 0 5px rgba(46,117,182,0.12),0 6px 22px rgba(46,117,182,0.55);}
  50%    {box-shadow:0 0 0 10px rgba(46,117,182,0.07),0 8px 30px rgba(46,117,182,0.70);}
}
.login-logo h1 { font-size:1.35rem; font-weight:800; color:#fff; letter-spacing:-0.01em; text-shadow:0 2px 10px rgba(0,0,0,0.6); }
.login-logo p  { font-size:.76rem; color:rgba(255,255,255,0.65); margin-top:3px; }

/* Badge */
.role-badge { text-align:center; margin-bottom:20px; }
.role-badge span {
  display:inline-flex; align-items:center; gap:5px;
  font-size:.68rem; font-weight:700; letter-spacing:.13em; text-transform:uppercase;
  color:rgba(255,255,255,0.92);
  background:linear-gradient(135deg,rgba(46,117,182,0.45),rgba(74,144,217,0.28));
  border:1px solid rgba(74,144,217,0.55);
  padding:4px 16px; border-radius:30px;
  box-shadow:0 0 14px rgba(46,117,182,0.22);
}

/* Error */
.alert-error {
  background:rgba(180,0,0,0.30); border:1px solid rgba(255,100,100,0.45); color:#ffcccc;
  border-radius:10px; padding:9px 13px; margin-bottom:14px;
  display:flex; align-items:center; gap:8px; font-size:.83rem; font-weight:500;
  animation:shake .4s ease; text-shadow:0 1px 4px rgba(0,0,0,0.5);
}
@keyframes shake { 0%,100%{transform:translateX(0)} 20%,60%{transform:translateX(-5px)} 40%,80%{transform:translateX(5px)} }

/* Form */
.fg  { display:grid; gap:14px; }
.fgroup { display:flex; flex-direction:column; gap:5px; }
.fgroup label { font-size:.72rem; font-weight:700; color:rgba(255,255,255,0.85); letter-spacing:.07em; text-transform:uppercase; text-shadow:0 1px 4px rgba(0,0,0,0.5); }

/* Inputs */
.fc {
  width:100%; padding:11px 15px !important; border-radius:11px !important;
  font-family:'DM Sans',sans-serif; font-size:.9rem !important;
  color:#fff !important; background:rgba(255,255,255,0.10) !important;
  border:1px solid rgba(255,255,255,0.25) !important; outline:none;
  transition:all .22s ease; text-shadow:0 1px 3px rgba(0,0,0,0.4);
}
.fc::placeholder { color:rgba(255,255,255,0.35) !important; }
.fc:focus { background:rgba(255,255,255,0.16) !important; border-color:rgba(74,144,217,0.75) !important; box-shadow:0 0 0 3px rgba(46,117,182,0.22) !important; }

/* Password wrapper */
.pw-wrap { position:relative; }
.pw-wrap .fc { padding-right:44px !important; }
.pw-toggle { position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:rgba(255,255,255,0.45); font-size:1rem; padding:0; line-height:1; transition:color .2s; }
.pw-toggle:hover { color:rgba(255,255,255,0.90); }

/* Button — green */
.btn-login {
  display:flex; align-items:center; justify-content:center; gap:8px;
  width:100%; margin-top:4px; padding:13px 20px;
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

/* Divider */
.divider { display:flex; align-items:center; gap:10px; margin:20px 0 16px; }
.divider::before,.divider::after { content:''; flex:1; height:1px; background:linear-gradient(90deg,transparent,rgba(255,255,255,0.18),transparent); }
.divider span { font-size:.68rem; color:rgba(255,255,255,0.38); letter-spacing:.06em; text-transform:uppercase; }

/* Footer */
.login-footer { text-align:center; font-size:.80rem; color:rgba(255,255,255,0.50); margin-bottom:6px; }
.login-footer a { color:rgba(100,170,240,0.90); font-weight:600; text-decoration:none; transition:color .2s; }
.login-footer a:hover { color:#fff; }
</style>
</head>
<body>
<div class="login-page">
  <div class="orb orb-1"></div>
  <div class="orb orb-2"></div>
  <div class="sparkle"></div><div class="sparkle"></div><div class="sparkle"></div>
  <div class="sparkle"></div><div class="sparkle"></div><div class="sparkle"></div>

  <div class="login-wrap">
    <div class="login-box">

      <div class="login-logo">
        <div class="logo-ring">🎓</div>
        <h1>Student Portal</h1>
        <p>PAF-IAST Hostel Management System</p>
      </div>

      <div class="role-badge"><span>🎓 &nbsp;Student Login</span></div>

      <?php if ($err): ?>
      <div class="alert alert-error"><span>✕</span><?= htmlspecialchars($err) ?></div>
      <?php endif; ?>

      <form method="POST" autocomplete="off">
        <div class="fg">
          <div class="fgroup">
            <label>Roll Number</label>
            <input type="text" name="roll_no" class="fc" placeholder="B24F0445AI321"
                   value="<?= htmlspecialchars($_POST['roll_no'] ?? '') ?>" required autofocus>
          </div>
          <div class="fgroup">
            <label>Password</label>
            <div class="pw-wrap">
              <input type="password" id="pw" name="password" class="fc" placeholder="••••••••" required>
              <button type="button" class="pw-toggle" onclick="togglePw()">👁</button>
            </div>
          </div>
          <button type="submit" class="btn-login">Login as Student &nbsp;<span class="btn-arrow">→</span></button>
        </div>
      </form>

      <div class="divider"><span>or</span></div>
      <p class="login-footer">No account yet? &nbsp;<a href="register.php">Register here →</a></p>
      <p class="login-footer">Admin? &nbsp;<a href="../admin/login.php">Admin Panel →</a></p>

    </div>
  </div>
</div>
<script>
function togglePw() {
  const pw=document.getElementById('pw'),btn=document.querySelector('.pw-toggle');
  if(pw.type==='password'){pw.type='text';btn.textContent='🙈';}
  else{pw.type='password';btn.textContent='👁';}
}
</script>
</body>
</html>
