<?php
$pageTitle = "Edit Profile";
$currentPage = "profile";
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

// First, check if profile exists
$check_profile = "SELECT COUNT(*) as count FROM jobseeker_profiles WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $check_profile);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$profile_exists = mysqli_fetch_assoc($result)['count'] > 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $skills = mysqli_real_escape_string($conn, $_POST['skills']);
    $experience = mysqli_real_escape_string($conn, $_POST['experience']);
    $education = mysqli_real_escape_string($conn, $_POST['education']);

    if ($profile_exists) {
        // Update existing profile
        $query = "UPDATE jobseeker_profiles SET 
                 first_name = ?, 
                 last_name = ?, 
                 email = ?, 
                 phone = ?,
                 location = ?, 
                 skills = ?, 
                 experience = ?, 
                 education = ?
                 WHERE user_id = ?";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssssssssi", 
            $first_name, $last_name, $email, $phone, 
            $location, $skills, $experience, $education, 
            $_SESSION['user_id']
        );
    } else {
        // Insert new profile
        $query = "INSERT INTO jobseeker_profiles 
                 (user_id, first_name, last_name, email, phone, location, skills, experience, education)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "issssssss", 
            $_SESSION['user_id'], $first_name, $last_name, $email, $phone, 
            $location, $skills, $experience, $education
        );
    }

    if (mysqli_stmt_execute($stmt)) {
        // Also update email in users table
        $update_user = "UPDATE users SET email = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_user);
        mysqli_stmt_bind_param($stmt, "si", $email, $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);
        
        $success = "Profile updated successfully!";
    } else {
        $error = "Error updating profile: " . mysqli_error($conn);
    }
}

// Add this after the existing POST handling in edit_profile.php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['resume']) && $_FILES['resume']['error'] != UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['resume'];
    $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    $max_size = 5 * 1024 * 1024; // 5MB

    if (!in_array($file['type'], $allowed_types)) {
        $error = "Only PDF and Word documents are allowed.";
    } elseif ($file['size'] > $max_size) {
        $error = "File size must be less than 5MB.";
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $error = "Upload failed. Please try again.";
    } else {
        // Create uploads directory if it doesn't exist
        $upload_dir = 'uploads/resumes';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Generate unique filename
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_filename = 'resume_' . $_SESSION['user_id'] . '_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . '/' . $new_filename;

        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            // Update resume path in database
            $update_resume_query = "UPDATE jobseeker_profiles SET resume_path = ? WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $update_resume_query);
            mysqli_stmt_bind_param($stmt, "si", $upload_path, $_SESSION['user_id']);
            
            if (!mysqli_stmt_execute($stmt)) {
                $error = "Error updating resume path in database.";
                // Delete uploaded file if database update fails
                unlink($upload_path);
            } else {
                $success = "Profile and resume updated successfully!";
                
                // Refresh profile data with a new query
                $refresh_query = "SELECT jp.*, u.email as user_email 
                                FROM jobseeker_profiles jp 
                                RIGHT JOIN users u ON jp.user_id = u.id 
                                WHERE u.id = ?";
                $stmt = mysqli_prepare($conn, $refresh_query);
                mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
                mysqli_stmt_execute($stmt);
                $profile = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            }
        } else {
            $error = "Error uploading file. Please try again.";
        }
    }
}

// Get current profile data
$query = "SELECT jp.*, u.email as user_email 
          FROM jobseeker_profiles jp 
          RIGHT JOIN users u ON jp.user_id = u.id 
          WHERE u.id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$profile = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
?>

<!-- Add this JavaScript at the top of the HTML -->
<script>
let originalFormData = {};

function checkFormChanges() {
    const form = document.getElementById('profileForm');
    const submitButton = document.getElementById('submitButton');
    let hasChanges = false;

    // Check each form element
    form.querySelectorAll('input, textarea').forEach(element => {
        if (element.value !== originalFormData[element.name]) {
            hasChanges = true;
        }
    });

    // Enable/disable submit button based on changes
    submitButton.disabled = !hasChanges;
    submitButton.classList.toggle('opacity-50', !hasChanges);
    submitButton.classList.toggle('cursor-not-allowed', !hasChanges);
}

// Store original form data when page loads
window.onload = function() {
    const form = document.getElementById('profileForm');
    form.querySelectorAll('input, textarea').forEach(element => {
        originalFormData[element.name] = element.value;
        element.addEventListener('input', checkFormChanges);
    });
    checkFormChanges();
};
</script>

