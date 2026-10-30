<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: index.php");
    exit;
}

include '../include/config2.php';
$user_id = (int)$_SESSION['user_id'];
$saved_affiliates = [];
$saved_referrals  = [];

$q = $conn->query("SELECT * FROM user_links WHERE user_id = $user_id");
if ($q) {
    while($row = $q->fetch_assoc()) {
        if($row['type'] == 'affiliate') $saved_affiliates[] = $row;
        if($row['type'] == 'referral')  $saved_referrals[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Url Extractor & Cr maintainer</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Inter', sans-serif; min-height: 100vh; background: linear-gradient(135deg, #1a56db 0%, #2563eb 30%, #0ea5e9 65%, #10b981 100%); padding: 2rem 1rem; }
  .wrap { max-width: 600px; margin: 0 auto; }
  .header { text-align: center; margin-bottom: 1rem; }
  .title { font-size: 26px; font-weight: 700; color: #fff; display: flex; align-items: center; justify-content: center; gap: 8px; }
  .subtitle { font-size: 12px; color: rgba(255,255,255,.8); margin-top: 6px; }
  .subtitle a { color: #6ee7b7; text-decoration: none; }
  .subtitle a:hover { text-decoration: underline; }
  .proxy-bar { background: rgba(255,255,255,.15); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,.25); border-radius: 8px; padding: 8px 16px; text-align: center; font-size: 12px; color: rgba(255,255,255,.9); margin-bottom: 1.2rem; display: flex; align-items: center; justify-content: center; gap: 6px; }
  .live-dot { width: 8px; height: 8px; border-radius: 50%; background: #10b981; box-shadow: 0 0 6px #10b981; display: inline-block; animation: blink 2s infinite; }
  @keyframes blink { 0%,100%{opacity:1} 50%{opacity:.3} }
  .card { background: #fff; border-radius: 16px; padding: 2rem; box-shadow: 0 20px 60px rgba(0,0,0,.18); }
  .field { margin-bottom: 1.25rem; position: relative; }
  .field-label { font-size: 13px; font-weight: 600; color: #1e3a5f; margin-bottom: 6px; display: flex; justify-content: space-between; align-items: center; }
  .manage-link { font-size: 11px; color: #2563eb; text-decoration: none; font-weight: 700; background: #eff6ff; padding: 2px 8px; border-radius: 4px; }
  .manage-link:hover { background: #dbeafe; }
  
  input[type=url], input[type=number], input[type=text] { width: 100%; padding: 11px 14px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 13px; font-family: 'Inter', sans-serif; color: #1e3a5f; background: #f8fafc; outline: none; transition: border .15s, box-shadow .15s; }
  input:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,.12); background: #fff; }
  input::placeholder { color: #94a3b8; }
  .hint { font-size: 11px; color: #94a3b8; margin-top: 4px; }

  .dropdown-wrapper { position: relative; }
  .dropdown-list { position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; margin-top: 4px; max-height: 200px; overflow-y: auto; z-index: 100; box-shadow: 0 4px 12px rgba(0,0,0,.1); display: none; }
  .dropdown-list.show { display: block; }
  .dropdown-item { padding: 10px 14px; font-size: 13px; cursor: pointer; border-bottom: 1px solid #f1f5f9; }
  .dropdown-item:hover { background: #f1f5f9; color: #2563eb; }
  .dropdown-item small { display: block; color: #94a3b8; font-size: 11px; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

  .geo-container { position: relative; }
  .geo-box { display: flex; flex-wrap: wrap; gap: 6px; padding: 8px 12px; border: 1.5px solid #e2e8f0; border-radius: 8px; background: #f8fafc; cursor: text; min-height: 42px; align-items: center; transition: border .15s; }
  .geo-box:focus-within { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,.12); background: #fff; }
  .geo-tag { background: #e0e7ff; color: #4338ca; font-size: 11px; font-weight: 700; padding: 4px 8px; border-radius: 6px; display: flex; align-items: center; gap: 4px; }
  .geo-tag span { cursor: pointer; font-size: 14px; line-height: 1; }
  .geo-tag span:hover { color: #ef4444; }
  .geo-input { border: none !important; background: transparent !important; padding: 4px !important; flex: 1; min-width: 120px; box-shadow: none !important; }
  .geo-dropdown { position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; margin-top: 4px; max-height: 200px; overflow-y: auto; z-index: 10; box-shadow: 0 4px 12px rgba(0,0,0,.1); display: none; }
  .geo-dropdown.show { display: block; }
  .geo-option { padding: 10px 14px; font-size: 13px; cursor: pointer; color: #334155; display: flex; justify-content: space-between; }
  .geo-option:hover { background: #f1f5f9; color: #2563eb; }
  .geo-error { color: #ef4444; font-size: 12px; font-weight: 600; margin-top: 6px; display: none; }
  .geo-error.show { display: block; }

  .range-wrap { margin-bottom: 1.5rem; }
  input[type=range] { width: 100%; -webkit-appearance: none; height: 5px; border-radius: 3px; border: none; padding: 0; cursor: pointer; outline: none; background: linear-gradient(90deg, #2563eb 40%, #e2e8f0 40%); }
  input[type=range]::-webkit-slider-thumb { -webkit-appearance: none; width: 18px; height: 18px; border-radius: 50%; background: #2563eb; border: 3px solid #fff; box-shadow: 0 0 0 2px #2563eb; cursor: pointer; }
  .delay-row { display: flex; align-items: center; gap: 8px; margin-top: 8px; }
  .delay-box { width: 54px; padding: 6px 10px; border: 1.5px solid #e2e8f0; border-radius: 6px; font-size: 13px; font-weight: 600; color: #1e3a5f; background: #f8fafc; text-align: center; }
  .delay-unit { font-size: 13px; color: #64748b; }
  .divider { height: 1px; background: #f1f5f9; margin: 0 0 1.25rem; }
  .btn-start { width: 100%; padding: 15px; border-radius: 10px; border: none; cursor: pointer; background: linear-gradient(135deg, #10b981, #059669); color: #fff; font-size: 15px; font-weight: 700; font-family: 'Inter', sans-serif; letter-spacing: .5px; transition: all .15s; display: flex; align-items: center; justify-content: center; gap: 8px; }
  .btn-start:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 8px 24px rgba(16,185,129,.35); }
  .btn-start:disabled { opacity: .6; cursor: not-allowed; transform: none; }
  .btn-stop { width: 100%; padding: 13px; border-radius: 10px; border: none; cursor: pointer; background: linear-gradient(135deg, #ef4444, #dc2626); color: #fff; font-size: 14px; font-weight: 700; font-family: 'Inter', sans-serif; letter-spacing: .5px; margin-top: 10px; display: none; transition: all .15s; }
  .btn-stop:disabled { opacity: .6; cursor: not-allowed; }
  .prog-wrap { margin-bottom: 1.25rem; display: none; }
  .prog-meta { display: flex; justify-content: space-between; font-size: 11px; color: #64748b; margin-bottom: 5px; }
  .prog-track { height: 6px; background: #e2e8f0; border-radius: 3px; overflow: hidden; }
  .prog-fill { height: 100%; background: linear-gradient(90deg, #2563eb, #10b981); border-radius: 3px; width: 0%; transition: width .3s; }
  .results-section { margin-top: 1.5rem; display: none; }
  .results-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
  .results-title { font-size: 14px; font-weight: 700; color: #1e3a5f; }
  .badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
  .badge-success { background: #d1fae5; color: #065f46; }
  .badge-partial  { background: #fef3c7; color: #92400e; }
  .stat-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-bottom: 1.25rem; }
  .stat { background: #f0f7ff; border-radius: 8px; padding: 10px 12px; text-align: center; }
  .stat-lbl { font-size: 10px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 3px; }
  .stat-n { font-size: 20px; font-weight: 700; }
  .c-blue { color: #2563eb; } .c-green { color: #10b981; } .c-red { color: #ef4444; } .c-amber { color: #f59e0b; }
  .scroll-box { max-height: 320px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 8px; }
  .scroll-box::-webkit-scrollbar { width: 4px; }
  .scroll-box::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 2px; }
  .log-table { width: 100%; border-collapse: collapse; font-size: 12px; }
  .log-table th { background: #f8fafc; padding: 8px 10px; text-align: left; font-size: 10px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: .5px; border-bottom: 1px solid #e2e8f0; position: sticky; top: 0; z-index: 1; }
  .log-table td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; color: #334155; }
  .log-table tr:last-child td { border-bottom: none; }
  .tag { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }
  .tag-ok  { background: #d1fae5; color: #065f46; }
  .tag-err { background: #fee2e2; color: #991b1b; }
  .url-cell { max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  @media (max-width: 480px) { .stat-row { grid-template-columns: repeat(2, 1fr); } }
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <div class="title">🚀 Url Extractor</div>
    <div class="subtitle">
       <a href="result.php" target="_blank">Result</a> |&nbsp; Custom Referral &nbsp;|&nbsp; GEO Targeting &nbsp;|&nbsp;
      <a href="/logout.php" class="text-sm font-medium text-red-600 hover:text-red-700">Logout</a>
    </div>
  </div>
  <div class="proxy-bar">
    🔌 Proxy Status: <span class="live-dot"></span> <strong>LIVE</strong> | Residential Rotating Pool
  </div>

  <div class="card">
    <div class="field">
      <div class="field-label">
        <span>🎯 Tracking URL *</span>
        <a href="manage-links.php" class="manage-link">+ Manage Links</a>
      </div>
      <div class="dropdown-wrapper">
        <input type="url" id="f-url" placeholder="Search saved links or type URL..." autocomplete="off" oninput="filterDropdown('url')" onfocus="filterDropdown('url')" />
        <div class="dropdown-list" id="url-dropdown">
          <?php if(empty($saved_affiliates)): ?>
            <div style="padding: 10px 14px; font-size:12px; color:#94a3b8;">No links saved yet. <a href="manage-links.php" style="color:#2563eb;">Add now</a></div>
          <?php endif; ?>
          <?php foreach($saved_affiliates as $aff): ?>
            <div class="dropdown-item" onclick="selectDropdown('url', '<?= addslashes($aff['url']) ?>')">
              <?= htmlspecialchars($aff['name']) ?>
              <small><?= htmlspecialchars($aff['url']) ?></small>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="field">
      <div class="field-label">📊 Total Clicks *</div>
      <input type="number" id="f-clicks" value="10" min="1" max="10000" />
    </div>

    <div class="field">
      <div class="field-label">
        <span>🌐 Custom Referral Website *</span>
        <a href="manage-links.php" class="manage-link">+ Manage Links</a>
      </div>
      <div class="dropdown-wrapper">
        <input type="url" id="f-ref" placeholder="Search referrals or type URL..." autocomplete="off" oninput="filterDropdown('ref')" onfocus="filterDropdown('ref')" />
        <div class="dropdown-list" id="ref-dropdown">
          <?php if(empty($saved_referrals)): ?>
            <div style="padding: 10px 14px; font-size:12px; color:#94a3b8;">No referrals saved yet. <a href="manage-links.php" style="color:#2563eb;">Add now</a></div>
          <?php endif; ?>
          <?php foreach($saved_referrals as $ref): ?>
            <div class="dropdown-item" onclick="selectDropdown('ref', '<?= addslashes($ref['url']) ?>')">
              <?= htmlspecialchars($ref['name']) ?>
              <small><?= htmlspecialchars($ref['url']) ?></small>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="hint">Enter ANY website URL — Impact mein ye hi dikhega</div>
    </div>
    
    <div class="field">
      <div class="field-label">🌍 Target GEOs * (Search & Select)</div>
      <div class="geo-container" id="geoContainer">
        <div class="geo-box" id="geoBox" onclick="document.getElementById('geoSearch').focus()">
          <input type="text" class="geo-input" id="geoSearch" placeholder="Type country name or code..." autocomplete="off">
        </div>
        <div class="geo-dropdown" id="geoDropdown"></div>
      </div>
      <div class="geo-error" id="geoError">Country does not exist!</div>
      <div class="hint">Multiple countries select kar sakte ho. Har click random country se hoga.</div>
    </div>

    <div class="range-wrap">
      <div class="field-label">⏱️ Maximum Delay Between Clicks</div>
      <input type="range" id="f-delay" min="2" max="600" value="600" step="1" oninput="updateDelay(this.value)" />
      <div class="delay-row">
        <div class="delay-box" id="delay-disp">10 min</div>
        <span class="delay-unit">maximum</span>
      </div>
    </div>
    <div class="divider"></div>

    <div class="prog-wrap" id="prog-wrap">
      <div class="prog-meta">
        <span id="prog-label">Sending clicks...</span>
        <span id="prog-txt">0 / 0</span>
      </div>
      <div class="prog-track"><div class="prog-fill" id="prog-fill"></div></div>
    </div>

    <button class="btn-start" id="btn-start" onclick="startRun()">⚡ START GEO CLICKING</button>
    <button class="btn-stop"  id="btn-stop"  onclick="stopRun()">⛔ STOP</button>

    <div class="results-section" id="results-section">
      <div class="results-header">
        <div class="results-title">📋 Results</div>
        <span class="badge badge-success" id="summary-badge">0 / 0 Success</span>
      </div>
      <div class="stat-row">
        <div class="stat"><div class="stat-lbl">Total</div><div class="stat-n c-blue" id="s-total">0</div></div>
        <div class="stat"><div class="stat-lbl">Success</div><div class="stat-n c-green" id="s-ok">0</div></div>
        <div class="stat"><div class="stat-lbl">Failed</div><div class="stat-n c-red" id="s-fail">0</div></div>
        <div class="stat"><div class="stat-lbl">Rate</div><div class="stat-n c-amber" id="s-rate">—</div></div>
      </div>
      <div class="scroll-box">
        <table class="log-table">
          <thead>
            <tr><th>#</th><th>Status</th><th>HTTP</th><th>GEO</th><th>IP Used</th><th>Final URL</th></tr>
          </thead>
          <tbody id="log-tbody"></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
// ── SEARCHABLE DROPDOWN LOGIC ──
function filterDropdown(type) {
    const input = document.getElementById(type === 'url' ? 'f-url' : 'f-ref');
    const dropdown = document.getElementById(type === 'url' ? 'url-dropdown' : 'ref-dropdown');
    const filter = input.value.toLowerCase();
    dropdown.classList.add('show');
    const items = dropdown.getElementsByClassName('dropdown-item');
    for (let i = 0; i < items.length; i++) {
        const txt = items[i].textContent.toLowerCase();
        items[i].style.display = txt.includes(filter) ? "block" : "none";
    }
}
function selectDropdown(type, url) {
    document.getElementById(type === 'url' ? 'f-url' : 'f-ref').value = url;
    document.getElementById(type === 'url' ? 'url-dropdown' : 'ref-dropdown').classList.remove('show');
}
document.addEventListener('click', function(e) {
    if (!e.target.closest('.dropdown-wrapper')) {
        document.querySelectorAll('.dropdown-list').forEach(el => el.classList.remove('show'));
    }
});

// ── MULTI-SELECT GEO ──
const COUNTRY_LIST = {"AD":"Andorra","AE":"United Arab Emirates","AF":"Afghanistan","AG":"Antigua and Barbuda","AI":"Anguilla","AL":"Albania","AM":"Armenia","AO":"Angola","AQ":"Antarctica","AR":"Argentina","AS":"American Samoa","AT":"Austria","AU":"Australia","AW":"Aruba","AX":"Åland Islands","AZ":"Azerbaijan","BA":"Bosnia and Herzegovina","BB":"Barbados","BD":"Bangladesh","BE":"Belgium","BF":"Burkina Faso","BG":"Bulgaria","BH":"Bahrain","BI":"Burundi","BJ":"Benin","BL":"Saint Barthélemy","BM":"Bermuda","BN":"Brunei","BO":"Bolivia","BQ":"Caribbean Netherlands","BR":"Brazil","BS":"Bahamas","BT":"Bhutan","BV":"Bouvet Island","BW":"Botswana","BY":"Belarus","BZ":"Belize","CA":"Canada","CC":"Cocos (Keeling) Islands","CD":"DR Congo","CF":"Central African Republic","CG":"Republic of the Congo","CH":"Switzerland","CI":"Côte d'Ivoire","CK":"Cook Islands","CL":"Chile","CM":"Cameroon","CN":"China","CO":"Colombia","CR":"Costa Rica","CU":"Cuba","CV":"Cape Verde","CW":"Curaçao","CX":"Christmas Island","CY":"Cyprus","CZ":"Czechia","DE":"Germany","DJ":"Djibouti","DK":"Denmark","DM":"Dominica","DO":"Dominican Republic","DZ":"Algeria","EC":"Ecuador","EE":"Estonia","EG":"Egypt","EH":"Western Sahara","ER":"Eritrea","ES":"Spain","ET":"Ethiopia","FI":"Finland","FJ":"Fiji","FK":"Falkland Islands","FM":"Micronesia","FO":"Faroe Islands","FR":"France","GA":"Gabon","GB":"United Kingdom","UK":"United Kingdom","GD":"Grenada","GE":"Georgia","GF":"French Guiana","GG":"Guernsey","GH":"Ghana","GI":"Gibraltar","GL":"Greenland","GM":"Gambia","GN":"Guinea","GP":"Guadeloupe","GQ":"Equatorial Guinea","GR":"Greece","GS":"South Georgia and the South Sandwich Islands","GT":"Guatemala","GU":"Guam","GW":"Guinea-Bissau","GY":"Guyana","HK":"Hong Kong","HM":"Heard Island and McDonald Islands","HN":"Honduras","HR":"Croatia","HT":"Haiti","HU":"Hungary","ID":"Indonesia","IE":"Ireland","IL":"Israel","IM":"Isle of Man","IN":"India","IO":"British Indian Ocean Territory","IQ":"Iraq","IR":"Iran","IS":"Iceland","IT":"Italy","JE":"Jersey","JM":"Jamaica","JO":"Jordan","JP":"Japan","KE":"Kenya","KG":"Kyrgyzstan","KH":"Cambodia","KI":"Kiribati","KM":"Comoros","KN":"Saint Kitts and Nevis","KP":"North Korea","KR":"South Korea","KW":"Kuwait","KY":"Cayman Islands","KZ":"Kazakhstan","LA":"Laos","LB":"Lebanon","LC":"Saint Lucia","LI":"Liechtenstein","LK":"Sri Lanka","LR":"Liberia","LS":"Lesotho","LT":"Lithuania","LU":"Luxembourg","LV":"Latvia","LY":"Libya","MA":"Morocco","MC":"Monaco","MD":"Moldova","ME":"Montenegro","MF":"Saint Martin","MG":"Madagascar","MH":"Marshall Islands","MK":"North Macedonia","ML":"Mali","MM":"Myanmar","MN":"Mongolia","MO":"Macau","MP":"Northern Mariana Islands","MQ":"Martinique","MR":"Mauritania","MS":"Montserrat","MT":"Malta","MU":"Mauritius","MV":"Maldives","MW":"Malawi","MX":"Mexico","MY":"Malaysia","MZ":"Mozambique","NA":"Namibia","NC":"New Caledonia","NE":"Niger","NF":"Norfolk Island","NG":"Nigeria","NI":"Nicaragua","NL":"Netherlands","NO":"Norway","NP":"Nepal","NR":"Nauru","NU":"Niue","NZ":"New Zealand","OM":"Oman","PA":"Panama","PE":"Peru","PF":"French Polynesia","PG":"Papua New Guinea","PH":"Philippines","PK":"Pakistan","PL":"Poland","PM":"Saint Pierre and Miquelon","PN":"Pitcairn Islands","PR":"Puerto Rico","PS":"Palestine","PT":"Portugal","PW":"Palau","PY":"Paraguay","QA":"Qatar","RE":"Réunion","RO":"Romania","RS":"Serbia","RU":"Russia","RW":"Rwanda","SA":"Saudi Arabia","SB":"Solomon Islands","SC":"Seychelles","SD":"Sudan","SE":"Sweden","SG":"Singapore","SH":"Saint Helena","SI":"Slovenia","SJ":"Svalbard and Jan Mayen","SK":"Slovakia","SL":"Sierra Leone","SM":"San Marino","SN":"Senegal","SO":"Somalia","SR":"Suriname","SS":"South Sudan","ST":"São Tomé and Príncipe","SV":"El Salvador","SX":"Sint Maarten","SY":"Syria","SZ":"Eswatini","TC":"Turks and Caicos Islands","TD":"Chad","TF":"French Southern Territories","TG":"Togo","TH":"Thailand","TJ":"Tajikistan","TK":"Tokelau","TL":"Timor-Leste","TM":"Turkmenistan","TN":"Tunisia","TO":"Tonga","TR":"Turkey","TT":"Trinidad and Tobago","TV":"Tuvalu","TW":"Taiwan","TZ":"Tanzania","UA":"Ukraine","UG":"Uganda","UM":"U.S. Minor Outlying Islands","US":"United States","UY":"Uruguay","UZ":"Uzbekistan","VA":"Vatican City","VC":"Saint Vincent and the Grenadines","VE":"Venezuela","VG":"British Virgin Islands","VI":"U.S. Virgin Islands","VN":"Vietnam","VU":"Vanuatu","WF":"Wallis and Futuna","WS":"Samoa","XK":"Kosovo","YE":"Yemen","YT":"Mayotte","ZA":"South Africa","ZM":"Zambia","ZW":"Zimbabwe"};

let selectedGeos = [];
const geoBox = document.getElementById('geoBox');
const geoSearch = document.getElementById('geoSearch');
const geoDropdown = document.getElementById('geoDropdown');
const geoError = document.getElementById('geoError');

function renderGeoTags() {
  document.querySelectorAll('.geo-tag').forEach(el => el.remove());
  selectedGeos.forEach(code => {
    const tag = document.createElement('div');
    tag.className = 'geo-tag';
    tag.innerHTML = `${COUNTRY_LIST[code]} (${code}) <span onclick="removeGeo(event, '${code}')">&times;</span>`;
    geoBox.insertBefore(tag, geoSearch);
  });
}

function removeGeo(e, code) {
  e.stopPropagation();
  selectedGeos = selectedGeos.filter(c => c !== code);
  renderGeoTags();
  geoSearch.focus();
}

function addGeo(code) {
  code = code.toUpperCase();
  if (!COUNTRY_LIST[code]) {
    showError(`Country '${code}' does not exist!`); return;
  }
  if (!selectedGeos.includes(code)) {
    selectedGeos.push(code); renderGeoTags();
  }
  geoSearch.value = ''; hideError(); geoDropdown.classList.remove('show'); geoSearch.focus();
}

function showError(msg) { geoError.textContent = msg; geoError.classList.add('show'); }
function hideError() { geoError.classList.remove('show'); }

geoSearch.addEventListener('input', function() {
  const query = this.value.trim().toLowerCase(); hideError(); geoDropdown.innerHTML = '';
  if (query.length === 0) { geoDropdown.classList.remove('show'); return; }
  let matches = [];
  for (const [code, name] of Object.entries(COUNTRY_LIST)) {
    if (code.toLowerCase().includes(query) || name.toLowerCase().includes(query)) {
      if (!selectedGeos.includes(code) && (code !== 'UK' || !selectedGeos.includes('GB'))) { matches.push({ code, name }); }
    }
  }
  if (matches.length > 0) {
    matches.slice(0, 50).forEach(m => {
      const div = document.createElement('div'); div.className = 'geo-option';
      div.innerHTML = `<span>${m.name}</span> <strong>${m.code}</strong>`;
      div.onclick = () => addGeo(m.code); geoDropdown.appendChild(div);
    });
    geoDropdown.classList.add('show');
  } else { geoDropdown.classList.remove('show'); }
});

geoSearch.addEventListener('keydown', function(e) {
  if (e.key === 'Enter') {
    e.preventDefault(); const val = this.value.trim().toUpperCase();
    if (val) {
      let foundCode = null;
      if (COUNTRY_LIST[val]) { foundCode = val; } 
      else { for (const [code, name] of Object.entries(COUNTRY_LIST)) { if (name.toUpperCase() === val) { foundCode = code; break; } } }
      if (foundCode) { addGeo(foundCode); } else { showError(`Country '${val}' does not exist!`); }
    }
  } else if (e.key === 'Backspace' && this.value === '' && selectedGeos.length > 0) { removeGeo(e, selectedGeos[selectedGeos.length - 1]); }
});

document.addEventListener('click', function(e) {
  if (!document.getElementById('geoContainer').contains(e.target)) { geoDropdown.classList.remove('show'); }
});
addGeo('IN');

// ── CLICKER LOGIC ──
let sent = 0, okCount = 0, failCount = 0;
let pollTimer = null;
let currentJobId = null;
let lastLogCount = 0;

function formatDelay(seconds) {
  seconds = Number(seconds);
  if (seconds < 60) return `${seconds} sec`;
  const minutes = Math.floor(seconds / 60);
  const remainingSeconds = seconds % 60;
  return remainingSeconds === 0 ? `${minutes} min` : `${minutes}m ${remainingSeconds}s`;
}

function updateDelay(val) {
  const seconds = Number(val);
  document.getElementById('delay-disp').textContent = formatDelay(seconds);
  const min = 2, max = 600;
  const pct = ((seconds - min) / (max - min) * 100).toFixed(0);
  document.getElementById('f-delay').style.background = `linear-gradient(90deg, #2563eb ${pct}%, #e2e8f0 ${pct}%)`;
}

async function startRun() {
  const url   = document.getElementById('f-url').value.trim();
  const ref   = document.getElementById('f-ref').value.trim();
  const total = Math.min(500, Math.max(1, parseInt(document.getElementById('f-clicks').value) || 10));
  const maxDelay = parseInt(document.getElementById('f-delay').value) || 600;

  if (!url) { alert('Target URL daalo!'); return; }
  if (!ref) { alert('Referral URL daalo!'); return; }
  if (selectedGeos.length === 0) { showError('Kam se kam ek country select karo!'); return; }

  sent = 0; okCount = 0; failCount = 0; lastLogCount = 0;
  document.getElementById('log-tbody').innerHTML = '';
  document.getElementById('results-section').style.display = 'block';
  document.getElementById('prog-wrap').style.display = 'block';
  document.getElementById('prog-fill').style.width = '0%';
  document.getElementById('prog-txt').textContent = `0 / ${total}`;
  document.getElementById('prog-label').textContent = 'Starting...';
  document.getElementById('btn-start').disabled = true;
  document.getElementById('btn-stop').style.display = 'block';
  document.getElementById('btn-stop').disabled = false;
  document.getElementById('btn-stop').textContent = '⛔ STOP';
  updateStats();

  try {
    const res = await fetch('start_run.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ url, referral_website: ref, geo_code: selectedGeos.join(','), total, max_delay: maxDelay })
    });
    const data = await res.json();
    if (!data.job_id) throw new Error(data.error || 'Failed to start job');
    currentJobId = data.job_id; pollStatus();
  } catch (e) {
    alert('Start failed: ' + e.message); resetButtons();
    document.getElementById('prog-label').textContent = '❌ Start failed';
  }
}

async function pollStatus() {
  if (!currentJobId) return;
  try {
    const res = await fetch('get_status.php?job=' + currentJobId + '&t=' + Date.now());
    const st  = await res.json();
    if (st.error) throw new Error(st.error);
    sent = st.sent || 0; okCount = st.ok || 0; failCount = st.fail || 0;
    updateStats();
    document.getElementById('prog-fill').style.width = Math.round((sent / (st.total || 1)) * 100) + '%';
    document.getElementById('prog-txt').textContent = sent + ' / ' + (st.total || 0);
    const tb = document.getElementById('log-tbody');
    const logs = st.logs || [];
    for (let i = lastLogCount; i < logs.length; i++) { appendRow(logs[i].i, logs[i]); }
    lastLogCount = logs.length;
    if (logs.length > 0) tb.closest('.scroll-box').scrollTop = 99999;
    if (st.state === 'running' || st.state === 'starting') {
      document.getElementById('prog-label').textContent = st.next_delay > 0 ? `Next click in ${formatDelay(st.next_delay)}...` : `Sending click ${st.current || (sent + 1)} of ${st.total}...`;
      pollTimer = setTimeout(pollStatus, 2000);
    } else if (st.state === 'done') {
      document.getElementById('prog-label').textContent = '✅ Done!';
      document.getElementById('prog-fill').style.width = '100%'; resetButtons();
    } else if (st.state === 'stopped') {
      document.getElementById('prog-label').textContent = '⛔ Stopped'; resetButtons();
    } else { pollTimer = setTimeout(pollStatus, 2000); }
  } catch (e) { pollTimer = setTimeout(pollStatus, 3000); }
}

function resetButtons() {
  if (pollTimer) { clearTimeout(pollTimer); pollTimer = null; }
  currentJobId = null;
  document.getElementById('btn-start').disabled = false;
  document.getElementById('btn-stop').style.display = 'none';
  document.getElementById('btn-stop').disabled = false;
  document.getElementById('btn-stop').textContent = '⛔ STOP';
}

async function stopRun() {
  if (!currentJobId) return;
  const btn = document.getElementById('btn-stop');
  btn.disabled = true; btn.textContent = 'Stopping after this click...';
  try { await fetch('stop_run.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams({ job: currentJobId }) }); } catch (e) {}
}

function appendRow(i, data) {
  const tb = document.getElementById('log-tbody');
  const tr = document.createElement('tr');
  let fu = data.final_url || '—';
  if (!data.success && data.curl_error) { fu = 'cURL ' + (data.curl_errno || '') + ': ' + data.curl_error; }
  const shortUrl = fu.length > 38 ? fu.substring(0, 38) + '...' : fu;
  tr.innerHTML = `
    <td>${i}</td>
    <td><span class="tag ${data.success ? 'tag-ok' : 'tag-err'}">${data.success ? '✓ OK' : '✗ FAIL'}</span></td>
    <td>${data.status_code || 'ERR'}</td>
    <td><strong>${data.geo || '—'}</strong></td>
    <td>${data.ip || '—'}</td>
    <td class="url-cell" title="${fu}">${shortUrl}</td>
  `;
  tb.appendChild(tr);
}

function updateStats() {
  document.getElementById('s-total').textContent = sent;
  document.getElementById('s-ok').textContent    = okCount;
  document.getElementById('s-fail').textContent  = failCount;
  document.getElementById('s-rate').textContent  = sent > 0 ? Math.round(okCount / sent * 100) + '%' : '—';
  const badge = document.getElementById('summary-badge');
  badge.textContent = `${okCount} / ${sent} Success`;
  badge.className = 'badge ' + (failCount > 0 ? 'badge-partial' : 'badge-success');
}
updateDelay(600);
</script>
</body>
</html>