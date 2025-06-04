<?php
session_start();

include 'config/db.php';

// Get the location ID from the query string
if (isset($_GET["id"])) {
    $location_id = escape_string($_GET["id"]);

    // Fetch the location data from the database
    $sql = "SELECT * FROM location WHERE id = '$location_id'";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $location_name = $row["location_name"];
        $location_photo = $row["photo"];
        $location_description = $row["description"];
        $location_link = $row["link"];
    } else {
        // Location not found, redirect to homepage
        header("Location: index.php");
        exit();
    }

    // Handle adding a new comment
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_comment"])) {
        if (isset($_SESSION["user_id"])) {
            $user_id = $_SESSION["user_id"];
            $comment = escape_string($_POST["comment"]);

            $sql = "INSERT INTO comment (location_id, user_id, comment) VALUES ('$location_id', '$user_id', '$comment')";
            if ($conn->query($sql) === TRUE) {
                // Comment added successfully, refresh the page
                header("Location: view.php?id=" . $location_id);
                exit();
            } else {
                $error = "Error: " . $sql . "<br>" . $conn->error;
            }
        } else {
            // User not logged in, display an error
            $error = "You must be logged in to add a comment.";
        }
    }

    // Handle deleting a comment
    if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["delete_comment"])) {
        if (isset($_SESSION["user_id"])) {
            $comment_id = escape_string($_GET["delete_comment"]);
            $sql = "DELETE FROM comment WHERE id = '$comment_id' AND user_id = '" . $_SESSION["user_id"] . "'";
            if ($conn->query($sql) === TRUE) {
                // Comment deleted successfully, refresh the page
                header("Location: view.php?id=" . $location_id);
                exit();
            } else {
                $error = "Error: " . $sql . "<br>" . $conn->error;
            }
        } else {
            // User not logged in, display an error
            $error = "You must be logged in to delete a comment.";
        }
    }

    // Handle editing a comment
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["edit_comment"])) {
        if (isset($_SESSION["user_id"])) {
            $comment_id = escape_string($_POST["comment_id"]);
            $comment = escape_string($_POST["comment"]);

            $sql = "UPDATE comment SET comment = '$comment' WHERE id = '$comment_id' AND user_id = '" . $_SESSION["user_id"] . "'";
            if ($conn->query($sql) === TRUE) {
                // Comment updated successfully, refresh the page
                header("Location: view.php?id=" . $location_id);
                exit();
            } else {
                $error = "Error: " . $sql . "<br>" . $conn->error;
            }
        } else {
            // User not logged in, display an error
            $error = "You must be logged in to edit a comment.";
        }
    }

    // Handle marking location as visited
    if (isset($_SESSION["user_id"])) {
        $user_id = $_SESSION["user_id"];
        if (isset($_GET["toggle_visited"])) {
            $visited = escape_string($_GET["toggle_visited"]);

            // Check if a record already exists for this user and location
            $sql = "SELECT * FROM user_checkpoint WHERE user_id = '$user_id' AND location_id = '$location_id'";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                // Update the existing record
                $sql = "UPDATE user_checkpoint SET visited = '$visited' WHERE user_id = '$user_id' AND location_id = '$location_id'";
            } else {
                // Insert a new record
                $sql = "INSERT INTO user_checkpoint (user_id, location_id, visited) VALUES ('$user_id', '$location_id', '$visited')";
            }

            if ($conn->query($sql) === TRUE) {
                // Visited status updated successfully, refresh the page
                header("Location: view.php?id=" . $location_id);
                exit();
            } else {
                $error = "Error: " . $sql . "<br>" . $conn->error;
            }
        }

        // Fetch the current visited status
        $sql = "SELECT visited FROM user_checkpoint WHERE user_id = '$user_id' AND location_id = '$location_id'";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $visited = $row["visited"];
        } else {
            $visited = 0; // Default to not visited
        }
    }

    // Fetch all comments for this location
    $sql_comments = "SELECT comment.id, comment.comment, user.name, comment.user_id FROM comment INNER JOIN user ON comment.user_id = user.id WHERE location_id = '$location_id'";
    $result_comments = $conn->query($sql_comments);
} else {
    // Location ID not provided, redirect to homepage
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($location_name); ?></title>
</head>
<body>

    <a href="index.php">Back to Homepage</a>

    <h1><?php echo htmlspecialchars($location_name); ?></h1>

    <img src="<?php echo htmlspecialchars($location_photo); ?>" alt="<?php echo htmlspecialchars($location_name); ?>" style="max-width: 400px; max-height: 400px;">

    <p><?php echo htmlspecialchars($location_description); ?></p>

    <p>
        <a href="<?php echo htmlspecialchars($location_link); ?>" target="_blank">Learn More</a>
    </p>

    <hr>

    <?php if (isset($_SESSION["user_id"])): ?>
        <?php if ($visited): ?>
            <a href="view.php?id=<?php echo $location_id; ?>&toggle_visited=0">Mark as Not Visited</a>
        <?php else: ?>
            <a href="view.php?id=<?php echo $location_id; ?>&toggle_visited=1">Mark as Visited</a>
        <?php endif; ?>
    <?php endif; ?>

    <hr>

    <h2>Comments</h2>

    <?php if (isset($error)): ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php endif; ?>

    <?php if (isset($_SESSION["user_id"])): ?>
        <form method="post" action="view.php?id=<?php echo $location_id; ?>">
            <label for="comment">Add a Comment:</label><br>
            <textarea name="comment" rows="4" cols="50" required></textarea><br><br>
            <input type="submit" name="add_comment" value="Add Comment">
        </form>
    <?php else: ?>
        <p>
            <a href="login.php">Login</a> to add a comment.
        </p>
    <?php endif; ?>

    <?php if ($result_comments->num_rows > 0): ?>
        <ul>
            <?php while($row = $result_comments->fetch_assoc()): ?>
                <li>
                    <?php echo htmlspecialchars($row["name"]); ?> says: <?php echo htmlspecialchars($row["comment"]); ?>
                    <?php if (isset($_SESSION["user_id"]) && $_SESSION["user_id"] == $row["user_id"]): ?>
                        <a href="view.php?id=<?php echo $location_id; ?>&delete_comment=<?php echo $row["id"]; ?>">Delete</a>

                        <!-- Edit Comment Form -->
                        <form method="post" action="view.php?id=<?php echo $location_id; ?>">
                            <input type="hidden" name="comment_id" value="<?php echo $row["id"]; ?>">
                            <textarea name="comment" rows="2" cols="30"><?php echo htmlspecialchars($row["comment"]); ?></textarea>
                            <input type="submit" name="edit_comment" value="Update Comment">
                        </form>
                    <?php endif; ?>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p>No comments yet.</p>
    <?php endif; ?>

</body>
</html>

<?php
$conn->close();
?>