<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
require '../config/db_connect.php';

$success = $error = '';
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id === $_SESSION['user_id']) {
        $error = "You cannot delete yourself!";
    } else {
        $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        if (mysqli_stmt_execute($stmt)) {
            $success = "User deleted successfully!";
        } else {
            $error = "Error deleting user.";
        }
    }
}

// Count total
$count_sql = "SELECT COUNT(*) as total FROM users";
if ($search) {
    $count_sql .= " WHERE name LIKE ? OR email LIKE ? OR role LIKE ?";
}
$count_stmt = $conn->prepare($count_sql);
if ($search) {
    $like = "%$search%";
    $count_stmt->bind_param("sss", $like, $like, $like);
}
$count_stmt->execute();
$total = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = max(1, ceil($total / $per_page));

// Fetch users
$sql = "SELECT id, name, email, role, created_at, last_login FROM users";
if ($search) {
    $sql .= " WHERE name LIKE ? OR email LIKE ? OR role LIKE ?";
}
$sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if ($search) {
    $like = "%$search%";
    $stmt->bind_param("sssii", $like, $like, $like, $per_page, $offset);
} else {
    $stmt->bind_param("ii", $per_page, $offset);
}
$stmt->execute();
$users = $stmt->get_result();

$page_title = "Manage Users";
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!-- MAIN CONTENT WRAPPER -->
<div class="main-content">
    <div class="container-fluid pt-5 pt-lg-4">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
            <div>
                <h1 class="h3 mb-1 text-gray-800">Manage Users</h1>
                <p class="mb-0 text-muted small">Total: <?= $total ?> users</p>
            </div>
            <a href="register.php" class="btn btn-primary mt-3 mt-sm-0">
                <i class="fas fa-user-plus"></i> Add New User
            </a>
        </div>

        <!-- Messages -->
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Search Bar -->
        <div class="card shadow mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-lg-6 col-md-8">
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" name="search" class="form-control" placeholder="Search by name, email or role..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-4">
                        <button class="btn btn-primary w-100" type="submit">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                    <?php if ($search): ?>
                    <div class="col-lg-3 col-md-12 mt-2 mt-lg-0">
                        <a href="users.php" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Users Table -->
        <div class="card shadow">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Last Login</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($users->num_rows > 0): ?>
                                <?php $no = $offset + 1; while ($user = $users->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($user['name']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $user['role'] == 'admin' ? 'danger' : 
                                            ($user['role'] == 'reception' ? 'primary' : 'info') 
                                        ?>">
                                            <?= ucfirst($user['role']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= $user['last_login'] 
                                            ? date('d/m/Y H:i:s', strtotime($user['last_login'])) 
                                            : '<em class="text-muted">Not login yet</em>' 
                                        ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="edit_user.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-warning" title="Edit User">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="change_password.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-secondary" title="Change Password">
                                            <i class="fas fa-key"></i>
                                        </a>
                                       
                                        <!-- Alternatively for admin / superuser, we can disable self-deletion. -->
                                        <?php if ($user['id'] != $_SESSION['user_id']) : ?>
                                            <button type="button" class="btn btn-sm btn-danger" title="Delete user" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $user['id'] ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php else : ?>
                                            <button type="button" class="btn btn-sm btn-secondary" title="Cannot delete yourself" disabled>
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>

                                        <!-- Delete Modal -->
                                        <div class="modal fade" id="deleteModal<?= $user['id'] ?>">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-danger text-white">
                                                        <h5 class="modal-title">Confirm Delete</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body text-center">
                                                        <p>You are about to delete user named <strong><?= htmlspecialchars($user['name']) ?></strong>?</p>
                                                        <p class="text-muted small">Email: <?= htmlspecialchars($user['email']) ?></p>
                                                    </div>
                                                    <div class="modal-footer justify-content-center">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <a href="?delete=<?= $user['id'] ?>&page=<?= $page ?>&search=<?= urlencode($search) ?>" class="btn btn-danger">Delete</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        No staff found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">Back</a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">Next</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<!-- END MAIN CONTENT WRAPPER -->

<?php include '../includes/footer.php'; ?>