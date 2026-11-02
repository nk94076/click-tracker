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
$countries = ['IN'=>'India','US'=>'United States','GB'=>'United Kingdom','CA'=>'Canada','AU'=>'Australia','DE'=>'Germany','FR'=>'France','AE'=>'United Arab Emirates','SG'=>'Singapore','NL'=>'Netherlands','IT'=>'Italy','ES'=>'Spain','BR'=>'Brazil','MX'=>'Mexico','JP'=>'Japan','KR'=>'South Korea','ID'=>'Indonesia','MY'=>'Malaysia','PH'=>'Philippines','TH'=>'Thailand','VN'=>'Vietnam','NZ'=>'New Zealand','SA'=>'Saudi Arabia','ZA'=>'South Africa','PK'=>'Pakistan','BD'=>'Bangladesh','NP'=>'Nepal','LK'=>'Sri Lanka','IE'=>'Ireland','SE'=>'Sweden','NO'=>'Norway','DK'=>'Denmark','FI'=>'Finland','CH'=>'Switzerland','AT'=>'Austria','BE'=>'Belgium','PL'=>'Poland','PT'=>'Portugal','TR'=>'Turkey','EG'=>'Egypt','NG'=>'Nigeria','KE'=>'Kenya','AR'=>'Argentina','CL'=>'Chile','CO'=>'Colombia','CN'=>'China','TW'=>'Taiwan','HK'=>'Hong Kong','IL'=>'Israel','QA'=>'Qatar','KW'=>'Kuwait','OM'=>'Oman','BH'=>'Bahrain','RO'=>'Romania','CZ'=>'Czechia','HU'=>'Hungary','GR'=>'Greece','UA'=>'Ukraine'];
$error = ''; $rows = []; $saved = []; $ready = false;
$editing = ['id' => 0, 'name' => '', 'url' => '', 'source_url' => '', 'geos' => '', 'device' => 'both'];
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
            $requestedGeos = $_POST['geos'] ?? [];
            $validGeos = is_array($requestedGeos) && count($requestedGeos) <= 20;
            if ($validGeos) {
                foreach ($requestedGeos as $geo) {
                    if (!is_string($geo) || !isset($countries[$geo])) { $validGeos = false; break; }
                }
            }
            $geos = $validGeos ? implode(',', array_unique($requestedGeos)) : '';
            $editing = ['id'=>$id, 'name'=>$name, 'url'=>$url, 'source_url'=>$source, 'geos'=>$geos, 'device'=>is_string($device)?$device:'both'];
            if (!$validGeos || $name === '' || strlen($name) > 120 || !campaign_url_valid($url) ||
                ($source !== '' && !campaign_url_valid($source)) || !in_array($device, ['android','desktop','both'], true)) {
                $error = 'Enter a name (up to 120 bytes), valid HTTP/HTTPS URLs and a device option.';
            } else {
                if ($id > 0) {
                    $stmt = $conn->prepare('UPDATE visitor_campaigns SET name=?, url=?, source_url=?, device=?, geos=? WHERE id=? AND user_id=?');
                    $stmt->bind_param('sssssii', $name, $url, $source, $device, $geos, $id, $userId);
                } else {
                    $token = bin2hex(random_bytes(24));
                    $stmt = $conn->prepare('INSERT INTO visitor_campaigns (user_id, token, name, url, source_url, device, geos) VALUES (?, ?, ?, ?, ?, ?, ?)');
                    $stmt->bind_param('issssss', $userId, $token, $name, $url, $source, $device, $geos);
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
<title>Campaign Manager</title><link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;650;700;750&display=swap" rel="stylesheet"><style>
*{box-sizing:border-box}body{margin:0;font:15px/1.5 system-ui,sans-serif;color:#163452;background:linear-gradient(125deg,#225ae6,#03a8ba);min-height:100vh}main{max-width:1050px;margin:32px auto;padding:0 18px}header{display:flex;justify-content:space-between;align-items:center;color:white;gap:20px}header a{color:white}h1{font-size:27px;margin:0}h2{font-size:20px;margin:0 0 18px}.panel{background:white;border-radius:22px;padding:30px;margin:22px 0;box-shadow:0 8px 28px #134b7820}.grid{display:grid;grid-template-columns:1fr 1fr;gap:20px}label{display:block;font-weight:650;margin-bottom:7px}input,select{width:100%;padding:13px 15px;border:1px solid #dce4ef;border-radius:10px;background:#f8fafc;color:#163452;font:inherit}small,.muted{color:#64748b}small{display:block;margin-top:7px}button,.button{font:inherit;cursor:pointer;border:1px solid #dce4ef;background:white;color:#235ad6;border-radius:9px;padding:9px 15px;text-decoration:none;display:inline-block}button:hover,.button:hover{background:#edf3ff}.primary{background:#04a879;color:white;border:0;padding:15px 25px;font-weight:700}.primary:hover{background:#008c68}.actions{display:flex;gap:9px;flex-wrap:wrap;margin-top:18px}.actions form{margin:0}.danger{color:#be123c}.card{border:1px solid #e1e8f0;border-radius:14px;padding:20px;margin-top:16px}.card h3{margin:0;font-size:18px}.url{overflow-wrap:anywhere;font-size:13px;color:#64748b}.badge{display:inline-block;font-size:12px;border-radius:20px;padding:3px 10px;background:#e0f7ef;color:#087a59}.paused{background:#fff3d6;color:#916100}.stats{display:flex;gap:18px;flex-wrap:wrap;margin:15px 0}.stats strong{font-size:22px;display:block}.stats span{font-size:12px;color:#64748b}.notice{background:#ecfdf5;border-radius:10px;padding:14px;margin:15px 0}.error{background:#fff1f2;color:#9f1239}.tracking{font-family:monospace;background:#f1f5f9;padding:10px;border-radius:7px;overflow-wrap:anywhere}.topline{display:flex;justify-content:space-between;gap:15px;align-items:center}@media(max-width:650px){.grid{grid-template-columns:1fr}.panel{padding:22px}header{align-items:flex-start}h1{font-size:22px}}

body{background:#f4f7fc;color:#182b4b;font-family:Inter,system-ui,sans-serif}
main{max-width:1160px;margin:0 auto;padding:38px 26px 50px}
header{color:#182b4b;align-items:center;margin-bottom:26px}
header a{color:#365b98;text-decoration:none;font-weight:600;background:white;border:1px solid #dee6f1;padding:10px 16px;border-radius:10px}
header h1{font-size:30px;letter-spacing:-1px;margin-bottom:5px}
header div div{font-size:14px;color:#73819a}
.panel{border:1px solid #e3e9f3;border-radius:18px;box-shadow:0 5px 22px #243f6a05;padding:30px;margin:22px 0}
.panel h2{font-size:19px;letter-spacing:-.3px}
.grid{gap:24px}.grid>div:has(#source){grid-column:1/-1}
input,select{background:#f9fbfe;border:1px solid #dce5f1;border-radius:10px;padding:13px 14px;font-size:14px;transition:.15s}
input:focus,select:focus{outline:3px solid #2563eb18;border-color:#4f7ee8;background:white}
label{font-size:13px;color:#334966;font-weight:650}
small{font-size:12px;color:#8190a6;line-height:1.6}
.primary{background:#2463eb;border-radius:10px;font-size:14px;box-shadow:0 4px 9px #2563eb20;min-width:180px}
.primary:hover{background:#174ed0}
button,.button{font-size:13px;font-weight:600;border-radius:8px}
.hero-summary{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin:24px 0}
.summary-card{background:white;border:1px solid #e2e9f3;border-radius:14px;padding:18px 22px;display:flex;align-items:center;gap:16px}
.summary-icon{width:42px;height:42px;border-radius:12px;background:#edf3ff;color:#3268da;display:grid;place-items:center;font-size:20px}
.summary-card strong{display:block;font-size:25px;line-height:1.2;letter-spacing:-.7px}
.summary-card span{font-size:12px;color:#7c8aa1}
.eyebrow{font-size:11px;font-weight:750;letter-spacing:1.6px;color:#3572dc;text-transform:uppercase;margin-bottom:7px}
.section-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:24px}
.section-head h2{margin:0 0 4px}.section-head p{margin:0;font-size:13px;color:#7b899f}
.step{width:34px;height:34px;border-radius:10px;background:#eef4ff;color:#3765c5;display:grid;place-items:center;font-weight:700;flex-shrink:0}
.form-footer{border-top:1px solid #edf1f7;padding-top:20px;margin-top:26px;display:flex;justify-content:space-between;align-items:center;gap:16px}
.form-footer .actions{margin:0}
.form-footer p{margin:0;font-size:12px;color:#8290a5}
.campaign-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}
.card{background:#fff;border:1px solid #e0e7f1;border-radius:14px;padding:22px;margin:0}
.card h3{font-size:16px;letter-spacing:-.2px}.card .url{font-size:12px}
.badge{font-size:11px;font-weight:650;background:#eaf8f1;color:#168254;white-space:nowrap}
.badge:before{content:'●';font-size:8px;margin-right:5px}.paused{background:#fff5e1;color:#a47717}
.stats{background:#f8fafe;border:1px solid #eef2f8;border-radius:10px;padding:14px;gap:0;display:grid;grid-template-columns:repeat(4,1fr);text-align:center}
.stats strong{font-size:21px;color:#294975}.stats span{font-size:10px}.stats>div+div{border-left:1px solid #e6ecf5}
.tracking{font-size:11px;border:1px dashed #d8e3f4;background:#f9fbff;color:#6580a7;padding:10px 12px}
.card .actions{gap:7px}.card .actions button,.card .actions .button{padding:8px 11px;font-size:12px}
.card .actions>[data-copy]{color:#2563eb;background:#eff5ff;border-color:#e2ecff}
.geo-box{border:1px solid #dce5f1;border-radius:10px;padding:9px 10px;background:#f9fbfe;min-height:49px}
.geo-search{border:0;background:transparent;padding:5px 4px;min-width:160px;width:100%}
.geo-search:focus{outline:none;background:transparent}
.geo-chips{display:flex;flex-wrap:wrap;gap:6px}
.geo-chip{background:#eaf0ff;color:#355dd3;border-radius:6px;padding:4px 8px;font-size:12px;display:flex;align-items:center;gap:6px}
.geo-chip button{padding:0;border:0;background:none;font-size:16px;line-height:1}
.geo-select{margin-top:8px;width:100%;height:96px;padding:6px;border-radius:6px;background:white}
.geo-select option{padding:5px}.geo-note{font-size:11px;color:#7c8fa9}
.geo-tags{display:flex;flex-wrap:wrap;gap:5px;margin:12px 0}.geo-tags span{font-size:10px;padding:3px 7px;background:#f0f4fb;border-radius:5px;color:#58729a}
.empty{text-align:center;padding:38px 20px;border:1px dashed #d6e2f3;border-radius:12px;color:#7386a3;grid-column:1/-1}
.empty strong{display:block;font-size:16px;color:#38557d;margin:8px 0}
.page-foot{color:#8896aa!important;font-size:12px;text-align:center;margin-top:26px}
@media(max-width:760px){main{padding:24px 16px}.campaign-grid{grid-template-columns:1fr}.hero-summary{gap:9px}.summary-card{padding:14px 10px;gap:9px}.summary-icon{display:none}.summary-card strong{font-size:22px}.summary-card span{font-size:10px}header h1{font-size:24px}header a{font-size:12px;padding:8px}.panel{padding:22px 18px}.form-footer{align-items:flex-start;flex-direction:column}.form-footer .primary{width:100%}.form-footer .actions{width:100%}.topline{align-items:flex-start}}
</style></head><body><main>
<header><div><div class="eyebrow">ADTRACKR · WORKSPACE</div><h1>Campaign Manager</h1><div>Your links, locations and visitor insights. All in one place.</div></div><a href="/user/dashboard.php">← Dashboard</a></header>
<?php $activeCount=0; $visitCount=0; foreach ($rows as $item) { $activeCount+=(int)$item['active']; $visitCount+=(int)$item['android_visits']+(int)$item['desktop_visits']+(int)$item['other_visits']; } ?>
<div class="hero-summary"><div class="summary-card"><div class="summary-icon">↗</div><div><strong><?= count($rows) ?></strong><span>Total campaigns</span></div></div><div class="summary-card"><div class="summary-icon">◉</div><div><strong><?= $activeCount ?></strong><span>Tracking active</span></div></div><div class="summary-card"><div class="summary-icon">▥</div><div><strong><?= number_format($visitCount) ?></strong><span>Recorded visits</span></div></div></div>
<?php if ($error): ?><div role="alert" class="notice error"><?= esc($error) ?></div><?php endif ?>
<?php if ($flash): ?><div role="status" class="notice"><?= esc($flash) ?></div><?php endif ?>
<?php if ($ready): ?>
<section class="panel" id="setup"><div class="section-head"><div><h2><?= $editing['id'] ? 'Edit campaign' : 'Create a campaign' ?></h2><p>Set it up once. Keep your tracking links ready to share.</p></div><span class="step">01</span></div>
<form method="post" action="campaigns.php"><input type="hidden" name="csrf" value="<?= esc($csrf) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)$editing['id'] ?>">
<div class="grid">
<div><label for="name">Campaign name *</label><input id="name" name="name" required maxlength="120" value="<?= esc($editing['name']) ?>" placeholder="e.g. Summer offer"></div>
<div><label for="url">Destination / affiliate URL *</label><input id="url" name="url" type="url" required maxlength="4096" list="saved-links" value="<?= esc($editing['url']) ?>" placeholder="https://…"><datalist id="saved-links"><?php foreach ($saved as $link): ?><option value="<?= esc($link['url']) ?>"><?= esc($link['name']) ?></option><?php endforeach ?></datalist><small>Choose a saved URL or enter a new one.</small></div>
<div><label for="source">Source website (optional)</label><input id="source" name="source_url" type="url" maxlength="4096" value="<?= esc($editing['source_url']) ?>" placeholder="https://your-website.com"><small>A note about where you plan to share the link.</small></div>
<div><label for="geo-search">Target GEOs</label><div class="geo-box" id="geo-picker"><div class="geo-chips" id="geo-chips"></div><input class="geo-search" id="geo-search" type="search" placeholder="Search country name or code…" autocomplete="off" aria-controls="geo-options"><select class="geo-select" id="geo-options" name="geos[]" multiple aria-label="Select target countries">
<?php $selectedGeos=explode(',', $editing['geos'] ?? ''); foreach ($countries as $code=>$country): ?><option value="<?= $code ?>" <?= in_array($code,$selectedGeos,true)?'selected':'' ?>><?= esc($country) ?> (<?= $code ?>)</option><?php endforeach ?>
</select></div><small>Select up to 20 countries. Leave empty for all locations.</small><small class="geo-note">Campaign planning only; does not change visitor location.</small></div>
<div><label for="device">Device reporting focus</label><select id="device" name="device"><?php foreach (['both'=>'Both (Android + Desktop)','android'=>'Android','desktop'=>'Desktop'] as $key=>$label): ?><option value="<?= $key ?>" <?= $editing['device']===$key?'selected':'' ?>><?= $label ?></option><?php endforeach ?></select><small>Highlights the selected visits. All devices can open the link; iOS and unknown devices appear under Other.</small></div>
</div><div class="form-footer"><p>Your campaign stays saved until you delete it.</p><div class="actions"><button class="primary" type="submit"><?= $editing['id'] ? 'Save changes' : 'Save campaign' ?></button><?php if ($editing['id']): ?><a class="button" href="campaigns.php">Cancel edit</a><?php endif ?></div></div></form></section>
<section class="panel"><div class="section-head"><div><h2>Saved campaigns <span class="muted">(<?= count($rows) ?>)</span></h2><p>Copy a tracking link to share it. Manage everything from the cards below.</p></div><span class="step">02</span></div><div class="campaign-grid">
<?php if (!$rows): ?><div class="empty"><div style="font-size:28px">↗</div><strong>Your next campaign starts here</strong>Save a campaign above to get your shareable tracking link.</div><?php endif ?>
<?php foreach ($rows as $row): $path='/user/visit.php?c='.$row['token']; $focused=$row['device']==='both'?(int)$row['android_visits']+(int)$row['desktop_visits']:(int)$row[$row['device'].'_visits']; ?>
<article class="card"><div class="topline"><h3><?= esc($row['name']) ?></h3><span class="badge <?= $row['active']?'':'paused' ?>"><?= $row['active']?'Tracking on':'Paused' ?></span></div>
<p class="url"><?= esc($row['url']) ?></p><?php if ($row['source_url']): ?><p class="url">Source note: <?= esc($row['source_url']) ?></p><?php endif ?>
<div class="geo-tags"><?php $rowGeos=array_filter(explode(',', $row['geos'] ?? '')); if (!$rowGeos): ?><span>◎ All locations</span><?php endif ?><?php foreach ($rowGeos as $g): ?><span>◎ <?= esc($countries[$g] ?? $g) ?> · <?= esc($g) ?></span><?php endforeach ?></div><div class="stats"><div><strong><?= $focused ?></strong><span><?= esc(ucfirst($row['device'])) ?> focus</span></div><div><strong><?= (int)$row['android_visits'] ?></strong><span>Android</span></div><div><strong><?= (int)$row['desktop_visits'] ?></strong><span>Desktop</span></div><div><strong><?= (int)$row['other_visits'] ?></strong><span>Other</span></div></div>
<div class="tracking" data-path="<?= esc($path) ?>"><?= esc($path) ?></div>
<div class="actions"><button type="button" data-copy="<?= esc($path) ?>">Copy tracking link</button><a class="button" href="?edit=<?= (int)$row['id'] ?>#setup">Edit</a>
<form method="post" action="campaigns.php"><input type="hidden" name="csrf" value="<?= esc($csrf) ?>"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><input type="hidden" name="action" value="<?= $row['active']?'pause':'resume' ?>"><button type="submit"><?= $row['active']?'Pause tracking':'Resume tracking' ?></button></form>
<form method="post" action="campaigns.php" data-delete><input type="hidden" name="csrf" value="<?= esc($csrf) ?>"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><input type="hidden" name="action" value="delete"><button class="danger" type="submit">Delete</button></form></div></article>
<?php endforeach ?></div><small style="margin-top:20px">Pause stops counting; links still open. Visits are not unique people. Common bots and previews are excluded where detected.</small></section><?php endif ?>
<p class="page-foot">ADTRACKR · Visit totals update when you refresh this page.</p></main><script>
document.querySelectorAll('[data-path]').forEach(el=>{el.textContent=location.origin+el.dataset.path});
document.querySelectorAll('[data-copy]').forEach(el=>el.addEventListener('click',async()=>{const link=location.origin+el.dataset.copy;try{await navigator.clipboard.writeText(link);el.textContent='Copied';setTimeout(()=>el.textContent='Copy tracking link',1800)}catch(e){window.prompt('Copy tracking link:',link)}}));
document.querySelectorAll('[data-delete]').forEach(el=>el.addEventListener('submit',event=>{if(!confirm('Delete this campaign and its visit totals? Its tracking link will stop working.'))event.preventDefault()}));

const geoSelect=document.getElementById('geo-options'),geoSearch=document.getElementById('geo-search'),geoChips=document.getElementById('geo-chips');
if(geoSelect){
  const renderGeos=()=>{
    geoChips.replaceChildren();
    Array.from(geoSelect.selectedOptions).forEach(option=>{
      const chip=document.createElement('span');chip.className='geo-chip';
      const text=document.createElement('span');text.textContent=option.textContent;chip.append(text);
      const remove=document.createElement('button');remove.type='button';remove.textContent='×';remove.setAttribute('aria-label','Remove '+option.textContent);
      remove.addEventListener('click',()=>{option.selected=false;renderGeos()});chip.append(remove);geoChips.append(chip);
    });
  };
  geoSearch.addEventListener('input',()=>{const q=geoSearch.value.toLowerCase();Array.from(geoSelect.options).forEach(option=>{option.hidden=!option.textContent.toLowerCase().includes(q)})});
  geoSelect.addEventListener('change',()=>{if(geoSelect.selectedOptions.length>20){alert('Select up to 20 countries.');while(geoSelect.selectedOptions.length>20)geoSelect.selectedOptions[geoSelect.selectedOptions.length-1].selected=false}renderGeos()});
  geoSelect.addEventListener('mousedown',event=>{if(event.target.tagName==='OPTION'){event.preventDefault();if(!event.target.selected&&geoSelect.selectedOptions.length>=20){alert('Select up to 20 countries.');return}event.target.selected=!event.target.selected;renderGeos()}});
  renderGeos();
}
</script></body></html>
