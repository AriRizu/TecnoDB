<!-- index.php -->
<?php
// Simple router to load the correct page content
$page = !empty($_GET['page']) ? $_GET['page'] : 'landing';
?>
<!doctype html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <title>TecnokeyDB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-body-tertiary">

    <?php 
    // Only include the header on pages other than the landing page
    if ($page !== 'landing') {
        include 'partials/header.php';
    }
    ?>

    <?php 
    // Use a simple <main> for the landing page for full-width control,
    // and a styled one for all other pages.
    if ($page === 'landing'): 
    ?>
    <main>
    <?php else: ?>
    <main class="container-fluid mt-4">
    <?php endif; ?>
        <?php
        // Load the content for the requested page
        switch ($page) {
            case 'dashboard':
                include 'pages/dashboard.php';
                break;
            case 'main':
                include 'pages/main.php';
                break;
            case 'trabajo-form':
                include 'pages/trabajo-form.php';
                break;
            default:
                include 'pages/landing.php';
                break;
        }
        ?>
    </main>
    
    <!-- Toast container for notifications -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1100">
        <!-- Toasts will be appended here by the showToast function -->
    </div>
    
    <?php include 'partials/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Main application logic -->
    <!-- Load main.js globally as it contains the state and core functions -->
    <script src="assets/js/main.js"></script>

    <?php
    // Conditionally load JavaScript based on the current page
    switch ($page) {
        case 'main':
            echo '<script src="assets/js/auto.js"></script>';
            echo '<script src="assets/js/item.js"></script>';
            echo '<script src="assets/js/equipo.js"></script>';
            echo '<script src="assets/js/trabajo.js"></script>';
            echo '<script src="assets/js/cliente.js"></script>';
            break;

        case 'trabajo-form':
            // For the form's "quick add" buttons to work, it needs these scripts...
            echo '<script src="assets/js/auto.js"></script>';
            echo '<script src="assets/js/item.js"></script>';
            echo '<script src="assets/js/equipo.js"></script>';
            echo '<script src="assets/js/cliente.js"></script>';
            // ...and its own dedicated script.
            echo '<script src="assets/js/trabajo-form.js"></script>';
            break;
        
        // Add other cases for other pages if they need specific JS
    }
    ?>
</body>
</html>

