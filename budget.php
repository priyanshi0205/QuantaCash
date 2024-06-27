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

// Update budget if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Process form submission
    $userID = $_SESSION['UserID'];
    $newExpenseType = isset($_POST['newExpenseType']) ? $_POST['newExpenseType'] : '';
    $newBudgetAmount = isset($_POST['newBudgetAmount']) ? floatval($_POST['newBudgetAmount']) : 0;

    if (!empty($newExpenseType) && $newBudgetAmount > 0) {
        // Check if the expense type already exists in the ExpenseTypes table
        $sqlCheckExpenseType = "SELECT ExpenseTypeID FROM ExpenseTypes WHERE TypeName = ?";
        $stmtCheckExpenseType = $conn->prepare($sqlCheckExpenseType);
        $stmtCheckExpenseType->bind_param("s", $newExpenseType);
        $stmtCheckExpenseType->execute();
        $resultCheckExpenseType = $stmtCheckExpenseType->get_result();
        
        if ($resultCheckExpenseType && $resultCheckExpenseType->num_rows > 0) {
            // Expense type exists, insert budget data
            $row = $resultCheckExpenseType->fetch_assoc();
            $expenseTypeID = $row['ExpenseTypeID'];
        } else {
            // Expense type does not exist, insert into ExpenseTypes first
            $sqlInsertExpenseType = "INSERT INTO ExpenseTypes (TypeName) VALUES (?)";
            $stmtInsertExpenseType = $conn->prepare($sqlInsertExpenseType);
            $stmtInsertExpenseType->bind_param("s", $newExpenseType);
            if ($stmtInsertExpenseType->execute()) {
                $expenseTypeID = $stmtInsertExpenseType->insert_id;
            } else {
                echo "Error: " . $conn->error;
                exit(); // Exit the script if there's an error
            }
            // Close prepared statement if initialized
            if (isset($stmtInsertExpenseType)) {
                $stmtInsertExpenseType->close();
            }
        }
        // Insert budget data
        $sqlInsertBudget = "INSERT INTO Budgets (UserID, ExpenseTypeID, BudgetAmount) VALUES (?, ?, ?)";
        $stmtInsertBudget = $conn->prepare($sqlInsertBudget);
        $stmtInsertBudget->bind_param("iid", $userID, $expenseTypeID, $newBudgetAmount);
        if (!$stmtInsertBudget->execute()) {
            echo "Error: " . $conn->error;
        }
        
        // Close prepared statements
        $stmtCheckExpenseType->close();
        $stmtInsertBudget->close();
        
        // Redirect the user to another page to avoid form resubmission
        header("Location: budget.php");
        exit();
    }

    // Process delete request
    if (isset($_POST['deleteExpenseType'])) {
        $deleteExpenseType = $_POST['deleteExpenseType'];
        
        // Delete budget entry for the specified expense type
        $sqlDeleteBudget = "DELETE FROM Budgets WHERE UserID = ? AND ExpenseTypeID IN (SELECT ExpenseTypeID FROM ExpenseTypes WHERE TypeName = ?)";
        $stmtDeleteBudget = $conn->prepare($sqlDeleteBudget);
        if (!$stmtDeleteBudget) {
            echo "Error preparing delete statement: " . $conn->error;
        } else {
            $stmtDeleteBudget->bind_param("is", $userID, $deleteExpenseType);
            if (!$stmtDeleteBudget->execute()) {
                echo "Error executing delete statement: " . $stmtDeleteBudget->error;
            } else {
                // Redirect after successful deletion
                header("Location: budget.php");
                exit();
            }
            // Close prepared statement
            $stmtDeleteBudget->close();
        }
    }
}

// Fetch user's budget data from the database
$userID = $_SESSION['UserID'];
$sqlBudget = "SELECT et.TypeName, COALESCE(b.BudgetAmount, 0) AS BudgetAmount
              FROM ExpenseTypes et
              LEFT JOIN Budgets b ON et.ExpenseTypeID = b.ExpenseTypeID AND b.UserID = $userID";
$resultBudget = $conn->query($sqlBudget);

