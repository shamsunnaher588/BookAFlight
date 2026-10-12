<?php
require_once __DIR__ . '/db.php';
require_login();

function agbEsc($v): string {
    return htmlspecialchars(
        (string)$v,
        ENT_QUOTES,
        'UTF-8'
    );
}

$auth = $pdo->prepare(
    'SELECT role FROM users
     WHERE id = ? LIMIT 1'
);

$auth->execute([
    (int)$_SESSION['user_id']
]);

if ($auth->fetchColumn() !== 'admin') {
    http_response_code(403);
    exit('Admin access required.');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!valid_csrf(
        $_POST['csrf_token'] ?? null
    )) {
        $errors[] =
            'Session expired. Please refresh.';
    }

    $id = (int)($_POST['request_id'] ?? 0);

    $status = is_string(
        $_POST['status'] ?? null
    ) ? $_POST['status'] : '';

    $adminNote = is_string(
        $_POST['admin_note'] ?? null
    ) ? trim($_POST['admin_note']) : '';

    $quoteText = is_string(
        $_POST['estimated_total'] ?? null
    ) ? trim($_POST['estimated_total']) : '';

    $estimate = null;

    if (!in_array(
        $status,
        [
            'pending',
            'reviewing',
            'quoted',
            'closed'
        ],
        true
    )) {
        $errors[] = 'Invalid status.';
    }

    if ($id < 1) {
        $errors[] = 'Invalid request.';
    }

    if (strlen($adminNote) > 2000) {
        $errors[] = 'Admin note is too long.';
    }

    if ($quoteText !== '') {

        if (
            !preg_match(
                '/^\d{1,10}(?:\.\d{1,2})?$/',
                $quoteText
            ) ||
            (float)$quoteText <= 0
        ) {
            $errors[] =
                'Enter a positive indicative quote ' .
                'up to 2 decimal places.';

        } else {
            $estimate = $quoteText;
        }
    }

    if (
        $status === 'quoted' &&
        $estimate === null
    ) {
        $errors[] =
            'Enter an indicative total before ' .
            'marking as quoted.';
    }

    if (!$errors) {

        $s = $pdo->prepare(
            'UPDATE group_booking_requests
             SET
                status = ?,
                estimated_total = ?,
                admin_note = ?
             WHERE id = ?'
        );

        $s->execute([
            $status,
            $estimate,
            $adminNote,
            $id
        ]);

        if ($s->rowCount() === 0) {

            $exists = $pdo->prepare(
                'SELECT id FROM group_booking_requests
                 WHERE id = ?'
            );

            $exists->execute([$id]);

            if (!$exists->fetchColumn()) {
                $errors[] = 'Request not found.';
            }
        }

        if (!$errors) {

            header(
                'Location: admin-group-bookings.php?id=' .
                $id . '&saved=1'
            );

            exit;
        }
    }
}

$filter = is_string(
    $_GET['status'] ?? null
) ? $_GET['status'] : '';

$search = is_string(
    $_GET['search'] ?? null
) ? trim($_GET['search']) : '';

$sql =
    'SELECT
        id,
        request_ref,
        contact_name,
        passenger_count,
        status,
        created_at
     FROM group_booking_requests
     WHERE 1=1';

$params = [];

if (in_array(
    $filter,
    ['pending','reviewing','quoted','closed'],
    true
)) {
    $sql .= ' AND status = ?';
    $params[] = $filter;
}

if ($search !== '') {

    $sql .=
        ' AND (
            request_ref LIKE ?
            OR contact_name LIKE ?
            OR contact_email LIKE ?
        )';

    $params = array_merge(
        $params,
        array_fill(
            0,
            3,
            '%' . substr($search, 0, 80) . '%'
        )
    );
}

$sql .= ' ORDER BY id DESC LIMIT 100';

$listStmt = $pdo->prepare($sql);
$listStmt->execute($params);

$requests = $listStmt->fetchAll(PDO::FETCH_ASSOC);

$selectedId = (int)(
    $_GET['id'] ??
    ($requests[0]['id'] ?? 0)
);

$current = null;
$legs = [];
$people = [];

