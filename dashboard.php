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

// Retrieve user's expenses from the database
$userID = $_SESSION['UserID'];
$sqlExpenses = "SELECT et.TypeName, SUM(e.Amount) AS TotalAmount
                FROM Expenses e
                INNER JOIN ExpenseTypes et ON e.ExpenseTypeID = et.ExpenseTypeID
                WHERE e.UserID = $userID
                GROUP BY et.TypeName";
$resultExpenses = $conn->query($sqlExpenses);

// Format expenses data for chart
$expensesData = array();
if ($resultExpenses->num_rows > 0) {
    while ($row = $resultExpenses->fetch_assoc()) {
        $expensesData[$row['TypeName']] = floatval($row['TotalAmount']);
    }
} else {
    // If there are no expenses, display a message
    $noExpensesMessage = "NO EXPENSES";
}

// Retrieve user's budget from the database
$sqlBudget = "SELECT et.TypeName, b.BudgetAmount AS BudgetAmount
              FROM ExpenseTypes et
              LEFT JOIN Budgets b ON et.ExpenseTypeID = b.ExpenseTypeID AND b.UserID = $userID";
$resultBudget = $conn->query($sqlBudget);

// Format budget data for chart
$budgetData = array();
if ($resultBudget->num_rows > 0) {
    while ($row = $resultBudget->fetch_assoc()) {
        $budgetData[$row['TypeName']] = floatval($row['BudgetAmount']);
    }
}

// Calculate remaining budget for each expense type
$remainingBudgetData = array();
foreach ($budgetData as $typeName => $budgetAmount) {
    $remainingBudgetData[$typeName] = $budgetAmount - ($expensesData[$typeName] ?? 0);
}


$sqlDetailedExpenses = "SELECT e.ExpenseID, et.TypeName, e.Amount, e.Description, e.ExpenseDate
                        FROM Expenses e
                        INNER JOIN ExpenseTypes et ON e.ExpenseTypeID = et.ExpenseTypeID
                        WHERE e.UserID = $userID";
$resultDetailedExpenses = $conn->query($sqlDetailedExpenses);

$sqlUserDetails = "SELECT Username, Email,Income FROM Users WHERE UserID = $userID";
$resultUserDetails = $conn->query($sqlUserDetails);

// Fetch user's details
if ($resultUserDetails->num_rows > 0) {
    $rowUserDetails = $resultUserDetails->fetch_assoc();
    $username = $rowUserDetails['Username'];
    $email = $rowUserDetails['Email'];
    $income = $rowUserDetails['Income'];
}
// Close database connection
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <!-- Load necessary libraries for charts (e.g., Chart.js) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            min-height:90vh;
        }
        .container h1 {
            color: rgba(139, 38, 178, 255);
            font-size:40px;
        }

        #expensesChart {
            border-radius: 10px;
            padding: 10px;
            padding-top: 15px;
            padding-bottom: 15px;
            background-color: rgb(220, 220, 220);
            margin-left: 150px;
        }

        #budgetChart {
            border-radius: 10px;
            padding: 10px;
            padding-top: 15px;
            padding-bottom: 15px;
            background-color: rgb(220, 220, 220);
            margin-left: 0px;
        }

        table {
            margin-left:auto;
            margin-right:auto;
            width: 95%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 10px;
            background-color: rgb(127, 60, 254.0.4);
        }

        th,
        td {
            padding: 10px;
            border: 1px solid #dddddd;
            text-align: center;
        }

        /* Adjust the text alignment and style of the headers */
        th {
            color: white;
            background-color: rgb(127, 60, 254, 1);
            font-weight: bold;
            text-transform: uppercase;
        }

        /* Add some padding to the cells */
        td {
            padding: 5px;
        }

        #dlt{
            font-weight:300;
            width:50%;
            color: white;
            background-color: rgb(127, 60, 254);
            padding: 8px 8px;
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
        .charts-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 20px;
        }

       

        header {
            position: fixed;
            left: 0;
            top: 0;
            z-index: 0;
        }

        footer {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            z-index: 0; /* Ensure the footer is behind other elements */
        }

        footer img {
            bottom: 0;
            width: 100%;
        }
        /* username and email */
       
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
      
        @media only screen and (max-width: 1250px) {
            .charts-container {
                flex-direction: column; /* Display charts in a column on small screens */
            }

            #expensesChart,
            #budgetChart {
                max-width: 100%; /* Allow charts to take full width on small screens */
                margin: 20px auto; /* Center align the charts */
            }

            #expensesChart {
                margin-top: 40px; /* Add some space between the charts */
            }

            .container {
                overflow: hidden; /* Hide overflow content */
            }

            @media (min-width: 1250px) {
                .charts-container {
                    flex-wrap: wrap; /* Allow charts to wrap on medium screens */
                }

                #expensesChart,
                #budgetChart {
                    max-width: 48%; /* Limit width to prevent overflow */
                    margin: 20px 1%; /* Add space between charts */
                }

                #expensesChart:nth-child(2n),
                #budgetChart:nth-child(2n) {
                    margin-right: 0; /* Remove right margin for even charts */
                }
            }
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
.income-section {
    position: absolute;
    top: 20px; /* Adjust as needed */
    right: 20px; /* Adjust as needed */
    z-index: 1;
}

.income-section h2 {
    font-size: 24px;
    color: rgba(0, 0, 0, 0.8); /* Adjust the color as needed */
    margin: 0;
}

    </style>
</head>
<header>
    <img src="images/head.svg" alt="">
