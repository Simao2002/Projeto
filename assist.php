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

// Função para gerar o número da guia no formato YYYYMM-Nr
function gerarNumeroGuia($created_at, $id, $conn) {
    $date = new DateTime($created_at);
    $yearMonth = $date->format('Ym');
    
    // Contar quantas assistências existem no mesmo mês/ano com ID menor ou igual
    $sql = "SELECT COUNT(*) as count FROM assists 
            WHERE DATE_FORMAT(created_at, '%Y%m') = '$yearMonth' AND id <= $id";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    
    // O número sequencial é o count (começando de 1)
    $sequential = $row['count'] - 1; // Subtrai 1 para começar do 0
    
    return $yearMonth . '-' . str_pad($sequential, 2, '0', STR_PAD_LEFT);
}

// Processar filtros
$filter_field = isset($_GET['filter_field']) ? $_GET['filter_field'] : '';
$search_term = isset($_GET['search_term']) ? $_GET['search_term'] : '';
$filter_month = isset($_GET['filter_month']) ? $_GET['filter_month'] : '';
$filter_year = isset($_GET['filter_year']) ? $_GET['filter_year'] : '';

// Construir a query base
$sql11 = "SELECT assists.id, assists.numero_guia, clientes.company, assists.problem, assists.help_description, 
                  assists.hours_spent, assists.service_status, assists.conditions, assists.lista_problemas, 
                  assists.intervencao, assists.Tecnico, assists.EmailTecnico, assists.created_at 
           FROM assists 
           JOIN clientes ON assists.company_id = clientes.id";

$where_clauses = [];

// Adicionar condições de filtro se existirem
if (!empty($filter_field) && !empty($search_term)) {
    // Se o campo for 'company', precisamos filtrar na tabela clientes
    if ($filter_field == 'company') {
        $where_clauses[] = "clientes.company LIKE '%" . mysqli_real_escape_string($conn, $search_term) . "%'";
    } else {
        $where_clauses[] = "assists." . mysqli_real_escape_string($conn, $filter_field) . " LIKE '%" . mysqli_real_escape_string($conn, $search_term) . "%'";
    }
}

// Adicionar filtro por mês/ano
if (!empty($filter_month) && !empty($filter_year)) {
    $where_clauses[] = "MONTH(assists.created_at) = " . intval($filter_month) . " AND YEAR(assists.created_at) = " . intval($filter_year);
} elseif (!empty($filter_month)) {
    $where_clauses[] = "MONTH(assists.created_at) = " . intval($filter_month);
} elseif (!empty($filter_year)) {
    $where_clauses[] = "YEAR(assists.created_at) = " . intval($filter_year);
}

// Combinar todas as condições WHERE
if (!empty($where_clauses)) {
    $sql11 .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql11 .= " ORDER BY assists.created_at DESC";
$assists_result = mysqli_query($conn, $sql11);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assistance</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assist.css">
    <style>
        .search-wrapper {
            display: flex;
            justify-content: center; /* Centraliza horizontalmente */
            width: 100%;
            margin: 15px 0;
        }
        
        .search-container {
            display: flex;
            gap: 6px;
            align-items: center;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 6px;
            width: 25%;
            min-width: 600px;
            box-sizing: border-box;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .search-container form {
            display: flex;
            gap: 6px;
            width: 100%;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .search-container select, .search-container input {
            padding: 7px 10px;
            border-radius: 4px;
            border: 1px solid #ced4da;
            font-size: 14px;
            height: 36px;
            box-sizing: border-box;
            flex-grow: 1;
            min-width: 120px; /* Largura mínima para os campos */
        }
        
        .search-container button, .clear-filter {
            padding: 7px 14px;
            border-radius: 4px;
            cursor: pointer;
            height: 36px;
            font-size: 14px;
            box-sizing: border-box;
            white-space: nowrap;
            flex-shrink: 0; /* Impede que os botões encolham */
        }
        
        .search-container button {
            background-color: #4CAF50;
            color: white;
            border: none;
        }
        
        .search-container button:hover {
            background-color: #45a049;
        }
        
        .clear-filter {
            background-color: #f44336;
            color: white;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .clear-filter:hover {
            background-color: #d32f2f;
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-grow: 1;
            min-width: 200px; /* Garante espaço para dois campos lado a lado */
        }
        
        /* Ajustes específicos para os selects de data */
        .date-filter-group {
            display: flex;
            gap: 6px;
            flex-grow: 1;
        }
        
        .date-filter-group select {
            flex: 1;
            min-width: 100px;
        }
        
        /* Ajuste para telas menores */
        @media (max-width: 768px) {
            .search-container {
                width: 100%;
                min-width: auto;
            }
        }
    </style>
</head>
<body>
    <!-- Back to Home Button -->
    <a href="home.php" class="back-button">
        <i class="fa-solid fa-house"></i>
    </a>

    <h1>Assistance</h1>

    <div class="search-wrapper">
    <!-- Filtro de pesquisa - Versão Compacta e Responsiva -->
    <div class="search-container">
        <form method="get" action="assist.php">
            <!-- Grupo de campo + pesquisa -->
            <div class="filter-group">
                <select name="filter_field" id="filter_field">
                    <option value="">Todos campos</option>
                    <option value="numero_guia" <?= $filter_field == 'numero_guia' ? 'selected' : '' ?>>Nº Guia</option>
                    <option value="company" <?= $filter_field == 'company' ? 'selected' : '' ?>>Empresa</option>
                    <option value="problem" <?= $filter_field == 'problem' ? 'selected' : '' ?>>Problema</option>
                    <option value="Tecnico" <?= $filter_field == 'Tecnico' ? 'selected' : '' ?>>Técnico</option>
                </select>
                
                <input type="text" name="search_term" placeholder="Pesquisar..." value="<?= htmlspecialchars($search_term) ?>">
            </div>

            <!-- Grupo de datas -->
            <div class="date-filter-group">
                <select name="filter_month">
                    <option value="">Todos meses</option>
                    <?php for ($i = 1; $i <= 12; $i++): ?>
                        <option value="<?= $i ?>" <?= $filter_month == $i ? 'selected' : '' ?>>
                            <?= date('M', mktime(0, 0, 0, $i, 1)) ?>
                        </option>
                    <?php endfor; ?>
                </select>
                
                <select name="filter_year">
                    <option value="">Todos anos</option>
                    <?php for ($i = date('Y'); $i >= date('Y') - 5; $i--): ?>
                        <option value="<?= $i ?>" <?= $filter_year == $i ? 'selected' : '' ?>>
                            <?= $i ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <!-- Botões -->
            <button type="submit">Filtrar</button>
            
            <?php if (!empty($filter_field) || !empty($search_term) || !empty($filter_month) || !empty($filter_year)): ?>
                <a href="assist.php" class="clear-filter">Limpar</a>
            <?php endif; ?>
        </form>
    </div>
    </div>

    <!-- List Assists -->
    <table>
        <tr>
            <th>Nº Guia</th>
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
        <?php if (mysqli_num_rows($assists_result) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($assists_result)) { 
                $numeroGuia = gerarNumeroGuia($row['created_at'], $row['id'], $conn);
                ?>
                <tr>
                    <td><?php echo $row['numero_guia']; ?></td>
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
        <?php else: ?>
            <tr>
                <td colspan="13" style="text-align: center;">Nenhum resultado encontrado</td>
            </tr>
        <?php endif; ?>
    </table>
</body>
</html>

<?php
mysqli_close($conn);
?>