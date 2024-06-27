<?php
session_start();
// Check if user is logged in, otherwise redirect to login page
if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password_db = "";
$dbname = "exp";

$conn = new mysqli($servername, $username, $password_db, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// Retrieve user's details
$userID = $_SESSION['UserID'];

$sqlUserDetails = "SELECT Username, Email FROM Users WHERE UserID = $userID";
$resultUserDetails = $conn->query($sqlUserDetails);

// Fetch user's details
if ($resultUserDetails->num_rows > 0) {
    $rowUserDetails = $resultUserDetails->fetch_assoc();
    $username = $rowUserDetails['Username'];
    $email = $rowUserDetails['Email'];
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $expenseType = $_POST['expenseType'];
    $amount = $_POST['amount'];
    $expenseDate = $_POST['expense_date'];
    $description = $_POST['description'];
    $userID = $_SESSION['UserID'];

    // Check if the expense type exists in the ExpenseTypes table
    $sql = "SELECT ExpenseTypeID FROM ExpenseTypes WHERE TypeName = '$expenseType'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // If the expense type exists, retrieve the ExpenseTypeID
        $row = $result->fetch_assoc();
        $expenseTypeID = $row['ExpenseTypeID'];
    } else {
        // If the expense type doesn't exist, insert it into the ExpenseTypes table
        $insertExpenseTypeSql = "INSERT INTO ExpenseTypes (TypeName) VALUES ('$expenseType')";
        if ($conn->query($insertExpenseTypeSql) === TRUE) {
            // Retrieve the auto-generated ExpenseTypeID
            $expenseTypeID = $conn->insert_id;
        } else {
            echo "Error: " . $insertExpenseTypeSql . "<br>" . $conn->error;
            exit();
        }
    }

    // Insert expense into database
    $insertExpenseSql = "INSERT INTO Expenses (UserID, ExpenseTypeID, Amount, ExpenseDate, Description) 
                        VALUES ($userID, $expenseTypeID, $amount, '$expenseDate', '$description')";

    if ($conn->query($insertExpenseSql) === TRUE) {
        // Set session variable to indicate that success message should be displayed
        $_SESSION['show_success_message'] = true;
        // Redirect to the same page to prevent form resubmission
        header("Location: addexp.php");
        exit();
    } else {
        echo "Error: " . $insertExpenseSql . "<br>" . $conn->error;
    }
}

// Check if the success message should be displayed
$showSuccessMessage = isset($_SESSION['show_success_message']) && $_SESSION['show_success_message'];
if ($showSuccessMessage) {
    // Unset the session variable after displaying the success message
    unset($_SESSION['show_success_message']);
}
?>


<!DOCTYPE html>
<html>
<head>
    <title>Add New Expense</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&family=Raleway:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <style>
        body {
            background-color:rgb(127, 60, 254);
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Raleway", sans-serif;
            font-optical-sizing: auto;
            font-weight: <weight>;
            font-style: normal;
        }
        .container {
            text-align: center;
            background-color: white;
            width: 73%;
            height: auto; /* Set height to auto */
            margin: 20px auto; /* Adjust margin for better spacing */
            margin-left: 350px;
            padding: 20px; /* Add padding */
            border-radius: 10px;
            box-shadow: 2px 2px 4px 2px rgb(0, 0, 0, 0.5);
            position: relative; /* Position relative for absolute positioning of footer */
            z-index: 1; /* Ensure the container is above the footer */
        }

        .container h1{
        color:rgba(139,38,178,255);
        font-size:40px;
        }

        form {
            margin-top: 20px;
        }

        label {
            display: inline-block;
            margin-bottom: 5px;
            font-size:20px;
            font-weight:700;
        }

        select,
        input[type="number"],
        textarea,
        input[type="date"],
        input[type="text"],
        input[type="submit"] {
            width: 90%;
            padding: 10px;
            margin-bottom: 30px;
            border: 1px solid rgb(127, 60, 254);
            border-radius: 10px;
            box-sizing: border-box;
            font-size: 16px;
        }

        input[type="submit"] {
            background-color: rgb(127, 60, 254);
            color: white;
            cursor: pointer;
            transition:background-color 0.3s ease;
        }

        input[type="submit"]:hover {
            background-color: #fff;
            color:rgb(127, 60, 254);
        }

        a {
            color: rgb(127, 60, 254);
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        header{
            position: fixed;
            left:0;
            top:0;
            z-index:0;
        }
        footer {
            position: fixed;
            bottom: 0;
            left:0;
            width:100%;
            z-index: 0; /* Ensure the footer is behind other elements */
        }
        footer img{
            bottom:0;
            width:100%;
        }
        #h1{
            letter-spacing:2px;
            text-decoration:underline;
            font-size:40px;
            margin-left: 40px;
            z-index: 2;
            color: white;
            position: fixed; /* Fixed positioning*/ 
            top: 80px;  
        } 

        .title h2 {
            text-transform: uppercase;
            font-size: 40px;
            margin-left: 0;
            z-index: 1;
            color: white;
            position: fixed;
            left: 80px; /* Adjust left position */
            top: 180px; /* Align with the top */
            font-weight: 400;
        }

        .title h3 {
            font-size: 20px;
            z-index: 1;
            color: white;
            position: fixed;
            left: 80px; /* Adjust left position */
            top: 240px; /* Align with the top */
            font-weight: 400;
        }
     
        #date-container {
            text-align: center;
        }

        #date {
            font-weight:400;
            font-size: 18px;
            color: #333;
        }
        .success-mesasge{
            color:green;
            
        }
        textarea {
           max-width:90%;
           min-width:90%;
            width: 100%;
           
        }
        .info {
            left:10px;
    top: 230px;
    position: fixed;
    width:330px;
    max-width: 330px;
    color: white;
    z-index: 2;
    text-align:center;
    overflow: hidden; /* Hide any overflowing content */
}

