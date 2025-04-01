<?php
session_start();

if (!isset($_SESSION['id']) || !isset($_SESSION['user_name'])) {
    header("Location: index.php");
    exit();
}

require_once('TCPDF-main/tcpdf.php');

// Configuração da base de dados
$sname = "localhost";
$uname = "root";
$password = "";
$db_name = "test_db";

$conn = mysqli_connect($sname, $uname, $password, $db_name);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Obter dados da empresa selecionada
$companyData = [
    'company' => 'Empresa não selecionada',
    'Morada' => 'Morada não disponível',
    'PostalCode' => '0000-000',
    'Localidade' => 'Localidade não disponível',
    'Responsavel' => 'Responsável não disponível',
    'InicioContrato' => '0000-00-00',
    'FimContrato' => '0000-00-00'
];

if (isset($_POST['company']) && !empty($_POST['company'])) {
    $company_id = $_POST['company'];
    $sql = "SELECT company, Morada, PostalCode, Localidade, Responsavel, InicioContrato, FimContrato 
            FROM clientes 
            WHERE id = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $company_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $companyData = $row;
    }
    
    mysqli_stmt_close($stmt);
}

// Obter outros dados do formulário
$problem = $_POST['problem'] ?? '';
$help_description = $_POST['help'] ?? '';
$hours_spent = $_POST['hours'] ?? '';
$service_status = $_POST['service_status'] ?? '';
$conditions = $_POST['conditions'] ?? '';
$lista_problemas = $_POST['lista_problemas'] ?? '';
$intervencao = $_POST['intervencao'] ?? '';
$tecnico = $_POST['tecnico'] ?? '';
$email_tecnico = $_POST['email_tecnico'] ?? '';
$data_assistencia = $_POST['data_assistencia'] ?? date('Y-m-d');

// Verificar se tem contrato válido
$temContrato = ($companyData['InicioContrato'] != '0000-00-00' && $companyData['FimContrato'] != '0000-00-00');

// Gerar número de guia temporário
$date = new DateTime($data_assistencia);
$yearMonth = $date->format('Ym');
$numero_guia = $yearMonth ;

// Criar novo PDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

$pdf->SetMargins(20, 10, 15);
$pdf->SetAutoPageBreak(true, 25);

$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Sistema de Assistência');


$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

$pdf->AddPage();
$pdf->SetFont('helvetica', '', 10);

// Logo
$imageWidth = 35;
$pageWidth = $pdf->getPageWidth() - $pdf->getMargins()['left'] - $pdf->getMargins()['right'];
$imageX = ($pageWidth - $imageWidth) / 2 - 35;
$pdf->Image('pcci.jpg', $imageX, 30, $imageWidth);

// Título
$pdf->SetXY(120, 30);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetFillColor(200, 200, 200);
$pdf->Cell(70, 10, 'Guia de Assistência N. ' . $numero_guia, 1, 1, 'C', 1); 


$pdf->Ln(15);

// Tabela de informações do cliente
$tbl = <<<EOD
<style>
    .cliente-table {
        width: 70mm;
        font-size: 9pt;
    }
    .cliente-title {
        background-color: #c8c8c8;
        font-weight: bold;
        text-align: center;
        border: 1px solid #000;
        font-size: 9pt;
        padding: 2px;
    }
    .cliente-label {
        background-color: #c8c8c8;
        width: 20mm;
        border: 1px solid #000;
        padding: 2px 2px 2px 3mm;
    }
    .cliente-value {
        background-color: #ffffff;
        border: 1px solid #000;
        width: 50mm;
        padding: 2px 2px 2px 2mm;
    }
    .cliente-final {
        background-color: #ffffff;
        text-align: center;
        border: 1px solid #000;
        padding: 2px;
    }
</style>

<table class="cliente-table" cellspacing="0" cellpadding="0" border="0">
    <tr>
        <td class="cliente-title" colspan="2">Cliente</td>
    </tr>
    <tr>
        <td class="cliente-label">Nome:</td>
        <td class="cliente-value">{$companyData['company']}</td>
    </tr>
    <tr>
        <td class="cliente-label">Morada:</td>
        <td class="cliente-value">{$companyData['Morada']}</td>
    </tr>
    <tr>
        <td class="cliente-final" colspan="2">{$companyData['PostalCode']} {$companyData['Localidade']}</td>
    </tr>
</table>
EOD;

$pdf->SetXY(120, 45);
$pdf->writeHTML($tbl, true, false, false, false, '');

$currentY = $pdf->GetY();
$pdf->SetY($currentY + 5);

$pdf->Ln(9);

// Descrição do Problema
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetFillColor(200, 200, 200);
$pdf->Cell(170, 7, 'Descrição do Problema', 0, 1, 'C', 1);
$pdf->SetFillColor(255, 255, 255);
$pdf->SetFont('helvetica', '', 10);

$problemHeight = $pdf->getStringHeight(170, $problem);
$pdf->Rect(20, $pdf->GetY(), 170, $problemHeight + 15);
$pdf->MultiCell(170, 6, $problem, 0, 'L');
$pdf->Ln($problemHeight + 12);

// Descrição do Serviço
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetFillColor(200, 200, 200);
$pdf->Cell(170, 7, 'Descrição do Serviço Efectuado', 0, 1, 'C', 1);
$pdf->SetFillColor(255, 255, 255);
$pdf->SetFont('helvetica', '', 10);