// Format budget data for chart
$budgetData = array();
while ($row = $resultBudget->fetch_assoc()) {
    $budgetAmount = floatval($row['BudgetAmount']);
    if ($budgetAmount > 0) {
        $budgetData[$row['TypeName']] = $budgetAmount;
    }
}

// Retrieve user's details from the database

$userID = $_SESSION['UserID'];
$sqlUserDetails = "SELECT Username, Email, Income FROM Users WHERE UserID = $userID";
$resultUserDetails = $conn->query($sqlUserDetails);

if ($resultUserDetails->num_rows > 0) {
    $rowUserDetails = $resultUserDetails->fetch_assoc();
    $username = $rowUserDetails['Username'];
    $email = $rowUserDetails['Email'];
    $income = $rowUserDetails['Income']; // Add this line to retrieve the income
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userID = $_SESSION['UserID'];
    $income = isset($_POST['income']) ? floatval($_POST['income']) : 0;

    if ($income > 0) {
        // Update user's income in the database
        $sqlUpdateIncome = "UPDATE Users SET Income = ? WHERE UserID = ?";
        $stmtUpdateIncome = $conn->prepare($sqlUpdateIncome);
        $stmtUpdateIncome->bind_param("di", $income, $userID);
        if (!$stmtUpdateIncome->execute()) {
            echo "Error updating income: " . $conn->error;
        }
        // Close prepared statement
        $stmtUpdateIncome->close();

        // Redirect the user after successful submission
        header("Location: budget.php");
        exit();
    }
}

// Close database connection
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Budget</title>
    <!-- Load necessary libraries for charts (e.g., Chart.js) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&family=Raleway:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: rgb(127, 60, 254);
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
        font-size: 24px;
        color: #333;
        }
        #usrname{
            text-transform: uppercase;
        }
        table{
            margin-left:auto; 
            margin-right:auto;
          
        }
        input{
            margin-left:50px;
            width: 80%;
            padding: 10px;
            margin-bottom: 30px;
            border: 1px solid rgb(127, 60, 254);
            border-radius: 10px;
            font-size: 16px;
        }
        input[type="submit"]{
            width: 84%;
            padding: 10px;
            margin-bottom: 30px;
            border: 1px solid rgb(127, 60, 254);
            border-radius: 10px;
            margin-left:50px;
            font-size: 16px;
        }

        table {
            margin-left:auto;
            margin-right:auto;
            width: 95%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 30px;
            background-color: rgb(127, 60, 254.0.4);
        }

     
        td {
            padding: 10px;
            border: 1px solid #dddddd;
            text-align: center;
        }

        /* Adjust the text alignment and style of the headers */
        th {
            padding: 10px;
            border: 1px solid #dddddd;
            text-align: center;
            color: white;
            background-color: rgb(127, 60, 254, 1);
            font-weight: bold;
            text-transform: uppercase;
        }

        .tab{
            margin-left:auto;
            margin-right:auto;
        }
        canvas{
            margin-left:auto;
            margin-right:auto;
        }
        .chart{
            display:flex;
            align-items:center;
            justify-content:center;
        }
        #dlt{
            font-weight:300;
            width:50%;
            color: white;
            background-color: rgb(127, 60, 254);
            padding: 10px 10px;
            border: none;
            cursor: pointer;
            border-radius: 10px;
            margin-top:0;
            margin-left:10px;
            transition: all 0.3s ease;
            margin-bottom:0;
        }
        #dlt:hover {
            background-color: #fff;
            transform: scale(1.01, 1.01);
            transition: all 0.3s ease;
            color: rgb(127, 60, 254);
            border: 1px solid rgb(127, 60, 254);
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

