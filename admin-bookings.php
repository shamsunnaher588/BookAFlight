<?php
require_once __DIR__ . '/db.php';
require_login();

// Check admin role directly from database
$check = $pdo->prepare(
    'SELECT role FROM users WHERE id = ? LIMIT 1'
);

$check->execute([(int)$_SESSION['user_id']]);

if ($check->fetchColumn() !== 'admin') {
    http_response_code(403);
    exit('Access denied. Administrators only.');
}

$allowed = ['saved', 'reviewing', 'cancelled'];

$labels = [
    'saved' => 'Saved Plan',
    'reviewing' => 'Under Review',
    'cancelled' => 'Cancelled'
];

// Update internal booking status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!valid_csrf($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Security check failed. Reload the page.');
    }

    $bookingId = filter_var(
        $_POST['booking_id'] ?? null,
        FILTER_VALIDATE_INT
    );

    $newStatus = $_POST['plan_status'] ?? null;

    if (
        !$bookingId ||
        !is_string($newStatus) ||
        !in_array($newStatus, $allowed, true)
    ) {
        http_response_code(400);
        exit('Invalid booking or status.');
    }

    $stmt = $pdo->prepare(
        'UPDATE bookings
         SET plan_status = ?
         WHERE id = ?'
    );

    $stmt->execute([$newStatus, $bookingId]);

    header('Location: admin-bookings.php?updated=1');
    exit;
}

// Search filters
$term = trim((string)($_GET['q'] ?? ''));
$term = substr($term, 0, 80);

$userTerm = trim((string)($_GET['userq'] ?? ''));
$userTerm = substr($userTerm, 0, 80);

// Statistics
$stats = $pdo->query(
    "SELECT COUNT(*) AS total,
        COALESCE(
            SUM(plan_status = 'reviewing'), 0
        ) AS reviewing,
        COALESCE(
            SUM(plan_status = 'cancelled'), 0
        ) AS cancelled,
        COALESCE(
            SUM(payment_status = 'paid'), 0
        ) AS paid
     FROM bookings"
)->fetch();

// Bookings Query
$sql = '
    SELECT
        b.id,
        b.booking_reference,
        b.airline,
        b.from_city,
        b.to_city,
        b.depart_date,
        b.passenger_count,
        b.preview_total,
        b.plan_status,
        b.payment_status,
        b.created_at,
        u.full_name,
        u.email
    FROM bookings b
    JOIN users u ON u.id = b.user_id
';

$params = [];

if ($term !== '') {

    $sql .= '
        WHERE b.booking_reference LIKE ?
           OR b.from_city LIKE ?
           OR b.to_city LIKE ?
           OR u.email LIKE ?
           OR u.full_name LIKE ?
    ';

    $params = array_fill(
        0, 5, '%' . $term . '%'
    );
}

$sql .= ' ORDER BY b.id DESC LIMIT 100';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$bookings = $stmt->fetchAll();

// User Search
$sqlUsers = '
    SELECT id, full_name, email, role, created_at
    FROM users
';

$userParams = [];

if ($userTerm !== '') {

    $sqlUsers .= '
        WHERE full_name LIKE ?
           OR email LIKE ?
    ';

    $userParams = [
        '%' . $userTerm . '%',
        '%' . $userTerm . '%'
    ];
}

$sqlUsers .= ' ORDER BY id DESC LIMIT 50';

$userStmt = $pdo->prepare($sqlUsers);
$userStmt->execute($userParams);

$users = $userStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport"
      content="width=device-width,initial-scale=1">

<title>Booking Management | BookAFlight</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f4f7fc;
    color: #18324f;
}

a {
    color: #075fa8;
    text-decoration: none;
}

header {
    background: #082858;
    color: #fff;
    padding: 22px 6%;
    display: flex;
    gap: 14px;
    justify-content: space-between;
    flex-wrap: wrap;
}

header a {
    color: #fff;
}

main {
    width: 94%;
    max-width: 1250px;
    margin: 32px auto;
}

h1 {
    margin-bottom: 8px;
}

