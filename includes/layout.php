<?php
function pageHead(string $title, string $css = '../includes/style.css'): void {
    echo '<!DOCTYPE html><html lang="en"><head>';
    echo '<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo "<title>".htmlspecialchars($title)." — Smart Hostel</title>";
    echo "<link rel=\"stylesheet\" href=\"{$css}\">";
    echo '</head><body><div class="layout">';
}
function pageClose(): void { echo '</div></div></div></body></html>'; }

function topbar(string $title, string $sub=''): void {
    $user = $_SESSION['admin_username'] ?? ($_SESSION['student_name'] ?? '');
    $ini  = strtoupper(substr($user,0,2));
    echo '<div class="topbar"><div>';
    echo '<div class="tb-title">'.htmlspecialchars($title).'</div>';
    if ($sub) echo '<div class="tb-sub">'.htmlspecialchars($sub).'</div>';
    echo '</div><div class="tb-right">';
    echo "<div class=\"flex-c\"><div class=\"avatar\">{$ini}</div><span style=\"font-size:.85rem;color:var(--g600)\">".htmlspecialchars($user)."</span></div>";
    echo '</div></div><div class="content">';
}

function adminSidebar(string $active=''): void {
    $nav = [
        'dashboard'     =>['🏠','Dashboard'],
        'students'      =>['👤','Students'],
        'rooms'         =>['🚪','Rooms & Occupancy'],
        'applications'  =>['📋','Applications'],
        'payments'      =>['💳','Payments'],
        'complaints'    =>['🔧','Complaints'],
        'notifications' =>['📢','Notifications'],
    ];
    echo '<aside class="sidebar">';
    echo '<div class="sb-brand"><div class="b-tag">PAF-IAST</div>';
    echo '<div class="b-name">Smart Hostel</div>';
    echo '<span class="b-role">Admin Panel</span></div>';
    echo '<nav class="sb-nav"><div class="nav-lbl">Management</div>';
    foreach ($nav as $k=>[$ic,$lb]) {
        $cls = $active===$k?' active':'';
        echo "<a href=\"{$k}.php\" class=\"nav-item{$cls}\"><span class=\"ni\">{$ic}</span>{$lb}</a>";
    }
    echo '</nav>';
    $u = htmlspecialchars($_SESSION['admin_username']??'Admin');
    echo "<div class=\"sb-foot\">Logged in as <strong style=\"color:#fff\">{$u}</strong><br>";
    echo '<a href="logout.php" style="color:#ef9a9a;font-size:.76rem;">← Logout</a></div>';
    echo '</aside>';
}

function studentSidebar(string $active=''): void {
    $nav = [
        'dashboard'    =>['🏠','My Dashboard'],
        'application'  =>['📋','Room Application'],
        'payment'      =>['💳','My Payments'],
        'complaint'    =>['🔧','Submit Complaint'],
        'notifications'=>['📢','Notifications'],
    ];
    echo '<aside class="sidebar">';
    echo '<div class="sb-brand"><div class="b-tag">PAF-IAST</div>';
    echo '<div class="b-name">Smart Hostel</div>';
    echo '<span class="b-role">Student Portal</span></div>';
    echo '<nav class="sb-nav">';
    foreach ($nav as $k=>[$ic,$lb]) {
        $cls = $active===$k?' active':'';
        $badge='';
        if ($k==='notifications' && isset($_SESSION['student_id'])) {
            try {
                $pdo=getPDO();
                $st=$pdo->prepare('SELECT COUNT(*) FROM student_notifications WHERE student_id=? AND is_read=0');
                $st->execute([$_SESSION['student_id']]);
                $cnt=$st->fetchColumn();
                if ($cnt>0) $badge=" <span class=\"ndot\">{$cnt}</span>";
            } catch(Exception $e){}
        }
        echo "<a href=\"{$k}.php\" class=\"nav-item{$cls}\"><span class=\"ni\">{$ic}</span>{$lb}{$badge}</a>";
    }
    echo '</nav>';
    $u = htmlspecialchars($_SESSION['student_name']??'Student');
    echo "<div class=\"sb-foot\">Logged in as <strong style=\"color:#fff\">{$u}</strong><br>";
    echo '<a href="logout.php" style="color:#ef9a9a;font-size:.76rem;">← Logout</a></div>';
    echo '</aside>';
}
