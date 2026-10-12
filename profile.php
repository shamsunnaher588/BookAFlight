<?php
/** BookAFlight - Profile details + image upload (PHP 8 / PDO) */
require_once __DIR__ . '/db.php';
require_login();

header('Cache-Control: private, no-store, max-age=0');
header('X-Content-Type-Options: nosniff');

function pfEsc($value): string {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

function pfText(string $key): string {
    return is_string($_POST[$key] ?? null)
        ? trim($_POST[$key]) : '';
}

function pfLength(string $text): int {
    return preg_match_all('/./us', $text, $matches) ?: 0;
}

function pfSafePhoto(?string $file): bool {
    return is_string($file) &&
        (bool)preg_match(
            '/\A[a-f0-9]{32}\.(?:jpg|png|webp)\z/D',
            $file
        );
}

function pfDeletePhoto(?string $file, string $folder): void {
    if (pfSafePhoto($file)) {
        $path = $folder . DIRECTORY_SEPARATOR . $file;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

/* Current logged-in user */

$userId = (int)($_SESSION['user_id'] ?? 0);

$photoFolder = __DIR__ . '/uploads/profile-pictures';
$photoBaseUrl = 'uploads/profile-pictures/';

$errors = [];
$email = '';

$profile = [
    'display_name' => '',
    'phone' => '',
    'city' => '',
    'country' => '',
    'bio' => '',
    'avatar_file' => null
];

if ($userId < 1) {
    http_response_code(403);
    exit('Please log in again.');
}

/* Load account and profile */

try {
    $s = $pdo->prepare(
        'SELECT email FROM users WHERE id = ? LIMIT 1'
    );

    $s->execute([$userId]);

    $email = (string)($s->fetchColumn() ?: '');

    $s = $pdo->prepare(
        'SELECT display_name, phone, city, country,
                bio, avatar_file
         FROM user_profiles
         WHERE user_id = ?
         LIMIT 1'
    );

    $s->execute([$userId]);

    $row = $s->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $profile = array_replace($profile, $row);
    }

} catch (PDOException $e) {
    error_log(
        'BookAFlight profile load: ' . $e->getMessage()
    );

    $errors[] =
        'Database setup is incomplete. Run the profile SQL first.';
}

/* Handle forms */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!valid_csrf($_POST['csrf_token'] ?? null)) {

        $errors[] =
            'Security token expired. Reload and try again.';

    } else {

        $action = pfText('action');

        /* ==================================
           UPDATE PERSONAL DETAILS
        ================================== */

        if ($action === 'save_profile') {

            $fields = [
                'display_name',
                'phone',
                'city',
                'country',
                'bio'
            ];

            foreach ($fields as $field) {
                $profile[$field] = pfText($field);
            }

            if (
                pfLength($profile['display_name']) < 2 ||
                pfLength($profile['display_name']) > 100
            ) {
                $errors[] =
                    'Display name must be 2 to 100 characters.';
            }

            foreach ([
                'city' => 100,
                'country' => 100,
                'bio' => 400
            ] as $field => $max) {

                if (pfLength($profile[$field]) > $max) {
                    $errors[] =
                        ucfirst($field) . ' is too long.';
                }
            }

            foreach ($fields as $field) {

                if (preg_match(
                    '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u',
                    $profile[$field]
                )) {
                    $errors[] =
                        'Invalid characters in ' . $field . '.';
                }
            }

            if (
                $profile['phone'] !== '' &&
                !preg_match(
                    '/\A\+?[0-9()\s-]{7,25}\z/D',
                    $profile['phone']
                )
            ) {
                $errors[] = 'Enter a valid phone number.';
            }

            if (!$errors) {

                try {

                    $s = $pdo->prepare(
                        'INSERT INTO user_profiles
                        (
                            user_id,
                            display_name,
                            phone,
                            city,
                            country,
                            bio
                        )
                        VALUES (?, ?, ?, ?, ?, ?)

                        ON DUPLICATE KEY UPDATE
                            display_name = VALUES(display_name),
                            phone = VALUES(phone),
                            city = VALUES(city),
                            country = VALUES(country),
                            bio = VALUES(bio)'
                    );

                    $s->execute([
                        $userId,
                        $profile['display_name'],
                        $profile['phone'],
                        $profile['city'],
                        $profile['country'],
                        $profile['bio']
                    ]);

                    header('Location: profile.php?updated=1');
                    exit;

                } catch (PDOException $e) {

                    error_log(
                        'BookAFlight profile save: ' .
                        $e->getMessage()
                    );

                    $errors[] =
                        'Could not save profile. Check database.';
                }
            }

        /* ==================================
           UPLOAD PROFILE PICTURE
        ================================== */

        } elseif ($action === 'upload_photo') {

            $file = $_FILES['avatar'] ?? null;

            $maxBytes = 3 * 1024 * 1024;

            $allowed = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp'
            ];

            if (
                !is_array($file) ||
                is_array($file['error'] ?? null) ||
                ($file['error'] ?? UPLOAD_ERR_NO_FILE)
                    !== UPLOAD_ERR_OK
            ) {

                $errors[] =
                    'Please select an image under 3 MB. ' .
                    'Check PHP upload settings if needed.';

            } elseif (
                ($file['size'] ?? 0) < 1 ||
                $file['size'] > $maxBytes ||
                !is_uploaded_file($file['tmp_name'] ?? '')
            ) {

                $errors[] =
                    'Image must be a valid upload no larger than 3 MB.';

            } else {

                $tmp = $file['tmp_name'];

                $finfo = new finfo(FILEINFO_MIME_TYPE);

                $mime = $finfo->file($tmp);

                $dimensions = @getimagesize($tmp);

                $width = (int)($dimensions[0] ?? 0);
                $height = (int)($dimensions[1] ?? 0);

                $detectedMime = $dimensions['mime'] ?? '';

                if (
                    !isset($allowed[$mime]) ||
                    $detectedMime !== $mime ||
                    $width < 80 ||
                    $height < 80 ||
                    $width > 8000 ||
                    $height > 8000
                ) {

                    $errors[] =
                        'Choose a valid JPG, PNG or WebP image ' .
                        '(at least 80 × 80 pixels).';
                }
            }

            /* Save uploaded image */

            if (!$errors) {

                $newFile =
                    bin2hex(random_bytes(16)) .
                    '.' . $allowed[$mime];

                $newPath =
                    $photoFolder .
                    DIRECTORY_SEPARATOR .
                    $newFile;

                if (
                    !is_dir($photoFolder) &&
                    !@mkdir($photoFolder, 0755, true)
                ) {

                    $errors[] =
                        'Could not create image folder. ' .
                        'Check folder permissions.';

                } elseif (!is_writable($photoFolder)) {

                    $errors[] =
                        'Image folder is not writable.';

                } elseif (!move_uploaded_file(
                    $tmp,
                    $newPath
                )) {

                    $errors[] =
                        'Image could not be saved. ' .
                        'Check PHP upload permissions.';

                } else {

                    @chmod($newPath, 0644);

                    try {

                        $s = $pdo->prepare(
                            'INSERT INTO user_profiles
                            (
                                user_id,
                                display_name,
                                avatar_file
                            )
                            VALUES (?, ?, ?)

                            ON DUPLICATE KEY UPDATE
                                avatar_file = VALUES(avatar_file)'
                        );

                        $s->execute([
                            $userId,
                            $profile['display_name'] ?: 'Traveler',
                            $newFile
                        ]);

                        /* Remove old photo after DB update */

                        pfDeletePhoto(
                            $profile['avatar_file'],
                            $photoFolder
                        );

                        header(
                            'Location: profile.php?photo=uploaded'
                        );

                        exit;

                    } catch (PDOException $e) {

                        @unlink($newPath);

                        error_log(
                            'BookAFlight photo save: ' .
                            $e->getMessage()
                        );

                        $errors[] =
                            'Could not save image information to database.';
                    }
                }
            }

        /* ==================================
           REMOVE PROFILE PICTURE
        ================================== */

        } elseif ($action === 'remove_photo') {

            try {

                $s = $pdo->prepare(
                    'UPDATE user_profiles
                     SET avatar_file = NULL
                     WHERE user_id = ?'
                );

                $s->execute([$userId]);

                pfDeletePhoto(
                    $profile['avatar_file'],
                    $photoFolder
                );

                header(
                    'Location: profile.php?photo=removed'
                );

                exit;

            } catch (PDOException $e) {

                error_log(
                    'BookAFlight photo remove: ' .
                    $e->getMessage()
                );

                $errors[] =
                    'Could not remove your profile photo.';
            }

        } else {

            $errors[] = 'Invalid form action.';
        }
    }
}

