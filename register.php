<?php
require_once "config.php";

// Check if the form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Get the data from the POST form
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);
    $First_name = trim($_POST["First_name"]);
    $Last_name = trim($_POST["Last_name"]);
    $room_number = trim($_POST["room_number"]);
    $urole = $_POST["role"]; 

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Prepare SQL statement to insert new user data into the database
    $sql = "INSERT INTO users (username, password, First_name, Last_name, room_number, urole) VALUES (?, ?, ?, ?, ?, ?)";

    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "ssssss", $param_username, $param_password, $param_First_name, $param_Last_name, $param_room_number, $param_urole);

        $param_username = $username;
        $param_password = $hashed_password;
        $param_First_name = $First_name;
        $param_Last_name= $Last_name;
        $param_room_number = $room_number;
        $param_urole = $urole;

        if(mysqli_stmt_execute($stmt)){
            echo "สมัครสมาชิกสำเร็จ";
            header("location: login.php");
            exit;
        } else{
            echo "มีบางอย่างผิดพลาด กรุณาลองใหม่ภายหลัง";
        }

        mysqli_stmt_close($stmt);
    }

    mysqli_close($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="li.css">
    <style>
        body {
            font-family: 'Prompt', sans-serif;
            background-color: #f0f8ff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .register-container {
            background-color: #ffffff;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 113, 195, 0.2);
            overflow: hidden;
            padding: 30px;
            max-width: 500px;
            width: 100%;
        }
        .register-title {
            color: #0071C3;
            font-weight: 600;
            margin-bottom: 30px;
            text-align: center;
        }
        .form-label {
            color: #0071C3;
            font-weight: 500;
        }
        .form-control, .form-select {
            border-radius: 10px;
            padding: 12px;
            border: 2px solid #0071C3;
            transition: all 0.3s;
        }
        .form-control:focus, .form-select:focus {
            box-shadow: 0 0 0 0.25rem rgba(0, 113, 195, 0.25);
            border-color: #0071C3;
        }
        .btn-register {
            background-color: #0071C3;
            border: none;
            color: #fff;
            padding: 12px 20px;
            font-size: 18px;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s;
        }
        .btn-register:hover {
            background-color: #005a9e;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 113, 195, 0.3);
        }
        .input-group-text {
            background-color: #0071C3;
            border: none;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2 class="register-title">ลงทะเบียน</h2>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="mb-3">
                <label for="username" class="form-label">ชื่อผู้ใช้งาน:</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">รหัสผ่าน:</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="First_name" class="form-label">ชื่อ:</label>
                <input type="text" id="First_name" name="First_name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="Last_name" class="form-label">นามสกุล:</label>
                <input type="text" id="Last_name" name="Last_name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="role" class="form-label">บทบาท:</label>
                <select id="role" name="role" class="form-select" required>
                    <option value="1">Admin</option>
                    <option value="2">Technician</option>
                </select>
            </div>
                <div class="mb-3" style="text-align: center;">
        <input type="submit" value="ยืนยัน" class="btn btn-register">
            </div>
        </form>
    </div>
</body>
</html>
