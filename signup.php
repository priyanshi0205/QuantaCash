<?php
session_start(); // Start the session to work with session variables

// Database configuration
$servername = "localhost"; // Change this to your database server name
$username = "root"; // Change this to your database username
$password = ""; // Change this to your database password
$dbname = "exp"; // Change this to your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $username = $_POST['username'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = $_POST['password'];

    // SQL query to insert user data into Users table
    $sql = "INSERT INTO Users (Username, Email, Phone, Password) VALUES ('$username', '$email', '$phone', '$password')";

    // Execute query
    try {
        if (mysqli_query($conn, $sql)) {
            // Redirect to login page after successful sign up
            header("Location: login.php");
            exit();
        } else {
            echo "Error: " . $sql . "<br>" . mysqli_error($conn);
        }
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1062) { // Check if the error is due to duplicate entry
            $_SESSION['error'] = "Email or phone already in use";
            header("Location: signup.php"); // Redirect back to the signup page
            exit(); // Ensure script execution stops after redirection
        } else {
            echo "Error: " . $e->getMessage();
        }
    }
}

// Clear the error message after displaying it
if(isset($_SESSION['error'])) {
    $errorMessage = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&family=Raleway:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #fff;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Raleway", sans-serif;
            font-optical-sizing: auto;
            font-weight: <weight>;
            font-style: normal;
            
        }
        .container
        {
            
            display:flex;
            width:650px;
            margin: 20px auto;
            /* padding: 30px;
            padding-top:50px;
            padding-bottom:50px; */
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3); */
        }
        .right {
            padding:30px;
            width:300px;
            height:350px;
            align-items:center;
           
        }
          
        
        .btn{
            color:#7f3cfe;
            border-radius:10px;
            margin-left:60px;
            width:100px;
            background-color:#fff;
            cursor: pointer;
            text-decoration:none;
            padding:8px;
            transition: background-color 0.3s ease-in-out;
            margin-top:10px;
        }
        .btn:hover{
            color:white;
            background-color:#7f3cfe;
            border:0.5px solid white;
            transition: background-color 0.3s ease-in-out;
            
        }
        .left img{
            height:300px;
        }
        .left{
            border-radius:10px 0px 0px 10px;
            color:white;
            padding:30px;
            background-color: #7f3cfe;;
            height:350px;
            width:300px;
            
        }
        
        h1 {
            font-size:50px;
            padding:30px;
            text-align: center;
        }
        label {
        
        }
        input{
            margin-top:10px;
        }
        input[type="email"],
        input[type="text"],
        input[type="password"],
        input[type="submit"] {
            width: 100%;
            padding:3px;
            margin-bottom:5px;
            border: 1px solid #ccc;
            border-radius: 3px;
            box-sizing: border-box; /* Make sure padding and border don't increase the width */
            transition: background-color 0.3s ease-in-out;
        }
        input[type="submit"] {
            padding:10px;
            background-color:#7f3cfe;
            color:white;
            cursor: pointer;
            margin-left:20%;
            width:50%;
            border-radius:10px;
            transition: background-color 0.3s ease-in-out;
        }
        input[type="submit"]:hover {
            color:#7f3cfe;
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
    p{
        color:red;
        margin-left:60%;
    }
    
  
    </style>
</head>
<body>
    <h1>Sign Up</h1>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <div class="container">
            <div class="left">
                <img src="images/D4_sign_Up.png" alt="">
                <a href="./login.php" class="btn">Login</a>
            </div>
            <div class="right">
               
                <label for="username">Username:</label><br>
                <input type="text" id="username" name="username" required><br><br>

                <label for="email">Email:</label><br>
                <input type="email" id="email" name="email" required><br><br>

                <label for="phone">Phone:</label><br>
                <input type="text" id="phone" name="phone" required><br><br>

                <label for="password">Password:</label><br>
                <input type="password" id="password" name="password" required><br><br>

                <input type="submit" value="Sign Up">
            </div>
        </div>
    </form>
    <?php if(isset($errorMessage)): ?>
                    <p class="error" ><?php echo $errorMessage; ?></p>
                <?php endif; ?>
    <!-- Your JavaScript code here -->

</body>
</html>