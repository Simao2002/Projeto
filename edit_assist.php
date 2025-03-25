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

// Fetch the assist record to edit
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql12 = "SELECT assists.id, assists.company_id, clientes.company, assists.problem, assists.help_description, 
                     assists.hours_spent, assists.service_status, assists.conditions, assists.lista_problemas, 
                     assists.intervencao, assists.Tecnico, assists.EmailTecnico, assists.created_at 
              FROM assists 
              JOIN clientes ON assists.company_id = clientes.id 
              WHERE assists.id=$id";
    $result = mysqli_query($conn, $sql12);
    $row = mysqli_fetch_assoc($result);
}

// Update the assist record
if (isset($_POST['edit'])) {
    $id = $_POST['id'];
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

    // Validar o formato das horas
    if (!preg_match('/^\d{2}:\d{2}$/', $hours_spent)) {
        echo "<script>alert('Formato de horas inválido. Use o formato hh:mm.');</script>";
    } else {
        $sql13 = "UPDATE assists SET 
                  company_id='$company_id', 
                  problem='$problem', 
                  help_description='$help_description', 
                  hours_spent='$hours_spent', 
                  service_status='$service_status', 
                  conditions='$conditions', 
                  lista_problemas='$lista_problemas', 
                  intervencao='$intervencao', 
                  Tecnico='$tecnico', 
                  EmailTecnico='$email_tecnico', 
                  created_at='$data_assistencia' 
                  WHERE id=$id";

        if (mysqli_query($conn, $sql13)) {
            echo "<script>alert('Assistance updated successfully!'); window.location.href = 'assist.php';</script>";
        } else {
            echo "<script>alert('Error updating assistance: " . mysqli_error($conn) . "');</script>";
        }
    }
}

