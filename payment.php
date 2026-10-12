<?php
require_once __DIR__ . '/db.php';

// Payment provider callbacks must NOT require the customer's login session.
$event = $_GET['event'] ?? '';
$configPath = dirname(__DIR__, 2) . '/bookaflight-ssl.php';
if (!is_file($configPath)) {
    http_response_code(500);
    exit('Set up C:\\xampp\\bookaflight-ssl.php first.');
}
$settings = require $configPath;
$storeId = trim((string)($settings['store_id'] ?? ''));
$storePass = trim((string)($settings['store_password'] ?? ''));
$baseUrl = rtrim((string)($settings['base_url'] ?? ''), '/');
if ($storeId === '' || $storePass === '' ||
    str_contains($storeId, 'PASTE_') || str_contains($storePass, 'PASTE_')) {
    exit('Add your private sandbox Store ID and Store Password to C:\\xampp\\bookaflight-ssl.php.');
}
if (!preg_match('~^https?://~i', $baseUrl)) {
    exit('Invalid base_url in the private configuration.');
}

function sslRequest(string $url, ?array $data = null): array {
    if (!function_exists('curl_init')) {
        throw new RuntimeException('Enable the PHP cURL extension in XAMPP php.ini.');
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);
    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($response === false || $httpCode < 200 || $httpCode >= 300) {
        throw new RuntimeException('SSLCommerz API request failed.');
    }
    $json = json_decode($response, true);
    if (!is_array($json)) {
        throw new RuntimeException('Unexpected response from payment gateway.');
    }
    return $json;
}

function safeCheckoutUrl(string $url): bool {
    $host = parse_url($url, PHP_URL_HOST);
    return parse_url($url, PHP_URL_SCHEME) === 'https' &&
        in_array(strtolower((string)$host), [
            'sandbox-gw.sslcommerz.com',
            'sandbox.sslcommerz.com',
        ], true);
}

// Success / fail / cancel / IPN are provider-initiated callbacks.
if (in_array($event, ['success', 'fail', 'cancel', 'ipn'], true)) {
    $message = 'Payment has not been verified. Please check your payment history.';
    $valid = false;
    $tranId = $_POST['tran_id'] ?? '';
    $valId = $_POST['val_id'] ?? '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
        is_string($tranId) && is_string($valId) &&
        preg_match('/^[A-Za-z0-9_-]{5,30}$/', $tranId) &&
        strlen($valId) >= 5 && strlen($valId) <= 80 &&
        ($event === 'success' || $event === 'ipn')) {
        $stmt = $pdo->prepare('SELECT * FROM payment_transactions WHERE tran_id = ? LIMIT 1');
        $stmt->execute([$tranId]);
        $attempt = $stmt->fetch();
        if ($attempt) {
            try {
                // Verify independently; never accept POST status as proof of payment.
                $query = http_build_query([
                    'val_id' => $valId,
                    'store_id' => $storeId,
                    'store_passwd' => $storePass,
                    'format' => 'json',
                ]);
                $verify = sslRequest(
                    'https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php?' . $query
                );
                $goodStatus = in_array($verify['status'] ?? '', ['VALID', 'VALIDATED'], true);
                $goodTran = hash_equals((string)$attempt['tran_id'], (string)($verify['tran_id'] ?? ''));
                $goodAmount = isset($verify['amount']) && is_numeric($verify['amount']) &&
                    abs((float)$verify['amount'] - (float)$attempt['amount']) < 0.005;
                $goodCurrency = strtoupper((string)($verify['currency'] ?? '')) === 'BDT';
                $safeRisk = (string)($verify['risk_level'] ?? '1') === '0';
                $valid = $goodStatus && $goodTran && $goodAmount && $goodCurrency;
                if ($valid) {
                    $status = $safeRisk ? 'verified' : 'risky';
                    $update = $pdo->prepare(
                        "UPDATE payment_transactions SET status = ?, val_id = ?,
                         verified_at = COALESCE(verified_at, NOW())
                         WHERE id = ? AND (val_id IS NULL OR val_id = ?)"
                    );
                    $update->execute([$status, $valId, $attempt['id'], $valId]);
                    if ($update->rowCount() === 0 &&
                        !($attempt['val_id'] === $valId && $attempt['status'] === $status)) {
                        $valid = false;
                    } else {
                        $message = $safeRisk
                            ? 'Sandbox payment verified successfully.'
                            : 'Payment verified but flagged for manual review.';
                    }
                }
            } catch (Throwable $e) {
                error_log('SSLCommerz verification error: ' . $e->getMessage());
            }
        }
    }

    if ($event === 'ipn') {
        http_response_code(200);
        exit($valid ? 'OK' : 'Not verified');
    }

    if ($event === 'fail' || $event === 'cancel') {
        $message = 'Payment was not completed or was cancelled.';
    }
    ?>
    <!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Payment Result | BookAFlight</title>
    <style>body{font-family:Arial,sans-serif;background:#f4f7fd;margin:0;color:#153457}.panel{max-width:650px;margin:70px auto;background:#fff;border-radius:18px;padding:35px;box-shadow:0 8px 30px #00204a12}a{color:#0b68b2}</style></head><body><main class="panel">
    <h1>BookAFlight · Sandbox Payment</h1><p><?= esc($message) ?></p>
    <p><strong>Note:</strong> A sandbox payment is not a live charge or an airline ticket.</p>
    <a href="payment.php">Back to Payment History</a>
    </main></body></html>
    <?php
    exit;
}

