<?php
// Debug ke time uncomment karo
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

session_start();

if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: /admin/dashboard.php");
    } else {
        header("Location: /user/impact-clicker.php");
    }
    exit;
}

require __DIR__ . '/include/config2.php';

$error    = '';
$email    = '';
$remember = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = !empty($_POST['remember']);

    if ($email === '' || $password === '') {
        $error = "Please enter your email and password.";
    } else {

        $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->num_rows === 1 ? $result->fetch_assoc() : null;

        if ($user && password_verify($password, $user['password'])) {

            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role']    = $user['role'];

            // Remember me: session cookie 30 din tak valid
            if ($remember) {
                $lifetime = 60 * 60 * 24 * 30;
                ini_set('session.gc_maxlifetime', $lifetime);
                setcookie(session_name(), session_id(), [
                    'expires'  => time() + $lifetime,
                    'path'     => '/',
                    'secure'   => true,
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
            }

            if ($user['role'] === 'admin') {
                header("Location: /admin/dashboard.php");
            } else {
                header("Location: /user/impact-clicker.php");
            }
            exit;
        }

        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login · Adhook Media</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Poppins', 'system-ui', 'sans-serif'] },
                    colors: {
                        brand: { 50:'#eef4ff', 100:'#dce7ff', 400:'#4f86ff', 500:'#1f63f5', 600:'#0b55ea', 700:'#0846c7', 900:'#0b1a3a' },
                        ink: '#0b1a3a'
                    }
                }
            }
        }
    </script>
    <style>
        .dots {
            background-image: radial-gradient(rgba(255,255,255,.35) 1.5px, transparent 1.6px);
            background-size: 16px 16px;
        }
    </style>
</head>

<body class="min-h-screen bg-white font-sans text-ink antialiased">

