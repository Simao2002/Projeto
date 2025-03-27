<?php
session_start();

if (!isset($_SESSION['id']) || !isset($_SESSION['user_name'])) {
    header("Location: index.php");
    exit();
}

require 'vendor/autoload.php';
require_once('TCPDF-main\tcpdf.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$sname = "localhost";
$uname = "root";
$password = "";
$db_name = "test_db";

$conn = mysqli_connect($sname, $uname, $password, $db_name);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8");

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Buscar os dados da assistência
    $sql = "SELECT assists.id, clientes.company, clientes.morada, clientes.PostalCode, clientes.Localidade, 
                   clientes.InicioContrato, clientes.FimContrato, clientes.responsavel, clientes.email,
                   assists.problem, assists.help_description, assists.hours_spent, assists.service_status, 
                   assists.conditions, assists.lista_problemas, assists.intervencao, 
                   assists.Tecnico, assists.EmailTecnico, assists.created_at 
            FROM assists 
            JOIN clientes ON assists.company_id = clientes.id 
            WHERE assists.id = $id";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);

    if ($row) {
        // Gerar o PDF
        $temContrato = ($row['InicioContrato'] != '0000-00-00' && $row['FimContrato'] != '0000-00-00');
        
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        $temContrato = ($row['InicioContrato'] != '0000-00-00' && $row['FimContrato'] != '0000-00-00');
        
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        $pdf->SetMargins(20, 10, 15);
        $pdf->SetAutoPageBreak(true, 25);
        
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Sistema de Assistência');
        $pdf->SetTitle('Guia de Assistência ' . $row['id']);
        $pdf->SetSubject('Guia de Assistência Técnica');
        
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 12);

        // Logo
        $imageWidth = 35;
        $pageWidth = $pdf->getPageWidth() - $pdf->getMargins()['left'] - $pdf->getMargins()['right'];
        $imageX = ($pageWidth - $imageWidth) / 2 - 35;
        $pdf->Image('63907485-7886-4fc0-8a3a-43b4b5ea7a8c.jpg', $imageX, 30, $imageWidth);

        // Título
        $pdf->SetXY(20, 15);
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Guia de Assistência N.: ' . $row['id'], 0, 1, 'C');
        $pdf->Ln(10);  

        $pdf->SetY($pdf->GetY() + 5);

        // Seção Cliente com Código Postal e Localidade - CAIXA REDUZIDA
        $pdf->SetXY(120, 40);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->Cell(60, 7, 'Cliente', 0, 1, 'C', 1);

        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetX(120);
        $pdf->Rect(120, $pdf->GetY(), 60, 18); // Altura reduzida para 18mm

        $pdf->MultiCell(60, 5, 'Empresa: ' . $row['company'], 0, 'L'); // Altura da linha reduzida para 5
        $pdf->SetX(120);
        $pdf->MultiCell(60, 5, 'Morada: ' . $row['morada'], 0, 'L');
        $pdf->SetX(120);
        // Código postal e localidade juntos
        $pdf->MultiCell(60, 5, $row['PostalCode'] . ' ' . $row['Localidade'], 0, 'L');

        $pdf->Ln(8);

        // Restante do código permanece igual...
        // Descrição do Problema
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->Cell(170, 7, 'Descrição do Problema', 0, 1, 'C', 1);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetFont('helvetica', '', 9);

        $problem = $row['problem'];
        $problemHeight = $pdf->getStringHeight(170, $problem);
        $pdf->Rect(20, $pdf->GetY(), 170, $problemHeight + 15);
        $pdf->MultiCell(170, 6, $problem, 0, 'L');
        $pdf->Ln($problemHeight + 10);

        // Descrição do Serviço
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->Cell(170, 7, 'Descrição do Serviço Efectuado', 0, 1, 'C', 1);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetFont('helvetica', '', 9);

        $helpDescription = $row['help_description'];
        $helpHeight = $pdf->getStringHeight(170, $helpDescription);
        $pdf->Rect(20, $pdf->GetY(), 170, $helpHeight + 15);
        $pdf->MultiCell(170, 6, $helpDescription, 0, 'L');
        $pdf->Ln($helpHeight + 12);

        // Tabela de informações
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetFillColor(200, 200, 200);

        $labelWidth = 45;
        $valueWidth = 35;
        $spacer = 5;

        // Primeira linha
        $pdf->SetX(20);
        $pdf->Cell($labelWidth, 7, 'Estado do Serviço', 1, 0, 'C', 1);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell($valueWidth, 7, $row['service_status'], 1, 0, 'C');
        $pdf->Cell($spacer, 7, '', 0, 0);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell($labelWidth, 7, 'Lista de Problemas', 1, 0, 'C', 1);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell($valueWidth, 7, $row['lista_problemas'], 1, 1, 'C');
        $pdf->Ln(2);

        // Segunda linha
        $pdf->SetX(20);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell($labelWidth, 7, 'Condições', 1, 0, 'C', 1);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell($valueWidth, 7, $row['conditions'], 1, 0, 'C');
        $pdf->Cell($spacer, 7, '', 0, 0);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell($labelWidth, 7, 'Intervenção', 1, 0, 'C', 1);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell($valueWidth, 7, $row['intervencao'], 1, 1, 'C');
        $pdf->Ln(2);

        // Terceira linha
        $pdf->SetX(20);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell($labelWidth, 7, 'Início do Contrato', 1, 0, 'C', 1);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell($valueWidth, 7, $temContrato ? date('d-m-Y', strtotime($row['InicioContrato'])) : 'Sem Contrato', 1, 0, 'C');
        $pdf->Cell($spacer, 7, '', 0, 0);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell($labelWidth, 7, 'Fim do Contrato', 1, 0, 'C', 1);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell($valueWidth, 7, $temContrato ? date('d-m-Y', strtotime($row['FimContrato'])) : 'Sem Contrato', 1, 1, 'C');
        $pdf->Ln(2);

        // Quarta linha
        $pdf->SetX(20);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell($labelWidth, 7, 'Responsável', 1, 0, 'C', 1);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell($valueWidth, 7, $row['responsavel'], 1, 0, 'C');
        $pdf->Cell($spacer, 7, '', 0, 0);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell($labelWidth, 7, 'Horas Realizadas', 1, 0, 'C', 1);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell($valueWidth, 7, substr($row['hours_spent'], 0, 5), 1, 1, 'C');
        $pdf->Ln(2);

        // Quinta linha
        $pdf->SetX(20);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell($labelWidth, 7, 'Técnico', 1, 0, 'C', 1);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell($valueWidth, 7, $row['Tecnico'], 1, 0, 'C');
        $pdf->Cell($spacer, 7, '', 0, 0);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell($labelWidth, 7, 'Email do Técnico', 1, 0, 'C', 1);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell($valueWidth, 7, $row['EmailTecnico'], 1, 1, 'C');
        $pdf->Ln(6);

        // Assinatura
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(0, 7, 'Assinatura do Cliente', 0, 1, 'C');
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(0, 7, '____________________________________________', 0, 1, 'C');
        $pdf->Ln(2);

        // Observações
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(0, 7, 'Observações do cliente', 0, 1, 'C');
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->MultiCell(0, 7, '________________________________________________________', 0, 'C');
        $pdf->MultiCell(0, 7, '________________________________________________________', 0, 'C');
        $pdf->MultiCell(0, 7, '________________________________________________________', 0, 'C');
        $pdf->Ln(3);
        
        $pdfFileName = 'Guia_Assistencia_' . $row['id'] . '_' . str_replace(' ', '_', $row['company']) . '.pdf';
        
        // Salvar o PDF temporariamente
        $tempPdfPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $pdfFileName;
        $pdf->Output($tempPdfPath, 'F');
        
        // Configurar PHPMailer
        $mail = new PHPMailer(true);
        
try {
    // Configurações do servidor SMTP (ajuste conforme seu provedor)
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';  // Seu servidor SMTP
    $mail->SMTPAuth = true;
    $mail->Username = 'simaopiresfontes@gmail.com'; // Seu email
    $mail->Password = 'vonk otzw kvvu kbrx';      // Sua senha
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // ou ENCRYPTION_SMTPS
    $mail->Port = 587; // ou 465 para SSL
    
    // Remetente e destinatário
    $mail->setFrom($row['EmailTecnico'], $row['Tecnico']);
    $mail->addBCC('shauinho9@gmail.com', 'Registro Interno');
    $mail->addAddress($row['email'], $row['company']);
    
    // Anexar o PDF
    $mail->addAttachment($tempPdfPath, $pdfFileName);
    
    // Conteúdo do email
    $mail->isHTML(false);
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';
    $mail->setLanguage('pt');

    // Preparar os dados para a mensagem
    $dataAssistencia = date('d-m-Y', strtotime($row['created_at']));
    $horasAssistencia = substr($row['hours_spent'], 0, 5);
    $numeroGuia = $row['id'];
    $descricaoServico = $row['help_description'];

    // Assunto do email
    $subject = "Folha de Assistência Técnica #$numeroGuia - " . $row['company'];
    $mail->Subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    
    // Corpo da mensagem conforme solicitado
    $message = "Estimado cliente,\n\n";
    $message .= "No seguimento da intervenção técnica efetuada, enviamos detalhes para seu conhecimento e concordância:\n\n";
    $message .= "Data: $dataAssistencia            Total Horas: $horasAssistencia\n";
    $message .= "Número de folha de assistência técnica associada ao serviço: $numeroGuia\n\n";
    $message .= "Descrição do serviço:\n";
    $message .= "$descricaoServico\n\n";
    $message .= "Solicitamos resposta com aprovação dos serviços mencionados.\n";
    $message .= "Após receção da folha de assistência técnica, agradecemos que a mesma nos seja devolvida pela mesma via devidamente assinada.\n\n";
    $message .= "Com os melhores cumprimentos,\n\n";
    $message .= "PCCI, LDA\n";
    
    $mail->Body = mb_convert_encoding($message, 'UTF-8');
    
    $mail->send();
    
    // Apagar o arquivo temporário
    unlink($tempPdfPath);
    
    echo "<script>alert('Email enviado com sucesso para {$row['email']}!'); window.location.href = 'assist.php';</script>";
} catch (Exception $e) {
    // Apagar o arquivo temporário em caso de erro
    if (file_exists($tempPdfPath)) {
        unlink($tempPdfPath);
    }
    
    echo "<script>alert('Erro ao enviar email: {$mail->ErrorInfo}'); window.location.href = 'assist.php';</script>";
}
} else {
    echo "<script>alert('Assistência não encontrada.'); window.location.href = 'assist.php';</script>";
}
} else {
    echo "<script>alert('Pedido inválido.'); window.location.href = 'assist.php';</script>";
}

mysqli_close($conn);
?>