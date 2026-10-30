<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}
include '../include/config2.php';

$msg = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $fname = trim($_POST['first_name']);
        $lname = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $pass  = $_POST['password'];
        $role  = $_POST['role'];

        $check = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
        if (mysqli_num_rows($check) > 0) {
            $msg = "Email already exists!"; $msg_type = 'error';
        } else {
            $hashed = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $fname, $lname, $email, $hashed, $role);
            if ($stmt->execute()) { $msg = "User created successfully!"; $msg_type = 'success'; }
            else { $msg = "Failed to create user."; $msg_type = 'error'; }
        }
    }

    if ($action === 'edit') {
        $id    = (int)$_POST['user_id'];
        $fname = trim($_POST['first_name']);
        $lname = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $role  = $_POST['role'];
        $pass  = $_POST['password'];

        if (!empty($pass)) {
            $hashed = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=?, email=?, role=?, password=? WHERE id=?");
            $stmt->bind_param("sssssi", $fname, $lname, $email, $role, $hashed, $id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=?, email=?, role=? WHERE id=?");
            $stmt->bind_param("ssssi", $fname, $lname, $email, $role, $id);
        }
        if ($stmt->execute()) { $msg = "User updated!"; $msg_type = 'success'; }
        else { $msg = "Failed to update user."; $msg_type = 'error'; }
    }

    if ($action === 'delete') {
        $id = (int)$_POST['user_id'];
        if ($id === (int)$_SESSION['user_id']) {
            $msg = "You cannot delete yourself!"; $msg_type = 'error';
        } else {
            $conn->query("DELETE FROM users WHERE id = $id");
            $msg = "User deleted!"; $msg_type = 'success';
        }
    }
}

$sql = "
    SELECT u.*, COUNT(l.id) as total_clicks, SUM(CASE WHEN l.success = 1 THEN 1 ELSE 0 END) as success_clicks
    FROM users u LEFT JOIN impact_click_logs l ON u.id = l.user_id
    GROUP BY u.id ORDER BY u.id DESC