/* Get photo URL */

$avatarFile = pfSafePhoto($profile['avatar_file'])
    ? $profile['avatar_file']
    : '';

$avatarUrl = (
    $avatarFile !== '' &&
    is_file($photoFolder . '/' . $avatarFile)
)
    ? $photoBaseUrl . rawurlencode($avatarFile)
    : '';

/* Success feedback */

$feedback = '';

if (($_GET['updated'] ?? '') === '1') {
    $feedback = 'Profile information updated.';
}

if (($_GET['photo'] ?? '') === 'uploaded') {
    $feedback = 'Profile picture uploaded successfully.';
}

if (($_GET['photo'] ?? '') === 'removed') {
    $feedback = 'Profile picture removed.';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta
    name="viewport"
    content="width=device-width,initial-scale=1"
>

<title>My Profile | BookAFlight</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: #f3f7fc;
    color: #183454;
    font: 15px/1.6 Arial, sans-serif;
}

header {
    background: #082957;
    color: #fff;
    padding: 18px 5%;

    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
}

header a {
    color: #fff;
    text-decoration: none;
    margin-right: 13px;
}

header strong {
    font-size: 22px;
}

main {
    max-width: 1020px;
    margin: 28px auto;
    padding: 0 15px;
}

h1 {
    margin: 0;
}