</header>
<body>
<div class="title">
        <h1 id="h1">QuantaCash</h1>
        <!-- <h1 id="h2">Wise</h1> -->
    </div>
    <div class="info">
        <h2><?php echo $username; ?></h2>
        <h3><?php echo $email; ?></h3>
        
        <button onclick="window.location.href='dashboard.php';" style="margin-top:30px; font-size: 25px; font-weight: 900;">Dashboard</button>
        <button  onclick="window.location.href='budget.php'; ">Budget</button>
        <button  onclick="window.location.href='addexp.php';">Add Expense</button>
        <button  onclick="logout()">Logout</button>
    
    </div>
<div class="container">
    <h1>Welcome to the Dashboard!</h1>
    <div class="income-section">
        <h2>Income: $<?php echo $income; ?></h2>
    </div>
    <div id="date-container">
        <p id="date"></p>
    </div>

    <?php if (!empty($expensesData)) { ?>
        <div class="charts-container">
            <?php if (!isset($noExpensesMessage)) { ?>
                <div>
                    <h2>Remaining Budget</h2>
                    <canvas id="budgetChart" width="400" height="280"></canvas>
                </div>
            <?php } ?>
            <div>
                <h2 style="margin-left:150px; ">Amount Spent</h2>
                <?php if (isset($noExpensesMessage)) {
                    echo "<p style='font-size: 60px; font-weight: 700;'>$noExpensesMessage</p>";
                } else { ?>
                    <canvas id="expensesChart" width="400" height="280"></canvas>
                <?php } ?>
            </div>
        </div>
    <?php } else { ?>
        <div class="no-expenses-message">
            <p style="font-size: 60px; font-weight: 700;">No expenses</p>
        </div>
    <?php } ?>

    <?php if ($resultDetailedExpenses->num_rows > 0) { ?>
        <h3>Detailed Expenses</h3>
        <div class="tab">
            <table>
                <tr>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Description</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
                <?php
                while ($row = $resultDetailedExpenses->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row['TypeName'] . "</td>";
                    echo "<td>" . $row['Amount'] . "</td>";
                    echo "<td>" . $row['Description'] . "</td>";
                    echo "<td>" . $row['ExpenseDate'] . "</td>";
                    // Check if ExpenseID key exists in the $row array before accessing it
                    if (isset($row['ExpenseID'])) {
                        echo "<td><button id='dlt' onclick=\"deleteExpense(" . $row['ExpenseID'] . ")\">Delete</button></td>";
                    } else {
                        echo "<td></td>"; // Output an empty cell if ExpenseID is not set
                    }
                    echo "</tr>";
                }
                ?>
            </table>
        </div>
    <?php } ?>
</div>

<footer>
    <img src="images/wavy_footer.svg" alt="">
</footer>

<script>
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

    function logout() {
        // Perform logout functionality here, such as clearing session variables or redirecting to the logout page
        window.location.href = "logout.php"; // Redirect to logout.php or any other logout endpoint
    }

    <?php if (!isset($noExpensesMessage)) { ?>
    // Create chart for expenses using Chart.js
    var ctxExpenses = document.getElementById('expensesChart').getContext('2d');
    var expensesChart = new Chart(ctxExpenses, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode(array_keys($expensesData)); ?>,
            datasets: [{
                label: 'Expenses',
                data: <?php echo json_encode(array_values($expensesData)); ?>,
                backgroundColor: [
                    '#f94144',
                    '#277da1',
                    '#f9c74f',
                    '#7f055f',
                    '#577590',
                    '#8d0801',
                    '#ff758f'
                ],
                borderColor: '#ffffff',
                borderWidth: 3
            }]
        },
        options: {
            responsive: false,
            maintainAspectRatio: false,
            legend: {
                position: 'right'
            },
            title: {
                display: true,
                text: 'Your Expenses'
            }
        }
    });

    // Create chart for remaining budget using Chart.js
    var ctxBudget = document.getElementById('budgetChart').getContext('2d');
    var budgetData = <?php echo json_encode(array_values($remainingBudgetData)); ?>;
    var labels = <?php echo json_encode(array_keys($remainingBudgetData)); ?>;
    var colors = [
                    'rgba(255, 99, 132)',
                    'rgba(255, 159, 64)',
                    'rgba(255, 205, 86)',
                    'rgba(75, 192, 192)',
                    'rgba(54, 162, 235)',
                    'rgba(153, 102, 255)',
                    'rgba(201, 203, 207)'
    ];
    var backgroundColors = [];
    var borderColor = [];

    budgetData.forEach(function (value, index) {
        if (value < 0) {
            backgroundColors.push('rgba(255, 0, 0)');
            borderColor.push('rgba(255, 0, 0)');
        } else {
            backgroundColors.push(colors[index % colors.length]);
            borderColor.push(colors[index % colors.length]);
        }
    });

    var budgetChart = new Chart(ctxBudget, {
        type: 'bar',
        data: {
            labels: labels.filter((_, i) => budgetData[i] !== 0),
            datasets: [{
                label: 'Remaining Budget',
                data: budgetData.filter(value => value !== 0),
                backgroundColor: backgroundColors.filter((_, i) => budgetData[i] !== 0),
                borderColor: borderColor.filter((_, i) => budgetData[i] !== 0),
                borderWidth: 1
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
            },
            tooltips: {
                enabled: true,
                callbacks: {
                    label: function (tooltipItem, data) {
                        var label = data.labels[tooltipItem.index];
                        var value = data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index];
                        if (value < 0) {
                            label += ': Budget Exceeded';
                        }
                        return label;
                    }
                }
            }
        }
    });
    <?php } ?>

    function deleteExpense(expenseID) {
        if (confirm("Are you sure you want to delete this expense?")) {
            // Perform AJAX request to delete the expense from the database
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "deleteexpense.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    // Reload the page after successful deletion
                    location.reload();
                }
            };
            xhr.send("expenseID=" + expenseID);
        }
    }
</script>

</body>
</html>