.muted {
    color: #68788a;
    font-size: 14px;
    line-height: 1.6;
}

.metrics {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    margin: 22px 0;
}

.metric,
.panel {
    background: white;
    border: 1px solid #e2eaf4;
    border-radius: 14px;
    padding: 22px;
    box-shadow: 0 4px 16px #001d4009;
}

.metric strong {
    display: block;
    font-size: 29px;
    margin-top: 12px;
    color: #076ab5;
}

.panel {
    margin: 23px 0;
}

.notice {
    padding: 13px 16px;
    background: #e6f5e9;
    color: #215c32;
    border-radius: 9px;
}

.caution {
    padding: 14px;
    background: #fff4d9;
    border-radius: 9px;
    font-size: 14px;
    line-height: 1.5;
}

.search {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin: 17px 0;
}

.search input {
    padding: 12px;
    border: 1px solid #cfdbea;
    border-radius: 8px;
    flex: 1;
    min-width: 190px;
}

.btn {
    background: #ffbd46;
    border: 0;
    border-radius: 8px;
    padding: 11px 15px;
    font-weight: 700;
    cursor: pointer;
    color: #23354a;
}

.scroller {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 890px;
    font-size: 14px;
}

th,
td {
    text-align: left;
    border-bottom: 1px solid #e9eef5;
    padding: 13px 10px;
    vertical-align: middle;
}

th {
    background: #f7faff;
    color: #566c85;
}

.amount {
    white-space: nowrap;
    font-weight: 700;
    color: #075fa8;
}

.small {
    font-size: 12px;
    color: #6a7c92;
}

.status-form {
    display: flex;
    gap: 7px;
    align-items: center;
}

.status-form select {
    border: 1px solid #cbd7e5;
    border-radius: 7px;
    padding: 8px;
}

.status-form button {
    padding: 8px 10px;
}

.tag {
    display: inline-block;
    border-radius: 25px;
    padding: 6px 9px;
    background: #e7f1fe;
    color: #075fa8;
    font-size: 12px;
    white-space: nowrap;
}

@media(max-width:750px) {

    .metrics {
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }

    .metric,
    .panel {
        padding: 15px;
    }
}

@media(max-width:420px) {

    .metrics {
        grid-template-columns: 1fr;
    }
}
</style>
</head>

<body>

<header>

    <b>✈ BookAFlight · Admin Management</b>

    <nav>
        <a href="admin.php">Overview</a>
        ·
        <a href="dashboard.php">My Dashboard</a>
        ·
        <a href="index.php">Home</a>
    </nav>

</header>

<main>

<h1>Booking Management</h1>

<p class="muted">
    Manage saved flight plans,
    search users and review payment status.
</p>

<div class="caution">
    Internal booking references are not airline PNRs.
    Payment status <b>Paid</b> is read-only here
    and must be verified by an approved
    payment provider.
</div>

<?php if (isset($_GET['updated'])): ?>

<p class="notice">
    Plan status updated successfully.
</p>

<?php endif; ?>

<!-- Statistics -->

<section class="metrics">

    <div class="metric">
        <span>All Plans</span>

        <strong>
            <?php echo (int)$stats['total']; ?>
        </strong>
    </div>

    <div class="metric">
        <span>Under Review</span>

        <strong>
            <?php echo (int)$stats['reviewing']; ?>
        </strong>
    </div>

    <div class="metric">
        <span>Cancelled Plans</span>

        <strong>
            <?php echo (int)$stats['cancelled']; ?>
        </strong>
    </div>

    <div class="metric">
        <span>Verified Paid</span>

        <strong>
            <?php echo (int)$stats['paid']; ?>
        </strong>
    </div>

</section>

<!-- Booking Search -->

<section class="panel">

<h2>Find Saved Bookings</h2>

<form
    class="search"
    method="GET"
    action="admin-bookings.php"
>

    <input
        name="q"
        value="<?php echo esc($term); ?>"
        placeholder="Reference, customer, email or route"
        maxlength="80"
    >

    <button class="btn" type="submit">
        Search Bookings
    </button>

    <a href="admin-bookings.php">Clear</a>

