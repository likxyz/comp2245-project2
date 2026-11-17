<?php

//files needed
require_once 'config.php';
require_once 'auth.php';
require_once 'csrf.php';

//if the user is already logged in, redirect them 
if(isLoggedIn()){
    header("Location: tickets.php");
    exit;
}

$error = "";

//checks if the form was submitted
if($_SERVER["REQUEST_METHOD"]==="POST"){

    //verify the csrf token
    verifyCSRFToken($_POST["csrf_token"] ?? "");

    //get email and password
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    //search for user in database
    $sql = "SELECT * FROM users WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(["email"=>$email]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    //check the password
    if ($user && password_verify($password, $user["password"])){

        //create a new session ID
        session_regenerate_id(true);

        //store user info in the session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['firstname'] = $user['firstname'];
        $_SESSION['lastname'] = $user['lastname'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];

        //redirect after succesful login
        header("Location: tickets.php");
        exit;
    }else{
        $error = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix-It Desk Login</title>
</head>
<body>

    <h1>Fix-It Desk</h1>
    <h2>Login</h2>

    <?php if ($error !== ''): ?>
        <p><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form method="POST" action="login.php">

        <input
            type="hidden"
            name="csrf_token"
            value="<?php echo htmlspecialchars(generateCSRFToken(), ENT_QUOTES, 'UTF-8'); ?>"
        >

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>

        <br><br>

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>

        <br><br>

        <button type="submit">Login</button>

    </form>

</body>
</html>