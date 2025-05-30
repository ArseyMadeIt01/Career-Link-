<?php
session_start();

// Check if user is logged in and is an employer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'employer') {
    header("Location: login.php");
    exit();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $db_host = 'localhost';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'career_link';

    $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

    if (!$conn) {
        $error = "Connection failed: " . mysqli_connect_error();
    } else {
        // Check profile completion
        $profile_query = "SELECT * FROM employer_profiles WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $profile_query);
        mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);
        $profile = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        $required_fields = ['company_name', 'company_description', 'industry', 'company_size', 'location', 'contact_email'];
        $profile_complete = true;
        foreach ($required_fields as $field) {
            if (empty($profile[$field])) {
                $profile_complete = false;
                break;
            }
        }

        if (!$profile_complete) {
            $_SESSION['error'] = "Please complete your company profile before posting a job.";
            header("Location: employer_profile.php");
            exit();
        }

        // Get form data
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $requirements = mysqli_real_escape_string($conn, $_POST['requirements']);
        $location = mysqli_real_escape_string($conn, $_POST['location']);
        $job_type = mysqli_real_escape_string($conn, $_POST['job_type']);
        $salary_range = mysqli_real_escape_string($conn, $_POST['salary_range']);
        $deadline_date = mysqli_real_escape_string($conn, $_POST['deadline_date']);

        // Insert job posting
        $query = "INSERT INTO jobs (employer_id, title, description, requirements, location, 
                                  job_type, salary_range, deadline_date, status) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')";
        
        if ($stmt = mysqli_prepare($conn, $query)) {
            mysqli_stmt_bind_param($stmt, "isssssss", 
                $_SESSION['user_id'], $title, $description, $requirements, 
                $location, $job_type, $salary_range, $deadline_date);
            
            if (mysqli_stmt_execute($stmt)) {
                header("Location: employer_dashboard.php?success=1");
                exit();
            } else {
                $error = "Error posting job. Please try again.";
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
    <title>Post a Job - Career Link</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Navigation (same as employer_dashboard.php) -->
    <nav class="bg-white shadow-lg border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="home.php" class="text-2xl font-bold text-blue-600">Career<span class="text-gray-800">Link</span></a>
                    </div>
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="employer_dashboard.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Dashboard
                        </a>
                        <a href="post_job.php" class="border-blue-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Post a Job
                        </a>
                        <a href="manage_jobs.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Manage Jobs
                        </a>
                    </div>
                </div>
                <!-- User Menu (same as employer_dashboard.php) -->
                <div class="flex items-center">
                    <div class="flex-shrink-0 relative ml-4 dropdown">
                        <button class="bg-white flex text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" id="user-menu-button">
                            <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                                <span class="text-blue-700 font-medium text-sm">
                                    <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                                </span>
                            </div>
                        </button>
                        <div class="hidden origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 dropdown-menu">
                            <a href="employer_profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Your Profile</a>
                            <a href="company_settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Company Settings</a>
                            <a href="logout.php" class="block px-4 py-2 text-sm text-red-700 hover:bg-gray-100">Sign out</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow px-5 py-6 sm:px-6">
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900">Post a New Job</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Fill out the form below to create a new job posting.
                </p>
            </div>

            <?php if (isset($error)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="space-y-6">
                <!-- Job Title -->
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700">Job Title</label>
                    <input type="text" name="title" id="title" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <!-- Job Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Job Description</label>
                    <textarea name="description" id="description" rows="4" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                </div>

                <!-- Requirements -->
                <div>
                    <label for="requirements" class="block text-sm font-medium text-gray-700">Requirements</label>
                    <textarea name="requirements" id="requirements" rows="4" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                </div>

                <!-- Location -->
                <div>
                    <label for="location" class="block text-sm font-medium text-gray-700">Location</label>
                    <input type="text" name="location" id="location" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <!-- Job Type -->
                <div>
                    <label for="job_type" class="block text-sm font-medium text-gray-700">Job Type</label>
                    <select name="job_type" id="job_type" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="full-time">Full Time</option>
                        <option value="part-time">Part Time</option>
                        <option value="contract">Contract</option>
                        <option value="internship">Internship</option>
                    </select>
                </div>

                <!-- Salary Range -->
                <div>
                    <label for="salary_range" class="block text-sm font-medium text-gray-700">Salary Range</label>
                    <input type="text" name="salary_range" id="salary_range" placeholder="e.g., Ksh.50,000 - Ksh.70,000"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <!-- Application Deadline -->
                <div>
                    <label for="deadline_date" class="block text-sm font-medium text-gray-700">Application Deadline</label>
                    <input type="date" name="deadline_date" id="deadline_date" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end">
                    <button type="submit"
                        class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Post Job
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Dropdown toggle (same as employer_dashboard.php)
        document.getElementById('user-menu-button').addEventListener('click', function() {
            document.querySelector('.dropdown-menu').classList.toggle('hidden');
        });

        document.addEventListener('click', function(event) {
            if (!event.target.closest('.dropdown')) {
                document.querySelector('.dropdown-menu').classList.add('hidden');
            }
        });

        // Set minimum date for deadline
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('deadline_date').min = today;
        });
    </script>
</body>
</html> 