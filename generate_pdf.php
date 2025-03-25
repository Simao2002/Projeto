<?php
session_start();

if (!isset($_SESSION['id']) || !isset($_SESSION['user_name'])) {
    header("Location: index.php");
    exit();
}

// Carrega a biblioteca TCPDF
require_once('TCPDF-main\tcpdf.php');

// Configuração da conexão com o banco de dados
$sname = "localhost";
$uname = "root";
$password = "";
$db_name = "test_db";

$conn = mysqli_connect($sname, $uname, $password, $db_name);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Define o charset para UTF-8
mysqli_set_charset($conn, "utf8");

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "SELECT assists.id, clientes.company, clientes.email, clientes.morada, clientes.InicioContrato, clientes.FimContrato, clientes.responsavel, 
                   assists.problem, assists.help_description, assists.hours_spent, assists.service_status, assists.conditions, 
                   assists.lista_problemas, assists.intervencao, assists.Tecnico, assists.EmailTecnico, assists.created_at 
            FROM assists 
            JOIN clientes ON assists.company_id = clientes.id 
            WHERE assists.id = $id";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);

    if ($row) {
        // Cria novo documento TCPDF
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Configura informações do documento
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Sistema de Assistência');
        $pdf->SetTitle('Guia de Assistência ' . $row['id']);
        $pdf->SetSubject('Guia de Assistência Técnica');
        
        // Remove cabeçalho e rodapé padrão
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Adiciona uma página
        $pdf->AddPage();
        
        // Configura a fonte padrão
        $pdf->SetFont('helvetica', '', 16);

        // Adiciona o logo
        $imageWidth = 45;
        $pageWidth = $pdf->getPageWidth() - $pdf->getMargins()['left'] - $pdf->getMargins()['right'];
        $imageX = ($pageWidth - $imageWidth) / 2 - 45;
        $pdf->Image('63907485-7886-4fc0-8a3a-43b4b5ea7a8c.jpg', $imageX, 5, 45);

        // Título do documento
        $pdf->SetXY(60, 10);
        $pdf->Cell(0, 10, 'Guia de Assistência N.: ' . $row['id'], 0, 1, 'C');
        $pdf->Ln(5);

        // Seção Cliente
        $pdf->SetXY(120, 25);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->Cell(70, 8, 'Cliente', 0, 1, 'C', 1);

        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetX(120);
        $pdf->Rect(120, $pdf->GetY(), 70, 18);

        $pdf->MultiCell(70, 6, 'Empresa: ' . $row['company'], 0, 'L');
        $pdf->SetX(120);
        $pdf->MultiCell(70, 6, 'Email: ' . $row['email'], 0, 'L');
        $pdf->SetX(120);
        $pdf->MultiCell(70, 6, 'Morada: ' . $row['morada'], 0, 'L');

        $pdf->Ln(10);

        // Descrição do Problema
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->Cell(0, 8, 'Descrição do Problema', 0, 1, 'C', 1);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetFont('helvetica', '', 9);

        $problem = $row['problem'];
        $problemHeight = $pdf->getStringHeight(190, $problem);
        $pdf->Rect(10, $pdf->GetY(), 190, $problemHeight + 30);
        $pdf->MultiCell(190, 8, $problem, 0, 'L');
        $pdf->Ln($problemHeight + 25);

        // Descrição do Serviço
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->Cell(0, 8, 'Descrição do Serviço Efectuado', 0, 1, 'C', 1);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetFont('helvetica', '', 9);

        $helpDescription = $row['help_description'];
        $helpHeight = $pdf->getStringHeight(190, $helpDescription);
        $pdf->Rect(10, $pdf->GetY(), 190, $helpHeight + 30);
        $pdf->MultiCell(190, 8, $helpDescription, 0, 'L');
        $pdf->Ln($helpHeight + 30);

        // Tabela de informações
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(200, 200, 200);

        // Primeira linha
        $pdf->Cell(50, 8, 'Estado do Serviço', 1, 0, 'C', 1);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(40, 8, $row['service_status'], 1, 0, 'C');
        $pdf->Cell(8, 8, '', 0, 0);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(50, 8, 'Lista de Problemas', 1, 0, 'C', 1);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(40, 8, $row['lista_problemas'], 1, 1, 'C');
        $pdf->Ln(3);

        // Segunda linha
        $pdf->SetFillColor(200, 200, 200);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(50, 8, 'Condições', 1, 0, 'C', 1);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(40, 8, $row['conditions'], 1, 0, 'C');
        $pdf->Cell(8, 8, '', 0, 0);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(50, 8, 'Intervenção', 1, 0, 'C', 1);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(40, 8, $row['intervencao'], 1, 1, 'C');
        $pdf->Ln(3);

        // Terceira linha
        $pdf->SetFillColor(200, 200, 200);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(50, 8, 'Início do Contrato', 1, 0, 'C', 1);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(40, 8, date('d-m-Y', strtotime($row['InicioContrato'])), 1, 0, 'C');
        $pdf->Cell(8, 8, '', 0, 0);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(50, 8, 'Fim do Contrato', 1, 0, 'C', 1);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(40, 8, date('d-m-Y', strtotime($row['FimContrato'])), 1, 1, 'C');
        $pdf->Ln(3);

        // Quarta linha
        $pdf->SetFillColor(200, 200, 200);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(50, 8, 'Responsável', 1, 0, 'C', 1);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(40, 8, $row['responsavel'], 1, 0, 'C');
        $pdf->Cell(8, 8, '', 0, 0);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(50, 8, 'Horas Realizadas', 1, 0, 'C', 1);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(40, 8, substr($row['hours_spent'], 0, 5), 1, 1, 'C');
        $pdf->Ln(3);

        // Quinta linha (Técnico e Email)
        $pdf->SetFillColor(200, 200, 200);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(50, 8, 'Técnico', 1, 0, 'C', 1);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(40, 8, $row['Tecnico'], 1, 0, 'C');
        $pdf->Cell(8, 8, '', 0, 0);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(50, 8, 'Email do Técnico', 1, 0, 'C', 1);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(40, 8, $row['EmailTecnico'], 1, 1, 'C');
        $pdf->Ln(10);

        // Assinatura
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(0, 8, 'Assinatura do Cliente', 0, 1, 'C', 1);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(0, 8, '____________________________________________________________', 0, 1, 'C');
        $pdf->Ln(3);

        // Observações
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(0, 8, 'Observações do cliente', 0, 1, 'C', 1);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->MultiCell(0, 8, '_______________________________________________________________________', 0, 'C');
        $pdf->MultiCell(0, 8, '_______________________________________________________________________', 0, 'C');
        $pdf->MultiCell(0, 8, '_______________________________________________________________________', 0, 'C');
        $pdf->Ln(5);

        // Gera o PDF para download
        $pdfFileName = 'Guia_Assistencia_' . $row['id'] . '_' . str_replace(' ', '_', $row['company']) . '.pdf';
        $pdf->Output($pdfFileName, 'D');
    } else {
        echo "Assistência não encontrada.";
    }
} else {
    echo "Pedido inválido.";
}

mysqli_close($conn);
?>