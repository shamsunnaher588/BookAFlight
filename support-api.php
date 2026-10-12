<?php
require_once __DIR__ . '/db.php';

header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

function out(array $data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );
    exit;
}

function fail(string $message, int $status = 400): never {
    out(['error' => $message], $status);
}

function txt($value, int $max = 2000): string {
    return is_string($value)
        ? trim(substr($value, 0, $max + 1))
        : '';
}

function conversation(
    int $id,
    int $user,
    bool $admin
): array {
    global $pdo;

    $s = $pdo->prepare(
        'SELECT * FROM chat_conversations
         WHERE conv_id = ? LIMIT 1'
    );

    $s->execute([$id]);
    $c = $s->fetch(PDO::FETCH_ASSOC);

    if (!$c || (!$admin && (int)$c['user_id'] !== $user)) {
        fail('Conversation not found.', 404);
    }

    return $c;
}

function currentConversation(
    int $user,
    bool $create = false
): ?array {
    global $pdo;

    $s = $pdo->prepare(
        "SELECT * FROM chat_conversations
         WHERE user_id = ? AND status <> 'closed'
         ORDER BY conv_id DESC LIMIT 1"
    );

    $s->execute([$user]);
    $c = $s->fetch(PDO::FETCH_ASSOC);

    if (!$c && $create) {
        $s = $pdo->prepare(
            "INSERT INTO chat_conversations
             (user_id, subject)
             VALUES (?, 'Website support')"
        );

        $s->execute([$user]);

        $c = conversation(
            (int)$pdo->lastInsertId(),
            $user,
            false
        );
    }

    return $c ?: null;
}

function addMessage(
    int $conv,
    string $sender,
    string $message,
    ?array $file = null
): void {
    global $pdo;

    $s = $pdo->prepare(
        'INSERT INTO chat_messages
        (conv_id, sender_type, message_text,
         attachment_path, attachment_name, attachment_mime)
         VALUES (?, ?, ?, ?, ?, ?)'
    );

    $s->execute([
        $conv,
        $sender,
        $message,
        $file['path'] ?? null,
        $file['name'] ?? null,
        $file['mime'] ?? null
    ]);

    $pdo->prepare(
        'UPDATE chat_conversations
         SET updated_at = NOW() WHERE conv_id = ?'
    )->execute([$conv]);
}

function onlineCount(): int {
    global $pdo;

    return (int)$pdo->query(
        "SELECT COUNT(*)
         FROM chat_agents a
         JOIN users u ON u.id = a.user_id
         WHERE u.role = 'admin'
           AND a.is_online = 1
           AND a.last_seen >= NOW() - INTERVAL 40 SECOND"
    )->fetchColumn();
}

function faqAnswer(string $question): string {
    global $pdo;

    $s = $pdo->query(
        'SELECT question_keywords, answer_text
         FROM chatbot_faq
         WHERE is_active = 1
         ORDER BY priority DESC, faq_id ASC'
    );

    $lower = function_exists('mb_strtolower')
        ? mb_strtolower($question, 'UTF-8')
        : strtolower($question);

    foreach ($s as $faq) {
        foreach (explode(',', $faq['question_keywords']) as $word) {

            $key = trim($word);

            if ($key === '') continue;

            $key = function_exists('mb_strtolower')
                ? mb_strtolower($key, 'UTF-8')
                : strtolower($key);

            if (str_contains($lower, $key)) {
                return $faq['answer_text'];
            }
        }
    }

    return 'Thanks for your message. Try a quick question ' .
        'or select Speak to Agent for human support.';
}

function allowPost(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        fail('POST required.', 405);
    }

    if (!valid_csrf($_POST['csrf_token'] ?? null)) {
        fail('Security token expired. Refresh the page.', 403);
    }
}

function uploadFile(): ?array {
    $file = $_FILES['attachment'] ?? null;

    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        fail('Upload failed. Try a smaller file.', 422);
    }

    if ($file['size'] > 2097152 || $file['size'] <= 0) {
        fail('Maximum file size is 2 MB.', 422);
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        fail('Invalid upload.', 422);
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))
        ->file($file['tmp_name']);

    $allowed = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf'
    ];

    if (!isset($allowed[$mime])) {
        fail('Only PNG, JPG, WEBP and PDF allowed.', 422);
    }

    // Private directory outside htdocs.
    $directory = dirname(__DIR__, 2)
        . DIRECTORY_SEPARATOR
        . 'bookaflight-chat-uploads';

    if (
        !is_dir($directory) &&
        !mkdir($directory, 0700, true)
    ) {
        fail('Upload storage unavailable.', 500);
    }

    $filename = bin2hex(random_bytes(20))
        . '.' . $allowed[$mime];

    if (!move_uploaded_file(
        $file['tmp_name'],
        $directory . DIRECTORY_SEPARATOR . $filename
    )) {
        fail('Could not save attachment.', 500);
    }

    $original = basename(
        str_replace('\\', '/', (string)$file['name'])
    );

    $original = substr(
        preg_replace('/[\x00-\x1F\x7F]/', '', $original),
        0,
        160
    );

    return [
        'path' => $filename,
        'name' => $original ?: 'attachment.' . $allowed[$mime],
        'mime' => $mime
    ];
}

