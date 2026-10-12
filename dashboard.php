<?php
require_once __DIR__ . '/db.php';

require_login();

$stmt = $pdo->prepare(
    'SELECT full_name, email, phone, role, created_at
     FROM users
     WHERE id = ?
     LIMIT 1'
);

$stmt->execute([$_SESSION['user_id']]);

$user = $stmt->fetch();

if (!$user) {

    $_SESSION = [];

    session_destroy();

    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport"
      content="width=device-width, initial-scale=1">

<title>My Dashboard | BookAFlight</title>

<link rel="stylesheet" href="auth.css">
</head>

<body>

<header class="topbar">

    <a class="brand" href="index.php">
        ✈ <span>Book</span>AFlight
    </a>

    <form
        class="logout"
        method="POST"
        action="logout.php"
    >

        <input
            type="hidden"
            name="csrf_token"
            value="<?php echo esc(csrf_token()); ?>"
        >

        <button type="submit">
            Logout
        </button>

    </form>

</header>

<main class="dashboard-container">

    <h1>
        Welcome,
        <?php echo esc($user['full_name']); ?>!
    </h1>

    <p class="muted">
        Manage your BookAFlight profile.
    </p>

    <div class="dash-grid">

        <section class="panel">

            <h2>My Profile</h2>

            <p>
                <strong>Name:</strong>
                <?php echo esc($user['full_name']); ?>
            </p>

            <p>
                <strong>Email:</strong>
                <?php echo esc($user['email']); ?>
            </p>

            <p>
                <strong>Phone:</strong>
                <?php echo esc(
                    $user['phone'] ?: 'Not added'
                ); ?>
            </p>

            <p>
                <strong>Account:</strong>
                <?php echo esc(
                    ucfirst($user['role'])
                ); ?>
            </p>

        </section>

        <section class="panel">

            <h2>My Bookings</h2>

            <p class="muted">
                Your booking history will
                appear here after the booking
                database is connected.
            </p>

            <a
                class="btn link-btn"
                href="index.php"
            >
                Search Flights →
            </a>

        </section>

    </div>

</main>
<?php include_once __DIR__ . '/theme-toggle.php'; ?>
<?php include_once __DIR__ . '/responsive-layout.php'; ?>
</body>
</html>