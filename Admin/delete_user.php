<?php
require_once 'config.php';

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $user_id = intval($_GET['id']);

    $sql = "DELETE FROM users WHERE id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $message = "User deleted successfully.";
            $alert_type = "success";
        } else {
            $message = "Error deleting user.";
            $alert_type = "danger";
        }
        $stmt->close();
    } else {
        $message = "Error preparing the statement.";
        $alert_type = "danger";
    }
} else {
    $message = "Invalid user ID.";
    $alert_type = "warning";
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container my-5">
        <div class="alert alert-<?php echo $alert_type; ?>" role="alert">
            <?php echo $message; ?>
        </div>
        <a href="crud.php" class="btn btn-primary">Back to User List</a>
    </div>
</body>
</html>