function transcriptText(int $conv): string {
    global $pdo;

    $s = $pdo->prepare(
        'SELECT sender_type, message_text,
                attachment_name, sent_at
         FROM chat_messages
         WHERE conv_id = ?
         ORDER BY msg_id ASC LIMIT 500'
    );

    $s->execute([$conv]);

    $lines = [
        'BookAFlight Support Transcript',
        'Local conversation #' . $conv,
        ''
    ];

    foreach ($s as $m) {
        $lines[] = $m['sent_at']
            . ' [' . strtoupper($m['sender_type']) . '] '
            . $m['message_text']
            . (
                $m['attachment_name']
                    ? ' [Attachment: ' . $m['attachment_name'] . ']'
                    : ''
            );
    }

    return implode("\r\n", $lines) . "\r\n";
}

// Logged-in user and permissions
$uid = (int)($_SESSION['user_id'] ?? 0);
$admin = false;
$email = '';

if ($uid) {
    $s = $pdo->prepare(
        'SELECT role, email FROM users WHERE id = ? LIMIT 1'
    );

    $s->execute([$uid]);
    $user = $s->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $uid = 0;
    } else {
        $admin = $user['role'] === 'admin';
        $email = (string)($user['email'] ?? '');
    }
}

$action = (string)(
    $_POST['action']
    ?? $_GET['action']
    ?? 'bootstrap'
);