.info h2 {
    text-transform:uppercase;
    font-size: 40px;
    margin: 0; /* Remove default margin */
    padding: 5px 0; /* Add padding for spacing */
    text-align: center; /* Center-align the text */
}

.info h3{
    font-size: 20px;
    margin: 5px 0; /* Add margin for spacing */
    text-align: center; /* Center-align the text */
}

.info button {
    font-size: 25px;
    font-weight:700;
    border: none;
    cursor: pointer;
    border-radius: 5px;
    background-color: transparent;
    color: white;
    display: block;
    transition: all 0.3s ease;
    margin-top: 20px;
    margin-left: auto; /* Auto margin for centering */
    margin-right: auto; /* Auto margin for centering */
}

.info button:hover {
    transform: scale(1.2, 1.2);
}
    </style>
</head>
<body>
    <header>
        <img src="images/head.svg" alt="">
    </header>
    <div class="title">
    <h1 id="h1">QuantaCash</h1>
    
    </div>
    <div class="info">
        <h2><?php echo $username; ?></h2>
        <h3><?php echo $email; ?></h3>
        
        <button onclick="window.location.href='dashboard.php';" style="margin-top:30px;">Dashboard</button>
        <button  onclick="window.location.href='budget.php';  ">Budget</button>
        <button  onclick="window.location.href='addexp.php';" style="font-size: 25px; font-weight: 900;">Add Expense</button>
        <button  onclick="logout()">Logout</button>
    
    </div>
    
    <div class="container">
        <h1>Add New Expense</h1>
        <div id="date-container">
        <p id="date"></p>
    </div>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <label for="expense_type">Expense Type:</label><br>
            <input type="text" id="expenseType" name="expenseType" required><br>
            <label for="amount">Amount:</label><br>
            <input type="number" id="amount" name="amount" required><br>
            <label for="description">Description:</label><br>
            <textarea id="description" name="description"></textarea><br>
            <label for="expense_date">Expense Date:</label><br>
            <input type="date" id="expense_date" name="expense_date" required><br><br>
            <input type="submit" value="Add Expense">
        </form>
        <?php if ($showSuccessMessage): ?>
    <div class="success-message">
        Expense added successfully
    </div>
<?php endif; ?>
        

    </div>

    <footer>
        <img src="images/wavy_footer.svg" alt="">
    </footer>

    <script>
        function logout() {
            // Perform logout functionality here, such as clearing session variables or redirecting to the logout page
            window.location.href = "logout.php"; // Redirect to logout.php or any other logout endpoint
        }
        document.addEventListener("DOMContentLoaded", function () {
        var dateContainer = document.getElementById("date");
        var currentDate = new Date();
        var day = currentDate.getDate();
        var month = currentDate.getMonth() + 1; // Month is zero-based, so we add 1
        var year = currentDate.getFullYear();

        // Pad day and month with leading zeros if needed
        if (day < 10) {
            day = '0' + day;
        }
        if (month < 10) {
            month = '0' + month;
        }

        var formattedDate = day + '/' + month + '/' + year;
        dateContainer.textContent = formattedDate;
    });

    
    </script>
</body>
</html>
