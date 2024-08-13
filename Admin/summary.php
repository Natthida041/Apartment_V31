<?php
require_once 'config.php';

$selected_month = $selected_year = $selected_room = "";
$rooms = $months = $years = [];

// ฟังก์ชันแปลงเดือนเป็นภาษาไทย
function getThaiMonth($month) {
    $thai_months = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
        5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
        9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    ];
    return isset($thai_months[$month]) ? $thai_months[$month] : null;
}

// ดึงข้อมูลห้อง เดือน และปีที่มีอยู่
$queries = [
    'rooms' => "SELECT DISTINCT Room_number FROM users ORDER BY Room_number",
    'months' => "SELECT DISTINCT month FROM bill ORDER BY month DESC", // Descending order
    'years' => "SELECT DISTINCT year FROM bill ORDER BY year DESC" // Descending order
];

foreach ($queries as $key => $query) {
    if ($result = $conn->query($query)) {
        while ($row = $result->fetch_assoc()) {
            if ($key === 'rooms') {
                $rooms[] = $row['Room_number'];
            } elseif ($key === 'months') {
                $months[] = $row['month'];
            } elseif ($key === 'years') {
                $years[] = $row['year'] + 543; // แปลงเป็น พ.ศ.
            }
        }
    }
}
$rooms[] = "ทั้งหมด";

// จัดการการส่งฟอร์ม
$records = [];
$total_sum = 0;
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $selected_year = $_POST['selected_year'] - 543; // แปลงกลับเป็น ค.ศ. สำหรับ query

    $sql = "SELECT b.*, u.Room_number, u.First_name, u.Last_name, 
                   CASE 
                       WHEN u.Room_number IN ('201', '202', '302', '303', '304', '305', '306', '203', '204', '205', '206', '301') THEN u.water_was 
                       WHEN u.Room_number = 'S1' THEN b.water_cost 
                       ELSE b.water_cost 
                   END as water_cost_display
            FROM bill b
            LEFT JOIN users u ON b.user_id = u.id
            WHERE b.year = ? AND u.Room_number IN ('201', '202', '302', '303', '304', '305', '306', '203', '204', '205', '206', '301', 'S1', 'S2')";

    if (isset($_POST['view_monthly_bill'])) {
        $selected_month = $_POST['selected_month'];
        $sql .= " AND b.month = ? AND (u.Room_number, b.id) IN (
                    SELECT u.Room_number, MAX(b.id)
                    FROM bill b
                    LEFT JOIN users u ON b.user_id = u.id
                    WHERE b.month = ? AND b.year = ?
                    GROUP BY u.Room_number
                )";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiii", $selected_year, $selected_month, $selected_month, $selected_year);
    } elseif (isset($_POST['view_yearly_bill'])) {
        $selected_room = $_POST['selected_room'];
        if ($selected_room != "ทั้งหมด") {
            $sql .= " AND u.Room_number = ?";
        }
        $sql .= " AND (u.Room_number, b.month, b.id) IN (
                    SELECT u.Room_number, b.month, MAX(b.id)
                    FROM bill b
                    LEFT JOIN users u ON b.user_id = u.id
                    WHERE b.year = ?
                    GROUP BY u.Room_number, b.month
                )";
        $stmt = $conn->prepare($sql);
        if ($selected_room != "ทั้งหมด") {
            $stmt->bind_param("isi", $selected_year, $selected_room, $selected_year);
        } else {
            $stmt->bind_param("ii", $selected_year, $selected_year);
        }
    }

    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $row['month'] = getThaiMonth($row['month']);
        $row['year'] += 543; // แปลงเป็น พ.ศ. สำหรับการแสดงผล
        $records[] = $row;
        $total_sum += $row['total_cost'];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สรุปยอดรายเดือน/ปี</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="smr.css">
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
                <a href="index.php"><i class="fas fa-home"></i> หน้าหลัก</a>
                <a href="crud.php"><i class="fas fa-users"></i> จัดการข้อมูลผู้ใช้</a>
                <a href="total.php"><i class="fas fa-tint"></i> การคำนวณค่าน้ำ-ค่าไฟ</a>
                <a href="bill.php"><i class="fas fa-file-invoice"></i> พิมพ์เอกสาร</a>
                <a href="bill_back.php"><i class="fas fa-history"></i> รายการย้อนหลัง</a>
                <a href="summary.php" class="active"><i class="fas fa-chart-bar"></i> สรุปยอดรายเดือน/ปี</a>
            </div>
        </aside>
        
        <main class="content">
            <h1>เลือกข้อมูลสำหรับการสรุปยอดรายเดือน/ปี</h1>         
            <section class="summary-section">
                <h2>สรุปยอดรายเดือน</h2>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="form-group">
                        <label for="selected_month">เดือน:</label>
                        <select id="selected_month" name="selected_month" required>
                            <?php foreach ($months as $month): ?>
                                <option value="<?php echo $month; ?>" <?php echo $month == $selected_month ? 'selected' : ''; ?>>
                                    <?php echo getThaiMonth($month); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="selected_year">ปี:</label>
                        <select id="selected_year" name="selected_year" required>
                            <?php foreach ($years as $year): ?>
                                <option value="<?php echo $year; ?>" <?php echo $year == $selected_year ? 'selected' : ''; ?>>
                                    <?php echo $year; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="view_monthly_bill" class="btn">ดูสรุปยอดรายเดือน</button>
                </form>
            </section>

            <section class="summary-section">
                <h2>สรุปยอดรายปี</h2>
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
                    </div>
                    <div class="form-group">
                        <label for="selected_year">ปี:</label>
                        <select id="selected_year" name="selected_year" required>
                            <?php foreach ($years as $year): ?>
                                <option value="<?php echo $year; ?>" <?php echo $year == $selected_year ? 'selected' : ''; ?>>
                                    <?php echo $year; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="view_yearly_bill" class="btn">ดูสรุปยอดรายปี</button>
                </form>
            </section>
            <?php if (!empty($records)): ?>
                <section class="results-section">
                    <div class="table-container">
                        <div class="total-sum">ยอดรวมทั้งหมด: <?php echo htmlspecialchars(number_format($total_sum, 2)); ?> บาท</div>
                        <h2>ผลลัพธ์:</h2>
                        <table>
                            <thead>
                                <tr>
                                    <th>หมายเลขห้อง</th>
                                    <th>เดือน</th>
                                    <th>ปี</th>
                                    <th>ค่าไฟฟ้า</th>
                                    <th>ค่าน้ำ</th>
                                    <th>ค่าห้อง</th>
                                    <th>ค่าใช้จ่ายทั้งหมด</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($records as $record): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($record['Room_number']); ?></td>
                                        <td><?php echo htmlspecialchars($record['month']); ?></td>
                                        <td><?php echo htmlspecialchars($record['year']); ?></td>
                                        <td><?php echo htmlspecialchars($record['electric_cost']); ?> บาท</td>
                                        <td><?php echo htmlspecialchars($record['water_cost_display']); ?> บาท</td>
                                        <td><?php echo htmlspecialchars($record['room_cost']); ?> บาท</td>
                                        <td><?php echo htmlspecialchars($record['total_cost']); ?> บาท</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