try {

    // Initial widget data
    if (
        $action === 'bootstrap' &&
        $_SERVER['REQUEST_METHOD'] === 'GET'
    ) {
        $c = ($uid && !$admin)
            ? currentConversation($uid)
            : null;

        $faqs = $pdo->query(
            'SELECT category FROM chatbot_faq
             WHERE is_active = 1
             ORDER BY priority DESC, faq_id LIMIT 7'
        )->fetchAll(PDO::FETCH_COLUMN);

        $isOnline = false;

        if ($admin) {
            $s = $pdo->prepare(
                'SELECT is_online FROM chat_agents WHERE user_id = ?'
            );
            $s->execute([$uid]);
            $isOnline = (bool)$s->fetchColumn();
        }

        out([
            'logged_in' => (bool)$uid,
            'is_admin' => $admin,
            'csrf' => csrf_token(),
            'agents_online' => onlineCount(),
            'agent_online' => $isOnline,
            'conversation' => $c ? [
                'conv_id' => (int)$c['conv_id'],
                'status' => $c['status'],
                'needs_agent' => (bool)$c['needs_agent'],
                'rating' => $c['rating']
            ] : null,
            'quick_replies' => $faqs
        ]);
    }

    // Authenticated private attachment download
    if (
        $action === 'file' &&
        $_SERVER['REQUEST_METHOD'] === 'GET'
    ) {
        if (!$uid) fail('Login required.', 401);

        $id = (int)($_GET['id'] ?? 0);

        $s = $pdo->prepare(
            'SELECT m.attachment_path,
                    m.attachment_name,
                    m.attachment_mime,
                    c.user_id
             FROM chat_messages m
             JOIN chat_conversations c
               ON c.conv_id = m.conv_id
             WHERE m.msg_id = ?'
        );

        $s->execute([$id]);
        $m = $s->fetch(PDO::FETCH_ASSOC);

        if (
            !$m ||
            (!$admin && (int)$m['user_id'] !== $uid) ||
            !$m['attachment_path']
        ) {
            fail('Attachment not found.', 404);
        }

        $path = dirname(__DIR__, 2)
            . DIRECTORY_SEPARATOR
            . 'bookaflight-chat-uploads'
            . DIRECTORY_SEPARATOR
            . basename($m['attachment_path']);

        if (!is_file($path)) {
            fail('Attachment unavailable.', 404);
        }

        header('Content-Type: ' . $m['attachment_mime']);
        header(
            'Content-Disposition: attachment; filename="attachment.'
            . pathinfo($m['attachment_path'], PATHINFO_EXTENSION)
            . '"'
        );
        header('Content-Length: ' . filesize($path));

        readfile($path);
        exit;
    }

    // TXT transcript download
    if (
        $action === 'transcript' &&
        $_SERVER['REQUEST_METHOD'] === 'GET'
    ) {
        if (!$uid) fail('Login required.', 401);

        $c = conversation(
            (int)($_GET['conv_id'] ?? 0),
            $uid,
            $admin
        );

        header('Content-Type: text/plain; charset=utf-8');
        header(
            'Content-Disposition: attachment; filename="bookaflight-chat-'
            . $c['conv_id'] . '.txt"'
        );

        echo transcriptText((int)$c['conv_id']);
        exit;
    }

    // Retrieve recent messages
    if (
        $action === 'messages' &&
        $_SERVER['REQUEST_METHOD'] === 'GET'
    ) {
        if (!$uid) fail('Login required.', 401);

        $c = $admin
            ? conversation((int)($_GET['conv_id'] ?? 0), $uid, true)
            : currentConversation($uid);

        if (!$c) {
            out([
                'messages' => [],
                'conversation' => null
            ]);
        }

        if ($admin) {
            $pdo->prepare(
                "UPDATE chat_messages SET is_read = 1
                 WHERE conv_id = ? AND sender_type = 'user'"
            )->execute([(int)$c['conv_id']]);
        }

        $s = $pdo->prepare(
            'SELECT msg_id, sender_type, message_text,
                    attachment_name, sent_at
             FROM (
                SELECT msg_id, sender_type, message_text,
                       attachment_name, sent_at
                FROM chat_messages
                WHERE conv_id = ?
                ORDER BY msg_id DESC LIMIT 100
             ) x ORDER BY msg_id ASC'
        );

        $s->execute([(int)$c['conv_id']]);

        out([
            'messages' => $s->fetchAll(PDO::FETCH_ASSOC),
            'conversation' => [
                'conv_id' => (int)$c['conv_id'],
                'status' => $c['status'],
                'needs_agent' => (bool)$c['needs_agent'],
                'rating' => $c['rating']
            ]
        ]);
    }

    // Admin conversation history and search
    if (
        $action === 'threads' &&
        $_SERVER['REQUEST_METHOD'] === 'GET'
    ) {
        if (!$admin) fail('Admin only.', 403);

        $q = txt($_GET['q'] ?? '', 80);
        $status = txt($_GET['status'] ?? '', 10);
        $date = txt($_GET['date'] ?? '', 10);

        $sql =
            "SELECT c.conv_id, c.user_id, c.subject,
                    c.status, c.needs_agent,
                    c.rating, c.updated_at, u.full_name,
                    (
                      SELECT COUNT(*)
                      FROM chat_messages m
                      WHERE m.conv_id = c.conv_id
                        AND m.sender_type = 'user'
                        AND m.is_read = 0
                    ) AS unread
             FROM chat_conversations c
             JOIN users u ON u.id = c.user_id
             WHERE 1 = 1";

        $params = [];

        if ($q !== '') {
            $sql .=
                ' AND (
                    u.full_name LIKE ?
                    OR c.subject LIKE ?
                    OR CAST(c.conv_id AS CHAR) LIKE ?
                    OR EXISTS (
                        SELECT 1 FROM chat_messages sm
                        WHERE sm.conv_id = c.conv_id
                        AND sm.message_text LIKE ?
                    )
                )';

            $params = array_fill(0, 4, '%' . $q . '%');
        }

        if (in_array(
            $status,
            ['active', 'pending', 'closed'],
            true
        )) {
            $sql .= ' AND c.status = ?';
            $params[] = $status;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $sql .= ' AND DATE(c.created_at) = ?';
            $params[] = $date;
        }

        $sql .= ' ORDER BY c.updated_at DESC LIMIT 100';

        $s = $pdo->prepare($sql);
        $s->execute($params);

        out([
            'threads' => $s->fetchAll(PDO::FETCH_ASSOC),
            'agents_online' => onlineCount()
        ]);
    }

    // Admin FAQ list
    if (
        $action === 'faqs' &&
        $_SERVER['REQUEST_METHOD'] === 'GET'
    ) {
        if (!$admin) fail('Admin only.', 403);

        out([
            'faqs' => $pdo->query(
                'SELECT * FROM chatbot_faq
                 ORDER BY priority DESC, faq_id ASC'
            )->fetchAll(PDO::FETCH_ASSOC)
        ]);
    }

    allowPost();

    // Public FAQ chatbot
    if ($action === 'ask') {
        $now = time();

        if (($_SESSION['baf_ask_start'] ?? 0) < $now - 60) {
            $_SESSION['baf_ask_start'] = $now;
            $_SESSION['baf_ask_count'] = 0;
        }

        $_SESSION['baf_ask_count'] =
            (int)($_SESSION['baf_ask_count'] ?? 0) + 1;

        if ($_SESSION['baf_ask_count'] > 20) {
            fail('Too many questions. Try again in a minute.', 429);
        }

        $question = txt($_POST['message'] ?? '', 300);

        if ($question === '') {
            fail('Please type a question.', 422);
        }

        out(['answer' => faqAnswer($question)]);
    }

    if (!$uid) {
        fail('Login required for live chat.', 401);
    }

    // User or Admin sends message and optional attachment
    if ($action === 'send') {

        $message = txt($_POST['message'] ?? '', 2000);

        $hasFile = isset($_FILES['attachment']) &&
            ($_FILES['attachment']['error'] ?? UPLOAD_ERR_NO_FILE)
                !== UPLOAD_ERR_NO_FILE;

        if (
            ($message === '' && !$hasFile) ||
            strlen($message) > 2000
        ) {
            fail('Enter a message or attach a file.', 422);
        }

        $c = $admin
            ? conversation((int)($_POST['conv_id'] ?? 0), $uid, true)
            : currentConversation($uid, true);

        if ($c['status'] === 'closed') {
            fail('This conversation is closed.', 409);
        }

        if ($admin && !$c['needs_agent']) {
            fail('Customer has not requested an agent.', 409);
        }

        $s = $pdo->prepare(
            'SELECT COUNT(*) FROM chat_messages
             WHERE conv_id = ? AND sender_type = ?
             AND sent_at >= NOW() - INTERVAL 60 SECOND'
        );

        $s->execute([
            (int)$c['conv_id'],
            $admin ? 'admin' : 'user'
        ]);

        if ((int)$s->fetchColumn() > 15) {
            fail('Too many messages. Wait a minute.', 429);
        }

        $file = uploadFile();

        try {
            $pdo->beginTransaction();

            addMessage(
                (int)$c['conv_id'],
                $admin ? 'admin' : 'user',
                $message,
                $file
            );

            if ($admin) {
                $pdo->prepare(
                    "UPDATE chat_conversations
                     SET status = 'active' WHERE conv_id = ?"
                )->execute([(int)$c['conv_id']]);

            } elseif (!$c['needs_agent']) {

                addMessage(
                    (int)$c['conv_id'],
                    'bot',
                    faqAnswer($message)
                );
            }

            $pdo->commit();

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($file) {
                @unlink(
                    dirname(__DIR__, 2)
                    . DIRECTORY_SEPARATOR
                    . 'bookaflight-chat-uploads'
                    . DIRECTORY_SEPARATOR
                    . $file['path']
                );
            }

            throw $e;
        }

        out(['ok' => true]);
    }

    // Transfer to human agent
    if ($action === 'connect') {

        if ($admin) fail('Customer only.', 403);

        $c = currentConversation($uid, true);

        $pdo->prepare(
            "UPDATE chat_conversations
             SET needs_agent = 1,
                 status = 'pending',
                 updated_at = NOW()
             WHERE conv_id = ?"
        )->execute([(int)$c['conv_id']]);

        $message = onlineCount() > 0
            ? 'An agent has been notified. Leave your question here.'
            : 'Agents are offline. Leave a message for later.';

        addMessage(
            (int)$c['conv_id'],
            'bot',
            $message
        );

        out(['ok' => true]);
    }

    // Close chat
    if ($action === 'end') {

        $c = $admin
            ? conversation((int)($_POST['conv_id'] ?? 0), $uid, true)
            : currentConversation($uid);

        if (!$c) fail('Conversation not found.', 404);

        $pdo->prepare(
            "UPDATE chat_conversations
             SET status = 'closed',
                 closed_at = NOW(),
                 updated_at = NOW()
             WHERE conv_id = ?"
        )->execute([(int)$c['conv_id']]);

        out(['ok' => true]);
    }

    // 1-5 star rating
    if ($action === 'rate') {

        if ($admin) fail('Customer only.', 403);

        $id = (int)($_POST['conv_id'] ?? 0);
        $rating = (int)($_POST['rating'] ?? 0);

        $c = conversation($id, $uid, false);

        if (
            $c['status'] !== 'closed' ||
            $rating < 1 ||
            $rating > 5
        ) {
            fail('Close chat, then select 1-5 stars.', 422);
        }

        $pdo->prepare(
            'UPDATE chat_conversations
             SET rating = ? WHERE conv_id = ?'
        )->execute([$rating, $id]);

        out(['ok' => true]);
    }

    // Email transcript: requires configured server mail
    if ($action === 'email') {

        if ($admin) fail('Customer only.', 403);

        $c = conversation(
            (int)($_POST['conv_id'] ?? 0),
            $uid,
            false
        );

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            fail('No valid email on your account.', 422);
        }

        if (
            isset($_SESSION['chat_last_mail']) &&
            time() - (int)$_SESSION['chat_last_mail'] < 120
        ) {
            fail('Wait two minutes before emailing again.', 429);
        }

        $body = transcriptText((int)$c['conv_id']);

        $sent = @mail(
            $email,
            'Your BookAFlight Support Transcript',
            $body,
            "Content-Type: text/plain; charset=UTF-8\r\n"
        );

        if (!$sent) {
            fail(
                'Email not configured. Use Download Transcript instead.',
                503
            );
        }

        $_SESSION['chat_last_mail'] = time();

        out([
            'ok' => true,
            'message' => 'Email accepted by the mail server; delivery is not guaranteed.'
        ]);
    }

    // Admin availability / heartbeat
    if ($action === 'presence') {

        if (!$admin) fail('Admin only.', 403);

        $mode = txt($_POST['mode'] ?? '', 20);

        if ($mode === 'on' || $mode === 'off') {

            $online = $mode === 'on' ? 1 : 0;

            $pdo->prepare(
                'INSERT INTO chat_agents
                 (user_id, is_online, last_seen)
                 VALUES (?, ?, NOW())
                 ON DUPLICATE KEY UPDATE
                 is_online = VALUES(is_online),
                 last_seen = NOW()'
            )->execute([$uid, $online]);

        } elseif ($mode === 'heartbeat') {

            $pdo->prepare(
                'UPDATE chat_agents
                 SET last_seen = NOW()
                 WHERE user_id = ? AND is_online = 1'
            )->execute([$uid]);

        } else {
            fail('Invalid availability value.', 422);
        }

        out(['agents_online' => onlineCount()]);
    }

    // Admin FAQ Manager
    if ($action === 'faq_save') {

        if (!$admin) fail('Admin only.', 403);

        $id = (int)($_POST['faq_id'] ?? 0);
        $category = txt($_POST['category'] ?? '', 70);
        $keywords = txt($_POST['question_keywords'] ?? '', 255);
        $answer = txt($_POST['answer_text'] ?? '', 2000);
        $priority = max(
            0,
            min(999, (int)($_POST['priority'] ?? 10))
        );

        if (
            !$category || !$keywords || !$answer ||
            strlen($answer) > 2000
        ) {
            fail('Complete FAQ information.', 422);
        }

        if ($id > 0) {

            $pdo->prepare(
                'UPDATE chatbot_faq
                 SET category = ?, question_keywords = ?,
                     answer_text = ?, priority = ?
                 WHERE faq_id = ?'
            )->execute([
                $category,
                $keywords,
                $answer,
                $priority,
                $id
            ]);

        } else {

            $pdo->prepare(
                'INSERT INTO chatbot_faq
                 (category,question_keywords,answer_text,priority)
                 VALUES (?,?,?,?)'
            )->execute([
                $category,
                $keywords,
                $answer,
                $priority
            ]);
        }

        out(['ok' => true]);
    }

    if ($action === 'faq_toggle') {

        if (!$admin) fail('Admin only.', 403);

        $pdo->prepare(
            'UPDATE chatbot_faq
             SET is_active = 1 - is_active
             WHERE faq_id = ?'
        )->execute([(int)($_POST['faq_id'] ?? 0)]);

        out(['ok' => true]);
    }

    if ($action === 'faq_delete') {

        if (!$admin) fail('Admin only.', 403);

        $pdo->prepare(
            'DELETE FROM chatbot_faq WHERE faq_id = ?'
        )->execute([(int)($_POST['faq_id'] ?? 0)]);

        out(['ok' => true]);
    }

    fail('Unknown action.', 404);

} catch (Throwable $e) {

    error_log('Support API: ' . $e->getMessage());

    fail(
        'Support service is temporarily unavailable.',
        500
    );
}