<?php
session_start();
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

$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verify the job belongs to this employer
$query = "SELECT * FROM jobs WHERE id = ? AND employer_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $job_id, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$job = mysqli_fetch_assoc($result);

if (!$job) {
    header("Location: employer_dashboard.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $requirements = mysqli_real_escape_string($conn, $_POST['requirements']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $job_type = mysqli_real_escape_string($conn, $_POST['job_type']);
    $salary_range = mysqli_real_escape_string($conn, $_POST['salary_range']);
    $deadline_date = mysqli_real_escape_string($conn, $_POST['deadline_date']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    $update_query = "UPDATE jobs SET 
                    title = ?, description = ?, requirements = ?, 
                    location = ?, job_type = ?, salary_range = ?,
                    deadline_date = ?, status = ?
                    WHERE id = ? AND employer_id = ?";
    
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "ssssssssii", 
        $title, $description, $requirements, $location, 
        $job_type, $salary_range, $deadline_date, $status, 
        $job_id, $_SESSION['user_id']);
    
    if (mysqli_stmt_execute($stmt)) {
        header("Location: manage_jobs.php?success=1");
        exit();
    } else {
        $error = "Error updating job. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Job - Career Link</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Navigation (same as employer_dashboard.php) -->
    <nav class="bg-white shadow-lg border-b border-gray-200">
        <!-- ... (same navigation code as post_job.php) ... -->
    </nav>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow px-5 py-6 sm:px-6">
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900">Edit Job Posting</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Update the job posting information below.
                </p>
            </div>

            <?php if (isset($error)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <!-- Job Title -->
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700">Job Title</label>
                    <input type="text" name="title" id="title" required
                        value="<?php echo htmlspecialchars($job['title']); ?>"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <!-- Job Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Job Description</label>
                    <textarea name="description" id="description" rows="4" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    ><?php echo htmlspecialchars($job['description']); ?></textarea>
                </div>

                <!-- Requirements -->
                <div>
                    <label for="requirements" class="block text-sm font-medium text-gray-700">Requirements</label>
                    <textarea name="requirements" id="requirements" rows="4" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    ><?php echo htmlspecialchars($job['requirements']); ?></textarea>
                </div>

                <!-- Location -->
                <div>
                    <label for="location" class="block text-sm font-medium text-gray-700">Location</label>
                    <input type="text" name="location" id="location" required
                        value="<?php echo htmlspecialchars($job['location']); ?>"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <!-- Job Type -->
                <div>
                    <label for="job_type" class="block text-sm font-medium text-gray-700">Job Type</label>
                    <select name="job_type" id="job_type" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="full-time" <?php echo $job['job_type'] == 'full-time' ? 'selected' : ''; ?>>Full Time</option>
                        <option value="part-time" <?php echo $job['job_type'] == 'part-time' ? 'selected' : ''; ?>>Part Time</option>
                        <option value="contract" <?php echo $job['job_type'] == 'contract' ? 'selected' : ''; ?>>Contract</option>
                        <option value="internship" <?php echo $job['job_type'] == 'internship' ? 'selected' : ''; ?>>Internship</option>
                    </select>
                </div>

                <!-- Salary Range -->
                <div>
                    <label for="salary_range" class="block text-sm font-medium text-gray-700">Salary Range</label>
                    <input type="text" name="salary_range" id="salary_range"
                        value="<?php echo htmlspecialchars($job['salary_range']); ?>"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <!-- Application Deadline -->
                <div>
                    <label for="deadline_date" class="block text-sm font-medium text-gray-700">Application Deadline</label>
                    <input type="date" name="deadline_date" id="deadline_date" required
                        value="<?php echo htmlspecialchars($job['deadline_date']); ?>"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" id="status" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="active" <?php echo $job['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="closed" <?php echo $job['status'] == 'closed' ? 'selected' : ''; ?>>Closed</option>
                        <option value="draft" <?php echo $job['status'] == 'draft' ? 'selected' : ''; ?>>Draft</option>
                    </select>
                </div>

                <!-- Submit Buttons -->
                <div class="flex justify-end space-x-3">
                    <a href="manage_jobs.php" 
                        class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                        Cancel
                    </a>
                    <button type="submit"
                        class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Update Job
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Set minimum date for deadline
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('deadline_date').min = today;
        });
    </script>
</body>
</html> 