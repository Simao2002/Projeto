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
    $hours_spent = $_POST['hours'];

    $sql10 = "INSERT INTO assists (company_id, problem, help_description, hours_spent) 
              VALUES ('$company_id', '$problem', '$help_description', '$hours_spent')";

    if (mysqli_query($conn, $sql10)) {
        echo "<script>alert('Assistance added successfully!');</script>";
    } else {
        echo "<script>alert('Error adding assistance: " . mysqli_error($conn) . "');</script>";
    }
}

// Fetch Assists with Company Names
$sql11 = "SELECT assists.id, clientes.company, assists.problem, assists.help_description, assists.hours_spent, assists.created_at 
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
                        <?php while ($row = mysqli_fetch_assoc($companies_result)) { ?>
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
                    <input type="number" name="hours" id="hours" placeholder="Enter hours spent" required>
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
            <th>Date</th>
            <th>Actions</th>
        </tr>
        <?php while ($row = mysqli_fetch_assoc($assists_result)) { ?>
            <tr>
                <td><?php echo $row['company']; ?></td>
                <td><?php echo $row['problem']; ?></td>
                <td><?php echo $row['help_description']; ?></td>
                <td><?php echo $row['hours_spent']; ?></td>
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
        // Get the modal
        var modal = document.getElementById("addAssistanceModal");

        // Get the button that opens the modal
        var btn = document.getElementById("addAssistanceBtn");

        // Get the <span> element that closes the modal
        var span = document.getElementsByClassName("close")[0];

        // When the user clicks the button, open the modal 
        btn.onclick = function() {
            modal.style.display = "block";
        }

        // When the user clicks on <span> (x), close the modal
        span.onclick = function() {
            modal.style.display = "none";
        }

        // When the user clicks anywhere outside of the modal, close it
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