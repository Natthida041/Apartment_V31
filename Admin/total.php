<?php
require_once 'config.php'; // Include the configuration file for database settings

// Initialize variables
$room_number = "";
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

function calculateWaterCost($room_number)
{
    // Define room groups with specific water costs
    $rooms_150 = ['201', '202', '302', '303', '304', '305', '306'];
    $rooms_200 = ['203', '204', '205', '206', '301'];

    if (in_array($room_number, $rooms_150)) {
        return 150;
    } elseif (in_array($room_number, $rooms_200)) {
        return 200;
    }
    return 0;
}

function getUserDetails($conn, $room_number)
{
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

function getLastMonthElectric($conn, $room_number)
{
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

function getLastMonthWater($conn, $room_number)
{
    try {
        $stmt = $conn->prepare("SELECT meter_water FROM water WHERE user_id = (SELECT id FROM users WHERE Room_number = ?) ORDER BY date_record DESC LIMIT 1");
        $stmt->bind_param("s", $room_number);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            return $result->fetch_assoc()['meter_water'];
        } else {
            return 0;
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        return 0;
    }
}

function saveElectricMeter($conn, $room_number, $current_electric, $difference_electric)
{
    try {
        $stmt = $conn->prepare("INSERT INTO electric (user_id, meter_electric, difference_electric, date_record) VALUES ((SELECT id FROM users WHERE Room_number = ?), ?, ?, CURDATE())");
        $stmt->bind_param("sdd", $room_number, $current_electric, $difference_electric);
        return $stmt->execute();
    } catch (Exception $e) {
        error_log($e->getMessage());
        return false;
    }
}

function saveWaterMeter($conn, $room_number, $current_water, $difference_water)
{
    try {
        $stmt = $conn->prepare("INSERT INTO water (user_id, meter_water, difference_water, date_record) VALUES ((SELECT id FROM users WHERE Room_number = ?), ?, ?, CURDATE())");
        $stmt->bind_param("sdd", $room_number, $current_water, $difference_water);
        return $stmt->execute();
    } catch (Exception $e) {
        error_log($e->getMessage());
        return false;
    }
}

function saveBill($conn, $room_number, $Electricity_total, $Water_total, $price, $total, $difference_electric, $difference_water)
{
    $water_cost = calculateWaterCost($room_number);
    $total_with_water = $total + $water_cost;

    try {
        $stmt = $conn->prepare("INSERT INTO bill (user_id, month, year, electric_cost, water_cost, room_cost, total_cost, difference_electric, difference_water) VALUES ((SELECT id FROM users WHERE Room_number = ?), MONTH(CURDATE()), YEAR(CURDATE()), ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sdddsdd", $room_number, $Electricity_total, $Water_total, $price, $total_with_water, $difference_electric, $difference_water);
        return $stmt->execute();
    } catch (Exception $e) {
        error_log($e->getMessage());
        return false;
    }
}

function fetchRoomNumbers($conn)
{
    try {
        $result = $conn->query("SELECT Room_number FROM users");
        if ($result->num_rows > 0) {
            return $result;
        } else {
            return [];
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        return [];
    }
}

$roomNumbers = fetchRoomNumbers($conn);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['room_number'])) {
        $room_number = $_POST['room_number'];
        $userDetails = getUserDetails($conn, $room_number);

        if ($userDetails) {
            $room_number = $userDetails['Room_number'];
            $first_name = $userDetails['First_name'];
            $last_name = $userDetails['Last_name'];
            $price = $userDetails['price'];
            $type_name = $userDetails['type_name'];
        }

        $last_month_electric = getLastMonthElectric($conn, $room_number);
        $last_month_water = getLastMonthWater($conn, $room_number);
    }

    if (isset($_POST['calculate'])) {
        $current_electric = (float)$_POST['current_electric'];
        $last_month_electric = (float)$_POST['last_month_electric'];
        $price = (float)$_POST['price'];

        $difference_electric = $current_electric - $last_month_electric;
        $Electricity_total = $difference_electric * 7; // Calculate cost based on electricity usage
        $total = $Electricity_total + $price; // Total cost including room price

        saveElectricMeter($conn, $room_number, $current_electric, $difference_electric);

        if ($room_number == 'S1') {
            $current_water = (float)$_POST['current_water'];
            $last_month_water = (float)$_POST['last_month_water'];

            $difference_water = $current_water - $last_month_water;
            $Water_total = $difference_water * 22; // Calculate cost based on water usage

            saveWaterMeter($conn, $room_number, $current_water, $difference_water);

            $total += $Water_total;
        }
    }

    if (isset($_POST['save'])) {
        $room_number = $_POST['room_number'];
        $current_electric = (float)$_POST['current_electric'];
        $last_month_electric = (float)$_POST['last_month_electric'];
        $difference_electric = $current_electric - $last_month_electric;
        $Electricity_total = $difference_electric * 7;
        $price = (float)$_POST['price'];
        $total = $Electricity_total + $price;

        if ($room_number == 'S1') {
            $current_water = (float)$_POST['current_water'];
            $last_month_water = (float)$_POST['last_month_water'];

            $difference_water = $current_water - $last_month_water;
            $Water_total = $difference_water * 22; // Calculate cost based on water usage

            $total += $Water_total;
        }

        if (saveBill($conn, $room_number, $Electricity_total, $Water_total, $price, $total, $difference_electric, $difference_water)) {
            echo "<script>alert('บันทึกข้อมูลเรียบร้อย');</script>";
        } else {
            echo "Error: ไม่สามารถบันทึกข้อมูลได้";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้าหลัก</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="total.css">
</head>
<body>
    <nav class="navbar">
        <img src="http://localhost/Apartment_V3/Admin/Ap.png" alt="เจ้าสัว Apartment Logo" class="logo-navbar">
    </nav>
    
    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-menu">
                <a href="index.php"><i class="fas fa-home"></i>หน้าหลัก</a>
                <a href="crud.php"><i class="fas fa-users"></i>จัดการข้อมูลผู้ใช้</a>
                <a href="total.php"><i class="fas fa-tint"></i>การคำนวณค่าน้ำ-ค่าไฟ</a>
                <a href="bill.php"><i class="fas fa-file-invoice"></i>พิมพ์เอกสาร</a>
                <a href="bill_back.php"><i class="fas fa-history"></i> รายการย้อนหลัง</a>
                <a href=""><i class="fas fa-chart-bar"></i> สรุปยอดรายเดือน/ปี</a>
            </div>
        </aside>
        
        <main class="main-content">
            <?php if (empty($room_number)) { ?>
                <h1>ระบบคำนวณค่าใช้จ่ายห้องพัก</h1>
                <div class="card">
                    <h2>เลือกห้องพัก</h2>
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
                        <div class="form-group">
                            <label for="room_number">หมายเลขห้อง:</label>
                            <select id="room_number" name="room_number" required>
                                <option value="">เลือกหมายเลขห้อง</option>
                                <?php if (!empty($roomNumbers)) {
                                    while ($row = $roomNumbers->fetch_assoc()) {
                                        echo "<option value='" . htmlspecialchars($row['Room_number']) . "'>" . htmlspecialchars($row['Room_number']) . "</option>";
                                    }
                                } ?>
                            </select>
                        </div>
                        <button type="submit" name="fetch">ยืนยัน</button>
                    </form>
                </div>
            <?php } else { ?>
                <div class="card">
                    <h2>ข้อมูลผู้ใช้</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <strong>หมายเลขห้อง:</strong> <?php echo htmlspecialchars($room_number); ?>
                        </div>
                        <div class="info-item">
                            <strong>ชื่อ-นามสกุล:</strong> <?php echo htmlspecialchars($first_name . ' ' . $last_name); ?>
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
                    </div>
                </div>
                
                <div class="card">
                    <h2>คำนวณค่าไฟฟ้าและค่าน้ำ</h2>
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
                        <div class="form-group">
                            <label for="current_electric">เลขมิเตอร์ไฟฟ้าปัจจุบัน:</label>
                            <input type="number" id="current_electric" name="current_electric" required>
                            <input type="hidden" name="last_month_electric" value="<?php echo htmlspecialchars($last_month_electric); ?>">
                            <input type="hidden" name="room_number" value="<?php echo htmlspecialchars($room_number); ?>">
                            <input type="hidden" name="price" value="<?php echo htmlspecialchars($price); ?>">
                        </div>
                        <div class="form-group">
                            <label for="last_month_electric">เลขมิเตอร์ไฟฟ้าครั้งก่อน:</label>
                            <input type="number" id="last_month_electric" value="<?php echo htmlspecialchars($last_month_electric); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="unit_price">ราคาต่อหน่วย:</label>
                            <input type="number" id="unit_price" value="7" readonly>
                        </div>
                        <?php if ($room_number == 'S1') { ?>
                            <div class="form-group">
                                <label for="current_water">เลขมิเตอร์น้ำปัจจุบัน:</label>
                                <input type="number" id="current_water" name="current_water" required>
                                <input type="hidden" name="last_month_water" value="<?php echo htmlspecialchars($last_month_water); ?>">
                            </div>
                            <div class="form-group">
                                <label for="last_month_water">เลขมิเตอร์น้ำครั้งก่อน:</label>
                                <input type="number" id="last_month_water" value="<?php echo htmlspecialchars($last_month_water); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label for="unit_price_water">ราคาต่อหน่วยน้ำ:</label>
                                <input type="number" id="unit_price_water" value="22" readonly>
                            </div>
                        <?php } ?>
                        <button type="submit" name="calculate">คำนวณ</button>
                    </form>
                </div>
                
                <?php if (isset($_POST['calculate'])) { ?>
                    <div class="card">
                        <h2>ยอดชำระค่าใช้จ่ายประจำเดือน</h2>
                        <p>ผลต่างของเลขมิเตอร์ไฟฟ้า: <?php echo htmlspecialchars($difference_electric); ?> หน่วย</p>
                        <p>ยอดค่าไฟ: <?php echo htmlspecialchars($Electricity_total); ?> บาท</p>

                        <?php if ($room_number == 'S1') { ?>
                            <p>ผลต่างของเลขมิเตอร์น้ำ: <?php echo htmlspecialchars($difference_water); ?> หน่วย</p>
                            <p>ยอดค่าน้ำ: <?php echo htmlspecialchars($Water_total); ?> บาท</p>
                        <?php } ?>

                        <p>ค่าห้องพัก: <?php echo htmlspecialchars($price); ?> บาท</p>
                        <p>ค่าใช้จ่ายทั้งหมด: <?php echo htmlspecialchars($total); ?> บาท</p>

                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" style="margin-top: 1rem;">
                            <input type="hidden" name="room_number" value="<?php echo htmlspecialchars($room_number); ?>">
                            <input type="hidden" name="current_electric" value="<?php echo htmlspecialchars($current_electric); ?>">
                            <input type="hidden" name="last_month_electric" value="<?php echo htmlspecialchars($last_month_electric); ?>">
                            <input type="hidden" name="price" value="<?php echo htmlspecialchars($price); ?>">
                            <input type="hidden" name="Electricity_total" value="<?php echo htmlspecialchars($Electricity_total); ?>">
                            <input type="hidden" name="total" value="<?php echo htmlspecialchars($total); ?>">
                            
                            <?php if ($room_number == 'S1') { ?>
                                <input type="hidden" name="current_water" value="<?php echo htmlspecialchars($current_water); ?>">
                                <input type="hidden" name="last_month_water" value="<?php echo htmlspecialchars($last_month_water); ?>">
                            <?php } ?>
                            <button type="submit" name="save">บันทึกข้อมูล</button>
                        </form>
                    </div>
                <?php } ?>
            <?php } ?>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');

            form.addEventListener('submit', function(event) {
                const currentElectric = parseFloat(document.getElementById('current_electric').value);
                const lastMonthElectric = parseFloat(document.getElementById('last_month_electric').value);
                const currentWater = parseFloat(document.getElementById('current_water') ? document.getElementById('current_water').value : 0);
                const lastMonthWater = parseFloat(document.getElementById('last_month_water') ? document.getElementById('last_month_water').value : 0);

                if (currentElectric < lastMonthElectric || currentElectric < 0) {
                    alert('เลขมิเตอร์ไฟฟ้าปัจจุบันต้องไม่ต่ำกว่าเลขมิเตอร์ครั้งก่อน และต้องเป็นจำนวนที่ไม่ติดลบ');
                    event.preventDefault();
                    return;
                }

                if (document.getElementById('current_water')) {
                    if (currentWater < lastMonthWater || currentWater < 0) {
                        alert('เลขมิเตอร์น้ำปัจจุบันต้องไม่ต่ำกว่าเลขมิเตอร์ครั้งก่อน และต้องเป็นจำนวนที่ไม่ติดลบ');
                        event.preventDefault();
                        return;
                    }
                }
            });
        });
    </script>
</body>
</html>
