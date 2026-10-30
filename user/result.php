<?php
ini_set('display_errors', '0');
error_reporting(0);
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: ../index.php");
    exit;
}

include '../include/config2.php'; 

// Set default timezone to IST
date_default_timezone_set('Asia/Kolkata');
// ✅ MySQL ko bhi bol do ki time IST me hi dikhaye
$conn->query("SET time_zone = '+05:30';");

// Filters
$geo        = $_GET['geo']        ?? '';
$status     = $_GET['status']     ?? 'ok';   // Default Success
$used       = $_GET['used']       ?? ''; 
$limit      = (int)($_GET['limit'] ?? 100);
$search     = $_GET['search']     ?? '';
$date_from  = $_GET['date_from']  ?? '';
$date_to    = $_GET['date_to']    ?? '';
$time_from  = $_GET['time_from']  ?? '';
$time_to    = $_GET['time_to']    ?? '';

$user_id = (int)($_SESSION['user_id'] ?? 0);

$where = ['1=1'];
$where[] = "user_id = $user_id"; // ✅ Sirf apne clicks

if ($geo)               $where[] = "geo = '" . mysqli_real_escape_string($conn, strtoupper($geo)) . "'";
if ($status === 'ok')   $where[] = "success = 1";
if ($status === 'fail') $where[] = "success = 0";
if ($used !== '')       $where[] = "is_served = " . (int)$used;

// ── Date + Time filter (IST) ──
$base_from_date = $date_from ?: date('Y-m-d');
$base_to_date   = $date_to   ?: date('Y-m-d');

if ($date_from || $time_from) {
    $from_time_str = $time_from ? $time_from . ':00' : '00:00:00';
    $from_dt = $base_from_date . ' ' . $from_time_str;
    $where[] = "created_at >= '" . mysqli_real_escape_string($conn, $from_dt) . "'";
}
if ($date_to || $time_to) {
    $to_time_str = $time_to ? $time_to . ':59' : '23:59:59';
    $to_dt = $base_to_date . ' ' . $to_time_str;
    $where[] = "created_at <= '" . mysqli_real_escape_string($conn, $to_dt) . "'";
}

if ($search) {
    $s = mysqli_real_escape_string($conn, $search);
    $where[] = "(final_url LIKE '%$s%' OR target_url LIKE '%$s%' OR ip LIKE '%$s%')";
}
$whereSql = implode(' AND ', $where);

// ── Rows ──
$result = mysqli_query($conn, "SELECT * FROM impact_click_logs WHERE $whereSql ORDER BY created_at DESC LIMIT $limit");
$rows = [];
while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;

// ── Copy-able URLs ──
$filteredUrls = [];
foreach ($rows as $r) {
    $success = (int)($r['success'] ?? 0);
    $finalUrl = trim($r['final_url'] ?? '');
    if ($success === 1 && $finalUrl !== '' && filter_var($finalUrl, FILTER_VALIDATE_URL)) {
        $filteredUrls[] = $finalUrl;
    }
}
$filteredUrls = array_values(array_unique($filteredUrls));

// ── Stats ──
$stRes = mysqli_query($conn, "SELECT COUNT(*) as total, SUM(success) as ok, SUM(is_served=1) as used_count FROM impact_click_logs WHERE $whereSql");
$stats = mysqli_fetch_assoc($stRes);

// ── Hourly clicks breakdown (IST) ──
$hourlyRes = mysqli_query($conn,
    "SELECT DATE_FORMAT(created_at, '%H') as hr, COUNT(*) as cnt
     FROM impact_click_logs WHERE $whereSql
     GROUP BY hr ORDER BY hr ASC"
);
$hourlyData = array_fill(0, 24, 0);
while ($h = mysqli_fetch_assoc($hourlyRes)) {
    $hourlyData[(int)$h['hr']] = (int)$h['cnt'];
}

// ── Distinct GEOs ──
$geoRes = mysqli_query($conn, "SELECT DISTINCT geo FROM impact_click_logs WHERE geo IS NOT NULL ORDER BY geo");
$geos = [];
while ($g = mysqli_fetch_row($geoRes)) $geos[] = $g[0];

