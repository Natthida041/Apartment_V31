<?php
session_start(); // Start the session

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Include config file
require_once "config.php";

// Set username variable
$username = isset($_SESSION["username"]) ? htmlspecialchars($_SESSION["username"], ENT_QUOTES, 'UTF-8') : "ผู้ใช้";


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

function calculateWaterCost($room_number) {
    $rooms_150 = ['201', '202', '302', '303', '304', '305', '306'];
    $rooms_200 = ['203', '204', '205', '206', '301'];

    return in_array($room_number, $rooms_150) ? 150 : (in_array($room_number, $rooms_200) ? 200 : 0);
}

function getUserDetails($conn, $room_number) {
    try {
        $stmt = $conn->prepare("SELECT u.Room_number, u.First_name, u.Last_name, t.price, t.type_name FROM users u JOIN type t ON u.type_id = t.id WHERE u.Room_number = ?");
        $stmt->bind_param("s", $room_number);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->num_rows > 0 ? $result->fetch_assoc() : null;
    } catch (Exception $e) {
        error_log($e->getMessage());
        return null;
    }
}

function getLastMonthElectric($conn, $room_number) {
    try {
        $stmt = $conn->prepare("SELECT meter_electric FROM electric WHERE user_id = (SELECT id FROM users WHERE Room_number = ?) ORDER BY date_record DESC LIMIT 1");
        $stmt->bind_param("s", $room_number);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->num_rows > 0 ? $result->fetch_assoc()['meter_electric'] : 0;
    } catch (Exception $e) {
        error_log($e->getMessage());
        return 0;
    }
}

function getLastMonthWater($conn, $room_number) {
    try {
        $stmt = $conn->prepare("SELECT meter_water FROM water WHERE user_id = (SELECT id FROM users WHERE Room_number = ?) ORDER BY date_record DESC LIMIT 1");
        $stmt->bind_param("s", $room_number);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->num_rows > 0 ? $result->fetch_assoc()['meter_water'] : 0;
    } catch (Exception $e) {
        error_log($e->getMessage());
        return 0;
    }
}

function saveElectricMeter($conn, $room_number, $current_electric) {
    try {
        $stmt = $conn->prepare("INSERT INTO electric (user_id, meter_electric, date_record) VALUES ((SELECT id FROM users WHERE Room_number = ?), ?, CURDATE())");
        $stmt->bind_param("sd", $room_number, $current_electric);
        return $stmt->execute();
    } catch (Exception $e) {
        error_log($e->getMessage());
        return false;
    }
}

function saveWaterMeter($conn, $room_number, $current_water) {
    try {
        $stmt = $conn->prepare("INSERT INTO water (user_id, meter_water, date_record) VALUES ((SELECT id FROM users WHERE Room_number = ?), ?, CURDATE())");
        $stmt->bind_param("sd", $room_number, $current_water);
        return $stmt->execute();
    } catch (Exception $e) {
        error_log($e->getMessage());
        return false;
    }
}

function saveBill($conn, $room_number, $Electricity_total, $Water_total, $price, $total) {
    $water_cost = calculateWaterCost($room_number);
    $total_with_water = $total + $water_cost;

    try {
        $stmt = $conn->prepare("INSERT INTO bill (user_id, month, year, electric_cost, water_cost, room_cost, total_cost, Room_number) VALUES ((SELECT id FROM users WHERE Room_number = ?), MONTH(CURDATE()), YEAR(CURDATE()), ?, ?, ?, ?, ?)");
        $stmt->bind_param("sddddd", $room_number, $Electricity_total, $Water_total, $price, $total_with_water, $room_number);
        return $stmt->execute();
    } catch (Exception $e) {
        error_log($e->getMessage());
        return false;
    }
}

