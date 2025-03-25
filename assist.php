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

// Fetch Companies from the clientes table
$sql9 = "SELECT id, company FROM clientes";
$companies_result = mysqli_query($conn, $sql9);

// Add Assistance
if (isset($_POST['add'])) {
    $company_id = $_POST['company'];
    $problem = $_POST['problem'];
    $help_description = $_POST['help'];
    $hours_spent = $_POST['hours']; // Valor no formato hh:mm
    $service_status = $_POST['service_status'];
    $conditions = $_POST['conditions'];
    $lista_problemas = $_POST['lista_problemas'];
    $intervencao = $_POST['intervencao'];
    $tecnico = $_POST['tecnico'];
    $email_tecnico = $_POST['email_tecnico'];
    $data_assistencia = $_POST['data_assistencia'];

    // Validar o valor de hours para o formato hh:mm
    if (preg_match('/^\d{2}:\d{2}$/', $hours_spent)) {
        // O valor já está no formato hh:mm
    } else {
        echo "<script>
                alert('Formato de horas inválido. Use o formato hh:mm.');
                window.location.href = 'assist.php'; // Redireciona de volta para a página
              </script>";
        return; // Interrompe a execução do script sem parar o carregamento da página
    }

    // Inserir no banco de dados
    $sql10 = "INSERT INTO assists (company_id, problem, help_description, hours_spent, service_status, conditions, lista_problemas, intervencao, Tecnico, EmailTecnico, created_at) 
              VALUES ('$company_id', '$problem', '$help_description', '$hours_spent', '$service_status', '$conditions', '$lista_problemas', '$intervencao', '$tecnico', '$email_tecnico', '$data_assistencia')";

    if (mysqli_query($conn, $sql10)) {
        // SÓ DESCONTA HORAS SE A CONDIÇÃO NÃO FOR "SEM CONTRATO"
        if ($conditions !== "Sem Contrato") {
            // Atualizar o SaldoHoras do cliente
            $sql11 = "UPDATE clientes SET SaldoHoras = SEC_TO_TIME(TIME_TO_SEC(SaldoHoras) - TIME_TO_SEC('$hours_spent')) WHERE id = $company_id";
            mysqli_query($conn, $sql11);
        }

        echo "<script>alert('Assistance added successfully!');</script>";
    } else {
        echo "<script>alert('Error adding assistance: " . mysqli_error($conn) . "');</script>";
    }
}

// Fetch Assists with Company Names
$sql11 = "SELECT assists.id, clientes.company, assists.problem, assists.help_description, assists.hours_spent, assists.service_status, assists.conditions, assists.lista_problemas, assists.intervencao, assists.Tecnico, assists.EmailTecnico, assists.created_at 
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
    <!-- Link to Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Link to the external CSS file -->
    <link rel="stylesheet" href="assist.css">
    <style>
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed; /* Fixo na tela */
            z-index: 1000; /* Garante que o modal fique acima de outros elementos */
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto; /* Permite rolagem dentro do modal, se necessário */
            background-color: rgba(0, 0, 0, 0.5); /* Fundo escurecido */
        }
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto; /* Centraliza o modal verticalmente */
            padding: 20px;
            border: 1px solid #888;
            width: 50%;
            max-width: 600px; /* Limita a largura máxima */
            position: relative; /* Para posicionar o botão de fechar corretamente */
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <!-- Back to Home Button -->
    <a href="home.php" class="back-button">
        <i class="fa-solid fa-house"></i>
    </a>

    <h1>Assistance</h1>

    <!-- Add Assistance Button -->
    <button id="addAssistanceBtn" style="font-size: 24px; margin-bottom: 20px;background-color: green;color: #ffffff;">+</button>

    <!-- Add Assistance Modal -->
    <div id="addAssistanceModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
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
                        <option value="Sem Contrato">Sem Contrato</option> <!-- CORRIGIDO O TEXTO -->
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
                    <input type="text" name="tecnico" id="tecnico" placeholder="Nome do técnico" required>
                </div>

                <div class="form-group">
                    <label for="email_tecnico">Email do Técnico:</label>
                    <input type="email" name="email_tecnico" id="email_tecnico" placeholder="Email do técnico" required>
                </div>

                <div class="form-group">
                    <label for="data_assistencia">Data da Assistência:</label>
                    <input type="date" name="data_assistencia" id="data_assistencia" required>
                </div>

                <button type="submit" name="add">Add Assistance</button>
            </form>
        </div>
    </div>

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
            <td><?php echo substr($row['hours_spent'], 0, 5); ?></td> <!-- Exibe apenas hh:mm -->
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
                <a href="?delete=<?php echo $row['id']; ?>" class="delete" onclick="return confirm('Are you sure?')">Delete</a>
            </td>
        </tr>
    <?php } ?>
</table>

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
        hoursInput.addEventListener('input', function () {
            formatHoursInput(this);
        });

        // Limita o campo a 5 caracteres (hh:mm)
        hoursInput.addEventListener('keypress', function (e) {
            if (this.value.length >= 5) {
                e.preventDefault();
            }
        });

        // Modal Script
        var modal = document.getElementById("addAssistanceModal");
        var btn = document.getElementById("addAssistanceBtn");
        var span = document.getElementsByClassName("close")[0];

        btn.onclick = function() {
            modal.style.display = "block";
        }

        span.onclick = function() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>

<?php
mysqli_close($conn);
?>