<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Pop Framework'; ?></title>

    <!-- Metronic Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />

    <?php if (env('APP_ENV') === 'local'): ?>
        <!-- Development: Vite Dev Server -->
        <script type="module" src="http://localhost:5173/@vite/client"></script>
        <script type="module" src="http://localhost:5173/src/main.jsx"></script>
    <?php else: ?>
        <!-- Production: Built Assets -->
        <link rel="stylesheet" href="/assets/css/main.css">
        <script type="module" src="/assets/js/main.js"></script>
    <?php endif; ?>

    <style>
        .dark body {
            background-color: hsl(240 10% 4%);
        }
    </style>

    <script>
        (function () {
            try {
                const theme = localStorage.getItem('vite-theme') || 'system';
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                const isDarkMode = theme === 'dark' || (theme === 'system' && prefersDark);
                if (isDarkMode) document.documentElement.classList.add('dark');
            } catch (e) {}
        })();
    </script>
</head>
<body class="antialiased">
    <div id="app" data-page='<?php echo $page; ?>'></div>
</body>
</html>
