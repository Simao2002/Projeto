<?php
session_start();

if (isset($_SESSION['id']) && isset($_SESSION['user_name'])) {

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>HOME</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
</head>
<body>
    <div class="top-right">
        <button class="b-log">
            <a href="logout.php">Logout</a>
        </button>  
    </div>
    <h1 class="hello">Hello, <?php echo $_SESSION['name']; ?></h1><br>
    <hr>
    <form class="big-button">

    <a href="clientes.php" class="big-button1">Clientes
        <i class="fas fa-user"></i>
    </a>

    <a href="assist.php" class="big-button2">Assistencias
        <i class="fa-solid fa-life-ring"></i>
    </a>

    <a href="extratos.php" class="big-button3">Extratos
        <i class="fa-solid fa-newspaper"></i>
    </a>

    </form>
</body>
</html>

<?php
}else{
    header("Location: index.php");
    exit();
}
?>