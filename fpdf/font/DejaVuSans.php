
<?php
require('fpdf/fpdf.php');
$pdf = new FPDF();
$pdf->AddFont('DejaVu', '', 'DejaVuSans.ttf', true);
$pdf->Output();
?>