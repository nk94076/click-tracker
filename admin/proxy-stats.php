<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}
include '../include/config2.php';

// ── DB Stats ──
$total_ips_res = mysqli_query($conn, "SELECT ip, geo, COUNT(*) as count FROM impact_click_logs WHERE ip IS NOT NULL AND ip != 'Unknown' GROUP BY ip, geo ORDER BY count DESC LIMIT 20");

// ── DataImpulse API (Stats Endpoint) ──
$di_username = "3ef0a21a53aae883cab"; // <--- Apna username daalo
$di_password = "255e04d20c70a8c1";     // <--- Apna password daalo
$di_status = "Disconnected";
$di_data = [];

if ($di_username && $di_password) {
    $ch = curl_init("https://api.dataimpulse.com/api/v1/stats"); 
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_USERPWD => $di_username . ":" . $di_password
    ]);
    $resp = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($http_code === 200) {
        $di_status = "Connected";
        $di_data = json_decode($resp, true);
    } else {
        $di_status = "Error: HTTP " . $http_code;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Proxy Stats</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
  body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
  .sidebar-link { transition: all 0.2s ease; border-left: 3px solid transparent; }
  .sidebar-link:hover, .sidebar-link.active { background: rgba(255,255,255,0.05); border-left-color: #3b82f6; color: #fff; }
</style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800">

  <!-- SIDEBAR (Same as dashboard) -->
  <aside class="w-64 bg-slate-900 text-slate-400 flex flex-col shadow-2xl z-20 absolute md:relative transform -translate-x-full md:translate-x-0 transition-transform duration-300" id="sidebar">
    <div class="h-16 flex items-center px-6 border-b border-slate-800">
      <span class="text-xl font-bold text-white tracking-wide">AdTrackr<span class="text-blue-500">.</span></span>
      <span class="ml-2 text-xs bg-blue-500/20 text-blue-400 px-2 py-0.5 rounded border border-blue-500/30">Admin</span>
    </div>
    <nav class="flex-1 py-6 space-y-1 overflow-y-auto">
      <a href="dashboard.php" class="sidebar-link flex items-center gap-3 px-6 py-3 text-sm font-medium hover:text-white">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg> Dashboard
      </a>
      <a href="users.php" class="sidebar-link flex items-center gap-3 px-6 py-3 text-sm font-medium hover:text-white">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg> Users & Logs
      </a>
      <a href="traffic.html" class="sidebar-link flex items-center gap-3 px-6 py-3 text-sm font-medium hover:text-white">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg> Send Traffic
      </a>
      <a href="proxy-stats.php" class="sidebar-link active flex items-center gap-3 px-6 py-3 text-sm font-medium">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg> Proxy Stats
      </a>
    </nav>
    <div class="p-4 border-t border-slate-800">
      <a href="../logout.php" class="flex items-center justify-center gap-2 w-full bg-slate-800 hover:bg-red-600 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition-colors">Logout</a>
    </div>
  </aside>

  <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
    <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 z-10 shadow-sm">
      <div class="flex items-center gap-4">
        <button id="openSidebar" class="md:hidden text-slate-500 hover:text-slate-800">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
        </button>
        <h1 class="text-xl font-semibold text-slate-800">Proxy Statistics</h1>
      </div>
    </header>

    <main class="flex-1 overflow-y-auto p-6 md:p-8">
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <!-- Card 1 -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 relative overflow-hidden">
            <div class="absolute right-0 top-0 w-24 h-24 bg-purple-50 rounded-bl-full -z-10"></div>
            <p class="text-sm font-medium text-slate-500 mb-1">Unique Proxy IPs (DB)</p>
            <h2 class="text-4xl font-bold text-slate-800 mt-2"><?= number_format(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT ip) as c FROM impact_click_logs WHERE ip != 'Unknown'"))['c']) ?></h2>
            <p class="text-xs text-slate-500 mt-4">Total unique residential IPs used in clicks</p>
        </div>

        <!-- Card 2 -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 relative overflow-hidden">
            <div class="absolute right-0 top-0 w-24 h-24 bg-emerald-50 rounded-bl-full -z-10"></div>
            <p class="text-sm font-medium text-slate-500 mb-1">DataImpulse API Status</p>
            <h2 class="text-3xl font-bold <?= $di_status === 'Connected' ? 'text-emerald-600' : 'text-red-500' ?> mt-2 flex items-center gap-2">
                <?= $di_status ?>
                <?php if($di_status === 'Connected'): ?>
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <?php endif; ?>
            </h2>
            <p class="text-xs text-slate-500 mt-4">
              <?= $di_status === 'Connected' ? 'Your API credentials are working correctly.' : 'Check your credentials in proxy-stats.php' ?>
            </p>
        </div>
      </div>

      <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-200">
          <h3 class="font-semibold text-slate-800 text-lg">Top 20 Used IPs</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider border-b border-slate-200">
                <th class="px-6 py-4 font-semibold">IP Address</th>
                <th class="px-6 py-4 font-semibold">GEO</th>
                <th class="px-6 py-4 font-semibold">Usage Count</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
              <?php while($ip = mysqli_fetch_assoc($total_ips_res)): ?>
              <tr class="hover:bg-slate-50 transition-colors">
                <td class="px-6 py-4 font-mono text-slate-700"><?= htmlspecialchars($ip['ip']) ?></td>
                <td class="px-6 py-4">
                  <span class="bg-blue-100 text-blue-700 px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider">
                    <?= htmlspecialchars($ip['geo']) ?>
                  </span>
                </td>
                <td class="px-6 py-4 font-semibold text-slate-800"><?= $ip['count'] ?></td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>

  <script>
    document.getElementById("openSidebar").onclick = () => document.getElementById("sidebar").classList.remove("-translate-x-full");
  </script>
</body>
</html>