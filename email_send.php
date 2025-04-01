<?php
session_start();

// Ensure we only return JSON
header('Content-Type: application/json');

if (!isset($_SESSION['id']) || !isset($_SESSION['user_name'])) {
    exit(json_encode(['success' => false, 'message' => 'Não autorizado']));
}

require 'vendor/autoload.php';
require_once('TCPDF-main/tcpdf.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Error configuration to prevent HTML output
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    $sname = "localhost";
    $uname = "root";
    $password = "";
    $db_name = "test_db";

    $conn = mysqli_connect($sname, $uname, $password, $db_name);

    if (!$conn) {
        throw new Exception('Connection failed: ' . mysqli_connect_error());
    }

    mysqli_set_charset($conn, "utf8");

    // Get form data
    $company_id = $_POST['company'] ?? null;
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

    if (!$company_id) {
        throw new Exception('Empresa não selecionada');
    }

    // 1. First register in the database
    function gerarNumeroGuia($conn, $data_assistencia) {
        $date = new DateTime($data_assistencia);
        $yearMonth = $date->format('Ym');
        
        $sql = "SELECT COUNT(*) as count FROM assists 
                WHERE DATE_FORMAT(created_at, '%Y%m') = '$yearMonth'";
        $result = mysqli_query($conn, $sql);
        $row = mysqli_fetch_assoc($result);
        
        return $yearMonth . '-' . str_pad($row['count'], 2, '0', STR_PAD_LEFT);
    }

    $numero_guia = gerarNumeroGuia($conn, $data_assistencia);

    $sql_insert = "INSERT INTO assists (numero_guia, company_id, problem, help_description, hours_spent, service_status, conditions, lista_problemas, intervencao, Tecnico, EmailTecnico, created_at) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql_insert);
    mysqli_stmt_bind_param($stmt, "sissssssssss", 
        $numero_guia, 
        $company_id, 
        $problem, 
        $help_description, 
        $hours_spent, 
        $service_status, 
        $conditions, 
        $lista_problemas, 
        $intervencao, 
        $tecnico, 
        $email_tecnico, 
        $data_assistencia
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Erro ao registrar assistência: ' . mysqli_error($conn));
    }

    $assist_id = mysqli_insert_id($conn);

    // If "Com Contrato", update hours balance
    if ($conditions === "Com Contrato") {
        $sql_update = "UPDATE clientes SET SaldoHoras = SEC_TO_TIME(TIME_TO_SEC(SaldoHoras) - TIME_TO_SEC(?)) WHERE id = ?";
        $stmt_update = mysqli_prepare($conn, $sql_update);
        mysqli_stmt_bind_param($stmt_update, "si", $hours_spent, $company_id);
        mysqli_stmt_execute($stmt_update);
        mysqli_stmt_close($stmt_update);
    }

    // 2. Get company data
    $sql_company = "SELECT company, email, morada, PostalCode, Localidade, InicioContrato, FimContrato, responsavel 
                    FROM clientes 
                    WHERE id = ?";
    $stmt_company = mysqli_prepare($conn, $sql_company);
    mysqli_stmt_bind_param($stmt_company, "i", $company_id);
    mysqli_stmt_execute($stmt_company);
    $result_company = mysqli_stmt_get_result($stmt_company);
    $company_data = mysqli_fetch_assoc($result_company);

    if (!$company_data) {
        throw new Exception('Dados da empresa não encontrados');
    }

    // 3. Generate PDF
    $temContrato = ($company_data['InicioContrato'] != '0000-00-00' && $company_data['FimContrato'] != '0000-00-00');

    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    $pdf->SetMargins(20, 10, 15);
    $pdf->SetAutoPageBreak(true, 25);

    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Sistema de Assistência');
    $pdf->SetTitle('Guia de Assistência ' . $numero_guia);
    $pdf->SetSubject('Guia de Assistência Técnica');

    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 10);

    // Logo
    $imageWidth = 35;
    $pageWidth = $pdf->getPageWidth() - $pdf->getMargins()['left'] - $pdf->getMargins()['right'];
    $imageX = ($pageWidth - $imageWidth) / 2 - 35;
    $pdf->Image('pcci.jpg', $imageX, 30, $imageWidth);

    // Title
    $pdf->SetXY(120, 30);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetFillColor(200, 200, 200);
    $pdf->Cell(70, 10, 'Guia de Assistência N. ' . $numero_guia, 1, 1, 'C', 1); 

    $pdf->Ln(15);

    // Client information table
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
        <td class="cliente-value">{$company_data['company']}</td>
    </tr>
    <tr>
        <td class="cliente-label">Morada:</td>
        <td class="cliente-value">{$company_data['morada']}</td>
    </tr>
    <tr>
        <td class="cliente-final" colspan="2">{$company_data['PostalCode']} {$company_data['Localidade']}</td>
    </tr>
