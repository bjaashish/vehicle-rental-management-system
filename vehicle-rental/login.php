<?php
require_once 'config/db.php';
$pageTitle = 'Login';
$base = '';
if (is_logged_in()) redirect(is_admin() ? 'admin/index.php' : 'index.php');

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $error = 'Please enter a valid email and password.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);   // prevent session fixation
            $_SESSION['user'] = [
                'id' => (int)$user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
            ];
            flash('success', 'Welcome back, ' . $user['name'] . '!');
            redirect($user['role'] === 'admin' ? 'admin/index.php' : 'vehicles.php');
        }
        $error = 'Invalid email or password.';
    }
}

include 'includes/header.php';
?>
<div class="form-card">
    <h1 class="text-center">Welcome Back</h1>
    <p class="text-center muted">Log in to manage your bookings.</p>

    <div class="demo-creds mt">
        <strong>Demo accounts</strong><br>
        Admin: admin@rental.com / admin123<br>
        Customer: user@rental.com / user123
    </div>

    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <form method="post" class="validate" novalidate>
        <?= csrf_field() ?>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= e($email) ?>" data-rules="required|email" autofocus>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" data-rules="required">
        </div>
        <button class="btn btn-primary btn-block">Log In</button>
    </form>
    <p class="form-footer">New here? <a href="register.php">Create an account</a></p>
</div>
<?php include 'includes/footer.php'; ?>
