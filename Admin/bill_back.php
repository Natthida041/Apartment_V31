<?php
require_once 'config.php'; // Include the database configuration file

$selected_room = $selected_month = $selected_year = "";
$rooms = [];
$months = [];
$years = [];

// Function to convert month numbers to Thai month names
function getThaiMonth($month) {
    $thaiMonths = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
        5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
        9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    ];
    return isset($thaiMonths[$month]) ? $thaiMonths[$month] : '';
}

// Function to convert Christian era to Buddhist era
function getBuddhistYear($year) {
    return $year + 543;
}

// Fetch room, month, and year data for dropdowns
$roomQuery = "SELECT DISTINCT Room_number FROM users WHERE Room_number IN ('201', '202', '302', '303', '304', '305', '306', '203', '204', '205', '206', '301', 'S1', 'S2') ORDER BY Room_number";
$monthQuery = "SELECT DISTINCT month FROM bill ORDER BY month DESC"; // Ordered in descending to get recent months at top
$yearQuery = "SELECT DISTINCT year FROM bill ORDER BY year DESC"; // Ordered in descending to get recent years at top

if ($roomResult = $conn->query($roomQuery)) {
    while ($row = $roomResult->fetch_assoc()) {
        $rooms[] = $row['Room_number'];
    }
}
$rooms[] = "ทั้งหมด"; // Add option "All"
if ($monthResult = $conn->query($monthQuery)) {
    while ($row = $monthResult->fetch_assoc()) {
        $months[] = $row['month'];
    }
}
if ($yearResult = $conn->query($yearQuery)) {
    while ($row = $yearResult->fetch_assoc()) {
        $years[] = $row['year'];
    }
}

// จัดการการส่งฟอร์ม
$bill_details = null;
$records = [];
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['view_bill'])) {
    $selected_room = $_POST['selected_room'];
    $selected_month = $_POST['selected_month'];
    $selected_year = $_POST['selected_year'];

    if ($selected_room == "ทั้งหมด") {
        // ดึงบิลล่าสุดสำหรับแต่ละห้องในเดือนและปีที่เลือก
        $sql = "SELECT b.*, u.Room_number, u.First_name, u.Last_name, 
                       CASE 
                           WHEN u.Room_number IN ('201', '202', '302', '303', '304', '305', '306', '203', '204', '205', '206', '301') THEN u.water_was 
                           WHEN u.Room_number = 'S1' THEN b.difference_water 
                           ELSE b.water_cost 
                       END as water_cost_display
                FROM bill b
                LEFT JOIN users u ON b.user_id = u.id
                WHERE b.month = ? AND b.year = ? AND u.Room_number IN ('201', '202', '302', '303', '304', '305', '306', '203', '204', '205', '206', '301', 'S1', 'S2')
                AND (u.Room_number, b.id) IN (
                    SELECT u.Room_number, MAX(b.id)
                    FROM bill b
                    LEFT JOIN users u ON b.user_id = u.id
                    WHERE b.month = ? AND b.year = ?
                    GROUP BY u.Room_number
                )
                ORDER BY u.Room_number";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiii", $selected_month, $selected_year, $selected_month, $selected_year);
    } elseif ($selected_room == "S1") {
        // ดึงบิลล่าสุดสำหรับห้อง S1 และน้ำจาก difference_water
        $sql = "SELECT b.*, u.Room_number, u.First_name, u.Last_name, 
                       b.difference_water as water_cost_display
                FROM bill b
                LEFT JOIN users u ON b.user_id = u.id
                WHERE u.Room_number = ? AND b.month = ? AND b.year = ?
                ORDER BY b.id DESC LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $selected_room, $selected_month, $selected_year);
    } else {
        // ดึงบิลล่าสุดสำหรับห้องที่เลือก
        $sql = "SELECT b.*, u.Room_number, u.First_name, u.Last_name, 
                       CASE 
                           WHEN u.Room_number = 'S2' THEN 0 
                           ELSE b.water_cost 
                       END as water_cost_display
                FROM bill b
                LEFT JOIN users u ON b.user_id = u.id
                WHERE u.Room_number = ? AND b.month = ? AND b.year = ?
                ORDER BY b.id DESC LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $selected_room, $selected_month, $selected_year);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if ($selected_room == "ทั้งหมด") {
        while ($row = $result->fetch_assoc()) {
            $records[] = $row;
        }
    } else {
        $bill_details = $result->fetch_assoc();
    }
}

