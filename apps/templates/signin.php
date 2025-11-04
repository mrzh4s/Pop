<?php extend('layouts.auth'); ?>

<?php section('title'); ?>
<?= app_name() ?> - Log Masuk
<?php endsection(); ?>


<?php section('content'); ?>
<div class="d-flex justify-content-center h-100">
    <div class="d-flex flex-column h-100 w-25 justify-content-center">
        <div class="auth-logo mb-3 text-center">
            <a href="/" class="logo-dark">
                <?= component('ui.icon', ['icon' => 'square-binary', 'class' => 'fa-2x text-primary']) ?>
                <span class="fs-30 fw-bold text-dark"> <?= app_name() ?></span>
            </a>

            <a href="/" class="logo-light">
                <?= component('ui.icon', ['icon' => 'binary', 'class' => 'fa-2x text-primary']) ?>
                <span class="fs-30 fw-bold"> <?= app_name() ?></span>
            </a>
        </div>
        <h2 class="fw-bold fs-24">Sign In</h2>

        <p class="text-muted mt-1 mb-4">Enter your Username and password</p>

        <div class="mb-5">
            <form class="authentication-form" id="login-form">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" placeholder="Enter your username">
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Enter your password">
                </div>

                <div class="mb-1 text-center d-grid">
                    <button class="btn btn-soft-primary" type="submit">Sign In</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endsection(); ?>

<?php push('scripts'); ?>
<script>
    document.getElementById('login-form').addEventListener('submit', async function(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        try {
            const response = await fetch('/api/auth/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data),
            });

            if (response.ok) {
                const result = await response.json();
                showToast('success', 'Login Successful', result.message);
                setTimeout(() => {
                    location.reload();
                }, 2500);
            } else {
                const error = await response.json();
                showToast('error', 'Login Failed', error.message);
            }
        } catch (error) {
            showToast('error', 'Error', 'An error occurred. Please try again.');
        }

        
    });
</script>
<?php endpush(); ?>