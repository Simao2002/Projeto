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

    // Verificar se é o primeiro mês do contrato
    $primeiroMesContrato = false;
    if ($temContrato) {
        $dataInicio = new DateTime($inicio_contrato);
        $primeiroMesContrato = ($dataInicio->format('m') == $month && $dataInicio->format('Y') == $year);
    }

    // Calcular horas mensais disponíveis
    $horas_mensais = $temContrato ? calcularHorasMensais($inicio_contrato, $fim_contrato, $horas_contratadas) : '00:00';
    $segundos_mensais = $temContrato ? timeToSeconds($horas_mensais) : 0;

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

    $total_segundos_utilizados_anteriores = $previous_row['total_seconds'] ?? 0;
    $segundos_contratados = timeToSeconds($horas_contratadas);
    
    // Calcular saldo anterior (total contratado - horas já utilizadas)
    $saldo_anterior_segundos = $temContrato ? 
        ($segundos_contratados - $total_segundos_utilizados_anteriores) : 
        0;

    // Calcular o saldo acumulado do mês anterior (pode ser positivo ou negativo)
    $mes_anterior = date('Y-m', strtotime('-1 month', strtotime($year.'-'.$month.'-01')));
    $sql_saldo_mes_anterior = "SELECT saldo_mensal FROM saldo_horas 
                              WHERE company_id = ? 
                              AND mes_ano = ?";
    $stmt_saldo_anterior = mysqli_prepare($conn, $sql_saldo_mes_anterior);
    mysqli_stmt_bind_param($stmt_saldo_anterior, "is", $company_id, $mes_anterior);
    mysqli_stmt_execute($stmt_saldo_anterior);
    $saldo_anterior_result = mysqli_stmt_get_result($stmt_saldo_anterior);
    $saldo_anterior_row = mysqli_fetch_assoc($saldo_anterior_result);
    
    $saldo_mes_anterior_segundos = $saldo_anterior_row['saldo_mensal'] ?? 0;
    
    // Calcular horas disponíveis para este mês (horas mensais + saldo do mês anterior)
    $horas_disponiveis_mes_atual_segundos = $segundos_mensais + $saldo_mes_anterior_segundos;
    $horas_disponiveis_mes_atual = secondsToTime($horas_disponiveis_mes_atual_segundos);

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
    $saldo_mensal_segundos = $horas_disponiveis_mes_atual_segundos;
    $total_horas_mes_segundos = 0;

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
    
    // Título centralizado (mantém tamanho 16)
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'EXTRATO MENSAL', 0, 1, 'C');
    $pdf->Ln(5);

    // Definir larguras das colunas (valores em mm)
    $col_width_guia = 18;
    $col_width_date = 18;
    $col_width_problem = 70;
    $col_width_description = 90;
    $col_width_horas_contrato = 20;
    $col_width_saldo_mensal = 25;
    $col_width_hours = 15;
    $col_width_saldo_total = 25;

    // Calcular largura total para centralização
    $table_width = $col_width_guia + $col_width_date + $col_width_problem + $col_width_description + 
                   $col_width_horas_contrato + $col_width_saldo_mensal + $col_width_hours + $col_width_saldo_total;
    $start_x = ($pdf->GetPageWidth() - $table_width) / 2;

    // Cabeçalho da tabela - PRIMEIRA LINHA (data esquerda, empresa direita)
    $pdf->SetFont('helvetica', 'B', 6);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetX($start_x);
        
    // Data à esquerda (sem borda)
    $pdf->Cell($table_width/2, 8, date('m-Y', mktime(0, 0, 0, $month, 1, $year)), 0, 0, 'L', true);
        
    // Nome da empresa à direita (sem borda)
    $pdf->Cell($table_width/2, 8, $company_name, 0, 1, 'R', true);
    
    // Segunda linha (cabeçalhos das colunas)
    $pdf->SetFont('helvetica', 'B', 6);
    $pdf->SetFillColor(230, 230, 230);
    
    // Calcular altura necessária para o cabeçalho
    $header_texts = [
        'Guia Nº', 
        'Data', 
        'Descrição do Problema', 
        'Descrição do Serviço Efetuado', 
        'Horas Contrato',
        'Horas Disponíveis',
        'Horas Inputadas', 
        'Horas Saldo'
    ];
    
    $header_lines = [];
    foreach ($header_texts as $text) {
        $header_lines[] = ceil($pdf->GetStringWidth($text) / ($text == 'Descrição do Serviço Efetuado' ? $col_width_description : 
                            ($text == 'Descrição do Problema' ? $col_width_problem : 
                            ($text == 'Horas Contrato' ? $col_width_horas_contrato : 22))));
    }
    $max_header_lines = max($header_lines);
    $header_height = 6 * $max_header_lines;
    
    // Função para desenhar célula com texto alinhado na parte inferior
    function drawHeaderCell($pdf, $x, $y, $width, $height, $text, $border, $fill) {
        $pdf->SetXY($x, $y);
        $pdf->Cell($width, $height, '', $border, 0, 'C', $fill);
        $pdf->SetXY($x, $y + ($height - 6));
        $pdf->Cell($width, 6, $text, 0, 0, 'C');
    }

    $start_y = $pdf->GetY();
    $pdf->SetY($start_y);
    
    // Desenhar cada célula do cabeçalho com texto alinhado na parte inferior
    $current_x = $start_x;
    drawHeaderCell($pdf, $current_x, $start_y, $col_width_guia, $header_height, 'Guia Nº', 1, true);
    $current_x += $col_width_guia;
    drawHeaderCell($pdf, $current_x, $start_y, $col_width_date, $header_height, 'Data', 1, true);
    $current_x += $col_width_date;
    drawHeaderCell($pdf, $current_x, $start_y, $col_width_problem, $header_height, 'Descrição do Problema', 1, true);
    $current_x += $col_width_problem;
    drawHeaderCell($pdf, $current_x, $start_y, $col_width_description, $header_height, 'Descrição do Serviço Efetuado', 1, true);
    $current_x += $col_width_description;
    drawHeaderCell($pdf, $current_x, $start_y, $col_width_horas_contrato, $header_height, 'Horas Contrato', 1, true);
    $current_x += $col_width_horas_contrato;
    drawHeaderCell($pdf, $current_x, $start_y, $col_width_saldo_mensal, $header_height, 'Horas Disponíveis', 1, true);
    $current_x += $col_width_saldo_mensal;
    drawHeaderCell($pdf, $current_x, $start_y, $col_width_hours, $header_height, 'Horas Inputadas', 1, true);
    $current_x += $col_width_hours;
    drawHeaderCell($pdf, $current_x, $start_y, $col_width_saldo_total, $header_height, 'Horas Saldo', 1, true);
    
    $pdf->SetY($start_y + $header_height);

    // Configuração para centralização vertical e horizontal
    $pdf->setCellHeightRatio(1.5);

    // Mostrar linha com total contratado apenas no primeiro mês do contrato
    if ($primeiroMesContrato) {
        // Linha com o total contratado (primeira linha de dados)
        $pdf->SetFont('helvetica', 'B', 6);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetX($start_x);
        $pdf->Cell($col_width_guia, 8, '', 1, 0, 'C');
        $pdf->Cell($col_width_date, 8, '', 1, 0, 'C');
        $pdf->Cell($col_width_problem, 8, '', 1, 0, 'C');
        $pdf->Cell($col_width_description, 8, '', 1, 0, 'C');
        $pdf->Cell($col_width_horas_contrato, 8, '', 1, 0, 'C');
        $pdf->Cell($col_width_saldo_mensal, 8, '', 1, 0, 'C');
        $pdf->Cell($col_width_hours, 8, '', 1, 0, 'C');
        
        // Formatar horas contratadas como HHH:MM
        $parts = explode(':', $horas_contratadas);
        $hours = (int)$parts[0];
        $minutes = isset($parts[1]) ? str_pad($parts[1], 2, '0', STR_PAD_LEFT) : '00';
        $total_contratado_formatado = $hours . ':' . $minutes;
        $pdf->Cell($col_width_saldo_total, 8, $total_contratado_formatado, 1, 1, 'C');
    }

    // Linha inicial com saldos
    $pdf->SetFont('helvetica', '', 6);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetX($start_x);
    $pdf->Cell($col_width_guia, 8, '', 1, 0, 'C');
    $pdf->Cell($col_width_date, 8, '', 1, 0, 'C');
    $pdf->Cell($col_width_problem, 8, '', 1, 0, 'C');
    $pdf->Cell($col_width_description, 8, '', 1, 0, 'C');
    $pdf->Cell($col_width_horas_contrato, 8, '', 1, 0, 'C');
    $pdf->Cell($col_width_saldo_mensal, 8, $temContrato ? $horas_disponiveis_mes_atual : 'N/A', 1, 0, 'C');
    $pdf->Cell($col_width_hours, 8, '', 1, 0, 'C');
    $pdf->Cell($col_width_saldo_total, 8, '', 1, 1, 'C');

    // Processar as assistências do mês atual
    $default_line_height = 6;
    
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
            $total_horas_mes_segundos += $hours_seconds;
        }
        
        $saldo_mensal_cell = $temContrato && $conditions === "Com Contrato" ? 
            ($saldo_mensal_segundos < 0 ? '-' . secondsToTime(abs($saldo_mensal_segundos)) : secondsToTime($saldo_mensal_segundos)) : 
            'N/A';
            
        // Determinar o que mostrar na célula Horas Saldo
        if ($conditions !== "Com Contrato") {
            $saldo_total_cell = ''; // Célula vazia para condições diferentes
        } else {
            $saldo_total_cell = $temContrato ? 
                ($saldo_total_segundos < 0 ? '-' . secondsToTime(abs($saldo_total_segundos)) : secondsToTime($saldo_total_segundos)) : 
                'N/A';
            
            // Formatar para HHH:MM apenas se for "Com Contrato"
            if($temContrato) {
                $parts = explode(':', $saldo_total_cell);
                $hours = (int)$parts[0];
                $minutes = str_pad($parts[1] ?? '00', 2, '0', STR_PAD_LEFT);
                $saldo_total_cell = $hours . ':' . $minutes;
            }
        }
        
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
        
        // Horas Contrato (nova coluna - em branco)
        $pdf->MultiCell($col_width_horas_contrato, $line_height, '', 1, 'C', false, 0);
        
        // Horas Disponíveis
        $pdf->MultiCell($col_width_saldo_mensal, $line_height, $saldo_mensal_cell, 1, 'C', false, 0);
        
        // Horas Inputadas (altura dinâmica)
        $pdf->MultiCell($col_width_hours, $line_height, $hours_spent, 1, 'C', false, 0);
        
        // Saldo Total (centralizado e formatado ou vazio)
        $pdf->MultiCell($col_width_saldo_total, $line_height, $saldo_total_cell, 1, 'C', false, 1);
    }

    // Calcular saldo para o próximo mês (horas disponíveis - horas utilizadas)
    $saldo_proximo_mes_segundos = $horas_disponiveis_mes_atual_segundos - $total_horas_mes_segundos;
    
    // Salvar o saldo para o próximo mês na tabela saldo_horas
    $mes_atual = date('Y-m', strtotime($year.'-'.$month.'-01'));
    $sql_save_saldo = "INSERT INTO saldo_horas (company_id, mes_ano, saldo_mensal) 
                      VALUES (?, ?, ?)
                      ON DUPLICATE KEY UPDATE saldo_mensal = ?";
    $stmt_save_saldo = mysqli_prepare($conn, $sql_save_saldo);
    mysqli_stmt_bind_param($stmt_save_saldo, "isii", $company_id, $mes_atual, $saldo_proximo_mes_segundos, $saldo_proximo_mes_segundos);
    mysqli_stmt_execute($stmt_save_saldo);

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
// Criar tabela saldo_horas se não existir
$sql_create_table = "CREATE TABLE IF NOT EXISTS saldo_horas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    mes_ano VARCHAR(7) NOT NULL,
    saldo_mensal INT NOT NULL COMMENT 'Saldo em segundos',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_company_month (company_id, mes_ano),
    FOREIGN KEY (company_id) REFERENCES clientes(id)
)";
mysqli_query($conn, $sql_create_table);

mysqli_close($conn);
?>