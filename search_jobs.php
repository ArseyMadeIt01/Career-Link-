<?php
$pageTitle = "Search Jobs";
$currentPage = "search_jobs";
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

// Basic query to get all active jobs
$query = "SELECT j.*, 
          COALESCE(j.company_name, ep.company_name) as company_name,
          (SELECT COUNT(*) FROM job_applications WHERE job_id = j.id) as application_count,
          CASE WHEN sj.job_id IS NOT NULL THEN 1 ELSE 0 END as is_saved
          FROM jobs j
          LEFT JOIN employer_profiles ep ON j.employer_id = ep.user_id
          LEFT JOIN saved_jobs sj ON j.id = sj.job_id AND sj.jobseeker_id = ?
          WHERE j.status = 'active'
          ORDER BY j.posted_date DESC";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$jobs = mysqli_stmt_get_result($stmt);

// Handle job saving (AJAX request)
if (isset($_POST['action']) && $_POST['action'] == 'toggle_save') {
    $job_id = (int)$_POST['job_id'];
    $is_saved = (int)$_POST['is_saved'];
    
    if ($is_saved) {
        $save_query = "DELETE FROM saved_jobs WHERE job_id = ? AND jobseeker_id = ?";
    } else {
        $save_query = "INSERT INTO saved_jobs (job_id, jobseeker_id) VALUES (?, ?)";
    }
    
    $stmt = mysqli_prepare($conn, $save_query);
    mysqli_stmt_bind_param($stmt, "ii", $job_id, $_SESSION['user_id']);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'is_saved' => !$is_saved]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit();
}
?>

<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <!-- Search Filters -->
    <div class="bg-white shadow-sm rounded-lg mb-6">
        <div class="px-4 py-5 sm:p-6">
            <form method="GET" class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                    <input type="text" name="search" id="search" 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                           placeholder="Job title, company, or keywords">
                </div>
                <div>
                    <label for="location" class="block text-sm font-medium text-gray-700">Location</label>
                    <input type="text" name="location" id="location"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                           placeholder="City or region">
                </div>
                <div>
                    <label for="job_type" class="block text-sm font-medium text-gray-700">Job Type</label>
                    <select name="job_type" id="job_type"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Types</option>
                        <option value="full-time">Full Time</option>
                        <option value="part-time">Part Time</option>
                        <option value="contract">Contract</option>
                        <option value="internship">Internship</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit"
                            class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Search Jobs
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Job Listings -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Available Jobs
            </h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">
                <?php echo mysqli_num_rows($jobs); ?> jobs found
            </p>
        </div>

        <div class="divide-y divide-gray-200">
            <?php while ($job = mysqli_fetch_assoc($jobs)): ?>
                <div class="p-4 hover:bg-gray-50 transition-colors duration-200">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex justify-between">
                                <h3 class="text-lg font-medium text-gray-900">
                                    <?php echo htmlspecialchars($job['title']); ?>
                                </h3>
                                <button onclick="toggleSaveJob(<?php echo $job['id']; ?>, <?php echo $job['is_saved']; ?>)"
                                        class="text-gray-400 hover:text-blue-500 save-job-btn"
                                        data-job-id="<?php echo $job['id']; ?>">
                                    <i class="fas fa-bookmark <?php echo $job['is_saved'] ? 'text-blue-500' : ''; ?>"></i>
                                </button>
                            </div>
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
                            <div class="mt-4 flex items-center justify-between">
                                <div class="flex items-center text-sm text-gray-500">
                                    <i class="fas fa-calendar mr-1"></i>
                                    Posted <?php echo date('M d, Y', strtotime($job['posted_date'])); ?>
                                    <span class="mx-2">•</span>
                                    <i class="fas fa-users mr-1"></i>
                                    <?php echo $job['application_count']; ?> applicants
                                </div>
                                <a href="view_job.php?id=<?php echo $job['id']; ?>"
                                   class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>

            <?php if (mysqli_num_rows($jobs) == 0): ?>
                <div class="p-4 text-center text-gray-500">
                    No jobs found.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleSaveJob(jobId, isSaved) {
    fetch('search_jobs.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=toggle_save&job_id=${jobId}&is_saved=${isSaved}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const btn = document.querySelector(`button[data-job-id="${jobId}"] i`);
            btn.classList.toggle('text-blue-500');
        }
    });
}
</script>

<?php mysqli_close($conn); ?> 