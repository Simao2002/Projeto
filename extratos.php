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

// Definir charset para UTF-8 na conexão
mysqli_set_charset($conn, "utf8");

// Fetch Companies from the clientes table
$sql = "SELECT id, company, HorasContratadas, InicioContrato, FimContrato FROM clientes";
$companies_result = mysqli_query($conn, $sql);

require_once('TCPDF-main\tcpdf.php');

// Função para converter hh:mm em segundos
function timeToSeconds($time) {
    $parts = explode(':', $time);
    $hours = (int)$parts[0];
    $minutes = (int)$parts[1];
    return ($hours * 3600) + ($minutes * 60);
}

// Função para converter segundos em hh:mm
function secondsToTime($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    return sprintf('%02d:%02d', $hours, $minutes);
}

// Handle PDF Generation
if (isset($_POST['generate_pdf'])) {
    $company_id = $_POST['company'];
    $month = $_POST['month'];
    $year = $_POST['year'];

    // Fetch Company Name and HorasContratadas
    $sql_company = "SELECT company, HorasContratadas, InicioContrato, FimContrato FROM clientes WHERE id = $company_id";
    $company_result = mysqli_query($conn, $sql_company);
    $company_row = mysqli_fetch_assoc($company_result);
    $company_name = $company_row['company'];
    $horas_contratadas = substr($company_row['HorasContratadas'], 0, 5);
    
    // Verificar se a empresa tem contrato
    $temContrato = ($company_row['InicioContrato'] != '0000-00-00' && $company_row['FimContrato'] != '0000-00-00' && $company_row['HorasContratadas'] != '00:00:00');

    // Fetch Assists for the selected company and date range (incluindo a condição)
    $sql = "SELECT assists.id, clientes.company, assists.help_description, assists.hours_spent, assists.created_at, assists.problem, assists.conditions 
        FROM assists 
        JOIN clientes ON assists.company_id = clientes.id 
        WHERE assists.company_id = $company_id 
        AND MONTH(assists.created_at) = $month 
        AND YEAR(assists.created_at) = $year 
        ORDER BY assists.created_at ASC";

    $assists_result = mysqli_query($conn, $sql);
    mysqli_data_seek($assists_result, 0);

    // Create PDF using TCPDF
    $pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Configurar documento
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Sistema de Assistência');
    $pdf->SetTitle('Relatório de Assistência - ' . $company_name);
    
    // Remover cabeçalho e rodapé padrão
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Adicionar página
    $pdf->AddPage('L');
    
    // Configurar fonte padrão (helvetica suporta UTF-8)
    $pdf->SetFont('helvetica', '', 16);

    // Adicionar logo
    $pdf->Image('63907485-7886-4fc0-8a3a-43b4b5ea7a8c.jpg', 10, 10, 20);
    
    // Título do documento
    $pdf->Cell(0, 10, 'Relatório de Assistência - ' . $company_name, 0, 1, 'C');
    
    // Mês e ano
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'Mês: ' . date('F', mktime(0, 0, 0, $month, 10)) . ' ' . $year, 0, 1, 'C');

    // Horas Contratadas e Saldo - Só mostra se tiver contrato
    $pdf->SetFont('helvetica', '', 12);
    if ($temContrato) {
        $pdf->SetX($pdf->GetPageWidth() - 100);
        $pdf->Cell(90, 10, 'Horas Contratadas: ' . $horas_contratadas, 0, 1, 'R');
    }

    // Calcular saldo de horas (considerando apenas assistências com contrato)
    $saldo_horas_segundos = $temContrato ? timeToSeconds($horas_contratadas) : 0;
    while ($row = mysqli_fetch_assoc($assists_result)) {
        // Só desconta horas se for "Com Contrato"
        if ($row['conditions'] === "Com Contrato" && $temContrato) {
            $hours_spent = substr($row['hours_spent'], 0, 5);
            $saldo_horas_segundos -= timeToSeconds($hours_spent);
        }
    }
    $saldo_horas_final = $temContrato ? secondsToTime($saldo_horas_segundos) : 'Sem Contrato';

    if ($temContrato) {
        $pdf->SetX($pdf->GetPageWidth() - 100);
        $pdf->Cell(90, 10, 'Saldo de Horas: ' . $saldo_horas_final, 0, 1, 'R');
    }
    $pdf->Ln(10);

    // Reset result pointer
    mysqli_data_seek($assists_result, 0);

    // Definir larguras das colunas (com data como 2º campo)
    $col_width_guia = 20;   // Número da guia
    $col_width_date = 25;   // Data como segundo campo
    $col_width_problem = 45; // Descrição do Problema
    $col_width_description = 65; // Descrição do Serviço
    $col_width_hours = 25;  // Horas gastas
    $col_width_conditions = 35; // Condição
    $col_width_saldo = 35;  // Saldo de horas

    // Calcular largura total e posição inicial
    $table_width = $col_width_guia + $col_width_date + $col_width_problem + $col_width_description + $col_width_hours + $col_width_conditions + $col_width_saldo;
    $start_x = ($pdf->GetPageWidth() - $table_width) / 2;

    // Cabeçalho da tabela
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetX($start_x);
    $pdf->Cell($col_width_guia, 10, 'Guia Nrº', 1, 0, 'C');
    $pdf->Cell($col_width_date, 10, 'Data', 1, 0, 'C');
    $pdf->Cell($col_width_problem, 10, 'Descrição do Problema', 1, 0, 'C');
    $pdf->Cell($col_width_description, 10, 'Descrição do Serviço Efectuado', 1, 0, 'C');
    $pdf->Cell($col_width_hours, 10, 'Horas', 1, 0, 'C');
    $pdf->Cell($col_width_conditions, 10, 'Condição', 1, 0, 'C');
    $pdf->Cell($col_width_saldo, 10, 'Saldo', 1, 1, 'C');

    // Reinicializar saldo
    $saldo_horas_segundos = $temContrato ? timeToSeconds($horas_contratadas) : 0;
    $pdf->SetFont('helvetica', '', 9);

    // Adicionar linhas da tabela
    while ($row = mysqli_fetch_assoc($assists_result)) {
        $pdf->SetX($start_x);
        
        $guia_id = $row['id'];
        $created_at = date('d-m-Y', strtotime($row['created_at']));
        $help_description = $row['help_description'];
        $problem = $row['problem'];
        $hours_spent = substr($row['hours_spent'], 0, 5);
        $conditions = $row['conditions'];
        
        // Calcular saldo (só desconta se for "Com Contrato" e a empresa tiver contrato)
        if ($conditions === "Com Contrato" && $temContrato) {
            $saldo_horas_segundos -= timeToSeconds($hours_spent);
        }
        $saldo_horas_cell = $temContrato ? secondsToTime($saldo_horas_segundos) : 'Sem Contrato';
        
        // Calcular altura da linha
        $max_height = 10;
        $problem_lines = $pdf->getNumLines($problem, $col_width_problem);
        $desc_lines = $pdf->getNumLines($help_description, $col_width_description);
        $max_lines = max($problem_lines, $desc_lines);
        $row_height = $max_lines * 5;
        
        // Primeira célula (Número da Guia)
        $pdf->Cell($col_width_guia, $row_height, $guia_id, 1, 0, 'C');
        
        // Segunda célula (Data)
        $pdf->Cell($col_width_date, $row_height, $created_at, 1, 0, 'C');
        
        // Terceira célula (Problem)
        $pdf->MultiCell($col_width_problem, $row_height, $problem, 1, 'C', false, 0);
        
        // Quarta célula (Description)
        $pdf->MultiCell($col_width_description, $row_height, $help_description, 1, 'L', false, 0);
        
        // Demais células
        $pdf->Cell($col_width_hours, $row_height, $hours_spent, 1, 0, 'C');
        $pdf->Cell($col_width_conditions, $row_height, $conditions, 1, 0, 'C');
        $pdf->Cell($col_width_saldo, $row_height, $saldo_horas_cell, 1, 1, 'C');
    }

    // Gerar PDF
    $filename = 'relatorio_assistencia_' . str_replace(' ', '_', $company_name) . '_' . $month . '_' . $year . '.pdf';
    $pdf->Output($filename, 'D');
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerar Relatório</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="extratos.css">
</head>
<body>
    <a href="home.php" class="back-button">
        <i class="fa-solid fa-house"></i>
    </a>

    <h1>Gerar Relatório de Assistência</h1>

    <form method="post">
        <div class="form-group">
            <label for="company">Empresa:</label>
            <select name="company" id="company" required>
                <option value="">Selecione uma empresa</option>
                <?php 
                mysqli_data_seek($companies_result, 0);
                while ($row = mysqli_fetch_assoc($companies_result)) { ?>
                    <option value="<?php echo $row['id']; ?>">
                        <?php echo htmlspecialchars($row['company']); ?>
                    </option>
                <?php } ?>
            </select>
        </div>

        <div class="form-group">
            <label for="month">Mês:</label>
            <select name="month" id="month" required>
                <option value="">Selecione um mês</option>
                <?php for ($i = 1; $i <= 12; $i++) { ?>
                    <option value="<?php echo $i; ?>"><?php echo date('F', mktime(0, 0, 0, $i, 10)); ?></option>
                <?php } ?>
            </select>
        </div>

        <div class="form-group">
            <label for="year">Ano:</label>
            <select name="year" id="year" required>
                <option value="">Selecione um ano</option>
                <?php for ($i = date('Y'); $i >= 2000; $i--) { ?>
                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                <?php } ?>
            </select>
        </div>

        <button type="submit" name="generate_pdf">Gerar PDF</button>
    </form>
</body>
</html>

<?php
mysqli_close($conn);
?>