require_login();
$userId = (int)$_SESSION['user_id'];
$error = '';
$selectedId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Start payment from a POST, using amount from the database only.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!valid_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Security check failed. Reload and try again.';
    } else {
        $bookingId = filter_var($_POST['booking_id'] ?? null, FILTER_VALIDATE_INT);
        $stmt = $pdo->prepare(
            "SELECT b.id, b.preview_total, b.plan_status, u.full_name, u.email
             FROM bookings b JOIN users u ON u.id = b.user_id
             WHERE b.id = ? AND b.user_id = ? LIMIT 1"
        );
        $stmt->execute([$bookingId ?: 0, $userId]);
        $booking = $stmt->fetch();
        $phone = is_string($_POST['phone'] ?? null) ? trim($_POST['phone']) : '';
        $address = is_string($_POST['address'] ?? null) ? trim($_POST['address']) : '';
        $city = is_string($_POST['city'] ?? null) ? trim($_POST['city']) : '';
        $postcode = is_string($_POST['postcode'] ?? null) ? trim($_POST['postcode']) : '';

        if (!$booking || $booking['plan_status'] === 'cancelled') {
            $error = 'Select an active saved flight plan.';
        } elseif ((float)$booking['preview_total'] < 10 || (float)$booking['preview_total'] > 500000) {
            $error = 'The test payment amount is outside the gateway limits.';
        } elseif (!preg_match('/^\+?[0-9 -]{7,20}$/', $phone) ||
                  strlen($address) < 4 || strlen($address) > 50 ||
                  strlen($city) < 2 || strlen($city) > 50 ||
                  !preg_match('/^[A-Za-z0-9 -]{3,12}$/', $postcode)) {
            $error = 'Enter a valid phone, address, city and postcode.';
        } else {
            $tranId = 'BF' . $booking['id'] . '_' . strtoupper(bin2hex(random_bytes(7)));
            $amount = number_format((float)$booking['preview_total'], 2, '.', '');
            $stmt = $pdo->prepare(
                'INSERT INTO payment_transactions
                 (booking_id, user_id, tran_id, amount) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$booking['id'], $userId, $tranId, $amount]);

            $callbackBase = $baseUrl . '/payment.php?event=';
            $post = [
                'store_id' => $storeId,
                'store_passwd' => $storePass,
                'total_amount' => $amount,
                'currency' => 'BDT',
                'tran_id' => $tranId,
                'success_url' => $callbackBase . 'success',
                'fail_url' => $callbackBase . 'fail',
                'cancel_url' => $callbackBase . 'cancel',
                'cus_name' => substr($booking['full_name'], 0, 50),
                'cus_email' => substr($booking['email'], 0, 50),
                'cus_add1' => $address,
                'cus_city' => $city,
                'cus_postcode' => $postcode,
                'cus_country' => 'Bangladesh',
                'cus_phone' => $phone,
                'shipping_method' => 'NO',
                'product_name' => 'Sandbox checkout integration test',
                'product_category' => 'Software Testing',
                'product_profile' => 'general',
                'value_a' => (string)$booking['id'],
            ];
            // IPN needs a publicly reachable HTTPS address, not localhost.
            if (str_starts_with(strtolower($baseUrl), 'https://') &&
                !str_contains(strtolower($baseUrl), 'localhost')) {
                $post['ipn_url'] = $callbackBase . 'ipn';
            }

            try {
                $answer = sslRequest(
                    'https://sandbox-gw.sslcommerz.com/gwprocess/v4/api.php',
                    $post
                );
                $gatewayUrl = (string)($answer['GatewayPageURL'] ?? '');
                if (($answer['status'] ?? '') !== 'SUCCESS' ||
                    !safeCheckoutUrl($gatewayUrl)) {
                    $error = 'Gateway rejected the request. Check sandbox credentials.';
                } else {
                    $update = $pdo->prepare(
                        'UPDATE payment_transactions SET gateway_session = ? WHERE tran_id = ?'
                    );
                    $update->execute([(string)($answer['sessionkey'] ?? ''), $tranId]);
                    header('Location: ' . $gatewayUrl, true, 303);
                    exit;
                }
            } catch (Throwable $e) {
                error_log('SSLCommerz initiation error: ' . $e->getMessage());
                $error = 'Could not connect to sandbox gateway. Check PHP cURL and internet.';
            }
        }
    }
}

