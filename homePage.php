<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="css/bootstrap.css">
    <title>Your Web Application</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


    <style>
        body {
            background-color: #f8f9fa;
            color: #343a40;
            font-family: 'Arial', sans-serif;
        }

        .container {
            max-width: 800px;
            margin: auto;
        }

        h1 {
            color: #007bff;
        }

        h2 {
            color: #007bff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #dee2e6;
            padding: 12px;
            text-align: left;
        }

        th {
            background-color: #007bff;
            color: #fff;
        }

        form {
            margin-top: 20px;
        }

        .btn-danger,
        .btn-primary {
            padding: 12px 20px;
            font-size: 16px;
        }

        .btn-danger {
            background-color: #dc3545;
            border: none;
            color: #fff;
            transition: background-color 0.3s;
        }

        .btn-danger:hover {
            background-color: #bd2130;
        }

        .btn-primary {
            background-color: #007bff;
            border: none;
            color: #fff;
            transition: background-color 0.3s;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .form-control {
            width: 100%;
            margin-bottom: 10px;
        }
        .spending-summary {
            margin-top: 40px;
        }

        canvas {
            max-width: 400px;
            margin: 20px 0;
        }
    </style>
    <script>
    var ctx = document.getElementById('pieChart').getContext('2d');
    var data = <?php echo json_encode($spendingSummary); ?>;
    var labels = Object.keys(data);
    var amounts = labels.map(function (label) {
        return data[label].totalAmount;
    });

    var pieChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: amounts,
                backgroundColor: ['#007bff', '#28a745', '#dc3545', '#ffc107', '#17a2b8', '#6610f2'],
            }]
        },
        options: {
            responsive: true,
            legend: {
                position: 'right',
            }
        }
    });
