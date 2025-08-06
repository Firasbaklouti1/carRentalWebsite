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
<body class="bg-light">
    <?php include 'includes/navigation.php'; ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-5">
                        <h1 class="display-4 mb-4"><?= __('About Us'); ?></h1>
                        <p class="lead text-muted mb-4">
                            <?= __('Welcome to Car Rental System, your trusted partner for all your car rental needs.'); ?>
                        </p>

                        <div class="row g-4 mb-5">
                            <div class="col-md-4">
                                <div class="text-center">
                                    <i class="fas fa-car-side fa-3x text-primary mb-3"></i>
                                    <h4><?= __('Wide Selection'); ?></h4>
                                    <p class="text-muted"><?= __('Choose from our diverse fleet of well-maintained vehicles.'); ?></p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
                                    <h4><?= __('Safe & Reliable'); ?></h4>
                                    <p class="text-muted"><?= __('All our vehicles undergo regular maintenance checks.'); ?></p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <i class="fas fa-headset fa-3x text-primary mb-3"></i>
                                    <h4><?= __('24/7 Support'); ?></h4>
                                    <p class="text-muted"><?= __('Our customer support team is always here to help.'); ?></p>
                                </div>
                            </div>
                        </div>

                        <h3 class="mb-4"><?= __('Our Story'); ?></h3>
                        <p class="mb-4">
                            <?= __('Founded with a vision to make car rentals accessible and hassle-free, we ve grown to become
                            one of the most trusted names in the industry. Our commitment to customer satisfaction and
                            quality service has earned us the loyalty of countless satisfied customers.'); ?>
                        </p>

                        <h3 class="mb-4"><?= __('Why Choose Us?'); ?></h3>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li class="mb-3">
                                        <i class="fas fa-check-circle text-primary me-2"></i>
                                        <?= __('Competitive pricing and transparent fees'); ?>
                                    </li>
                                    <li class="mb-3">
                                        <i class="fas fa-check-circle text-primary me-2"></i>
                                        <?= __('Flexible rental periods'); ?>
                                    </li>
                                    <li class="mb-3">
                                        <i class="fas fa-check-circle text-primary me-2"></i>
                                        <?= __('Regular maintenance and cleaning'); ?>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li class="mb-3">
                                        <i class="fas fa-check-circle text-primary me-2"></i>
                                        <?= __('Easy online booking system'); ?>
                                    </li>
                                    <li class="mb-3">
                                        <i class="fas fa-check-circle text-primary me-2"></i>
                                        <?= __('Multiple pickup locations'); ?>
                                    </li>
                                    <li class="mb-3">
                                        <i class="fas fa-check-circle text-primary me-2"></i>
                                        <?= __('Comprehensive insurance coverage'); ?>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