@media (max-width: 480px) {
        #h1 {
            font-size: 24px;
            top: 50px;
            margin-left: 20px;
        }

        .title h2 {
            font-size: 24px;
            top: 120px;
            left: 20px;
        }

        .title h3 {
            font-size: 16px;
            top: 160px;
            left: 20px;
        }

        .info {
            width: 200px;
        }

        .info h2 {
            font-size: 24px;
        }

        .info h3 {
            font-size: 14px;
        }

        .info button {
            font-size: 20px;
        }

        .container {
            width: 100%;
            padding: 5px;
            margin: 5px auto;
        }

        input {
            width: 95%;
            margin-left: 2.5%;
        }

        input[type="submit"] {
            width: 95%;
            margin-left: 2.5%;
        }

        #dlt {
            width: 95%;
            margin-left: 2.5%;
        }
    }

    </style>
</head>
<header>
    <img src="images/head.svg" alt="">
</header>
<body>
    <div class="title">
    <h1 id="h1">QuantaCash</h1>
    
    </div>
    <div class="info">
        <h2><?php echo $username; ?></h2>
        <h3><?php echo $email; ?></h3>
        
        <button onclick="window.location.href='dashboard.php';" style="margin-top:30px;">Dashboard</button>
        <button  onclick="window.location.href='budget.php';" style="font-size: 25px; font-weight: 900;  ">Budget</button>
        <button  onclick="window.location.href='addexp.php';">Add Expense</button>
        <button  onclick="logout()">Logout</button>
    
    </div>
    
    
    <div class="container">
            <h1>Welcome to the Budget Page, <span id="usrname"><?php echo $_SESSION['Username']; ?></span> !</h1>
            <div class="add-income">
                <h2>Add Income</h2>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <input type="number" name="income" placeholder="Enter Income">
                <input type="submit" value="Add Income" class="add-income-btn">
                </form>
            </div>
            <h2 >Add Expense Type and Budget Amount</h2>
            <div class="chart">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <input type='text' name='newExpenseType' placeholder='New Expense Type'>
            <input type='number' name='newBudgetAmount' placeholder='Budget Amount'>
        
            <input type="submit" value="Add">
            </form>
            <canvas id="budgetChart" width="400" height="300"></canvas>
            </div>
            <h2>Current Budget Details</h2>
            <div class="tab">
            
            <table>
                <thead>
                    <tr>
                        <th>Expense Type</th>
                        <th>Budget Amount</th>
                        <th>Action</th> 
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($budgetData as $expenseType => $budgetAmount) {
                        if ($budgetAmount > 0) {
                            echo "<tr>";
                            echo "<td>$expenseType</td>";
                            echo "<td>$budgetAmount</td>";
                            // Add delete button with form for each row
                            echo "<td>";
                            echo "<form action='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "' method='post'>";
                            echo "<input type='hidden' name='deleteExpenseType' value='$expenseType'>";
                            echo "<input type='submit' id='dlt' value='Delete'>";
                            echo "</form>";
                            echo "</td>";
                            echo "</tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>
            </div>
            
        
</div>
<footer>
    <img src="images/wavy_footer.svg" alt="">
</footer>

<script>
    
        // Format remaining budget data for chart
        var remainingBudgetData = <?php echo json_encode(array_values($budgetData)); ?>;
        var budgetLabels = <?php echo json_encode(array_keys($budgetData)); ?>;
        
        var ctx = document.getElementById('budgetChart').getContext('2d');
        var chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: budgetLabels,
                datasets: [{
                    label: 'Budget',
                    data: remainingBudgetData,
                    backgroundColor: [
                        'rgba(255, 99, 132)',
                        'rgba(54, 162, 235)',
                        'rgba(255, 206, 86)',
                        'rgba(75, 192, 192)',
                        'rgba(153, 102, 255)',
                        'rgba(255, 159, 64)'
                    ],
                    borderColor: [
                        'rgb(255,255,255)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: false,
                maintainAspectRatio: false,
                legend: {
                    display: false
                },
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true
                        }
                    }]
                },
                title: {
                    display: true,
                    text: 'Remaining Budget'
                }
            }
        });
   document.addEventListener("DOMContentLoaded", function() {
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
    function logout() {
        // Perform logout functionality here, such as clearing session variables or redirecting to the logout page
        window.location.href = "logout.php"; // Redirect to logout.php or any other logout endpoint
    }
   
   
</script>

</body>
</html>