</script>
</head>
<body class="container mt-4">

    <?php
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        session_start();
        require_once 'db.php';

        // Logout
        if (isset($_POST['logout'])) {
            session_destroy();
            header("Location: auth.php");
            exit();
        }

        function getCustomerId($email) {
            global $conn;
            $stmt = $conn->prepare("SELECT id FROM customers WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->bind_result($customerId);
            $stmt->fetch();
            $stmt->close();
            return $customerId;
        }

        function addTransaction($conn, $customerId, $transactionName, $category, $transactionDate, $amount) {
            $stmt = $conn->prepare("INSERT INTO transactions (customer_id, transaction_name, category, transaction_date, amount) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isssd", $customerId, $transactionName, $category, $transactionDate, $amount);

            try {
                $stmt->execute();
        
                if ($stmt->affected_rows == 1) {
                    printf("%d Row inserted.\n", $stmt->affected_rows);
                } else {
                    echo "<p>Error executing " . $stmt . "<p><br>" . $conn->error;
                }
        
            } catch (Exception $e) {
                echo "<p>Error: " . $e->getMessage() . "<p>";
                return false;
            } finally {
                if (isset($stmt)) {
                    $stmt->close();
                }
            }
        }
        // Function to get spending summary
        function getSpendingSummary($customerId) {
            global $conn;
            $query = "SELECT category, SUM(amount) AS total_amount FROM transactions WHERE customer_id = ? GROUP BY category";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $customerId);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();

            if (!$result) die($conn->error);

            $spendingSummary = [];
            while ($row = $result->fetch_assoc()) {
                $spendingSummary[] = $row;
            }

            return $spendingSummary;
        }

        // Function to get total spending amount
        function getTotalSpending($customerId) {
            global $conn;
            $query = "SELECT SUM(amount) AS total_spending FROM transactions WHERE customer_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $customerId);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();

            if (!$result) die($conn->error);

            $row = $result->fetch_assoc();
            return $row['total_spending'];
        }

        if (isset($_SESSION['username'])){
            $email = $_SESSION['email'];
            $customerId = getCustomerId($email);
    
            // Update database when the form is submitted
            if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    
                $transactionName = $_POST["transaction_name"];
                $category = $_POST["category"];
                $transactionDate = $_POST["transaction_date"];
                $amount = $_POST["amount"];
    
                addTransaction($conn, $customerId, $transactionName, $category, $transactionDate, $amount);
                // Redirect after form submission to prevent resubmission on refresh
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            
            }
    
            // Display the results in a table
            $query = "SELECT transaction_name, category, transaction_date, amount FROM transactions WHERE customer_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $customerId);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();

            // Get spending summary
            $spendingSummary = getSpendingSummary($customerId);

            // Get total spending amount
            $totalSpending = getTotalSpending($customerId);
    
            if (!$result) die($conn->error);
    
            // Get the number of rows in the result
            $rows = $result->num_rows;
    ?>

    <h1 class="mb-3">Welcome, <?php echo $_SESSION['username']; ?></h1>
    
    <form action="" method="POST">
        <button type="submit" name="logout" class="btn btn-danger">Logout</button>
    </form>

    <!-- Form for recording a transaction -->
    <div class="mt-4">
        <h2>Add Transaction</h2>
        <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
            <div class="form-group">
                <label for="transaction_name">Transaction Name:</label>
                <input type="text" name="transaction_name" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="category">Category:</label>
                <select name="category" class="form-control" required>
                    <option value="Travel">Travel</option>
                    <option value="Education">Education</option>
                    <option value="Entertainment">Entertainment</option>
                    <option value="Utilities">Utilities</option>
                    <option value="Shopping">Shopping</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div class="form-group">
                <label for="transaction_date">Transaction Date:</label>
                <input type="date" name="transaction_date" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="amount">Amount:</label>
                <input type="number" name="amount" step="0.01" class="form-control" required>
            </div>

            <button type="submit" name="submit" class="btn btn-primary">Submit</button>
        </form>
    </div>


    <?php
    
            echo "<div class='mb-4'>";
            echo "<h3>Your Transactions:</h3>";
            echo "<table class='table'><thead class='thead-dark'><tr><th>Name(description)</th><th>Category</th><th>Date</th><th>Amount</th></tr></thead><tbody>";
            for ($j = 0; $j < $rows; ++$j) {
                $result->data_seek($j);
                $row = $result->fetch_array(MYSQLI_NUM);
                echo "<tr>";
                for ($k = 0; $k < 4; ++$k) echo "<td>$row[$k]</td>";
                echo "</tr>";
            }
            echo "</tbody></table>";
            echo "</div>";
    
            // Close the connection
            $result->close();
            $conn->close();
        } else {
            header("Location: auth.php");
            exit();
        }
    ?>

    <div class="spending-summary">
        <h2>Your Spending Summary</h2>
        <table class="table">
            <thead class="thead-dark">
                <tr>
                    <th>Category</th>
                    <th>Total Amount</th>
                    <th>Percentage of Total Spending</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($spendingSummary as $row) {
                    $percentage = number_format(($row['total_amount'] / $totalSpending) * 100, 2);
                    echo "<tr>";
                    echo "<td>{$row['category']}</td>";
                    echo "<td>{$row['total_amount']}</td>";
                    echo "<td>{$percentage}%</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>

        <h3>Percentage of Total Spending by Category</h3>
        <canvas id="pieChart"></canvas>
        <script>
            // Your JavaScript code for generating the pie chart goes here
            var ctx = document.getElementById('pieChart').getContext('2d');
            var data = <?php echo json_encode($spendingSummary); ?>;
            var labels = data.map(function(item) {
                return item.category;
            });
            var amounts = data.map(function(item) {
                return item.total_amount;
            });

            var pieChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: amounts,
                        backgroundColor: ['#007bff', '#28a745', '#dc3545', '#ffc107', '#17a2b8', '#6610f2'],
                    }]
                },
                options: {
                    responsive: true,
                    legend: {
                        position: 'right',
                        labels: {
                            fontSize: 14,
                        }
                    }
                }
            });
        </script>

    </div>

</body>
</html>
