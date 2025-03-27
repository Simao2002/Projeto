<?php
session_start();

if (!isset($_SESSION['id']) || !isset($_SESSION['user_name'])) {
    header("Location: index.php");
    exit();
}

require 'vendor/autoload.php';

$sname = "localhost";
$uname = "root";
$password = "";
$db_name = "test_db";

$conn = mysqli_connect($sname, $uname, $password, $db_name);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

define('DESTINATARIO_NOTIFICACOES', 'shauinho9@gmail.com');
define('NOTIFICACOES_DIR', 'notificacoes_enviadas');

// Criar diretório se não existir
if (!file_exists(NOTIFICACOES_DIR)) {
    mkdir(NOTIFICACOES_DIR, 0755, true);
}

// Configurações do e-mail
$mail = new PHPMailer\PHPMailer\PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'simaopiresfontes@gmail.com';
    $mail->Password = 'vonk otzw kvvu kbrx';
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->setFrom('pccisuporte@pcci.pt', 'Sistema de Contratos');
    $mail->isHTML(false); // Email em texto puro
} catch (Exception $e) {
    die("Erro na configuração do e-mail: " . $e->getMessage());
}

// Buscar clientes com contratos a expirar em 30 dias
$data_30_dias = date('Y-m-d', strtotime('+30 days'));
$sql = "SELECT id, company FROM clientes WHERE FimContrato = '$data_30_dias' AND InicioContrato != '0000-00-00'";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $arquivo_notificacao = NOTIFICACOES_DIR . '/cliente_' . $row['id'] . '.txt';
        
        // Verificar se já foi notificado hoje
        if (!file_exists($arquivo_notificacao) || file_get_contents($arquivo_notificacao) !== date('Y-m-d')) {
            try {
                $mail->clearAddresses();
                $mail->addAddress(DESTINATARIO_NOTIFICACOES);
                
                $assunto = 'Contrato a expirar - ' . $row['company'];
                $corpo ='Faltam 30 dias para acabar o contrato do cliente ' . $row['company'];
                
                $mail->Subject = $assunto;
                $mail->Body = $corpo;
                
                if ($mail->send()) {
                    // Registrar notificação
                    file_put_contents($arquivo_notificacao, date('Y-m-d'));
                    error_log("Notificação enviada para cliente ID: " . $row['id']);
                }
            } catch (Exception $e) {
                error_log("Erro ao enviar notificação: " . $e->getMessage());
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
    
    // Verificar se o checkbox "Com Contrato" está marcado
    $com_contrato = isset($_POST['com_contrato']) ? 1 : 0;
    
    if ($com_contrato) {
        $inicio_contrato = $_POST['inicio_contrato'];
        $fim_contrato = $_POST['fim_contrato'];
        $horas_contratadas = $_POST['horas_contratadas'] . ':00';
        $saldo_horas = $horas_contratadas;
    } else {
        $inicio_contrato = '0000-00-00';
        $fim_contrato = '0000-00-00';
        $horas_contratadas = '00:00:00';
        $saldo_horas = '00:00:00';
    }

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

        /* Estilo para os campos de contrato */
        #contrato_fields {
            display: block;
            transition: all 0.3s ease;
        }

        #contrato_fields input {
            margin-bottom: 10px;
        }

        #com_contrato {
            margin-right: 10px;
        }
        
        /* Estilo para células com "Sem Contrato" */
        .sem-contrato {
            color: #888;
            font-style: italic;
        }

        .checkbox-container {
            display: flex;
            align-items: center;
            margin: 15px 0;
            gap: 8px;
        }

        .checkbox-container input[type="checkbox"] {
            margin: 0;
        }

        .checkbox-container label {
            margin: 0;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <a href="home.php" class="back-button">
        <i class="fa-solid fa-house"></i>
    </a>

    <h1>Clientes</h1>

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
                
                <div class="checkbox-container">
                <input type="checkbox" id="com_contrato" name="com_contrato" checked>
                <label for="com_contrato">Com Contrato</label>
                </div>
                
                <div id="contrato_fields">
                    <input type="date" id="inicio_contrato" name="inicio_contrato" placeholder="Início de Contrato" required>
                    <input type="date" id="fim_contrato" name="fim_contrato" placeholder="Fim de Contrato" required>
                    <input type="text" id="horas_contratadas" name="horas_contratadas" placeholder="Horas Contratadas (hh:mm)" required>
                </div>
                
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
            const comContrato = document.getElementById('com_contrato').checked;
            
            if (comContrato) {
                const regex = /^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/;
                if (!regex.test(horasContratadasInput.value)) {
                    alert('Formato de horas inválido. Use hh:mm (ex: 08:30).');
                    event.preventDefault();
                }
            }
        });

        // Controle da checkbox "Com Contrato"
        const comContratoCheckbox = document.getElementById('com_contrato');
        const contratoFields = document.getElementById('contrato_fields');

        // Função para atualizar a visibilidade dos campos
        function updateContractFieldsVisibility() {
            if (comContratoCheckbox.checked) {
                contratoFields.style.display = 'block';
                // Tornar os campos obrigatórios
                document.getElementById('inicio_contrato').required = true;
                document.getElementById('fim_contrato').required = true;
                document.getElementById('horas_contratadas').required = true;
            } else {
                contratoFields.style.display = 'none';
                // Remover a obrigatoriedade dos campos
                document.getElementById('inicio_contrato').required = false;
                document.getElementById('fim_contrato').required = false;
                document.getElementById('horas_contratadas').required = false;
            }
        }

        // Event listener para a checkbox
        comContratoCheckbox.addEventListener('change', updateContractFieldsVisibility);

        // Inicializar a visibilidade quando a modal é aberta
        openModalBtn.onclick = function() {
            modal.style.display = 'block';
            updateContractFieldsVisibility();
        }
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
                // Verificar se o cliente tem contrato
                $temContrato = ($row['InicioContrato'] != '0000-00-00' && 
                               $row['FimContrato'] != '0000-00-00' && 
                               $row['HorasContratadas'] != '00:00:00');
                
                // Preparar os valores para exibição
                $inicioContrato = $temContrato ? $row['InicioContrato'] : 'Sem Contrato';
                $fimContrato = $temContrato ? $row['FimContrato'] : 'Sem Contrato';
                $horasContratadas = $temContrato ? substr($row['HorasContratadas'], 0, 5) : 'Sem Contrato';
                $saldoHoras = $temContrato ? substr($row['SaldoHoras'], 0, 5) : 'Sem Contrato';
                
                // Classe CSS para células sem contrato
                $semContratoClass = $temContrato ? '' : 'class="sem-contrato"';
                
                echo "<tr>
                    <td>{$row['id']}</td>
                    <td>{$row['company']}</td>
                    <td>{$row['PostalCode']}</td>
                    <td>{$row['Morada']}</td>
                    <td>{$row['Localidade']}</td>
                    <td>{$row['Responsavel']}</td>
                    <td>{$row['email']}</td>
                    <td {$semContratoClass}>{$inicioContrato}</td>
                    <td {$semContratoClass}>{$fimContrato}</td>
                    <td {$semContratoClass}>{$horasContratadas}</td>
                    <td {$semContratoClass}>{$saldoHoras}</td>
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