$conn->close();

// ฟังก์ชันแปลงเดือนและปีปัจจุบันเป็นภาษาไทย
function getCurrentThaiMonthYear() {
    $currentMonth = date('n'); // ดึงเดือนปัจจุบันเป็นตัวเลข
    $currentYear = date('Y') + 543; // ดึงปีปัจจุบันและแปลงเป็นพุทธศักราช
    $thaiMonths = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน', 5 => 'พฤษภาคม', 6 => 'มิถุนายน',
        7 => 'กรกฎาคม', 8 => 'สิงหาคม', 9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    ];
    return [$thaiMonths[$currentMonth], $currentYear];
}

list($currentThaiMonth, $currentBuddhistYear) = getCurrentThaiMonthYear();
?>


<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการย้อนหลัง</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="bill_b.css"> <!-- เชื่อมโยงกับไฟล์ CSS -->
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
                <a href="summary.php"><i class="fas fa-chart-bar"></i> สรุปยอดรายเดือน/ปี</a>
            </div>
        </aside>

        <main class="content">
            <h1>เลือกข้อมูลสำหรับรายการค่าไฟและค่าน้ำรายเดือนย้อนหลัง</h1>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label for="selected_room">หมายเลขห้อง:</label>
                    <select id="selected_room" name="selected_room" required>
                        <?php foreach ($rooms as $room): ?>
                            <option value="<?php echo $room; ?>" <?php echo $room == $selected_room ? 'selected' : ''; ?>><?php echo $room; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="selected_month">เดือน:</label>
                    <select id="selected_month" name="selected_month" required>
                        <?php foreach ($months as $month): ?>
                            <option value="<?php echo $month; ?>" <?php echo $month == $selected_month ? 'selected' : ''; ?>><?php echo getThaiMonth($month); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="selected_year">ปี:</label>
                    <select id="selected_year" name="selected_year" required>
                        <?php foreach ($years as $year): ?>
                            <option value="<?php echo $year; ?>" <?php echo $year == $selected_year ? 'selected' : ''; ?>><?php echo getBuddhistYear($year); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="view_bill">ดูใบเสร็จย้อนหลัง</button>
            </form>
            <?php if ($selected_room == "ทั้งหมด" && !empty($records)): ?>
                <div id="all-bills">
                    <?php foreach ($records as $index => $record): ?>
                        <div class="printable bill">
                            <h2>รายการค่าไฟและค่าน้ำ</h2>
                            <p>หมายเลขห้อง: <?php echo htmlspecialchars($record['Room_number']); ?></p>
                            <p>ชื่อ: <?php echo htmlspecialchars($record['First_name']); ?> <?php echo htmlspecialchars($record['Last_name']); ?></p>
                            <p>เดือน/ปี: <?php echo getThaiMonth($selected_month); ?> <?php echo getBuddhistYear($selected_year); ?></p>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>รายการ</th>
                                        <th>จำนวนหน่วย</th>
                                        <th>ราคาต่อหน่วย</th>
                                        <th>รวม(บาท)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>ค่าไฟฟ้า</td>
                                        <td><?php echo htmlspecialchars($record['difference_electric']); ?></td>
                                        <td>7</td>
                                        <td><?php echo htmlspecialchars($record['difference_electric'] * 7); ?></td>
                                    </tr>
                                    <tr>
                                        <td>ค่าน้ำ</td>
                                        <td>
                                            <?php 
                                                if ($record['Room_number'] == 'S1') {
                                                    echo htmlspecialchars($record['difference_water']); 
                                                } elseif ($record['Room_number'] == 'S2') {
                                                    echo '0';
                                                } elseif (in_array($record['Room_number'], ['201', '202', '302', '303', '304', '305', '306'])) {
                                                    echo '1';
                                                } elseif (in_array($record['Room_number'], ['203', '204', '205', '206', '301'])) {
                                                    echo '1';
                                                }
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                                if ($record['Room_number'] == 'S1') {
                                                    echo '22';
                                                } elseif ($record['Room_number'] == 'S2') {
                                                    echo '0';
                                                } elseif (in_array($record['Room_number'], ['201', '202', '302', '303', '304', '305', '306'])) {
                                                    echo '150';
                                                } elseif (in_array($record['Room_number'], ['203', '204', '205', '206', '301'])) {
                                                    echo '200';
                                                }
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                                if ($record['Room_number'] == 'S1') {
                                                    echo htmlspecialchars($record['difference_water']) * 22; 
                                                } elseif ($record['Room_number'] == 'S2') {
                                                    echo '0';
                                                } elseif (in_array($record['Room_number'], ['201', '202', '302', '303', '304', '305', '306'])) {
                                                    echo '150';
                                                } elseif (in_array($record['Room_number'], ['203', '204', '205', '206', '301'])) {
                                                    echo '200';
                                                }
                                            ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>ค่าห้อง</td>
                                        <td>1</td>
                                        <td><?php echo htmlspecialchars($record['room_cost']); ?></td>
                                        <td><?php echo htmlspecialchars($record['room_cost']); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="total">
                                <strong>ยอดรวม: <?php echo htmlspecialchars($record['total_cost']); ?> บาท</strong>
                            </div>
                        </div>
                        <?php if ($index < count($records) - 1): ?>
                            <div class="page-break"></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php elseif (!empty($bill_details)): ?>
                <div class="printable">
                    <h2>รายการค่าไฟและค่าน้ำ</h2>
                    <p>หมายเลขห้อง: <?php echo htmlspecialchars($bill_details['Room_number']); ?></p>
                    <p>ชื่อ: <?php echo htmlspecialchars($bill_details['First_name']); ?> <?php echo htmlspecialchars($bill_details['Last_name']); ?></p>
                    <p>เดือน/ปี: <?php echo getThaiMonth($selected_month); ?> <?php echo getBuddhistYear($selected_year); ?></p>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>รายการ</th>
                                <th>จำนวนหน่วย</th>
                                <th>ราคาต่อหน่วย</th>
                                <th>รวม(บาท)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>ค่าไฟฟ้า</td>
                                <td><?php echo htmlspecialchars($bill_details['difference_electric']); ?></td>
                                <td>7</td>
                                <td><?php echo htmlspecialchars($bill_details['difference_electric'] * 7); ?></td>
                            </tr>
                            <tr>
                                <td>ค่าน้ำ</td>
                                <td>
                                    <?php 
                                        if ($bill_details['Room_number'] == 'S1') {
                                            echo htmlspecialchars($bill_details['difference_water']); 
                                        } elseif ($bill_details['Room_number'] == 'S2') {
                                            echo '0';
                                        } elseif (in_array($bill_details['Room_number'], ['201', '202', '302', '303', '304', '305', '306'])) {
                                            echo '1';
                                        } elseif (in_array($bill_details['Room_number'], ['203', '204', '205', '206', '301'])) {
                                            echo '1';
                                        }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                        if ($bill_details['Room_number'] == 'S1') {
                                            echo '22';
                                        } elseif ($bill_details['Room_number'] == 'S2') {
                                            echo '0';
                                        } elseif (in_array($bill_details['Room_number'], ['201', '202', '302', '303', '304', '305', '306'])) {
                                            echo '150';
                                        } elseif (in_array($bill_details['Room_number'], ['203', '204', '205', '206', '301'])) {
                                            echo '200';
                                        }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                        if ($bill_details['Room_number'] == 'S1') {
                                            echo htmlspecialchars($bill_details['difference_water']) * 22; 
                                        } elseif ($bill_details['Room_number'] == 'S2') {
                                            echo '0';
                                        } elseif (in_array($bill_details['Room_number'], ['201', '202', '302', '303', '304', '305', '306'])) {
                                            echo '150';
                                        } elseif (in_array($bill_details['Room_number'], ['203', '204', '205', '206', '301'])) {
                                            echo '200';
                                        }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td>ค่าห้อง</td>
                                <td>1</td>
                                <td><?php echo htmlspecialchars($bill_details['room_cost']); ?></td>
                                <td><?php echo htmlspecialchars($bill_details['room_cost']); ?></td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="total">
                        <strong>ยอดรวม: <?php echo htmlspecialchars($bill_details['total_cost']); ?> บาท</strong>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
