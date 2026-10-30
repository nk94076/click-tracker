<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require 'include/config2.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $first_name = trim($_POST['first_name']);
    $last_name  = trim($_POST['last_name']);
    $email      = trim($_POST['email']);
    $role       = $_POST['role'];
    $password   = $_POST['password'];

    // Validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($role)) {
        $error = "All fields are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address";
    } else {

        // Check email exists
        $check = mysqli_query($conn, "SELECT id FROM users WHERE email='$email'");
        if (mysqli_num_rows($check) > 0) {
            $error = "Email already registered";
        } else {

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO users (first_name, last_name, email, role, password)
                    VALUES ('$first_name','$last_name','$email','$role','$hashed_password')";

            if (mysqli_query($conn, $sql)) {
                $success = "Registration successful";
            } else {
                $error = "Something went wrong";
            }
        }
    }
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-slate-900 flex items-center justify-center p-4">

    <!-- Card -->
    <div class="w-full max-w-5xl bg-white rounded-2xl shadow-2xl overflow-hidden grid grid-cols-1 md:grid-cols-2">

        <!-- LEFT PANEL -->
        <div class="bg-indigo-500 flex items-center justify-center p-10 relative">
            <img src="img/register.png" alt="Illustration" class="w-4/5" />
        </div>

        <!-- RIGHT PANEL -->
        <div class="p-10 flex items-center">
            <div class="w-full">

                <h2 class="text-2xl font-bold text-gray-800 text-center">
                    REGISTER
                </h2>
                <p class="text-sm text-gray-500 text-center mb-8">
                    Enter your information to register
                </p>

                <form method="POST" class="space-y-5">

                    <!-- Messages -->
                    <?php if($error): ?>
                        <div class="text-red-500 text-sm"><?= $error ?></div>
                    <?php endif; ?>
                
                    <?php if($success): ?>
                        <div class="text-green-500 text-sm"><?= $success ?></div>
                    <?php endif; ?>
                
                    <!-- First & Last Name -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm text-gray-600">First name</label>
                            <input type="text" name="first_name" placeholder="John"
                                class="w-full px-4 py-2 border rounded-lg">
                        </div>
                
                        <div>
                            <label class="text-sm text-gray-600">Last name</label>
                            <input type="text" name="last_name" placeholder="Smith"
                                class="w-full px-4 py-2 border rounded-lg">
                        </div>
                    </div>
                
                    <!-- Email -->
                    <div>
                        <label class="text-sm text-gray-600">Email</label>
                        <input type="email" name="email" placeholder="johnsmith@example.com"
                            class="w-full px-4 py-2 border rounded-lg">
                    </div>
                
                    <!-- Role -->
                    <div>
                        <label class="text-sm text-gray-600">Register As</label>
                        <select name="role"
                            class="w-full px-4 py-3 rounded-lg border">
                            <option value="">Select role</option>
                            <option value="admin">Admin</option>
                            <option value="user">User</option>
                        </select>
                    </div>
                
                    <!-- Password -->
                    <div>
                        <label class="text-sm text-gray-600">Password</label>
                        <input type="password" name="password" placeholder="********"
                            class="w-full px-4 py-2 border rounded-lg">
                    </div>
                
                    <!-- Button -->
                    <button class="w-full bg-indigo-500 text-white py-3 rounded-lg font-semibold">
                        REGISTER NOW
                    </button>
                
                </form>


            </div>
        </div>

    </div>

</body>

</html>