</form>

<div class="scroller">

<table>

<thead>
<tr>
    <th>Reference</th>
    <th>Customer</th>
    <th>Route / Date</th>
    <th>Passengers</th>
    <th>Illustrative Fare</th>
    <th>Payment</th>
    <th>Plan Status</th>
</tr>
</thead>

<tbody>

<?php foreach ($bookings as $b): ?>

<tr>

    <td>
        <b>
            <?php
            echo esc(
                $b['booking_reference'] ?: 'Pending'
            );
            ?>
        </b>

        <div class="small">
            ID <?php echo (int)$b['id']; ?>
        </div>
    </td>

    <td>
        <?php echo esc($b['full_name']); ?>

        <div class="small">
            <?php echo esc($b['email']); ?>
        </div>
    </td>

    <td>
        <?php
        echo esc(
            $b['from_city'] . ' → ' . $b['to_city']
        );
        ?>

        <div class="small">
            <?php echo esc($b['depart_date']); ?>
            ·
            <?php echo esc($b['airline']); ?>
        </div>
    </td>

    <td>
        <?php echo (int)$b['passenger_count']; ?>
    </td>

    <td class="amount">
        ৳<?php
        echo number_format(
            (float)$b['preview_total']
        );
        ?>
    </td>

    <td>
        <span class="tag">
            <?php
            echo esc(
                ucwords(
                    str_replace(
                        '_',
                        ' ',
                        $b['payment_status']
                    )
                )
            );
            ?>
        </span>
    </td>

    <td>

        <form
            class="status-form"
            method="POST"
            action="admin-bookings.php"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo esc(csrf_token()); ?>"
            >

            <input
                type="hidden"
                name="booking_id"
                value="<?php echo (int)$b['id']; ?>"
            >

            <select
                name="plan_status"
                aria-label="Status for booking <?php
                    echo (int)$b['id'];
                ?>"
            >

            <?php foreach ($allowed as $status): ?>

                <option
                    value="<?php echo esc($status); ?>"
                    <?php
                    echo $b['plan_status'] === $status
                        ? 'selected'
                        : '';
                    ?>
                >
                    <?php
                    echo esc($labels[$status]);
                    ?>
                </option>

            <?php endforeach; ?>

            </select>

            <button class="btn" type="submit">
                Save
            </button>

        </form>

    </td>

</tr>

<?php endforeach; ?>

<?php if (!$bookings): ?>

<tr>
    <td colspan="7">
        No saved bookings found.
    </td>
</tr>

<?php endif; ?>

</tbody>
</table>

</div>

<p class="muted">
    Showing up to 100 recent matching records.
</p>

</section>

<!-- Users Search -->

<section class="panel">

<h2>Registered Users</h2>

<form
    class="search"
    method="GET"
    action="admin-bookings.php"
>

    <input
        name="userq"
        value="<?php echo esc($userTerm); ?>"
        placeholder="Search user name or email"
        maxlength="80"
    >

    <button class="btn" type="submit">
        Search Users
    </button>

</form>

<div class="scroller">

<table>

<thead>
<tr>
    <th>User ID</th>
    <th>Name</th>
    <th>Email</th>
    <th>Role</th>
    <th>Joined</th>
</tr>
</thead>

<tbody>

<?php foreach ($users as $user): ?>

<tr>

    <td>
        <?php echo (int)$user['id']; ?>
    </td>

    <td>
        <?php echo esc($user['full_name']); ?>
    </td>

    <td>
        <?php echo esc($user['email']); ?>
    </td>

    <td>
        <?php echo esc($user['role']); ?>
    </td>

    <td>
        <?php echo esc($user['created_at']); ?>
    </td>

</tr>

<?php endforeach; ?>

<?php if (!$users): ?>

<tr>
    <td colspan="5">
        No matching users.
    </td>
</tr>

<?php endif; ?>

</tbody>
</table>

</div>

</section>

</main>

</body>
</html>