$helpHeight = $pdf->getStringHeight(170, $help_description);
$pdf->Rect(20, $pdf->GetY(), 170, $helpHeight + 25);
$pdf->MultiCell(170, 6, $help_description, 0, 'L');
$pdf->Ln($helpHeight + 25);

// Tabela de informações - CENTRALIZADA
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetFillColor(200, 200, 200);

$labelWidth = 45;
$valueWidth = 35;
$totalWidth = ($labelWidth + $valueWidth) * 2 + 10;
$startX = ($pdf->getPageWidth() - $totalWidth) / 2;

// Primeira linha - CENTRALIZADA
$pdf->SetX($startX);
$pdf->Cell($labelWidth, 7, 'Estado do Serviço', 1, 0, 'C', 1);
$pdf->SetFillColor(255, 255, 255);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell($valueWidth, 7, $service_status, 1, 0, 'C');
$pdf->Cell(10, 7, '', 0, 0);
$pdf->SetFillColor(200, 200, 200);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell($labelWidth, 7, 'Lista de Problemas', 1, 0, 'C', 1);
$pdf->SetFillColor(255, 255, 255);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell($valueWidth, 7, $lista_problemas, 1, 1, 'C');
$pdf->Ln(2);

// Segunda linha - CENTRALIZADA
$pdf->SetX($startX);
$pdf->SetFillColor(200, 200, 200);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell($labelWidth, 7, 'Condições', 1, 0, 'C', 1);
$pdf->SetFillColor(255, 255, 255);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell($valueWidth, 7, $conditions, 1, 0, 'C');
$pdf->Cell(10, 7, '', 0, 0);
$pdf->SetFillColor(200, 200, 200);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell($labelWidth, 7, 'Intervenção', 1, 0, 'C', 1);
$pdf->SetFillColor(255, 255, 255);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell($valueWidth, 7, $intervencao, 1, 1, 'C');
$pdf->Ln(2);

// Terceira linha - CENTRALIZADA
$pdf->SetX($startX);
$pdf->SetFillColor(200, 200, 200);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell($labelWidth, 7, 'Início do Contrato', 1, 0, 'C', 1);
$pdf->SetFillColor(255, 255, 255);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell($valueWidth, 7, $temContrato ? date('d-m-Y', strtotime($companyData['InicioContrato'])) : 'Sem Contrato', 1, 0, 'C');
$pdf->Cell(10, 7, '', 0, 0);
$pdf->SetFillColor(200, 200, 200);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell($labelWidth, 7, 'Fim do Contrato', 1, 0, 'C', 1);
$pdf->SetFillColor(255, 255, 255);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell($valueWidth, 7, $temContrato ? date('d-m-Y', strtotime($companyData['FimContrato'])) : 'Sem Contrato', 1, 1, 'C');
$pdf->Ln(2);

// Quarta linha - CENTRALIZADA
$pdf->SetX($startX);
$pdf->SetFillColor(200, 200, 200);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell($labelWidth, 7, 'Responsável', 1, 0, 'C', 1);
$pdf->SetFillColor(255, 255, 255);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell($valueWidth, 7, $companyData['Responsavel'], 1, 0, 'C');
$pdf->Cell(10, 7, '', 0, 0);
$pdf->SetFillColor(200, 200, 200);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell($labelWidth, 7, 'Horas Realizadas', 1, 0, 'C', 1);
$pdf->SetFillColor(255, 255, 255);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell($valueWidth, 7, substr($hours_spent, 0, 5), 1, 1, 'C');
$pdf->Ln(2);

// Quinta linha - CENTRALIZADA
$pdf->SetX($startX);
$pdf->SetFillColor(200, 200, 200);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell($labelWidth, 7, 'Técnico', 1, 0, 'C', 1);
$pdf->SetFillColor(255, 255, 255);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell($valueWidth, 7, $tecnico, 1, 0, 'C');
$pdf->Cell(10, 7, '', 0, 0);
$pdf->SetFillColor(200, 200, 200);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell($labelWidth, 7, 'Email do Técnico', 1, 0, 'C', 1);
$pdf->SetFillColor(255, 255, 255);
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell($valueWidth, 7, $email_tecnico, 1, 1, 'C');
$pdf->Ln(10);

// Assinatura
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 7, 'Assinatura do Cliente', 0, 1, 'C');
$pdf->SetFillColor(255, 255, 255);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 7, '____________________________________________', 0, 1, 'C');
$pdf->Ln(2);

// Observações
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 7, 'Observações do cliente', 0, 1, 'C');
$pdf->SetFillColor(255, 255, 255);
$pdf->SetFont('helvetica', '', 10);
$pdf->MultiCell(0, 7, '________________________________________________________', 0, 'C');
$pdf->MultiCell(0, 7, '________________________________________________________', 0, 'C');
$pdf->MultiCell(0, 7, '________________________________________________________', 0, 'C');
$pdf->Ln(3);

// Saída do PDF
$pdfFileName = 'Previsualizacao_Guia_Assistencia_' . date('YmdHis') . '.pdf';

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $pdfFileName . '"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

$pdf->Output($pdfFileName, 'I');
exit();