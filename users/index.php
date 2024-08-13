<?php
session_start();

// Redirect to login page if not logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

require_once '../config.php';

// ดึงข้อมูลผู้ใช้จากเซสชัน
$room_number = $_SESSION["room_number"];


// Initialize variables
$first_name = "";
$last_name = "";
$price = "";
$type_name = "";
$last_month_electric = 0;
$difference_electric = 0;
$Electricity_total = 0;
$total = 0;

$last_month_water = 0;
$difference_water = 0;
$Water_total = 0;

// ฟังก์ชันคำนวณค่าน้ำ
function calculateWaterCost($room_number) {
    $rooms_150 = ['201', '202', '302', '303', '304', '305', '306'];
    $rooms_200 = ['203', '204', '205', '206', '301'];

    if (in_array($room_number, $rooms_150)) {
        return 150;
    } elseif (in_array($room_number, $rooms_200)) {
        return 200;
    }
    return 0;
}

// ฟังก์ชันดึงข้อมูลผู้ใช้
function getUserDetails($conn, $room_number) {
    try {
        $stmt = $conn->prepare("SELECT u.Room_number, u.First_name, u.Last_name, t.price, t.type_name FROM users u JOIN type t ON u.type_id = t.id WHERE u.Room_number = ?");
        $stmt->bind_param("s", $room_number);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        } else {
            return null;
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        return null;
    }
}

// ฟังก์ชันดึงเลขมิเตอร์ไฟฟ้าครั้งก่อน
function getLastMonthElectric($conn, $room_number) {
    try {
        $stmt = $conn->prepare("SELECT meter_electric FROM electric WHERE user_id = (SELECT id FROM users WHERE Room_number = ?) ORDER BY date_record DESC LIMIT 1");
        $stmt->bind_param("s", $room_number);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            return $result->fetch_assoc()['meter_electric'];
        } else {
            return 0;
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        return 0;
    }
}

// ดึงข้อมูลผู้ใช้
$userDetails = getUserDetails($conn, $room_number);
if ($userDetails) {
    $room_number = $userDetails['Room_number'];
    $first_name = $userDetails['First_name'];
    $last_name = $userDetails['Last_name'];
    $price = $userDetails['price'];
    $type_name = $userDetails['type_name'];
    $last_month_electric = getLastMonthElectric($conn, $room_number); // ดึงเลขมิเตอร์ไฟฟ้าครั้งก่อน
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>คำนวณค่าใช้จ่ายประจำเดือน</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="index_u.css">
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
                <a href="index.php"><i class="fas fa-tint"></i> การคำนวณค่าน้ำ-ค่าไฟ</a>
                <a href="bill.php"><i class="fas fa-history"></i> รายการย้อนหลัง</a>
            </div>
        </aside>
        
        <div class="main-content">
            <h1>คำนวณค่าใช้จ่ายประจำเดือน</h1>

            <!-- Section 2: User Information -->
            <div class="info-grid">
                <div class="info-item">
                    <strong>หมายเลขห้อง:</strong> <?php echo htmlspecialchars($room_number); ?>
                </div>
                <div class="info-item">
                    <strong>ชื่อ:</strong> <?php echo htmlspecialchars($first_name); ?>
                </div>
                <div class="info-item">
                    <strong>นามสกุล:</strong> <?php echo htmlspecialchars($last_name); ?>
                </div>
                <div class="info-item">
                    <strong>ประเภทห้อง:</strong> <?php echo htmlspecialchars($type_name); ?>
                </div>
                <div class="info-item">
                    <strong>ราคาห้อง:</strong> <?php echo htmlspecialchars($price); ?> บาท
                </div>
                <div class="info-item">
                    <strong>ยอดค่าน้ำประจำห้อง:</strong> <?php echo calculateWaterCost($room_number); ?> บาท
                </div>
                <div class="info-item">
                    <strong>เลขมิเตอร์ไฟฟ้าครั้งก่อน:</strong> <?php echo htmlspecialchars($last_month_electric); ?>
                </div>
            </div>

            <!-- Section 3: Calculate Electricity and Water Costs -->
            <h2>คำนวณค่าใช้จ่ายประจำเดือน</h2>
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="form-group">
                <div class="form-group">
                    <label for="current_electric">เลขมิเตอร์ไฟฟ้าปัจจุบัน:</label>
                    <input type="number" id="current_electric" name="current_electric" required>
                    <input type="hidden" name="last_month_electric" value="<?php echo htmlspecialchars($last_month_electric); ?>">
                    <input type="hidden" name="room_number" value="<?php echo htmlspecialchars($room_number); ?>">
                    <input type="hidden" name="price" value="<?php echo htmlspecialchars($price); ?>">
                </div>

                <div class="form-group">
                    <label for="last_month_electric_display">เลขมิเตอร์ไฟฟ้าครั้งก่อน:</label>
                    <input type="number" id="last_month_electric_display" value="<?php echo htmlspecialchars($last_month_electric); ?>" readonly>
                </div>

                <div class="form-group">
                    <label for="unit_price">ราคาต่อหน่วย:</label>
                    <input type="number" id="unit_price" value="7" readonly>
                </div>

                <div class="form-group">
                    <button type="submit" name="calculate">คำนวณ</button>
                </div>
            </form>

            <?php
            if (isset($_POST['calculate'])) {
                $current_electric = $_POST['current_electric'];
                $last_month_electric = $_POST['last_month_electric'];
                $difference_electric = $current_electric - $last_month_electric;
                $Electricity_total = $difference_electric * 7;

                $water_cost = calculateWaterCost($room_number);
                $total_with_water = $Electricity_total + $water_cost + $price;

            ?>
                <div class="info-grid">
                    <div class="info-item">
                        <strong>ผลต่างของเลขมิเตอร์ไฟฟ้า:</strong> <?php echo htmlspecialchars($difference_electric); ?> หน่วย
                    </div>
                    <div class="info-item">
                        <strong>ยอดค่าไฟ:</strong> <?php echo htmlspecialchars($Electricity_total); ?> บาท
                    </div>
                    <div class="info-item">
                        <strong>ยอดค่าน้ำที่กำหนด:</strong> <?php echo htmlspecialchars($water_cost); ?> บาท
                    </div>
                    <div class="info-item">
                        <strong>ค่าห้องพัก:</strong> <?php echo htmlspecialchars($price); ?> บาท
                    </div>
                    <div class="info-item">
                        <strong>ค่าใช้จ่ายทั้งหมด:</strong> <?php echo htmlspecialchars($total_with_water); ?> บาท
                    </div>
                </div>

                <div class="form-group">
                    <button type="button" onclick="window.location.href='<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>'">ตกลง</button>
                </div>
            <?php } ?>
        </div>
    </div>
</body>
</html>
