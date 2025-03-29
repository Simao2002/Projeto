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
    
    // Buscar os dados da assistência incluindo numero_guia
    $sql = "SELECT assists.id, assists.numero_guia, clientes.company, clientes.morada, clientes.PostalCode, clientes.Localidade, 
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
        // Verificar se numero_guia existe, caso contrário gerar um temporário
        if (!isset($row['numero_guia']) || empty($row['numero_guia'])) {
            $date = new DateTime($row['created_at']);
            $yearMonth = $date->format('Ym');
            $row['numero_guia'] = $yearMonth . '-' . str_pad($row['id'], 2, '0', STR_PAD_LEFT);
        }

        // Gerar o PDF
        $temContrato = ($row['InicioContrato'] != '0000-00-00' && $row['FimContrato'] != '0000-00-00');
        
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        $pdf->SetMargins(20, 10, 15);
        $pdf->SetAutoPageBreak(true, 25);
        
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Sistema de Assistência');
        $pdf->SetTitle('Guia de Assistência ' . $row['numero_guia']);
        $pdf->SetSubject('Guia de Assistência Técnica');
        
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Limpar qualquer saída anterior
        ob_clean();
        
        $pdf->AddPage();
        $pdf->AddFont('arial', '', 'TCPDF-main\fonts\arial.php');
        $pdf->SetFont('Arial', '', 10);

        // Logo
        $imageWidth = 35;
        $pageWidth = $pdf->getPageWidth() - $pdf->getMargins()['left'] - $pdf->getMargins()['right'];
        $imageX = ($pageWidth - $imageWidth) / 2 - 35;
        $pdf->Image('pcci.jpg', $imageX, 30, $imageWidth);

        // Título
        $pdf->SetXY(120, 30);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->Cell(70, 10, 'Guia de Assistência N. ' . $row['numero_guia'], 1, 1, 'C', 1); 
        
        $pdf->Ln(15);
// Limpa qualquer output anterior
ob_clean();

