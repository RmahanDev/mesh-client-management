<?php defined('APP_ROOT') or exit; ?>
<div class="auth-wrap">
    <div class="auth-card">
        <div class="text-center mb-4">
            <img src="<?= asset('img/logo.svg') ?>" width="68" height="68" alt="" class="mb-3" style="filter:drop-shadow(0 10px 25px rgba(37,99,235,.5))">
            <h1 class="h4 fw-black mb-1"><?= e(\App\Core\Settings::get('company_name', config('app_name'))) ?></h1>
            <div class="text-muted small">سامانه مدیریت مشتریان، پرداخت‌ها و پشتیبانی</div>
        </div>
        <div class="card">
            <div class="card-body">
                <?php if ($error): ?><div class="alert alert-danger py-2 small"><?= e($error) ?></div><?php endif; ?>
                <form method="post" action="<?= url('auth/login') ?>" autocomplete="on">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">نام کاربری</label>
                        <div class="input-group"><span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" name="username" class="form-control ltr" required autofocus value="<?= e(input('username')) ?>" autocomplete="username"></div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">رمز عبور</label>
                        <div class="input-group"><span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" name="password" class="form-control ltr" required autocomplete="current-password"></div>
                    </div>
                    <button class="btn btn-grad w-100 py-2"><i class="bi bi-box-arrow-in-left me-1"></i>ورود به پنل</button>
                </form>
            </div>
        </div>
        <div class="text-center mt-3"><button class="btn btn-sm btn-soft" data-toggle-theme><i class="bi bi-circle-half me-1"></i>تغییر تم</button></div>
    </div>
</div>
