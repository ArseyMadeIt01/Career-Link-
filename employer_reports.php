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

// Initialize date range variables first
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get jobs data first since we need it for exports
$jobs_query = "SELECT 
    j.title,
    j.posted_date,
    COUNT(ja.id) as application_count,
    COUNT(CASE WHEN ja.status = 'shortlisted' THEN 1 END) as shortlisted_count,
    COUNT(CASE WHEN ja.status = 'rejected' THEN 1 END) as rejected_count,
    COUNT(CASE WHEN ja.status = 'pending' THEN 1 END) as pending_count
    FROM jobs j
    LEFT JOIN job_applications ja ON j.id = ja.job_id
    WHERE j.employer_id = ?
    AND j.posted_date BETWEEN ? AND ?
    GROUP BY j.id
    ORDER BY j.posted_date DESC";

$stmt = mysqli_prepare($conn, $jobs_query);
mysqli_stmt_bind_param($stmt, "iss", $_SESSION['user_id'], $start_date, $end_date);
mysqli_stmt_execute($stmt);
$jobs_stats = mysqli_stmt_get_result($stmt);

// Now handle exports
require('vendor/autoload.php'); // For TCPDF and PhpSpreadsheet
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Handle CSV Export
if (isset($_POST['export_csv'])) {
    // Get fresh data for export
    $export_query = "SELECT 
        j.title,
        j.posted_date,
        COUNT(ja.id) as application_count,
        COUNT(CASE WHEN ja.status = 'shortlisted' THEN 1 END) as shortlisted_count,
        COUNT(CASE WHEN ja.status = 'rejected' THEN 1 END) as rejected_count,
        COUNT(CASE WHEN ja.status = 'pending' THEN 1 END) as pending_count
        FROM jobs j
        LEFT JOIN job_applications ja ON j.id = ja.job_id
        WHERE j.employer_id = ?
        AND j.posted_date BETWEEN ? AND ?
        GROUP BY j.id, j.title, j.posted_date
        ORDER BY j.posted_date DESC";

    $stmt = mysqli_prepare($conn, $export_query);
    mysqli_stmt_bind_param($stmt, "iss", $_SESSION['user_id'], $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $export_data = mysqli_stmt_get_result($stmt);

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="job_applications_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add headers
    fputcsv($output, ['Job Title', 'Posted Date', 'Total Applications', 'Shortlisted', 'Rejected', 'Pending']);
    
    // Add data
    while ($row = mysqli_fetch_assoc($export_data)) {
        fputcsv($output, [
            $row['title'],
            date('Y-m-d', strtotime($row['posted_date'])),
            $row['application_count'],
            $row['shortlisted_count'],
            $row['rejected_count'],
            $row['pending_count']
        ]);
    }
    
    fclose($output);
    exit();
}

// Handle PDF Export
if (isset($_POST['export_pdf'])) {
    // Get fresh data for export
    $export_query = "SELECT 
        j.title,
        j.posted_date,
        COUNT(ja.id) as application_count,
        COUNT(CASE WHEN ja.status = 'shortlisted' THEN 1 END) as shortlisted_count,
        COUNT(CASE WHEN ja.status = 'rejected' THEN 1 END) as rejected_count,
        COUNT(CASE WHEN ja.status = 'pending' THEN 1 END) as pending_count
        FROM jobs j
        LEFT JOIN job_applications ja ON j.id = ja.job_id
        WHERE j.employer_id = ?
        AND j.posted_date BETWEEN ? AND ?
        GROUP BY j.id, j.title, j.posted_date
        ORDER BY j.posted_date DESC";

    $stmt = mysqli_prepare($conn, $export_query);
    mysqli_stmt_bind_param($stmt, "iss", $_SESSION['user_id'], $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $export_data = mysqli_stmt_get_result($stmt);

    // Create new PDF instance
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('Career Link');
    $pdf->SetAuthor('Career Link');
    $pdf->SetTitle('Job Applications Report');

    // Set margins
    $pdf->SetMargins(15, 15, 15);
    $pdf->AddPage();

    // Add title
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Job Applications Report', 0, 1, 'C');
    $pdf->Ln(10);

    // Add date range
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'Report Period: ' . $start_date . ' to ' . $end_date, 0, 1, 'L');
    $pdf->Ln(5);

    // Add table headers
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(60, 10, 'Job Title', 1);
    $pdf->Cell(30, 10, 'Applications', 1);
    $pdf->Cell(30, 10, 'Shortlisted', 1);
    $pdf->Cell(30, 10, 'Rejected', 1);
    $pdf->Cell(30, 10, 'Pending', 1);
    $pdf->Ln();

    // Add data
    $pdf->SetFont('helvetica', '', 12);
    while ($row = mysqli_fetch_assoc($export_data)) {
        $pdf->Cell(60, 10, $row['title'], 1);
        $pdf->Cell(30, 10, $row['application_count'], 1);
        $pdf->Cell(30, 10, $row['shortlisted_count'], 1);
        $pdf->Cell(30, 10, $row['rejected_count'], 1);
        $pdf->Cell(30, 10, $row['pending_count'], 1);
        $pdf->Ln();
    }

    // Output PDF
    $pdf->Output('job_applications_report_' . date('Y-m-d') . '.pdf', 'D');
    exit();
}

// Handle Excel Export
if (isset($_POST['export_excel'])) {
    // Get fresh data for export
    $export_query = "SELECT 
        j.title,
        j.posted_date,
        COUNT(ja.id) as application_count,
        COUNT(CASE WHEN ja.status = 'shortlisted' THEN 1 END) as shortlisted_count,
        COUNT(CASE WHEN ja.status = 'rejected' THEN 1 END) as rejected_count,
        COUNT(CASE WHEN ja.status = 'pending' THEN 1 END) as pending_count
        FROM jobs j
        LEFT JOIN job_applications ja ON j.id = ja.job_id
        WHERE j.employer_id = ?
        AND j.posted_date BETWEEN ? AND ?
        GROUP BY j.id, j.title, j.posted_date
        ORDER BY j.posted_date DESC";

    $stmt = mysqli_prepare($conn, $export_query);
    mysqli_stmt_bind_param($stmt, "iss", $_SESSION['user_id'], $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $export_data = mysqli_stmt_get_result($stmt);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set headers
    $sheet->setCellValue('A1', 'Job Title');
    $sheet->setCellValue('B1', 'Posted Date');
    $sheet->setCellValue('C1', 'Total Applications');
    $sheet->setCellValue('D1', 'Shortlisted');
    $sheet->setCellValue('E1', 'Rejected');
    $sheet->setCellValue('F1', 'Pending');

    // Add data
    $row = 2;
    while ($job = mysqli_fetch_assoc($export_data)) {
        $sheet->setCellValue('A' . $row, $job['title']);
        $sheet->setCellValue('B' . $row, date('Y-m-d', strtotime($job['posted_date'])));
        $sheet->setCellValue('C' . $row, $job['application_count']);
        $sheet->setCellValue('D' . $row, $job['shortlisted_count']);
        $sheet->setCellValue('E' . $row, $job['rejected_count']);
        $sheet->setCellValue('F' . $row, $job['pending_count']);
        $row++;
    }

    // Auto-size columns
    foreach(range('A','F') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Create Excel file
    $writer = new Xlsx($spreadsheet);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="job_applications_report_' . date('Y-m-d') . '.xlsx"');
    header('Cache-Control: max-age=0');
    $writer->save('php://output');
    exit();
}

// Now we can include the header and continue with the page
$pageTitle = "Reports";
$currentPage = "reports";
require_once 'includes/header.php';

// Get overall statistics
$stats_query = "SELECT 
    COUNT(DISTINCT j.id) as total_jobs,
    COUNT(DISTINCT ja.id) as total_applications,
    COUNT(DISTINCT CASE WHEN ja.status = 'shortlisted' THEN ja.id END) as shortlisted,
    COUNT(DISTINCT CASE WHEN ja.status = 'rejected' THEN ja.id END) as rejected,
    COUNT(DISTINCT CASE WHEN ja.status = 'pending' THEN ja.id END) as pending
    FROM jobs j
    LEFT JOIN job_applications ja ON j.id = ja.job_id
    WHERE j.employer_id = ? 
    AND j.posted_date BETWEEN ? AND ?";

$stmt = mysqli_prepare($conn, $stats_query);
mysqli_stmt_bind_param($stmt, "iss", $_SESSION['user_id'], $start_date, $end_date);
mysqli_stmt_execute($stmt);
$stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Refresh jobs data for display
mysqli_data_seek($jobs_stats, 0);
?>

<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <!-- Date Range Filter -->
    <div class="bg-white shadow-sm rounded-lg mb-6">
        <div class="px-4 py-5 sm:p-6">
            <form method="GET" class="flex items-center space-x-4">
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                    <input type="date" name="start_date" id="start_date" 
                           value="<?php echo $start_date; ?>"
                           class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                    <input type="date" name="end_date" id="end_date" 
                           value="<?php echo $end_date; ?>"
                           class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="flex items-end">
                    <button type="submit"
                            class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                        Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Overall Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm font-medium text-gray-500">Total Jobs</div>
            <div class="mt-2 text-3xl font-semibold text-gray-900"><?php echo $stats['total_jobs']; ?></div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm font-medium text-gray-500">Total Applications</div>
            <div class="mt-2 text-3xl font-semibold text-gray-900"><?php echo $stats['total_applications']; ?></div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm font-medium text-gray-500">Shortlisted</div>
            <div class="mt-2 text-3xl font-semibold text-green-600"><?php echo $stats['shortlisted']; ?></div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm font-medium text-gray-500">Rejected</div>
            <div class="mt-2 text-3xl font-semibold text-red-600"><?php echo $stats['rejected']; ?></div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm font-medium text-gray-500">Pending</div>
            <div class="mt-2 text-3xl font-semibold text-yellow-600"><?php echo $stats['pending']; ?></div>
        </div>
    </div>

    <!-- Detailed Report -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Detailed Job Report</h3>
            <div class="flex space-x-3">
                <form method="POST" class="inline-block">
                    <button type="submit" name="export_pdf"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700">
                        <i class="fas fa-file-pdf mr-2"></i>
                        Export PDF
                    </button>
                </form>
                <form method="POST" class="inline-block">
                    <button type="submit" name="export_excel"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                        <i class="fas fa-file-excel mr-2"></i>
                        Export Excel
                    </button>
                </form>
                <form method="POST" class="inline-block">
                    <button type="submit" name="export_csv"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                        <i class="fas fa-download mr-2"></i>
                        Export CSV
                    </button>
                </form>
            </div>
        </div>
        <div class="border-t border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Job Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Posted Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Applications</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Shortlisted</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rejected</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pending</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php while ($job = mysqli_fetch_assoc($jobs_stats)): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($job['title']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('M d, Y', strtotime($job['posted_date'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo $job['application_count']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">
                                <?php echo $job['shortlisted_count']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                                <?php echo $job['rejected_count']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-yellow-600">
                                <?php echo $job['pending_count']; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php mysqli_close($conn); ?> 