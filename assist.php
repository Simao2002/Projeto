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

// Handle Delete Action
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $sql8 = "DELETE FROM assists WHERE id=$id";
    if (mysqli_query($conn, $sql8)) {
        echo "<script>alert('Assistance deleted successfully!'); window.location.href = 'assist.php';</script>";
    } else {
        echo "<script>alert('Error deleting assistance: " . mysqli_error($conn) . "');</script>";
    }
}

// Fetch Assists with Company Names
$sql11 = "SELECT assists.id, clientes.company, assists.problem, assists.help_description, assists.hours_spent, 
                 assists.service_status, assists.conditions, assists.lista_problemas, assists.intervencao, 
                 assists.Tecnico, assists.EmailTecnico, assists.created_at 
          FROM assists 
          JOIN clientes ON assists.company_id = clientes.id 
          ORDER BY assists.created_at DESC";
$assists_result = mysqli_query($conn, $sql11);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assistance</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assist.css">
</head>
<body>
    <!-- Back to Home Button -->
    <a href="home.php" class="back-button">
        <i class="fa-solid fa-house"></i>
    </a>

    <h1>Assistance</h1>

    <!-- List Assists -->
    <table>
        <tr>
            <th>Company</th>
            <th>Problem</th>
            <th>Help Description</th>
            <th>Hours Spent</th>
            <th>Estado do Serviço</th>
            <th>Condições</th>
            <th>Lista de Problemas</th>
            <th>Intervenção</th>
            <th>Técnico</th>
            <th>Email Técnico</th>
            <th>Date</th>
            <th>Actions</th>
        </tr>
        <?php while ($row = mysqli_fetch_assoc($assists_result)) { ?>
            <tr>
                <td><?php echo $row['company']; ?></td>
                <td><?php echo $row['problem']; ?></td>
                <td><?php echo $row['help_description']; ?></td>
                <td><?php echo substr($row['hours_spent'], 0, 5); ?></td>
                <td><?php echo $row['service_status']; ?></td>
                <td><?php echo $row['conditions']; ?></td>
                <td><?php echo $row['lista_problemas']; ?></td>
                <td><?php echo $row['intervencao']; ?></td>
                <td><?php echo $row['Tecnico']; ?></td>
                <td><?php echo $row['EmailTecnico']; ?></td>
                <td><?php echo date('d-m-Y', strtotime($row['created_at'])); ?></td>
                <td class="actions">
                    <a href="edit_assist.php?id=<?php echo $row['id']; ?>" class="edit">Edit</a>
                    <a href="generate_pdf.php?id=<?php echo $row['id']; ?>" class="pdf" target="_blank">PDF</a>
                    <a href="send_pdf_email.php?id=<?php echo $row['id']; ?>" class="email" onclick="return confirm('Enviar este PDF por email para o cliente?')">
                        <i class="fas fa-envelope"></i> Email
                    </a>
                    <a href="?delete=<?php echo $row['id']; ?>" class="delete" onclick="return confirm('Are you sure?')">Delete</a>
                </td>
            </tr>
        <?php } ?>
    </table>
</body>
</html>

<?php
mysqli_close($conn);
?>