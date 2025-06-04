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
        $error .= "File is not an image.<br>";
        $uploadOk = 0;
    }

    // Check if file already exists
    if (file_exists($target_file)) {
        $error .= "Sorry, file already exists.<br>";
        $uploadOk = 0;
    }

    // Check file size
    if ($_FILES["photo"]["size"] > 10485760) {
        $error .= "Sorry, your file is too large.<br>";
        $uploadOk = 0;
    }

    // Allow certain file formats
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
    && $imageFileType != "gif" ) {
        $error .= "Sorry, only JPG, JPEG, PNG & GIF files are allowed.<br>";
        $uploadOk = 0;
    }

    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        $error .= "Sorry, your file was not uploaded.<br>";
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
$sql_comments = "SELECT comment.id, comment.comment, user.name, location.location_name
                FROM comment
                INNER JOIN user ON comment.user_id = user.id
                INNER JOIN location ON comment.location_id = location.id
                ORDER BY location.location_name";
$result_comments = $conn->query($sql_comments);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Tour Website</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 300;
            margin-bottom: 10px;
        }

        .back-link {
            display: inline-block;
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border: 2px solid white;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            background-color: white;
            color: #667eea;
        }

        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .error-message {
            background: #fee;
            color: #c33;
            border-left: 4px solid #c33;
        }

        .success-message {
            background: #efe;
            color: #363;
            border-left: 4px solid #363;
        }

        .section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 1.8rem;
            margin-bottom: 20px;
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }

        .form-grid {
            display: grid;
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: 500;
        }

        input[type="text"],
        input[type="file"],
        textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e1e1;
            border-radius: 5px;
            font-size: 16px;
            font-family: inherit;
            transition: border-color 0.3s ease;
        }

        input[type="text"]:focus,
        textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: transform 0.2s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .items-list {
            list-style: none;
        }

        .item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 4px solid #667eea;
        }

        .item-name {
            font-weight: 500;
            color: #333;
        }

        .delete-link {
            color: #dc3545;
            text-decoration: none;
            padding: 6px 12px;
            border: 1px solid #dc3545;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .delete-link:hover {
            background-color: #dc3545;
            color: white;
        }

        .no-items {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 20px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .header {
                padding: 20px;
            }

            .header h1 {
                font-size: 2rem;
            }

            .section {
                padding: 20px;
            }

            .item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .delete-link {
                align-self: flex-end;
            }
        }

        @media (max-width: 480px) {
            .header h1 {
                font-size: 1.8rem;
            }

            .section-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Manage Panel</h1>
            <a href="index.php" class="back-link">← Back to Homepage</a>
        </div>

        <?php if ($error): ?>
            <div class="message error-message"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="message success-message"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="section">
            <h2 class="section-title">Add New Location</h2>
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="location_name">Location Name:</label>
                        <input type="text" name="location_name" id="location_name" required>
                    </div>

                    <div class="form-group">
                        <label for="photo">Photo:</label>
                        <input type="file" name="photo" id="photo" required accept="image/*">
                    </div>

                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea name="description" id="description" required placeholder="Describe this amazing location..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="link">Link:</label>
                        <input type="text" name="link" id="link" placeholder="https://example.com">
                    </div>

                    <input type="submit" name="add_location" value="Add Location" class="btn">
                </div>
            </form>
        </div>

        <div class="section">
            <h2 class="section-title">Manage Locations</h2>
            <?php if ($result_locations->num_rows > 0): ?>
                <ul class="items-list">
                    <?php while($row = $result_locations->fetch_assoc()): ?>
                        <li class="item">
                            <span class="item-name"><?php echo htmlspecialchars($row["location_name"]); ?></span>
                            <a href="manage.php?delete_location=<?php echo $row["id"]; ?>" 
                               class="delete-link"
                               onclick="return confirm('Are you sure you want to delete this location?')">Delete</a>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <div class="no-items">
                    <p>No locations found.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2 class="section-title">Manage Comments</h2>
            <?php if ($result_comments->num_rows > 0): ?>
                <ul class="items-list">
                    <?php while($row = $result_comments->fetch_assoc()): ?>
                        <li class="item">
                            <strong><?php echo htmlspecialchars($row["location_name"]); ?></strong> 
                            <?php echo htmlspecialchars($row["name"]); ?> says: 
                            <?php echo htmlspecialchars($row["comment"]); ?>
                            <a href="manage.php?delete_comment=<?php echo $row["id"]; ?>" 
                               class="delete-link"
                               onclick="return confirm('Are you sure you want to delete this comment?')">Delete</a>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <div class="no-items">
                    <p>No comments found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?>
