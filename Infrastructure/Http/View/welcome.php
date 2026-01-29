<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - Pop Framework</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-linear-to-br from-slate-50 to-slate-100 min-h-screen">
    <div class="container mx-auto px-4 py-16">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-16">
                <h1 class="text-6xl font-bold text-slate-900 mb-4">
                    Pop Framework
                </h1>
                <p class="text-xl text-slate-600">
                    Modern PHP with Vertical Slice Architecture
                </p>
            </div>
            <!-- Feature Cards -->
            <div class="grid md:grid-cols-3 gap-6 mb-16">
                <div class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow">
                    <div class="text-3xl mb-3">⚡</div>
                    <h3 class="text-lg font-semibold text-slate-900 mb-2">Fast & Minimal</h3>
                    <p class="text-slate-600 text-sm">Zero external dependencies, built for performance</p>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow">
                    <div class="text-3xl mb-3">🎯</div>
                    <h3 class="text-lg font-semibold text-slate-900 mb-2">VSA Architecture</h3>
                    <p class="text-slate-600 text-sm">Vertical slices for maintainable features</p>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow">
                    <div class="text-3xl mb-3">⚛️</div>
                    <h3 class="text-lg font-semibold text-slate-900 mb-2">Inertia + React</h3>
                    <p class="text-slate-600 text-sm">Modern SPA without the API complexity</p>
                </div>
            </div>

            <!-- CTA Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                <a href="<?= route('auth.signin') ?>"
                   class="px-8 py-3 bg-slate-900 text-white rounded-lg font-medium hover:bg-slate-800 transition-colors w-full sm:w-auto text-center">
                    Sign In
                </a>
                <a href="<?= route('auth.register') ?>"
                   class="px-8 py-3 bg-white text-slate-900 rounded-lg font-medium border-2 border-slate-200 hover:border-slate-300 transition-colors w-full sm:w-auto text-center">
                    Create Account
                </a>
            </div>

            <!-- Footer -->
            <div class="text-center mt-16 text-sm text-slate-500">
                <p>Built with PHP 8.4 · Tailwind CSS · React · Inertia.js</p>
            </div>
        </div>
    </div>
</body>
</html>
