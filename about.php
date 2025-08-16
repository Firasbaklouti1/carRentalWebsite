<?php require_once 'includes/auth.php';
require_once 'includes/init.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('About Us - Car Rental System'); ?></title>
    <?php include 'includes/header.php'; ?>
</head>
<body class="bg-light d-flex flex-column min-vh-100">
    <?php include 'includes/navigation.php'; ?>

    <header class="section hero-modern">
        <div class="hero-content container">
            <span class="hero-kicker"><?= __('About Us'); ?></span>
            <h1 class="section-title mb-3"><?= __('We keep you moving'); ?></h1>
            <p class="section-subtitle text-white-75">
                <?= __('Welcome to Car Rental System, your trusted partner for all your car rental needs.'); ?>
            </p>
        </div>
    </header>

    <main class="container section">
        <div class="row g-4 align-items-stretch">
            <div class="col-lg-5">
                <div class="card modern-card h-100">
                    <div class="card-body p-4 p-md-5">
                        <h2 class="h4 mb-3"><?= __('Our Story'); ?></h2>
                        <p class="text-muted mb-0">
                            <?= __('Founded with a vision to make car rentals accessible and hassle-free, we ve grown to become one of the most trusted names in the industry. Our commitment to customer satisfaction and quality service has earned us the loyalty of countless satisfied customers.'); ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card modern-card h-100 text-center">
                            <div class="card-body p-4">
                                <i class="fas fa-car-side fa-2x text-primary mb-3" aria-hidden="true"></i>
                                <h3 class="h6 mb-1"><?= __('Wide Selection'); ?></h3>
                                <p class="text-muted mb-0"><?= __('Choose from our diverse fleet of well-maintained vehicles.'); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card modern-card h-100 text-center">
                            <div class="card-body p-4">
                                <i class="fas fa-shield-alt fa-2x text-primary mb-3" aria-hidden="true"></i>
                                <h3 class="h6 mb-1"><?= __('Safe & Reliable'); ?></h3>
                                <p class="text-muted mb-0"><?= __('All our vehicles undergo regular maintenance checks.'); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card modern-card h-100 text-center">
                            <div class="card-body p-4">
                                <i class="fas fa-headset fa-2x text-primary mb-3" aria-hidden="true"></i>
                                <h3 class="h6 mb-1"><?= __('24/7 Support'); ?></h3>
                                <p class="text-muted mb-0"><?= __('Our customer support team is always here to help.'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <section class="section pt-5">
            <h2 class="h4 text-center mb-4"><?= __('Why Choose Us?'); ?></h2>
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card modern-card h-100">
                        <div class="card-body">
                            <ul class="list-unstyled mb-0">
                                <li class="mb-3 d-flex align-items-center">
                                    <i class="fas fa-check-circle text-primary me-2" aria-hidden="true"></i>
                                    <span><?= __('Competitive pricing and transparent fees'); ?></span>
                                </li>
                                <li class="mb-3 d-flex align-items-center">
                                    <i class="fas fa-check-circle text-primary me-2" aria-hidden="true"></i>
                                    <span><?= __('Flexible rental periods'); ?></span>
                                </li>
                                <li class="mb-0 d-flex align-items-center">
                                    <i class="fas fa-check-circle text-primary me-2" aria-hidden="true"></i>
                                    <span><?= __('Regular maintenance and cleaning'); ?></span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card modern-card h-100">
                        <div class="card-body">
                            <ul class="list-unstyled mb-0">
                                <li class="mb-3 d-flex align-items-center">
                                    <i class="fas fa-check-circle text-primary me-2" aria-hidden="true"></i>
                                    <span><?= __('Easy online booking system'); ?></span>
                                </li>
                                <li class="mb-3 d-flex align-items-center">
                                    <i class="fas fa-check-circle text-primary me-2" aria-hidden="true"></i>
                                    <span><?= __('Multiple pickup locations'); ?></span>
                                </li>
                                <li class="mb-0 d-flex align-items-center">
                                    <i class="fas fa-check-circle text-primary me-2" aria-hidden="true"></i>
                                    <span><?= __('Comprehensive insurance coverage'); ?></span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
