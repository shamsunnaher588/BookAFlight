<?php
require_once __DIR__ . '/db.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = strtolower(
        trim((string)($_POST['email'] ?? ''))
    );

    $password = (string)($_POST['password'] ?? '');

    if (!valid_csrf($_POST['csrf_token'] ?? null)) {

        $error = 'Session expired. Refresh and try again.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = 'Enter a valid email address.';

    } else {

        $stmt = $pdo->prepare(
            'SELECT id, full_name, password_hash
             FROM users
             WHERE email = ?
             LIMIT 1'
        );

        $stmt->execute([$email]);

        $user = $stmt->fetch();

        if (
            $user &&
            password_verify(
                $password,
                $user['password_hash']
            )
        ) {

            session_regenerate_id(true);

            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['user_name'] = $user['full_name'];

            header('Location: dashboard.php');
            exit;
        }

        $error = 'Email or password is incorrect.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport"
      content="width=device-width, initial-scale=1">

<title>Login | BookAFlight</title>

<link rel="stylesheet" href="auth.css">
</head>

<body>

<header class="topbar">

    <a class="brand" href="index.php">
        ✈ <span>Book</span>AFlight
    </a>

    <a href="register.php">Register</a>

</header>

<main class="auth-container">

<section class="panel">

    <h1>Welcome Back</h1>

    <p class="muted">
        Log in to your BookAFlight account.
    </p>

    <?php if (isset($_GET['registered'])): ?>

        <div class="alert success">
            Account created successfully.
            Please log in.
        </div>

    <?php endif; ?>

    <?php if ($error): ?>

        <div class="alert error">
            <?php echo esc($error); ?>
        </div>

    <?php endif; ?>

    <form method="POST" action="login.php">

        <input
            type="hidden"
            name="csrf_token"
            value="<?php echo esc(csrf_token()); ?>"
        >

        <label>
            Email Address

            <input
                type="email"
                name="email"
                value="<?php echo esc($email); ?>"
                required
                autocomplete="email"
            >
        </label>

        <label>
            Password

            <input
                type="password"
                name="password"
                required
                autocomplete="current-password"
            >
        </label>

        <button class="btn" type="submit">
            Login
        </button>

    </form>

    <p class="small">
        Don't have an account?
        <a href="register.php">Create Account</a>
    </p>

</section>

</main>

</body>
</html>