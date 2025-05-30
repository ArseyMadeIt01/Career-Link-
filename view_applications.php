<?php
session_start();
$pageTitle = "View Applications";
$currentPage = "view_applications";
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

$job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;

// Verify the job belongs to this employer
$job_query = "SELECT * FROM jobs WHERE id = ? AND employer_id = ?";
$stmt = mysqli_prepare($conn, $job_query);
mysqli_stmt_bind_param($stmt, "ii", $job_id, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$job_result = mysqli_stmt_get_result($stmt);
$job = mysqli_fetch_assoc($job_result);

if (!$job) {
    header("Location: manage_jobs.php");
    exit();
}

// Handle application status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $application_id = (int)$_POST['application_id'];
    $new_status = mysqli_real_escape_string($conn, $_POST['status']);
    
    $update_query = "UPDATE job_applications SET status = ? WHERE id = ? AND job_id = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "sii", $new_status, $application_id, $job_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $success = "Application status updated successfully.";
    } else {
        $error = "Error updating application status.";
    }
}

// Get applications for this job
$applications_query = "SELECT ja.*, js.first_name, js.last_name, js.email, js.phone,
                      js.resume_path, u.username
                      FROM job_applications ja
                      JOIN jobseeker_profiles js ON ja.jobseeker_id = js.user_id
                      JOIN users u ON ja.jobseeker_id = u.id
                      WHERE ja.job_id = ?
                      ORDER BY ja.application_date DESC";
$stmt = mysqli_prepare($conn, $applications_query);
mysqli_stmt_bind_param($stmt, "i", $job_id);
mysqli_stmt_execute($stmt);
$applications = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Applications - Career Link</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Navigation (same as employer_dashboard.php) -->
    <nav class="bg-white shadow-lg border-b border-gray-200">
        <!-- ... (same navigation code) ... -->
    </nav>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Job Details Header -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Applications for: <?php echo htmlspecialchars($job['title']); ?>
                </h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">
                    Posted: <?php echo date('M d, Y', strtotime($job['posted_date'])); ?>
                </p>
            </div>
            <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
                <dl class="grid grid-cols-1 gap-x-4 gap-y-8 sm:grid-cols-2">
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Location</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($job['location']); ?></dd>
                    </div>
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Job Type</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo ucfirst($job['job_type']); ?></dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Applications List -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Applications (<?php echo mysqli_num_rows($applications); ?>)
                </h3>
            </div>
            
            <?php if (mysqli_num_rows($applications) > 0): ?>
                <div class="divide-y divide-gray-200">
                    <?php while ($application = mysqli_fetch_assoc($applications)): ?>
                        <div class="p-6">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <h4 class="text-lg font-medium text-gray-900">
                                        <?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?>
                                    </h4>
                                    <div class="mt-2 flex flex-col sm:flex-row sm:flex-wrap sm:space-x-6">
                                        <div class="mt-2 flex items-center text-sm text-gray-500">
                                            <i class="fas fa-envelope mr-1.5"></i>
                                            <?php echo htmlspecialchars($application['email']); ?>
                                        </div>
                                        <?php if ($application['phone']): ?>
                                            <div class="mt-2 flex items-center text-sm text-gray-500">
                                                <i class="fas fa-phone mr-1.5"></i>
                                                <?php echo htmlspecialchars($application['phone']); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="mt-2 flex items-center text-sm text-gray-500">
                                            <i class="fas fa-calendar mr-1.5"></i>
                                            Applied: <?php echo date('M d, Y', strtotime($application['application_date'])); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-4">
                                    <?php if ($application['resume_path']): ?>
                                        <a href="<?php echo htmlspecialchars($application['resume_path']); ?>" 
                                           class="text-blue-600 hover:text-blue-800" target="_blank">
                                            <i class="fas fa-file-pdf mr-1"></i> View Resume
                                        </a>
                                    <?php endif; ?>
                                    
                                    <form method="POST" class="inline-block">
                                        <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                                        <input type="hidden" name="update_status" value="1">
                                        <select name="status" onchange="this.form.submit()"
                                            class="rounded-md border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                                            <option value="pending" <?php echo $application['status'] == 'pending' ? 'selected' : ''; ?>>
                                                Pending
                                            </option>
                                            <option value="reviewed" <?php echo $application['status'] == 'reviewed' ? 'selected' : ''; ?>>
                                                Reviewed
                                            </option>
                                            <option value="shortlisted" <?php echo $application['status'] == 'shortlisted' ? 'selected' : ''; ?>>
                                                Shortlisted
                                            </option>
                                            <option value="rejected" <?php echo $application['status'] == 'rejected' ? 'selected' : ''; ?>>
                                                Rejected
                                            </option>
                                        </select>
                                    </form>
                                </div>
                            </div>
                            
                            <?php if ($application['cover_letter']): ?>
                                <div class="mt-4 text-sm text-gray-700">
                                    <h5 class="font-medium mb-2">Cover Letter:</h5>
                                    <p class="whitespace-pre-line"><?php echo nl2br(htmlspecialchars($application['cover_letter'])); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="p-6 text-center text-gray-500">
                    No applications received yet.
                </div>
            <?php endif; ?>
        </div>

        <!-- Back Button -->
        <div class="mt-6">
            <a href="manage_jobs.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Job Listings
            </a>
        </div>
    </div>
</body>
</html> 