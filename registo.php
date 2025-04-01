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
    
    $sql = "SELECT COUNT(*) as count FROM assists 
            WHERE DATE_FORMAT(created_at, '%Y%m') = '$yearMonth'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    
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

    if (!preg_match('/^\d{2}:\d{2}$/', $hours_spent)) {
        echo "<script>alert('Formato de horas inválido. Use o formato hh:mm.'); window.location.href = 'registo.php';</script>";
        return;
    }

    $numero_guia = gerarNumeroGuia($conn, $data_assistencia);

    $sql10 = "INSERT INTO assists (numero_guia, company_id, problem, help_description, hours_spent, service_status, conditions, lista_problemas, intervencao, Tecnico, EmailTecnico, created_at) 
              VALUES ('$numero_guia', '$company_id', '$problem', '$help_description', '$hours_spent', '$service_status', '$conditions', '$lista_problemas', '$intervencao', '$tecnico', '$email_tecnico', '$data_assistencia')";

    if (mysqli_query($conn, $sql10)) {
        if ($conditions === "Com Contrato") {
            $sql11 = "UPDATE clientes SET SaldoHoras = SEC_TO_TIME(TIME_TO_SEC(SaldoHoras) - TIME_TO_SEC('$hours_spent')) WHERE id = $company_id";
            mysqli_query($conn, $sql11);
        }
        echo "<script>alert('Assistance added successfully!'); </script>";
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
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: url('646d4bb79ff76f75b218a08a_listicles-tech-image-large.jpg') no-repeat center center fixed;
        }
        
        .back-button {
            position: absolute;
            top: 30px;
            left: 30px;
            padding: 10px;
            background-color: #000000;
            color: white;
            text-decoration: none;
            border-radius: 50%;
            font-size: 18px;
            cursor: pointer;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        
        h1 {
            color: white;
            margin-bottom: 30px;
            text-align: center;
        }
        
        form {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }
        
        select, input[type="text"], input[type="email"], input[type="date"], textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
        }
        
        textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .button-group button {
            flex: 1;
        }
        
        button[type="submit"] {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 12px 20px;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        button[type="submit"]:hover {
            background-color: #45a049;
        }
        
        .pdf-button {
            background-color: #f44336;
            color: white;
            border: none;
            padding: 12px 20px;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .pdf-button:hover {
            background-color: #d32f2f;
        }
        
        .email-button {
            background-color: #2196F3;
            color: white;
            border: none;
            padding: 12px 20px;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .email-button:hover {
            background-color: #0b7dda;
        }
        
        .button-group button i {
            font-size: 18px;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
        }
        
        .form-col {
            flex: 1;
        }
        
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
            }
            .button-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <a href="home.php" class="back-button">
        <i class="fa-solid fa-house"></i>
    </a>

    <h1>Assistance Registration</h1>

    <form method="post" id="assistanceForm">
        <div class="form-row">
            <div class="form-col">
                <div class="form-group">
                    <label for="company">Company:</label>
                    <select name="company" id="company" required>
                        <option value="">Select a company</option>
                        <?php 
                        mysqli_data_seek($companies_result, 0);
                        while ($row = mysqli_fetch_assoc($companies_result)) { ?>
                            <option value="<?php echo $row['id']; ?>"><?php echo $row['company']; ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            
            <div class="form-col">
                <div class="form-group">
                    <label for="data_assistencia">Data da Assistência:</label>
                    <input type="date" name="data_assistencia" id="data_assistencia" required>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label for="problem">Problem:</label>
            <textarea name="problem" id="problem" placeholder="Describe the problem in detail" required></textarea>
        </div>

        <div class="form-group">
            <label for="help">Help Provided:</label>
            <textarea name="help" id="help" placeholder="Describe in detail the help provided" required></textarea>
        </div>

        <div class="form-row">
            <div class="form-col">
                <div class="form-group">
                    <label for="hours">Hours Spent:</label>
                    <input type="text" name="hours" id="hours" placeholder="Enter hours spent (hh:mm)" maxlength="5" required>
                </div>
            </div>
            
            <div class="form-col">
                <div class="form-group">
                    <label for="service_status">Estado do Serviço:</label>
                    <select name="service_status" id="service_status" required>
                        <option value="">Selecione o estado do serviço</option>
                        <option value="Completo">Completo</option>
                        <option value="Em Curso">Em Curso</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-row">
            <div class="form-col">
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
            </div>
            
            <div class="form-col">
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
            </div>
        </div>

        <div class="form-row">
            <div class="form-col">
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
            </div>
            
            <div class="form-col">
                <div class="form-group">
                    <label for="tecnico">Técnico:</label>
                    <input type="text" name="tecnico" id="tecnico" value="<?php echo htmlspecialchars($_SESSION['name']); ?>" readonly required>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label for="email_tecnico">Email do Técnico:</label>
            <input type="email" name="email_tecnico" id="email_tecnico" value="<?php echo htmlspecialchars($user_email); ?>" readonly required>
        </div>

        <div class="button-group">
            <button type="submit" name="add">Add Assistance</button>
            <button type="button" class="pdf-button" id="previewPdf">
                <i class="fas fa-file-pdf"></i> Preview PDF
            </button>
            <button type="button" class="email-button" id="sendEmail">
                <i class="fas fa-envelope"></i> Email
            </button>
        </div>
    </form>

    <script>
        // Função para formatar o campo de horas (hh:mm)
        function formatHoursInput(input) {
            let value = input.value.replace(/\D/g, '');

            if (value.length > 4) {
                value = value.slice(0, 4);
            }

            if (value.length > 2) {
                value = value.slice(0, 2) + ':' + value.slice(2);
            }

            input.value = value;
        }

        // Aplica a formatação ao campo de horas
        const hoursInput = document.getElementById('hours');
        if (hoursInput) {
            hoursInput.addEventListener('input', function () {
                formatHoursInput(this);
            });

            hoursInput.addEventListener('keypress', function (e) {
                if (this.value.length >= 5) {
                    e.preventDefault();
                }
            });
        }
        
        // Define a data atual como padrão no campo de data
        document.getElementById('data_assistencia').valueAsDate = new Date();
        
        // Botão de pré-visualização do PDF
        document.getElementById('previewPdf').addEventListener('click', function(e) {
            e.preventDefault();
            
            // Validar campos obrigatórios
            const requiredFields = document.querySelectorAll('#assistanceForm [required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = 'red';
                } else {
                    field.style.borderColor = '#ddd';
                }
            });
            
            if (!isValid) {
                alert('Por favor, preencha todos os campos obrigatórios antes de gerar a pré-visualização.');
                return;
            }
            
            // Criar um formulário temporário
            const form = document.getElementById('assistanceForm');
            const formData = new FormData(form);
            
            // Adicionar campo para identificar que é uma pré-visualização
            formData.append('preview', 'true');
            
            // Criar uma requisição AJAX para enviar os dados
            fetch('generate_pdf_preview.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.ok) {
                    return response.blob();
                }
                throw new Error('Network response was not ok.');
            })
            .then(blob => {
                const pdfUrl = URL.createObjectURL(blob);
                window.open(pdfUrl, '_blank');
                URL.revokeObjectURL(pdfUrl);
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Ocorreu um erro ao gerar a pré-visualização. Verifique o console para mais detalhes.');
            });
        });
        
        // Botão para enviar por email
        document.getElementById('sendEmail').addEventListener('click', function(e) {
            e.preventDefault();
            
            // Validar campos obrigatórios
            const requiredFields = document.querySelectorAll('#assistanceForm [required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = 'red';
                } else {
                    field.style.borderColor = '#ddd';
                }
            });
            
            if (!isValid) {
                alert('Por favor, preencha todos os campos obrigatórios antes de enviar por email.');
                return;
            }
            
            // Criar um formulário temporário
            const form = document.getElementById('assistanceForm');
            const formData = new FormData(form);
            
            // Adicionar campo para identificar que é para enviar email
            formData.append('send_email', 'true');
            
            // Mostrar mensagem de carregamento
            const emailBtn = document.getElementById('sendEmail');
            const originalText = emailBtn.innerHTML;
            emailBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
            emailBtn.disabled = true;
            
            // Criar uma requisição AJAX para enviar os dados
            fetch('email_send.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert('Email enviado com sucesso para: ' + data.email);
                    
                } else {
                    throw new Error(data.message || 'Erro ao enviar email');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Ocorreu um erro ao enviar o email: ' + error.message);
            })
            .finally(() => {
                emailBtn.innerHTML = originalText;
                emailBtn.disabled = false;
            });
        });
    </script>
</body>
</html>

<?php
mysqli_close($conn);
?>