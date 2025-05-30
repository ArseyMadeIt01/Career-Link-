<?php
session_start();
$pageTitle = "Manage Jobs";
$currentPage = "manage_jobs";
require_once 'includes/header.php';


// Check if user is logged in and is an employer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'employer') {
    header("Location: login.php");
    exit();
}

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'career_link';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Handle job deletion
if (isset($_POST['delete_job'])) {
    $job_id = (int)$_POST['job_id'];
    $delete_query = "DELETE FROM jobs WHERE id = ? AND employer_id = ?";
    $stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($stmt, "ii", $job_id, $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
}

// Get all jobs with application counts
$query = "SELECT j.*, 
          COUNT(ja.id) as application_count,
          (SELECT COUNT(*) FROM job_applications 
           WHERE job_id = j.id AND status = 'pending') as pending_count
          FROM jobs j
          LEFT JOIN job_applications ja ON j.id = ja.job_id
          WHERE j.employer_id = ?
          GROUP BY j.id
          ORDER BY j.posted_date DESC";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$jobs = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Jobs - Career Link</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Navigation (same as employer_dashboard.php) -->
    <nav class="bg-white shadow-lg border-b border-gray-200">
        <!-- ... (same navigation code) ... -->
    </nav>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="md:flex md:items-center md:justify-between mb-6">
            <div class="flex-1 min-w-0">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                    Manage Jobs
                </h2>
            </div>
            <div class="mt-4 flex md:mt-0 md:ml-4">
                <a href="post_job.php" class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-plus mr-2"></i>
                    Post New Job
                </a>
            </div>
        </div>

        <!-- Success Message -->
        <?php if (isset($_GET['success'])): ?>
            <div class="mb-6 bg-green-100 border-l-4 border-green-500 text-green-700 p-4" role="alert">
                <p>Job updated successfully!</p>
            </div>
        <?php endif; ?>

        <!-- Jobs Table -->
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <ul class="divide-y divide-gray-200">
                <?php foreach ($jobs as $job): ?>
                    <li>
                        <div class="px-4 py-4 sm:px-6">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <h3 class="text-lg font-medium text-gray-900">
                                        <?php echo htmlspecialchars($job['title']); ?>
                                        <span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php echo $job['status'] == 'active' ? 'bg-green-100 text-green-800' : 
                                                    ($job['status'] == 'draft' ? 'bg-gray-100 text-gray-800' : 
                                                    'bg-red-100 text-red-800'); ?>">
                                            <?php echo ucfirst($job['status']); ?>
                                        </span>
                                    </h3>
                                    <div class="mt-2 flex items-center text-sm text-gray-500">
                                        <i class="fas fa-map-marker-alt mr-1.5"></i>
                                        <?php echo htmlspecialchars($job['location']); ?>
                                        <span class="mx-2">•</span>
                                        <i class="fas fa-clock mr-1.5"></i>
                                        <?php echo $job['job_type']; ?>
                                    </div>
                                </div>
                                <div class="flex flex-col items-end">
                                    <div class="flex space-x-2 mb-2">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            <i class="fas fa-users mr-1"></i>
                                            <?php echo $job['application_count']; ?> Applications
                                        </span>
                                        <?php if ($job['pending_count'] > 0): ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                <?php echo $job['pending_count']; ?> Pending
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex space-x-3">
                                        <a href="view_applications.php?job_id=<?php echo $job['id']; ?>" 
                                           class="text-blue-600 hover:text-blue-900">
                                            <i class="fas fa-eye mr-1"></i> View Applications
                                        </a>
                                        <a href="edit_job.php?id=<?php echo $job['id']; ?>" 
                                           class="text-gray-600 hover:text-gray-900">
                                            <i class="fas fa-edit mr-1"></i> Edit
                                        </a>
                                        <button onclick="confirmDelete(<?php echo $job['id']; ?>)" 
                                                class="text-red-600 hover:text-red-900">
                                            <i class="fas fa-trash-alt mr-1"></i> Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>

                <?php if (empty($jobs)): ?>
                    <li class="px-4 py-8 text-center text-gray-500">
                        No jobs posted yet. Click "Post New Job" to get started!
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-sm mx-auto">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Confirm Deletion</h3>
            <p class="text-sm text-gray-500 mb-4">
                Are you sure you want to delete this job posting? This action cannot be undone.
            </p>
            <div class="flex justify-end space-x-3">
                <button onclick="closeDeleteModal()" 
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <form id="deleteForm" method="POST" class="inline">
                    <input type="hidden" name="job_id" id="deleteJobId">
                    <input type="hidden" name="delete_job" value="1">
                    <button type="submit" 
                            class="px-4 py-2 bg-red-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-red-700">
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete(jobId) {
            document.getElementById('deleteJobId').value = jobId;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });
    </script>
</body>
</html>
