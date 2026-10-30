<?php
// Current page ka naam pata karne ke liye
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="w-64 bg-slate-900 text-slate-400 flex flex-col shadow-2xl z-20 absolute md:relative transform -translate-x-full md:translate-x-0 transition-transform duration-300" id="sidebar">
    <div class="h-16 flex items-center px-6 border-b border-slate-800">
      <span class="text-xl font-bold text-white tracking-wide">AdTrackr<span class="text-blue-500">.</span></span>
      <span class="ml-2 text-xs bg-blue-500/20 text-blue-400 px-2 py-0.5 rounded border border-blue-500/30">Admin</span>
    </div>
    
    <nav class="flex-1 py-6 space-y-1 overflow-y-auto">
      <!-- Dashboard Link -->
      <a href="dashboard.php" class="sidebar-link <?= $current_page == 'dashboard.php' ? 'active' : 'hover:text-white' ?> flex items-center gap-3 px-6 py-3 text-sm font-medium">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg> Dashboard
      </a>

      <!-- Users & Logs Link -->
      <a href="user.php" class="sidebar-link <?= $current_page == 'user.php' ? 'active' : 'hover:text-white' ?> flex items-center gap-3 px-6 py-3 text-sm font-medium">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg> Users & Logs
      </a>

      <!-- Send Traffic Link -->
      <a href="traffic.html" class="sidebar-link <?= $current_page == 'traffic.html' || $current_page == 'traffic.php' ? 'active' : 'hover:text-white' ?> flex items-center gap-3 px-6 py-3 text-sm font-medium">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg> Send Traffic
      </a>

      <!-- Proxy Stats Link -->
      <a href="proxy-stats.php" class="sidebar-link <?= $current_page == 'proxy-stats.php' ? 'active' : 'hover:text-white' ?> flex items-center gap-3 px-6 py-3 text-sm font-medium">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg> Proxy Stats
      </a>
      
      <!-- ✅ Aap yahan naye links add kar sakte ho (Example) -->
      <!-- 
      <a href="settings.php" class="sidebar-link <?= $current_page == 'settings.php' ? 'active' : 'hover:text-white' ?> flex items-center gap-3 px-6 py-3 text-sm font-medium">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg> Settings
      </a>
      -->
    </nav>

    <div class="p-4 border-t border-slate-800">
      <a href="../logout.php" class="flex items-center justify-center gap-2 w-full bg-slate-800 hover:bg-red-600 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
        Logout
      </a>
    </div>
</aside>