if ($selectedId > 0) {

    $s = $pdo->prepare(
        'SELECT * FROM group_booking_requests
         WHERE id = ?'
    );

    $s->execute([$selectedId]);

    $current = $s->fetch(PDO::FETCH_ASSOC)
        ?: null;

    if ($current) {

        $s = $pdo->prepare(
            'SELECT
                leg_no,
                from_airport,
                to_airport,
                depart_date
             FROM group_booking_legs
             WHERE request_id = ?
             ORDER BY leg_no'
        );

        $s->execute([$selectedId]);

        $legs = $s->fetchAll(PDO::FETCH_ASSOC);

        $s = $pdo->prepare(
            'SELECT
                passenger_no,
                first_name,
                last_name
             FROM group_booking_passengers
             WHERE request_id = ?
             ORDER BY passenger_no'
        );

        $s->execute([$selectedId]);

        $people = $s->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<!doctype html>
<html lang="en">

<head>
<meta charset="utf-8">
<meta name="viewport"
      content="width=device-width,initial-scale=1">

<title>Admin Group Bookings | BookAFlight</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: #f3f7fc;
    color: #142b4a;
    font-family: Arial, sans-serif;
}

header {
    background: #082858;
    color: white;
    padding: 20px 5%;
    display: flex;
    gap: 20px;
}

header a {
    color: white;
}

main {
    max-width: 1200px;
    margin: 25px auto;
    padding: 0 14px;
}

.layout {
    display: grid;
    grid-template-columns: 320px 1fr;
    gap: 16px;
}

.card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 16px #0001;
    margin-bottom: 14px;
}

.item {
    display: block;
    background: #eef5ff;
    padding: 12px;
    border-radius: 8px;
    margin: 9px 0;
    text-decoration: none;
    color: #183c65;
    line-height: 1.5;
}

.item:hover {
    background: #d9eaff;
}

input,
select,
textarea {
    font: inherit;
    padding: 11px;
    border-radius: 8px;
    border: 1px solid #c9d7e9;
    width: 100%;
    margin: 7px 0 14px;
}

button {
    background: #ffbc43;
    border: 0;
    border-radius: 8px;
    padding: 12px 17px;
    font-weight: bold;
    cursor: pointer;
}

th,
td {
    text-align: left;
    padding: 9px;
    border-bottom: 1px solid #e3e9f2;
}

table {
    width: 100%;
    border-collapse: collapse;
}

.note {
    background: #fff5dc;
    padding: 12px;
    border-radius: 9px;
    line-height: 1.6;
}

.success {
    background: #e2f8ed;
    padding: 12px;
}

.error {
    background: #ffe6e6;
    padding: 12px;
}

@media(max-width:750px) {
    .layout {
        grid-template-columns: 1fr;
    }
}
</style>

</head>

<body>

<header>
    <strong>
        ✈ BookAFlight — Group Booking Admin
    </strong>

    <a href="admin.php">Main Admin</a>
    <a href="index.php">Home</a>
</header>

<main>

<h1>Group Booking Requests</h1>

<p class="note">
    Review requests and send an
    <strong>indicative quote</strong>.
    A quote/status change does not confirm
    an airline booking or collect payment.
</p>

<?php if (isset($_GET['saved'])): ?>

<p class="success">
    ✅ Request updated.
</p>

<?php endif; ?>

<?php if ($errors): ?>

<div class="error">

    <?php foreach ($errors as $e): ?>

    <p><?php echo agbEsc($e); ?></p>

    <?php endforeach; ?>

</div>

<?php endif; ?>

<div class="layout">

<aside class="card">

<h2>Requests</h2>

<form method="get">

    <input
        type="search"
        name="search"
        placeholder="Reference, name or email"
        value="<?php echo agbEsc($search); ?>"
    >

    <select name="status">

        <option value="">All statuses</option>

        <?php foreach (
            [
                'pending',
                'reviewing',
                'quoted',
                'closed'
            ] as $s
        ): ?>

        <option
            value="<?php echo $s; ?>"
            <?php echo $filter === $s
                ? 'selected' : ''; ?>
        >
            <?php echo ucfirst($s); ?>
        </option>

        <?php endforeach; ?>

    </select>

    <button type="submit">
        Search
    </button>

</form>

<?php if (!$requests): ?>

<p>No requests found.</p>

<?php endif; ?>

<?php foreach ($requests as $r): ?>