$stmt = $pdo->prepare(
    'SELECT b.id, b.booking_reference, b.from_city, b.to_city,
            b.depart_date, b.preview_total, b.plan_status,
            (SELECT status FROM payment_transactions t WHERE t.booking_id=b.id
             ORDER BY t.id DESC LIMIT 1) AS last_payment_status
     FROM bookings b WHERE b.user_id = ? ORDER BY b.id DESC LIMIT 30'
);
$stmt->execute([$userId]);
$bookings = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Payment Checkout | BookAFlight</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#f1f6fc;color:#142d4c;font-family:Arial,sans-serif}header{background:#082858;color:white;padding:22px 7%;font-size:23px;font-weight:bold}main{max-width:900px;margin:30px auto;padding:0 15px}.panel{background:#fff;border-radius:16px;padding:25px;margin:17px 0;box-shadow:0 5px 25px #092c5b13}.muted{color:#687e95;line-height:1.6}.note{background:#fff4d8;padding:13px;border-radius:9px}.error{background:#ffe3e3;color:#842828;padding:13px;border-radius:9px}.list{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;border-top:1px solid #e5ebf3;padding:15px 0}.list:first-of-type{border-top:0}a{color:#0874bb}.btn{background:#ffbd43;color:#1f3151;border:0;padding:12px 17px;border-radius:9px;font-weight:bold;cursor:pointer;display:inline-block;text-decoration:none}label{display:block;font-size:14px;font-weight:bold;margin-top:13px}input{display:block;width:100%;padding:12px;border:1px solid #cbd5e1;border-radius:8px;margin-top:7px;font:inherit}.grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}@media(max-width:600px){.grid{grid-template-columns:1fr}}
</style></head><body><header>✈ BookAFlight · Payment</header><main>
<h1>Secure Checkout</h1>
<p class="note">SSLCommerz Sandbox testing. This is not a real charge or an airline-issued ticket.</p>
<?php if ($error): ?><p class="error"><?= esc($error) ?></p><?php endif; ?>
<?php if (!$bookings): ?><section class="panel">No saved bookings yet. <a href="index.php">Search Flights</a></section><?php endif; ?>
<section class="panel"><h2>My Saved Bookings</h2>
<?php foreach ($bookings as $b): ?>
<div class="list"><div><strong><?= esc($b['from_city']) ?> → <?= esc($b['to_city']) ?></strong>
<p class="muted"><?= esc($b['depart_date']) ?> · Ref: <?= esc($b['booking_reference'] ?: $b['id']) ?><br>Latest sandbox test: <?= esc($b['last_payment_status'] ?: 'Not started') ?></p>
<strong>৳<?= number_format((float)$b['preview_total'], 2) ?></strong></div>
<?php if ($b['plan_status'] !== 'cancelled'): ?>
<a class="btn" href="payment.php?id=<?= (int)$b['id'] ?>#checkout">Test Payment →</a>
<?php else: ?><span class="muted">Cancelled plan</span><?php endif; ?></div>
<?php endforeach; ?></section>
<?php foreach ($bookings as $b): ?>
<?php if ((int)$selectedId === (int)$b['id'] && $b['plan_status'] !== 'cancelled'): ?>
<section class="panel" id="checkout"><h2>Payment for Booking #<?= (int)$b['id'] ?></h2><p><strong>৳<?= number_format((float)$b['preview_total'], 2) ?></strong></p>
<form method="post" action="payment.php?id=<?= (int)$b['id'] ?>">
<input type="hidden" name="csrf_token" value="<?= esc(csrf_token()) ?>">
<input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
<div class="grid"><label>Test Customer Phone<input type="tel" name="phone" placeholder="017XXXXXXXX" required maxlength="20"></label>
<label>City<input name="city" value="Dhaka" required maxlength="50"></label>
<label>Address<input name="address" value="Test address, Dhaka" required maxlength="50"></label>
<label>Postcode<input name="postcode" value="1207" required maxlength="12"></label></div>
<p class="muted">Only use sandbox test methods on the next gateway page.</p><button class="btn" type="submit">Continue to SSLCommerz →</button>
</form></section>
<?php endif; ?>
<?php endforeach; ?>
<p><a href="my-bookings.php">← My Bookings</a></p>
</main></body></html>