<div class="flex min-h-screen flex-col lg:flex-row">

    <!-- ============ LEFT ============ -->
    <section class="relative hidden lg:flex lg:w-[56%] overflow-hidden bg-gradient-to-br from-white via-white to-brand-50">

        <!-- soft background shapes -->
        <div class="pointer-events-none absolute -top-20 right-10 h-[420px] w-[420px] rotate-12 rounded-[60px] bg-brand-100/40"></div>
        <div class="pointer-events-none absolute -bottom-24 -left-24 h-80 w-80 rounded-full bg-emerald-200/30 blur-3xl"></div>

        <div class="relative z-10 flex w-full items-center">

            <!-- Text column -->
            <div class="w-full xl:w-[52%] px-12 xl:pl-20 xl:pr-6 py-16">

                <!-- Logo -->
                <div class="inline-block">
                    <div class="flex items-start">
                        <span class="text-[44px] font-extrabold leading-none tracking-tight text-ink">Adhook</span>
                        <svg class="-mt-1 ml-0.5 h-6 w-6 text-brand-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round">
                            <path d="M6 18 18 6M9 6h9v9"/>
                        </svg>
                    </div>
                    <p class="mt-2 pl-1 text-[11px] font-medium tracking-[0.45em] text-ink">MEDIA PVT. LTD.</p>
                </div>

                <h1 class="mt-14 text-[44px] font-extrabold leading-tight tracking-tight">
                    Welcome <span class="text-brand-500">Back!</span>
                </h1>
                <p class="mt-4 max-w-sm text-[17px] leading-relaxed text-slate-500">
                    Sign in to your account and continue your journey with Adhook Media.
                </p>

                <!-- Features -->
                <ul class="mt-10 space-y-7">
                    <li class="flex items-center gap-5">
                        <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-brand-50 text-brand-500">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="8" r="3.2"/><circle cx="5.5" cy="9.5" r="2.4"/><circle cx="18.5" cy="9.5" r="2.4"/><path d="M6 19c0-3.3 2.7-5.5 6-5.5s6 2.2 6 5.5v.5H6z"/><path d="M1.5 18.5c0-2.4 1.8-4 4.2-4 .6 0 1.1.1 1.6.3A6.6 6.6 0 0 0 5 19H1.5zM22.5 18.5c0-2.4-1.8-4-4.2-4-.6 0-1.1.1-1.6.3A6.6 6.6 0 0 1 19 19h3.5z"/></svg>
                        </span>
                        <div>
                            <p class="text-lg font-semibold">Collaborate</p>
                            <p class="text-[15px] text-slate-500">Work together seamlessly</p>
                        </div>
                    </li>
                    <li class="flex items-center gap-5">
                        <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-500">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="currentColor"><rect x="3" y="13" width="4" height="8" rx="1"/><rect x="10" y="8" width="4" height="13" rx="1"/><rect x="17" y="4" width="4" height="17" rx="1"/></svg>
                        </span>
                        <div>
                            <p class="text-lg font-semibold">Stay Productive</p>
                            <p class="text-[15px] text-slate-500">Track progress in one place</p>
                        </div>
                    </li>
                    <li class="flex items-center gap-5">
                        <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-orange-50 text-orange-500">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2 4 5v6c0 5.2 3.4 9.4 8 11 4.6-1.6 8-5.8 8-11V5l-8-3z"/><path d="m8.5 12 2.5 2.5 4.5-5" stroke="#fff" stroke-width="2.2" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                        <div>
                            <p class="text-lg font-semibold">Secure &amp; Reliable</p>
                            <p class="text-[15px] text-slate-500">Your data is always safe</p>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- Illustration column -->
            <div class="hidden xl:flex w-[48%] items-end justify-center self-stretch pr-6 pb-10">
                <img src="/img/login.png" alt="Working at desk" class="w-full max-w-[460px] object-contain drop-shadow-sm">
            </div>
        </div>
    </section>

    <!-- ============ RIGHT ============ -->
    <section class="relative flex flex-1 items-center justify-center overflow-hidden bg-gradient-to-br from-brand-600 to-brand-500 px-5 py-12 sm:px-10">

        <!-- decorations -->
        <div class="dots pointer-events-none absolute left-6 top-6 h-28 w-32 opacity-70"></div>
        <div class="dots pointer-events-none absolute bottom-6 right-6 h-36 w-24 opacity-70"></div>
        <div class="pointer-events-none absolute -right-28 -top-28 h-[380px] w-[380px] rounded-full border-[70px] border-white/10"></div>
        <div class="pointer-events-none absolute -bottom-40 -left-24 h-[360px] w-[360px] rounded-full bg-white/10"></div>

        <div class="relative z-10 w-full max-w-[480px]">

            <!-- Mobile logo -->
            <div class="mb-8 text-center text-white lg:hidden">
                <span class="text-4xl font-extrabold tracking-tight">Adhook</span>
                <p class="mt-1 text-[10px] tracking-[0.45em] text-white/80">MEDIA PVT. LTD.</p>
            </div>

            <div class="rounded-2xl bg-white p-8 shadow-2xl shadow-brand-900/20 sm:p-10">

                <h2 class="text-4xl font-extrabold tracking-tight">Hello!</h2>
                <p class="mt-1 text-[17px] text-slate-500">Sign in to get started</p>

                <?php if (!empty($error)): ?>
                    <div class="mt-6 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
                        <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" class="mt-7" novalidate>

                    <!-- Email -->
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-5 text-slate-500">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="m3.5 6.5 8.5 6 8.5-6"/></svg>
                        </span>
                        <input type="email" name="email" autocomplete="email" required autofocus
                               value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                               placeholder="Email Address" aria-label="Email Address"
                               class="block w-full rounded-xl border border-slate-200 bg-white py-4 pl-14 pr-4 text-[15px] placeholder-slate-400 transition focus:border-brand-500 focus:outline-none focus:ring-4 focus:ring-brand-100">
                    </div>

                    <!-- Password -->
                    <div class="relative mt-4">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-5 text-slate-500">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="5" y="10.5" width="14" height="10" rx="2"/><path stroke-linecap="round" d="M8 10.5V7.5a4 4 0 0 1 8 0v3"/><circle cx="12" cy="15.5" r="1.2" fill="currentColor"/></svg>
                        </span>
                        <input id="password" type="password" name="password" autocomplete="current-password" required
                               placeholder="Password" aria-label="Password"
                               class="block w-full rounded-xl border border-slate-200 bg-white py-4 pl-14 pr-14 text-[15px] placeholder-slate-400 transition focus:border-brand-500 focus:outline-none focus:ring-4 focus:ring-brand-100">
                        <button type="button" id="togglePwd" aria-label="Show password"
                                class="absolute inset-y-0 right-0 flex items-center pr-5 text-slate-500 hover:text-slate-700">
                            <svg id="eyeOpen" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12S6 5 12 5s9.5 7 9.5 7-3.5 7-9.5 7S2.5 12 2.5 12z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg id="eyeClosed" class="hidden h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M10.6 10.6a2 2 0 002.8 2.8M9.9 5.1A9.7 9.7 0 0112 5c6 0 9.5 7 9.5 7a17 17 0 01-3.2 4.2M6.1 6.1C3.9 7.7 2.5 12 2.5 12s3.5 7 9.5 7a9.5 9.5 0 004.3-1"/></svg>
                        </button>
                    </div>

                    <!-- Remember / Forgot -->
                    <div class="mt-5 flex items-center justify-between">
                        <label class="flex cursor-pointer items-center gap-3 text-sm text-slate-600">
                            <input type="checkbox" name="remember" value="1" <?= $remember ? 'checked' : '' ?>
                                   class="h-5 w-5 rounded-md border-slate-300 text-brand-600 focus:ring-brand-500">
                            Remember me
                        </label>
                        <a href="#" class="text-sm font-medium text-brand-600 hover:text-brand-700">Forgot Password?</a>
                    </div>

                    <!-- Login -->
                    <button type="submit" id="submitBtn"
                            class="mt-7 flex w-full items-center justify-center gap-2 rounded-full bg-brand-600 py-4 text-lg font-semibold text-white shadow-lg shadow-brand-600/30 transition hover:bg-brand-700 focus:outline-none focus:ring-4 focus:ring-brand-100 disabled:cursor-not-allowed disabled:opacity-70">
                        <svg id="spinner" class="hidden h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                        <span id="btnText">Login</span>
                        <svg id="btnArrow" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </section>
</div>

<script>
    const pwd = document.getElementById('password');
    document.getElementById('togglePwd').addEventListener('click', function () {
        const show = pwd.type === 'password';
        pwd.type = show ? 'text' : 'password';
        this.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        document.getElementById('eyeOpen').classList.toggle('hidden', show);
        document.getElementById('eyeClosed').classList.toggle('hidden', !show);
    });

    document.querySelector('form').addEventListener('submit', function (e) {
        if (!this.checkValidity()) { e.preventDefault(); this.reportValidity(); return; }
        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        document.getElementById('spinner').classList.remove('hidden');
        document.getElementById('btnArrow').classList.add('hidden');
        document.getElementById('btnText').textContent = 'Signing in…';
    });
</script>

</body>
</html>