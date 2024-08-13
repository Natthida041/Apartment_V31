<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

require_once '../config.php';

// Query to fetch user data
function readUsers() {
    global $conn;
    $sql = "SELECT id, First_name, Last_name, Room_number, urole FROM users ORDER BY id";
    return $conn->query($sql);
}

function getUserRoleText($urole) {
    switch ($urole) {
        case '1':
            return 'Admin';
        case '2':
            return 'Technician';
        case '3':
            return 'User';
        default:
            return 'ไม่ระบุ';
    }
}

$users = readUsers(); // Fetch user data for use in the HTML
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการข้อมูลผู้ใช้</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="crud.css">
</head>
<body>
    <nav class="navbar">
    <img src="http://localhost/Apartment_V3/Admin/Ap.png" alt="เจ้าสัว Apartment Logo" class="logo-navbar">
</span>
        <div class="navbar-menu">
            <a href="logout.php">ออกจากระบบ</a>
        </div>
    </nav>
    
    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-menu">
                <a href="index.php"><i class="fas fa-home"></i>หน้าหลัก</a>
                <a href="crud.php"><i class="fas fa-users"></i>จัดการข้อมูลผู้ใช้</a>
                <a href="total.php"><i class="fas fa-tint"></i>การคำนวณค่าน้ำ-ค่าไฟ</a>
                <a href="bill.php"><i class="fas fa-file-invoice"></i>พิมพ์เอกสาร</a>
                <a href="bill_back.php"><i class="fas fa-history"></i> รายการย้อนหลัง</a>
                <a href="summary.php.php"><i class="fas fa-chart-bar"></i> สรุปยอดรายเดือน/ปี</a>
            </div>
        </aside>
        
        <main class="content">
            <h1>จัดการข้อมูลผู้ใช้งาน</h1>
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>รายชื่อผู้ใช้งาน</span>
                    <a href="create_user.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-user-plus me-2"></i>เพิ่มผู้ใช้งาน
                    </a>
                </div>
                <div class="card-body">
                        <table class="table table-bordered table-hover table-center">
                            <thead>
                                <tr>
                                    <th>ลำดับ</th>
                                    <th>ชื่อ-นามสกุล</th>
                                    <th>ห้องพัก</th>
                                    <th>ประเภทผู้ใช้</th>
                                    <th>การจัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($users->num_rows > 0) {
                                    while ($user = $users->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($user['id']) . "</td>";
                                        echo "<td>" . htmlspecialchars($user['First_name'] . ' ' . $user['Last_name']) . "</td>";
                                        echo "<td>" . htmlspecialchars($user['Room_number']) . "</td>";
                                        echo "<td>" . htmlspecialchars(getUserRoleText($user['urole'])) . "</td>";
                                        echo "<td>
                                                <a href='edit_user.php?id=" . htmlspecialchars($user['id']) . "' class='btn btn-warning btn-action' title='แก้ไข'><i class='fas fa-edit'></i></a>
                                                <a href='delete_user.php?id=" . htmlspecialchars($user['id']) . "' class='btn btn-danger btn-action' title='ลบ' onclick='return confirm(\"คุณแน่ใจหรือไม่ที่จะลบผู้ใช้งานนี้?\")'><i class='fas fa-trash-alt'></i></a>
                                              </td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='text-center'>ไม่พบข้อมูลผู้ใช้งาน</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Script for mobile sidebar toggle
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
        }
    </script>
</body>
</html>

<?php
mysqli_close($conn);
?>
