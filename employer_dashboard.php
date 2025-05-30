<?php
session_start();
$pageTitle = "Dashboard";
$currentPage = "dashboard";
require_once 'includes/header.php';

// Check if user is logged in and is an employer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'employer') {
    header("Location: login.php");
    exit();
}

// Database connection
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'career_link';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Get employer's posted jobs
$query = "SELECT * FROM jobs WHERE employer_id = ? ORDER BY posted_date DESC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$jobs = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Get total applications
$applications_query = "SELECT COUNT(*) as total FROM job_applications 
                      WHERE job_id IN (SELECT id FROM jobs WHERE employer_id = ?)";
$stmt = mysqli_prepare($conn, $applications_query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$applications_result = mysqli_stmt_get_result($stmt);
$total_applications = mysqli_fetch_assoc($applications_result)['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employer Dashboard - Career Link</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body class="bg-gray-50">
    
    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Stats Section -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-briefcase text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-gray-500">Posted Jobs</p>
                        <p class="text-2xl font-semibold"><?php echo count($jobs); ?></p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-users text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-gray-500">Total Applications</p>
                        <p class="text-2xl font-semibold"><?php echo $total_applications; ?></p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <a href="post_job.php" class="flex items-center justify-center h-full">
                    <button class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-plus mr-2"></i> Post New Job
                    </button>
                </a>
            </div>
        </div>

        <!-- Recent Jobs Section -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-800">Recent Job Postings</h2>
            </div>
            <div class="divide-y divide-gray-200">
                <?php foreach ($jobs as $job): ?>
                <div class="p-6 hover:bg-gray-50">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">
                                <?php echo htmlspecialchars($job['title']); ?>
                            </h3>
                            <p class="text-gray-600 mt-1">
                                <i class="fas fa-map-marker-alt mr-2"></i>
                                <?php echo htmlspecialchars($job['location']); ?>
                            </p>
                            <p class="text-gray-500 mt-2">
                                Posted: <?php echo date('M d, Y', strtotime($job['posted_date'])); ?>
                            </p>
                        </div>
                        <div class="flex space-x-3">
                            <a href="view_applications.php?job_id=<?php echo $job['id']; ?>" 
                               class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-users mr-1"></i> View Applications
                            </a>
                            <a href="edit_job.php?id=<?php echo $job['id']; ?>" 
                               class="text-gray-600 hover:text-gray-800">
                                <i class="fas fa-edit mr-1"></i> Edit
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($jobs)): ?>
                <div class="p-6 text-center text-gray-500">
                    No jobs posted yet. Click "Post New Job" to get started!
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add this JavaScript at the bottom of the file, before </body> -->
    <script>
        // Dropdown toggle
        document.getElementById('user-menu-button').addEventListener('click', function() {
            document.querySelector('.dropdown-menu').classList.toggle('hidden');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.dropdown')) {
                document.querySelector('.dropdown-menu').classList.add('hidden');
            }
        });
    </script>
</body>
</html> 