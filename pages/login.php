<?php
session_start();
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("Location: admin.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $_POST['username'];
    $pass = $_POST['password'];
    if ($user === "admin" && $pass === "password") {
        $_SESSION['loggedin'] = true;
        header("Location: admin.php");
    } else {
        echo "Invalid credentials";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 flex items-center justify-center h-screen">
    <form method="post" class="bg-white p-8 rounded shadow-md">
        <h2 class="text-2xl mb-4">Admin Login</h2>
        <input type="text" name="username" placeholder="Username" class="block w-full mb-4 p-2 border">
        <input type="password" name="password" placeholder="Password" class="block w-full mb-4 p-2 border">
        <button type="submit" class="bg-blue-500 text-white p-2 w-full">Login</button>
    </form>
</body>

</html>