<?php
$pageTitle = "My Applications";
$currentPage = "applications";
require_once 'includes/header.php';

// Check if user is logged in and is a jobseeker
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'jobseeker') {
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

// Get all applications with job details
$query = "SELECT ja.*, j.title, j.company_name, j.location, j.job_type
          FROM job_applications ja 
          JOIN jobs j ON ja.job_id = j.id 
          WHERE ja.jobseeker_id = ? 
          ORDER BY ja.application_date DESC";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$applications = mysqli_stmt_get_result($stmt);
?>

<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                My Applications
            </h2>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
            <a href="search_jobs.php" class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                <i class="fas fa-search mr-2"></i>
                Find More Jobs
            </a>
        </div>
    </div>

    <!-- Applications List -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="divide-y divide-gray-200">
            <?php if (mysqli_num_rows($applications) > 0): ?>
                <?php while ($application = mysqli_fetch_assoc($applications)): ?>
                    <div class="p-6 hover:bg-gray-50">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <h3 class="text-lg font-medium text-gray-900">
                                    <?php echo htmlspecialchars($application['title']); ?>
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-600">
                                        <i class="fas fa-building mr-2"></i>
                                        <?php echo htmlspecialchars($application['company_name']); ?>
                                    </p>
                                    <p class="text-sm text-gray-600 mt-1">
                                        <i class="fas fa-map-marker-alt mr-2"></i>
                                        <?php echo htmlspecialchars($application['location']); ?>
                                        <span class="mx-2">•</span>
                                        <i class="fas fa-clock mr-2"></i>
                                        <?php echo ucfirst($application['job_type']); ?>
                                    </p>
                                </div>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">
                                        <i class="fas fa-calendar mr-2"></i>
                                        Applied: <?php echo date('M d, Y', strtotime($application['application_date'])); ?>
                                    </p>
                                </div>
                            </div>
                            <div class="ml-4 flex flex-col items-end">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    <?php echo $application['status'] == 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                            ($application['status'] == 'reviewed' ? 'bg-blue-100 text-blue-800' : 
                                            ($application['status'] == 'shortlisted' ? 'bg-green-100 text-green-800' : 
                                            'bg-red-100 text-red-800')); ?>">
                                    <?php echo ucfirst($application['status']); ?>
                                </span>
                                <a href="view_job.php?id=<?php echo $application['job_id']; ?>" 
                                   class="mt-2 text-sm text-blue-600 hover:text-blue-800">
                                    View Job Details
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="p-6 text-center text-gray-500">
                    <i class="fas fa-folder-open text-4xl mb-4"></i>
                    <p>You haven't applied to any jobs yet.</p>
                    <a href="search_jobs.php" class="mt-2 inline-block text-blue-600 hover:text-blue-800">
                        Start searching for jobs
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php mysqli_close($conn); ?> 