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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($location_name); ?> - Wanderlust</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #2d3748;
            background: #f8fafc;
            position: relative;
            overflow-x: hidden;
        }

        /* Sophisticated Background Animation */
        .background-canvas {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        }

        .geometric-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        .shape {
            position: absolute;
            opacity: 0.03;
            animation: float 20s infinite linear;
        }

        .shape:nth-child(1) {
            top: 10%;
            left: 10%;
            width: 100px;
            height: 100px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            border-radius: 50%;
            animation-delay: 0s;
        }

        .shape:nth-child(2) {
            top: 60%;
            right: 15%;
            width: 80px;
            height: 80px;
            background: linear-gradient(45deg, #764ba2, #f093fb);
            transform: rotate(45deg);
            animation-delay: 5s;
        }

        .shape:nth-child(3) {
            bottom: 20%;
            left: 20%;
            width: 120px;
            height: 120px;
            background: linear-gradient(45deg, #667eea, #f093fb);
            clip-path: polygon(50% 0%, 0% 100%, 100% 100%);
            animation-delay: 10s;
        }

        .shape:nth-child(4) {
            top: 30%;
            left: 60%;
            width: 60px;
            height: 60px;
            background: linear-gradient(45deg, #f093fb, #667eea);
            border-radius: 50%;
            animation-delay: 15s;
        }

        @keyframes float {
            0% {
                transform: translateY(0px) rotate(0deg);
                opacity: 0.03;
            }
            50% {
                opacity: 0.08;
            }
            100% {
                transform: translateY(-20px) rotate(360deg);
                opacity: 0.03;
            }
        }

        /* Main Layout */
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            position: relative;
            z-index: 1;
        }

        .back-navigation {
            margin-bottom: 30px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            color: #667eea;
            text-decoration: none;
            padding: 12px 24px;
            background: white;
            border-radius: 50px;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.15);
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            font-weight: 500;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .back-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.1), transparent);
            transition: left 0.5s;
        }

        .back-link:hover::before {
            left: 100%;
        }

        .back-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(102, 126, 234, 0.25);
            border-color: #667eea;
        }

        .back-icon {
            margin-right: 8px;
            font-size: 1.2em;
            transition: transform 0.3s ease;
        }

        .back-link:hover .back-icon {
            transform: translateX(-3px);
        }

        /* Location Header */
        .location-hero {
            background: white;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            margin-bottom: 40px;
            position: relative;
            border: 1px solid #e2e8f0;
        }

        .location-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #667eea, #764ba2, #f093fb, #667eea);
            background-size: 200% 100%;
            animation: gradientShift 3s ease-in-out infinite;
        }

        @keyframes gradientShift {
            0%, 100% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
        }

        .image-container {
            position: relative;
            height: 500px;
            overflow: hidden;
        }

        .location-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s ease;
        }

        .location-hero:hover .location-image {
            transform: scale(1.05);
        }

        .image-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 50%;
            background: linear-gradient(transparent, rgba(0,0,0,0.7));
            display: flex;
            align-items: flex-end;
            padding: 40px;
        }

        .location-title-overlay {
            color: white;
            font-size: 3rem;
            font-weight: 300;
            text-shadow: 0 2px 20px rgba(0,0,0,0.5);
            letter-spacing: -1px;
        }

        .location-content {
            padding: 40px;
        }

        .location-description {
            font-size: 1.2rem;
            line-height: 1.8;
            color: #4a5568;
            margin-bottom: 30px;
            text-align: justify;
        }

        .action-buttons {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            padding: 15px 30px;
            border-radius: 50px;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            font-weight: 500;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
            border: none;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .btn-primary:hover::before {
            left: 100%;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(102, 126, 234, 0.6);
        }

        .btn-icon {
            margin-left: 8px;
            transition: transform 0.3s ease;
        }

        .btn-primary:hover .btn-icon {
            transform: translateX(3px);
        }

        /* Visited Status */
        .visited-section {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            margin-bottom: 40px;
            text-align: center;
            border: 1px solid #e2e8f0;
            position: relative;
        }

        .visited-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #48bb78, #38a169);
            border-radius: 20px 20px 0 0;
        }

        .visited-btn {
            display: inline-flex;
            align-items: center;
            padding: 15px 30px;
            border-radius: 50px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }

        .visited-btn.visited {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
        }

        .visited-btn.not-visited {
            background: linear-gradient(135deg, #a0aec0 0%, #718096 100%);
            color: white;
        }

        .visited-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.2);
        }

        .visited-icon {
            margin-right: 8px;
            font-size: 1.1em;
        }

        /* Comments Section */
        .comments-section {
            background: white;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            border: 1px solid #e2e8f0;
            overflow: hidden;
            position: relative;
        }

        .comments-header {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            padding: 30px 40px;
            border-bottom: 1px solid #e2e8f0;
            position: relative;
        }

        .comments-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2, #f093fb);
        }

        .comments-title {
            font-size: 2.2rem;
            font-weight: 300;
            color: #2d3748;
            text-align: center;
            position: relative;
        }

        .comments-count {
            position: absolute;
            top: -10px;
            right: -10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .comments-content {
            padding: 40px;
        }

        /* Error Messages */
        .error-message {
            background: linear-gradient(135deg, #fed7d7 0%, #feb2b2 100%);
            color: #c53030;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            border-left: 5px solid #e53e3e;
            box-shadow: 0 4px 20px rgba(229, 62, 62, 0.2);
            position: relative;
        }

        .error-message::before {
            content: '⚠';
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.2em;
        }

        .error-message {
            padding-left: 50px;
        }

        /* Comment Form */
        .comment-form {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 40px;
            border: 2px dashed #cbd5e0;
            position: relative;
            transition: all 0.3s ease;
        }

        .comment-form:hover {
            border-color: #667eea;
            background: linear-gradient(135deg, #edf2f7 0%, #e2e8f0 100%);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 10px;
            color: #4a5568;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .form-textarea {
            width: 100%;
            padding: 20px;
            border: 2px solid #e2e8f0;
            border-radius: 15px;
            font-size: 16px;
            font-family: inherit;
            resize: vertical;
            min-height: 120px;
            transition: all 0.3s ease;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .form-textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1), 0 4px 20px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .form-textarea::placeholder {
            color: #a0aec0;
            font-style: italic;
        }

        /* Login Prompt */
        .login-prompt {
            text-align: center;
            padding: 40px;
            background: linear-gradient(135deg, #edf2f7 0%, #e2e8f0 100%);
            border-radius: 20px;
            margin-bottom: 30px;
            border: 2px dashed #cbd5e0;
            position: relative;
        }

        .login-prompt::before {
            content: '🔐';
            display: block;
            font-size: 3rem;
            margin-bottom: 15px;
        }

        .login-prompt a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .login-prompt a:hover {
            background: rgba(102, 126, 234, 0.1);
            text-decoration: underline;
        }

        /* Comments List */
        .comments-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .comment-item {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            padding: 30px;
            border-radius: 20px;
            border-left: 5px solid #667eea;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .comment-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(180deg, #667eea, #764ba2, #f093fb);
            transition: width 0.3s ease;
        }

        .comment-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.1);
        }

        .comment-item:hover::before {
            width: 8px;
        }

        .comment-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            gap: 15px;
        }

        .comment-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.2rem;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .comment-author {
            font-weight: 600;
            color: #2d3748;
            font-size: 1.1rem;
        }

        .comment-text {
            color: #4a5568;
            line-height: 1.7;
            font-size: 1.05rem;
            margin-bottom: 20px;
            text-align: justify;
        }

        .comment-actions {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .btn-small {
            padding: 8px 16px;
            font-size: 14px;
            border-radius: 25px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-edit {
            background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(237, 137, 54, 0.4);
        }

        .btn-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(237, 137, 54, 0.6);
        }

        .btn-delete {
            background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(229, 62, 62, 0.4);
        }

        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(229, 62, 62, 0.6);
        }

        .btn-cancel {
            background: linear-gradient(135deg, #a0aec0 0%, #718096 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(160, 174, 192, 0.4);
        }

        .btn-cancel:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(160, 174, 192, 0.6);
        }

        /* Edit Form */
        .edit-form {
            margin-top: 25px;
            padding: 25px;
            background: white;
            border-radius: 15px;
            border: 2px solid #e2e8f0;
            display: none;
            opacity: 0;
            transform: translateY(-10px);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .edit-form.show {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }

        .edit-form textarea {
            min-height: 100px;
            margin-bottom: 20px;
            border-color: #cbd5e0;
        }

        .edit-form-actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        /* No Comments State */
        .no-comments {
            text-align: center;
            padding: 60px 20px;
            color: #718096;
            font-size: 1.2rem;
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            border-radius: 20px;
            border: 2px dashed #cbd5e0;
            position: relative;
        }

        .no-comments::before {
            content: '💬';
            display: block;
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .location-title-overlay {
                font-size: 2.2rem;
            }

            .location-content {
                padding: 25px;
            }

            .image-container {
                height: 300px;
            }

            .comments-content {
                padding: 25px;
            }

            .comment-item {
                padding: 20px;
            }

            .comment-actions {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .edit-form-actions {
                flex-direction: column;
            }

            .action-buttons {
                flex-direction: column;
            }

            .comments-header {
                padding: 20px 25px;
            }

            .comment-form {
                padding: 20px;
            }
        }

        @media (max-width: 480px) {
            .location-title-overlay {
                font-size: 1.8rem;
            }

            .comments-title {
                font-size: 1.8rem;
            }

            .back-link {
                padding: 10px 20px;
            }

            .btn-primary {
                padding: 12px 24px;
            }

            .image-overlay {
                padding: 20px;
            }

            .location-content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="background-canvas">
        <div class="geometric-shapes">
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
        </div>
    </div>

    <div class="container">
        <nav class="back-navigation">
            <a href="index.php" class="back-link">
                <span class="back-icon">←</span>
                Back to Destinations
            </a>
        </nav>

        <div class="location-hero">
            <div class="image-container">
                <img src="<?php echo htmlspecialchars($location_photo); ?>" 
                     alt="<?php echo htmlspecialchars($location_name); ?>" 
                     class="location-image">
                <div class="image-overlay">
                    <h1 class="location-title-overlay"><?php echo htmlspecialchars($location_name); ?></h1>
                </div>
            </div>
            <div class="location-content">
                <p class="location-description"><?php echo htmlspecialchars($location_description); ?></p>
                <div class="action-buttons">
                    <a href="<?php echo htmlspecialchars($location_link); ?>" 
                       target="_blank" 
                       class="btn-primary">
                        🗺️ Google Maps
                        <span class="btn-icon">→</span>
                    </a>
                </div>
            </div>
        </div>

        <?php if (isset($_SESSION["user_id"])): ?>
            <div class="visited-section">
                <?php if ($visited): ?>
                    <a href="view.php?id=<?php echo $location_id; ?>&toggle_visited=0" 
                       class="visited-btn visited">
                        <span class="visited-icon">✓</span>
                        Visited - Mark as Not Visited
                    </a>
                <?php else: ?>
                    <a href="view.php?id=<?php echo $location_id; ?>&toggle_visited=1" 
                       class="visited-btn not-visited">
                        <span class="visited-icon">○</span>
                        Mark as Visited
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="comments-section">
            <div class="comments-header">
                <h2 class="comments-title">
                    Community Insights
                    <?php if ($result_comments->num_rows > 0): ?>
                        <span class="comments-count"><?php echo $result_comments->num_rows; ?></span>
                    <?php endif; ?>
                </h2>
            </div>
            
            <div class="comments-content">
                <?php if (isset($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if (isset($_SESSION["user_id"])): ?>
                    <form method="post" action="view.php?id=<?php echo $location_id; ?>" class="comment-form">
                        <div class="form-group">
                            <label for="comment" class="form-label">Share Your Experience</label>
                            <textarea name="comment" 
                                      id="comment" 
                                      class="form-textarea"
                                      required 
                                      placeholder="Tell others about your experience at this amazing destination..."></textarea>
                        </div>
                        <button type="submit" name="add_comment" class="btn-primary">
                            Share Experience
                            <span class="btn-icon">→</span>
                        </button>
                    </form>
                <?php else: ?>
                    <div class="login-prompt">
                        <p><a href="login.php">Login</a> to share your experience and connect with fellow travelers.</p>
                    </div>
                <?php endif; ?>

                <?php if ($result_comments->num_rows > 0): ?>
                    <ul class="comments-list">
                        <?php while($row = $result_comments->fetch_assoc()): ?>
                            <li class="comment-item">
                                <div class="comment-header">
                                    <div class="comment-avatar">
                                        <?php echo strtoupper(substr($row["name"], 0, 1)); ?>
                                    </div>
                                    <div class="comment-author"><?php echo htmlspecialchars($row["name"]); ?></div>
                                </div>
                                
                                <div class="comment-text"><?php echo htmlspecialchars($row["comment"]); ?></div>
                                
                                <?php if (isset($_SESSION["user_id"]) && $_SESSION["user_id"] == $row["user_id"]): ?>
                                    <div class="comment-actions">
                                        <button type="button" 
                                                class="btn-small btn-edit" 
                                                onclick="toggleEditForm(<?php echo $row['id']; ?>)">
                                            Edit
                                        </button>
                                        <a href="view.php?id=<?php echo $location_id; ?>&delete_comment=<?php echo $row["id"]; ?>" 
                                           class="btn-small btn-delete"
                                           onclick="return confirm('Are you sure you want to delete this comment?')">
                                            Delete
                                        </a>
                                    </div>

                                    <div class="edit-form" id="edit-form-<?php echo $row['id']; ?>">
                                        <form method="post" action="view.php?id=<?php echo $location_id; ?>">
                                            <input type="hidden" name="comment_id" value="<?php echo $row["id"]; ?>">
                                            <textarea name="comment" 
                                                      class="form-textarea"
                                                      required><?php echo htmlspecialchars($row["comment"]); ?></textarea>
                                            <div class="edit-form-actions">
                                                <button type="submit" name="edit_comment" class="btn-small btn-primary">
                                                    Update Comment
                                                </button>
                                                <button type="button" 
                                                        class="btn-small btn-cancel" 
                                                        onclick="toggleEditForm(<?php echo $row['id']; ?>)">
                                                    Cancel
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <div class="no-comments">
                        <p>No experiences shared yet. Be the first to tell others about this amazing destination!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function toggleEditForm(commentId) {
            const editForm = document.getElementById('edit-form-' + commentId);
            
            if (editForm.classList.contains('show')) {
                editForm.classList.remove('show');
                setTimeout(() => {
                    editForm.style.display = 'none';
                }, 400);
            } else {
                editForm.style.display = 'block';
                setTimeout(() => {
                    editForm.classList.add('show');
                }, 10);
            }
        }

        // Add smooth scroll behavior for better UX
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Add loading state to forms
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.style.opacity = '0.7';
                    submitBtn.style.pointerEvents = 'none';
                    submitBtn.innerHTML = 'Processing...';
                }
            });
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>