</table>
EOD;

    $pdf->SetXY(120, 45);
    $pdf->writeHTML($tbl, true, false, false, false, '');

    $currentY = $pdf->GetY();
    $pdf->SetY($currentY + 5);

    $pdf->Ln(9);

    // Problem Description
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(200, 200, 200);
    $pdf->Cell(170, 7, 'Descrição do Problema', 0, 1, 'C', 1);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetFont('helvetica', '', 10);

    $problemHeight = $pdf->getStringHeight(170, $problem);
    $pdf->Rect(20, $pdf->GetY(), 170, $problemHeight + 15);
    $pdf->MultiCell(170, 6, $problem, 0, 'L');
    $pdf->Ln($problemHeight + 12);

    // Service Description
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(200, 200, 200);
    $pdf->Cell(170, 7, 'Descrição do Serviço Efectuado', 0, 1, 'C', 1);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetFont('helvetica', '', 10);

    $helpHeight = $pdf->getStringHeight(170, $help_description);
    $pdf->Rect(20, $pdf->GetY(), 170, $helpHeight + 25);
    $pdf->MultiCell(170, 6, $help_description, 0, 'L');
    $pdf->Ln($helpHeight + 25);

    // Information table - CENTERED
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(200, 200, 200);

    $labelWidth = 45;
    $valueWidth = 35;
    $totalWidth = ($labelWidth + $valueWidth) * 2 + 10;
    $startX = ($pdf->getPageWidth() - $totalWidth) / 2;

    // First row - CENTERED
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

    // Second row - CENTERED
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

    // Third row - CENTERED
    $pdf->SetX($startX);
    $pdf->SetFillColor(200, 200, 200);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell($labelWidth, 7, 'Início do Contrato', 1, 0, 'C', 1);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell($valueWidth, 7, $temContrato ? date('d-m-Y', strtotime($company_data['InicioContrato'])) : 'Sem Contrato', 1, 0, 'C');
    $pdf->Cell(10, 7, '', 0, 0);
    $pdf->SetFillColor(200, 200, 200);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell($labelWidth, 7, 'Fim do Contrato', 1, 0, 'C', 1);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell($valueWidth, 7, $temContrato ? date('d-m-Y', strtotime($company_data['FimContrato'])) : 'Sem Contrato', 1, 1, 'C');
    $pdf->Ln(2);

    // Fourth row - CENTERED
    $pdf->SetX($startX);
    $pdf->SetFillColor(200, 200, 200);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell($labelWidth, 7, 'Responsável', 1, 0, 'C', 1);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell($valueWidth, 7, $company_data['responsavel'], 1, 0, 'C');
    $pdf->Cell(10, 7, '', 0, 0);
    $pdf->SetFillColor(200, 200, 200);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell($labelWidth, 7, 'Horas Realizadas', 1, 0, 'C', 1);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell($valueWidth, 7, substr($hours_spent, 0, 5), 1, 1, 'C');
    $pdf->Ln(2);

    // Fifth row - CENTERED
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

    // Signature
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 7, 'Assinatura do Cliente', 0, 1, 'C');
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 7, '____________________________________________', 0, 1, 'C');
    $pdf->Ln(2);

    // Observations
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 7, 'Observações do cliente', 0, 1, 'C');
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->MultiCell(0, 7, '________________________________________________________', 0, 'C');
    $pdf->MultiCell(0, 7, '________________________________________________________', 0, 'C');
    $pdf->MultiCell(0, 7, '________________________________________________________', 0, 'C');
    $pdf->Ln(3);

    // Generate unique filename for email
    $pdfFileName = 'Relatorio_Assistencia_' . date('Ymd_His') . '_' . str_replace(' ', '_', $company_data['company']) . '.pdf';
    $tempPdfPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $pdfFileName;
    $pdf->Output($tempPdfPath, 'F');

    // 4. Send email
    $mail = new PHPMailer(true);

    try {
        // SMTP configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'simaopiresfontes@gmail.com';
        $mail->Password = 'vonk otzw kvvu kbrx';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Sender and recipient
        $mail->setFrom($email_tecnico, $tecnico);
        $mail->addBCC('shauinho9@gmail.com', 'Registro Interno');
        $mail->addAddress($company_data['email'], $company_data['company']);
        
        // Attach PDF
        $mail->addAttachment($tempPdfPath, $pdfFileName);
        
        // Email content
        $mail->isHTML(false);
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->setLanguage('pt');

        // Prepare data for message
        $dataAssistencia = date('d-m-Y', strtotime($data_assistencia));
        $horasAssistencia = substr($hours_spent, 0, 5);

        // Email subject
        $subject = "Folha de Assistência Técnica #$numero_guia - " . $company_data['company'];
        $mail->Subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        
        // Message body
        $message = "Estimado cliente,\n\n";
        $message .= "No seguimento da intervenção técnica efetuada, enviamos detalhes para seu conhecimento e concordância:\n\n";
        $message .= "Data: $dataAssistencia            Total Horas: $horasAssistencia\n";
        $message .= "Número de folha de assistência técnica associada ao serviço: $numero_guia\n\n";
        $message .= "Descrição do serviço:\n";
        $message .= "$help_description\n\n";
        $message .= "Solicitamos resposta com aprovação dos serviços mencionados.\n";
        $message .= "Após receção da folha de assistência técnica, agradecemos que a mesma nos seja devolvida pela mesma via devidamente assinada.\n\n";
        $message .= "Com os melhores cumprimentos,\n\n";
        $message .= "PCCI, LDA\n";
        
        $mail->Body = mb_convert_encoding($message, 'UTF-8');
        
        $mail->send();
        
        unlink($tempPdfPath);
        
        echo json_encode([
            'success' => true,
            'message' => 'Email enviado com sucesso',
            'email' => $company_data['email'],
            'assist_id' => $assist_id,
            'pdf_filename' => $pdfFileName
        ]);

    } catch (Exception $e) {
        if (file_exists($tempPdfPath)) {
            unlink($tempPdfPath);
        }
        
        throw new Exception('Erro PHPMailer: ' . $e->getMessage());
    }

} catch (Exception $e) {
    error_log('Erro no email_send.php: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Erro: ' . $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        mysqli_close($conn);
    }
}
?>