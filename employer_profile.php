<?php
$pageTitle = "Company Profile";
$currentPage = "profile";
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

// Check if profile exists
$check_profile = "SELECT COUNT(*) as count FROM employer_profiles WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $check_profile);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$profile_exists = mysqli_fetch_assoc($result)['count'] > 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $company_name = mysqli_real_escape_string($conn, $_POST['company_name']);
    $company_description = mysqli_real_escape_string($conn, $_POST['company_description']);
    $industry = mysqli_real_escape_string($conn, $_POST['industry']);
    $company_size = mysqli_real_escape_string($conn, $_POST['company_size']);
    $website = mysqli_real_escape_string($conn, $_POST['website']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $contact_email = mysqli_real_escape_string($conn, $_POST['contact_email']);
    $contact_phone = mysqli_real_escape_string($conn, $_POST['contact_phone']);

    if ($profile_exists) {
        // Update existing profile
        $query = "UPDATE employer_profiles SET 
                 company_name = ?, 
                 company_description = ?, 
                 industry = ?, 
                 company_size = ?,
                 website = ?, 
                 location = ?, 
                 contact_email = ?, 
                 contact_phone = ?
                 WHERE user_id = ?";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssssssssi", 
            $company_name, $company_description, $industry, $company_size,
            $website, $location, $contact_email, $contact_phone,
            $_SESSION['user_id']
        );
    } else {
        // Insert new profile
        $query = "INSERT INTO employer_profiles 
                 (user_id, company_name, company_description, industry, company_size,
                  website, location, contact_email, contact_phone)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "issssssss", 
            $_SESSION['user_id'], $company_name, $company_description, $industry,
            $company_size, $website, $location, $contact_email, $contact_phone
        );
    }

    if (mysqli_stmt_execute($stmt)) {
        $success = "Company profile updated successfully!";
    } else {
        $error = "Error updating profile: " . mysqli_error($conn);
    }
}

// Get current profile data
$query = "SELECT * FROM employer_profiles WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$profile = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Calculate profile completion percentage
$required_fields = ['company_name', 'company_description', 'industry', 'company_size', 'location', 'contact_email'];
$filled_fields = 0;
foreach ($required_fields as $field) {
    if (!empty($profile[$field])) {
        $filled_fields++;
    }
}
$profile_completion = ($filled_fields / count($required_fields)) * 100;
?>

<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
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

    <!-- Profile Completion Card -->
    <div class="bg-white shadow-sm rounded-lg mb-6">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Profile Completion</h3>
            <div class="mt-2">
                <div class="relative">
                    <div class="overflow-hidden h-2 text-xs flex rounded bg-gray-200">
                        <div style="width:<?php echo $profile_completion; ?>%" 
                             class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-blue-500">
                        </div>
                    </div>
                </div>
                <p class="mt-2 text-sm text-gray-600">
                    <?php echo round($profile_completion); ?>% Complete
                    <?php if ($profile_completion < 100): ?>
                        <span class="text-red-600 ml-2">
                            (Complete your profile to post jobs)
                        </span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Company Profile Form -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Company Profile</h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">
                Provide information about your company to attract the best candidates.
            </p>
        </div>
        <div class="border-t border-gray-200">
            <form method="POST" class="divide-y divide-gray-200">
                <!-- Basic Information -->
                <div class="px-4 py-5 sm:p-6">
                    <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="company_name" class="block text-sm font-medium text-gray-700">Company Name *</label>
                            <input type="text" name="company_name" id="company_name" required
                                value="<?php echo htmlspecialchars($profile['company_name'] ?? ''); ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div class="sm:col-span-2">
                            <label for="company_description" class="block text-sm font-medium text-gray-700">Company Description *</label>
                            <textarea name="company_description" id="company_description" rows="4" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Tell potential candidates about your company..."
                            ><?php echo htmlspecialchars($profile['company_description'] ?? ''); ?></textarea>
                        </div>

                        <div>
                            <label for="industry" class="block text-sm font-medium text-gray-700">Industry *</label>
                            <select name="industry" id="industry" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Select Industry</option>
                                <option value="technology" <?php echo ($profile['industry'] ?? '') == 'technology' ? 'selected' : ''; ?>>Technology</option>
                                <option value="healthcare" <?php echo ($profile['industry'] ?? '') == 'healthcare' ? 'selected' : ''; ?>>Healthcare</option>
                                <option value="finance" <?php echo ($profile['industry'] ?? '') == 'finance' ? 'selected' : ''; ?>>Finance</option>
                                <option value="education" <?php echo ($profile['industry'] ?? '') == 'education' ? 'selected' : ''; ?>>Education</option>
                                <option value="retail" <?php echo ($profile['industry'] ?? '') == 'retail' ? 'selected' : ''; ?>>Retail</option>
                                <option value="manufacturing" <?php echo ($profile['industry'] ?? '') == 'manufacturing' ? 'selected' : ''; ?>>Manufacturing</option>
                                <option value="other" <?php echo ($profile['industry'] ?? '') == 'other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>

                        <div>
                            <label for="company_size" class="block text-sm font-medium text-gray-700">Company Size *</label>
                            <select name="company_size" id="company_size" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Select Size</option>
                                <option value="1-10" <?php echo ($profile['company_size'] ?? '') == '1-10' ? 'selected' : ''; ?>>1-10 employees</option>
                                <option value="11-50" <?php echo ($profile['company_size'] ?? '') == '11-50' ? 'selected' : ''; ?>>11-50 employees</option>
                                <option value="51-200" <?php echo ($profile['company_size'] ?? '') == '51-200' ? 'selected' : ''; ?>>51-200 employees</option>
                                <option value="201-500" <?php echo ($profile['company_size'] ?? '') == '201-500' ? 'selected' : ''; ?>>201-500 employees</option>
                                <option value="501+" <?php echo ($profile['company_size'] ?? '') == '501+' ? 'selected' : ''; ?>>501+ employees</option>
                            </select>
                        </div>

                        <div>
                            <label for="website" class="block text-sm font-medium text-gray-700">Website</label>
                            <input type="url" name="website" id="website"
                                value="<?php echo htmlspecialchars($profile['website'] ?? ''); ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="https://www.example.com">
                        </div>

                        <div>
                            <label for="location" class="block text-sm font-medium text-gray-700">Location *</label>
                            <input type="text" name="location" id="location" required
                                value="<?php echo htmlspecialchars($profile['location'] ?? ''); ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="City, Country">
                        </div>

                        <div>
                            <label for="contact_email" class="block text-sm font-medium text-gray-700">Contact Email *</label>
                            <input type="email" name="contact_email" id="contact_email" required
                                value="<?php echo htmlspecialchars($profile['contact_email'] ?? ''); ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label for="contact_phone" class="block text-sm font-medium text-gray-700">Contact Phone</label>
                            <input type="tel" name="contact_phone" id="contact_phone"
                                value="<?php echo htmlspecialchars($profile['contact_phone'] ?? ''); ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="px-4 py-3 bg-gray-50 text-right sm:px-6">
                    <button type="submit"
                        class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php mysqli_close($conn); ?> 