function fetchRoomNumbers($conn) {
    try {
        $result = $conn->query("SELECT Room_number FROM users");
        return $result->num_rows > 0 ? $result : [];
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
            $first_name = $userDetails['First_name'];
            $last_name = $userDetails['Last_name'];
            $price = $userDetails['price'];
            $type_name = $userDetails['type_name'];
        }

        $last_month_electric = getLastMonthElectric($conn, $room_number);
        $last_month_water = getLastMonthWater($conn, $room_number);
    }

    if (isset($_POST['calculate'])) {
        $current_electric = isset($_POST['current_electric']) ? floatval($_POST['current_electric']) : 0;
        $last_month_electric = isset($_POST['last_month_electric']) ? floatval($_POST['last_month_electric']) : 0;
        $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;

        if ($current_electric < $last_month_electric || $current_electric < 0) {
            echo "<script>alert('เลขมิเตอร์ไฟฟ้าปัจจุบันต้องไม่ต่ำกว่าเลขมิเตอร์ครั้งก่อน และต้องเป็นจำนวนที่ไม่ติดลบ');</script>";
        } else {
            $difference_electric = $current_electric - $last_month_electric;
            $Electricity_total = $difference_electric * 7;
            $total = $Electricity_total + $price;

            if (!empty($room_number)) {
                saveElectricMeter($conn, $room_number, $current_electric);
            }

            if ($room_number == 'S1') {
                $current_water = isset($_POST['current_water']) ? floatval($_POST['current_water']) : 0;
                $last_month_water = isset($_POST['last_month_water']) ? floatval($_POST['last_month_water']) : 0;

                if ($current_water < $last_month_water || $current_water < 0) {
                    echo "<script>alert('เลขมิเตอร์น้ำปัจจุบันต้องไม่ต่ำกว่าเลขมิเตอร์ครั้งก่อน และต้องเป็นจำนวนที่ไม่ติดลบ');</script>";
                } else {
                    $difference_water = $current_water - $last_month_water;
                    $Water_total = $difference_water * 22;

                    if (!empty($room_number)) {
                        saveWaterMeter($conn, $room_number, $current_water);
                    }

                    $total += $Water_total;
                }
            }
        }
    }

    if (isset($_POST['save'])) {
        $room_number = $_POST['room_number'];
        $current_electric = isset($_POST['current_electric']) ? floatval($_POST['current_electric']) : 0;
        $last_month_electric = isset($_POST['last_month_electric']) ? floatval($_POST['last_month_electric']) : 0;
        $Electricity_total = ($current_electric - $last_month_electric) * 7;
        $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
        $total = $Electricity_total + $price;

        if ($room_number == 'S1') {
            $current_water = isset($_POST['current_water']) ? floatval($_POST['current_water']) : 0;
            $last_month_water = isset($_POST['last_month_water']) ? floatval($_POST['last_month_water']) : 0;

            if ($current_water < $last_month_water || $current_water < 0) {
                echo "<script>alert('เลขมิเตอร์น้ำปัจจุบันต้องไม่ต่ำกว่าเลขมิเตอร์ครั้งก่อน และต้องเป็นจำนวนที่ไม่ติดลบ');</script>";
            } else {
                $Water_total = ($current_water - $last_month_water) * 22;
                $total += $Water_total;
            }
        }

        if (saveBill($conn, $room_number, $Electricity_total, $Water_total, $price, $total)) {
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
    <link rel="stylesheet" href="index_t.css">
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
                <a href="index.php"><i class="fas fa-tint"></i>การคำนวณค่าน้ำ-ค่าไฟ</a>
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
                                    echo "<option value='" . $row['Room_number'] . "'>" . $row['Room_number'] . "</option>";
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
                    <input type="hidden" name="room_number" value="<?php echo htmlspecialchars($room_number); ?>">
                    <input type="hidden" name="price" value="<?php echo htmlspecialchars($price); ?>">
                    <button type="submit" name="calculate">คำนวณ</button>
                </form>
            </div>
            
            <?php if (isset($_POST['calculate'])) { ?>
            <div class="card">
                <h2>ผลการคำนวณ</h2>
                <table>
                    <thead>
                        <tr>
                            <th>รายการ</th>
                            <th>จำนวน</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>ผลต่างของเลขมิเตอร์ไฟฟ้า</td>
                            <td><?php echo htmlspecialchars($difference_electric); ?> หน่วย</td>
                        </tr>
                        <tr>
                            <td>ยอดค่าไฟ</td>
                            <td><?php echo htmlspecialchars($Electricity_total); ?> บาท</td>
                        </tr>
                        <?php if ($room_number == 'S1') { ?>
                        <tr>
                            <td>ผลต่างของเลขมิเตอร์น้ำ</td>
                            <td><?php echo htmlspecialchars($difference_water); ?> หน่วย</td>
                        </tr>
                        <tr>
                            <td>ยอดค่าน้ำ</td>
                            <td><?php echo htmlspecialchars($Water_total); ?> บาท</td>
                        </tr>
                        <?php } ?>
                        <tr>
                            <td>ค่าห้องพัก</td>
                            <td><?php echo htmlspecialchars($price); ?> บาท</td>
                        </tr>
                        <tr>
                            <td><strong>ค่าใช้จ่ายทั้งหมด</strong></td>
                            <td><strong><?php echo htmlspecialchars($total); ?> บาท</strong></td>
                        </tr>
                    </tbody>
                </table>
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
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
        }
    </script>
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
