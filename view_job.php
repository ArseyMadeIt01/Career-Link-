<?php
session_start();

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

$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get job details with employer profile information
$query = "SELECT j.*, ep.company_name, ep.company_description, ep.industry, 
          ep.company_size, ep.website, ep.location as company_location, 
          ep.contact_email, ep.contact_phone,
          CASE WHEN sj.job_id IS NOT NULL THEN 1 ELSE 0 END as is_saved,
          CASE WHEN ja.id IS NOT NULL THEN 1 ELSE 0 END as has_applied,
          ja.status as application_status
          FROM jobs j
          LEFT JOIN employer_profiles ep ON j.employer_id = ep.user_id
          LEFT JOIN saved_jobs sj ON j.id = sj.job_id AND sj.jobseeker_id = ?
          LEFT JOIN job_applications ja ON j.id = ja.job_id AND ja.jobseeker_id = ?
          WHERE j.id = ? AND j.status = 'active'";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "iii", $_SESSION['user_id'], $_SESSION['user_id'], $job_id);
mysqli_stmt_execute($stmt);
$job = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$job) {
    $_SESSION['error'] = "Job not found or no longer active.";
    header("Location: search_jobs.php");
    exit();
}

// Now we can safely include the header and start output
$pageTitle = "View Job";
$currentPage = "search_jobs";
require_once 'includes/header.php';

// Handle job application submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['apply'])) {
    // Check if user has completed their profile
    $profile_query = "SELECT * FROM jobseeker_profiles WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $profile_query);
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $profile = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$profile || !$profile['resume_path']) {
        $error = "Please complete your profile and upload your resume before applying.";
    } else {
        $cover_letter = mysqli_real_escape_string($conn, $_POST['cover_letter']);
        
        $apply_query = "INSERT INTO job_applications (job_id, jobseeker_id, cover_letter, status) 
                       VALUES (?, ?, ?, 'pending')";
        $stmt = mysqli_prepare($conn, $apply_query);
        mysqli_stmt_bind_param($stmt, "iis", $job_id, $_SESSION['user_id'], $cover_letter);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = "Application submitted successfully!";
            $job['has_applied'] = true;
            $job['application_status'] = 'pending';
        } else {
            $error = "Error submitting application. Please try again.";
        }
    }
}
?>

