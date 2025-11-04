<!DOCTYPE html>
<html lang="en" class="h-100">

<head>
    <title><?= slot('title', app_name() ?? 'MIGRATE') ?></title>
    <meta charset="utf-8" />
    <meta content="follow, index" name="robots" />
    <meta content="width=device-width, initial-scale=1, shrink-to-fit=no" name="viewport" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta property="og:locale" content="ms_MY" />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="<?= slot('title',  app_name() ?? 'MIGRATE') ?>" />
    <meta property="og:site_name" content="<?= app_name() ?? 'MIGRATE' ?>" />

    <?= csrf_meta() ?>

    <?= stack('styles') ?>

    <?= css('css/vendor.min.css') ?>

    <?= css('css/app.min.css') ?>

    <?= css('fonts/css/all.min.css') ?>


    <?= js('js/config.js') ?>

</head>


<body class="h-100">
    <?= component('ui/toast') ?>

    <div class="wrapper">
        <?= component('ui/header') ?>
        <?= component('ui/sidebar') ?>
        <div class="page-content">
            <div class="container-xxl">
                <?= slot('content') ?>
            </div>
            <?= component('ui/footer') ?>
        </div>
    </div>

    <!-- End of Page -->
    <!-- Scripts -->
    <?= js('js/vendor.js') ?>

    <?= js('js/app.js') ?>

    <?= stack('scripts') ?>
    <!-- End of Scripts -->

</body>
</html>