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

// Check if the user is logged in
if (isset($_SESSION["user_id"])) {
    $user_id = $_SESSION["user_id"];

    // Get the total number of locations visited by the user
    $sql_visited = "SELECT COUNT(DISTINCT location_id) AS total_visited FROM user_checkpoint WHERE user_id = '$user_id' AND visited = 1";
    $result_visited = $conn->query($sql_visited);
    $row_visited = $result_visited->fetch_assoc();
    $total_visited = $row_visited["total_visited"];

    // Get the total number of locations
    $sql_total_locations = "SELECT COUNT(*) AS total_locations FROM location";
    $result_total_locations = $conn->query($sql_total_locations);
    $row_total_locations = $result_total_locations->fetch_assoc();
    $total_locations = $row_total_locations["total_locations"];

    // Calculate the number of locations remaining to visit
    $locations_remaining = $total_locations - $total_visited;
} else {
    // User is not logged in, set default values
    $total_visited = 0;
    $total_locations = 0;
    $locations_remaining = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eXplore Sulut - Discover Amazing Places</title>
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
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 0;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 20% 80%, rgba(255,255,255,0.1) 0%, transparent 50%),
                        radial-gradient(circle at 80% 20%, rgba(255,255,255,0.1) 0%, transparent 50%);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            position: relative;
            z-index: 1;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .header-left h1 {
            font-size: 3.5rem;
            font-weight: 300;
            margin-bottom: 15px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
            letter-spacing: -1px;
        }

        .header-subtitle {
            font-size: 1.3rem;
            opacity: 0.9;
            font-weight: 300;
            letter-spacing: 0.5px;
        }

        .nav-links {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 30px;
            transition: all 0.3s ease;
            font-weight: 500;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
            position: relative;
            overflow: hidden;
        }

        .nav-links a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .nav-links a:hover::before {
            left: 100%;
        }

        .nav-links a:hover {
            background: rgba(255,255,255,0.25);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .welcome-message {
            background: rgba(255,255,255,0.15);
            padding: 15px 25px;
            border-radius: 30px;
            margin: 15px 0;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
            font-weight: 500;
            animation: fadeInUp 0.6s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .main-content {
            padding: 60px 0;
        }

        .hero-section {
            text-align: center;
            margin-bottom: 60px;
        }

        .hero-title {
            font-size: 2.8rem;
            font-weight: 300;
            margin-bottom: 20px;
            color: #2d3748;
            position: relative;
            display: inline-block;
        }

        .hero-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            border-radius: 2px;
        }

        .hero-description {
            font-size: 1.2rem;
            color: #4a5568;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.7;
        }

        .locations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .location-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            text-decoration: none;
            color: inherit;
            position: relative;
            border: 1px solid #e2e8f0;
        }

        .location-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2, #f093fb);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .location-card:hover::before {
            transform: scaleX(1);
        }

        .location-card:hover {
            transform: translateY(-15px) scale(1.02);
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
        }

        .location-image-container {
            position: relative;
            overflow: hidden;
            height: 250px;
        }

        .location-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }

        .location-card:hover .location-image {
            transform: scale(1.1);
        }

        .location-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(102, 126, 234, 0.8), rgba(118, 75, 162, 0.8));
            opacity: 0;
            transition: opacity 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .location-card:hover .location-overlay {
            opacity: 1;
        }

        .location-content {
            padding: 25px;
            position: relative;
        }

        .location-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 12px;
            color: #2d3748;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .location-description {
            color: #4a5568;
            line-height: 1.6;
            font-size: 1rem;
        }

        .read-more-indicator {
            position: absolute;
            bottom: 15px;
            right: 20px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            opacity: 0;
            transform: translateX(10px);
            transition: all 0.3s ease;
        }

        .location-card:hover .read-more-indicator {
            opacity: 1;
            transform: translateX(0);
        }

        .read-more-indicator::after {
            content: '→';
        }

        .no-locations {
            text-align: center;
            padding: 80px 20px;
            color: #4a5568;
            font-size: 1.3rem;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            border: 2px dashed #cbd5e0;
            position: relative;
        }

        .no-locations::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            opacity: 0.1;
        }

        .stats-section {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            margin-bottom: 50px;
            text-align: center;
            border: 1px solid #e2e8f0;
            position: relative;
            overflow: hidden;
        }

        .stats-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2, #f093fb);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }

        .stat-item {
            padding: 25px;
            border-radius: 15px;
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            border: 1px solid #e2e8f0;
            transition: transform 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .stat-item:hover::before {
            opacity: 1;
        }

        .stat-item:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 300;
            color: #667eea;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }

        .stat-label {
            color: #4a5568;
            font-weight: 500;
            position: relative;
            z-index: 1;
        }

        .floating-elements {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }

        .floating-circle {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            animation: float 6s ease-in-out infinite;
        }

        .floating-circle:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }

        .floating-circle:nth-child(2) {
            width: 120px;
            height: 120px;
            top: 60%;
            right: 10%;
            animation-delay: 2s;
        }

        .floating-circle:nth-child(3) {
            width: 60px;
            height: 60px;
            bottom: 20%;
            left: 20%;
            animation-delay: 4s;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px) rotate(0deg);
            }
            50% {
                transform: translateY(-20px) rotate(180deg);
            }
        }

        @media (max-width: 768px) {
            .header {
                padding: 30px 0;
            }

            .header-content {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }

            .header-left h1 {
                font-size: 2.5rem;
            }

            .hero-title {
                font-size: 2.2rem;
            }

            .nav-links {
                justify-content: center;
                gap: 10px;
            }

            .nav-links a {
                padding: 10px 16px;
                font-size: 14px;
            }

            .locations-grid {
                grid-template-columns: 1fr;
                gap: 25px;
            }

            .container {
                padding: 0 15px;
            }

            .main-content {
                padding: 40px 0;
            }

            .stats-section {
                padding: 25px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }

            .floating-elements {
                display: none;
            }
        }

        @media (max-width: 480px) {
            .header-left h1 {
                font-size: 2rem;
            }

            .header-subtitle {
                font-size: 1.1rem;
            }

            .hero-title {
                font-size: 1.8rem;
            }

            .nav-links {
                flex-direction: column;
                gap: 8px;
                width: 100%;
            }

            .nav-links a {
                padding: 12px 20px;
                width: 100%;
                text-align: center;
            }

            .location-content {
                padding: 20px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="floating-elements">
        <div class="floating-circle"></div>
        <div class="floating-circle"></div>
        <div class="floating-circle"></div>
    </div>

    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="header-left">
                    <h1>eXplore Sulut</h1>
                    <p class="header-subtitle">Discover Amazing Places Around North Sulawesi</p>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="welcome-message">
                            Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>!
                        </div>
                    <?php endif; ?>
                </div>
                
                <nav class="nav-links">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if ($_SESSION['is_admin']): ?>
                            <a href="manage.php">Manage</a>
                        <?php endif; ?>
                        <a href="logout.php">Logout</a>
                    <?php else: ?>
                        <a href="login.php">Login</a>
                        <a href="register.php">Register</a>
                    <?php endif; ?>
                    <a href="about.html">About</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <div class="hero-section">
                <h2 class="hero-title">Explore Our Destinations</h2>
                <p class="hero-description">
                    Embark on unforgettable journeys to breathtaking locations around the globe. 
                    Each destination tells a unique story waiting to be discovered.
                </p>
            </div>

            <?php if ($result->num_rows > 0): ?>
                <div class="stats-section">
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $result->num_rows; ?></div>
                            <div class="stat-label">Locations to Explore</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $total_visited; ?></div>
                            <div class="stat-label">Locations Visited</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $locations_remaining; ?></div>
                            <div class="stat-label">Locations Remaining</div>
                        </div>
                    </div>
                </div>

                <div class="locations-grid">
                    <?php while($row = $result->fetch_assoc()): ?>
                        <a href="view.php?id=<?php echo $row["id"]; ?>" class="location-card">
                            <div class="location-image-container">
                                <img src="<?php echo htmlspecialchars($row["photo"]); ?>" 
                                     alt="<?php echo htmlspecialchars($row["location_name"]); ?>" 
                                     class="location-image">
                                <!--
                                <div class="location-overlay">
                                    Visit?
                                </div> -->
                            </div>
                            <div class="location-content">
                                <h3 class="location-title"><?php echo htmlspecialchars($row["location_name"]); ?></h3>
                                <p class="location-description"><?php echo htmlspecialchars(getExcerpt($row["description"])); ?></p>
                                <div class="read-more-indicator"></div>
                            </div>
                        </a>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-locations">
                    <p>No destinations found yet. Check back soon for amazing places to explore!</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>

<?php
$conn->close(); // Close the database connection
?>
