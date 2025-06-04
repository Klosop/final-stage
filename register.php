<?php
session_start();

include 'config/db.php';

$error = ''; // Variable to store error messages

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = escape_string($_POST["name"]);
    $email = escape_string($_POST["email"]);
    $password = $_POST["password"]; // Don't escape the password yet!

    // Basic validation
    if (empty($name) || empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Check if the email already exists
        $sql = "SELECT id FROM user WHERE email = '$email'";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $error = "Email already registered.";
        } else {
            // Insert the new user into the database
            $sql = "INSERT INTO user (name, email, password) VALUES ('$name', '$email', '$hashed_password')";

            if ($conn->query($sql) === TRUE) {
                // Registration successful, redirect to login page
                header("Location: login.php");
                exit();
            } else {
                $error = "Error: " . $sql . "<br>" . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
</head>
<body>

    <h1>Register</h1>

    <?php if ($error): ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php endif; ?>

    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <label for="name">Name:</label><br>
        <input type="text" id="name" name="name"><br><br>

        <label for="email">Email:</label><br>
        <input type="email" id="email" name="email"><br><br>

        <label for="password">Password:</label><br>
        <input type="password" id="password" name="password"><br><br>

        <input type="submit" value="Register">
    </form>

    <p>Already have an account? <a href="login.php">Login here</a></p>

</body>
</html>

<?php
$conn->close();
?>