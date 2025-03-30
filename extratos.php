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

// Funções auxiliares
function timeToSeconds($time) {
    $parts = explode(':', $time);
    $hours = (int)$parts[0];
    $minutes = isset($parts[1]) ? (int)$parts[1] : 0;
    return ($hours * 3600) + ($minutes * 60);
}

function secondsToTime($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    return sprintf('%02d:%02d', $hours, $minutes);
}

function calcularHorasMensais($inicio, $fim, $horasTotais) {
    $dataInicio = new DateTime($inicio);
    $dataFim = new DateTime($fim);
    
    $diferenca = $dataInicio->diff($dataFim);
    $mesesContrato = ($diferenca->y * 12) + $diferenca->m;
    $mesesContrato = max(1, $mesesContrato);
    
    $segundosTotais = timeToSeconds($horasTotais);
    $segundosMensais = floor($segundosTotais / $mesesContrato);
    
    return secondsToTime($segundosMensais);
}

// Processar geração de PDF
if (isset($_POST['generate_pdf'])) {
    require_once('TCPDF-main/tcpdf.php');
    
    $company_id = $_POST['company'];
    $month = $_POST['month'];
    $year = $_POST['year'];

    // Obter dados da empresa
    $sql_company = "SELECT company, HorasContratadas, InicioContrato, FimContrato FROM clientes WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql_company);
    mysqli_stmt_bind_param($stmt, "i", $company_id);
    mysqli_stmt_execute($stmt);
    $company_result = mysqli_stmt_get_result($stmt);
    $company_row = mysqli_fetch_assoc($company_result);
    
    $company_name = $company_row['company'];
    $horas_contratadas = substr($company_row['HorasContratadas'], 0, 5);
    $inicio_contrato = $company_row['InicioContrato'];
    $fim_contrato = $company_row['FimContrato'];
    
    $temContrato = ($inicio_contrato != '0000-00-00' && $fim_contrato != '0000-00-00' && $horas_contratadas != '00:00:00');

    // Calcular horas mensais disponíveis
    $horas_mensais = $temContrato ? calcularHorasMensais($inicio_contrato, $fim_contrato, $horas_contratadas) : '00:00';
    $saldo_mensal_segundos = $temContrato ? timeToSeconds($horas_mensais) : 0;

    // Obter o saldo acumulado até o mês ANTERIOR
    $start_date = date('Y-m-01', strtotime($year.'-'.$month.'-01'));
    $sql_previous_assists = "SELECT SUM(TIME_TO_SEC(SUBSTRING(hours_spent, 1, 5))) as total_seconds 
                            FROM assists 
                            WHERE company_id = ? 
                            AND conditions = 'Com Contrato' 
                            AND created_at < ?";
    $stmt_previous = mysqli_prepare($conn, $sql_previous_assists);
    mysqli_stmt_bind_param($stmt_previous, "is", $company_id, $start_date);
    mysqli_stmt_execute($stmt_previous);
    $previous_result = mysqli_stmt_get_result($stmt_previous);
    $previous_row = mysqli_fetch_assoc($previous_result);

    $saldo_anterior_segundos = $temContrato ? 
        (timeToSeconds($horas_contratadas) - ($previous_row['total_seconds'] ?? 0)) : 
        0;

    // Obter assistências do mês atual
    $sql_month_assists = "SELECT id, numero_guia, help_description, hours_spent, created_at, problem, conditions 
                         FROM assists 
                         WHERE company_id = ? 
                         AND MONTH(created_at) = ? 
                         AND YEAR(created_at) = ?
                         ORDER BY created_at ASC";
    $stmt_month = mysqli_prepare($conn, $sql_month_assists);
    mysqli_stmt_bind_param($stmt_month, "iii", $company_id, $month, $year);
    mysqli_stmt_execute($stmt_month);
    $month_assists_result = mysqli_stmt_get_result($stmt_month);

    // Inicializar saldos
    $saldo_total_segundos = $saldo_anterior_segundos;
    $saldo_mensal_segundos = $temContrato ? timeToSeconds($horas_mensais) : 0;

    // Criar PDF com configurações para tabela maior
    $pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Sistema de Gestão de Horas');
    $pdf->SetTitle('Extrato Mensal - ' . $company_name);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Margens ajustadas para tabela maior
    $pdf->SetMargins(10, 20, 10);
    $pdf->SetAutoPageBreak(true, 10);
    
    $pdf->AddPage('L');

    $imageWidth = 20; // Largura menor da imagem
    $imageX = 10; // Margem esquerda
    $imageY = 10; // Margem superior

    $pdf->Image('pcci.jpg', $imageX, $imageY, $imageWidth);
    
    // Título centralizado (aumentado)
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'EXTRATO MENSAL', 0, 1, 'C');
    $pdf->Ln(5);

    // Definir larguras das colunas (aumentadas em ~50%)
    $col_width_guia = 22;
    $col_width_date = 22;
    $col_width_problem = 45;
    $col_width_description = 60;
    $col_width_hours = 22;
    $col_width_conditions = 30;
    $col_width_saldo_mensal = 30;
    $col_width_saldo_total = 30;

    // Calcular largura total para centralização
    $table_width = $col_width_guia + $col_width_date + $col_width_problem + $col_width_description + 
                   $col_width_hours + $col_width_conditions + $col_width_saldo_mensal + $col_width_saldo_total;
    $start_x = ($pdf->GetPageWidth() - $table_width) / 2;

    // Cabeçalho da tabela - PRIMEIRA LINHA (data esquerda, empresa direita)
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(230, 230, 230); // Cinza claro
    $pdf->SetX($start_x);
    
    // Data à esquerda
    $pdf->Cell($table_width/2, 8, date('m-Y', mktime(0, 0, 0, $month, 1, $year)), 1, 0, 'L', true);
    
    // Nome da empresa à direita
    $pdf->Cell($table_width/2, 8, $company_name, 1, 1, 'R', true);
    
    // Segunda linha (cabeçalhos das colunas) - COM FUNDO CINZENTO
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor(230, 230, 230); // Cinza claro
    
    // Calcular altura necessária para o cabeçalho
    $header_texts = [
        'Guia Nº', 
        'Data', 
        'Descrição do Problema', 
        'Descrição do Serviço Efetuado', 
        'Horas Inputadas', 
        'Condição', 
        'Horas Mensais', 
        'Horas Saldo'
    ];
    
    $header_lines = [];
    foreach ($header_texts as $text) {
        $header_lines[] = ceil($pdf->GetStringWidth($text) / ($text == 'Descrição do Serviço Efetuado' ? $col_width_description : 
                            ($text == 'Descrição do Problema' ? $col_width_problem : 22)));
    }
    $max_header_lines = max($header_lines);
    $header_height = 6 * $max_header_lines; // 6mm por linha
    
    $pdf->SetX($start_x);
    $pdf->MultiCell($col_width_guia, $header_height, 'Guia Nº', 1, 'C', true, 0);
    $pdf->MultiCell($col_width_date, $header_height, 'Data', 1, 'C', true, 0);
    $pdf->MultiCell($col_width_problem, $header_height, 'Descrição do Problema', 1, 'C', true, 0);
    $pdf->MultiCell($col_width_description, $header_height, 'Descrição do Serviço Efetuado', 1, 'C', true, 0);
    $pdf->MultiCell($col_width_hours, $header_height, 'Horas Inputadas', 1, 'C', true, 0);
    $pdf->MultiCell($col_width_conditions, $header_height, 'Condição', 1, 'C', true, 0);
    $pdf->MultiCell($col_width_saldo_mensal, $header_height, 'Horas Mensais', 1, 'C', true, 0);
    $pdf->MultiCell($col_width_saldo_total, $header_height, 'Horas Saldo', 1, 1, 'C', true);

    // Linha inicial com saldos
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetFillColor(255, 255, 255); // Fundo branco
    $pdf->SetX($start_x);
    $pdf->Cell($col_width_guia, 8, '', 1, 0, 'C');
    $pdf->Cell($col_width_date, 8, '', 1, 0, 'C');
    $pdf->Cell($col_width_problem, 8, '', 1, 0, 'C');
    $pdf->Cell($col_width_description, 8, '', 1, 0, 'C');
    $pdf->Cell($col_width_hours, 8, '', 1, 0, 'C');
    $pdf->Cell($col_width_conditions, 8, '', 1, 0, 'C');
    $pdf->Cell($col_width_saldo_mensal, 8, $temContrato ? $horas_mensais : 'N/A', 1, 0, 'C');
    $pdf->Cell($col_width_saldo_total, 8, $temContrato ? secondsToTime($saldo_anterior_segundos) : 'N/A', 1, 1, 'C');

    // Processar as assistências do mês atual
    $default_line_height = 6; // Altura padrão de uma linha em mm
    
    while ($row = mysqli_fetch_assoc($month_assists_result)) {
        $guia_id = isset($row['numero_guia']) && !empty($row['numero_guia']) ? $row['numero_guia'] : $row['id'];
        $created_at = date('d-m-Y', strtotime($row['created_at']));
        $help_description = $row['help_description'];
        $problem = $row['problem'];
        $hours_spent = substr($row['hours_spent'], 0, 5);
        $conditions = $row['conditions'];
        $hours_seconds = timeToSeconds($hours_spent);
        
        if ($conditions === "Com Contrato" && $temContrato) {
            $saldo_mensal_segundos -= $hours_seconds;
            $saldo_total_segundos -= $hours_seconds;
        }
        
        $saldo_mensal_cell = $temContrato ? 
            ($saldo_mensal_segundos < 0 ? '-' . secondsToTime(abs($saldo_mensal_segundos)) : secondsToTime($saldo_mensal_segundos)) : 
            'N/A';
            
        $saldo_total_cell = $temContrato ? 
            ($saldo_total_segundos < 0 ? '-' . secondsToTime(abs($saldo_total_segundos)) : secondsToTime($saldo_total_segundos)) : 
            'N/A';
        
        // Calcular número de linhas necessárias para cada campo
        $problem_lines = max(1, ceil($pdf->GetStringWidth($problem) / $col_width_problem));
        $desc_lines = max(1, ceil($pdf->GetStringWidth($help_description) / $col_width_description));
        $max_lines = max(1, $problem_lines, $desc_lines);
        $line_height = $default_line_height * $max_lines;
        
        // Posicionar na linha
        $pdf->SetX($start_x);
        
        // Guia (altura dinâmica)
        $pdf->MultiCell($col_width_guia, $line_height, $guia_id, 1, 'C', false, 0);
        
        // Data (altura dinâmica)
        $pdf->MultiCell($col_width_date, $line_height, $created_at, 1, 'C', false, 0);
        
        // Problema (altura dinâmica)
        $pdf->MultiCell($col_width_problem, $line_height, $problem, 1, 'C', false, 0);
        
        // Descrição (altura dinâmica)
        $pdf->MultiCell($col_width_description, $line_height, $help_description, 1, 'C', false, 0);
        
        // Horas (altura dinâmica)
        $pdf->MultiCell($col_width_hours, $line_height, $hours_spent, 1, 'C', false, 0);
        
        // Condições (altura dinâmica)
        $pdf->MultiCell($col_width_conditions, $line_height, $conditions, 1, 'C', false, 0);
        
        // Saldo Mensal
        if ($temContrato && $saldo_mensal_segundos < 0) {
            $pdf->SetTextColor(0, 0, 0);
            $pdf->MultiCell($col_width_saldo_mensal, $line_height, $saldo_mensal_cell, 1, 'C', false, 0);
            $pdf->SetTextColor(0, 0, 0);
        } else {
            $pdf->MultiCell($col_width_saldo_mensal, $line_height, $saldo_mensal_cell, 1, 'C', false, 0);
        }
        
        // Saldo Total
        if ($temContrato && $saldo_total_segundos < 0) {
            $pdf->SetTextColor(0, 0, 0);
            $pdf->MultiCell($col_width_saldo_total, $line_height, $saldo_total_cell, 1, 1, 'C');
            $pdf->SetTextColor(0, 0, 0);
        } else {
            $pdf->MultiCell($col_width_saldo_total, $line_height, $saldo_total_cell, 1, 1, 'C');
        }
    }

    // Gerar PDF
    $filename = 'extrato_mensal_' . str_replace(' ', '_', $company_name) . '_' . $month . '_' . $year . '.pdf';
    $pdf->Output($filename, 'D');
    exit();
}

// Obter lista de empresas
$sql_companies = "SELECT id, company FROM clientes ORDER BY company";
$companies_result = mysqli_query($conn, $sql_companies);
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

    <div class="container">
        <h1>Gerar Relatório de Assistência</h1>
        
        <form method="post">
            <div class="form-group">
                <label for="company">Empresa:</label>
                <select name="company" id="company" required>
                    <option value="">Selecione uma empresa</option>
                    <?php while ($row = mysqli_fetch_assoc($companies_result)): ?>
                        <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['company']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="month">Mês:</label>
                <select name="month" id="month" required>
                    <option value="">Selecione um mês</option>
                    <?php for ($i = 1; $i <= 12; $i++): ?>
                        <option value="<?= $i ?>"><?= date('F', mktime(0, 0, 0, $i, 1)) ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="year">Ano:</label>
                <select name="year" id="year" required>
                    <option value="">Selecione um ano</option>
                    <?php for ($i = date('Y'); $i >= date('Y') - 5; $i--): ?>
                        <option value="<?= $i ?>"><?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <button type="submit" name="generate_pdf">Gerar PDF</button>
        </form>
    </div>
</body>
</html>

<?php
mysqli_close($conn);
?>