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

// Retrieve user ID
$userID = $_SESSION['UserID'];

// Process the submitted budget amounts
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Loop through the submitted budget data and update the database accordingly
    foreach ($_POST['budget'] as $expenseType => $budgetAmount) {
        // Escape user inputs to prevent SQL injection
        $expenseType = $conn->real_escape_string($expenseType);
        $budgetAmount = $conn->real_escape_string($budgetAmount);

        // Check if the user already has a budget entry for this expense type
        $checkBudgetExistsQuery = "SELECT * FROM Budgets WHERE UserID = $userID AND ExpenseTypeID = (SELECT ExpenseTypeID FROM ExpenseTypes WHERE TypeName = '$expenseType')";
        $checkBudgetExistsResult = $conn->query($checkBudgetExistsQuery);

        if ($checkBudgetExistsResult->num_rows > 0) {
            // If the budget entry exists, update it
            $updateBudgetQuery = "UPDATE Budgets SET BudgetAmount = $budgetAmount WHERE UserID = $userID AND ExpenseTypeID = (SELECT ExpenseTypeID FROM ExpenseTypes WHERE TypeName = '$expenseType')";
            $conn->query($updateBudgetQuery);
        } else {
            // If the budget entry does not exist, insert it
            $insertBudgetQuery = "INSERT INTO Budgets (UserID, ExpenseTypeID, BudgetAmount) VALUES ($userID, (SELECT ExpenseTypeID FROM ExpenseTypes WHERE TypeName = '$expenseType'), $budgetAmount)";
            $conn->query($insertBudgetQuery);
        }
    }
}

// Close database connection
$conn->close();

// Redirect back to the budget page
header("Location: budget.php");
exit();
?>
