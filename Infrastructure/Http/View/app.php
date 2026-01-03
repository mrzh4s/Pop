<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= config('app.name', 'KITER') ?></title>

    <?php if (env('APP_ENV') === 'local' || env('APP_ENV') === 'development'): ?>
        <!-- Vite Dev Server -->
        <?php
        $viteHost = env('VITE_HOST', 'localhost');
        $vitePort = env('VITE_PORT', '5173');
        $viteUrl = "http://{$viteHost}:{$vitePort}";
        ?>
        <script type="module" src="<?= $viteUrl ?>/@vite/client"></script>
        <script type="module" src="<?= $viteUrl ?>/Infrastructure/Resources/js/app.jsx"></script>
    <?php else: ?>
        <!-- Production Assets -->
        <?php
        $manifestPath = ROOT_PATH . '/Infrastructure/Http/Public/assets/.vite/manifest.json';
        if (file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);
            $entryKey = 'Infrastructure/Resources/js/app.jsx';
            if (isset($manifest[$entryKey])) {
                $jsFile = '/assets/' . $manifest[$entryKey]['file'];
                echo '<script type="module" src="' . $jsFile . '"></script>';

                // Load CSS if exists
                if (isset($manifest[$entryKey]['css'])) {
                    foreach ($manifest[$entryKey]['css'] as $cssFile) {
                        echo '<link rel="stylesheet" href="/assets/' . $cssFile . '">';
                    }
                }
            }
        }
        ?>
    <?php endif; ?>
</head>
<body>
    <div id="app" data-page='<?= $page ?>'></div>
</body>
</html>
