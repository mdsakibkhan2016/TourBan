<?php
require_once __DIR__ . '/../config/env.php';

// Start session with secure cookie settings
secure_session_start();

$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$userName = $isLoggedIn ? $_SESSION['user_name'] : '';
$base = BASE_URL;
?>
<!DOCTYPE html>
<html lang="bn">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>TourBan Website</title>

    <!-- Bootstrap 5 CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous" />

    <!-- External Dependencies -->
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet" />
    <link
        href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;500;700&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />

    <!-- Custom Styles -->
    <link rel="stylesheet" href="assets/css/main.css" />
    <link rel="stylesheet" href="assets/css/footer.css" />
    <link rel="stylesheet" href="assets/css/chatbot.css" />
    <link rel="stylesheet" href="assets/css/registration.css" />
    <link rel="stylesheet" href="assets/css/login.css" />
</head>

<body>
    <!-- Bootstrap Navbar Start -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
        <div class="container">
            <a class="navbar-brand" href="<?php echo htmlspecialchars($base ?: '/', ENT_QUOTES, 'UTF-8'); ?>">
                <img
                    src="assets/images/logo.png"
                    alt="Tourist Logo"
                    height="50"
                    class="d-inline-block align-text-top" />
            </a>

            <button
                class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navbarNav"
                aria-controls="navbarNav"
                aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" href="<?php echo htmlspecialchars($base ?: '/', ENT_QUOTES, 'UTF-8'); ?>">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo htmlspecialchars($base, ENT_QUOTES, 'UTF-8'); ?>/about.php">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo htmlspecialchars($base, ENT_QUOTES, 'UTF-8'); ?>/services.php">Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo htmlspecialchars($base, ENT_QUOTES, 'UTF-8'); ?>/destinations.php">Destinations</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo htmlspecialchars($base, ENT_QUOTES, 'UTF-8'); ?>/contact.php">Contact</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <?php if ($isLoggedIn): ?>
                        <!-- User is logged in - show user menu -->
                        <div class="dropdown">
                            <button class="btn btn-outline-primary dropdown-toggle px-4" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($userName); ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="<?php echo htmlspecialchars($base, ENT_QUOTES, 'UTF-8'); ?>/dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                                <li><a class="dropdown-item" href="<?php echo htmlspecialchars($base, ENT_QUOTES, 'UTF-8'); ?>/profile.php"><i class="fas fa-user-edit me-2"></i>Profile</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item text-danger" href="#" onclick="logout()"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <!-- User is not logged in - show login/register buttons -->
                        <a href="<?php echo htmlspecialchars($base, ENT_QUOTES, 'UTF-8'); ?>/login.php" class="btn btn-outline-primary px-4 me-2">Login</a>
                        <a href="<?php echo htmlspecialchars($base, ENT_QUOTES, 'UTF-8'); ?>/register.php" class="btn btn-primary px-4">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <!-- Bootstrap Navbar End -->

    <!-- Logout JavaScript Function -->
    <script>
        window.BASE_URL = <?php echo json_encode($base, JSON_UNESCAPED_SLASHES); ?>;

        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                fetch('api/logout.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        }
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            window.location.href = (window.BASE_URL || '') + '/login.php';
                        } else {
                            alert('Logout failed. Please try again.');
                        }
                    })
                    .catch(error => {
                        console.error('Logout error:', error);
                        // Force redirect even if API call fails
                        window.location.href = (window.BASE_URL || '') + '/login.php';
                    });
            }
        }
    </script>