.sub {
    margin: 5px 0 20px;
    color: #657992;
}

.layout {
    display: grid;
    grid-template-columns: 320px minmax(0,1fr);
    gap: 18px;
    align-items: start;
}

.card {
    background: #fff;
    padding: 23px;

    border: 1px solid #dde7f0;
    border-radius: 15px;

    box-shadow: 0 4px 17px #1233540a;
}

.card h2 {
    font-size: 21px;
    margin: 0 0 13px;
}

.avatar {
    width: 142px;
    height: 142px;

    margin: 5px auto 12px;
    border-radius: 50%;

    object-fit: cover;

    display: grid;
    place-items: center;

    background: linear-gradient(
        120deg,
        #1f82bb,
        #0a3267
    );

    color: white;
    font-size: 60px;

    border: 5px solid #edf5fc;
}

.center {
    text-align: center;
}

.muted {
    color: #6d8095;
    font-size: 13px;
}

[hidden] {
    display: none !important;
}

label {
    display: block;
    font-weight: 700;
    font-size: 13px;
    margin-bottom: 12px;
}

input,
textarea {
    width: 100%;
    padding: 11px;
    margin-top: 5px;

    font: inherit;
    border: 1px solid #b6cddd;
    border-radius: 9px;
    color: #183454;
}

input[readonly] {
    background: #eff4fa;
}

textarea {
    min-height: 100px;
    resize: vertical;
}

.grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 13px;
}

.full {
    grid-column: 1/-1;
}

button,
.btn {
    border: 0;
    background: #ffbf49;
    color: #123252;

    padding: 11px 16px;
    border-radius: 9px;

    font: 700 14px Arial;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
}

button:hover,
.btn:hover {
    filter: brightness(.96);
}

.remove {
    background: #ffebeb;
    color: #8a2020;
    margin-top: 9px;
}

.notice {
    border-radius: 9px;
    padding: 12px 15px;
    margin-bottom: 15px;
}

.ok {
    background: #e7f7ed;
    color: #196237;
}

.bad {
    background: #ffe9e9;
    color: #852929;
}

.links {
    display: flex;
    justify-content: center;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 16px;
}

.links a {
    color: #1267ae;
    text-decoration: none;
    font-size: 13px;
}

@media(max-width:730px) {

    .layout,
    .grid {
        grid-template-columns: 1fr;
    }

    .full {
        grid-column: 1;
    }

    .card {
        padding: 17px;
    }
}
</style>

</head>

<body>

<header>

    <strong>✈ BookAFlight</strong>

    <nav>
        <a href="index.php">Home</a>
        <a href="my-bookings.php">My Bookings</a>
        <a href="saved-passengers.php">Passengers</a>
    </nav>

</header>

