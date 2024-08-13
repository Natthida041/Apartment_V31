<?php
require_once 'config.php'; // รวมไฟล์การตั้งค่าฐานข้อมูล

$selected_room = "";
$rooms = [];

// ฟังก์ชันแปลงหมายเลขเดือนเป็นชื่อเดือนภาษาไทย
function getThaiMonth($month) {
    $thaiMonths = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
        5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
        9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    ];
    return isset($thaiMonths[$month]) ? $thaiMonths[$month] : '';
}

// ฟังก์ชันแปลงปีคริสต์ศักราชเป็นปีพุทธศักราช
function getBuddhistYear($year) {
    return $year + 543;
}

// ดึงข้อมูลห้องจากฐานข้อมูลสำหรับ dropdown
$roomQuery = "SELECT DISTINCT Room_number FROM users WHERE Room_number IN ('201', '202', '302', '303', '304', '305', '306', '203', '204', '205', '206', '301', 'S1', 'S2') ORDER BY Room_number";

if ($roomResult = $conn->query($roomQuery)) {
    while ($row = $roomResult->fetch_assoc()) {
        $rooms[] = $row['Room_number'];
    }
}
$rooms[] = "ทั้งหมด"; // เพิ่มตัวเลือก "ทั้งหมด"

// จัดการการส่งฟอร์ม
$bill_details = null;
$records = [];
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['view_bill'])) {
    $selected_room = $_POST['selected_room'];

    if ($selected_room == "ทั้งหมด") {
        // ดึงบิลล่าสุดสำหรับแต่ละห้อง
        $sql = "SELECT b.*, u.Room_number, u.First_name, u.Last_name, 
                       CASE 
                           WHEN u.Room_number IN ('201', '202', '302', '303', '304', '305', '306', '203', '204', '205', '206', '301') THEN u.water_was 
                           WHEN u.Room_number = 'S1' THEN b.difference_water 
                           ELSE b.water_cost 
                       END as water_cost_display
                FROM bill b
                LEFT JOIN users u ON b.user_id = u.id
                WHERE u.Room_number IN ('201', '202', '302', '303', '304', '305', '306', '203', '204', '205', '206', '301', 'S1', 'S2')
                AND (u.Room_number, b.id) IN (
                    SELECT u.Room_number, MAX(b.id)
                    FROM bill b
                    LEFT JOIN users u ON b.user_id = u.id
                    GROUP BY u.Room_number
                )
                ORDER BY u.Room_number";
        $stmt = $conn->prepare($sql);
    } elseif ($selected_room == "S1") {
        // ดึงบิลล่าสุดสำหรับห้อง S1 และน้ำจาก difference_water
        $sql = "SELECT b.*, u.Room_number, u.First_name, u.Last_name, 
                       b.difference_water as water_cost_display
                FROM bill b
                LEFT JOIN users u ON b.user_id = u.id
                WHERE u.Room_number = ?
                ORDER BY b.id DESC LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $selected_room);
    } else {
        // ดึงบิลล่าสุดสำหรับห้องที่เลือก
        $sql = "SELECT b.*, u.Room_number, u.First_name, u.Last_name, 
                       CASE 
                           WHEN u.Room_number = 'S2' THEN 0 
                           ELSE b.water_cost 
                       END as water_cost_display
                FROM bill b
                LEFT JOIN users u ON b.user_id = u.id
                WHERE u.Room_number = ?
                ORDER BY b.id DESC LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $selected_room);
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
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบเสร็จ</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="bill.css"> <!-- เชื่อมโยงกับไฟล์ CSS -->
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
            <h1>เลือกข้อมูลสำหรับการออกใบเสร็จรับเงิน</h1>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
    <label for="selected_room">หมายเลขห้อง:</label>
    <select id="selected_room" name="selected_room" required>
        <?php foreach ($rooms as $room): ?>
            <option value="<?php echo $room; ?>" <?php echo $room == $selected_room ? 'selected' : ''; ?>>
                <?php echo $room; ?>
            </option>
        <?php endforeach; ?>
    </select>
    <div class="button-container">
        <button type="submit" name="view_bill" class="new-view-bill-button">ดูใบเสร็จรับเงิน</button>
    </div>
</div>
            </form>
            <?php if ($selected_room == "ทั้งหมด" && !empty($records)): ?>
                <div class="print-all-button-container">
                <button onclick="printAllBills()" class="print-all-button">พิมพ์ใบเสร็จทั้งหมด</button>
            </div>
                <div id="all-bills">
                    <?php foreach ($records as $index => $record): ?>
                        <div class="printable bill">
                            <div class="logo-container">
                    <img src="http://localhost/Apartment_V3/Admin/Ap.png" alt="เจ้าสัว Apartment Logo" class="logo">
                    </div>
                            <p>หมายเลขห้อง: <?php echo htmlspecialchars($record['Room_number']); ?></p>
                            <p>ชื่อ: <?php echo htmlspecialchars($record['First_name']); ?> <?php echo htmlspecialchars($record['Last_name']); ?></p>
                            <p>เดือน/ปี: <?php echo getThaiMonth($record['month']); ?> <?php echo getBuddhistYear($record['year']); ?></p>
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
                            <div class="payment-info">
                                <p><strong>โอนเงินเข้าบัญชี ธ.กรุงไทย (ชื่อบัญชี คุณกรรณิกา กุลจินต์)</strong></p>
                                <p>เลขบัญชี 915-1-16412-4 เท่านั้น ! (ชำระทุกวันที่ 1-5 นะคะ)</p>
                            </div>
                        </div>
                        <?php if ($index < count($records) - 1): ?>
                            <div class="page-break"></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
           <?php elseif (!empty($bill_details)): ?>
                <div class="printable">
                    <div class="logo-container">
                    <img src="http://localhost/Apartment_V3/Admin/Ap.png" alt="เจ้าสัว Apartment Logo" class="logo">
                    </div>
                    <div class="print-button-container">
                        <button onclick="window.print()" class="print-button">พิมพ์ใบเสร็จ</button>
                    </div>
                    <p>หมายเลขห้อง: <?php echo htmlspecialchars($bill_details['Room_number']); ?></p>
                    <p>ชื่อ: <?php echo htmlspecialchars($bill_details['First_name']); ?> <?php echo htmlspecialchars($bill_details['Last_name']); ?></p>
                    <p>เดือน/ปี: <?php echo getThaiMonth($bill_details['month']); ?> <?php echo getBuddhistYear($bill_details['year']); ?></p>
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
                    <div class="payment-info">
                        <p><strong>โอนเงินเข้าบัญชี ธ.กรุงไทย (ชื่อบัญชี คุณกรรณิกา กุลจินต์)</strong></p>
                        <p>เลขบัญชี 915-1-16412-4 เท่านั้น ! (ชำระทุกวันที่ 1-5 นะคะ)</p>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
    <script>
        function printAllBills() {
            var originalContents = document.body.innerHTML;
            var printContents = document.getElementById('all-bills').innerHTML;
            document.body.innerHTML = printContents;
            window.print();
            document.body.innerHTML = originalContents;
        }
    </script>
</body>
</html>