<a
    class="item"
    href="?id=<?php echo (int)$r['id']; ?>&status=<?php
        echo rawurlencode($filter);
    ?>&search=<?php
        echo rawurlencode($search);
    ?>"
>

    <strong>
        <?php echo agbEsc($r['request_ref']); ?>
    </strong>

    <?php echo agbEsc($r['contact_name']); ?>

    · <?php echo (int)$r['passenger_count']; ?>
    people

    Status:
    <?php echo agbEsc($r['status']); ?>

</a>

<?php endforeach; ?>

</aside>

<section class="card">

<?php if (!$current): ?>

    <h2>Select a request</h2>

<?php else: ?>

    <h2>
        <?php echo agbEsc(
            $current['request_ref']
        ); ?>
    </h2>

    <p>
        Submitted:
        <?php echo agbEsc(
            $current['created_at']
        ); ?>

        · <?php echo (int)$current['passenger_count']; ?>
        travelers

        · <?php echo agbEsc(
            $current['trip_type']
        ); ?>

        · <?php echo agbEsc(
            $current['cabin']
        ); ?>
    </p>

    <p>
        <strong>Contact:</strong>

        <?php echo agbEsc(
            $current['contact_name']
        ); ?>

        |

        <?php echo agbEsc(
            $current['contact_email']
        ); ?>

        |

        <?php echo agbEsc(
            $current['contact_phone']
        ); ?>
    </p>

    <h3>Routes</h3>

    <ul>

    <?php foreach ($legs as $leg): ?>

    <li>

        Flight <?php echo (int)$leg['leg_no']; ?>:

        <?php echo agbEsc(
            $leg['from_airport']
        ); ?>

        →

        <?php echo agbEsc(
            $leg['to_airport']
        ); ?>

        —

        <?php echo agbEsc(
            $leg['depart_date']
        ); ?>

    </li>

    <?php endforeach; ?>

    </ul>

    <h3>Travelers</h3>

    <div style="max-height:230px;overflow:auto">

    <table>

        <thead>

        <tr>
            <th>#</th>
            <th>First name</th>
            <th>Last name</th>
        </tr>

        </thead>

        <tbody>

        <?php foreach ($people as $person): ?>

        <tr>

            <td>
                <?php echo (int)$person['passenger_no']; ?>
            </td>

            <td>
                <?php echo agbEsc(
                    $person['first_name']
                ); ?>
            </td>

            <td>
                <?php echo agbEsc(
                    $person['last_name']
                ); ?>
            </td>

        </tr>

        <?php endforeach; ?>

        </tbody>

    </table>

    </div>

    <h3>Customer notes</h3>

    <p>
        <?php echo nl2br(
            agbEsc(
                $current['notes'] ?: 'None'
            )
        ); ?>
    </p>

    <form method="post">

        <input
            type="hidden"
            name="csrf_token"
            value="<?php echo agbEsc(
                csrf_token()
            ); ?>"
        >

        <input
            type="hidden"
            name="request_id"
            value="<?php echo (int)$current['id']; ?>"
        >

        <label>
            Status

            <select name="status">

            <?php foreach (
                [
                    'pending',
                    'reviewing',
                    'quoted',
                    'closed'
                ] as $s
            ): ?>

            <option
                value="<?php echo $s; ?>"
                <?php echo $current['status'] === $s
                    ? 'selected' : ''; ?>
            >
                <?php echo ucfirst($s); ?>
            </option>

            <?php endforeach; ?>

            </select>
        </label>

        <label>
            Indicative total (BDT)

            <input
                type="number"
                name="estimated_total"
                min="0.01"
                max="9999999999.99"
                step="0.01"
                value="<?php echo agbEsc(
                    $current['estimated_total'] ?? ''
                ); ?>"
                placeholder="Example: 250000.00"
            >
        </label>

        <label>
            Message visible to customer

            <textarea
                name="admin_note"
                rows="4"
                maxlength="2000"
                placeholder="We are reviewing your request..."
            ><?php echo agbEsc(
                $current['admin_note'] ?? ''
            ); ?></textarea>
        </label>

        <button type="submit">
            Save Request Update
        </button>

    </form>

<?php endif; ?>

</section>

</div>

</main>

</body>
</html>