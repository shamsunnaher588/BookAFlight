<?php
// BookAFlight - Local Project Checker
// Read-only. Does not modify database records.
// Remove before publishing the website.

require_once __DIR__ . '/db.php';

require_login();

$auth = $pdo->prepare(
    'SELECT role FROM users WHERE id = ? LIMIT 1'
);

$auth->execute([
    (int)$_SESSION['user_id']
]);

if ($auth->fetchColumn() !== 'admin') {
    http_response_code(403);
    exit('Admin access required.');
}

function checkEsc($value): string {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

$checks = [];

function putCheck(
    string $type,
    string $name,
    bool $ok,
    string $detail
): void {
    global $checks;

    $checks[] = compact(
        'type',
        'name',
        'ok',
        'detail'
    );
}

// Check PHP Files
$expectedFiles = [
    'index.php' => 'Homepage',
    'results.php' => 'Flight Results',
    'booking.php' => 'Seat Selection',
    'my-bookings.php' => 'Saved Plans',
    'journeys.php' => 'Multi-Leg Journeys',
    'ticket.php' => 'Ticket Preview',
    'route-map.php' => 'Route Map',
    'support.php' => 'Customer Chat',
    'support-api.php' => 'Chat Backend',
    'support-admin.php' => 'Admin Support',
    'login.php' => 'Login',
    'register.php' => 'Registration',
    'dashboard.php' => 'Dashboard',
    'payment.php' => 'Sandbox Payment Page'
];

foreach ($expectedFiles as $file => $label) {

    putCheck(
        'File',
        $label,
        is_file(__DIR__ . '/' . $file),
        $file
    );
}

// Check MySQL Tables
$tables = [
    'users' => 'Users',
    'bookings' => 'Flight Plans',
    'booking_passengers' => 'Passengers',
    'chat_conversations' => 'Chat Conversations',
    'chat_messages' => 'Chat Messages',
    'chatbot_faq' => 'Chatbot FAQ',
    'chat_agents' => 'Agent Availability',
    'payment_transactions' => 'Payment Transactions'
];

foreach ($tables as $table => $label) {

    $query = $pdo->prepare(
        'SELECT COUNT(*)
         FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE()
         AND TABLE_NAME = ?'
    );

    $query->execute([$table]);

    putCheck(
        'Database',
        $label,
        (bool)$query->fetchColumn(),
        $table
    );
}

// Check Multi-City/Round-Trip DB Columns
$columns = [
    'journey_group',
    'journey_type',
    'journey_leg'
];

foreach ($columns as $column) {

    $query = $pdo->prepare(
        'SELECT COUNT(*)
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
         AND TABLE_NAME = ?
         AND COLUMN_NAME = ?'
    );

    $query->execute([
        'bookings',
        $column
    ]);

    putCheck(
        'Database',
        'Multi-Leg: ' . $column,
        (bool)$query->fetchColumn(),
        'bookings.' . $column
    );
}

// Check PHP Environment
putCheck(
    'PHP',
    'PHP 8.1 or later',
    PHP_VERSION_ID >= 80100,
    PHP_VERSION
);

putCheck(
    'PHP',
    'PDO MySQL Extension',
    extension_loaded('pdo_mysql'),
    'Database connection'
);

putCheck(
    'PHP',
    'FileInfo Extension',
    extension_loaded('fileinfo'),
    'Attachment checking'
);

// Check Private Upload Folder
$uploads = dirname(__DIR__, 2)
    . DIRECTORY_SEPARATOR
    . 'bookaflight-chat-uploads';

putCheck(
    'Storage',
    'Chat Upload Directory',
    is_dir($uploads),
    'Private folder outside htdocs'
);

putCheck(
    'Storage',
    'Upload Directory Writable',
    is_dir($uploads) && is_writable($uploads),
    'Required for attachments'
);

$total = count($checks);

$passed = count(
    array_filter(
        $checks,
        fn($item) => $item['ok']
    )
);
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1">

<title>BookAFlight - Project Check</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: #f3f7fc;
    color: #132b4d;
    font: 15px Arial, sans-serif;
}

header {
    background: #082858;
    color: white;
    padding: 20px 6%;
}

header a {
    color: white;
}

main {
    max-width: 1050px;
    margin: 25px auto;
    padding: 0 15px;
}

.panel {
    background: white;
    border-radius: 12px;
    padding: 18px;
    margin: 17px 0;
    box-shadow: 0 4px 16px #0000000b;
}

table {
    border-collapse: collapse;
    width: 100%;
}

th,
td {
    border-bottom: 1px solid #e4eaf3;
    text-align: left;
    padding: 10px;
}

th {
    background: #eff5fc;
}

.ok {
    color: #147643;
    font-weight: bold;
}

.bad {
    color: #ae3333;
    font-weight: bold;
}

a {
    color: #075d9d;
}

li {
    margin: 10px 0;
}

.notice {
    background: #fff5da;
    padding: 12px;
    border-radius: 8px;
    line-height: 1.5;
}

@media(max-width:600px) {
    table {
        font-size: 12px;
    }

    th,
    td {
        padding: 6px;
    }
}
</style>
</head>

<body>

<header>
    <strong>
        ✈ BookAFlight — Local Test Center
    </strong>

    · <a href="index.php">Home</a>
</header>

<main>

<h1>Website Readiness Check</h1>

<div class="notice">

    This page checks required files,
    database tables and PHP extensions.

    It does not prove that every feature works.

    BookAFlight currently uses illustrative
    flight and seat data.

    Delete project-check.php before publishing.

</div>

<section class="panel">

    <h2>
        Automatic Checks:
        <?php echo $passed; ?>
        /
        <?php echo $total; ?>
    </h2>

    <table>

        <thead>
        <tr>
            <th>Type</th>
            <th>Feature</th>
            <th>Result</th>
            <th>Detail</th>
        </tr>
        </thead>

        <tbody>

        <?php foreach ($checks as $check): ?>

        <tr>

            <td>
                <?php echo checkEsc(
                    $check['type']
                ); ?>
            </td>

            <td>
                <?php echo checkEsc(
                    $check['name']
                ); ?>
            </td>

            <td class="<?php
                echo $check['ok'] ? 'ok' : 'bad';
            ?>">

                <?php echo $check['ok']
                    ? 'Found'
                    : 'Check Needed'; ?>

            </td>

            <td>
                <?php echo checkEsc(
                    $check['detail']
                ); ?>
            </td>

        </tr>

        <?php endforeach; ?>

        </tbody>
    </table>

</section>

<section class="panel">

    <h2>Manual Feature Testing</h2>

    <ol>

        <li>
            <a href="index.php">Flight Search</a>
            — Test One-Way, Round-Trip
            and Multi-City.
        </li>

        <li>
            <a href="journeys.php">My Journeys</a>
            — Save two flights and
            verify the journey group.
        </li>

        <li>
            <a href="support.php">Support Chat</a>
            — Test Quick Replies, End Chat,
            Rating and TXT Transcript.
        </li>

        <li>
            <a href="support-admin.php">
                Admin Support
            </a>
            — Test Online Status,
            Conversation Search and FAQ Manager.
        </li>

        <li>
            <a href="payment.php">
                Payment Page
            </a>
            — Sandbox only.
            Real payment verification is required.
        </li>

        <li>
            Flight Route Map
            — Search DAC to DXB and check
            the map in Flight Results.
        </li>

        <li>
            Email Transcript
            — Requires configured
            SMTP or mail delivery.
        </li>

    </ol>

</section>

</main>
</body>
</html>