<?php
require_once "config.php";

$username_err = $password_err = "";

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the data from the POST form
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    // Prepare SQL statement to find the user with the entered username
    $sql = "SELECT id, username, password, room_number, urole FROM users WHERE username = ?";

    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $param_username);

        $param_username = $username;

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_store_result($stmt);

            if (mysqli_stmt_num_rows($stmt) == 1) {
                mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password, $room_number, $urole);
                if (mysqli_stmt_fetch($stmt)) {
                    if (password_verify($password, $hashed_password)) {
                        session_start();

                        $_SESSION["loggedin"] = true;
                        $_SESSION["id"] = $id;
                        $_SESSION["username"] = $username;
                        $_SESSION["room_number"] = $room_number; // Store room_number in session
                        $_SESSION["urole"] = $urole;

                        // Redirect based on user role
                        switch ($urole) {
                            case "1":
                                header("location: Admin/index.php");
                                break;
                            case "2":
                                header("location: technician/index.php");
                                break;
                            case "3":
                                header("location: users/index.php");
                                break;
                            default:
                                echo "Invalid user role";
                                exit;
                        }
                    } else {
                        $password_err = "รหัสผ่านที่คุณป้อนไม่ถูกต้อง";
                    }
                }
            } else {
                $username_err = "ไม่พบบัญชีผู้ใช้ดังกล่าว";
            }
        } else {
            echo "มีบางอย่างผิดพลาด กรุณาลองใหม่ภายหลัง";
        }

        mysqli_stmt_close($stmt);
    }

    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - เจ้าสัว Apartment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="li.css">
</head>
<body>

<div class="login-container">
    <div class="login-header">
        <h2><i class="fas fa-building me-2"></i>เจ้าสัว Apartment</h2>
        <p>กรุณาเข้าสู่ระบบเพื่อดำเนินการต่อ</p>
    </div>
    <?php 
    if (!empty($username_err)) {
        echo '<div class="alert alert-danger">' . $username_err . '</div>';
    }
    if (!empty($password_err)) {
        echo '<div class="alert alert-danger">' . $password_err . '</div>';
    }
    ?>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <div class="form-group">
            <input type="text" id="username" name="username" placeholder=" " required>
            <label for="username">ชื่อผู้ใช้งาน</label>
            <i class="fas fa-user input-icon"></i>
        </div>
        <div class="form-group">
            <input type="password" id="password" name="password" placeholder=" " required>
            <label for="password">รหัสผ่าน</label>
            <i class="fas fa-lock input-icon"></i>
        </div>
        <button type="submit" class="btn btn-login">
            <i class="fas fa-sign-in-alt me-2"></i>เข้าสู่ระบบ
        </button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
