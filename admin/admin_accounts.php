<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tshonline";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Promote user
if (isset($_POST['promote'])) {
    $user_id = intval($_POST['user_id']);
    $role = $_POST['role'];

    $stmt = $conn->prepare("UPDATE users SET is_admin = 1, role = ? WHERE id = ?");
    $stmt->bind_param("si", $role, $user_id);
    $stmt->execute();
    $stmt->close();

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

if (isset($_POST['promote']) || isset($_POST['update_role'])) {
    $user_id = intval($_POST['user_id']);
    $role = $_POST['role'];
    $position_title = $_POST['position_title'];

    // Check if position is already taken for Executive/Section Head
    if (in_array($role, ['Executive','Section Head'])) {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE role=? AND position_title=? AND id<>?");
        $stmt->bind_param("ssi", $role, $position_title, $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        if ($result['count'] > 0) {
            die("Error: This position is already assigned to another $role.");
        }
        $stmt->close();
    }

    // Update user
    if ($role === 'User') {
        $stmt = $conn->prepare("UPDATE users SET is_admin=0, role='User', position_title=NULL WHERE id=?");
        $stmt->bind_param("i", $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET is_admin=1, role=?, position_title=? WHERE id=?");
        $stmt->bind_param("ssi", $role, $position_title, $user_id);
    }

    $stmt->execute();
    $stmt->close();
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}


// Fetch all users (both admins and normal users)
$sql = "SELECT id, username, name, email, profile_pic, is_admin, role, position_title FROM users ORDER BY id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin - Accounts</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../adminstyles/admin-style.css">
<link rel="stylesheet" href="../adminstyles/admin-articles.css">
<link rel="stylesheet" href="../adminstyles/admin_accounts.css">
</head>

<body>
    <button id="hamburgerBtn" class="hamburger-btn">
        <i class="fa-solid fa-bars"></i>
    </button>
<div class="dashboard-container">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main content -->
    <main class="main-content">
        <!-- Header -->
        <div class="accounts-header">
            <h1>Accounts</h1>
            <div class="toggle-switch" id="toggleSwitch">
                <span id="userBtn" class="active">Users</span>
                <span id="adminBtn">Admins</span>
            </div>
        </div>

        <!-- ===== Filter by Role (hidden by default, shown only in admin view) ===== -->
        <div class="category-filter-row" id="roleFilterContainer" style="display: none;">
            <div class="filter-wrapper">
                <label for="roleFilter">Filter by Role:</label>
                <select id="roleFilter">
                    <option value="all" selected>All Roles</option>
                    <option value="Executive">Executive</option>
                    <option value="Staff">Staff</option>
                    <option value="Section Head">Section Head</option>
                </select>

            </div>
        </div>

        <!-- Accounts Table -->
        <div class="table-container">
            <?php if ($result && $result->num_rows > 0): ?>
                <table class="accounts-table" id="accountsTable">
                    <thead>
                        <tr>
                            <th>Profile</th>
                            <th>Username</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr class="<?php echo $row['is_admin'] == 1 ? 'admin-row' : 'user-row'; ?>">
                                <td>
                                    <img src="../<?php echo htmlspecialchars($row['profile_pic']); ?>" alt="Profile" class="profile-img">
                                </td>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td>
                                    <?php if ($row['is_admin'] == 0): ?>
                                        <!-- Promote form: only choose role -->
                                        <form method="POST" class="promote-form">
                                            <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                                            <select name="role" required class="promote-select">
                                                <option value="" disabled selected>Choose role</option>
                                                <option value="Executive">Executive</option>
                                                <option value="Staff">Staff</option>
                                                <option value="Section Head">Section Head</option>
                                            </select>
                                            <button type="submit" name="promote" class="promote-btn">Promote</button>
                                        </form>

                                    <?php else: ?>
                                        <!-- Admin form: choose role + position -->
                                        <form method="POST" class="promote-form">
                                            <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                                            <select name="role" required class="promote-select">
                                                <option value="Executive" <?php if($row['role']=='Executive') echo 'selected'; ?>>Executive</option>
                                                <option value="Staff" <?php if($row['role']=='Staff') echo 'selected'; ?>>Staff</option>
                                                <option value="Section Head" <?php if($row['role']=='Section Head') echo 'selected'; ?>>Section Head</option>
                                                <option value="User" <?php if($row['role']=='User') echo 'selected'; ?>>Demote</option>
                                            </select>

                                            <!-- Position selection: only required for admin roles -->
                                            <?php if ($row['role'] != 'User'): ?>
                                                <select name="position_title" required class="promote-select">
                                                    <option value="" disabled selected>Choose Position</option>
                                                    <?php
                                                    $executives = ["Editor-in-Chief","Associate Editor","Managing Editor-Print","Managing Editor-Online","Circulations Head"];
                                                    $section_heads = ["News Editor","Social Media Manager","Features/ Literary Editor","DevComm Editor","Sports Editor","Social Media Manager","Head Photojournalist","Head Layout Artist","Head Cartoonist","Head Graphics Artist","Head Broadcast"];
                                                    $pub_staff = ["Cartoonist","Graphic Artist","Photojournalist","Video Technician","News Presenter", "Publication Staff", "Junior Herald"];

                                                    // Show positions based on role
                                                    $positions = [];
                                                    if ($row['role'] == 'Executive') $positions = $executives;
                                                    elseif ($row['role'] == 'Section Head') $positions = $section_heads;
                                                    elseif ($row['role'] == 'Staff') $positions = $pub_staff;

                                                    foreach ($positions as $pos) {
                                                        $selected = ($row['position_title'] === $pos) ? 'selected' : '';
                                                        echo "<option value='$pos' $selected>$pos</option>";
                                                    }
                                                    ?>
                                                </select>
                                            <?php endif; ?>

                                            <button type="submit" name="update_role" class="promote-btn">Update</button>
                                        </form>
                                    <?php endif; ?>
                                    </td>

                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-users">No accounts found.</div>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <footer style="margin-top: 30px; text-align: center; color: #888; font-size: 0.9em;">
            <p>&copy; <?php echo date('Y'); ?> TSH Admin Dashboard. All Rights Reserved.</p>
        </footer>
    </main>
</div>

<script>
// ===== TOGGLE USERS / ADMINS VIEW =====
const userBtn = document.getElementById("userBtn");
const adminBtn = document.getElementById("adminBtn");
const rows = document.querySelectorAll("#accountsTable tbody tr");
const roleFilterContainer = document.getElementById("roleFilterContainer");

function showUsers() {
    userBtn.classList.add("active");
    adminBtn.classList.remove("active");
    roleFilterContainer.style.display = "none"; // hide filter in user view
    rows.forEach(row => {
        row.style.display = row.classList.contains("user-row") ? "table-row" : "none";
    });
}

function showAdmins() {
    adminBtn.classList.add("active");
    userBtn.classList.remove("active");
    roleFilterContainer.style.display = "block"; // show filter only in admin view
    rows.forEach(row => {
        row.style.display = row.classList.contains("admin-row") ? "table-row" : "none";
    });
}

userBtn.addEventListener("click", showUsers);
adminBtn.addEventListener("click", showAdmins);

// Default: show only users at page load
showUsers();

// ===== ROLE FILTER FUNCTIONALITY =====
const roleFilter = document.getElementById("roleFilter");
if (roleFilter) {
    roleFilter.addEventListener("change", () => {
        const selectedRole = roleFilter.value.toLowerCase();
        const adminRows = document.querySelectorAll(".admin-row");

        adminRows.forEach(row => {
            const select = row.querySelector("select[name='role']");
            let currentRole = select ? select.options[select.selectedIndex].text.toLowerCase() : "user";

            if (selectedRole === "all" || currentRole === selectedRole) {
                row.style.display = "table-row";
            } else {
                row.style.display = "none";
            }
        });
    });
}
</script>

<script>
const hamburgerBtn = document.getElementById('hamburgerBtn');
const sidebar = document.querySelector('.sidebar');

hamburgerBtn.addEventListener('click', () => {
    sidebar.classList.toggle('show');
});

// Optional: close sidebar when clicking outside
document.addEventListener('click', e => {
    if (!sidebar.contains(e.target) && !hamburgerBtn.contains(e.target)) {
        sidebar.classList.remove('show');
    }
});
</script>

</body>
</html>
<!-- ang admin specific rolesssssssssssss