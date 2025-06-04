<?php
session_start(); // Start the session

include 'config/db.php'; // Include database connection

// Function to get a short excerpt from the description
function getExcerpt($description, $length = 100) {
    $excerpt = substr($description, 0, $length);
    $excerpt = substr($excerpt, 0, strrpos($excerpt, ' ')); // Cut off at the last word
    return $excerpt . '...';
}

// Fetch locations from the database
$sql = "SELECT id, location_name, description, photo FROM location";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tour Website (Name Needed)</title>
</head>
<body>

    <h1>Welcome to the Tour Website!</h1>

    <?php if (isset($_SESSION['user_id'])): ?>
        <p>Welcome, <?php echo $_SESSION['name']; ?>!</p>
        <?php if ($_SESSION['is_admin']): ?>
            <a href="manage.php">Manage</a> |
        <?php endif; ?>
        <a href="logout.php">Logout</a> |
    <?php else: ?>
        <a href="login.php">Login</a> |
        <a href="register.php">Register</a> |
    <?php endif; ?>
    <a href="about.html">About</a>

    <hr>

    <?php
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $location_id = $row["id"];
            $location_name = $row["location_name"];
            $location_description = $row["description"];
            $location_photo = $row["photo"]; // Get the photo path
            ?>
            <a href="view.php?id=<?php echo $location_id; ?>">
                <div>
                    <img src="<?php echo htmlspecialchars($location_photo); ?>" alt="<?php echo htmlspecialchars($location_name); ?>" style="max-width: 200px; max-height: 200px;">
                    <h3><?php echo htmlspecialchars($location_name); ?></h3>
                    <p><?php echo htmlspecialchars(getExcerpt($location_description)); ?></p>
                </div>
            </a>
            <?php
        }
    } else {
        echo "No locations found.";
    }
    ?>

</body>
</html>

<?php
$conn->close(); // Close the database connection
?>