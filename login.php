<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Database connection
    $servername = "localhost";
    $username = "root";
    $password_db = "";
    $dbname = "exp";

    $conn = new mysqli($servername, $username, $password_db, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // SQL query to fetch user details
    $sql = "SELECT * FROM Users WHERE Email = '$email' AND Password = '$password'";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
        // Login successful
        $row = $result->fetch_assoc();
        $_SESSION['UserID'] = $row['UserID'];
        $_SESSION['Username'] = $row['Username'];
        header("Location: dashboard.php"); // Redirect to dashboard after successful login
    } else {
        // Invalid credentials
        $_SESSION['error'] = "Invalid email or password";
        header("Location: login.php"); // Redirect back to the login page
        exit(); // Ensure script execution stops after redirection
    }

    $conn->close();
}
?>



<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&family=Raleway:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <style>
        body {
            background-color:#fff;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Raleway", sans-serif;
            font-optical-sizing: auto;
            font-weight: <weight>;
            font-style: normal;
        }
        .container {
            display:flex;
            width:650px;
            margin: 20px auto;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
        }
        .right {
            border-radius:0px 10px 10px 0px;
            padding:30px;
            width:300px;
            height:350px;
            align-items:center;
            background-color: #7f3cfe;;
        }
        .btn {
            color: #7f3cfe;
            border-radius:10px;
            margin-left:60px;
            width:100px;
            background-color:#fff;
            cursor: pointer;
            text-decoration:none;
            padding:8px;
            transition: background-color 0.2s ease-in-out;
        }
        .btn:hover {
            background-color:#7f3cfe;
            border:0.5px solid white;
            transition: background-color 0.2s ease-in-out;
            color: #fff;
        }
        .right img {
            margin-top:30px;
            height:250px;
            margin-bottom:20px;
        }
        .left {
            border-radius:10px 0px 0px 10px;
            padding:30px;
            height:350px;
            width:300px;
        }
        .email {
            margin-top:50px;
        }
        .pass {
            margin-top:20px;
        }
        h1 {
            font-size:50px;
            padding:30px;
            text-align: center;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input[type="email"],
        input[type="password"],
        input[type="submit"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 3px;
            box-sizing: border-box;
            transition: background-color 0.2s ease-in-out;
        }
        input[type="submit"] {
            background-color:#7f3cfe;
            color:white;
            cursor: pointer;
            margin-top:40px;
            margin-left:20%;
            width:50%;
            border-radius:10px;
            transition: background-color 0.2s ease-in-out;
        }
        input[type="submit"]:hover {
            color: #7f3cfe;
            background-color: #fff;
            border:0.5px solid #7f3cfe;
        }
        a {
            display: block;
            text-align: center;
            color:white;
            text-decoration:none;
        }
        a:hover{
            color: #7f3cfe;
        }
        span {
            color:white;
            margin-left:20px;
        }
        p.error {
            color: red;
            margin-top: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <h1>Login</h1>
   
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <div class="container">
            <div class="left">
                
                <label for="email" class="email">Email:</label><br>
                <input type="email" id="email" name="email" required><br>
                <label for="password" class="pass">Password:</label><br>
                <input type="password" id="password" name="password" required><br><br>
                <input type="submit" value="Login">
            </div>
            <div class="right">
                <span>Spend smarter, Save farther...</span>
                <!-- <img src="log.jpg" alt=""> -->
                <img src="images/D3_Chillar_Bottle.png" alt="">
                <a class="btn" href="./signup.php">Sign up</a>
            </div>
        </div>
    </form>
    <?php if(isset($_SESSION['error'])): ?>
        <p class="error"><?php echo $_SESSION['error']; ?></p>
        <?php unset($_SESSION['error']); ?> <!-- Clear the error message after displaying it -->
    <?php endif; ?>
</body>
</html>

