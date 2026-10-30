<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}
include '../include/config2.php';

$user_id = (int)($_GET['user_id'] ?? 0);
if (!$user_id) die("User ID missing");

// User details
$u_res = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
$user = mysqli_fetch_assoc($u_res);
if (!$user) die("User not found");

// Fetch their clicks
$query = "SELECT * FROM impact_click_logs WHERE user_id = $user_id ORDER BY id DESC LIMIT 500";
$logs = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Logs: <?= htmlspecialchars($user['email']) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 p-6">
  <div class="max-w-6xl mx-auto">
    <div class="flex justify-between items-center mb-6">
      <div>
        <a href="users.php" class="text-blue-600 text-sm hover:underline mb-2 block">&larr; Back to Users</a>
        <h1 class="text-2xl font-bold text-gray-800">Click Logs: <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?> (<?= htmlspecialchars($user['email']) ?>)</h1>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow overflow-hidden">
      <div class="overflow-x-auto max-h-[75vh]">
        <table class="w-full text-sm text-left relative">
          <thead class="bg-gray-800 text-white sticky top-0">
            <tr>
              <th class="p-3">Status</th>
              <th class="p-3">GEO</th>
              <th class="p-3">IP Used</th>
              <th class="p-3">Target URL</th>
              <th class="p-3">Final URL</th>
              <th class="p-3">Date (IST)</th>
            </tr>
          </thead>
          <tbody>
            <?php while($r = mysqli_fetch_assoc($logs)): 
                // IST Time Fix
                $conn->query("SET time_zone = '+05:30';"); 
            ?>
            <tr class="border-b hover:bg-gray-50">
              <td class="p-3">
                <?= $r['success'] ? '<span class="bg-green-100 text-green-700 font-bold px-2 py-1 rounded text-xs">OK</span>' : '<span class="bg-red-100 text-red-700 font-bold px-2 py-1 rounded text-xs">FAIL</span>' ?>
              </td>
              <td class="p-3 font-semibold text-blue-600"><?= htmlspecialchars($r['geo']) ?></td>
              <td class="p-3 font-mono text-xs text-gray-600"><?= htmlspecialchars($r['ip']) ?></td>
              <td class="p-3 text-gray-500 max-w-[200px] truncate" title="<?= htmlspecialchars($r['target_url']) ?>"><?= htmlspecialchars($r['target_url']) ?></td>
              <td class="p-3 text-blue-500 max-w-[200px] truncate" title="<?= htmlspecialchars($r['final_url']) ?>"><?= htmlspecialchars($r['final_url']) ?></td>
              <td class="p-3 text-gray-500 text-xs"><?= date('d M Y, H:i:s', strtotime($r['created_at'])) ?></td>
            </tr>
            <?php endwhile; ?>
            <?php if(mysqli_num_rows($logs) == 0): ?>
                <tr><td colspan="6" class="p-6 text-center text-gray-500">No clicks found for this user.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</body>
</html>