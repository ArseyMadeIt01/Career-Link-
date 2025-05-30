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
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $user_type = mysqli_real_escape_string($conn, $_POST['user_type']);

        // Validation
        if ($password !== $confirm_password) {
            $error = "Passwords do not match";
        } else {
            // Check if username already exists
            $check_query = "SELECT id FROM users WHERE username = ? OR email = ?";
            $check_stmt = mysqli_prepare($conn, $check_query);
            mysqli_stmt_bind_param($check_stmt, "ss", $username, $email);
            mysqli_stmt_execute($check_stmt);
            mysqli_stmt_store_result($check_stmt);

            if (mysqli_stmt_num_rows($check_stmt) > 0) {
                $error = "Username or email already exists";
            } else {
                // Create new user
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $insert_query = "INSERT INTO users (username, email, password_hash, user_type) VALUES (?, ?, ?, ?)";
                
                if ($insert_stmt = mysqli_prepare($conn, $insert_query)) {
                    mysqli_stmt_bind_param($insert_stmt, "ssss", $username, $email, $password_hash, $user_type);
                    
                    if (mysqli_stmt_execute($insert_stmt)) {
                        $_SESSION['registration_success'] = true;
                        header("Location: login.php");
                        exit();
                    } else {
                        $error = "Registration failed. Please try again.";
                    }
                    mysqli_stmt_close($insert_stmt);
                }
            }
            mysqli_stmt_close($check_stmt);
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
    <title>Register - Career Link</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center py-12">
    <div class="max-w-md w-full mx-4">
        <div class="bg-white rounded-lg shadow-xl p-8">
            <!-- Header Section -->
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-gray-800">Create Account</h2>
                <p class="text-gray-500 mt-2">Join Career Link today</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="space-y-6">
                <!-- Username Input -->
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="username">
                        Username
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                            <i class="fas fa-user"></i>
                        </span>
                        <input class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            id="username" type="text" name="username" required 
                            placeholder="Choose a username">
                    </div>
                </div>

                <!-- Email Input -->
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="email">
                        Email
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            id="email" type="email" name="email" required 
                            placeholder="Enter your email">
                    </div>
                </div>

                <!-- Password Input -->
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="password">
                        Password
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            id="password" type="password" name="password" required
                            placeholder="Create a password">
                    </div>
                </div>

                <!-- Confirm Password Input -->
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="confirm_password">
                        Confirm Password
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            id="confirm_password" type="password" name="confirm_password" required
                            placeholder="Confirm your password">
                    </div>
                </div>

                <!-- User Type Selection -->
                <div>
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
                    Create Account
                </button>
            </form>

            <!-- Login Link -->
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    Already have an account? 
                    <a href="login.php" class="text-blue-600 hover:text-blue-800 font-semibold">
                        Sign in
                    </a>
                </p>
            </div>
        </div>
    </div>

    <script>
        // Optional: Add client-side password validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
        });
    </script>
</body>
</html> 