<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <!-- Back Button -->
    <div class="mb-6">
        <a href="jobseeker_dashboard.php" class="inline-flex items-center text-gray-600 hover:text-gray-800">
            <i class="fas fa-arrow-left mr-2"></i>
            Back to Dashboard
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

    <!-- Profile Form -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Profile Information</h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">
                Update your personal information and career details.
            </p>
        </div>
        <div class="border-t border-gray-200">
            <form method="POST" id="profileForm" class="divide-y divide-gray-200" enctype="multipart/form-data">
                <!-- Personal Information -->
                <div class="px-4 py-5 sm:p-6">
                    <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-gray-700">First Name</label>
                            <input type="text" name="first_name" id="first_name" required
                                value="<?php echo htmlspecialchars($profile['first_name'] ?? ''); ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label for="last_name" class="block text-sm font-medium text-gray-700">Last Name</label>
                            <input type="text" name="last_name" id="last_name" required
                                value="<?php echo htmlspecialchars($profile['last_name'] ?? ''); ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" name="email" id="email" required
                                value="<?php echo htmlspecialchars($profile['user_email'] ?? ''); ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                            <input type="tel" name="phone" id="phone"
                                value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div class="sm:col-span-2">
                            <label for="location" class="block text-sm font-medium text-gray-700">Location</label>
                            <input type="text" name="location" id="location"
                                value="<?php echo htmlspecialchars($profile['location'] ?? ''); ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="City, Country">
                        </div>
                    </div>
                </div>

                <!-- Resume Upload Section -->
                <div class="px-4 py-5 sm:p-6">
                    <h4 class="text-lg font-medium text-gray-900 mb-4">Resume</h4>
                    <?php if ($profile && $profile['resume_path']): ?>
                        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <i class="fas fa-file-pdf text-red-500 text-2xl mr-3"></i>
                                    <div>
                                        <h3 class="text-sm font-medium text-gray-900">Current Resume</h3>
                                        <p class="text-sm text-gray-500">
                                            Uploaded: <?php echo date("M d, Y", filemtime($profile['resume_path'])); ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex space-x-3">
                                    <a href="<?php echo htmlspecialchars($profile['resume_path']); ?>" 
                                       class="text-blue-600 hover:text-blue-800" target="_blank">
                                        <i class="fas fa-download mr-1"></i> Download
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700">Update Resume</label>
                        <div class="mt-2 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg">
                            <div class="space-y-1 text-center">
                                <i class="fas fa-cloud-upload-alt text-gray-400 text-3xl mb-3"></i>
                                <div class="flex text-sm text-gray-600">
                                    <label for="resume" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                        <span>Upload a file</span>
                                        <input id="resume" name="resume" type="file" class="sr-only" accept=".pdf,.doc,.docx">
                                    </label>
                                    <p class="pl-1">or drag and drop</p>
                                </div>
                                <p class="text-xs text-gray-500">PDF or Word up to 5MB</p>
                            </div>
                        </div>
                        <div id="fileInfo" class="mt-2 text-sm text-gray-500"></div>
                    </div>
                </div>

                <!-- Professional Information -->
                <div class="px-4 py-5 sm:p-6">
                    <div class="space-y-6">
                        <div>
                            <label for="skills" class="block text-sm font-medium text-gray-700">
                                Skills
                                <span class="text-sm text-gray-500">(separate with commas)</span>
                            </label>
                            <textarea name="skills" id="skills" rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="e.g., JavaScript, Python, Project Management"
                            ><?php echo htmlspecialchars($profile['skills'] ?? ''); ?></textarea>
                        </div>

                        <div>
                            <label for="experience" class="block text-sm font-medium text-gray-700">Work Experience</label>
                            <textarea name="experience" id="experience" rows="4"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Describe your work experience..."
                            ><?php echo htmlspecialchars($profile['experience'] ?? ''); ?></textarea>
                        </div>

                        <div>
                            <label for="education" class="block text-sm font-medium text-gray-700">Education</label>
                            <textarea name="education" id="education" rows="4"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="List your educational background..."
                            ><?php echo htmlspecialchars($profile['education'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="px-4 py-3 bg-gray-50 text-right sm:px-6">
                    <button type="submit" id="submitButton"
                        class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// File upload preview
document.getElementById('resume').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const fileSize = (file.size / 1024 / 1024).toFixed(2);
        document.getElementById('fileInfo').textContent = `Selected: ${file.name} (${fileSize}MB)`;
        checkFormChanges(); // Check form changes when file is selected
    }
});

// Drag and drop functionality
const dropZone = document.querySelector('.border-dashed');
['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, preventDefaults, false);
});

function preventDefaults (e) {
    e.preventDefault();
    e.stopPropagation();
}

['dragenter', 'dragover'].forEach(eventName => {
    dropZone.addEventListener(eventName, highlight, false);
});

['dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, unhighlight, false);
});

function highlight(e) {
    dropZone.classList.add('border-blue-500', 'bg-blue-50');
}

function unhighlight(e) {
    dropZone.classList.remove('border-blue-500', 'bg-blue-50');
}

dropZone.addEventListener('drop', handleDrop, false);

function handleDrop(e) {
    const dt = e.dataTransfer;
    const file = dt.files[0];
    const fileInput = document.getElementById('resume');
    
    fileInput.files = dt.files;
    if (file) {
        const fileSize = (file.size / 1024 / 1024).toFixed(2);
        document.getElementById('fileInfo').textContent = `Selected: ${file.name} (${fileSize}MB)`;
        checkFormChanges(); // Check form changes when file is dropped
    }
}
</script>

<?php mysqli_close($conn); ?> 