$today_ist = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Final URL Viewer | AdTrackr</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:'Inter',sans-serif;min-height:100vh;background:linear-gradient(135deg,#1e293b 0%,#334155 100%);padding:2rem 1rem;color:#1e293b;}
  .wrap{max-width:1200px;margin:0 auto} .header{text-align:center;margin-bottom:1.2rem}
  .title{font-size:24px;font-weight:700;color:#fff;display:flex;align-items:center;justify-content:center;gap:8px}
  .subtitle{font-size:12px;color:rgba(255,255,255,.8);margin-top:6px} .subtitle a{color:#6ee7b7;text-decoration:none}
  .card{background:#fff;border-radius:16px;padding:1.5rem;box-shadow:0 20px 60px rgba(0,0,0,.3);margin-bottom:1.5rem}

  .stat-row{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:1.25rem}
  .stat{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px 16px;text-align:center}
  .stat-lbl{font-size:10px;color:#64748b;font-weight:600;text-transform:uppercase;margin-bottom:4px}
  .stat-n{font-size:24px;font-weight:700} .c-blue{color:#2563eb}.c-green{color:#10b981}.c-orange{color:#f59e0b}.c-purple{color:#7c3aed}

  .filters{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:1rem;align-items:center;background:#f1f5f9;padding:12px;border-radius:12px}
  .filter-input,.filter-select{padding:8px 12px;border:1.5px solid #cbd5e1;border-radius:8px;font-size:12px;outline:none;background:#fff}
  .filter-select.status-ok{border-color:#86efac;background:#f0fdf4;font-weight:600;color:#15803d}
  .filter-select.status-fail{border-color:#fca5a5;background:#fef2f2;font-weight:600;color:#b91c1c}

  .date-row{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:1rem;align-items:center;background:#eff6ff;padding:12px;border-radius:12px;border:1.5px solid #bfdbfe}
  .date-row label{font-size:11px;font-weight:600;color:#1d4ed8;white-space:nowrap}
  .date-row input[type=date]{padding:8px 10px;border:1.5px solid #93c5fd;border-radius:8px;font-size:12px;outline:none;background:#fff;color:#1e293b;cursor:pointer}
  .date-row input[type=date]:focus{border-color:#2563eb}

  .time-row{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:1rem;align-items:center;background:#f0fdf4;padding:12px;border-radius:12px;border:1.5px solid #86efac}
  .time-row label{font-size:11px;font-weight:600;color:#15803d;white-space:nowrap}
  .time-row input[type=time]{padding:8px 10px;border:1.5px solid #86efac;border-radius:8px;font-size:12px;outline:none;background:#fff;color:#1e293b;cursor:pointer}
  .time-row input[type=time]:focus{border-color:#16a34a}

  .filter-badge{font-size:10px;font-weight:700;padding:3px 8px;border-radius:6px;white-space:nowrap}
  .badge-blue{background:#dbeafe;color:#1d4ed8}
  .badge-green{background:#dcfce7;color:#15803d}
  .badge-warn{background:#fef3c7;color:#92400e}
  .badge-yellow{background:#fef9c3;color:#a16207}

  .btn-clear{padding:6px 12px;border-radius:8px;font-size:11px;font-weight:600;border:none;cursor:pointer}
  .btn-clear-date{background:#fee2e2;color:#dc2626}.btn-clear-date:hover{background:#fca5a5}
  .btn-clear-time{background:#fef9c3;color:#a16207}.btn-clear-time:hover{background:#fde047}

  .presets{display:flex;gap:5px;flex-wrap:wrap}
  .preset{padding:5px 10px;border-radius:16px;font-size:10px;font-weight:700;border:1.5px solid #86efac;color:#15803d;cursor:pointer;background:#fff;line-height:1.4;text-align:center}
  .preset:hover,.preset.sel{background:#16a34a;color:#fff;border-color:#16a34a}

  .toolbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;background:#fff;padding:10px;border-radius:8px;border:1px solid #e2e8f0;flex-wrap:wrap;gap:8px}
  .btn{padding:8px 16px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;border:none;display:flex;align-items:center;gap:6px;text-decoration:none;}
  .btn-copy{background:#2563eb;color:#fff} .btn-export{background:#fff;color:#1e293b;border:1px solid #cbd5e1}

  /* Hourly chart */
  .chart-card{background:#fff;border-radius:16px;padding:1.5rem;box-shadow:0 20px 60px rgba(0,0,0,.3);margin-bottom:1.5rem}
  .chart-title{font-size:14px;font-weight:700;color:#1e293b;margin-bottom:1.2rem;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
  .chart-outer{overflow-x:auto}
  .chart-wrap{display:flex;align-items:flex-end;gap:4px;height:140px;padding-bottom:36px;position:relative;min-width:600px}
  .bar-col{display:flex;flex-direction:column;align-items:center;flex:1;position:relative;cursor:default}
  .bar{width:85%;border-radius:4px 4px 0 0;transition:height 0.3s,opacity 0.3s;min-height:2px;position:relative}
  .bar:hover::after{
    content: attr(data-tip);
    position:absolute;top:-38px;left:50%;transform:translateX(-50%);
    background:#1e293b;color:#fff;padding:4px 8px;border-radius:6px;
    font-size:10px;font-weight:700;white-space:nowrap;z-index:10;
  }
  .bar:hover::before{
    content:'';position:absolute;top:-10px;left:50%;transform:translateX(-50%);
    border:5px solid transparent;border-top-color:#1e293b;z-index:10;
  }
  .bar-lbl{font-size:8px;color:#94a3b8;position:absolute;bottom:-18px;white-space:nowrap}
  .bar-count{font-size:8px;font-weight:700;position:absolute;bottom:-30px;color:#64748b}
  .chart-legend{display:flex;gap:16px;font-size:11px;color:#64748b;margin-top:2.5rem;flex-wrap:wrap}
  .legend-dot{width:10px;height:10px;border-radius:3px;display:inline-block;margin-right:4px}

  .scroll-box{border:1px solid #e2e8f0;border-radius:10px;overflow:auto;max-height:600px;background:#fff}
  table{width:100%;border-collapse:collapse;font-size:12px;min-width:1000px}
  thead th{background:#f8fafc;padding:12px;text-align:left;font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;border-bottom:2px solid #e2e8f0;position:sticky;top:0;z-index:2}
  tbody td{padding:12px;border-bottom:1px solid #f1f5f9;vertical-align:middle}
  .tag{display:inline-block;padding:3px 8px;border-radius:6px;font-size:10px;font-weight:700;text-transform:uppercase}
  .tag-used{background:#f1f5f9;color:#64748b;border:1px solid #e2e8f0} .tag-fresh{background:#ecfdf5;color:#059669;border:1px solid #a7f3d0}
  .url-cell{max-width:300px;font-family:monospace;font-size:11px;word-break:break-all;white-space:normal;line-height:1.4}
  .copy-btn{padding:5px 12px;border-radius:6px;font-size:11px;font-weight:600;background:#f1f5f9;border:1px solid #e2e8f0;cursor:pointer;color:#1e293b}
  .copy-btn:disabled{opacity:0.4;cursor:not-allowed;background:#fee2e2;border-color:#fecaca;color:#b91c1c}
  .toast{position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#1e293b;color:#fff;padding:10px 20px;border-radius:8px;opacity:0;transition:0.3s;z-index:99;pointer-events:none}
  .toast.show{opacity:1} @media (max-width:768px){.stat-row{grid-template-columns:repeat(2,1fr)}}
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <div class="title">🚀 AdTrackr Live Viewer</div>
    <div class="subtitle">
      <a href="./impact-clicker.php">Dashboard</a> &nbsp;•&nbsp; <a href="../logout.php">Logout</a>
    </div>
  </div>

  <!-- ══ Hourly Chart Card ══ -->
  <?php
    $maxClicks   = max($hourlyData) ?: 1;
    $peakHour    = array_search(max($hourlyData), $hourlyData);
    $totalClicks = array_sum($hourlyData);
    $barColors   = ['#6366f1','#818cf8','#a78bfa','#c084fc','#e879f9','#f472b6','#fb7185','#f97316','#fb923c','#fbbf24','#a3e635','#34d399','#2dd4bf','#38bdf8','#60a5fa','#818cf8','#6366f1','#a78bfa','#c084fc','#e879f9','#f472b6','#fb7185','#f97316','#fbbf24'];
  ?>
  <div class="chart-card">
    <div class="chart-title">
      ⏱️ Clicks Per Hour — IST
      <span style="font-size:11px;font-weight:400;color:#64748b;">
        Total: <b><?= number_format($totalClicks) ?></b> clicks
        <?php if($time_from||$time_to): ?>
          &nbsp;|&nbsp; Filter: <b><?= $time_from?:'00:00' ?> – <?= $time_to?:'23:59' ?> IST</b>
        <?php endif; ?>
      </span>
    </div>
    <div class="chart-outer">
      <div class="chart-wrap">
        <?php for($h=0;$h<24;$h++):
          $cnt  = $hourlyData[$h];
          $barH = $maxClicks > 0 ? max(2, round(($cnt/$maxClicks)*120)) : 2;
          $isPeak = ($h === $peakHour && $cnt > 0);
          $inRange = $time_from && $time_to && $h>=(int)$time_from && $h<=(int)explode(':',$time_to)[0];
          $color = $isPeak ? '#f97316' : ($inRange ? '#2563eb' : $barColors[$h]);
          $opacity = $cnt > 0 ? 1 : 0.12;
          $tip = str_pad($h,2,'0',STR_PAD_LEFT).':00–'.str_pad($h,2,'0',STR_PAD_LEFT).':59 → '.$cnt.' clicks';
        ?>
        <div class="bar-col">
          <div class="bar"
               style="height:<?= $barH ?>px;background:<?= $color ?>;opacity:<?= $opacity ?>"
               data-tip="<?= htmlspecialchars($tip) ?>">
          </div>
          <div class="bar-lbl"><?= str_pad($h,2,'0',STR_PAD_LEFT) ?></div>
          <?php if($cnt>0): ?>
          <div class="bar-count"><?= $cnt>=1000?round($cnt/1000,1).'k':$cnt ?></div>
          <?php endif; ?>
        </div>
        <?php endfor; ?>
      </div>
    </div>
    <div class="chart-legend">
      <span><span class="legend-dot" style="background:#f97316"></span>🔥 Peak Hour: <b><?= str_pad($peakHour,2,'0',STR_PAD_LEFT) ?>:00 IST</b> (<?= number_format($hourlyData[$peakHour]) ?> clicks)</span>
      <span><span class="legend-dot" style="background:#2563eb"></span>Time-filter range</span>
      <span><span class="legend-dot" style="background:#6366f1"></span>Normal hours</span>
      <span style="opacity:0.5"><span class="legend-dot" style="background:#94a3b8"></span>No traffic</span>
    </div>
  </div>

  <!-- ══ Filters + Table Card ══ -->
  <div class="card">
    <div class="stat-row">
      <div class="stat"><div class="stat-lbl">Filtered Clicks</div><div class="stat-n c-blue"><?= number_format(count($rows)) ?></div></div>
      <div class="stat"><div class="stat-lbl">Served (Used)</div><div class="stat-n c-orange"><?= number_format((int)$stats['used_count']) ?></div></div>
      <div class="stat"><div class="stat-lbl">Success OK</div><div class="stat-n c-green"><?= number_format((int)$stats['ok']) ?></div></div>
      <div class="stat"><div class="stat-lbl">📋 Copy-able URLs</div><div class="stat-n c-purple"><?= number_format(count($filteredUrls)) ?></div></div>
    </div>

    <form method="GET" action="" id="filterForm">

      <!-- Main filters -->
      <div class="filters">
        <input class="filter-input" style="flex:1;min-width:180px" type="text" name="search" placeholder="Search URL or IP..." value="<?= htmlspecialchars($search) ?>">

        <select class="filter-select <?= $status==='ok'?'status-ok':($status==='fail'?'status-fail':'') ?>" name="status" onchange="this.form.submit()">
          <option value=""     <?= $status===''?'selected':''     ?>>📊 All Status</option>
          <option value="ok"   <?= $status==='ok'?'selected':''   ?>>✅ Success Only</option>
          <option value="fail" <?= $status==='fail'?'selected':'' ?>>❌ Failed Only</option>
        </select>

        <select class="filter-select" name="used" onchange="this.form.submit()">
          <option value="">🔄 All Logic</option>
          <option value="0" <?= $used==='0'?'selected':'' ?>>🟢 Fresh Only</option>
          <option value="1" <?= $used==='1'?'selected':'' ?>>⚪ Used Only</option>
        </select>

        <select class="filter-select" name="geo" onchange="this.form.submit()">
          <option value="">🌍 All GEOs</option>
          <?php foreach($geos as $g): ?>
            <option value="<?= htmlspecialchars($g) ?>" <?= $geo===$g?'selected':'' ?>><?= htmlspecialchars($g) ?></option>
          <?php endforeach; ?>
        </select>

        <select class="filter-select" name="limit" onchange="this.form.submit()">
          <?php foreach([50,100,500,1000] as $l): ?>
            <option value="<?= $l ?>" <?= $limit==$l?'selected':'' ?>><?= $l ?> Rows</option>
          <?php endforeach; ?>
        </select>
        
        <!-- ✅ Search & Reset Buttons -->
        <button type="submit" class="btn btn-copy" style="padding:8px 16px; font-size:12px;">Search</button>
        <a href="result.php" class="btn btn-export" style="padding:8px 16px; font-size:12px;">🔄 Reset</a>
      </div>

      <!-- ✅ Date filter (IST) -->
      <div class="date-row">
        <span class="filter-badge badge-blue">📅 IST Date</span>
        <label>From</label>
        <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>" max="<?= $today_ist ?>" onchange="this.form.submit()">
        <label>To</label>
        <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>" max="<?= $today_ist ?>" onchange="this.form.submit()">
        <?php if($date_from||$date_to): ?>
          <button type="button" class="btn-clear btn-clear-date" onclick="clearParam(['date_from','date_to'])">✕ Clear</button>
          <span class="filter-badge badge-warn"><?= $date_from?:'—' ?> → <?= $date_to?:'—' ?></span>
        <?php endif; ?>
      </div>

      <!-- ✅ Time filter (IST) -->
      <div class="time-row">
        <span class="filter-badge badge-green">🕐 IST Time</span>
        <label>From</label>
        <input type="time" name="time_from" id="tf" value="<?= htmlspecialchars($time_from) ?>" onchange="this.form.submit()">
        <label>To</label>
        <input type="time" name="time_to" id="tt" value="<?= htmlspecialchars($time_to) ?>" onchange="this.form.submit()">

        <div class="presets">
          <button type="button" class="preset <?= ($time_from==='00:00'&&$time_to==='05:59')?'sel':'' ?>" onclick="setTime('00:00','05:59')">🌙 Night<br><small>12–6am</small></button>
          <button type="button" class="preset <?= ($time_from==='06:00'&&$time_to==='11:59')?'sel':'' ?>" onclick="setTime('06:00','11:59')">🌅 Morning<br><small>6am–12pm</small></button>
          <button type="button" class="preset <?= ($time_from==='12:00'&&$time_to==='17:59')?'sel':'' ?>" onclick="setTime('12:00','17:59')">☀️ Afternoon<br><small>12–6pm</small></button>
          <button type="button" class="preset <?= ($time_from==='18:00'&&$time_to==='23:59')?'sel':'' ?>" onclick="setTime('18:00','23:59')">🌆 Evening<br><small>6pm–12am</small></button>
          <button type="button" class="preset <?= ($time_from==='09:00'&&$time_to==='18:00')?'sel':'' ?>" onclick="setTime('09:00','18:00')">💼 Business<br><small>9am–6pm</small></button>
        </div>

        <?php if($time_from||$time_to): ?>
          <button type="button" class="btn-clear btn-clear-time" onclick="clearParam(['time_from','time_to'])">✕ Clear</button>
          <span class="filter-badge badge-yellow"><?= $time_from?:'00:00' ?> → <?= $time_to?:'23:59' ?> IST</span>
        <?php endif; ?>
      </div>

    </form>

    <div class="toolbar">
      <div style="font-size:13px;font-weight:600;">
        📋 Showing <?= count($rows) ?> results
        &nbsp;|&nbsp;
        <span style="color:#7c3aed;">✅ <?= count($filteredUrls) ?> copy-able URLs</span>
      </div>
      <div style="display:flex;gap:8px;">
        <button class="btn btn-copy" onclick="copyAllFiltered()">📋 Copy Success URLs</button>
        <button class="btn btn-export" onclick="exportFilteredTxt()">⬇️ Export .txt</button>
      </div>
    </div>

    <div class="scroll-box">
      <table>
        <thead>
          <tr>
            <th>Status</th><th>Queue</th><th>GEO</th><th>Domain</th>
            <th>Final URL (Background Hit)</th><th>IP Address</th><th>Time (IST)</th><th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($rows as $r):
            $fu      = $r['final_url'] ?? '';
            $served  = $r['is_served'] ?? 0;
            $success = (int)($r['success'] ?? 0);
            $domain  = parse_url($fu, PHP_URL_HOST) ?? '—';
            
            $ts_ist  = strtotime($r['created_at']);
            $tdisp   = date('d M, H:i', $ts_ist);
            
            $copyable = $success === 1 && $fu !== '' && filter_var($fu, FILTER_VALIDATE_URL);
          ?>
          <tr>
            <td><span class="tag"><?= $success ? '✅ OK' : '❌ Fail' ?></span></td>
            <td><?= $served ? '<span class="tag tag-used">Used</span>' : '<span class="tag tag-fresh">Fresh</span>' ?></td>
            <td><span class="tag" style="background:#eff6ff;color:#1d4ed8;"><?= htmlspecialchars($r['geo']??'??') ?></span></td>
            <td style="font-weight:600;color:#475569;"><?= str_replace('www.','',$domain) ?></td>
            <td class="url-cell">
              <?php if ($fu): ?>
                <a href="<?= htmlspecialchars($fu) ?>" target="_blank" style="color:#2563eb;text-decoration:none;">
                  <?= substr(htmlspecialchars($fu),0,70) ?>...
                </a>
              <?php else: ?>
                <span style="color:#94a3b8;">—</span>
              <?php endif; ?>
            </td>
            <td style="font-family:monospace;color:#64748b"><?= htmlspecialchars($r['ip']??'—') ?></td>
            <td style="font-size:10px;color:#94a3b8"><?= $tdisp ?> IST</td>
            <td>
              <?php if ($copyable): ?>
                <button class="copy-btn" onclick="copySingle(this,'<?= addslashes($fu) ?>')">Copy</button>
              <?php else: ?>
                <button class="copy-btn" disabled title="Failed click — copy nahi hoga">Copy</button>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>

<script>
const filteredUrls = <?= json_encode($filteredUrls) ?>;

function showToast(msg){
  const t=document.getElementById('toast');
  t.innerText=msg; t.classList.add('show');
  setTimeout(()=>t.classList.remove('show'),2500);
}
function copyAllFiltered(){
  if(!filteredUrls.length){showToast('❌ No successful URLs to copy!');return;}
  navigator.clipboard.writeText(filteredUrls.join('\n')).then(()=>showToast('✅ '+filteredUrls.length+' success URLs copied'));
}
function exportFilteredTxt(){
  if(!filteredUrls.length){showToast('❌ No successful URLs to export!');return;}
  const a=document.createElement('a');
  a.href=URL.createObjectURL(new Blob([filteredUrls.join('\n')],{type:'text/plain'}));
  a.download='success_urls_<?= date('Y-m-d_H-i') ?>.txt';
  a.click();
  showToast('✅ Exporting '+filteredUrls.length+' success URLs...');
}
function copySingle(btn,text){
  if(!text)return;
  navigator.clipboard.writeText(text).then(()=>{
    btn.innerText='Copied!';
    setTimeout(()=>{btn.innerText='Copy';},2000);
  });
}
function setTime(from,to){
  document.getElementById('tf').value=from;
  document.getElementById('tt').value=to;
  document.getElementById('filterForm').submit();
}
function clearParam(keys){
  const form=document.getElementById('filterForm');
  const params=new URLSearchParams(new FormData(form));
  keys.forEach(k=>params.delete(k));
  window.location.href='?'+params.toString();
}
</script>
</body>
</html>