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


// Add Client
if (isset($_POST['add'])) {
    $name = $_POST['company'];
    $email = $_POST['email'];
    $postalcode = $_POST['PostalCode'];

    $sql3 = "INSERT INTO clientes (company, email, PostalCode) VALUES ('$name', '$email', '$postalcode')";
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

    $sql5 = "UPDATE clientes SET company='$name', email='$email', postalcode='$postalcode' WHERE id=$id";
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
    <!-- Link to the external CSS file -->
    <link rel="stylesheet" href="clientes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <a href="home.php" class="back-button">
        <i class="fa-solid fa-house"></i>
    </a>

    <h1>Clientes</h1>

    <!-- Add Client Form -->
    <form method="post">
        <input type="text" name="company" placeholder="Company Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="text" id="postalcode" name="PostalCode" placeholder="Postal Code" required>
        <button type="submit" name="add">Add Client</button>
    </form>
    <script>
        const postalCodeInput = document.getElementById('postalcode');

        postalCodeInput.addEventListener('input', function (e) {
            // Remove qualquer hífen existente
            let value = e.target.value.replace(/\D/g, '');

            // Adiciona o hífen na posição correta
            if (value.length > 4) {
                value = value.slice(0, 4) + '-' + value.slice(4, 7);
            }

            // Atualiza o valor do input
            e.target.value = value;
        });
    </script>
</body>

    <!-- List Clients -->
    <table>
        <tr>
            <th>ID</th>
            <th>Company</th>
            <th>Email</th>
            <th>Postal Code</th>
            <th>Actions</th>
        </tr>
        <?php while ($row = mysqli_fetch_assoc($result)) { ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo $row['company']; ?></td>
                <td><?php echo $row['email']; ?></td>
                <td><?php echo $row['PostalCode']; ?></td>
                <td class="actions">
                    <a href="edit.php?id=<?php echo $row['id']; ?>" class="edit">Edit</a>
                    <a href="?delete=<?php echo $row['id']; ?>" class="delete" onclick="return confirm('Are you sure?')">Delete</a>
                </td>
            </tr>
        <?php } ?>
    </table>
</body>
</html>

<?php
mysqli_close($conn);
?>