<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$userType = $isLoggedIn ? $_SESSION['user_type'] : '';

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'career_link';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Get total stats
$stats = [
    'jobs' => 0,
    'companies' => 0,
    'jobseekers' => 0,
    'placements' => 0
];

if ($conn) {
    // Get active jobs count
    $query = "SELECT COUNT(*) as count FROM jobs WHERE status = 'active'";
    $result = mysqli_query($conn, $query);
    $stats['jobs'] = mysqli_fetch_assoc($result)['count'];

    // Get companies count
    $query = "SELECT COUNT(DISTINCT user_id) as count FROM employer_profiles";
    $result = mysqli_query($conn, $query);
    $stats['companies'] = mysqli_fetch_assoc($result)['count'];

    // Get jobseekers count
    $query = "SELECT COUNT(DISTINCT user_id) as count FROM jobseeker_profiles";
    $result = mysqli_query($conn, $query);
    $stats['jobseekers'] = mysqli_fetch_assoc($result)['count'];

    // Get successful placements (shortlisted applications)
    $query = "SELECT COUNT(*) as count FROM job_applications WHERE status = 'shortlisted'";
    $result = mysqli_query($conn, $query);
    $stats['placements'] = mysqli_fetch_assoc($result)['count'];

    // Get featured jobs
    $featured_jobs_query = "SELECT j.*, ep.company_name, 
                           (SELECT COUNT(*) FROM job_applications WHERE job_id = j.id) as application_count
                           FROM jobs j
                           LEFT JOIN employer_profiles ep ON j.employer_id = ep.user_id
                           WHERE j.status = 'active'
                           ORDER BY j.posted_date DESC LIMIT 6";
    $featured_jobs = mysqli_query($conn, $featured_jobs_query);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Career Link - Find Your Dream Job</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="index.php" class="text-2xl font-bold text-blue-600">
                            Career<span class="text-gray-800">Link</span>
                        </a>
                    </div>
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="#featured-jobs" class="text-gray-500 hover:text-gray-700 px-3 py-2 rounded-md text-sm font-medium">
                            Browse Jobs
                        </a>
                        <a href="#how-it-works" class="text-gray-500 hover:text-gray-700 px-3 py-2 rounded-md text-sm font-medium">
                            How It Works
                        </a>
                        <a href="#about" class="text-gray-500 hover:text-gray-700 px-3 py-2 rounded-md text-sm font-medium">
                            About Us
                        </a>
                    </div>
                </div>
                <div class="flex items-center">
                    <?php if ($isLoggedIn): ?>
                        <a href="<?php echo $userType == 'employer' ? 'employer_dashboard.php' : 'jobseeker_dashboard.php'; ?>" 
                           class="text-gray-500 hover:text-gray-700 px-3 py-2 rounded-md text-sm font-medium">
                            Dashboard
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="text-gray-500 hover:text-gray-700 px-3 py-2 rounded-md text-sm font-medium">
                            Login
                        </a>
                        <a href="register.php" 
                           class="ml-4 px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                            Sign up
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="relative bg-white overflow-hidden">
        <div class="max-w-7xl mx-auto">
            <div class="relative z-10 pb-8 bg-white sm:pb-16 md:pb-20 lg:max-w-2xl lg:w-full lg:pb-28 xl:pb-32">
                <main class="mt-10 mx-auto max-w-7xl px-4 sm:mt-12 sm:px-6 md:mt-16 lg:mt-20 lg:px-8 xl:mt-28">
                    <div class="sm:text-center lg:text-left">
                        <h1 class="text-4xl tracking-tight font-extrabold text-gray-900 sm:text-5xl md:text-6xl">
                            <span class="block">Find Your Dream Job</span>
                            <span class="block text-blue-600">Start Your Journey Today</span>
                        </h1>
                        <p class="mt-3 text-base text-gray-500 sm:mt-5 sm:text-lg sm:max-w-xl sm:mx-auto md:mt-5 md:text-xl lg:mx-0">
                            Connect with top employers and discover opportunities that match your skills and aspirations. 
                            Your next career move starts here.
                        </p>
                        <div class="mt-5 sm:mt-8 sm:flex sm:justify-center lg:justify-start">
                            <div class="rounded-md shadow">
                                <a href="register.php" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 md:py-4 md:text-lg md:px-10">
                                    Get Started
                                </a>
                            </div>
                            <div class="mt-3 sm:mt-0 sm:ml-3">
                                <a href="#featured-jobs" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 md:py-4 md:text-lg md:px-10">
                                    View Jobs
                                </a>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>
        <div class="lg:absolute lg:inset-y-0 lg:right-0 lg:w-1/2">
            <img class="h-56 w-full object-cover sm:h-72 md:h-96 lg:w-full lg:h-full" 
                 src="https://images.unsplash.com/photo-1551434678-e076c223a692?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=2850&q=80" 
                 alt="Office workspace">
        </div>
    </div>

    <!-- Stats Section -->
    <div class="bg-blue-600">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:py-16 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                <div class="text-center">
                    <p class="text-4xl font-extrabold text-white"><?php echo number_format($stats['jobs']); ?>+</p>
                    <p class="mt-2 text-sm font-medium text-blue-100">Active Jobs</p>
                </div>
                <div class="text-center">
                    <p class="text-4xl font-extrabold text-white"><?php echo number_format($stats['companies']); ?>+</p>
                    <p class="mt-2 text-sm font-medium text-blue-100">Companies</p>
                </div>
                <div class="text-center">
                    <p class="text-4xl font-extrabold text-white"><?php echo number_format($stats['jobseekers']); ?>+</p>
                    <p class="mt-2 text-sm font-medium text-blue-100">Job Seekers</p>
                </div>
                <div class="text-center">
                    <p class="text-4xl font-extrabold text-white"><?php echo number_format($stats['placements']); ?>+</p>
                    <p class="mt-2 text-sm font-medium text-blue-100">Successful Placements</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Featured Jobs Section -->
    <div id="featured-jobs" class="bg-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl font-extrabold text-gray-900">Featured Jobs</h2>
                <p class="mt-4 text-lg text-gray-500">Discover your next opportunity from our latest job postings</p>
            </div>

            <div class="mt-12 grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                <?php if ($conn && mysqli_num_rows($featured_jobs) > 0):
                    while ($job = mysqli_fetch_assoc($featured_jobs)): ?>
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-lg transition-shadow duration-200">
                            <div class="p-6">
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
                                    </p>
                                    <p class="text-sm text-gray-600 mt-1">
                                        <i class="fas fa-clock mr-2"></i>
                                        <?php echo ucfirst($job['job_type']); ?>
                                    </p>
                                </div>
                                <div class="mt-4">
                                    <p class="text-sm text-gray-500 line-clamp-2">
                                        <?php echo htmlspecialchars(substr($job['description'], 0, 150)) . '...'; ?>
                                    </p>
                                </div>
                                <div class="mt-4 flex justify-between items-center">
                                    <span class="text-sm text-gray-500">
                                        <i class="fas fa-users mr-1"></i>
                                        <?php echo $job['application_count']; ?> applicants
                                    </span>
                                    <a href="<?php echo $isLoggedIn ? 'view_job.php?id=' . $job['id'] : 'login.php'; ?>" 
                                       class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile;
                endif; ?>
            </div>

            <div class="mt-12 text-center">
                <a href="<?php echo $isLoggedIn ? 'search_jobs.php' : 'register.php'; ?>" 
                   class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    View All Jobs
                    <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- How It Works Section -->
    <div id="how-it-works" class="bg-gray-50 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl font-extrabold text-gray-900">How It Works</h2>
                <p class="mt-4 text-lg text-gray-500">Simple steps to start your career journey</p>
            </div>

            <div class="mt-12 grid gap-8 md:grid-cols-3">
                <div class="text-center">
                    <div class="mx-auto h-12 w-12 text-blue-600 text-3xl">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h3 class="mt-6 text-xl font-medium text-gray-900">Create Account</h3>
                    <p class="mt-2 text-base text-gray-500">
                        Sign up as a job seeker or employer and complete your profile
                    </p>
                </div>

                <div class="text-center">
                    <div class="mx-auto h-12 w-12 text-blue-600 text-3xl">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3 class="mt-6 text-xl font-medium text-gray-900">Search Jobs</h3>
                    <p class="mt-2 text-base text-gray-500">
                        Browse through our extensive list of job opportunities
                    </p>
                </div>

                <div class="text-center">
                    <div class="mx-auto h-12 w-12 text-blue-600 text-3xl">
                        <i class="fas fa-paper-plane"></i>
                    </div>
                    <h3 class="mt-6 text-xl font-medium text-gray-900">Apply & Connect</h3>
                    <p class="mt-2 text-base text-gray-500">
                        Submit your applications and connect with employers
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- About Section -->
    <div id="about" class="bg-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="lg:text-center">
                <h2 class="text-3xl font-extrabold text-gray-900">About Career Link</h2>
                <p class="mt-4 max-w-2xl text-xl text-gray-500 lg:mx-auto">
                    We're dedicated to connecting talented professionals with their dream careers
                </p>
            </div>

            <div class="mt-12">
                <div class="prose prose-blue mx-auto lg:max-w-3xl">
                    <p class="text-gray-500 leading-relaxed">
                        Career Link is your gateway to professional success. We understand that finding the right job or the perfect candidate 
                        can be challenging. That's why we've created a platform that makes the process seamless and efficient. Whether you're 
                        a job seeker looking for your next opportunity or an employer searching for top talent, we're here to help you succeed.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="col-span-1 md:col-span-2">
                    <h3 class="text-2xl font-bold text-white">Career<span class="text-blue-400">Link</span></h3>
                    <p class="mt-4 text-gray-300">
                        Connecting talent with opportunity. Your next career move starts here.
                    </p>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-gray-400 uppercase tracking-wider">Quick Links</h4>
                    <ul class="mt-4 space-y-2">
                        <li>
                            <a href="#featured-jobs" class="text-base text-gray-300 hover:text-white">Browse Jobs</a>
                        </li>
                        <li>
                            <a href="register.php" class="text-base text-gray-300 hover:text-white">Sign Up</a>
                        </li>
                        <li>
                            <a href="#about" class="text-base text-gray-300 hover:text-white">About Us</a>
                        </li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-gray-400 uppercase tracking-wider">Legal</h4>
                    <ul class="mt-4 space-y-2">
                        <li>
                            <a href="#" class="text-base text-gray-300 hover:text-white">Privacy Policy</a>
                        </li>
                        <li>
                            <a href="#" class="text-base text-gray-300 hover:text-white">Terms of Service</a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="mt-8 border-t border-gray-700 pt-8">
                <p class="text-base text-gray-400 text-center">
                    © <?php echo date('Y'); ?> Career Link. All rights reserved.
                </p>
            </div>
        </div>
    </footer>
</body>
</html>
