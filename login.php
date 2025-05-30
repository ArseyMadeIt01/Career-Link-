<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $db_host = 'localhost';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'career_link';

    $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

    if (!$conn) {
        $error = "Connection failed: " . mysqli_connect_error();
    } else {
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $password = $_POST['password'];
        $user_type = mysqli_real_escape_string($conn, $_POST['user_type']);

        $query = "SELECT id, username, password_hash, user_type FROM users 
                WHERE username = ? AND user_type = ?";
        
        if ($stmt = mysqli_prepare($conn, $query)) {
            mysqli_stmt_bind_param($stmt, "ss", $username, $user_type);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($user = mysqli_fetch_assoc($result)) {
                if (password_verify($password, $user['password_hash'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_type'] = $user['user_type'];

                    header("Location: " . ($user['user_type'] == 'employer' ? 'employer_dashboard.php' : 'jobseeker_dashboard.php'));
                    exit();
                } else {
                    $error = "Invalid password";
                }
            } else {
                $error = "User not found";
            }
            mysqli_stmt_close($stmt);
        }
        mysqli_close($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Career Link</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <div class="bg-white rounded-lg shadow-xl p-8">
            <!-- Logo/Header Section -->
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-gray-800">Welcome Back</h2>
                <p class="text-gray-500 mt-2">Please sign in to your account</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <!-- Username Input -->
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="username">
                        Username
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                            <i class="fas fa-user"></i>
                        </span>
                        <input class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            id="username" type="text" name="username" required 
                            placeholder="Enter your username">
                    </div>
                </div>

                <!-- Password Input -->
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="password">
                        Password
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            id="password" type="password" name="password" required
                            placeholder="Enter your password">
                    </div>
                </div>

                <!-- User Type Selection -->
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-semibold mb-2">Account Type</label>
                    <div class="flex gap-6">
                        <label class="flex items-center">
                            <input type="radio" name="user_type" value="jobseeker" 
                                class="form-radio text-blue-600" required>
                            <span class="ml-2 text-gray-700">Job Seeker</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="user_type" value="employer" 
                                class="form-radio text-blue-600">
                            <span class="ml-2 text-gray-700">Employer</span>
                        </label>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" 
                    class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-300">
                    Sign In
                </button>
            </form>

            <!-- Links -->
            <div class="mt-6 text-center">
                <a href="forgot_password.php" class="text-sm text-blue-600 hover:text-blue-800">
                    Forgot your password?
                </a>
                <p class="mt-4 text-sm text-gray-600">
                    Don't have an account? 
                    <a href="register.php" class="text-blue-600 hover:text-blue-800 font-semibold">
                        Sign up
                    </a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>

