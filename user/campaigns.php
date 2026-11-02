<?php
session_start();
if (($_SESSION['role'] ?? '') !== 'user' || empty($_SESSION['user_id'])) {
    header('Location: /index.php'); exit;
}
require_once __DIR__ . '/campaign-lib.php';
header('Cache-Control: no-store');
header('X-Frame-Options: SAMEORIGIN');
$_SESSION['campaign_csrf'] = $_SESSION['campaign_csrf'] ?? bin2hex(random_bytes(32));
$csrf = $_SESSION['campaign_csrf'];
$userId = (int) $_SESSION['user_id'];
function esc($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
$error = ''; $rows = []; $saved = []; $ready = false;
$editing = ['id' => 0, 'name' => '', 'url' => '', 'source_url' => '', 'device' => 'both'];
try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    require __DIR__ . '/../include/config2.php';
    $conn->set_charset('utf8mb4');
    campaign_schema($conn);
    $ready = true;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!is_string($_POST['csrf'] ?? null) || !hash_equals($csrf, $_POST['csrf'])) {
            http_response_code(403); exit('Session expired. Reload the page and try again.');
        }
        $action = $_POST['action'] ?? '';
        $id = (int)($_POST['id'] ?? 0);
        if ($action === 'save') {
            $name = is_string($_POST['name'] ?? null) ? trim($_POST['name']) : '';
            $url = is_string($_POST['url'] ?? null) ? trim($_POST['url']) : '';
            $source = is_string($_POST['source_url'] ?? null) ? trim($_POST['source_url']) : '';
            $device = $_POST['device'] ?? 'both';
            $editing = ['id'=>$id, 'name'=>$name, 'url'=>$url, 'source_url'=>$source, 'device'=>$device];
            if ($name === '' || strlen($name) > 120 || !campaign_url_valid($url) ||
                ($source !== '' && !campaign_url_valid($source)) || !in_array($device, ['android','desktop','both'], true)) {
                $error = 'Enter a name (up to 120 bytes), valid HTTP/HTTPS URLs and a device option.';
            } else {
                if ($id > 0) {
                    $stmt = $conn->prepare('UPDATE visitor_campaigns SET name=?, url=?, source_url=?, device=? WHERE id=? AND user_id=?');
                    $stmt->bind_param('ssssii', $name, $url, $source, $device, $id, $userId);
                } else {
                    $token = bin2hex(random_bytes(24));
                    $stmt = $conn->prepare('INSERT INTO visitor_campaigns (user_id, token, name, url, source_url, device) VALUES (?, ?, ?, ?, ?, ?)');
                    $stmt->bind_param('isssss', $userId, $token, $name, $url, $source, $device);
                }
                $stmt->execute();
            }
        } elseif (in_array($action, ['pause','resume','delete'], true) && $id > 0) {
            if ($action === 'delete') {
                $stmt = $conn->prepare('DELETE FROM visitor_campaigns WHERE id=? AND user_id=?');
                $stmt->bind_param('ii', $id, $userId);
            } else {
                $active = $action === 'resume' ? 1 : 0;
                $stmt = $conn->prepare('UPDATE visitor_campaigns SET active=? WHERE id=? AND user_id=?');
                $stmt->bind_param('iii', $active, $id, $userId);
            }
            $stmt->execute();
        } else { $error = 'Unknown action.'; }
        if ($error === '') {
            $_SESSION['campaign_flash'] = 'Campaign updated.';
            header('Location: campaigns.php'); exit;
        }
    }
    $stmt = $conn->prepare('SELECT * FROM visitor_campaigns WHERE user_id=? ORDER BY id DESC');
    $stmt->bind_param('i', $userId); $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_GET['edit'])) {
        foreach ($rows as $row) { if ((int)$row['id'] === (int)$_GET['edit']) $editing = $row; }
    }
    try {
        $stmt = $conn->prepare('SELECT name, url FROM user_links WHERE user_id=? AND type=? ORDER BY id DESC');
        $type = 'affiliate'; $stmt->bind_param('is', $userId, $type); $stmt->execute();
        $saved = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } catch (Throwable $e) { /* Existing saved-link table is optional. */ }
} catch (Throwable $e) {
    error_log('Campaign manager: ' . $e->getMessage());
    $error = 'Campaign storage is unavailable. Please contact the site administrator.';
    $ready = false;
}
$flash = $_SESSION['campaign_flash'] ?? ''; unset($_SESSION['campaign_flash']);
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Campaign Manager</title><style>
*{box-sizing:border-box}body{margin:0;font:15px/1.5 system-ui,sans-serif;color:#163452;background:linear-gradient(125deg,#225ae6,#03a8ba);min-height:100vh}main{max-width:1050px;margin:32px auto;padding:0 18px}header{display:flex;justify-content:space-between;align-items:center;color:white;gap:20px}header a{color:white}h1{font-size:27px;margin:0}h2{font-size:20px;margin:0 0 18px}.panel{background:white;border-radius:22px;padding:30px;margin:22px 0;box-shadow:0 8px 28px #134b7820}.grid{display:grid;grid-template-columns:1fr 1fr;gap:20px}label{display:block;font-weight:650;margin-bottom:7px}input,select{width:100%;padding:13px 15px;border:1px solid #dce4ef;border-radius:10px;background:#f8fafc;color:#163452;font:inherit}small,.muted{color:#64748b}small{display:block;margin-top:7px}button,.button{font:inherit;cursor:pointer;border:1px solid #dce4ef;background:white;color:#235ad6;border-radius:9px;padding:9px 15px;text-decoration:none;display:inline-block}button:hover,.button:hover{background:#edf3ff}.primary{background:#04a879;color:white;border:0;padding:15px 25px;font-weight:700}.primary:hover{background:#008c68}.actions{display:flex;gap:9px;flex-wrap:wrap;margin-top:18px}.actions form{margin:0}.danger{color:#be123c}.card{border:1px solid #e1e8f0;border-radius:14px;padding:20px;margin-top:16px}.card h3{margin:0;font-size:18px}.url{overflow-wrap:anywhere;font-size:13px;color:#64748b}.badge{display:inline-block;font-size:12px;border-radius:20px;padding:3px 10px;background:#e0f7ef;color:#087a59}.paused{background:#fff3d6;color:#916100}.stats{display:flex;gap:18px;flex-wrap:wrap;margin:15px 0}.stats strong{font-size:22px;display:block}.stats span{font-size:12px;color:#64748b}.notice{background:#ecfdf5;border-radius:10px;padding:14px;margin:15px 0}.error{background:#fff1f2;color:#9f1239}.tracking{font-family:monospace;background:#f1f5f9;padding:10px;border-radius:7px;overflow-wrap:anywhere}.topline{display:flex;justify-content:space-between;gap:15px;align-items:center}@media(max-width:650px){.grid{grid-template-columns:1fr}.panel{padding:22px}header{align-items:flex-start}h1{font-size:22px}}
</style></head><body><main>
<header><div><h1>Campaign Manager</h1><div>Save once. Manage your visitor tracking here.</div></div><a href="/user/dashboard.php">Dashboard</a></header>
<?php if ($error): ?><div role="alert" class="notice error"><?= esc($error) ?></div><?php endif ?>
<?php if ($flash): ?><div role="status" class="notice"><?= esc($flash) ?></div><?php endif ?>
<?php if ($ready): ?>
<section class="panel" id="setup"><h2><?= $editing['id'] ? 'Edit campaign' : 'Set up a campaign' ?></h2>
<form method="post" action="campaigns.php"><input type="hidden" name="csrf" value="<?= esc($csrf) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)$editing['id'] ?>">
<div class="grid">
<div><label for="name">Campaign name *</label><input id="name" name="name" required maxlength="120" value="<?= esc($editing['name']) ?>" placeholder="e.g. Summer offer"></div>
<div><label for="url">Destination / affiliate URL *</label><input id="url" name="url" type="url" required maxlength="4096" list="saved-links" value="<?= esc($editing['url']) ?>" placeholder="https://…"><datalist id="saved-links"><?php foreach ($saved as $link): ?><option value="<?= esc($link['url']) ?>"><?= esc($link['name']) ?></option><?php endforeach ?></datalist><small>Choose a saved URL or enter a new one.</small></div>
<div><label for="source">Source website (optional)</label><input id="source" name="source_url" type="url" maxlength="4096" value="<?= esc($editing['source_url']) ?>" placeholder="https://your-website.com"><small>A note about where you plan to share the link.</small></div>
<div><label for="device">Device reporting focus</label><select id="device" name="device"><?php foreach (['both'=>'Both (Android + Desktop)','android'=>'Android','desktop'=>'Desktop'] as $key=>$label): ?><option value="<?= $key ?>" <?= $editing['device']===$key?'selected':'' ?>><?= $label ?></option><?php endforeach ?></select><small>Highlights the selected visits. All devices can open the link; iOS and unknown devices appear under Other.</small></div>
</div><div class="actions"><button class="primary" type="submit"><?= $editing['id'] ? 'Save changes' : 'Save campaign' ?></button><?php if ($editing['id']): ?><a class="button" href="campaigns.php">Cancel edit</a><?php endif ?></div></form></section>
<section class="panel"><h2>Saved campaigns <span class="muted">(<?= count($rows) ?>)</span></h2><p class="muted">Share the tracking link to record incoming visits. Pause stops recording; the destination still opens. Counts are visits, not unique people. Common bots and previews are excluded on a best-effort basis.</p>
<?php if (!$rows): ?><p class="notice">No campaigns yet. Save your first campaign above.</p><?php endif ?>
<?php foreach ($rows as $row): $path='/user/visit.php?c='.$row['token']; $focused=$row['device']==='both'?(int)$row['android_visits']+(int)$row['desktop_visits']:(int)$row[$row['device'].'_visits']; ?>
<article class="card"><div class="topline"><h3><?= esc($row['name']) ?></h3><span class="badge <?= $row['active']?'':'paused' ?>"><?= $row['active']?'Tracking on':'Paused' ?></span></div>
<p class="url"><?= esc($row['url']) ?></p><?php if ($row['source_url']): ?><p class="url">Source note: <?= esc($row['source_url']) ?></p><?php endif ?>
<div class="stats"><div><strong><?= $focused ?></strong><span><?= esc(ucfirst($row['device'])) ?> focus</span></div><div><strong><?= (int)$row['android_visits'] ?></strong><span>Android</span></div><div><strong><?= (int)$row['desktop_visits'] ?></strong><span>Desktop</span></div><div><strong><?= (int)$row['other_visits'] ?></strong><span>Other</span></div></div>
<div class="tracking" data-path="<?= esc($path) ?>"><?= esc($path) ?></div>
<div class="actions"><button type="button" data-copy="<?= esc($path) ?>">Copy tracking link</button><a class="button" href="?edit=<?= (int)$row['id'] ?>#setup">Edit</a>
<form method="post" action="campaigns.php"><input type="hidden" name="csrf" value="<?= esc($csrf) ?>"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><input type="hidden" name="action" value="<?= $row['active']?'pause':'resume' ?>"><button type="submit"><?= $row['active']?'Pause tracking':'Resume tracking' ?></button></form>
<form method="post" action="campaigns.php" data-delete><input type="hidden" name="csrf" value="<?= esc($csrf) ?>"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><input type="hidden" name="action" value="delete"><button class="danger" type="submit">Delete</button></form></div></article>
<?php endforeach ?></section><?php endif ?>
<p style="color:white">Visit totals update when you refresh this page.</p></main><script>
document.querySelectorAll('[data-path]').forEach(el=>{el.textContent=location.origin+el.dataset.path});
document.querySelectorAll('[data-copy]').forEach(el=>el.addEventListener('click',async()=>{const link=location.origin+el.dataset.copy;try{await navigator.clipboard.writeText(link);el.textContent='Copied';setTimeout(()=>el.textContent='Copy tracking link',1800)}catch(e){window.prompt('Copy tracking link:',link)}}));
document.querySelectorAll('[data-delete]').forEach(el=>el.addEventListener('submit',event=>{if(!confirm('Delete this campaign and its visit totals? Its tracking link will stop working.'))event.preventDefault()}));
</script></body></html>
