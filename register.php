<?php
require_once __DIR__ . '/db.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$name = '';
$email = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim((string)($_POST['full_name'] ?? ''));
    $email = strtolower(
        trim((string)($_POST['email'] ?? ''))
    );
    $phone = trim((string)($_POST['phone'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    if (!valid_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Session expired. Refresh and try again.';
    }

    if (strlen($name) < 2 || strlen($name) > 150) {
        $errors[] = 'Full name must be 2 to 150 characters.';
    }

    if (
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($email) > 190
    ) {
        $errors[] = 'Enter a valid email address.';
    }

    if (
        $phone !== '' &&
        !preg_match('/^\+?[0-9 ()-]{7,25}$/', $phone)
    ) {
        $errors[] = 'Enter a valid phone number.';
    }

    if (strlen($password) < 8 || strlen($password) > 72) {
        $errors[] = 'Password must be 8 to 72 characters.';
    }

    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {

        try {

            $stmt = $pdo->prepare(
                'INSERT INTO users
                (full_name, email, phone, password_hash)
                VALUES (?, ?, ?, ?)'
            );

            $stmt->execute([
                $name,
                $email,
                $phone === '' ? null : $phone,
                password_hash($password, PASSWORD_DEFAULT)
            ]);

            header('Location: login.php?registered=1');
            exit;

        } catch (PDOException $e) {

            if ($e->getCode() === '23000') {
                $errors[] = 'This email is already registered.';
            } else {
                $errors[] = 'Registration failed. Check database.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport"
      content="width=device-width, initial-scale=1">

<title>Create Account | BookAFlight</title>

<link rel="stylesheet" href="auth.css">
</head>

<body>

<header class="topbar">

    <a class="brand" href="index.php">
        ✈ <span>Book</span>AFlight
    </a>

    <a href="login.php">Login</a>

</header>

<main class="auth-container">

<section class="panel">

    <h1>Create your account</h1>

    <p class="muted">
        Join BookAFlight to manage your travel profile.
    </p>

    <?php if ($errors): ?>

    <div class="alert error">

        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo esc($error); ?></li>
            <?php endforeach; ?>
        </ul>

    </div>

    <?php endif; ?>

    <form method="POST" action="register.php">

        <input
            type="hidden"
            name="csrf_token"
            value="<?php echo esc(csrf_token()); ?>"
        >

        <label>
            Full Name

            <input
                type="text"
                name="full_name"
                maxlength="150"
                value="<?php echo esc($name); ?>"
                required
                autocomplete="name"
            >
        </label>

        <label>
            Email Address

            <input
                type="email"
                name="email"
                maxlength="190"
                value="<?php echo esc($email); ?>"
                required
                autocomplete="email"
            >
        </label>

        <label>
            Phone Number (Optional)

            <input
                type="tel"
                name="phone"
                maxlength="25"
                value="<?php echo esc($phone); ?>"
                autocomplete="tel"
            >
        </label>

        <label>
            Password

            <input
                type="password"
                name="password"
                minlength="8"
                maxlength="72"
                required
                autocomplete="new-password"
            >
        </label>

        <label>
            Confirm Password

            <input
                type="password"
                name="confirm_password"
                minlength="8"
                maxlength="72"
                required
                autocomplete="new-password"
            >
        </label>

        <button class="btn" type="submit">
            Create Account
        </button>

    </form>

    <p class="small">
        Already registered?
        <a href="login.php">Login here</a>
    </p>

</section>

</main>

</body>
</html>