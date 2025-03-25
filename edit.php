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

// Edit Client
if (isset($_POST['edit'])) {
    $id = $_POST['id'];
    $name = $_POST['company'];
    $email = $_POST['email'];
    $postalcode = $_POST['PostalCode'];
    $morada = $_POST['morada'];
    $localidade = $_POST['localidade'];
    $responsavel = $_POST['responsavel'];
    $inicio_contrato = $_POST['inicio_contrato'];
    $fim_contrato = $_POST['fim_contrato'];
    $horas_contratadas = $_POST['horas_contratadas'] . ':00'; // Adiciona os segundos
    $saldo_horas = $_POST['saldo_horas']; // Novo campo para editar SaldoHoras

    // Atualiza os dados do cliente
    $sql5 = "UPDATE clientes SET
             company='$name',
             email='$email',
             PostalCode='$postalcode',
             Morada='$morada',
             Localidade='$localidade',
             Responsavel='$responsavel',
             InicioContrato='$inicio_contrato',
             FimContrato='$fim_contrato',
             HorasContratadas='$horas_contratadas',
             SaldoHoras='$saldo_horas'
             WHERE id=$id";
    mysqli_query($conn, $sql5);

    // Redireciona para a página dos clientes após a atualização
    header("Location: clientes.php");
    exit();
}

// Fetch Client Data
$id = $_GET['id'];
$sql6 = "SELECT * FROM clientes WHERE id=$id";
$result = mysqli_query($conn, $sql6);
$row = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Client</title>
    <link rel="stylesheet" href="clientes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgb(0,0,0);
            background-color: rgba(0,0,0,0.4);
            padding-top: 60px;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <a href="home.php" class="back-button">
        <i class="fa-solid fa-house"></i>
    </a>

    <h1>Edit Client</h1>

    <form method="post" id="clientForm">
        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
        <input type="text" name="company" value="<?php echo $row['company']; ?>" placeholder="Company Name" required>
        <input type="text" name="PostalCode" value="<?php echo $row['PostalCode']; ?>" placeholder="Postal Code" required>
        <input type="text" name="morada" value="<?php echo $row['Morada']; ?>" placeholder="Morada" required>
        <input type="text" name="localidade" value="<?php echo $row['Localidade']; ?>" placeholder="Localidade" required>
        <input type="text" name="responsavel" value="<?php echo $row['Responsavel']; ?>" placeholder="Responsável" required>
        <input type="email" name="email" value="<?php echo $row['email']; ?>" placeholder="Email do Responsável" required>
        <input type="date" name="inicio_contrato" value="<?php echo $row['InicioContrato']; ?>" placeholder="Início de Contrato" required>
        <input type="date" name="fim_contrato" value="<?php echo $row['FimContrato']; ?>" placeholder="Fim de Contrato" required>
        <input type="text" id="horas_contratadas" name="horas_contratadas" value="<?php echo substr($row['HorasContratadas'], 0, 5); ?>" placeholder="Horas Contratadas (hh:mm)" required>
        
        <!-- Campo para editar o SaldoHoras -->
        <input type="text" id="saldo_horas" name="saldo_horas" value="<?php echo substr($row['SaldoHoras'], 0, 5); ?>" placeholder="Saldo Horas (hh:mm)" required>

        <button type="submit" name="edit">Save Changes</button>
    </form>

    <script>
        // Função para formatação do campo de horas
        function formatHoras(input) {
            let value = input.value;

            // Remove qualquer caractere que não seja número ou ":"
            value = value.replace(/[^0-9:]/g, '');

            // Adiciona automaticamente o ":" após dois dígitos
            if (value.length > 2 && !value.includes(':')) {
                value = value.slice(0, 2) + ':' + value.slice(2);
            }

            // Limita o comprimento máximo a 5 caracteres
            if (value.length > 5) {
                value = value.slice(0, 5);
            }

            // Atualiza o valor do input
            input.value = value;
        }

        // Aplique a formatação tanto para "horas_contratadas" quanto para "saldo_horas"
        const horasContratadasInput = document.getElementById('horas_contratadas');
        const saldoHorasInput = document.getElementById('saldo_horas');

        horasContratadasInput.addEventListener('input', function() {
            formatHoras(horasContratadasInput);
        });

        saldoHorasInput.addEventListener('input', function() {
            formatHoras(saldoHorasInput);
        });

        // Validação do formato hh:mm no envio do formulário
        const form = document.getElementById('clientForm');

        form.addEventListener('submit', function(event) {
            const horasContratadas = horasContratadasInput.value;
            const saldoHoras = saldoHorasInput.value;

            // Verifica se o formato é hh:mm
            const regex = /^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/;
            if (!regex.test(horasContratadas) || !regex.test(saldoHoras)) {
                alert('O formato das horas contratadas e saldo de horas deve ser hh:mm (ex: 08:30).');
                event.preventDefault(); // Impede o envio do formulário
            }
        });
    </script>
</body>
</html>

<?php
mysqli_close($conn);
?>
