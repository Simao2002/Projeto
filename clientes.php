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

// Configuração para evitar cache
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// Add Client
if (isset($_POST['add'])) {
    $name = mysqli_real_escape_string($conn, $_POST['company']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $postalcode = mysqli_real_escape_string($conn, $_POST['PostalCode']);
    $morada = mysqli_real_escape_string($conn, $_POST['morada']);
    $localidade = mysqli_real_escape_string($conn, $_POST['localidade']);
    $responsavel = mysqli_real_escape_string($conn, $_POST['responsavel']);
    
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

    $sql = "INSERT INTO clientes (company, email, PostalCode, Morada, Localidade, Responsavel, InicioContrato, FimContrato, HorasContratadas, SaldoHoras)
            VALUES ('$name', '$email', '$postalcode', '$morada', '$localidade', '$responsavel', '$inicio_contrato', '$fim_contrato', '$horas_contratadas', '$saldo_horas')";
    
    if (mysqli_query($conn, $sql)) {
        $_SESSION['success'] = "Cliente adicionado com sucesso!";
        header("Location: clientes.php");
        exit();
    } else {
        $_SESSION['error'] = "Erro ao adicionar cliente: " . mysqli_error($conn);
    }
}

// Delete Client
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $sql = "DELETE FROM clientes WHERE id=$id";
    
    if (mysqli_query($conn, $sql)) {
        $_SESSION['success'] = "Cliente removido com sucesso!";
        header("Location: clientes.php");
        exit();
    } else {
        $_SESSION['error'] = "Erro ao remover cliente: " . mysqli_error($conn);
    }
}

