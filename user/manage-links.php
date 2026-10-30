<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: ../index.php");
    exit;
}
include '../include/config2.php';
$user_id = (int)$_SESSION['user_id'];

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_link'])) {
    $type = $_POST['type'];
    $name = trim($_POST['name']);
    $url  = trim($_POST['url']);
    
    if($type && $name && $url) {
        $stmt = $conn->prepare("INSERT INTO user_links (user_id, type, name, url) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $type, $name, $url);
        $stmt->execute();
        $msg = "Link saved successfully!";
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM user_links WHERE id = $id AND user_id = $user_id");
    header("Location: manage-links.php");
    exit;
}

$links = $conn->query("SELECT * FROM user_links WHERE user_id = $user_id ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Links</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style> body { font-family: 'Inter', sans-serif; background: #f8fafc; } </style>
</head>
<body class="p-6">
  <div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-slate-800">🔗 Manage My Links</h1>
        <a href="impact-clicker.php" class="text-blue-600 text-sm font-semibold hover:underline">&larr; Back to Click Tool</a>
    </div>

    <?php if($msg): ?>
        <div class="bg-green-100 text-green-700 p-3 rounded-lg mb-4 text-sm font-bold border border-green-200"><?= $msg ?></div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Add Form -->
        <div class="bg-white p-6 rounded-xl shadow border border-slate-200">
            <h2 class="text-lg font-bold mb-4">Add New Link</h2>
            <form method="POST">
                <input type="hidden" name="add_link" value="1">
                <div class="mb-4">
                    <label class="text-xs font-bold text-slate-600 mb-1 block">Link Type</label>
                    <select name="type" required class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                        <option value="affiliate">Affiliate / Tracking URL</option>
                        <option value="referral">Custom Referral URL</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="text-xs font-bold text-slate-600 mb-1 block">Name / Label</label>
                    <input type="text" name="name" required placeholder="e.g. Samsonite US" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div class="mb-6">
                    <label class="text-xs font-bold text-slate-600 mb-1 block">Full URL</label>
                    <input type="url" name="url" required placeholder="https://..." class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <button type="submit" class="w-full bg-blue-600 text-white font-bold py-2 rounded-lg hover:bg-blue-700">Save Link</button>
            </form>
        </div>

        <!-- List -->
        <div class="bg-white p-6 rounded-xl shadow border border-slate-200 max-h-[500px] overflow-y-auto">
            <h2 class="text-lg font-bold mb-4">My Saved Links</h2>
            <?php if(mysqli_num_rows($links) == 0): ?>
                <p class="text-sm text-slate-500">No links saved yet.</p>
            <?php endif; ?>
            
            <?php while($l = mysqli_fetch_assoc($links)): ?>
                <div class="border border-slate-100 p-3 rounded-lg mb-3 bg-slate-50 relative group">
                    <span class="text-[10px] font-bold uppercase tracking-wider <?= $l['type'] == 'affiliate' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' ?> px-2 py-0.5 rounded">
                        <?= $l['type'] ?>
                    </span>
                    <h3 class="font-bold text-sm text-slate-800 mt-1"><?= htmlspecialchars($l['name']) ?></h3>
                    <p class="text-xs text-slate-500 truncate mt-1"><?= htmlspecialchars($l['url']) ?></p>
                    <a href="?delete=<?= $l['id'] ?>" class="absolute top-3 right-3 text-red-500 hover:text-red-700 text-xs font-bold opacity-0 group-hover:opacity-100 transition">Delete</a>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
  </div>
</body>
</html>