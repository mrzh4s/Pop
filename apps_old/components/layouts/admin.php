<!DOCTYPE html>
<html class="h-full" data-kt-theme="true" data-kt-theme-mode="light" dir="ltr" lang="en">

<head>
    <base href="../../../../" />
    <title><?= slot('title', app_name() ?? 'APP') ?></title>
    <meta charset="utf-8" />
    <meta content="follow, index" name="robots" />
    <meta content="width=device-width, initial-scale=1, shrink-to-fit=no" name="viewport" />
    <meta property="og:locale" content="ms_MY" />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="<?= slot('title',  app_name() ?? 'APP') ?>" />
    <meta property="og:site_name" content="<?= app_name() ?? 'APP' ?>" />

    <?= csrf_meta() ?>

    <?= favicon('media/apps/favicon.ico') ?>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />

    <?= css('css/styles.css') ?>
    <?= css('vendors/fontawesome/css/fontawesome.min.css') ?>

</head>
<style>
    .page-bg {
        background-image: url('<?= media('images/2600x1200/bg-1.png') ?>');
    }

    .dark .page-bg {
        background-image: url('<?= media('images/2600x1200/bg-1-dark.png') ?>'); 
    }
</style>
<?= stack('styles') ?>


<body class="antialiased flex h-full text-base text-foreground bg-background">
    <!-- Theme Mode -->
    <script>
        const defaultThemeMode = 'light'; // light|dark|system
        let themeMode;

        if (document.documentElement) {
            if (localStorage.getItem('kt-theme')) {
                themeMode = localStorage.getItem('kt-theme');
            } else if (
                document.documentElement.hasAttribute('data-kt-theme-mode')
            ) {
                themeMode =
                    document.documentElement.getAttribute('data-kt-theme-mode');
            } else {
                themeMode = defaultThemeMode;
            }

            if (themeMode === 'system') {
                themeMode = window.matchMedia('(prefers-color-scheme: dark)').matches ?
                    'dark' :
                    'light';
            }

            document.documentElement.classList.add(themeMode);
        }
    </script>
    <!-- End of Theme Mode -->
    <div class="flex items-center justify-center grow bg-center bg-no-repeat page-bg">
        <?= slot('content') ?>
    </div>

    <!-- End of Page -->
    <!-- Scripts -->
    <?= js('js/core.bundle.js') ?>

    <?= js('vendors/ktui/ktui.min.js') ?>
    <?= js('vendors/axios/axios.min.js') ?>

    <?= stack('scripts') ?>
    <!-- End of Scripts -->

</body>
</html>