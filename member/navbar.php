<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$default_icon_path = "../img/default-profile.jpg";
$member_name = "Guest";
$profile_icon_src = $default_icon_path;

if (isset($_SESSION['Member_IC']) && !empty($_SESSION['Member_IC'])) {
    $member_name = $_SESSION['Member_Name'] ?? 'Member';
    $profile_icon_src = $_SESSION['Member_Image'] ?? $default_icon_path;
    $_SESSION["loggedin"] = true;
} elseif (isset($_SESSION['Staff_IC']) && !empty($_SESSION['Staff_IC'])) {
    $member_name = $_SESSION['Staff_Name'] ?? 'Staff';
    $profile_icon_src = $_SESSION['Staff_Image'] ?? $default_icon_path;
    $_SESSION["loggedin"] = true;
} else {
    $_SESSION["loggedin"] = false;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Navbar</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

    <style>
        * {
            font-family: 'Poppins', sans-serif;
            font-weight: 400;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 150vh;
            background-color: #f8f8f8;
        }

        .navbar {
            position: sticky;
            top: 0;
            height: auto;
            width: 100%;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background-color: white;
            padding: 20px 100px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .logo img {
            height: 40px;
            transition: transform 0.3s ease;
        }

        .logo img:hover {
            transform: scale(1.05);
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .menu {
            position: relative;
        }

        .menu-button {
            background-color: #00c4cc;
            color: white;
            padding: 6px 8px;
            font-size: 16px;
            border: none;
            border-radius: 6px;
            width: 130px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .menu-button:hover {
            background-color: #009ea6;
            transform: translateY(-2px);
        }

        .menu-content {
            display: none;
            position: absolute;
            background-color: white;
            min-width: 130px;
            box-shadow: 0px 6px 12px rgba(0, 0, 0, 0.2);
            z-index: 13;
            border-radius: 6px;
            right: 0;
            top: 100%;
            overflow: hidden;
            transform-origin: top;
            animation: fadeInScale 0.3s ease forwards;
        }

        .menu:hover .menu-content {
            display: block;
        }

        .menu-content a {
            color: black;
            padding: 7px 12px;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            width: 100%;
            height: 50px;
            transition: background-color 0.3s ease, color 0.3s ease, padding-left 0.3s ease;
        }

        .menu-content a:hover {
            background-color: #f0f0f0;
            color: #00c4cc;
            padding-left: 15px;
        }

        .side-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            color: black;
            padding: 7px 12px;
            font-size: 16px;
            border: 2px solid transparent;
            border-radius: 4px;
            text-decoration: none;
            transition: all 0.3s ease, transform 0.2s ease;
            width: 160px;
            box-sizing: border-box;
            position: relative; /* Added for dropdown positioning */
        }

        .side-btn:hover {
            border-color: #00c4cc;
            background-color: white;
            color: #00c4cc;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 196, 204, 0.2);
        }

        /* New menu content style matching side-btn width */
        .side-menu-content {
            display: none;
            position: absolute;
            background-color: white;
            width: 160px; /* Match side-btn width */
            box-shadow: 0px 6px 12px rgba(0, 0, 0, 0.2);
            z-index: 13;
            border-radius: 6px;
            left: 0; /* Align under the button */
            top: 100%;
            overflow: hidden;
            transform-origin: top;
            animation: fadeInScale 0.3s ease forwards;
        }

        .menu:hover .side-menu-content {
            display: block;
        }

        .side-menu-content a {
            color: black;
            padding: 7px 12px;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            width: 100%;
            height: 50px;
            transition: background-color 0.3s ease, color 0.3s ease, padding-left 0.3s ease;
        }

        .side-menu-content a:hover {
            background-color: #f0f0f0;
            color: #00c4cc;
            padding-left: 15px;
        }

        .profile-menu-button {
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .profile-menu-button .profile-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #00c4cc;
            padding: 2px;
            box-sizing: border-box;
            transition: border-color 0.3s ease, transform 0.2s ease;
        }

        .profile-menu-button .bi-person-circle {
            font-size: 40px !important;
            color: #00c4cc !important;
            border: 2px solid #00c4cc;
            border-radius: 50%;
            padding: 2px;
            box-sizing: border-box;
            transition: color 0.3s ease, border-color 0.3s ease, transform 0.2s ease;
        }

        .profile-menu-button:hover .profile-icon,
        .profile-menu-button:hover .bi-person-circle {
            border-color: #009ea6;
            transform: scale(1.05);
        }

        .profile-menu-button:hover .bi-person-circle {
            color: #009ea6 !important;
        }

        .profile-dropdown-content {
            background-color: #ccc;
            min-width: 180px;
            border-radius: 10px;
            overflow: hidden;
            right: 0;
            top: 100%;
            padding: 5px;
            box-sizing: border-box;
            position: absolute;
            display: none;
            box-shadow: 0px 6px 12px rgba(0, 0, 0, 0.2);
            z-index: 13;
            transform-origin: top;
            animation: fadeInScale 0.3s ease forwards;
        }

        .profile-dropdown-content .dropdown-header-welcome {
            background-color: white;
            color: black;
            padding: 7px 12px;
            text-align: center;
            font-weight: 500;
            border-radius: 8px;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
        }

        .profile-dropdown-content a {
            background-color: #00c4cc;
            display: flex;
            text-align: center;
            color: white;
            font-size: 14px;
            border-radius: 8px;
            margin-bottom: 5px;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-sizing: border-box;
            justify-content: center;
            align-items: center;
        }

        .profile-dropdown-content a:last-child {
            margin-bottom: 0;
        }

        .profile-dropdown-content a:hover {
            background-color: #009ea6;
            color: white;
            transform: translateX(3px);
        }

        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scaleY(0);
            }
            to {
                opacity: 1;
                transform: scaleY(1);
            }
        }
    </style>
</head>

<body>
    <div class="navbar">
        <div class="logo">
            <a href="./homepage.php">
                <img src="../img/logo.png" alt="Happy Tooth Logo" />
            </a>
        </div>
        <div class="nav-right">
            <div class="menu">
                <button class="menu-button">About Us ▼</button>
                <div class="menu-content">
                    <a href="./homepage.php">About Us</a>
                    <a href="./staff_dir.php">Staff Directory</a>
                    <a href="./web_policy.php">Web Policy</a>
                </div>
            </div>
            <a href="./view_package.php" class="side-btn">Membership</a>
            <div class="menu">
                <a href="#" class="side-btn">Appointment ▼</a>
                <div class="side-menu-content">
                    <a href="./view_appt.php">Book</a>
                    <a href="./view_history.php">History</a>
                </div>
            </div>

            <div class="menu">
                <button class="profile-menu-button" id="profileMenuButton">
                    <?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                        <img src="<?php echo htmlspecialchars($profile_icon_src); ?>" alt="User Icon" class="profile-icon">
                    <?php else: ?>
                        <i class="bi bi-person-circle fs-4"></i>
                    <?php endif; ?>
                </button>
                <div class="menu-content profile-dropdown-content" id="profileDropdownContent">
                    <?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                        <div class="dropdown-header-welcome">Welcome, <?php echo htmlspecialchars($member_name); ?>!</div>
                        <a href="./profile.php">My Profile</a>
                        <a href="../auth/logout.php" onclick="return confirmLogout();" style="background-color:red;">
                            <span class="menu-label">Logout</span>
                        </a>
                    <?php else: ?>
                        <a href="../auth/login.php">Login</a>
                        <a href="../auth/register.php">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function confirmLogout() {
            return confirm('Are you sure you want to logout?');
        }

        document.addEventListener('DOMContentLoaded', function () {
            const profileMenuButton = document.getElementById('profileMenuButton');
            const profileDropdownContent = document.getElementById('profileDropdownContent');

            // Function to toggle display for the profile menu
            function toggleProfileMenu() {
                profileDropdownContent.classList.toggle('show');
            }

            // Click listener for Profile button
            profileMenuButton.addEventListener('click', function (event) {
                event.stopPropagation(); // Prevent document click from immediately closing
                toggleProfileMenu();
            });

            // Click listener on document to close only the profile menu if clicked outside
            document.addEventListener('click', function (event) {
                if (!profileDropdownContent.contains(event.target) && event.target !== profileMenuButton) {
                    profileDropdownContent.classList.remove('show');
                }
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>