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
    $sql12 ="SELECT assists.id, assists.company_id, clientes.company, assists.problem, assists.help_description, assists.hours_spent 
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

    $sql13 = "UPDATE assists SET company_id='$company_id', problem='$problem', help_description='$help_description', hours_spent='$hours_spent' WHERE id=$id";

    if (mysqli_query($conn, $sql13)) {
        echo "<script>alert('Assistance updated successfully!'); window.location.href = 'assist.php';</script>";
    } else {
        echo "<script>alert('Error updating assistance: " . mysqli_error($conn) . "');</script>";
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
            <label for="help">Help Provided:</label>
            <textarea name="help" id="help" placeholder="Describe the help provided" required><?php echo $row['help_description']; ?></textarea>
        </div>

        <div class="form-group">
            <label for="problem">Problem:</label>
            <input type="text" name="problem" id="problem" placeholder="Describe the problem" value="<?php echo $row['problem']; ?>" required>
        </div>

        <div class="form-group">
            <label for="hours">Hours Spent:</label>
            <input type="number" name="hours" id="hours" placeholder="Enter hours spent" value="<?php echo $row['hours_spent']; ?>" required>
        </div>

        <button type="submit" name="edit">Update Assistance</button>
    </form>
</body>
</html>

<?php
mysqli_close($conn);
?>