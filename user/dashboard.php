<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: /index.php");
    exit;
}
?>


<!DOCTYPE html>
<html>
<head>
  <title>User Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<style>
 @media (min-width: 768px) {
  html {
    overflow: auto !important;
  }
 }

  @media (max-width: 767px) {
      .sidebar {
        transition: .3s all;
    }

    .sidebar.open {
        left: 0 !important;
    }
  }
</style>
<body class="bg-gray-100">

<div class="flex min-h-screen">
   <!-- SIDEBAR -->
    <aside class="sidebar w-72 bg-gradient-to-b from-blue-700 to-indigo-700 text-white py-6 md:flex flex-col absolute md:static left-[-100%] top-0 h-full md:h-auto">
      <h2 class="text-2xl font-bold mb-10 px-6 flex md:block justify-between">User Panel <span id="close" class="block md:hidden cursor-pointer"><svg xmlns="http://www.w3.org/2000/svg" width="30px" height="30px" viewBox="0 0 24 24" fill="none">
<path d="M10.0303 8.96965C9.73741 8.67676 9.26253 8.67676 8.96964 8.96965C8.67675 9.26255 8.67675 9.73742 8.96964 10.0303L10.9393 12L8.96966 13.9697C8.67677 14.2625 8.67677 14.7374 8.96966 15.0303C9.26255 15.3232 9.73743 15.3232 10.0303 15.0303L12 13.0607L13.9696 15.0303C14.2625 15.3232 14.7374 15.3232 15.0303 15.0303C15.3232 14.7374 15.3232 14.2625 15.0303 13.9696L13.0606 12L15.0303 10.0303C15.3232 9.73744 15.3232 9.26257 15.0303 8.96968C14.7374 8.67678 14.2625 8.67678 13.9696 8.96968L12 10.9393L10.0303 8.96965Z" fill="#fff"/>
<path fill-rule="evenodd" clip-rule="evenodd" d="M12 1.25C6.06294 1.25 1.25 6.06294 1.25 12C1.25 17.9371 6.06294 22.75 12 22.75C17.9371 22.75 22.75 17.9371 22.75 12C22.75 6.06294 17.9371 1.25 12 1.25ZM2.75 12C2.75 6.89137 6.89137 2.75 12 2.75C17.1086 2.75 21.25 6.89137 21.25 12C21.25 17.1086 17.1086 21.25 12 21.25C6.89137 21.25 2.75 17.1086 2.75 12Z" fill="#fff"/>
</svg></span></h2>

      <nav class="text-sm">
        <a href="/user/dashboard.php" class="flex items-center gap-2 bg-white/20 px-4 py-3">
          <span><svg xmlns="http://www.w3.org/2000/svg" width="30px" height="30px" viewBox="0 -0.5 25 25" fill="#fff">
              <path
                d="M12.5 11.75C12.9142 11.75 13.25 11.4142 13.25 11C13.25 10.5858 12.9142 10.25 12.5 10.25V11.75ZM5.5 10.25C5.08579 10.25 4.75 10.5858 4.75 11C4.75 11.4142 5.08579 11.75 5.5 11.75V10.25ZM12.5 10.25C12.0858 10.25 11.75 10.5858 11.75 11C11.75 11.4142 12.0858 11.75 12.5 11.75V10.25ZM19.5 11.75C19.9142 11.75 20.25 11.4142 20.25 11C20.25 10.5858 19.9142 10.25 19.5 10.25V11.75ZM11.75 11C11.75 11.4142 12.0858 11.75 12.5 11.75C12.9142 11.75 13.25 11.4142 13.25 11H11.75ZM13.25 5C13.25 4.58579 12.9142 4.25 12.5 4.25C12.0858 4.25 11.75 4.58579 11.75 5H13.25ZM6.25 11C6.25 10.5858 5.91421 10.25 5.5 10.25C5.08579 10.25 4.75 10.5858 4.75 11H6.25ZM20.25 11C20.25 10.5858 19.9142 10.25 19.5 10.25C19.0858 10.25 18.75 10.5858 18.75 11H20.25ZM4.75 11C4.75 11.4142 5.08579 11.75 5.5 11.75C5.91421 11.75 6.25 11.4142 6.25 11H4.75ZM12.5 5.75C12.9142 5.75 13.25 5.41421 13.25 5C13.25 4.58579 12.9142 4.25 12.5 4.25V5.75ZM18.75 11C18.75 11.4142 19.0858 11.75 19.5 11.75C19.9142 11.75 20.25 11.4142 20.25 11H18.75ZM12.5 4.25C12.0858 4.25 11.75 4.58579 11.75 5C11.75 5.41421 12.0858 5.75 12.5 5.75V4.25ZM12.5 10.25H5.5V11.75H12.5V10.25ZM12.5 11.75H19.5V10.25H12.5V11.75ZM13.25 11V5H11.75V11H13.25ZM4.75 11V15H6.25V11H4.75ZM4.75 15C4.75 17.6234 6.87665 19.75 9.5 19.75V18.25C7.70507 18.25 6.25 16.7949 6.25 15H4.75ZM9.5 19.75H15.5V18.25H9.5V19.75ZM15.5 19.75C18.1234 19.75 20.25 17.6234 20.25 15H18.75C18.75 16.7949 17.2949 18.25 15.5 18.25V19.75ZM20.25 15V11H18.75V15H20.25ZM6.25 11V9H4.75V11H6.25ZM6.25 9C6.25 7.20507 7.70507 5.75 9.5 5.75V4.25C6.87665 4.25 4.75 6.37665 4.75 9H6.25ZM9.5 5.75H12.5V4.25H9.5V5.75ZM20.25 11V9H18.75V11H20.25ZM20.25 9C20.25 6.37665 18.1234 4.25 15.5 4.25V5.75C17.2949 5.75 18.75 7.20507 18.75 9H20.25ZM15.5 4.25H12.5V5.75H15.5V4.25Z"
                fill="#fff" />
            </svg></span> Dashboard
        </a>
        <a href="/user/profile.html" class="flex items-center gap-2 hover:bg-white/20 px-4 py-3">
          <span><svg xmlns="http://www.w3.org/2000/svg" width="30px" height="30px" viewBox="0 0 24 24" fill="none">
              <path
                d="M16 7C16 9.20914 14.2091 11 12 11C9.79086 11 8 9.20914 8 7C8 4.79086 9.79086 3 12 3C14.2091 3 16 4.79086 16 7Z"
                stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
              <path d="M12 14C8.13401 14 5 17.134 5 21H19C19 17.134 15.866 14 12 14Z" stroke="#fff" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round" />
            </svg></span> Profile
        </a>
      </nav>

      <div class="mt-auto bg-white/10 p-4 text-sm">
        Logged in as <b>User</b>
      </div>
    </aside>

  <main class="flex-1 p-6">
        <h1 class="text-2xl font-bold flex gap-1 align-center md:block"><span id="open" class="block md:hidden cursor-pointer"><svg xmlns="http://www.w3.org/2000/svg" width="30px" height="30px" viewBox="0 0 24 24" fill="none">
