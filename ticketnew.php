<?php

require_once __DIR__ . '/includes/bootstrap.php';


$title = '';
$description = '';
$priority = '';
$assigned_to = '';

$errors = [];
$success = '';


$stmt = $pdo->query(
    "SELECT id, firstname, lastname
     FROM users
     ORDER BY firstname, lastname"
);

$users = $stmt->fetchAll();


if (isset($_GET['created']) && $_GET['created'] === '1') {
    $success = "Ticket created successfully!";
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

   
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority = $_POST['priority'] ?? '';
    $assigned_to = $_POST['assigned_to'] ?? '';

    
    $submitted_token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($submitted_token) ||
        !hash_equals($_SESSION['csrf_token'], $submitted_token)
    ) {
        http_response_code(403);
        $errors['general'] = "Invalid security token. Please try again.";
    }

    
    if ($title === '') {

        $errors['title'] = "Please enter a ticket title.";

    } elseif (mb_strlen($title, 'UTF-8') > 150) {

        $errors['title'] = "The title cannot exceed 150 characters.";

    }

    if ($description === '') {

        $errors['description'] = "Please describe the problem.";

    }

  
    $valid_priorities = ['Low', 'Medium', 'High'];

    if (!in_array($priority, $valid_priorities, true)) {

        $errors['priority'] = "Please choose a valid priority.";

    }

    $assigned_user_id = null;

    if ($assigned_to !== '') {

        if (
            !is_string($assigned_to) ||
            !ctype_digit($assigned_to) ||
            (int) $assigned_to < 1
        ) {

            $errors['assigned_to'] = "Please select a valid user.";

        } else {

            $checkUser = $pdo->prepare(
                "SELECT id FROM users WHERE id = ?"
            );

            $checkUser->execute([(int) $assigned_to]);

            if (!$checkUser->fetch()) {

                $errors['assigned_to'] = "The selected user does not exist.";

            } else {

                $assigned_user_id = (int) $assigned_to;

            }
        }
    }

    if (empty($errors)) {

        try {

            $stmt = $pdo->prepare(
                "INSERT INTO tickets
                (title, description, priority, created_by, assigned_to)
                VALUES
                (:title, :description, :priority, :created_by, :assigned_to)"
            );

            $stmt->execute([
                ':title' => $title,
                ':description' => $description,
                ':priority' => $priority,
                ':created_by' => $_SESSION['user']['id'],
                ':assigned_to' => $assigned_user_id
            ]);

            header("Location: ticket-new.php?created=1");
            exit;

        } catch (PDOException $e) {

            $errors['general'] = "Something went wrong. The ticket could not be saved.";

        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>New Ticket | Fix-It Desk</title>

    <link rel="stylesheet" href="styles.css">

</head>

<body>

    
    <header class="navbar">

        <a href="index.php" class="brand">Fix-It Desk</a>

        <nav>
            <a href="index.php">Tickets</a>
            <a href="ticket-new.php" class="active">New ticket</a>

            <?php if (($_SESSION['user']['role'] ?? '') === 'admin'): ?>
                <a href="users.php">Users</a>
            <?php endif; ?>
        </nav>

        <div class="nav-user">

            <span>
                <?= e($_SESSION['user']['firstname'] ?? 'User') ?>
            </span>

            <form action="logout.php" method="POST">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e($_SESSION['csrf_token']) ?>"
                >

                <button type="submit" class="logout-btn">
                    Log out
                </button>

            </form>

        </div>

    </header>


    <main class="page-container">

        <h2>New ticket</h2>

        <?php if ($success): ?>

            <p class="success-message">
                <?= e($success) ?>
            </p>

        <?php endif; ?>


        <?php if (isset($errors['general'])): ?>

            <p class="error-message">
                <?= e($errors['general']) ?>
            </p>

        <?php endif; ?>


        <form action="ticket-new.php" method="POST" class="ticket-form">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e($_SESSION['csrf_token']) ?>"
            >

            <div class="form-group">

                <label for="title">Title</label>

                <input
                    type="text"
                    id="title"
                    name="title"
                    maxlength="150"
                    value="<?= e($title) ?>"
                >

                <?php if (isset($errors['title'])): ?>

                    <small class="field-error">
                        <?= e($errors['title']) ?>
                    </small>

                <?php endif; ?>

            </div>

            <div class="form-group">

                <label for="description">Description</label>

                <textarea
                    id="description"
                    name="description"
                    rows="6"
                ><?= e($description) ?></textarea>

                <?php if (isset($errors['description'])): ?>

                    <small class="field-error">
                        <?= e($errors['description']) ?>
                    </small>

                <?php endif; ?>

            </div>

            <div class="form-row">

                <div class="form-group">

                    <label for="priority">Priority</label>

                    <select id="priority" name="priority">

                        <option value="">Choose...</option>

                        <option value="Low"
                            <?= $priority === 'Low' ? 'selected' : '' ?>>
                            Low
                        </option>

                        <option value="Medium"
                            <?= $priority === 'Medium' ? 'selected' : '' ?>>
                            Medium
                        </option>

                        <option value="High"
                            <?= $priority === 'High' ? 'selected' : '' ?>>
                            High
                        </option>

                    </select>

                    <?php if (isset($errors['priority'])): ?>

                        <small class="field-error">
                            <?= e($errors['priority']) ?>
                        </small>

                    <?php endif; ?>

                </div>

                <div class="form-group">

                    <label for="assigned_to">Assign to</label>

                    <select id="assigned_to" name="assigned_to">

                        <option value="">Nobody yet</option>

                        <?php foreach ($users as $user): ?>

                            <option
                                value="<?= (int) $user['id'] ?>"
                                <?= (string) $assigned_to === (string) $user['id']
                                    ? 'selected'
                                    : '' ?>
                            >

                                <?= e($user['firstname'] . ' ' . $user['lastname']) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                    <?php if (isset($errors['assigned_to'])): ?>

                        <small class="field-error">
                            <?= e($errors['assigned_to']) ?>
                        </small>

                    <?php endif; ?>

                </div>

            </div>

            <div class="form-buttons">

                <button type="submit" class="create-btn">
                    Create ticket
                </button>

                <a href="index.php" class="cancel-link">
                    Cancel
                </a>

            </div>

        </form>

    </main>

</body>

</html>
