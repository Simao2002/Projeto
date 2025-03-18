<?php
session_start();

if (!isset($_SESSION['id']) || !isset($_SESSION['user_name'])) {
    header("Location: index.php");
    exit();
}

require('fpdf/fpdf.php'); // Ajuste o caminho para o arquivo FPDF

$sname = "localhost";
$uname = "root";
$password = "";
$db_name = "test_db";

$conn = mysqli_connect($sname, $uname, $password, $db_name);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Fetch assistance data
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "SELECT assists.id, clientes.company, clientes.email, assists.problem, assists.help_description, assists.hours_spent, assists.created_at 
            FROM assists 
            JOIN clientes ON assists.company_id = clientes.id 
            WHERE assists.id = $id";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);

    if ($row) {
        // Create PDF
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);

        // Add image in the top-left corner, aligned with the Cliente section
        $pdf->Image('image001.png', 10, 30, 45); // Ajuste o caminho da imagem e o tamanho

        // Header
        $pdf->SetY(10); // Posiciona o cabeçalho abaixo da imagem
        $pdf->Cell(0, 10, 'Guia de Assistencia N.: ' . $row['id'], 0, 1, 'C');
        $pdf->Ln(10);

        // Cliente Section (top-right corner, moved a bit lower)
        $pdf->SetXY(140, 30); // Ajustei o valor de Y para 30 (mais abaixo)
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetFillColor(200, 200, 200); // Cinza claro
        $pdf->Cell(50, 10, 'Cliente', 0, 1, 'C', 1); // Fundo cinza apenas no título
        $pdf->SetFont('Arial', '', 12);
        $pdf->SetX(140); // Alinha o texto à direita
        $pdf->Rect(140, $pdf->GetY(), 50, 30); // Borda preta ao redor da seção do cliente
        $pdf->Cell(50, 10, 'Empresa: ' . $row['company'], 0, 1, 'C');
        $pdf->SetX(140);
        $pdf->Cell(50, 10, 'Email: ' . $row['email'], 0, 1, 'C');
        $pdf->Ln(20); // Ajusta o espaçamento após o campo do cliente

        // Descrição do Problema
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetFillColor(200, 200, 200); // Cinza claro
        $pdf->Cell(0, 10, 'Descricao do Problema', 0, 1, 'C', 1); // Fundo cinza apenas no título
        $pdf->SetFillColor(255, 255, 255); // Restaura o fundo para branco
        $pdf->SetFont('Arial', '', 12);

        // Calcular a altura manualmente
        $problemLines = ceil($pdf->GetStringWidth($row['problem']) / 190); // Número de linhas necessárias
        $problemHeight = $problemLines * 10; // Altura baseada no número de linhas
        $pdf->Rect(10, $pdf->GetY(), 190, $problemHeight + 10); // Borda preta ao redor da seção
        $pdf->MultiCell(190, 10, $row['problem'], 0, 'L'); // Centralizado e sem fundo cinza
        $pdf->Ln(15); // Aumentei o espaçamento após a Descrição do Problema

        // Descrição do Serviço Efectuado
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetFillColor(200, 200, 200); // Cinza claro
        $pdf->Cell(0, 10, 'Descricao do Servico Efectuado', 0, 1, 'C', 1); // Fundo cinza apenas no título
        $pdf->SetFillColor(255, 255, 255); // Restaura o fundo para branco
        $pdf->SetFont('Arial', '', 12);

        // Calcular a altura manualmente
        $helpLines = ceil($pdf->GetStringWidth($row['help_description']) / 190); // Número de linhas necessárias
        $helpHeight = $helpLines * 10; // Altura baseada no número de linhas
        $pdf->Rect(10, $pdf->GetY(), 190, $helpHeight + 10); // Borda preta ao redor da seção
        $pdf->MultiCell(190, 10, $row['help_description'], 0, 'L'); // Centralizado e sem fundo cinza
        $pdf->Ln(15); // Aumentei o espaçamento após a Descrição do Serviço Efectuado

        // Data e Horas Realizadas (mais compacta e alinhada à esquerda, com conteúdo centralizado)
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetFillColor(200, 200, 200); // Cinza claro
        $pdf->Cell(80, 10, 'Data e Horas Realizadas', 0, 1, 'C', 1); // Título centralizado
        $pdf->SetFillColor(255, 255, 255); // Restaura o fundo para branco
        $pdf->SetFont('Arial', '', 12);
        $pdf->Rect(10, $pdf->GetY(), 80, 30); // Borda preta ao redor da seção (largura reduzida)
        $pdf->Cell(80, 10, 'Data Inicio: ' . date('d-m-Y', strtotime($row['created_at'])), 0, 1, 'C'); // Conteúdo centralizado
        $pdf->Cell(80, 10, 'Data Fim: ' . date('d-m-Y', strtotime($row['created_at'])), 0, 1, 'C'); // Conteúdo centralizado
        $pdf->Cell(80, 10, 'Total Horas: ' . $row['hours_spent'], 0, 1, 'C'); // Conteúdo centralizado
        $pdf->Ln(10);

        // Output PDF
        $pdf->Output('D', 'assistance_' . $row['id'] . '.pdf'); // Força o download do PDF
    } else {
        echo "Assistance not found.";
    }
} else {
    echo "Invalid request.";
}

mysqli_close($conn);
?>