<path d="M4 6H20M4 12H20M4 18H20" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
</svg></span> Welcome User</h1>

    
  <div class="bg-white w-full max-w-md rounded-xl shadow-xl p-8 mx-auto">

    <!-- Title -->
    <div class="text-center mb-6">
      <h1 class="text-2xl font-bold text-blue-600 flex items-center justify-center gap-2">
        🎯 Custom Click Tool
      </h1>
    </div>

    <!-- Form -->
    <form class="space-y-5">

      <!-- Target URL -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Target URL
        </label>
        <input type="url" placeholder="https://example.com"
          class="w-full border rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 outline-none">
      </div>

      <!-- Number of Clicks -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Number of Clicks
        </label>
        <input type="number" value="10"
          class="w-full border rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 outline-none">
      </div>

      <!-- Duration -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Duration (minutes)
        </label>
        <input type="number" value="0"
          class="w-full border rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 outline-none">
        <p class="text-xs text-gray-400 mt-1">
          Enter 0 for immediate clicks
        </p>
      </div>

      <!-- Target GEO -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Target GEO (e.g., in, us, de)
        </label>
        <input type="text" value="in"
          class="w-full border rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 outline-none">
      </div>

      <!-- Button -->
      <button
        type="submit"
        class="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition flex items-center justify-center gap-2">
        🚀 Start Clicks
      </button>

    </form>

  </div>
  </main>

</div>

<script>
document.getElementById("open").addEventListener("click", () => {
  document.querySelector(".sidebar").classList.add("open");
  document.documentElement.style.overflow = "hidden";
});

document.getElementById("close").addEventListener("click", () => {
  document.querySelector(".sidebar").classList.remove("open");
  document.documentElement.style.overflow = "auto";
});


</script>
</body>
</html>