<main>

    <h1>👤 My Profile</h1>

    <p class="sub">
        Edit your details and upload a profile picture.
    </p>

    <?php if ($feedback): ?>

        <div class="notice ok" role="status">
            <?= pfEsc($feedback) ?>
        </div>

    <?php endif; ?>

    <?php foreach ($errors as $error): ?>

        <div class="notice bad" role="alert">
            <?= pfEsc($error) ?>
        </div>

    <?php endforeach; ?>

    <div class="layout">

        <!-- PROFILE PHOTO SECTION -->

        <aside class="card center">

            <h2>Profile Picture</h2>

            <?php if ($avatarUrl): ?>

                <img
                    class="avatar"
                    id="avatarPreview"
                    src="<?= pfEsc($avatarUrl) ?>"
                    alt="Your profile picture"
                >

            <?php else: ?>

                <div
                    class="avatar"
                    id="avatarPlaceholder"
                    aria-label="No profile photo"
                >
                    👤
                </div>

                <img
                    class="avatar"
                    id="avatarPreview"
                    src=""
                    alt="Selected profile picture preview"
                    hidden
                >

            <?php endif; ?>

            <strong>
                <?= pfEsc(
                    $profile['display_name'] ?: 'Traveler'
                ) ?>
            </strong>

            <p class="muted">
                <?= pfEsc($email) ?>
            </p>

            <!-- PHOTO UPLOAD FORM -->

            <form
                action="profile.php"
                method="post"
                enctype="multipart/form-data"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= pfEsc(csrf_token()) ?>"
                >

                <input
                    type="hidden"
                    name="action"
                    value="upload_photo"
                >

                <label for="avatar">
                    Choose JPG, PNG or WebP (max 3 MB)
                </label>

                <input
                    id="avatar"
                    name="avatar"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    required
                >

                <button
                    type="submit"
                    style="margin-top:12px"
                >
                    📷 Upload Photo
                </button>

            </form>

            <!-- REMOVE PHOTO -->

            <?php if ($avatarUrl): ?>

                <form
                    action="profile.php"
                    method="post"
                    onsubmit="return confirm('Remove profile picture?');"
                >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= pfEsc(csrf_token()) ?>"
                    >

                    <input
                        type="hidden"
                        name="action"
                        value="remove_photo"
                    >

                    <button
                        class="remove"
                        type="submit"
                    >
                        Remove Photo
                    </button>

                </form>

            <?php endif; ?>

            <div class="links">

                <a href="my-bookings.php">
                    🎫 My Bookings
                </a>

                <a href="saved-passengers.php">
                    👥 Saved Passengers
                </a>

            </div>

        </aside>

        <!-- PROFILE DETAILS SECTION -->

        <section class="card">

            <h2>Edit Personal Information</h2>

            <form
                action="profile.php"
                method="post"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= pfEsc(csrf_token()) ?>"
                >

                <input
                    type="hidden"
                    name="action"
                    value="save_profile"
                >

                <div class="grid">

                    <label class="full">
                        Display Name *

                        <input
                            name="display_name"
                            type="text"
                            minlength="2"
                            maxlength="100"
                            required
                            value="<?= pfEsc($profile['display_name']) ?>"
                        >
                    </label>

                    <label class="full">
                        Registered Email (read only)

                        <input
                            type="email"
                            readonly
                            value="<?= pfEsc($email) ?>"
                        >
                    </label>

                    <label>
                        Phone Number

                        <input
                            type="tel"
                            name="phone"
                            maxlength="25"
                            value="<?= pfEsc($profile['phone']) ?>"
                        >
                    </label>

                    <label>
                        City

                        <input
                            type="text"
                            name="city"
                            maxlength="100"
                            value="<?= pfEsc($profile['city']) ?>"
                        >
                    </label>

                    <label class="full">
                        Country

                        <input
                            type="text"
                            name="country"
                            maxlength="100"
                            value="<?= pfEsc($profile['country']) ?>"
                        >
                    </label>

                    <label class="full">
                        About Me

                        <textarea
                            name="bio"
                            maxlength="400"
                        ><?= pfEsc($profile['bio']) ?></textarea>
                    </label>

                </div>

                <button type="submit">
                    💾 Save Profile Changes
                </button>

            </form>

        </section>

    </div>

</main>

<!-- INSTANT PHOTO PREVIEW -->

<script>
(() => {
    const input = document.getElementById('avatar');
    const img = document.getElementById('avatarPreview');

    if (!input || !img) return;

    let temporaryUrl = null;

    input.addEventListener('change', () => {

        const file = input.files?.[0];

        if (temporaryUrl) {
            URL.revokeObjectURL(temporaryUrl);
        }

        if (!file) return;

        if (file.size > 3 * 1024 * 1024) {

            alert('Choose an image under 3 MB.');

            input.value = '';
            return;
        }

        temporaryUrl = URL.createObjectURL(file);

        img.src = temporaryUrl;
        img.hidden = false;

        const placeholder = document.getElementById(
            'avatarPlaceholder'
        );

        if (placeholder) {
            placeholder.hidden = true;
        }

    });

    window.addEventListener('pagehide', () => {

        if (temporaryUrl) {
            URL.revokeObjectURL(temporaryUrl);
        }

    });

})();
</script>

</body>
</html>