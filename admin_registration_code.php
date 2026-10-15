<?php
// admin_registration_codes.php - Admin-only tool to issue one-time student registration codes
require_once __DIR__ . '/config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || (($_SESSION['role'] ?? '') !== 'admin')) {
    header('Location: login.php');
    exit;
}

if (empty($_SESSION['admin_code_csrf'])) {
    $_SESSION['admin_code_csrf'] = bin2hex(random_bytes(32));
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string)($_POST['csrf_token'] ?? '');
    $action = (string)($_POST['action'] ?? '');

    if (!hash_equals($_SESSION['admin_code_csrf'], $token)) {
        $error = 'Security check failed. Refresh the page and try again.';
    } elseif ($action === 'generate') {
        $quantity = max(1, min(25, (int)($_POST['quantity'] ?? 1)));
        $expiryDays = (int)($_POST['expiry_days'] ?? 30);
        if (!in_array($expiryDays, [0, 1, 7, 14, 30, 90], true)) {
            $expiryDays = 30;
        }

        $stmt = $conn->prepare(
            "INSERT INTO registration_codes (code, is_used, created_by, expires_at, created_at)
             VALUES (?, 0, ?, ?, NOW())"
        );
        if (!$stmt) {
            $error = 'Registration code table is missing. Run the SQL setup first.';
        } else {
            $created = [];
            $adminId = (int)$_SESSION['user_id'];
            try {
                for ($i = 0; $i < $quantity; $i++) {
                    // Human-friendly code, generated with a cryptographically secure random source.
                    $code = 'CSS-' . strtoupper(bin2hex(random_bytes(4)));
                    $expiresAt = $expiryDays === 0 ? null : date('Y-m-d H:i:s', time() + ($expiryDays * 86400));
                    $stmt->bind_param('sis', $code, $adminId, $expiresAt);
                    if (!$stmt->execute()) {
                        throw new Exception('A code could not be saved. Please try again.');
                    }
                    $created[] = $code;
                }
                $message = 'Registration code(s) generated. Copy and give each code to one student only.';
                $_SESSION['new_registration_codes'] = $created;
                $_SESSION['admin_code_csrf'] = bin2hex(random_bytes(32));
            } catch (Throwable $e) {
                $error = $e->getMessage();
            }
            $stmt->close();
        }
    }
}

$newCodes = $_SESSION['new_registration_codes'] ?? [];
unset($_SESSION['new_registration_codes']);

