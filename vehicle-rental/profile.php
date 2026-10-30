<?php
require_once 'config/db.php';
$pageTitle = 'My Profile';
$base = '';
require_login();
$uid = current_user()['id'];

$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$uid]);
$user = $stmt->fetch();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'profile') {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        if (!preg_match("/^[A-Za-z][A-Za-z .'-]{1,99}$/", $name)) $errors[] = 'Enter a valid name.';
        if (!preg_match('/^[6-9]\d{9}$/', $phone)) $errors[] = 'Enter a valid 10-digit mobile number.';
        if (!$errors) {
            $pdo->prepare('UPDATE users SET name = ?, phone = ? WHERE id = ?')->execute([$name, $phone, $uid]);
            $_SESSION['user']['name'] = $name;
            flash('success', 'Profile updated.');
            redirect('profile.php');
        }
    } elseif ($action === 'password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if (!password_verify($current, $user['password'])) $errors[] = 'Current password is incorrect.';
        if (strlen($new) < 6 || !preg_match('/[A-Za-z]/', $new) || !preg_match('/\d/', $new)) $errors[] = 'New password must be 6+ characters with letters and numbers.';
        if ($new !== $confirm) $errors[] = 'New passwords do not match.';
        if (!$errors) {
            $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $uid]);
            flash('success', 'Password changed successfully.');
            redirect('profile.php');
        }
    }
}

include 'includes/header.php';
?>
<h1>My Profile</h1>
<?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>

<div class="contact-grid">
    <div class="info-box">
        <h3>Account details</h3>
        <p><strong>Email:</strong> <?= e($user['email']) ?></p>
        <p><strong>Role:</strong> <?= status_badge($user['role']) ?></p>
        <p><strong>Member since:</strong> <?= date('d M Y', strtotime($user['created_at'])) ?></p>
        <hr style="margin:18px 0;border:0;border-top:1px solid var(--border)">
        <h3>Change password</h3>
        <form method="post" class="validate" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="password">
            <div class="form-group"><label for="current_password">Current password</label><input type="password" id="current_password" name="current_password" data-rules="required"></div>
            <div class="form-group"><label for="new_password">New password</label><input type="password" id="new_password" name="new_password" data-rules="required|min:6|strong"></div>
            <div class="form-group"><label for="confirm_password">Confirm new password</label><input type="password" id="confirm_password" name="confirm_password" data-rules="required|match:new_password"></div>
            <button class="btn btn-warning">Change Password</button>
        </form>
    </div>
    <div class="info-box">
        <h3>Edit profile</h3>
        <form method="post" class="validate" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="profile">
            <div class="form-group"><label for="name">Full name</label><input type="text" id="name" name="name" value="<?= e($user['name']) ?>" data-rules="required|name"></div>
            <div class="form-group"><label for="phone">Mobile number</label><input type="tel" id="phone" name="phone" maxlength="10" value="<?= e($user['phone']) ?>" data-rules="required|phone"></div>
            <button class="btn btn-primary">Save Changes</button>
        </form>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
