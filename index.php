<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT 
        t.id,
        t.title,
        t.priority,
        t.status,
        t.created_at,
        CONCAT(creator.firstname, ' ', creator.lastname) AS creator_name,
        CONCAT(assignee.firstname, ' ', assignee.lastname) AS assignee_name
    FROM tickets t
    INNER JOIN users creator ON t.created_by = creator.id
    LEFT JOIN users assignee ON t.assigned_to = assignee.id
    ORDER BY t.created_at DESC, t.id DESC
");
$stmt->execute();
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

require_once __DIR__ . '/includes/header.php';
?>

<main class="container">
    <?php if ($flash): ?>
        <div class="alert alert-success" role="status">
            <?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="page-header">
        <h1>Tickets</h1>
        <a href="ticket-new.php" class="btn btn-primary">New ticket</a>
    </div>

    <div class="filter-bar" role="group" aria-label="Filter tickets">
        <button type="button" class="filter-btn active" data-filter="all" aria-pressed="true">All</button>
        <button type="button" class="filter-btn" data-filter="open" aria-pressed="false">Open</button>
        <button type="button" class="filter-btn" data-filter="mine" aria-pressed="false">My tickets</button>
    </div>

    <p id="ticket-count" class="ticket-count">
        <?= count($tickets) ?> <?= count($tickets) === 1 ? 'ticket' : 'tickets' ?>
    </p>

    <div class="table-wrapper">
        <table class="tickets-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Title</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Created by</th>
                    <th>Assigned to</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody id="tickets-tbody">
                <?php if (empty($tickets)): ?>
                    <tr>
                        <td colspan="7">No tickets found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tickets as $ticket): ?>
                        <tr>
                            <td><?= (int)$ticket['id'] ?></td>
                            <td>
                                <a href="ticket.php?id=<?= (int)$ticket['id'] ?>">
                                    <?= htmlspecialchars($ticket['title'], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </td>
                            <td>
                                <span class="badge priority-<?= strtolower(htmlspecialchars($ticket['priority'], ENT_QUOTES, 'UTF-8')) ?>">
                                    <?= htmlspecialchars($ticket['priority'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge status-<?= strtolower(str_replace(' ', '-', htmlspecialchars($ticket['status'], ENT_QUOTES, 'UTF-8'))) ?>">
                                    <?= htmlspecialchars($ticket['status'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($ticket['creator_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($ticket['assignee_name'] ?? 'Nobody', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars(date('j M Y, g:i a', strtotime($ticket['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>