<?php
session_start();

if (!isset($_SESSION['id']) || !isset($_SESSION['user_name']) || !isset($_SESSION['name'])) {
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

// Buscar o email do usuário logado
$user_id = $_SESSION['id'];
$sql_user = "SELECT email FROM users WHERE id = '$user_id'";
$result_user = mysqli_query($conn, $sql_user);
$user_email = "";

if ($row_user = mysqli_fetch_assoc($result_user)) {
    $user_email = $row_user['email'];
}

// Fetch Companies from the clientes table
$sql9 = "SELECT id, company FROM clientes";
$companies_result = mysqli_query($conn, $sql9);

// Função para gerar o número da guia
function gerarNumeroGuia($conn, $data_assistencia) {
    $date = new DateTime($data_assistencia);
    $yearMonth = $date->format('Ym');
    
    // Contar quantas assistências existem no mesmo mês/ano
    $sql = "SELECT COUNT(*) as count FROM assists 
            WHERE DATE_FORMAT(created_at, '%Y%m') = '$yearMonth'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    
    // O número sequencial é o count (começando do 0)
    $sequential = $row['count'];
    
    return $yearMonth . '-' . str_pad($sequential, 2, '0', STR_PAD_LEFT);
}

// Add Assistance
if (isset($_POST['add'])) {
    $company_id = $_POST['company'];
    $problem = $_POST['problem'];
    $help_description = $_POST['help'];
    $hours_spent = $_POST['hours'];
    $service_status = $_POST['service_status'];
    $conditions = $_POST['conditions'];
    $lista_problemas = $_POST['lista_problemas'];
    $intervencao = $_POST['intervencao'];
    $tecnico = $_POST['tecnico'];
    $email_tecnico = $_POST['email_tecnico'];
    $data_assistencia = $_POST['data_assistencia'];

    if (preg_match('/^\d{2}:\d{2}$/', $hours_spent)) {
        // Formato correto
    } else {
        echo "<script>alert('Formato de horas inválido. Use o formato hh:mm.'); window.location.href = 'registo.php';</script>";
        return;
    }

    // Gerar o número da guia antes de inserir
    $numero_guia = gerarNumeroGuia($conn, $data_assistencia);

    $sql10 = "INSERT INTO assists (numero_guia, company_id, problem, help_description, hours_spent, service_status, conditions, lista_problemas, intervencao, Tecnico, EmailTecnico, created_at) 
              VALUES ('$numero_guia', '$company_id', '$problem', '$help_description', '$hours_spent', '$service_status', '$conditions', '$lista_problemas', '$intervencao', '$tecnico', '$email_tecnico', '$data_assistencia')";

    if (mysqli_query($conn, $sql10)) {
        // Só desconta horas se for "Com Contrato"
        if ($conditions === "Com Contrato") {
            $sql11 = "UPDATE clientes SET SaldoHoras = SEC_TO_TIME(TIME_TO_SEC(SaldoHoras) - TIME_TO_SEC('$hours_spent')) WHERE id = $company_id";
            mysqli_query($conn, $sql11);
        }
        echo "<script>alert('Assistance added successfully!'); window.location.href = 'assist.php';</script>";
    } else {
        echo "<script>alert('Error adding assistance: " . mysqli_error($conn) . "');</script>";
    }
}
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
    <a href="home.php" class="back-button">
        <i class="fa-solid fa-house"></i>
    </a>

    <h1>Assistance Registration</h1>

    <form method="post">
        <div class="form-group">
            <label for="company">Company:</label>
            <select name="company" id="company" required>
                <option value="">Select a company</option>
                <?php 
                // Reset the pointer to the beginning of the result set
                mysqli_data_seek($companies_result, 0);
                while ($row = mysqli_fetch_assoc($companies_result)) { ?>
                    <option value="<?php echo $row['id']; ?>"><?php echo $row['company']; ?></option>
                <?php } ?>
            </select>
        </div>

        <div class="form-group">
            <label for="problem">Problem:</label>
            <input type="text" name="problem" id="problem" placeholder="Describe the problem" required>
        </div>

        <div class="form-group">
            <label for="help">Help Provided:</label>
            <textarea name="help" id="help" placeholder="Describe the help provided" required></textarea>
        </div>

        <div class="form-group">
            <label for="hours">Hours Spent:</label>
            <input type="text" name="hours" id="hours" placeholder="Enter hours spent (hh:mm)" maxlength="5" required>
        </div>

        <div class="form-group">
            <label for="service_status">Estado do Serviço:</label>
            <select name="service_status" id="service_status" required>
                <option value="">Selecione o estado do serviço</option>
                <option value="Completo">Completo</option>
                <option value="Em Curso">Em Curso</option>
            </select>
        </div>

        <div class="form-group">
            <label for="conditions">Condições:</label>
            <select name="conditions" id="conditions" required>
                <option value="">Selecione a condição</option>
                <option value="Em Garantia">Em Garantia</option>
                <option value="A Faturar">A Faturar</option>
                <option value="Serviços internos">Serviços internos</option>
                <option value="Com Contrato">Com Contrato</option>
            </select>
        </div>

        <div class="form-group">
            <label for="lista_problemas">Lista de Problemas:</label>
            <select name="lista_problemas" id="lista_problemas" required>
                <option value="">Selecione um problema</option>
                <option value="Hardware">Hardware</option>
                <option value="Software">Software</option>
                <option value="Impressoras / Cópia">Impressoras / Cópia</option>
                <option value="Gestão de Dominios">Gestão de Dominios</option>
                <option value="Migração de Servidores">Migração de Servidores</option>
                <option value="Backups">Backups</option>
            </select>
        </div>

        <div class="form-group">
            <label for="intervencao">Intervenção:</label>
            <select name="intervencao" id="intervencao" required>
                <option value="">Selecione o tipo de intervenção</option>
                <option value="Presencial">Presencial</option>
                <option value="Remota">Remota</option>
                <option value="Telefónica / Email">Telefónica / Email</option>
                <option value="Nossas Instalações">Nossas Instalações</option>
            </select>
        </div>

        <div class="form-group">
            <label for="tecnico">Técnico:</label>
            <input type="text" name="tecnico" id="tecnico" value="<?php echo htmlspecialchars($_SESSION['name']); ?>" readonly required>
        </div>

        <div class="form-group">
            <label for="email_tecnico">Email do Técnico:</label>
            <input type="email" name="email_tecnico" id="email_tecnico" value="<?php echo htmlspecialchars($user_email); ?>" readonly required>
        </div>

        <div class="form-group">
            <label for="data_assistencia">Data da Assistência:</label>
            <input type="date" name="data_assistencia" id="data_assistencia" required>
        </div>

        <button type="submit" name="add">Add Assistance</button>
    </form>

    <script>
        // Função para formatar o campo de horas (hh:mm)
        function formatHoursInput(input) {
            // Remove qualquer caractere que não seja número
            let value = input.value.replace(/\D/g, '');

            // Limita o valor a 4 dígitos (hhmm)
            if (value.length > 4) {
                value = value.slice(0, 4);
            }

            // Adiciona os dois pontos automaticamente após 2 dígitos (hh:mm)
            if (value.length > 2) {
                value = value.slice(0, 2) + ':' + value.slice(2);
            }

            // Atualiza o valor do campo
            input.value = value;
        }

        // Aplica a formatação ao campo de horas
        const hoursInput = document.getElementById('hours');
        if (hoursInput) {
            hoursInput.addEventListener('input', function () {
                formatHoursInput(this);
            });

            // Limita o campo a 5 caracteres (hh:mm)
            hoursInput.addEventListener('keypress', function (e) {
                if (this.value.length >= 5) {
                    e.preventDefault();
                }
            });
        }
    </script>
</body>
</html>

<?php
mysqli_close($conn);
?>