// Fetch Companies from the clientes table
$sql14 = "SELECT id, company FROM clientes";
$companies_result = mysqli_query($conn, $sql14);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Assistance</title>
    <!-- Link to Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Link to the external CSS file -->
    <link rel="stylesheet" href="assist.css">
    <style>
        /* Estilos para o formulário */
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], input[type="email"], input[type="date"], 
        select, textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button[type="submit"] {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button[type="submit"]:hover {
            background-color: #45a049;
        }
        .back-button {
            display: inline-block;
            margin-bottom: 20px;
            color: #333;
            text-decoration: none;
            font-size: 24px;
        }
    </style>
</head>
<body>
    <!-- Back to Home Button -->
    <a href="home.php" class="back-button">
        <i class="fa-solid fa-house"></i>
    </a>

    <h1>Edit Assistance</h1>

    <!-- Edit Assistance Form -->
    <form method="post">
        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
        
        <div class="form-group">
            <label for="company">Company:</label>
            <select name="company" id="company" required>
                <option value="">Select a company</option>
                <?php 
                // Reset the pointer of the result set
                mysqli_data_seek($companies_result, 0);
                while ($company = mysqli_fetch_assoc($companies_result)) { 
                ?>
                    <option value="<?php echo $company['id']; ?>" <?php echo ($company['id'] == $row['company_id']) ? 'selected' : ''; ?>>
                        <?php echo $company['company']; ?>
                    </option>
                <?php } ?>
            </select>
        </div>

        <div class="form-group">
            <label for="problem">Problem:</label>
            <input type="text" name="problem" id="problem" placeholder="Describe the problem" value="<?php echo $row['problem']; ?>" required>
        </div>

        <div class="form-group">
            <label for="help">Help Provided:</label>
            <textarea name="help" id="help" placeholder="Describe the help provided" required><?php echo $row['help_description']; ?></textarea>
        </div>

        <div class="form-group">
            <label for="hours">Hours Spent:</label>
            <input type="text" name="hours" id="hours" placeholder="Enter hours spent (hh:mm)" value="<?php echo substr($row['hours_spent'], 0, 5); ?>" required>
        </div>

        <div class="form-group">
            <label for="service_status">Estado do Serviço:</label>
            <select name="service_status" id="service_status" required>
                <option value="">Selecione o estado do serviço</option>
                <option value="Completo" <?php echo ($row['service_status'] == 'Completo') ? 'selected' : ''; ?>>Completo</option>
                <option value="Em Curso" <?php echo ($row['service_status'] == 'Em Curso') ? 'selected' : ''; ?>>Em Curso</option>
            </select>
        </div>

        <div class="form-group">
            <label for="conditions">Condições:</label>
            <select name="conditions" id="conditions" required>
                <option value="">Selecione a condição</option>
                <option value="Em Garantia" <?php echo ($row['conditions'] == 'Em Garantia') ? 'selected' : ''; ?>>Em Garantia</option>
                <option value="A Faturar" <?php echo ($row['conditions'] == 'A Faturar') ? 'selected' : ''; ?>>A Faturar</option>
                <option value="Serviços internos" <?php echo ($row['conditions'] == 'Serviços internos') ? 'selected' : ''; ?>>Serviços internos</option>
                <option value="Com Contrato" <?php echo ($row['conditions'] == 'Com Contrato') ? 'selected' : ''; ?>>Com Contrato</option>
            </select>
        </div>

        <div class="form-group">
            <label for="lista_problemas">Lista de Problemas:</label>
            <select name="lista_problemas" id="lista_problemas" required>
                <option value="">Selecione um problema</option>
                <option value="Hardware" <?php echo ($row['lista_problemas'] == 'Hardware') ? 'selected' : ''; ?>>Hardware</option>
                <option value="Software" <?php echo ($row['lista_problemas'] == 'Software') ? 'selected' : ''; ?>>Software</option>
                <option value="Impressoras / Cópia" <?php echo ($row['lista_problemas'] == 'Impressoras / Cópia') ? 'selected' : ''; ?>>Impressoras / Cópia</option>
                <option value="Gestão de Dominios" <?php echo ($row['lista_problemas'] == 'Gestão de Dominios') ? 'selected' : ''; ?>>Gestão de Dominios</option>
                <option value="Migração de Servidores" <?php echo ($row['lista_problemas'] == 'Migração de Servidores') ? 'selected' : ''; ?>>Migração de Servidores</option>
                <option value="Backups" <?php echo ($row['lista_problemas'] == 'Backups') ? 'selected' : ''; ?>>Backups</option>
            </select>
        </div>

        <div class="form-group">
            <label for="intervencao">Intervenção:</label>
            <select name="intervencao" id="intervencao" required>
                <option value="">Selecione o tipo de intervenção</option>
                <option value="Presencial" <?php echo ($row['intervencao'] == 'Presencial') ? 'selected' : ''; ?>>Presencial</option>
                <option value="Remota" <?php echo ($row['intervencao'] == 'Remota') ? 'selected' : ''; ?>>Remota</option>
                <option value="Telefónica / Email" <?php echo ($row['intervencao'] == 'Telefónica / Email') ? 'selected' : ''; ?>>Telefónica / Email</option>
                <option value="Nossas Instalações" <?php echo ($row['intervencao'] == 'Nossas Instalações') ? 'selected' : ''; ?>>Nossas Instalações</option>
            </select>
        </div>

        <div class="form-group">
            <label for="tecnico">Técnico:</label>
            <input type="text" name="tecnico" id="tecnico" placeholder="Nome do técnico" value="<?php echo $row['Tecnico']; ?>" required>
        </div>

        <div class="form-group">
            <label for="email_tecnico">Email do Técnico:</label>
            <input type="email" name="email_tecnico" id="email_tecnico" placeholder="Email do técnico" value="<?php echo $row['EmailTecnico']; ?>" required>
        </div>

        <div class="form-group">
            <label for="data_assistencia">Data da Assistência:</label>
            <input type="date" name="data_assistencia" id="data_assistencia" value="<?php echo date('Y-m-d', strtotime($row['created_at'])); ?>" required>
        </div>

        <button type="submit" name="edit">Update Assistance</button>
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