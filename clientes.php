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

// Array para armazenar múltiplos alertas
$alertMessages = [];
$currentDate = new DateTime(); // Data atual

// Buscar os clientes e verificar a diferença de datas e horas
$sql6 = "SELECT * FROM clientes";
$result = mysqli_query($conn, $sql6);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Verificação de data do contrato
        $fimContrato = new DateTime($row['FimContrato']);
        $currentDateWithoutTime = clone $currentDate;
        $currentDateWithoutTime->setTime(0, 0);
        
        $interval = $currentDateWithoutTime->diff($fimContrato);
        
        // Alerta para data do contrato
        if ($interval->invert == 0) { // Se a data ainda não passou
            if ($interval->days == 3) {
                $alertMessages[] = 'Contrato de ' . $row['company'] . ' expira em 3 dias!';
            } elseif ($interval->days == 2) {
                $alertMessages[] = 'Contrato de ' . $row['company'] . ' expira em 2 dias!';
            } elseif ($interval->days == 1) {
                $alertMessages[] = 'Contrato de ' . $row['company'] . ' expira em 1 dia!';
            }
        } elseif ($fimContrato->format('Y-m-d') == $currentDate->format('Y-m-d')) {
            $alertMessages[] = 'Contrato de ' . $row['company'] . ' expira hoje!';
        } elseif ($fimContrato->format('Y-m-d') < $currentDate->format('Y-m-d')) {
            $alertMessages[] = 'Contrato de ' . $row['company'] . ' expirou!';
        }
        
        // Verificação de horas do contrato
        $saldoHoras = $row['SaldoHoras'];
        $horasContratadas = $row['HorasContratadas'];
        
        // Converter para segundos para comparação
        $saldoSegundos = timeToSeconds($saldoHoras);
        
        // Alerta para horas do contrato (5 horas ou menos)
        if ($saldoSegundos <= 18000) { // 5 horas = 18000 segundos
            $horasRestantes = floor($saldoSegundos / 3600);
            $minutosRestantes = floor(($saldoSegundos % 3600) / 60);
            
            if ($saldoSegundos <= 0) {
                $alertMessages[] = 'Contrato de ' . $row['company'] . ' - Horas esgotadas!';
            } else {
                $alertMessages[] = 'Contrato de ' . $row['company'] . ' - ' . 
                                  $horasRestantes . 'h' . $minutosRestantes . 'm restantes!';
            }
        }
    }
}

// Função para converter hh:mm:ss em segundos
function timeToSeconds($time) {
    $parts = explode(':', $time);
    $hours = (int)$parts[0];
    $minutes = isset($parts[1]) ? (int)$parts[1] : 0;
    $seconds = isset($parts[2]) ? (int)$parts[2] : 0;
    return ($hours * 3600) + ($minutes * 60) + $seconds;
}

// Add Client
if (isset($_POST['add'])) {
    $name = $_POST['company'];
    $email = $_POST['email'];
    $postalcode = $_POST['PostalCode'];
    $morada = $_POST['morada'];
    $localidade = $_POST['localidade'];
    $responsavel = $_POST['responsavel'];
    $inicio_contrato = $_POST['inicio_contrato'];
    $fim_contrato = $_POST['fim_contrato'];
    $horas_contratadas = $_POST['horas_contratadas'] . ':00';

    $saldo_horas = $horas_contratadas;

    $sql3 = "INSERT INTO clientes (company, email, PostalCode, Morada, Localidade, Responsavel, InicioContrato, FimContrato, HorasContratadas, SaldoHoras)
             VALUES ('$name', '$email', '$postalcode', '$morada', '$localidade', '$responsavel', '$inicio_contrato', '$fim_contrato', '$horas_contratadas', '$saldo_horas')";
    mysqli_query($conn, $sql3);
}

// Delete Client
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $sql4 = "DELETE FROM clientes WHERE id=$id";
    mysqli_query($conn, $sql4);
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
    $horas_contratadas = $_POST['horas_contratadas'] . ':00';

    $saldo_horas = $horas_contratadas;

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
}

