<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}
include '../include/config2.php';

// ── 1. Stats Query ──
$total_clicks = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM impact_click_logs"))['c'];
$total_users  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE role='user'"))['c'];
$clicks_today = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM impact_click_logs WHERE DATE(created_at) = CURDATE()"))['c'];
$unique_ips   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT ip) as c FROM impact_click_logs WHERE ip IS NOT NULL AND ip != 'Unknown'"))['c'];

// ── 2. Recent Traffic ──
$recent = mysqli_query($conn, "
    SELECT l.*, u.email 
    FROM impact_click_logs l 
    LEFT JOIN users u ON l.user_id = u.id 
    ORDER BY l.id DESC LIMIT 6
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard | Premium Panel</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
  body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
  .sidebar-link { transition: all 0.2s ease; border-left: 3px solid transparent; }
  .sidebar-link:hover, .sidebar-link.active { background: rgba(255,255,255,0.05); border-left-color: #3b82f6; color: #fff; }
  .stat-card { transition: transform 0.2s ease, box-shadow 0.2s ease; }
  .stat-card:hover { transform: translateY(-2px); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); }
</style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800">

  <!-- PREMIUM SIDEBAR -->
  <aside class="w-64 bg-slate-900 text-slate-400 flex flex-col shadow-2xl z-20 absolute md:relative transform -translate-x-full md:translate-x-0 transition-transform duration-300" id="sidebar">
    <div class="h-16 flex items-center px-6 border-b border-slate-800">
      <span class="text-xl font-bold text-white tracking-wide">AdTrackr<span class="text-blue-500">.</span></span>
      <span class="ml-2 text-xs bg-blue-500/20 text-blue-400 px-2 py-0.5 rounded border border-blue-500/30">Admin</span>
    </div>
    
    <nav class="flex-1 py-6 space-y-1 overflow-y-auto">
      <a href="dashboard.php" class="sidebar-link active flex items-center gap-3 px-6 py-3 text-sm font-medium">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
        Dashboard
      </a>
      <a href="user.php" class="sidebar-link flex items-center gap-3 px-6 py-3 text-sm font-medium hover:text-white">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
        Users & Logs
      </a>
      <a href="traffic.html" class="sidebar-link flex items-center gap-3 px-6 py-3 text-sm font-medium hover:text-white">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
        Send Traffic
      </a>
      <a href="proxy-stats.php" class="sidebar-link flex items-center gap-3 px-6 py-3 text-sm font-medium hover:text-white">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
        Proxy Stats
      </a>
    </nav>

    <div class="p-4 border-t border-slate-800">
      <a href="../logout.php" class="flex items-center justify-center gap-2 w-full bg-slate-800 hover:bg-red-600 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
        Logout
      </a>
    </div>
  </aside>

  <!-- MAIN CONTENT -->
  <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
    <!-- TOP HEADER -->
    <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 z-10 shadow-sm">
      <div class="flex items-center gap-4">
        <button id="openSidebar" class="md:hidden text-slate-500 hover:text-slate-800">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
        </button>
        <h1 class="text-xl font-semibold text-slate-800">Dashboard Overview</h1>
      </div>
      <div class="flex items-center gap-3">
        <div class="text-right hidden sm:block">
          <p class="text-sm font-semibold text-slate-700">Admin</p>
          <p class="text-xs text-slate-500">Super Administrator</p>
        </div>
        <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold shadow-md">
          A
        </div>
      </div>
    </header>

    <!-- PAGE CONTENT -->
    <main class="flex-1 overflow-y-auto p-6 md:p-8">
      
      <!-- STATS CARDS -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Card 1 -->
        <div class="stat-card bg-white rounded-xl p-6 border border-slate-200 shadow-sm relative overflow-hidden">
          <div class="absolute right-0 top-0 w-24 h-24 bg-blue-50 rounded-bl-full -z-10"></div>
          <p class="text-sm font-medium text-slate-500 mb-1">Total Clicks</p>
          <h2 class="text-3xl font-bold text-slate-800"><?= number_format($total_clicks) ?></h2>
          <div class="mt-4 flex items-center text-xs font-medium text-blue-600 bg-blue-50 w-max px-2.5 py-1 rounded-md">
            All time traffic
          </div>
        </div>
        <!-- Card 2 -->
        <div class="stat-card bg-white rounded-xl p-6 border border-slate-200 shadow-sm relative overflow-hidden">
          <div class="absolute right-0 top-0 w-24 h-24 bg-emerald-50 rounded-bl-full -z-10"></div>
          <p class="text-sm font-medium text-slate-500 mb-1">Total Users</p>
          <h2 class="text-3xl font-bold text-slate-800"><?= number_format($total_users) ?></h2>
          <div class="mt-4 flex items-center text-xs font-medium text-emerald-600 bg-emerald-50 w-max px-2.5 py-1 rounded-md">
            Active accounts
          </div>
        </div>
        <!-- Card 3 -->
        <div class="stat-card bg-white rounded-xl p-6 border border-slate-200 shadow-sm relative overflow-hidden">
          <div class="absolute right-0 top-0 w-24 h-24 bg-amber-50 rounded-bl-full -z-10"></div>
          <p class="text-sm font-medium text-slate-500 mb-1">Clicks Today</p>
          <h2 class="text-3xl font-bold text-slate-800"><?= number_format($clicks_today) ?></h2>
          <div class="mt-4 flex items-center text-xs font-medium text-amber-600 bg-amber-50 w-max px-2.5 py-1 rounded-md">
            Last 24 hours
          </div>
        </div>
        <!-- Card 4 -->
        <div class="stat-card bg-white rounded-xl p-6 border border-slate-200 shadow-sm relative overflow-hidden">
          <div class="absolute right-0 top-0 w-24 h-24 bg-purple-50 rounded-bl-full -z-10"></div>
          <p class="text-sm font-medium text-slate-500 mb-1">Unique Proxy IPs</p>
          <h2 class="text-3xl font-bold text-slate-800"><?= number_format($unique_ips) ?></h2>
          <div class="mt-4 flex items-center text-xs font-medium text-purple-600 bg-purple-50 w-max px-2.5 py-1 rounded-md">
            From DataImpulse
          </div>
        </div>
      </div>

      <!-- RECENT TRAFFIC TABLE -->
      <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-200 flex justify-between items-center">
          <h3 class="font-semibold text-slate-800 text-lg">Recent Activity</h3>
          <a href="users.php" class="text-sm font-medium text-blue-600 hover:text-blue-700 hover:underline">View All &rarr;</a>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                <th class="px-6 py-4 font-semibold">User</th>
                <th class="px-6 py-4 font-semibold">Target URL</th>
                <th class="px-6 py-4 font-semibold">GEO</th>
                <th class="px-6 py-4 font-semibold">IP Address</th>
                <th class="px-6 py-4 font-semibold">Status</th>
                <th class="px-6 py-4 font-semibold">Time</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
              <?php while($r = mysqli_fetch_assoc($recent)): ?>
              <tr class="hover:bg-slate-50 transition-colors">
                <td class="px-6 py-4 font-medium text-slate-700"><?= htmlspecialchars($r['email'] ?? 'Guest') ?></td>
                <td class="px-6 py-4 text-blue-600 truncate max-w-[200px]" title="<?= htmlspecialchars($r['target_url']) ?>">
                  <?= htmlspecialchars($r['target_url']) ?>
                </td>
                <td class="px-6 py-4">
                  <span class="bg-blue-100 text-blue-700 px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider">
                    <?= htmlspecialchars($r['geo']) ?>
                  </span>
                </td>
                <td class="px-6 py-4 font-mono text-xs text-slate-500"><?= htmlspecialchars($r['ip']) ?></td>
                <td class="px-6 py-4">
                  <?php if($r['success']): ?>
                    <span class="inline-flex items-center gap-1.5 text-emerald-600 font-medium text-xs bg-emerald-50 px-2.5 py-1 rounded-md">
                      <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Success
                    </span>
                  <?php else: ?>
                    <span class="inline-flex items-center gap-1.5 text-red-600 font-medium text-xs bg-red-50 px-2.5 py-1 rounded-md">
                      <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Failed
                    </span>
                  <?php endif; ?>
                </td>
                <td class="px-6 py-4 text-slate-500 text-xs"><?= date('d M, H:i', strtotime($r['created_at'])) ?></td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>

  <script>
    document.getElementById("openSidebar").onclick = () => {
      document.getElementById("sidebar").classList.remove("-translate-x-full");
    };
  </script>
</body>
</html>