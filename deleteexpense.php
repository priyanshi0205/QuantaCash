<?php
session_start();

if (!isset($_SESSION['UserID'])) {
    // Redirect to login page if user is not logged in
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['expenseID'])) {
    // Get the expense ID from the POST data
    $expenseID = $_POST['expenseID'];

    // Database connection
    $servername = "localhost";
    $username = "root";
    $password = ""; // Add your password here if any
    $dbname = "exp";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Construct the SQL query to delete the expense
    $sql = "DELETE FROM Expenses WHERE ExpenseID = $expenseID";

    if ($conn->query($sql) === TRUE) {
        // Expense deleted successfully
        echo "Expense deleted successfully";
    } else {
        // Error deleting expense
        echo "Error deleting expense: " . $conn->error;
    }

    $conn->close();
} else {
    // Invalid request
    echo "Invalid request";
}
?>
