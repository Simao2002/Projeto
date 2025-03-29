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
    
    // Verifica se tem contrato para atualizar esses campos
    $tem_contrato = ($_POST['inicio_contrato'] != '0000-00-00' && 
                    $_POST['fim_contrato'] != '0000-00-00' && 
                    $_POST['horas_contratadas'] != '00:00');
    
    $inicio_contrato = $tem_contrato ? $_POST['inicio_contrato'] : '0000-00-00';
    $fim_contrato = $tem_contrato ? $_POST['fim_contrato'] : '0000-00-00';
    $horas_contratadas = $tem_contrato ? $_POST['horas_contratadas'] . ':00' : '00:00:00';
    $saldo_horas = $tem_contrato ? $_POST['saldo_horas'] . ':00' : '00:00:00';

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

// Verifica se o cliente tem contrato
$tem_contrato = ($row['InicioContrato'] != '0000-00-00' && 
                 $row['FimContrato'] != '0000-00-00' && 
                 $row['HorasContratadas'] != '00:00:00');
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
        
        /* Estilo para os campos de horas */
        .horas-input {
            width: 100px;
        }
        
        .disabled-field {
            background-color: #f0f0f0;
            color: #a0a0a0;
            cursor: not-allowed;
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

    <h1>Edit Client</h1>

    <form method="post" id="clientForm">
        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
        <input type="text" name="company" value="<?php echo $row['company']; ?>" placeholder="Company Name" required>
        <input type="text" name="PostalCode" value="<?php echo $row['PostalCode']; ?>" placeholder="Postal Code" required>
        <input type="text" name="morada" value="<?php echo $row['Morada']; ?>" placeholder="Morada" required>
        <input type="text" name="localidade" value="<?php echo $row['Localidade']; ?>" placeholder="Localidade" required>
        <input type="text" name="responsavel" value="<?php echo $row['Responsavel']; ?>" placeholder="Responsável" required>
        <input type="email" name="email" value="<?php echo $row['email']; ?>" placeholder="Email do Responsável" required>
        
        <div class="checkbox-container">
            <input type="checkbox" id="tem_contrato" name="tem_contrato" <?php echo $tem_contrato ? 'checked' : ''; ?>>
            <label for="tem_contrato">Tem Contrato</label>
        </div>
        
        <input type="date" name="inicio_contrato" id="inicio_contrato" 
               value="<?php echo $tem_contrato ? $row['InicioContrato'] : ''; ?>" 
               placeholder="Início de Contrato" 
               <?php echo !$tem_contrato ? 'disabled' : ''; ?>
               class="<?php echo !$tem_contrato ? 'disabled-field' : ''; ?>">
               
        <input type="date" name="fim_contrato" id="fim_contrato" 
               value="<?php echo $tem_contrato ? $row['FimContrato'] : ''; ?>" 
               placeholder="Fim de Contrato" 
               <?php echo !$tem_contrato ? 'disabled' : ''; ?>
               class="<?php echo !$tem_contrato ? 'disabled-field' : ''; ?>">
        
        <!-- Campo Horas Contratadas -->
        <input type="text" id="horas_contratadas" name="horas_contratadas" 
               value="<?php echo $tem_contrato ? substr($row['HorasContratadas'], 0, 5) : ''; ?>" 
               placeholder="Horas (ex: 120:00)" 
               class="horas-input <?php echo !$tem_contrato ? 'disabled-field' : ''; ?>"
               maxlength="6"
               <?php echo !$tem_contrato ? 'disabled' : ''; ?>>
        
        <!-- Campo Saldo de Horas -->
        <input type="text" id="saldo_horas" name="saldo_horas" 
               value="<?php echo $tem_contrato ? substr($row['SaldoHoras'], 0, 5) : ''; ?>" 
               placeholder="Saldo (ex: 30:00)" 
               class="horas-input <?php echo !$tem_contrato ? 'disabled-field' : ''; ?>"
               maxlength="6"
               <?php echo !$tem_contrato ? 'disabled' : ''; ?>>

        <button type="submit" name="edit">Save Changes</button>
    </form>

    <script>
        // Função para validar o formato das horas
        function validarFormatoHoras(input) {
            const value = input.value;
            
            // Verifica se tem exatamente um ":"
            if ((value.match(/:/g) || []).length !== 1) {
                alert('Formato inválido. Deve incluir um ":" (ex: 120:00)');
                return false;
            }
            
            // Divide em horas e minutos
            const partes = value.split(':');
            
            // Verifica se tem 2 dígitos após o ":"
            if (partes.length !== 2 || partes[1].length !== 2) {
                alert('Formato inválido. Deve ter 2 dígitos após o ":" (ex: 120:00)');
                return false;
            }
            
            // Verifica se os minutos são válidos (00-59)
            const minutos = parseInt(partes[1]);
            if (minutos < 0 || minutos > 59) {
                alert('Minutos inválidos. Deve ser entre 00 e 59');
                return false;
            }
            
            return true;
        }

        // Validação no envio do formulário
        document.getElementById('clientForm').addEventListener('submit', function(event) {
            const temContrato = document.getElementById('tem_contrato').checked;
            
            if (temContrato) {
                const horasContratadas = document.getElementById('horas_contratadas');
                const saldoHoras = document.getElementById('saldo_horas');
                
                if (!validarFormatoHoras(horasContratadas) || !validarFormatoHoras(saldoHoras)) {
                    event.preventDefault();
                }
            }
        });

        // Validação durante a digitação (limita a 6 caracteres e apenas números e :)
        document.getElementById('horas_contratadas').addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^0-9:]/g, '');
            
            // Remove ":" extras
            const parts = value.split(':');
            if (parts.length > 2) {
                value = parts[0] + ':' + parts[1];
            }
            
            // Limita a 6 caracteres
            if (value.length > 6) {
                value = value.substring(0, 6);
            }
            
            e.target.value = value;
        });

        // Aplica a mesma validação para o campo de saldo
        document.getElementById('saldo_horas').addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^0-9:]/g, '');
            
            const parts = value.split(':');
            if (parts.length > 2) {
                value = parts[0] + ':' + parts[1];
            }
            
            if (value.length > 6) {
                value = value.substring(0, 6);
            }
            
            e.target.value = value;
        });
        
        // Toggle para habilitar/desabilitar campos de contrato
        document.getElementById('tem_contrato').addEventListener('change', function(e) {
            const temContrato = e.target.checked;
            const inicioContrato = document.getElementById('inicio_contrato');
            const fimContrato = document.getElementById('fim_contrato');
            const horasContratadas = document.getElementById('horas_contratadas');
            const saldoHoras = document.getElementById('saldo_horas');
            
            // Habilita ou desabilita os campos
            inicioContrato.disabled = !temContrato;
            fimContrato.disabled = !temContrato;
            horasContratadas.disabled = !temContrato;
            saldoHoras.disabled = !temContrato;
            
            // Adiciona ou remove a classe de estilo
            if (temContrato) {
                inicioContrato.classList.remove('disabled-field');
                fimContrato.classList.remove('disabled-field');
                horasContratadas.classList.remove('disabled-field');
                saldoHoras.classList.remove('disabled-field');
                
                // Define valores padrão se estiverem vazios
                if (!inicioContrato.value) inicioContrato.value = '<?php echo date('Y-m-d'); ?>';
                if (!fimContrato.value) fimContrato.value = '<?php echo date('Y-m-d', strtotime('+1 year')); ?>';
                if (!horasContratadas.value) horasContratadas.value = '100:00';
                if (!saldoHoras.value) saldoHoras.value = '100:00';
            } else {
                inicioContrato.classList.add('disabled-field');
                fimContrato.classList.add('disabled-field');
                horasContratadas.classList.add('disabled-field');
                saldoHoras.classList.add('disabled-field');
                
                // Limpa os valores
                inicioContrato.value = '';
                fimContrato.value = '';
                horasContratadas.value = '';
                saldoHoras.value = '';
            }
        });
    </script>
</body>
</html>

<?php
mysqli_close($conn);
?>