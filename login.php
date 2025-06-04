<?php
session_start();

include 'config/db.php';

$error = ''; // Variable to store error messages

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $email = escape_string($_POST["email"]);
    $password = $_POST["password"];

    // Basic validation
    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        // Retrieve user from the database based on email
        $sql = "SELECT id, name, password, is_admin FROM user WHERE email = '$email'";
        $result = $conn->query($sql);

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            // Verify the password
            if (password_verify($password, $row["password"])) {
                // Password is correct, set session variables
                $_SESSION["user_id"] = $row["id"];  // Fixed: Added $ before _SESSION
                $_SESSION["name"] = $row["name"];
                $_SESSION["is_admin"] = $row["is_admin"];

                // Redirect to the homepage
                header("Location: index.php");
                exit();
            } else {
                $error = "Incorrect password.";  // Fixed: Added missing $ before error and proper braces
            }
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>

    <h1>Login</h1>

    <?php if ($error): ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php endif; ?>

    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <label for="email">Email:</label><br>
        <input type="email" id="email" name="email"><br><br>

        <label for="password">Password:</label><br>
        <input type="password" id="password" name="password"><br><br>

        <input type="submit" value="Login">
    </form>

    <p>Don't have an account? <a href="register.php">Register here</a></p>

</body>
</html>

<?php
$conn->close();
?>