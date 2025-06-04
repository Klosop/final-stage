<?php
session_start();

include 'config/db.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION["user_id"]) || !$_SESSION["is_admin"]) {
    header("Location: index.php"); // Redirect to homepage if not admin
    exit();
}

$error = '';
$success = '';

// Handle adding a new location
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_location"])) {
    $location_name = escape_string($_POST["location_name"]);
    $description = escape_string($_POST["description"]);
    $link = escape_string($_POST["link"]);

    // Handle image upload
    $target_dir = "images/";
    $target_file = $target_dir . basename($_FILES["photo"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

    // Check if image file is a actual image or fake image
    $check = @getimagesize($_FILES["photo"]["tmp_name"]); // Use @ to suppress warnings
    if($check === false) {
        $error = "File is not an image.";
        $uploadOk = 0;
    }

    // Check if file already exists
    if (file_exists($target_file)) {
        $error = "Sorry, file already exists.";
        $uploadOk = 0;
    }

    // Check file size
    if ($_FILES["photo"]["size"] > 500000) {
        $error = "Sorry, your file is too large.";
        $uploadOk = 0;
    }

    // Allow certain file formats
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
    && $imageFileType != "gif" ) {
        $error = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        $uploadOk = 0;
    }

    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        $error = "Sorry, your file was not uploaded.";
    // if everything is ok, try to upload file
    } else {
        if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
            $sql = "INSERT INTO location (location_name, photo, description, link) VALUES ('$location_name', '$target_file', '$description', '$link')";
            if ($conn->query($sql) === TRUE) {
                $success = "Location added successfully.";
            } else {
                $error = "Error: " . $sql . "<br>" . $conn->error;
            }
        } else {
            $error = "Sorry, there was an error uploading your file.";
        }
    }
}

// Handle deleting a location
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["delete_location"])) {
    $location_id = escape_string($_GET["delete_location"]);
    $sql = "DELETE FROM location WHERE id = '$location_id'";
    if ($conn->query($sql) === TRUE) {
        $success = "Location deleted successfully.";
    } else {
        $error = "Error: " . $sql . "<br>" . $conn->error;
    }
}

// Handle deleting a comment
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["delete_comment"])) {
    $comment_id = escape_string($_GET["delete_comment"]);
    $sql = "DELETE FROM comment WHERE id = '$comment_id'";
    if ($conn->query($sql) === TRUE) {
        $success = "Comment deleted successfully.";
    } else {
        $error = "Error: " . $sql . "<br>" . $conn->error;
    }
}

// Fetch all locations for display
$sql_locations = "SELECT id, location_name FROM location";
$result_locations = $conn->query($sql_locations);

// Fetch all comments for display
$sql_comments = "SELECT id, comment FROM comment";
$result_comments = $conn->query($sql_comments);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel</title>
</head>
<body>

    <h1>Admin Panel</h1>

    <a href="index.php">Back to Homepage</a>

    <?php if ($error): ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
        <p style="color: green;"><?php echo $success; ?></p>
    <?php endif; ?>

    <h2>Add New Location</h2>
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
        <label for="location_name">Location Name:</label><br>
        <input type="text" name="location_name" required><br><br>

        <label for="photo">Photo:</label><br>
        <input type="file" name="photo" id="photo" required><br><br>

        <label for="description">Description:</label><br>
        <textarea name="description" rows="4" cols="50" required></textarea><br><br>

        <label for="link">Link:</label><br>
        <input type="text" name="link"><br><br>

        <input type="submit" name="add_location" value="Add Location">
    </form>

    <h2>Manage Locations</h2>
    <?php if ($result_locations->num_rows > 0): ?>
        <ul>
            <?php while($row = $result_locations->fetch_assoc()): ?>
                <li>
                    <?php echo htmlspecialchars($row["location_name"]); ?>
                    <a href="manage.php?delete_location=<?php echo $row["id"]; ?>">Delete</a>
                    <!-- Add Edit Link Here Later -->
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p>No locations found.</p>
    <?php endif; ?>

    <h2>Manage Comments</h2>
    <?php if ($result_comments->num_rows > 0): ?>
        <ul>
            <?php while($row = $result_comments->fetch_assoc()): ?>
                <li>
                    <?php echo htmlspecialchars($row["comment"]); ?>
                    <a href="manage.php?delete_comment=<?php echo $row["id"]; ?>">Delete</a>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p>No comments found.</p>
    <?php endif; ?>

</body>
</html>

<?php
$conn->close();
?>