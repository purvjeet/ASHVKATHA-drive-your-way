<?php
/**
 * ASHVKATHA — Smart Vehicle Rental & Fleet Management System
 * Global HTML Header Include
 */
require_once __DIR__ . '/functions.php';

if (!isset($page_title)) {
    $page_title = "ASHVKATHA — Smart Vehicle Rental & Fleet Management";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="ASHVKATHA - Drive Your Way. Premier vehicle rental, supercar hire, and fleet management platform.">
    
    <!-- Google Fonts: Schibsted Grotesk & Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Schibsted+Grotesk:ital,wght@0,600;0,700;0,800;0,900;1,700&display=swap" rel="stylesheet">
    
    <!-- FontAwesome Vector Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- CSS Design System Stylesheets -->
    <link rel="stylesheet" href="<?php echo url('/assets/css/global.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('/assets/css/navbar.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('/assets/css/forms.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('/assets/css/responsive.css'); ?>">

    <?php if (isset($extra_css) && is_array($extra_css)): ?>
        <?php foreach ($extra_css as $css): ?>
            <link rel="stylesheet" href="<?php echo url('/assets/css/' . $css); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body class="<?php echo $body_class ?? ''; ?>">
<?php echo display_flash_messages(); ?>
