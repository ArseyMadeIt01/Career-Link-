<?php
$pageTitle = "Saved Jobs";
$currentPage = "saved_jobs";
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

// Handle job unsave
if (isset($_POST['unsave'])) {
    $job_id = (int)$_POST['job_id'];
    $delete_query = "DELETE FROM saved_jobs WHERE job_id = ? AND jobseeker_id = ?";
    $stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($stmt, "ii", $job_id, $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
}

// Get saved jobs
$query = "SELECT j.*, sj.saved_date as saved_date,
          (SELECT COUNT(*) FROM job_applications WHERE job_id = j.id) as application_count
          FROM saved_jobs sj
          JOIN jobs j ON sj.job_id = j.id
          WHERE sj.jobseeker_id = ? AND j.status = 'active'
          ORDER BY sj.saved_date DESC";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$saved_jobs = mysqli_stmt_get_result($stmt);
?>

<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                Saved Jobs
            </h2>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
            <a href="search_jobs.php" class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                <i class="fas fa-search mr-2"></i>
                Find More Jobs
            </a>
        </div>
    </div>

    <!-- Saved Jobs List -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="divide-y divide-gray-200">
            <?php if (mysqli_num_rows($saved_jobs) > 0): ?>
                <?php while ($job = mysqli_fetch_assoc($saved_jobs)): ?>
                    <div class="p-6 hover:bg-gray-50">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <h3 class="text-lg font-medium text-gray-900">
                                    <?php echo htmlspecialchars($job['title']); ?>
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-600">
                                        <i class="fas fa-building mr-2"></i>
                                        <?php echo htmlspecialchars($job['company_name']); ?>
                                    </p>
                                    <p class="text-sm text-gray-600 mt-1">
                                        <i class="fas fa-map-marker-alt mr-2"></i>
                                        <?php echo htmlspecialchars($job['location']); ?>
                                        <span class="mx-2">•</span>
                                        <i class="fas fa-clock mr-2"></i>
                                        <?php echo ucfirst($job['job_type']); ?>
                                    </p>
                                </div>
                                <div class="mt-4">
                                    <p class="text-sm text-gray-600 line-clamp-2">
                                        <?php echo htmlspecialchars(substr($job['description'], 0, 200)) . '...'; ?>
                                    </p>
                                </div>
                                <div class="mt-4 flex items-center text-sm text-gray-500">
                                    <i class="fas fa-bookmark mr-1.5"></i>
                                    Saved: <?php echo date('M d, Y', strtotime($job['saved_date'])); ?>
                                    <span class="mx-2">•</span>
                                    <i class="fas fa-users mr-1.5"></i>
                                    <?php echo $job['application_count']; ?> applicants
                                </div>
                            </div>
                            <div class="ml-4 flex flex-col items-end space-y-2">
                                <form method="POST" class="inline-block">
                                    <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                    <button type="submit" name="unsave" 
                                            class="text-red-600 hover:text-red-800"
                                            onclick="return confirm('Are you sure you want to remove this job from your saved list?')">
                                        <i class="fas fa-trash-alt mr-1"></i> Remove
                                    </button>
                                </form>
                                <a href="view_job.php?id=<?php echo $job['id']; ?>" 
                                   class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="p-6 text-center text-gray-500">
                    <i class="fas fa-bookmark text-4xl mb-4"></i>
                    <p>You haven't saved any jobs yet.</p>
                    <a href="search_jobs.php" class="mt-2 inline-block text-blue-600 hover:text-blue-800">
                        Start searching for jobs
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php mysqli_close($conn); ?> 