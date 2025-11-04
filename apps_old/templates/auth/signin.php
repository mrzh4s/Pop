<?php extend('layouts.auth'); ?>

<?php section('title'); ?>
<?= app_name() ?> - Log Masuk
<?php endsection(); ?>


<?php section('content'); ?>
<div class="kt-card max-w-[370px] w-full">
    <form class="kt-card-content flex flex-col gap-5 p-10" id="sign_in_form">
        <a href="<?= route('home') ?>" class="inline-flex items-center justify-center">
            <?= img('apps/' . app_name() . '-default.webp', app_name(), ['class' => 'dark:hidden h-10 w-auto']) ?>
            <?= img('apps/' . app_name() . '-dark.webp', app_name(), ['class'=>'hidden dark:block h-10 w-auto']) ?>
        </a>

        <div class="text-center mb-2.5">
            <h3 class="text-lg font-medium text-mono leading-none mb-2.5">
                Log Masuk
            </h3>
            <div class="flex items-center justify-center font-medium">
                <span class="text-sm text-secondary-foreground me-1.5">
                    Daftar akaun baru?
                </span>
                <a class="text-sm link" href="<?= route('auth.signup') ?>">
                    Di sini
                </a>
            </div>
        </div>

        <!-- Email Field -->
        <div class="kt-form-item flex flex-col gap-1">
            <label class="kt-form-label font-normal text-mono">
                E-mel
            </label>
            <div class="kt-form-control">
                <input class="kt-input" name="email" placeholder="Email Anda" type="text" value="" />
            </div>
            <div class="kt-form-message" style="display: none;"></div>
        </div>

        <!-- Password Field -->
        <div class="kt-form-item flex flex-col gap-1">
            <div class="flex items-center justify-between gap-1">
                <label class="kt-form-label font-normal text-mono">
                    Kata Laluan
                </label>
                <a class="text-sm kt-link shrink-0" href="<?= route('auth.reset') ?>">
                    Terlupa Kata Laluan?
                </a>
            </div>
            <div class="kt-form-control">
                <div class="kt-input" data-kt-toggle-password="true">
                    <input name="password" placeholder="Kata Laluan Anda" type="password" value="" />
                    <button class="kt-btn kt-btn-sm kt-btn-ghost kt-btn-icon bg-transparent! -me-1.5" data-kt-toggle-password-trigger="true" type="button">
                        <span class="kt-toggle-password-active:hidden">
                            <?= component('ui.icon', ['icon' => 'eye', 'variant' => 'far', 'class' => 'text-muted-foreground']) ?>
                        </span>
                        <span class="hidden kt-toggle-password-active:block">
                            <?= component('ui.icon', ['icon' => 'eye-slash', 'variant' => 'far', 'class' => 'text-muted-foreground']) ?>
                        </span>
                    </button>
                </div>
            </div>
            <div class="kt-form-message" style="display: none;"></div>
        </div>

        <!-- Remember Me -->
        <label class="kt-label">
            <input class="kt-checkbox kt-checkbox-sm" name="remember" type="checkbox" value="1" />
            <span class="kt-checkbox-label">
                Ingat Saya
            </span>
        </label>

        <!-- Submit Button -->
        <button type="submit" id="sign_in_submit" class="kt-btn kt-btn-primary flex justify-center grow">Log Masuk</button>
    </form>
</div>
<?php endsection(); ?>

<?php push('scripts'); ?>
<?= js('js/auth/login.js') ?>
<?php endpush(); ?>