// Fetch Clients
$sql = "SELECT * FROM clientes ORDER BY id DESC";
$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Erro na consulta: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Clientes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="clientes.css">
    <style>
        /* Estilos do Modal */

        .actions {
            display: flex;
            gap: 10px; /* Espaço entre os botões */
        }

        .actions a {
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
        }

        .actions .edit {
            background-color: #2196F3;
            color: white;
        }

        .actions .delete {
            background-color: #f44336;
            color: white;
        }

        .actions a:hover {
            opacity: 0.8;
        }
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

        /* Estilo para células com "Sem Contrato" */
        .sem-contrato {
            color: #888;
            font-style: italic;
        }

        /* Estilo para mensagens */
        .success-message {
            background-color: #4CAF50;
            color: white;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }

        .error-message {
            background-color: #f44336;
            color: white;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }

        /* Estilo da tabela */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        /* Estilo dos botões */
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            cursor: pointer;
            margin-bottom: 20px;
        }

        button:hover {
            background-color: #45a049;
        }

        .edit {
            color: #2196F3;
            text-decoration: none;
            margin-right: 10px;
        }

        .delete {
            color: #f44336;
            text-decoration: none;
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

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="success-message"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="error-message"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Botão para abrir a modal -->
    <button id="openModalBtn"><i class="fa-solid fa-plus"></i> Add Client</button>

    <!-- Modal -->
    <div id="addClientModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Add New Client</h2>
            <form method="post" id="clientForm">
                <div class="form-group">
                    <input type="text" name="company" placeholder="Company Name" required>
                </div>
                <div class="form-group">
                    <input type="text" id="postalcode" name="PostalCode" placeholder="Postal Code" required>
                </div>
                <div class="form-group">
                    <input type="text" name="morada" placeholder="Morada" required>
                </div>
                <div class="form-group">
                    <input type="text" name="localidade" placeholder="Localidade" required>
                </div>
                <div class="form-group">
                    <input type="text" name="responsavel" placeholder="Responsável" required>
                </div>
                <div class="form-group">
                    <input type="email" name="email" placeholder="Email do Responsável" required>
                </div>
                
                <div class="checkbox-container">
                    <input type="checkbox" id="com_contrato" name="com_contrato" checked>
                    <label for="com_contrato">Com Contrato</label>
                </div>
                
                <div id="contrato_fields">
                    <div class="form-group">
                        <input type="date" id="inicio_contrato" name="inicio_contrato" placeholder="Início de Contrato" required>
                    </div>
                    <div class="form-group">
                        <input type="date" id="fim_contrato" name="fim_contrato" placeholder="Fim de Contrato" required>
                    </div>
                    <div class="form-group">
                        <input type="text" id="horas_contratadas" name="horas_contratadas" placeholder="Horas Contratadas (ex: 200:30)" required>
                    </div>
                </div>
                
                <button type="submit" name="add">Add Client</button>
            </form>
        </div>
    </div>

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
        <?php if (mysqli_num_rows($result) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <?php
                $temContrato = ($row['InicioContrato'] != '0000-00-00' && 
                               $row['FimContrato'] != '0000-00-00' && 
                               $row['HorasContratadas'] != '00:00:00');
                
                $inicioContrato = $temContrato ? $row['InicioContrato'] : 'Sem Contrato';
                $fimContrato = $temContrato ? $row['FimContrato'] : 'Sem Contrato';
                $horasContratadas = $temContrato ? substr($row['HorasContratadas'], 0, 5) : 'Sem Contrato';
                $saldoHoras = $temContrato ? substr($row['SaldoHoras'], 0, 5) : 'Sem Contrato';
                
                $semContratoClass = $temContrato ? '' : 'sem-contrato';
                ?>
                <tr>
                    <td><?= htmlspecialchars($row['id']) ?></td>
                    <td><?= htmlspecialchars($row['company']) ?></td>
                    <td><?= htmlspecialchars($row['PostalCode']) ?></td>
                    <td><?= htmlspecialchars($row['Morada']) ?></td>
                    <td><?= htmlspecialchars($row['Localidade']) ?></td>
                    <td><?= htmlspecialchars($row['Responsavel']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td class="<?= $semContratoClass ?>"><?= htmlspecialchars($inicioContrato) ?></td>
                    <td class="<?= $semContratoClass ?>"><?= htmlspecialchars($fimContrato) ?></td>
                    <td class="<?= $semContratoClass ?>"><?= htmlspecialchars($horasContratadas) ?></td>
                    <td class="<?= $semContratoClass ?>"><?= htmlspecialchars($saldoHoras) ?></td>
                    <td class="actions">
                        <a href="edit.php?id=<?= $row['id'] ?>" class="edit">Edit</a>
                        <a href="?delete=<?= $row['id'] ?>" class="delete" onclick="return confirm('Tem certeza que deseja remover este cliente?')">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="12">Nenhum cliente encontrado</td>
            </tr>
        <?php endif; ?>
    </table>

    <script>
        // Script para o Modal
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

        // Controle da checkbox "Com Contrato"
        const comContratoCheckbox = document.getElementById('com_contrato');
        const contratoFields = document.getElementById('contrato_fields');

        function updateContractFieldsVisibility() {
            if (comContratoCheckbox.checked) {
                contratoFields.style.display = 'block';
                document.getElementById('inicio_contrato').required = true;
                document.getElementById('fim_contrato').required = true;
                document.getElementById('horas_contratadas').required = true;
            } else {
                contratoFields.style.display = 'none';
                document.getElementById('inicio_contrato').required = false;
                document.getElementById('fim_contrato').required = false;
                document.getElementById('horas_contratadas').required = false;
            }
        }

        comContratoCheckbox.addEventListener('change', updateContractFieldsVisibility);
        updateContractFieldsVisibility(); // Inicializar

        // Validação das horas contratadas
        const horasContratadasInput = document.getElementById('horas_contratadas');

        horasContratadasInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^0-9:]/g, '');
            
            if (value.length > 6) {
                value = value.substring(0, 6);
            }
            
            e.target.value = value;
        });

        // Validação no submit
        const form = document.getElementById('clientForm');
        form.addEventListener('submit', function(event) {
            if (comContratoCheckbox.checked) {
                const value = horasContratadasInput.value;
                const parts = value.split(':');
                
                if (parts.length !== 2 || parts[1].length !== 2) {
                    alert('Formato inválido. Deve ter 2 dígitos após o ":" (ex: 120:00)');
                    event.preventDefault();
                    return;
                }
                
                const minutos = parseInt(parts[1]);
                if (minutos < 0 || minutos > 59) {
                    alert('Minutos inválidos. Deve ser entre 00 e 59');
                    event.preventDefault();
                    return;
                }
            }
        });
    </script>
</body>
</html>

<?php
mysqli_close($conn);