";
$users = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Users Management</title>
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
      <a href="users.php" class="sidebar-link active flex items-center gap-3 px-6 py-3 text-sm font-medium">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg> Users & Logs
      </a>
      <a href="traffic.html" class="sidebar-link flex items-center gap-3 px-6 py-3 text-sm font-medium hover:text-white">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg> Send Traffic
      </a>
      <a href="proxy-stats.php" class="sidebar-link flex items-center gap-3 px-6 py-3 text-sm font-medium hover:text-white">
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
        <h1 class="text-xl font-semibold text-slate-800">User Management</h1>
      </div>
      <button onclick="openModal('createModal')" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm font-medium shadow-sm transition-colors flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        Create User
      </button>
    </header>

    <main class="flex-1 overflow-y-auto p-6 md:p-8">
      <?php if($msg): ?>
        <div class="mb-6 p-4 rounded-lg text-sm font-medium flex items-center gap-3 <?= $msg_type === 'success' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-700 border border-red-200' ?>">
          <?= htmlspecialchars($msg) ?>
        </div>
      <?php endif; ?>

      <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider border-b border-slate-200">
                <th class="px-6 py-4 font-semibold">Name / Email</th>
                <th class="px-6 py-4 font-semibold">Role</th>
                <th class="px-6 py-4 font-semibold">Total Clicks</th>
                <th class="px-6 py-4 font-semibold">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
              <?php while($u = mysqli_fetch_assoc($users)): 
                $rate = $u['total_clicks'] > 0 ? round(($u['success_clicks'] / $u['total_clicks']) * 100) : 0;
              ?>
              <tr class="hover:bg-slate-50 transition-colors">
                <td class="px-6 py-4">
                  <div class="font-medium text-slate-800"><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></div>
                  <div class="text-slate-500 text-xs mt-0.5"><?= htmlspecialchars($u['email']) ?></div>
                </td>
                <td class="px-6 py-4">
                  <?php if($u['role'] === 'admin'): ?>
                    <span class="bg-purple-100 text-purple-700 px-2.5 py-1 rounded-md text-xs font-bold uppercase tracking-wider">Admin</span>
                  <?php else: ?>
                    <span class="bg-slate-100 text-slate-600 px-2.5 py-1 rounded-md text-xs font-bold uppercase tracking-wider">User</span>
                  <?php endif; ?>
                </td>
                <td class="px-6 py-4">
                  <div class="font-semibold text-slate-800"><?= number_format($u['total_clicks']) ?></div>
                  <div class="text-xs text-emerald-600 mt-0.5"><?= $rate ?>% Success</div>
                </td>
                <td class="px-6 py-4">
                  <div class="flex items-center gap-2">
                    <a href="user-logs.php?user_id=<?= $u['id'] ?>" class="text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors">View Logs</a>
                    <button onclick='openEditModal(<?= json_encode($u) ?>)' class="text-amber-600 hover:text-amber-800 bg-amber-50 hover:bg-amber-100 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors">Edit</button>
                    <form method="POST" class="inline" onsubmit="return confirm('Delete this user?');">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                      <button type="submit" class="text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors">Delete</button>
                    </form>
                  </div>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>

  <!-- CREATE MODAL -->
  <div id="createModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
      <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center">
        <h3 class="font-bold text-lg text-slate-800">Create New User</h3>
        <button onclick="closeModal('createModal')" class="text-slate-400 hover:text-slate-600">&times;</button>
      </div>
      <form method="POST" class="p-6">
        <input type="hidden" name="action" value="create">
        <div class="grid grid-cols-2 gap-4 mb-4">
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">First Name</label>
            <input type="text" name="first_name" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Last Name</label>
            <input type="text" name="last_name" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none transition">
          </div>
        </div>
        <div class="mb-4">
          <label class="block text-xs font-semibold text-slate-600 mb-1">Email Address</label>
          <input type="email" name="email" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none transition">
        </div>
        <div class="mb-4">
          <label class="block text-xs font-semibold text-slate-600 mb-1">Password</label>
          <input type="text" name="password" required minlength="6" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none transition">
        </div>
        <div class="mb-6">
          <label class="block text-xs font-semibold text-slate-600 mb-1">Role</label>
          <select name="role" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none transition">
            <option value="user">User (Client)</option>
            <option value="admin">Admin</option>
          </select>
        </div>
        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 rounded-lg transition-colors">Create User</button>
      </form>
    </div>
  </div>

  <!-- EDIT MODAL -->
  <div id="editModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
      <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center">
        <h3 class="font-bold text-lg text-slate-800">Edit User</h3>
        <button onclick="closeModal('editModal')" class="text-slate-400 hover:text-slate-600">&times;</button>
      </div>
      <form method="POST" class="p-6">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="user_id" id="edit_id">
        <div class="grid grid-cols-2 gap-4 mb-4">
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">First Name</label>
            <input type="text" name="first_name" id="edit_fname" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none transition">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Last Name</label>
            <input type="text" name="last_name" id="edit_lname" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none transition">
          </div>
        </div>
        <div class="mb-4">
          <label class="block text-xs font-semibold text-slate-600 mb-1">Email Address</label>
          <input type="email" name="email" id="edit_email" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none transition">
        </div>
        <div class="mb-4">
          <label class="block text-xs font-semibold text-slate-600 mb-1">New Password <span class="text-slate-400 font-normal">(Leave empty to keep old)</span></label>
          <input type="text" name="password" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none transition">
        </div>
        <div class="mb-6">
          <label class="block text-xs font-semibold text-slate-600 mb-1">Role</label>
          <select name="role" id="edit_role" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none transition">
            <option value="user">User (Client)</option>
            <option value="admin">Admin</option>
          </select>
        </div>
        <button type="submit" class="w-full bg-amber-500 hover:bg-amber-600 text-white font-medium py-2.5 rounded-lg transition-colors">Save Changes</button>
      </form>
    </div>
  </div>

  <script>
    document.getElementById("openSidebar").onclick = () => document.getElementById("sidebar").classList.remove("-translate-x-full");

    function openModal(id) {
      document.getElementById(id).classList.remove('hidden');
      document.getElementById(id).classList.add('flex');
    }
    function closeModal(id) {
      document.getElementById(id).classList.add('hidden');
      document.getElementById(id).classList.remove('flex');
    }
    function openEditModal(user) {
      document.getElementById('edit_id').value = user.id;
      document.getElementById('edit_fname').value = user.first_name;
      document.getElementById('edit_lname').value = user.last_name;
      document.getElementById('edit_email').value = user.email;
      document.getElementById('edit_role').value = user.role;
      openModal('editModal');
    }
  </script>
</body>
</html>