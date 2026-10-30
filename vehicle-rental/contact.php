<?php
require_once 'config/db.php';
$pageTitle = 'Contact Us';
$base = '';
$errors = [];
$old = ['name' => current_user()['name'] ?? '', 'email' => current_user()['email'] ?? '', 'subject' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    foreach ($old as $k => $_) $old[$k] = trim($_POST[$k] ?? '');
    if (strlen($old['name']) < 2) $errors['name'] = 'Please enter your name.';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email.';
    if (strlen($old['subject']) < 3) $errors['subject'] = 'Subject must be at least 3 characters.';
    if (strlen($old['message']) < 10) $errors['message'] = 'Message must be at least 10 characters.';

    if (!$errors) {
        $pdo->prepare('INSERT INTO messages (name, email, subject, message) VALUES (?, ?, ?, ?)')
            ->execute([$old['name'], $old['email'], $old['subject'], $old['message']]);
        flash('success', 'Thank you! Your message has been sent. We will get back to you soon.');
        redirect('contact.php');
    }
}

include 'includes/header.php';
?>
<h1>Contact Us</h1>
<p class="muted">Questions about a booking or a vehicle? Send us a message.</p>

<div class="contact-grid mt">
    <div class="info-box">
        <h3>Get in touch</h3>
        <p>📍 12 Rental Street, Connaught Place, New Delhi 110001</p>
        <p>📞 +91 98765 43210</p>
        <p>✉️ support@driveeasy.com</p>
        <p>🕘 Mon–Sun, 8:00 AM – 10:00 PM</p>
    </div>
    <div class="info-box">
        <form method="post" class="validate" novalidate>
            <?= csrf_field() ?>
            <div class="form-row">
                <div class="form-group">
                    <label for="name">Name <span class="req">*</span></label>
                    <input type="text" id="name" name="name" value="<?= e($old['name']) ?>" data-rules="required|min:2">
                    <small class="error-msg"><?= e($errors['name'] ?? '') ?></small>
                </div>
                <div class="form-group">
                    <label for="email">Email <span class="req">*</span></label>
                    <input type="email" id="email" name="email" value="<?= e($old['email']) ?>" data-rules="required|email">
                    <small class="error-msg"><?= e($errors['email'] ?? '') ?></small>
                </div>
            </div>
            <div class="form-group">
                <label for="subject">Subject <span class="req">*</span></label>
                <input type="text" id="subject" name="subject" maxlength="150" value="<?= e($old['subject']) ?>" data-rules="required|min:3|max:150">
                <small class="error-msg"><?= e($errors['subject'] ?? '') ?></small>
            </div>
            <div class="form-group">
                <label for="message">Message <span class="req">*</span></label>
                <textarea id="message" name="message" rows="5" maxlength="2000" data-rules="required|min:10|max:2000"><?= e($old['message']) ?></textarea>
                <small class="error-msg"><?= e($errors['message'] ?? '') ?></small>
            </div>
            <button class="btn btn-primary">Send Message</button>
        </form>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
