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

$id = $_GET['id'];
$sql6 = "SELECT * FROM clientes WHERE id=$id";
$result = mysqli_query($conn, $sql6);
$row = mysqli_fetch_assoc($result);

if (isset($_POST['edit'])) {
    $name = $_POST['company'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    $sql7 = "UPDATE clientes SET company='$name', email='$email', phone='$phone' WHERE id=$id";
    mysqli_query($conn, $sql7);
    header("Location: clientes.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Client</title>
</head>
<body>
    <h1>Edit Client</h1>

    <form method="post">
        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
        <input type="text" name="company" value="<?php echo $row['company']; ?>" required>
        <input type="email" name="email" value="<?php echo $row['email']; ?>" required>
        <input type="text" name="phone" value="<?php echo $row['phone']; ?>" required>
        <button type="submit" name="edit">Save Changes</button>
    </form>
</body>
</html>

<?php
mysqli_close($conn);
?>