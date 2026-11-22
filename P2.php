<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

require_login();

$currentUser = current_user();

// Admin-only check on the server (403 for members or non-admins)
if (!is_array($currentUser) || ($currentUser['role'] ?? '') !== 'admin') {
    http_response_code(403);
    require_once __DIR__ . '/includes/header.php';
    ?>
    <main class="container page-main">
        <div class="card error-card" role="alert">
            <h1>403 Forbidden</h1>
            <p>You do not have permission to access the Users management page.</p>
            <p><a class="btn btn-primary" href="index.php">Back to Tickets</a></p>
        </div>
    </main>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$errors = [
    'firstname' => '',
    'lastname'  => '',
    'email'     => '',
    'password'  => '',
    'role'      => '',
    'general'   => '',
];

$formValues = [
    'firstname' => '',
    'lastname'  => '',
    'email'     => '',
    'role'      => 'member',
];

$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf(is_string($submittedToken) ? $submittedToken : null)) {
        http_response_code(403);
        $errors['general'] = 'Invalid security token. Please refresh the page and try again.';
    } else {
        $formValues['firstname'] = trim((string)($_POST['firstname'] ?? ''));
        $formValues['lastname']  = trim((string)($_POST['lastname'] ?? ''));
        $formValues['email']     = trim((string)($_POST['email'] ?? ''));
        $rawPassword             = (string)($_POST['password'] ?? '');
        $roleInput               = (string)($_POST['role'] ?? 'member');
        $formValues['role']      = in_array($roleInput, ['admin', 'member'], true) ? $roleInput : 'member';

        // Validate first name
        if ($formValues['firstname'] === '') {
            $errors['firstname'] = 'First name is required.';
        } elseif (mb_strlen($formValues['firstname']) > 50) {
            $errors['firstname'] = 'First name must be 50 characters or fewer.';
        }

        // Validate last name
        if ($formValues['lastname'] === '') {
            $errors['lastname'] = 'Last name is required.';
        } elseif (mb_strlen($formValues['lastname']) > 50) {
            $errors['lastname'] = 'Last name must be 50 characters or fewer.';
        }

        // Validate email format & length
        if ($formValues['email'] === '') {
            $errors['email'] = 'Email address is required.';
        } elseif (mb_strlen($formValues['email']) > 255 || !filter_var($formValues['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
        }

        // Validate password: at least 8 chars, at least 1 letter and 1 number
        if ($rawPassword === '') {
            $errors['password'] = 'Password is required.';
        } elseif (
            mb_strlen($rawPassword) < 8
            || !preg_match('/[A-Za-z]/', $rawPassword)
            || !preg_match('/\d/', $rawPassword)
        ) {
            $errors['password'] = 'Password must be at least 8 characters long and include at least one letter and one number.';
        }

        // Validate role
        if (!in_array($roleInput, ['admin', 'member'], true)) {
            $errors['role'] = 'Please select a valid role.';
        }

        // Check duplicate email using prepared statement
        if ($errors['email'] === '') {
            $checkStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
            $checkStmt->execute([':email' => $formValues['email']]);
            if ($checkStmt->fetch()) {
                $errors['email'] = 'An account with that email address already exists.';
            }
        }

        $hasErrors = false;
        foreach ($errors as $msg) {
            if ($msg !== '') {
                $hasErrors = true;
                break;
            }
        }

        if (!$hasErrors) {
            $passwordHash = password_hash($rawPassword, PASSWORD_DEFAULT);

            $insertStmt = $pdo->prepare(
                'INSERT INTO users (firstname, lastname, email, password, role)
                 VALUES (:firstname, :lastname, :email, :password, :role)'
            );
            $insertStmt->execute([
                ':firstname' => $formValues['firstname'],
                ':lastname'  => $formValues['lastname'],
                ':email'     => $formValues['email'],
                ':password'  => $passwordHash,
                ':role'      => $formValues['role'],
            ]);

            $successMessage = 'User added successfully.';
            $formValues = [
                'firstname' => '',
                'lastname'  => '',
                'email'     => '',
                'role'      => 'member',
            ];
        }
    }
}

// Fetch all users (never select or expose password hashes)
$usersStmt = $pdo->query(
    'SELECT id, firstname, lastname, email, role, created_at
     FROM users
     ORDER BY firstname ASC, lastname ASC, id ASC'
);
$users = $usersStmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
<main class="container page-main">
    <div class="page-header">
        <div>
            <h1>Users</h1>
            <p class="page-subtitle">Manage staff accounts and access roles.</p>
        </div>
    </div>

    <?php if ($successMessage !== ''): ?>
        <div class="alert alert-success" role="status">
            <?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if ($errors['general'] !== ''): ?>
        <div class="alert alert-error" role="alert">
            <?= htmlspecialchars($errors['general'], ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="users-layout">
        <section class="card table-card" aria-label="Users list">
            <div class="table-responsive">
                <table class="data-table users-table">
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Email</th>
                            <th scope="col">Role</th>
                            <th scope="col">Added</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $account): ?>
                            <?php
                            $fullName = trim((string)$account['firstname'] . ' ' . (string)$account['lastname']);
                            $roleLabel = ($account['role'] === 'admin') ? 'Admin' : 'Member';
                            $addedTimestamp = strtotime((string)$account['created_at']);
                            $addedFormatted = $addedTimestamp !== false
                                ? date('j M Y', $addedTimestamp)
                                : (string)$account['created_at'];
                            ?>
                            <tr>
                                <td data-label="Name">
                                    <span class="user-name"><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                                <td data-label="Email">
                                    <span class="user-email"><?= htmlspecialchars((string)$account['email'], ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                                <td data-label="Role">
                                    <span class="role-badge role-<?= htmlspecialchars((string)$account['role'], ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td data-label="Added">
                                    <?= htmlspecialchars($addedFormatted, ENT_QUOTES, 'UTF-8') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card form-card" aria-labelledby="add-user-heading">
            <h2 id="add-user-heading" class="card-title">Add a user</h2>

            <form method="post" action="users.php" novalidate class="stack-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">

                <div class="form-row-2">
                    <div class="form-group">
                        <label for="firstname">First name</label>
                        <input
                            type="text"
                            id="firstname"
                            name="firstname"
                            maxlength="50"
                            value="<?= htmlspecialchars($formValues['firstname'], ENT_QUOTES, 'UTF-8') ?>"
                            class="<?= $errors['firstname'] !== '' ? 'input-error' : '' ?>"
                            required
                        >
                        <?php if ($errors['firstname'] !== ''): ?>
                            <p class="field-error" role="alert"><?= htmlspecialchars($errors['firstname'], ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="lastname">Last name</label>
                        <input
                            type="text"
                            id="lastname"
                            name="lastname"
                            maxlength="50"
                            value="<?= htmlspecialchars($formValues['lastname'], ENT_QUOTES, 'UTF-8') ?>"
                            class="<?= $errors['lastname'] !== '' ? 'input-error' : '' ?>"
                            required
                        >
                        <?php if ($errors['lastname'] !== ''): ?>
                            <p class="field-error" role="alert"><?= htmlspecialchars($errors['lastname'], ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        maxlength="255"
                        value="<?= htmlspecialchars($formValues['email'], ENT_QUOTES, 'UTF-8') ?>"
                        class="<?= $errors['email'] !== '' ? 'input-error' : '' ?>"
                        required
                    >
                    <?php if ($errors['email'] !== ''): ?>
                        <p class="field-error" role="alert"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        autocomplete="new-password"
                        class="<?= $errors['password'] !== '' ? 'input-error' : '' ?>"
                        required
                    >
                    <p class="field-hint">At least 8 characters, with a letter and a number.</p>
                    <?php if ($errors['password'] !== ''): ?>
                        <p class="field-error" role="alert"><?= htmlspecialchars($errors['password'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="role">Role</label>
                    <select
                        id="role"
                        name="role"
                        class="<?= $errors['role'] !== '' ? 'input-error' : '' ?>"
                    >
                        <option value="member" <?= $formValues['role'] === 'member' ? 'selected' : '' ?>>Member</option>
                        <option value="admin" <?= $formValues['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                    <?php if ($errors['role'] !== ''): ?>
                        <p class="field-error" role="alert"><?= htmlspecialchars($errors['role'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Add user</button>
                </div>
            </form>
        </section>
    </div>
</main>
<?php
require_once __DIR__ . '/includes/footer.php';