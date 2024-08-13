<?php
require_once 'config.php';

function createUser($Room_number, $type_id, $First_name, $Last_name, $username, $password, $urole) {
    global $conn;
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Check if type_id exists in the type table
    $check_type_sql = "SELECT COUNT(*) as count FROM type WHERE id = ?";
    $check_stmt = $conn->prepare($check_type_sql);
    $check_stmt->bind_param("i", $type_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $row = $result->fetch_assoc();
    $type_exists = $row['count'];

    if ($type_exists) {
        // Type exists, proceed with user creation
        $sql = "INSERT INTO users (Room_number, type_id, First_name, Last_name, username, password, urole) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssss", $Room_number, $type_id, $First_name, $Last_name, $username, $hashed_password, $urole);
        if ($stmt->execute()) {
            return true;
        } else {
            return false;
        }
    } else {
        // Type does not exist, display an error message
        echo "<script>alert('Type does not exist. Please select a valid type.');</script>";
        return false;
    }
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $Room_number = $_POST['Room_number'];
    $type_id = $_POST['type_id'];
    $First_name = $_POST['First_name'];
    $Last_name = $_POST['Last_name'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $urole = $_POST['urole'];

    // Call createUser function
    if (createUser($Room_number, $type_id, $First_name, $Last_name, $username, $password, $urole)) {
        // If user creation is successful, redirect to curd.php
        header("Location: crud.php");
        exit();
    } else {
        // If user creation fails, display an error message
        echo "<script>alert('Create User failed.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มผู้ใช้งานใหม่</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="c_u.css">
</head>
<body>
    <div class="container my-5">
        <div class="card">
            <div class="card-header">
                <h3 class="text-center"><i class="fas fa-user-plus me-2"></i>เพิ่มผู้ใช้งานใหม่</h3>
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="Room_number" class="form-label">หมายเลขห้อง</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-door-open"></i></span>
                                <input type="text" class="form-control" id="Room_number" name="Room_number" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="type_id" class="form-label">ประเภทผู้ใช้</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user-tag"></i></span>
                                <select class="form-select" id="type_id" name="type_id" required>
                                    <option value="1">Normal</option>
                                    <option value="2">Extra</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="First_name" class="form-label">ชื่อ</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" id="First_name" name="First_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="Last_name" class="form-label">นามสกุล</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" id="Last_name" name="Last_name" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="username" class="form-label">ชื่อผู้ใช้</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-at"></i></span>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">รหัสผ่าน</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="urole" class="form-label">บทบาท</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user-shield"></i></span>
                            <select class="form-select" id="urole" name="urole" required>
                                <option value="1">Admin</option>
                                <option value="2">Technician</option>
                                <option value="3">User</option>
                            </select>
                        </div>
                        <!-- Add conditions for creating user -->
            <script>
                document.getElementById('type_id').addEventListener('change', function() {
                    var type_id = this.value;
                    var urole = document.getElementById('urole').value;

                    if ((type_id == 1 && urole == 1) || (type_id == 2 && urole == 2) || (type_id == 3 && urole == 1) || (type_id == 4 && urole == 3)) {
                        document.getElementById('create_user_button').removeAttribute('disabled');
                    } else {
                        document.getElementById('create_user_button').setAttribute('disabled', 'disabled');
                    }
                });

                document.getElementById('urole').addEventListener('change', function() {
                    var type_id = document.getElementById('type_id').value;
                    var urole = this.value;

                    if ((type_id == 1 && urole == 1) || (type_id == 2 && urole == 2) || (type_id == 3 && urole == 1) || (type_id == 4 && urole == 3)) {
                        document.getElementById('create_user_button').removeAttribute('disabled');
                    } else {
                        document.getElementById('create_user_button').setAttribute('disabled', 'disabled');
                    }
                });
            </script>
                    </div>
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-user-plus me-2"></i>สร้างผู้ใช้งาน
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>