$codes = [];
$query = $conn->query(
    "SELECT rc.id, rc.code, rc.is_used, rc.created_at, rc.expires_at, rc.used_at,
            u.full_name AS used_by_name
     FROM registration_codes rc
     LEFT JOIN users u ON u.id = rc.used_by
     ORDER BY rc.created_at DESC
     LIMIT 200"
);
if ($query) {
    while ($row = $query->fetch_assoc()) {
        $codes[] = $row;
    }
}
function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Registration Codes | CSS TESDA Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
*{box-sizing:border-box}body{margin:0;background:#080d19;color:#e5f1ff;font-family:Rajdhani,sans-serif;min-height:100vh;background-image:linear-gradient(rgba(0,255,157,.025) 1px,transparent 1px),linear-gradient(90deg,rgba(0,255,157,.025) 1px,transparent 1px);background-size:40px 40px}.page{width:min(1150px,100%);margin:auto;padding:24px 16px 50px}.head{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:22px}.head h1{font:700 clamp(1.1rem,4vw,1.65rem) Orbitron,sans-serif;color:#00ff9d;margin:0 0 7px}.head p{margin:0;color:#94a8c4}.link{color:#00ff9d;text-decoration:none;border:1px solid rgba(0,255,157,.3);border-radius:10px;padding:10px 13px}.panel{background:rgba(11,20,37,.94);border:1px solid rgba(0,255,157,.23);border-radius:17px;padding:20px;margin-bottom:18px;box-shadow:0 14px 40px #0003}.panel h2{font:700 1rem Orbitron,sans-serif;margin:0 0 16px}.form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;align-items:end}.field label{display:block;color:#b9c9df;font-weight:700;margin-bottom:6px}.field select{width:100%;min-height:45px;background:#070d18;color:#e5f1ff;border:1px solid #2d465a;border-radius:9px;padding:10px;font:600 1rem Rajdhani,sans-serif}.btn{min-height:45px;border:0;border-radius:9px;background:linear-gradient(100deg,#00ff9d,#54aaff);color:#07131b;font:700 .85rem Orbitron,sans-serif;padding:12px 16px;cursor:pointer}.notice{padding:12px 14px;border-radius:10px;margin-bottom:14px}.ok{color:#00ff9d;border:1px solid #00ff9d;background:#00ff9d10}.bad{color:#ff8d9a;border:1px solid #ff6377;background:#ff637710}.newcodes{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}.code{font:700 1rem monospace;color:#00ff9d;border:1px dashed rgba(0,255,157,.5);border-radius:8px;padding:10px;background:#00ff9d0b;user-select:all}.table-wrap{overflow-x:auto}table{width:100%;border-collapse:collapse;min-width:720px}th,td{text-align:left;padding:12px 10px;border-bottom:1px solid #233347}th{color:#00ff9d;font-size:.82rem;text-transform:uppercase;letter-spacing:.04em}td{font-size:.98rem}.status{display:inline-block;border-radius:999px;padding:4px 9px;font-size:.8rem;font-weight:700}.unused{color:#00ff9d;background:#00ff9d13}.used{color:#ffbd69;background:#ffbd6915}.expired{color:#ff8d9a;background:#ff637715}.muted{color:#91a4bd}.hint{color:#91a4bd;line-height:1.5;margin:12px 0 0}@media(max-width:600px){.page{padding:17px 11px 35px}.panel{padding:15px}.form{grid-template-columns:1fr}.btn{width:100%}}
</style>
</head>
<body>
<main class="page">
  <header class="head">
    <div><h1><i class="fas fa-key"></i> STUDENT REGISTRATION CODES</h1><p>Generate one-time codes for authorized CSS students.</p></div>
    <a class="link" href="dashboard.php"><i class="fas fa-arrow-left"></i> Admin Dashboard</a>
  </header>

  <?php if ($message): ?><div class="notice ok"><i class="fas fa-circle-check"></i> <?php echo h($message); ?></div><?php endif; ?>
  <?php if ($error): ?><div class="notice bad"><i class="fas fa-circle-exclamation"></i> <?php echo h($error); ?></div><?php endif; ?>

  <?php if ($newCodes): ?>
  <section class="panel">
    <h2><i class="fas fa-copy"></i> Newly Generated Codes</h2>
    <p class="muted">Copy these codes now and send one code to each student. Each code can be used only once.</p>
    <div class="newcodes" id="newCodes"><?php foreach ($newCodes as $code): ?><span class="code"><?php echo h($code); ?></span><?php endforeach; ?></div>
    <button class="btn" type="button" style="margin-top:14px" onclick="copyCodes()"><i class="fas fa-copy"></i> Copy All Codes</button>
  </section>
  <?php endif; ?>

  <section class="panel">
    <h2><i class="fas fa-wand-magic-sparkles"></i> Generate New Codes</h2>
    <form method="post" class="form">
      <input type="hidden" name="csrf_token" value="<?php echo h($_SESSION['admin_code_csrf']); ?>">
      <input type="hidden" name="action" value="generate">
      <div class="field"><label for="quantity">Number of codes</label><select name="quantity" id="quantity"><?php for ($i=1;$i<=25;$i++): ?><option value="<?php echo $i; ?>" <?php echo $i===1?'selected':''; ?>><?php echo $i; ?> code<?php echo $i===1?'':'s'; ?></option><?php endfor; ?></select></div>
      <div class="field"><label for="expiry_days">Code expiry</label><select name="expiry_days" id="expiry_days"><option value="7">7 days</option><option value="14">14 days</option><option value="30" selected>30 days</option><option value="90">90 days</option><option value="0">Never expires</option></select></div>
      <button class="btn" type="submit"><i class="fas fa-plus-circle"></i> Generate Codes</button>
    </form>
    <p class="hint"><i class="fas fa-shield-halved"></i> Do not post codes publicly. A code becomes unavailable after successful registration.</p>
  </section>

  <section class="panel">
    <h2><i class="fas fa-list-check"></i> Recent Registration Codes</h2>
    <div class="table-wrap"><table>
      <thead><tr><th>Code</th><th>Status</th><th>Created</th><th>Expires</th><th>Used by</th></tr></thead>
      <tbody>
      <?php if (!$codes): ?><tr><td colspan="5" class="muted">No codes yet. Generate the first code above.</td></tr>
      <?php else: foreach ($codes as $row):
        $isExpired = empty($row['is_used']) && !empty($row['expires_at']) && strtotime($row['expires_at']) < time();
        $status = !empty($row['is_used']) ? 'USED' : ($isExpired ? 'EXPIRED' : 'AVAILABLE');
        $class = !empty($row['is_used']) ? 'used' : ($isExpired ? 'expired' : 'unused');
      ?>
      <tr><td><strong><?php echo h($row['code']); ?></strong></td><td><span class="status <?php echo $class; ?>"><?php echo $status; ?></span></td><td><?php echo h($row['created_at']); ?></td><td><?php echo $row['expires_at'] ? h($row['expires_at']) : 'Never'; ?></td><td><?php echo h($row['used_by_name'] ?: '—'); ?></td></tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table></div>
  </section>
</main>
<script>
function copyCodes(){
  const codes=[...document.querySelectorAll('#newCodes .code')].map(el=>el.textContent.trim()).join('\n');
  if(!codes)return;
  if(navigator.clipboard&&window.isSecureContext){navigator.clipboard.writeText(codes).then(()=>alert('Codes copied. Give each code to one student only.')).catch(()=>fallbackCopy(codes));}
  else fallbackCopy(codes);
}
function fallbackCopy(text){const area=document.createElement('textarea');area.value=text;document.body.appendChild(area);area.select();document.execCommand('copy');area.remove();alert('Codes copied. Give each code to one student only.');}
</script>
</body>
</html>
