<?php
$pageTitle = "Upload Resume";
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

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['resume'])) {
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
            // Update database
            $query = "UPDATE jobseeker_profiles SET resume_path = ? WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "si", $upload_path, $_SESSION['user_id']);
            
            if (mysqli_stmt_execute($stmt)) {
                $success = "Resume uploaded successfully!";
            } else {
                $error = "Error updating database. Please try again.";
                // Delete uploaded file if database update fails
                unlink($upload_path);
            }
        } else {
            $error = "Error uploading file. Please try again.";
        }
    }
}

// Get current resume info
$query = "SELECT resume_path FROM jobseeker_profiles WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$profile = mysqli_fetch_assoc($result);
?>

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

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Resume Upload</h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">
                Upload your resume in PDF or Word format (max 5MB)
            </p>
        </div>

        <div class="border-t border-gray-200 px-4 py-5 sm:p-6">
            <?php if ($profile && $profile['resume_path']): ?>
                <!-- Current Resume Section -->
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
                        <a href="<?php echo htmlspecialchars($profile['resume_path']); ?>" 
                           class="text-blue-600 hover:text-blue-800" target="_blank">
                            <i class="fas fa-download mr-1"></i> Download
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Upload Form -->
            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <div class="flex items-center justify-center w-full">
                    <label class="flex flex-col items-center justify-center w-full h-64 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100">
                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                            <i class="fas fa-cloud-upload-alt mb-3 text-gray-400 text-3xl"></i>
                            <p class="mb-2 text-sm text-gray-500">
                                <span class="font-semibold">Click to upload</span> or drag and drop
                            </p>
                            <p class="text-xs text-gray-500">PDF or Word (MAX. 5MB)</p>
                        </div>
                        <input type="file" name="resume" class="hidden" accept=".pdf,.doc,.docx" required>
                    </label>
                </div>

                <div class="flex justify-end space-x-3">
                    <button type="submit" 
                            class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Upload Resume
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Preview filename when selected
document.querySelector('input[type="file"]').addEventListener('change', function(e) {
    const fileName = e.target.files[0].name;
    const fileSize = (e.target.files[0].size / 1024 / 1024).toFixed(2);
    const fileInfo = `Selected: ${fileName} (${fileSize}MB)`;
    
    const infoElement = document.createElement('p');
    infoElement.className = 'text-sm text-gray-600 mt-2 text-center';
    infoElement.textContent = fileInfo;
    
    // Remove any existing file info
    const existingInfo = document.querySelector('.file-info');
    if (existingInfo) existingInfo.remove();
    
    // Add new file info
    e.target.parentElement.appendChild(infoElement);
    infoElement.classList.add('file-info');
});
</script>

<?php mysqli_close($conn); ?> 