// Estilo CSS com espaçamento ajustado
$tbl = '
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
        padding: 2px 2px 2px 3mm; /* Espaço à esquerda aumentado */
    }
    .cliente-value {
        background-color: #ffffff;
        border: 1px solid #000;
        width: 50mm;
        padding: 2px 2px 2px 2mm; /* Espaço à esquerda */
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
        <td class="cliente-value"> '.htmlspecialchars($row['company']).'</td>
    </tr>
    <tr>
        <td class="cliente-label">Morada:</td>
        <td class="cliente-value"> '.htmlspecialchars($row['morada']).'</td>
    </tr>
    <tr>
        <td class="cliente-final" colspan="2">'.htmlspecialchars($row['PostalCode'].' '.$row['Localidade']).'</td>
    </tr>
</table>
';

// Posiciona a tabela corretamente
$pdf->SetXY(120, 45);
$pdf->writeHTML($tbl, true, false, false, false, '');

// Ajusta a posição Y para o próximo elemento
$currentY = $pdf->GetY();
$pdf->SetY($currentY + 5);


$pdf->Ln(9); // Adiciona um espaço após a caixa

        // Descrição do Problema
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->Cell(170, 7, 'Descrição do Problema', 0, 1, 'C', 1);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetFont('Arial', '', 10);

        $problem = $row['problem'];
        $problemHeight = $pdf->getStringHeight(170, $problem);
        $pdf->Rect(20, $pdf->GetY(), 170, $problemHeight + 15);
        $pdf->MultiCell(170, 6, $problem, 0, 'L');
        $pdf->Ln($problemHeight + 12);

        // Descrição do Serviço
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->Cell(170, 7, 'Descrição do Serviço Efectuado', 0, 1, 'C', 1);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetFont('Arial', '', 10);

        $helpDescription = $row['help_description'];
        $helpHeight = $pdf->getStringHeight(170, $helpDescription);
        $pdf->Rect(20, $pdf->GetY(), 170, $helpHeight + 25);
        $pdf->MultiCell(170, 6, $helpDescription, 0, 'L');
        $pdf->Ln($helpHeight + 25);

        // Tabela de informações - CENTRALIZADA
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(200, 200, 200);

        $labelWidth = 45;
        $valueWidth = 35;
        $totalWidth = ($labelWidth + $valueWidth) * 2 + 10;
        $startX = ($pdf->getPageWidth() - $totalWidth) / 2;

        // Primeira linha - CENTRALIZADA
        $pdf->SetX($startX);
        $pdf->Cell($labelWidth, 7, 'Estado do Serviço', 1, 0, 'C', 1);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell($valueWidth, 7, $row['service_status'], 1, 0, 'C');
        $pdf->Cell(10, 7, '', 0, 0);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell($labelWidth, 7, 'Lista de Problemas', 1, 0, 'C', 1);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell($valueWidth, 7, $row['lista_problemas'], 1, 1, 'C');
        $pdf->Ln(2);

        // Segunda linha - CENTRALIZADA
        $pdf->SetX($startX);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell($labelWidth, 7, 'Condições', 1, 0, 'C', 1);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell($valueWidth, 7, $row['conditions'], 1, 0, 'C');
        $pdf->Cell(10, 7, '', 0, 0);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell($labelWidth, 7, 'Intervenção', 1, 0, 'C', 1);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell($valueWidth, 7, $row['intervencao'], 1, 1, 'C');
        $pdf->Ln(2);

        // Terceira linha - CENTRALIZADA
        $pdf->SetX($startX);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell($labelWidth, 7, 'Início do Contrato', 1, 0, 'C', 1);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell($valueWidth, 7, $temContrato ? date('d-m-Y', strtotime($row['InicioContrato'])) : 'Sem Contrato', 1, 0, 'C');
        $pdf->Cell(10, 7, '', 0, 0);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell($labelWidth, 7, 'Fim do Contrato', 1, 0, 'C', 1);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell($valueWidth, 7, $temContrato ? date('d-m-Y', strtotime($row['FimContrato'])) : 'Sem Contrato', 1, 1, 'C');
        $pdf->Ln(2);

        // Quarta linha - CENTRALIZADA
        $pdf->SetX($startX);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell($labelWidth, 7, 'Responsável', 1, 0, 'C', 1);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell($valueWidth, 7, $row['responsavel'], 1, 0, 'C');
        $pdf->Cell(10, 7, '', 0, 0);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell($labelWidth, 7, 'Horas Realizadas', 1, 0, 'C', 1);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell($valueWidth, 7, substr($row['hours_spent'], 0, 5), 1, 1, 'C');
        $pdf->Ln(2);

        // Quinta linha - CENTRALIZADA
        $pdf->SetX($startX);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell($labelWidth, 7, 'Técnico', 1, 0, 'C', 1);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell($valueWidth, 7, $row['Tecnico'], 1, 0, 'C');
        $pdf->Cell(10, 7, '', 0, 0);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell($labelWidth, 7, 'Email do Técnico', 1, 0, 'C', 1);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell($valueWidth, 7, $row['EmailTecnico'], 1, 1, 'C');
        $pdf->Ln(10);


        // Assinatura
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 7, 'Assinatura do Cliente', 0, 1, 'C');
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 7, '____________________________________________', 0, 1, 'C');
        $pdf->Ln(2);

        // Observações
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 7, 'Observações do cliente', 0, 1, 'C');
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetFont('Arial', '', 10);
        $pdf->MultiCell(0, 7, '________________________________________________________', 0, 'C');
        $pdf->MultiCell(0, 7, '________________________________________________________', 0, 'C');
        $pdf->MultiCell(0, 7, '________________________________________________________', 0, 'C');
        $pdf->Ln(3);

        $pdfFileName = 'Guia_Assistencia_' . $row['numero_guia'] . '_' . str_replace(' ', '_', $row['company']) . '.pdf';
        
        
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
            $numeroGuia = $row['numero_guia'];
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