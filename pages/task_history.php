<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'housekeeping') {
    header("Location: ../login.php");
    exit();
}
require '../config/db_connect.php';

// Pagination & Search Setup
$limit = 10; // Tasks per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$search = trim($_GET['search'] ?? '');
$where = "";
$params = [];
$types = "";

if ($search !== '') {
    $where = "AND (r.room_number LIKE ? OR b.guest_name LIKE ? OR ht.notes LIKE ?)";
    $like = "%$search%";
    $params = [$like, $like, $like];
    $types = "sss";
}

// Count total for pagination
$count_query = "SELECT COUNT(*) as total 
                FROM housekeeping_tasks ht
                JOIN rooms r ON ht.room_id = r.id
                LEFT JOIN bookings b ON r.id = b.room_id AND b.booking_status = 'checked_out'
                WHERE ht.assigned_to = {$_SESSION['user_id']}
                AND ht.status = 'completed' $where";
if ($types) {
    $stmt = $conn->prepare($count_query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
} else {
    $total = mysqli_fetch_assoc(mysqli_query($conn, $count_query))['total'];
}
$total_pages = ceil($total / $limit);

// Fetch paginated history
$query = "
    SELECT ht.*, r.room_number, rt.type_name, b.guest_id, b.check_out,
           TIMESTAMPDIFF(HOUR, b.check_out, ht.completed_at) AS hours_since_checkout
    FROM housekeeping_tasks ht
    JOIN rooms r ON ht.room_id = r.id
    JOIN room_types rt ON r.type_id = rt.id
    LEFT JOIN bookings b ON r.id = b.room_id AND b.booking_status = 'checked_out'
    WHERE ht.assigned_to = {$_SESSION['user_id']}
      AND ht.status = 'completed' $where
    ORDER BY ht.completed_at DESC
    LIMIT ? OFFSET ?
";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$history = $stmt->get_result();

$page_title = "My Task History";
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid pt-5 pt-lg-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 gap-3">
            <h1 class="h3 mb-0 text-gray-800">Housekeeping Task History</h1>
            
            <!-- Search Form -->
            <form method="GET" class="d-flex gap-2 w-100 w-md-auto">
                <input type="text" name="search" class="form-control" placeholder="Search room, guest or notes ..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if ($search): ?>
                    <a href="task_history.php" class="btn btn-outline-secondary">Clear Filter</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Summary Card -->
        <div class="card shadow mb-4">
            <div class="card-body text-center py-4">
                <h5 class="mb-2">Total Completed Tasks</h5>
                <h2 class="text-success"><?= number_format($total) ?></h2>
                <small class="text-muted">Completed so far</small>
            </div>
        </div>

        <?php if (mysqli_num_rows($history) > 0): ?>
            <div class="row g-4">
                <?php while ($task = mysqli_fetch_assoc($history)): 
                    $hours = $task['hours_since_checkout'] ?? 0;
                    $priority = 'Normal';
                    $badge_class = 'bg-secondary';

                    if (stripos($task['notes'], 'VIP') !== false || stripos($task['notes'], 'urgent') !== false) {
                        $priority = 'High Priority';
                        $badge_class = 'bg-danger';
                    } elseif ($hours > 2) {
                        $priority = 'High Priority';
                        $badge_class = 'bg-danger';
                    } elseif ($hours >= 1 && $hours <= 2) {
                        $priority = 'Medium Priority';
                        $badge_class = 'bg-warning';
                    }
                ?>
                    <div class="col-lg-6 col-xl-4">
                        <div class="card shadow h-100 border-0 hover-shadow">
                            <div class="card-header d-flex justify-content-between align-items-center py-3">
                                <h5 class="mb-0">Room <?= htmlspecialchars($task['room_number']) ?></h5>
                                <span class="badge <?= $badge_class ?> fs-6"><?= $priority ?></span>
                            </div>
                            <div class="card-body">
                                <p class="mb-2"><strong>Type:</strong> <?= htmlspecialchars($task['type_name']) ?></p>
                                <!-- Completing status -->
                                 <p><strong>Status:</strong> 
                                    <span class="badge bg-success">Completed</span>
                                </p>
                                <!-- Completion time -->
                                <p class="mb-2"><strong>Completed at:</strong> <?= date('d/m/Y H:i', strtotime($task['completed_at'])) ?></p>
                                <p class="mb-2"><strong>Hours since assigned:</strong> <?= $hours > 0 ? $hours . ' hour(s) ago' : 'New' ?></p>
                                <?php if ($task['notes']): ?>
                                    <p class="mb-3"><strong>Notes:</strong> <?= htmlspecialchars($task['notes']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Pagination" class="mt-5">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>

        <?php else: ?>
            <div class="card shadow text-center py-5">
                <i class="fas fa-history fa-5x text-muted mb-3"></i>
                <h4 class="text-muted">No Task History Yet</h4>
                <p class="lead text-muted">Completed tasks will appear here after finishing a task.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>