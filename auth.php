<!DOCTYPE html>
<html>
<head>
    <title>Login/Register</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
            color: #343a40;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        h1 {
            text-align: center;
            color: #007bff;
            margin-top: 50px;
        }

        .container {
            max-width: 800px;
            margin: auto;
            margin-top: 20px;
        }

        .card {
            background-color: #343a40;
            color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .card-title {
            background-color: #007bff;
            padding: 10px;
            border-radius: 10px 10px 0 0;
        }

        .card-title h2 {
            text-align: center;
        }

        form {
            margin-top: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #fff;
        }

        input {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background-color: #0056b3;
        }

        #message-placeholder {
            color: #fff;
            text-align: center;
            margin-top: 15px;
        }

        #message-placeholder a {
            color: #007bff;
        }

        #message-placeholder a:hover {
            text-decoration: underline;
        }
    </style>
    <script>
        function toggleForm() {
            var loginForm = document.getElementById("login-form");
            var registerForm = document.getElementById("register-form");
            var messagePlaceholder = document.getElementById("message-placeholder");

            if (loginForm.style.display === "none") {
                loginForm.style.display = "block";
                registerForm.style.display = "none";
                messagePlaceholder.innerHTML = "Don't have an account? <a href='#' onclick='toggleForm();'>Sign up</a>.";
            } else {
                loginForm.style.display = "none";
                registerForm.style.display = "block";
                messagePlaceholder.innerHTML = "Already have an account? <a href='#' onclick='toggleForm();'>Log In</a>.";
            }
        }

        function validateLoginForm() {
            var email = document.getElementById("login-email").value;
            var password = document.getElementById("login-password").value;

            if (email.trim() === "" || password.trim() === "") {
                alert("Please fill in all fields.");
                return false;
            }
            return true;
        }
        function validateRegisterForm() {
            var username = document.getElementById("register-username").value;
            var email = document.getElementById("register-email").value;
            var password = document.getElementById("register-password").value;

            if (email.trim() === "" || password.trim() === "" || username.trim() === "") {
                alert("Please fill in all fields.");
                return false;
            }
            return true;
        }
    </script>
</head>
<body>

    <?php 
        
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        require_once 'db.php';
        session_start();

            // Ensure Database Connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        
        // Handle user registration and login logic
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['login'])) {
                $email = $_POST['email'];
                $password = $_POST['password'];
                $query = "SELECT username, password FROM customers WHERE email = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $stmt->bind_result($username, $hashed_password);
                $stmt->fetch();
                $stmt->close();

                if (password_verify($password, $hashed_password)) {
                    session_start();
                    $_SESSION['username'] = $username;
                    $_SESSION['email'] = $email;
                    header("Location: homePage.php");
                    exit();
                } else {
                    // Authentication failed, show an error message
                    echo '<script>document.getElementById("message-placeholder").innerHTML = "Invalid credentials. Please try again.";</script>';
                }
            } elseif (isset($_POST['register'])) {
                $username = $_POST['username'];
                $email = $_POST['email'];
                $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

                // Insert user details into the credentials table
                $stmt = $conn->prepare("INSERT INTO customers (username, email, password) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $username, $email, $password);

                try {
                    $stmt->execute();
                    session_start();
                    $_SESSION['username'] = $username;
                    $_SESSION['email'] = $email;
                    header("Location: homePage.php");
                    exit();
                } catch (Exception $e) {
                    echo '<script>document.getElementById("message-placeholder").innerHTML = "Error: ' . $e->getMessage() . '";</script>';
                    exit();
                } finally {
                    $stmt->close();
                }
            } 
        }
    ?>
    
    <h1>Welcome to your personal finance tracker</h1>
    <div class="container">
        <div class="row">
            <div class="col-lg-6 m-auto">
                <div class="card bg-dark mt-5">
                    <div class="card-title bg-primary text-white mt-5">
                        <h2 class="text-center py-3">Login or Register</h2>
                    </div>
                    <div class="card-body">
                        <form id="login-form" action="" method="POST" onsubmit="return validateLoginForm()">
                            <label for="login-email">Email:</label>
                            <input type="text" name="email" id="login-email" required><br>

                            <label for="login-password">Password:</label>
                            <input type="password" name="password" id="login-password" required><br>

                            <button type="submit" name="login">Login</button>
                        </form>

                        <form id="register-form" style="display: none;" action="" method="POST" onsubmit="return validateRegisterForm()">
                            <label for="register-username">Username:</label>
                            <input type="text" name="username" id="register-username" required><br>

                            <label for="register-email">Email:</label>
                            <input type="text" name="email" id="register-email" required><br>

                            <label for="register-password">Password:</label>
                            <input type="password" name="password" id="register-password" required><br>

                            <button type="submit" name="register">Register</button>
                        </form>

                        <div id='message-placeholder'>
                            Don't have an account? <a href='#' onclick='toggleForm();'>Sign up</a>.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>


