<?php
session_start();

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

$applicant_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;

// Verify this application belongs to one of the employer's jobs
$verify_query = "SELECT ja.*, j.title as job_title, j.employer_id
                 FROM job_applications ja
                 JOIN jobs j ON ja.job_id = j.id
                 WHERE ja.jobseeker_id = ? AND j.id = ? AND j.employer_id = ?";
$stmt = mysqli_prepare($conn, $verify_query);
mysqli_stmt_bind_param($stmt, "iii", $applicant_id, $job_id, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$application = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$application) {
    header("Location: manage_jobs.php");
    exit();
}

// Get detailed applicant information
$profile_query = "SELECT u.username, u.email, jp.*, 
                  (SELECT COUNT(*) FROM job_applications 
                   WHERE jobseeker_id = u.id) as total_applications
                 FROM users u
                 LEFT JOIN jobseeker_profiles jp ON u.id = jp.user_id
                 WHERE u.id = ?";
$stmt = mysqli_prepare($conn, $profile_query);
mysqli_stmt_bind_param($stmt, "i", $applicant_id);
mysqli_stmt_execute($stmt);
$profile = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Get applicant's other applications to your jobs
$other_applications_query = "SELECT ja.*, j.title as job_title, j.location
                           FROM job_applications ja
                           JOIN jobs j ON ja.job_id = j.id
                           WHERE ja.jobseeker_id = ? AND j.employer_id = ?
                           AND ja.job_id != ?
                           ORDER BY ja.application_date DESC";
$stmt = mysqli_prepare($conn, $other_applications_query);
mysqli_stmt_bind_param($stmt, "iii", $applicant_id, $_SESSION['user_id'], $job_id);
mysqli_stmt_execute($stmt);
$other_applications = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicant Profile - Career Link</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg border-b border-gray-200">
        <!-- ... (same navigation code as other pages) ... -->
    </nav>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Back Button -->
        <div class="mb-6">
            <a href="view_applications.php?job_id=<?php echo $job_id; ?>" 
               class="inline-flex items-center text-gray-600 hover:text-gray-800">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Applications
            </a>
        </div>

        <!-- Profile Header -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
            <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
                <div>
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        <?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?>
                    </h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">
                        Applied for: <?php echo htmlspecialchars($application['job_title']); ?>
                    </p>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="px-3 py-1 rounded-full text-sm font-medium
                        <?php echo $application['status'] == 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                ($application['status'] == 'reviewed' ? 'bg-blue-100 text-blue-800' : 
                                ($application['status'] == 'shortlisted' ? 'bg-green-100 text-green-800' : 
                                'bg-red-100 text-red-800')); ?>">
                        <?php echo ucfirst($application['status']); ?>
                    </span>
                    <?php if ($profile['resume_path']): ?>
                        <a href="<?php echo htmlspecialchars($profile['resume_path']); ?>" 
                           class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700"
                           target="_blank">
                            <i class="fas fa-download mr-2"></i>
                            Download Resume
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
                <dl class="grid grid-cols-1 gap-x-4 gap-y-8 sm:grid-cols-2">
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Email</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($profile['email']); ?></dd>
                    </div>
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Phone</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($profile['phone'] ?? 'Not provided'); ?></dd>
                    </div>
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Location</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($profile['location'] ?? 'Not provided'); ?></dd>
                    </div>
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Total Applications</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo $profile['total_applications']; ?></dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Skills and Experience -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Skills & Experience</h3>
            </div>
            <div class="border-t border-gray-200">
                <dl>
                    <?php if ($profile['skills']): ?>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Skills</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <?php 
                            $skills = explode(',', $profile['skills']);
                            foreach ($skills as $skill): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mr-2 mb-2">
                                    <?php echo htmlspecialchars(trim($skill)); ?>
                                </span>
                            <?php endforeach; ?>
                        </dd>
                    </div>
                    <?php endif; ?>

                    <?php if ($profile['experience']): ?>
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Experience</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <?php echo nl2br(htmlspecialchars($profile['experience'])); ?>
                        </dd>
                    </div>
                    <?php endif; ?>

                    <?php if ($profile['education']): ?>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Education</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <?php echo nl2br(htmlspecialchars($profile['education'])); ?>
                        </dd>
                    </div>
                    <?php endif; ?>
                </dl>
            </div>
        </div>

        <!-- Cover Letter -->
        <?php if ($application['cover_letter']): ?>
        <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Cover Letter</h3>
            </div>
            <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
                <p class="text-sm text-gray-900 whitespace-pre-line">
                    <?php echo nl2br(htmlspecialchars($application['cover_letter'])); ?>
                </p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Other Applications -->
        <?php if (mysqli_num_rows($other_applications) > 0): ?>
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Other Applications</h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">
                    Other positions this candidate has applied for at your company
                </p>
            </div>
            <div class="border-t border-gray-200">
                <ul class="divide-y divide-gray-200">
                    <?php while ($other = mysqli_fetch_assoc($other_applications)): ?>
                    <li class="px-4 py-4">
                        <div class="flex justify-between items-center">
                            <div>
                                <h4 class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($other['job_title']); ?>
                                </h4>
                                <p class="text-sm text-gray-500">
                                    Applied: <?php echo date('M d, Y', strtotime($other['application_date'])); ?>
                                </p>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium
                                <?php echo $other['status'] == 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                        ($other['status'] == 'reviewed' ? 'bg-blue-100 text-blue-800' : 
                                        ($other['status'] == 'shortlisted' ? 'bg-green-100 text-green-800' : 
                                        'bg-red-100 text-red-800')); ?>">
                                <?php echo ucfirst($other['status']); ?>
                            </span>
                        </div>
                    </li>
                    <?php endwhile; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html> 