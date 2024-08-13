<?php
require_once 'config.php';

// Function to read user details
function readUser($id) {
    global $conn;
    $sql = "SELECT * FROM users WHERE id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    return null;
}

// Function to update user details
function updateUser($id, $Room_number, $type_id, $First_name, $Last_name, $username, $password, $urole) {
    global $conn;
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $sql = "UPDATE users SET Room_number = ?, type_id = ?, First_name = ?, Last_name = ?, username = ?, password = ?, urole = ? WHERE id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sisssssi", $Room_number, $type_id, $First_name, $Last_name, $username, $hashed_password, $urole, $id);
        return $stmt->execute();
    }
    return false;
}

// Check if user ID is provided
if (!isset($_GET['id']) && empty($_GET['id'])) {
    die('Invalid user ID.');
}
$user_id = intval($_GET['id']);
$user = readUser($user_id);
if (!$user) {
    die('User not found.');
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $Room_number = $_POST['Room_number'];
    $type_id = $_POST['type_id'];
    $First_name = $_POST['First_name'];
    $Last_name = $_POST['Last_name'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $urole = $_POST['urole'];

    if (updateUser($user_id, $Room_number, $type_id, $First_name, $Last_name, $username, $password, $urole)) {
        header("Location: crud.php");
        exit();
    } else {
        $error_message = "Error updating user.";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขข้อมูลผู้ใช้</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="e_u.css">
</head>
<body>
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0"><i class="fas fa-user-edit me-2"></i>แก้ไขข้อมูลผู้ใช้</h3>
                    </div>
                    <div class="card-body p-4">
                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error_message; ?>
                            </div>
                        <?php endif; ?>
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $user_id; ?>" method="post">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="Room_number" class="form-label"><i class="fas fa-door-open me-2"></i>หมายเลขห้อง</label>
                                    <input type="text" class="form-control" id="Room_number" name="Room_number" value="<?php echo htmlspecialchars($user['Room_number']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="type_id" class="form-label"><i class="fas fa-id-badge me-2"></i>ประเภท</label>
                                    <select class="form-select" id="type_id" name="type_id" required>
                                        <option value="1" <?php if ($user['type_id'] == '1' && $user['urole'] == '2') echo 'selected'; ?>>Normal</option>
                                        <option value="2" <?php if ($user['type_id'] == '2' && $user['urole'] == '2') echo 'selected'; ?>>Extra</option>
                                        <option value="3" <?php if ($user['type_id'] == '3' && $user['urole'] == '1') echo 'selected'; ?>>Admin</option>
                                        <option value="4" <?php if ($user['type_id'] == '4' && $user['urole'] == '3') echo 'selected'; ?>>Technician</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="First_name" class="form-label"><i class="fas fa-user me-2"></i>ชื่อ</label>
                                    <input type="text" class="form-control" id="First_name" name="First_name" value="<?php echo htmlspecialchars($user['First_name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="Last_name" class="form-label"><i class="fas fa-user me-2"></i>นามสกุล</label>
                                    <input type="text" class="form-control" id="Last_name" name="Last_name" value="<?php echo htmlspecialchars($user['Last_name']); ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="username" class="form-label"><i class="fas fa-at me-2"></i>ชื่อผู้ใช้งาน</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label"><i class="fas fa-lock me-2"></i>รหัสผ่าน</label>
                                <input type="password" class="form-control" id="password" name="password">
                                <small class="text-muted">เว้นว่างไว้หากไม่ต้องการเปลี่ยนรหัสผ่าน</small>
                            </div>
                            <div class="mb-4">
                                <label for="urole" class="form-label"><i class="fas fa-user-tag me-2"></i>บทบาท</label>
                                <select class="form-select" id="urole" name="urole" required>
                                    <option value="1" <?php if ($user['urole'] == '1') echo 'selected'; ?>>Admin</option>
                                    <option value="2" <?php if ($user['urole'] == '2') echo 'selected'; ?>>Technician</option>
                                    <option value="3" <?php if ($user['urole'] == '3') echo 'selected'; ?>>User</option>
                                </select>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save me-2"></i>อัพเดทข้อมูลผู้ใช้</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>