<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <!-- Back Button -->
    <div class="mb-6">
        <a href="search_jobs.php" class="inline-flex items-center text-gray-600 hover:text-gray-800">
            <i class="fas fa-arrow-left mr-2"></i>
            Back to Search Results
        </a>
    </div>

    <?php if (isset($success)): ?>
        <div class="mb-6 bg-green-100 border-l-4 border-green-500 text-green-700 p-4" role="alert">
            <p><?php echo htmlspecialchars($success); ?></p>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="mb-6 bg-red-100 border-l-4 border-red-500 text-red-700 p-4" role="alert">
            <p><?php echo htmlspecialchars($error); ?></p>
        </div>
    <?php endif; ?>

    <!-- Company Information -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
        <div class="px-4 py-5 sm:px-6">
            <div class="flex justify-between items-start">
                <div>
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        <?php echo htmlspecialchars($job['company_name']); ?>
                    </h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">
                        <i class="fas fa-industry mr-2"></i>
                        <?php echo ucfirst($job['industry']); ?>
                        <span class="mx-2">•</span>
                        <i class="fas fa-users mr-2"></i>
                        <?php echo $job['company_size']; ?> employees
                    </p>
                </div>
                <?php if ($job['website']): ?>
                    <a href="<?php echo htmlspecialchars($job['website']); ?>" 
                       target="_blank"
                       class="text-blue-600 hover:text-blue-800">
                        <i class="fas fa-external-link-alt mr-1"></i>
                        Visit Website
                    </a>
                <?php endif; ?>
            </div>
            <?php if ($job['company_description']): ?>
                <div class="mt-4 text-sm text-gray-600">
                    <?php echo nl2br(htmlspecialchars($job['company_description'])); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Job Details -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6 flex justify-between items-start">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">
                    <?php echo htmlspecialchars($job['title']); ?>
                </h2>
                <div class="mt-2 flex flex-wrap gap-4">
                    <p class="text-sm text-gray-600">
                        <i class="fas fa-map-marker-alt mr-2"></i>
                        <?php echo htmlspecialchars($job['location']); ?>
                    </p>
                    <p class="text-sm text-gray-600">
                        <i class="fas fa-clock mr-2"></i>
                        <?php echo ucfirst($job['job_type']); ?>
                    </p>
                    <?php if ($job['salary_range']): ?>
                        <p class="text-sm text-gray-600">
                            <i class="fas fa-money-bill-wave mr-2"></i>
                            <?php echo htmlspecialchars($job['salary_range']); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <button onclick="toggleSaveJob(<?php echo $job['id']; ?>, <?php echo $job['is_saved']; ?>)"
                        class="inline-flex items-center text-gray-400 hover:text-blue-500">
                    <i class="fas fa-bookmark <?php echo $job['is_saved'] ? 'text-blue-500' : ''; ?> mr-2"></i>
                    <?php echo $job['is_saved'] ? 'Saved' : 'Save Job'; ?>
                </button>
                <?php if (!$job['has_applied']): ?>
                    <button onclick="showApplicationForm()"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                        <i class="fas fa-paper-plane mr-2"></i>
                        Apply Now
                    </button>
                <?php else: ?>
                    <span class="inline-flex items-center px-4 py-2 rounded-md text-sm font-medium text-white 
                        <?php echo $job['application_status'] == 'pending' ? 'bg-yellow-500' : 
                            ($job['application_status'] == 'reviewed' ? 'bg-blue-500' : 
                            ($job['application_status'] == 'shortlisted' ? 'bg-green-500' : 'bg-red-500')); ?>">
                        <i class="fas fa-check-circle mr-2"></i>
                        <?php echo ucfirst($job['application_status']); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
            <dl class="grid grid-cols-1 gap-x-4 gap-y-8">
                <div class="col-span-1">
                    <dt class="text-sm font-medium text-gray-500">Job Description</dt>
                    <dd class="mt-1 text-sm text-gray-900 whitespace-pre-line">
                        <?php echo nl2br(htmlspecialchars($job['description'])); ?>
                    </dd>
                </div>

                <div class="col-span-1">
                    <dt class="text-sm font-medium text-gray-500">Requirements</dt>
                    <dd class="mt-1 text-sm text-gray-900 whitespace-pre-line">
                        <?php echo nl2br(htmlspecialchars($job['requirements'])); ?>
                    </dd>
                </div>

                <div class="col-span-1">
                    <dt class="text-sm font-medium text-gray-500">Additional Information</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <ul class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <li>
                                <span class="font-medium">Posted:</span> 
                                <?php echo date('M d, Y', strtotime($job['posted_date'])); ?>
                            </li>
                            <li>
                                <span class="font-medium">Application Deadline:</span> 
                                <?php echo date('M d, Y', strtotime($job['deadline_date'])); ?>
                            </li>
                            <li>
                                <span class="font-medium">Contact Email:</span> 
                                <?php echo htmlspecialchars($job['contact_email']); ?>
                            </li>
                            <?php if ($job['contact_phone']): ?>
                                <li>
                                    <span class="font-medium">Contact Phone:</span> 
                                    <?php echo htmlspecialchars($job['contact_phone']); ?>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Application Form Modal -->
    <div id="applicationModal" class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Apply for <?php echo htmlspecialchars($job['title']); ?></h3>
            <form method="POST" class="space-y-6">
                <div>
                    <label for="cover_letter" class="block text-sm font-medium text-gray-700">Cover Letter</label>
                    <textarea name="cover_letter" id="cover_letter" rows="6" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Tell us why you're the perfect fit for this role..."></textarea>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="hideApplicationForm()"
                            class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" name="apply"
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                        Submit Application
                    </button>
                </div>
            </form>
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
            location.reload();
        }
    });
}

function showApplicationForm() {
    document.getElementById('applicationModal').classList.remove('hidden');
}

function hideApplicationForm() {
    document.getElementById('applicationModal').classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('applicationModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideApplicationForm();
    }
});
</script>

<?php mysqli_close($conn); ?> 