// Fetch Clients
$sql6 = "SELECT * FROM clientes";
$result = mysqli_query($conn, $sql6);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Clientes</title>
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
            cursor: pointer;
        }

        .close:hover {
            color: black;
        }

        /* Estilo dos alertas */
        .alert-container {
            position: fixed;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            z-index: 9999;
            width: 80%;
            max-width: 600px;
        }

        .alert {
            background-color: #f44336;
            color: white;
            padding: 15px;
            margin-bottom: 5px;
            border-radius: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .close-alert {
            color: white;
            font-weight: bold;
            font-size: 22px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <a href="home.php" class="back-button">
        <i class="fa-solid fa-house"></i>
    </a>

    <h1>Clientes</h1>

    <!-- Container para múltiplos alertas -->
    <?php if (!empty($alertMessages)): ?>
        <div class="alert-container">
            <?php foreach ($alertMessages as $message): ?>
                <div class="alert">
                    <span><?php echo $message; ?></span>
                    <span class="close-alert">&times;</span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Botão para abrir a modal -->
    <button id="openModalBtn" style="margin-bottom: 20px;"><i class="fa-solid fa-plus"></i> Add Client</button>

    <!-- Modal -->
    <div id="addClientModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Add New Client</h2>
            <form method="post" id="clientForm">
                <input type="text" name="company" placeholder="Company Name" required>
                <input type="text" id="postalcode" name="PostalCode" placeholder="Postal Code" required>
                <input type="text" name="morada" placeholder="Morada" required>
                <input type="text" name="localidade" placeholder="Localidade" required>
                <input type="text" name="responsavel" placeholder="Responsável" required>
                <input type="email" name="email" placeholder="Email do Responsável" required>
                <input type="date" id="inicio_contrato" name="inicio_contrato" placeholder="Início de Contrato" required>
                <input type="date" id="fim_contrato" name="fim_contrato" placeholder="Fim de Contrato" required>
                <input type="text" id="horas_contratadas" name="horas_contratadas" placeholder="Horas Contratadas (hh:mm)" required>
                <button type="submit" name="add">Add Client</button>
            </form>
        </div>
    </div>

    <script>
        // Script para abrir e fechar a modal
        const modal = document.getElementById('addClientModal');
        const openModalBtn = document.getElementById('openModalBtn');
        const closeModalBtn = document.querySelector('.close');

        openModalBtn.onclick = function() {
            modal.style.display = 'block';
        }

        closeModalBtn.onclick = function() {
            modal.style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }

        // Validação das horas contratadas (hh:mm)
        const horasContratadasInput = document.getElementById('horas_contratadas');

        horasContratadasInput.addEventListener('input', function (e) {
            let value = e.target.value.replace(/[^0-9:]/g, '');
            
            if (value.length > 2 && !value.includes(':')) {
                value = value.slice(0, 2) + ':' + value.slice(2);
            }
            
            if (value.length > 5) {
                value = value.slice(0, 5);
            }
            
            e.target.value = value;
        });

        // Validação do formato hh:mm
        const form = document.getElementById('clientForm');
        form.addEventListener('submit', function(event) {
            const regex = /^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/;
            if (!regex.test(horasContratadasInput.value)) {
                alert('Formato de horas inválido. Use hh:mm (ex: 08:30).');
                event.preventDefault();
            }
        });

        // Fechar alertas
        document.querySelectorAll('.close-alert').forEach(btn => {
            btn.addEventListener('click', function() {
                this.parentElement.style.display = 'none';
            });
        });
    </script>

    <!-- List Clients -->
    <table>
        <tr>
            <th>ID</th>
            <th>Company</th>
            <th>Postal Code</th>
            <th>Morada</th>
            <th>Localidade</th>
            <th>Responsável</th>
            <th>Email do Responsável</th>
            <th>Início Contrato</th>
            <th>Fim Contrato</th>
            <th>Horas Contratadas</th>
            <th>Saldo Horas</th>
            <th>Actions</th>
        </tr>
        <?php
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $horasContratadas = substr($row['HorasContratadas'], 0, 5);
                $saldoHoras = substr($row['SaldoHoras'], 0, 5);
                
                echo "<tr>
                    <td>{$row['id']}</td>
                    <td>{$row['company']}</td>
                    <td>{$row['PostalCode']}</td>
                    <td>{$row['Morada']}</td>
                    <td>{$row['Localidade']}</td>
                    <td>{$row['Responsavel']}</td>
                    <td>{$row['email']}</td>
                    <td>{$row['InicioContrato']}</td>
                    <td>{$row['FimContrato']}</td>
                    <td>{$horasContratadas}</td>
                    <td>{$saldoHoras}</td>
                    <td class='actions'>
                        <a href='edit.php?id={$row['id']}' class='edit'>Edit</a>
                        <a href='?delete={$row['id']}' class='delete' onclick='return confirm(\"Are you sure?\")'>Delete</a>
                    </td>
                </tr>";
            }
        } else {
            echo "<tr><td colspan='12'>No clients found.</td></tr>";
        }
        ?>
    </table>
</body>
</html>

<?php
mysqli_close($conn);
?>