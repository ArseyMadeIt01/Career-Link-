<?php
$pageTitle = "Dashboard";
$currentPage = "dashboard";
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

// Get user profile information
$profile_query = "SELECT * FROM jobseeker_profiles WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $profile_query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$profile = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Get recent job applications
$applications_query = "SELECT ja.*, j.title, j.location, j.company_name
                      FROM job_applications ja 
                      JOIN jobs j ON ja.job_id = j.id 
                      WHERE ja.jobseeker_id = ? 
                      ORDER BY ja.application_date DESC LIMIT 5";
$stmt = mysqli_prepare($conn, $applications_query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$applications = mysqli_stmt_get_result($stmt);

// Get saved jobs count
$saved_query = "SELECT COUNT(*) as count FROM saved_jobs WHERE jobseeker_id = ?";
$stmt = mysqli_prepare($conn, $saved_query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$saved_count = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['count'];

// Get total applications count
$total_applications_query = "SELECT COUNT(*) as count FROM job_applications WHERE jobseeker_id = ?";
$stmt = mysqli_prepare($conn, $total_applications_query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$total_applications = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['count'];

// Calculate profile completion percentage
$profile_fields = ['first_name', 'last_name', 'email', 'phone', 'resume_path', 'skills', 'experience', 'education'];
$filled_fields = 0;
foreach ($profile_fields as $field) {
    if (!empty($profile[$field])) {
        $filled_fields++;
    }
}
$profile_completion = ($filled_fields / count($profile_fields)) * 100;
?>

<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <!-- Welcome Banner -->
    <div class="bg-white shadow-sm rounded-lg mb-6">
        <div class="px-4 py-5 sm:p-6">
            <div class="md:flex md:items-center md:justify-between">
                <div class="flex-1 min-w-0">
                    <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                        Welcome back, <?php echo htmlspecialchars($profile['first_name'] ?? $_SESSION['username']); ?>!
                    </h2>
                    <div class="mt-1 flex flex-col sm:flex-row sm:flex-wrap sm:mt-0 sm:space-x-6">
                        <div class="mt-2 flex items-center text-sm text-gray-500">
                            <i class="fas fa-briefcase mr-1.5"></i>
                            <?php echo $total_applications; ?> Applications Submitted
                        </div>
                        <div class="mt-2 flex items-center text-sm text-gray-500">
                            <i class="fas fa-bookmark mr-1.5"></i>
                            <?php echo $saved_count; ?> Jobs Saved
                        </div>
                    </div>
                </div>
                <div class="mt-5 flex lg:mt-0 lg:ml-4">
                    <a href="search_jobs.php" 
                       class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                        <i class="fas fa-search mr-2"></i>
                        Find Jobs
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Completion Card -->
    <?php if ($profile_completion < 100): ?>
    <div class="bg-white shadow-sm rounded-lg mb-6">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Complete Your Profile</h3>
            <div class="mt-2 max-w-xl text-sm text-gray-500">
                <p>Complete your profile to increase your chances of getting hired.</p>
            </div>
            <div class="mt-4">
                <div class="relative">
                    <div class="overflow-hidden h-2 text-xs flex rounded bg-gray-200">
                        <div style="width:<?php echo $profile_completion; ?>%" 
                             class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-blue-500">
                        </div>
                    </div>
                </div>
                <p class="mt-2 text-sm text-gray-600"><?php echo round($profile_completion); ?>% Complete</p>
            </div>
            <div class="mt-4">
                <a href="edit_profile.php" 
                   class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Complete Profile
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Recent Applications -->
        <div class="bg-white shadow-sm rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Applications</h3>
                    <a href="my_applications.php" class="text-sm text-blue-600 hover:text-blue-800">View All</a>
                </div>
                <div class="space-y-4">
                    <?php while ($application = mysqli_fetch_assoc($applications)): ?>
                        <div class="border rounded-lg p-4">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($application['title']); ?>
                                    </h4>
                                    <p class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($application['company_name']); ?>
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Applied: <?php echo date('M d, Y', strtotime($application['application_date'])); ?>
                                    </p>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    <?php echo $application['status'] == 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                            ($application['status'] == 'reviewed' ? 'bg-blue-100 text-blue-800' : 
                                            ($application['status'] == 'shortlisted' ? 'bg-green-100 text-green-800' : 
                                            'bg-red-100 text-red-800')); ?>">
                                    <?php echo ucfirst($application['status']); ?>
                                </span>
                            </div>
                        </div>
                    <?php endwhile; ?>

                    <?php if (mysqli_num_rows($applications) == 0): ?>
                        <p class="text-gray-500 text-sm text-center py-4">
                            No applications yet. Start your job search today.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="bg-white shadow-sm rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Quick Actions</h3>
                <div class="grid grid-cols-2 gap-4">
                    <a href="upload_resume.php" 
                       class="p-4 border rounded-lg hover:bg-gray-50 flex flex-col items-center justify-center">
                        <i class="fas fa-file-upload text-2xl text-blue-500 mb-2"></i>
                        <span class="text-sm font-medium text-gray-900">Update Resume</span>
                    </a>
                    <a href="saved_jobs.php" 
                       class="p-4 border rounded-lg hover:bg-gray-50 flex flex-col items-center justify-center">
                        <i class="fas fa-bookmark text-2xl text-blue-500 mb-2"></i>
                        <span class="text-sm font-medium text-gray-900">Saved Jobs</span>
                    </a>
                    <a href="edit_profile.php" 
                       class="p-4 border rounded-lg hover:bg-gray-50 flex flex-col items-center justify-center">
                        <i class="fas fa-user-edit text-2xl text-blue-500 mb-2"></i>
                        <span class="text-sm font-medium text-gray-900">Edit Profile</span>
                    </a>
                    <button onclick="showProfileModal()" 
                            class="p-4 border rounded-lg hover:bg-gray-50 flex flex-col items-center justify-center">
                        <i class="fas fa-user text-2xl text-blue-500 mb-2"></i>
                        <span class="text-sm font-medium text-gray-900">View Profile</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Profile Modal -->
<div id="profileModal" class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="px-4 py-5 sm:px-6 flex justify-between items-center border-b border-gray-200">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Profile Details</h3>
            <button onclick="closeProfileModal()" class="text-gray-400 hover:text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="px-4 py-5 sm:p-6">
            <dl class="grid grid-cols-1 gap-x-4 gap-y-8 sm:grid-cols-2">
                <!-- Personal Information -->
                <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-gray-500">Personal Information</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <p class="text-xs text-gray-500">Full Name</p>
                                    <p class="font-medium">
                                        <?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Email</p>
                                    <p class="font-medium"><?php echo htmlspecialchars($profile['email']); ?></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Phone</p>
                                    <p class="font-medium"><?php echo htmlspecialchars($profile['phone'] ?? 'Not provided'); ?></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Location</p>
                                    <p class="font-medium"><?php echo htmlspecialchars($profile['location'] ?? 'Not provided'); ?></p>
                                </div>
                            </div>
                        </div>
                    </dd>
                </div>

                <!-- Resume -->
                <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-gray-500">Resume</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <?php if ($profile['resume_path']): ?>
                            <div class="flex items-center justify-between bg-gray-50 rounded-lg p-4">
                                <div class="flex items-center">
                                    <i class="fas fa-file-pdf text-red-500 text-2xl mr-3"></i>
                                    <div>
                                        <p class="font-medium">Current Resume</p>
                                        <p class="text-xs text-gray-500">
                                            Uploaded: <?php echo date("M d, Y", filemtime($profile['resume_path'])); ?>
                                        </p>
                                    </div>
                                </div>
                                <a href="<?php echo htmlspecialchars($profile['resume_path']); ?>" 
                                   class="text-blue-600 hover:text-blue-800" target="_blank">
                                    <i class="fas fa-download mr-1"></i> Download
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="bg-gray-50 rounded-lg p-4 text-center">
                                <p class="text-gray-500">No resume uploaded yet</p>
                                <a href="upload_resume.php" class="text-blue-600 hover:text-blue-800 text-sm">
                                    Upload Resume
                                </a>
                            </div>
                        <?php endif; ?>
                    </dd>
                </div>

                <!-- Skills -->
                <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-gray-500">Skills</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <?php if ($profile['skills']): ?>
                                <?php foreach (explode(',', $profile['skills']) as $skill): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mr-2 mb-2">
                                        <?php echo htmlspecialchars(trim($skill)); ?>
                                    </span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-gray-500">No skills listed</p>
                            <?php endif; ?>
                        </div>
                    </dd>
                </div>

                <!-- Experience -->
                <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-gray-500">Work Experience</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <?php if ($profile['experience']): ?>
                                <?php echo nl2br(htmlspecialchars($profile['experience'])); ?>
                            <?php else: ?>
                                <p class="text-gray-500">No experience listed</p>
                            <?php endif; ?>
                        </div>
                    </dd>
                </div>

                <!-- Education -->
                <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-gray-500">Education</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <?php if ($profile['education']): ?>
                                <?php echo nl2br(htmlspecialchars($profile['education'])); ?>
                            <?php else: ?>
                                <p class="text-gray-500">No education listed</p>
                            <?php endif; ?>
                        </div>
                    </dd>
                </div>
            </dl>

            <div class="mt-6 flex justify-end">
                <a href="edit_profile.php" 
                   class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    <i class="fas fa-edit mr-2"></i>
                    Edit Profile
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Add this to your existing JavaScript section -->
<script>
function showProfileModal() {
    const modal = document.getElementById('profileModal');
    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
}

function closeProfileModal() {
    const modal = document.getElementById('profileModal');
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
}

// Close modal when clicking outside
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('profileModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeProfileModal();
            }
        });
    }
});
</script>

<?php mysqli_close($conn); ?> 