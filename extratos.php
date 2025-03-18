<?php
session_start();

if (!isset($_SESSION['id']) || !isset($_SESSION['user_name'])) {
    header("Location: index.php");
    exit();
}

$sname = "localhost";
$uname = "root";
$password = "";
$db_name = "test_db";

$conn = mysqli_connect($sname, $uname, $password, $db_name);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Fetch Companies from the clientes table
$sql = "SELECT id, company FROM clientes";
$companies_result = mysqli_query($conn, $sql);

require('fpdf/fpdf.php');

// Custom FPDF class with helper methods
class PDF extends FPDF {
    // Helper method to calculate the height of MultiCell
    function GetMultiCellHeight($width, $line_height, $text) {
        $lines = $this->NbLines($width, $text);
        return $lines * $line_height;
    }

    // Helper method to calculate the number of lines
    function NbLines($width, $text) {
        $cw = &$this->CurrentFont['cw'];
        if ($width == 0) {
            $width = $this->w - $this->rMargin - $this->x;
        }
        $wmax = ($width - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $text);
        $nb = strlen($s);
        if ($nb > 0 && $s[$nb - 1] == "\n") {
            $nb--;
        }
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if ($c == ' ') {
                $sep = $i;
            }
            $l += $cw[$c];
            if ($l > $wmax) {
                if ($sep == -1) {
                    if ($i == $j) {
                        $i++;
                    }
                } else {
                    $i = $sep + 1;
                }
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            } else {
                $i++;
            }
        }
        return $nl;
    }
}

// Handle PDF Generation
if (isset($_POST['generate_pdf'])) {
    $company_id = $_POST['company'];

    // Fetch Company Name
    $sql_company = "SELECT company FROM clientes WHERE id = $company_id";
    $company_result = mysqli_query($conn, $sql_company);
    $company_row = mysqli_fetch_assoc($company_result);
    $company_name = $company_row['company'];

    // Fetch Assists for the selected company
    $sql = "SELECT clientes.company, assists.help_description, assists.hours_spent, assists.created_at, assists.problem 
            FROM assists 
            JOIN clientes ON assists.company_id = clientes.id 
            WHERE assists.company_id = $company_id 
            ORDER BY assists.created_at DESC";
    $assists_result = mysqli_query($conn, $sql);

    // Calculate total hours spent
    $total_hours = 0;
    while ($row = mysqli_fetch_assoc($assists_result)) {
        $total_hours += $row['hours_spent'];
    }
    // Reset the pointer to the beginning of the result set
    mysqli_data_seek($assists_result, 0);

    // Create PDF using the custom class
    $pdf = new PDF(); // Use the custom PDF class
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);

    // Add Company Name as Title
    $pdf->Cell(0, 10, 'Assistance Report for ' . $company_name, 0, 1, 'C');

    $pdf->SetFont('Arial', '', 12); // Smaller font size (12 instead of 16)
    $pdf->Cell(0, 10, 'Total Hours spent: ' . $total_hours, 0, 1, 'C');

    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Ln(10); // Add some space after the title

    // Define column widths
    $col_width_description = 60; // Width for the description column
    $col_width_problem = 60;     // Width for the problem column
    $col_width_hours = 30;       // Width for the hours column
    $col_width_date = 40;        // Width for the date column

    // Calculate total table width
    $table_width = $col_width_description + $col_width_problem + $col_width_hours + $col_width_date;

    // Calculate starting X position to center the table
    $start_x = ($pdf->GetPageWidth() - $table_width) / 2;

    // Add table headers
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetX($start_x); // Set X position to center the table
    $pdf->Cell($col_width_problem, 10, 'Problem', 1, 0, 'C');
    $pdf->Cell($col_width_description, 10, 'Help Description', 1, 0, 'C');
    $pdf->Cell($col_width_hours, 10, 'Hours Spent', 1, 0, 'C');
    $pdf->Cell($col_width_date, 10, 'Date', 1, 1, 'C');

    // Add table rows
    $pdf->SetFont('Arial', '', 12);
    while ($row = mysqli_fetch_assoc($assists_result)) {
        $pdf->SetX($start_x); // Set X position to center the table

        // Calculate the height required for the help_description and problem cells
        $help_description = $row['help_description'];
        $problem = $row['problem'];
        $height_description = $pdf->GetMultiCellHeight($col_width_description, 10, $help_description);
        $height_problem = $pdf->GetMultiCellHeight($col_width_problem, 10, $problem);
        $height = max($height_description, $height_problem);

        // Output the help_description cell with MultiCell
        $pdf->MultiCell($col_width_description, 10, $help_description, 1, 'L');

        // Save the current Y position
        $y = $pdf->GetY();

        // Output the problem cell with MultiCell
        $pdf->SetXY($start_x + $col_width_description, $y - $height);
        $pdf->MultiCell($col_width_problem, 10, $problem, 1, 'L');

        // Output the hours_spent cell with the same height
        $pdf->SetXY($start_x + $col_width_description + $col_width_problem, $y - $height);
        $pdf->Cell($col_width_hours, $height, $row['hours_spent'], 1, 0, 'C');

        // Output the created_at cell with the same height
        $pdf->SetXY($start_x + $col_width_description + $col_width_problem + $col_width_hours, $y - $height);
        $pdf->Cell($col_width_date, $height, date('d-m-Y', strtotime($row['created_at'])), 1, 1, 'C');
    }

    // Output the PDF with a dynamic filename
    $filename = 'assistance_report_' . str_replace(' ', '_', $company_name) . '.pdf';
    $pdf->Output('D', $filename); // 'D' forces download with the dynamic filename
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Generate Report</title>
    <!-- Link to Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Link to the external CSS file -->
    <link rel="stylesheet" href="extratos.css">
</head>
<body>
    <!-- Back to Home Button -->
    <a href="home.php" class="back-button">
        <i class="fa-solid fa-house"></i>
    </a>

    <h1>Generate Assistance Report</h1>

    <!-- Select Company Form -->
    <form method="post">
        <div class="form-group">
            <label for="company">Company:</label>
            <select name="company" id="company" required>
                <option value="">Select a company</option>
                <?php while ($row = mysqli_fetch_assoc($companies_result)) { ?>
                    <option value="<?php echo $row['id']; ?>"><?php echo $row['company']; ?></option>
                <?php } ?>
            </select>
        </div>

        <button type="submit" name="generate_pdf">Generate PDF</button>
    </form>
</body>
</html>

<?php
mysqli_close($conn);
?>\