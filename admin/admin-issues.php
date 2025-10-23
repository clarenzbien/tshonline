<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
  header("Location: ../login.php");
  exit();
}

$conn = new mysqli("localhost", "root", "", "tshonline");
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// ✅ ADD issue
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
  $title = $_POST['title'];
  $link = $_POST['link'];
  $date_posted = $_POST['date_posted'] ?? date('Y-m-d'); // fallback to today

  $targetDir = "../uploads/footer/";
  if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
  $targetFile = $targetDir . basename($_FILES["cover_image"]["name"]);
  move_uploaded_file($_FILES["cover_image"]["tmp_name"], $targetFile);

  $relativePath = "uploads/footer/" . basename($_FILES["cover_image"]["name"]);
  $stmt = $conn->prepare("INSERT INTO footer_publications (title, link, cover_image, created_at) VALUES (?, ?, ?, ?)");
  $stmt->bind_param("ssss", $title, $link, $relativePath, $date_posted);
  $stmt->execute();
  $stmt->close();

  header("Location: admin-issues.php?success=Issue added successfully!");
  exit();
}

// ✅ EDIT issue
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
  $id = $_POST['id'];
  $title = $_POST['title'];
  $link = $_POST['link'];
  $date_posted = $_POST['date_posted'] ?? date('Y-m-d'); // use selected date

  $imagePath = $_POST['current_image'];
  if (isset($_FILES['cover_image']) && $_FILES['cover_image']['name'] != '') {
    $targetDir = "../uploads/footer/";
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
    $targetFile = $targetDir . basename($_FILES["cover_image"]["name"]);
    move_uploaded_file($_FILES["cover_image"]["tmp_name"], $targetFile);
    $imagePath = "uploads/footer/" . basename($_FILES["cover_image"]["name"]);
  }

  $stmt = $conn->prepare("UPDATE footer_publications SET title=?, link=?, cover_image=?, created_at=? WHERE id=?");
  $stmt->bind_param("ssssi", $title, $link, $imagePath, $date_posted, $id);
  $stmt->execute();
  $stmt->close();

  echo "success";
  exit();
}

// ✅ DELETE issue
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
  $id = $_POST['id'];

  // Delete image file
  $res = $conn->query("SELECT cover_image FROM footer_publications WHERE id=$id");
  if ($res && $row = $res->fetch_assoc()) {
    $filePath = "../" . $row['cover_image'];
    if (file_exists($filePath)) unlink($filePath);
  }

  $conn->query("DELETE FROM footer_publications WHERE id=$id");
  echo "deleted";
  exit();
}

// Fetch publications
$result = $conn->query("SELECT * FROM footer_publications ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Issues</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../adminstyles/admin-style.css">
  <link rel="stylesheet" href="../adminstyles/admin_issues.css">
  <style>
    .edit-btn {
      background: #ffc107;
      color: #000;
      padding: 6px 10px;
      border: none;
      border-radius: 5px;
      font-size: 13px;
      margin-top: 8px;
      cursor: pointer;
      transition: background 0.3s;
    }
    .edit-btn:hover { background: #e0a800; }

    .delete-btn {
      background: #dc3545;
      color: white;
      border: none;
      padding: 10px 15px;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 600;
      width: 100%;
      margin-top: 10px;
    }
    .delete-btn:hover { background: #b02a37; }
  </style>
</head>
<body>

  <button id="hamburgerBtn" class="hamburger-btn">
    <i class="fa-solid fa-bars"></i>
  </button>

  <div class="dashboard-container">
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
      <div class="issues-header">
        <h1>All Issues</h1>
        <button id="openModalBtn" class="add-btn">
          <i class="fa-solid fa-plus"></i> Add Issue
        </button>
      </div>

      <?php if (isset($_GET['success'])): ?>
        <div class="success-message"><?= htmlspecialchars($_GET['success']); ?></div>
      <?php endif; ?>

      <!-- Issues Grid -->
      <div class="grid">
        <?php while ($row = $result->fetch_assoc()): ?>
          <div class="item">
            <img src="../<?= htmlspecialchars($row['cover_image']); ?>" alt="">
            <p><strong><?= htmlspecialchars($row['title']); ?></strong></p>
            <p style="color:#777; font-size:13px; margin:4px 0;">
              <?= date("F j, Y", strtotime($row['created_at'])); ?>
            </p>
            <a href="<?= htmlspecialchars($row['link']); ?>" target="_blank">View Link</a>
            <br>
            <button class="edit-btn"
              data-id="<?= $row['id']; ?>"
              data-title="<?= htmlspecialchars($row['title']); ?>"
              data-link="<?= htmlspecialchars($row['link']); ?>"
              data-image="<?= htmlspecialchars($row['cover_image']); ?>"
              data-date="<?= date('Y-m-d', strtotime($row['created_at'])); ?>">
              <i class="fa-solid fa-pen"></i> Edit
            </button>
          </div>
        <?php endwhile; ?>
      </div>
    </main>
  </div>

  <!-- Add Issue Modal -->
  <div id="addIssueModal" class="modal">
    <div class="modal-content">
      <span class="close-btn">&times;</span>
      <h2>Add New Issue</h2>
      <form action="" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add">

        <label>Publication Title:</label>
        <input type="text" name="title" required>

        <label>Publication Link:</label>
        <input type="url" name="link" required>

        <label>Publication Date:</label>
        <input type="date" name="date_posted" required 
               value="<?= date('Y-m-d') ?>" 
               max="<?= date('Y-m-d') ?>">

        <label>Cover Image:</label>
        <input type="file" name="cover_image" accept="image/*" required>

        <button type="submit">Add Publication</button>
      </form>
    </div>
  </div>

  <!-- Edit Issue Modal -->
  <div id="editIssueModal" class="modal">
    <div class="modal-content">
      <span class="close-btn">&times;</span>
      <h2>Edit Issue</h2>
      <form id="editForm" enctype="multipart/form-data">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id">
        <input type="hidden" name="current_image">

        <label>Publication Title:</label>
        <input type="text" name="title" required>

        <label>Publication Link:</label>
        <input type="url" name="link" required>

        <label>Publication Date:</label>
        <input type="date" name="date_posted" required>

        <label>Change Cover Image (optional):</label>
        <input type="file" name="cover_image" accept="image/*">

        <button type="submit">Save Changes</button>
      </form>

      <!-- Delete Button inside modal -->
      <button id="deleteBtn" class="delete-btn">
        <i class="fa-solid fa-trash"></i> Delete Issue
      </button>
    </div>
  </div>

  <script>
  // Modals
  const openModalBtn = document.getElementById('openModalBtn');
  const addIssueModal = document.getElementById('addIssueModal');
  const editModal = document.getElementById('editIssueModal');
  const closeBtns = document.querySelectorAll('.close-btn');

  openModalBtn.addEventListener('click', () => addIssueModal.style.display = 'flex');
  closeBtns.forEach(btn => btn.addEventListener('click', () => btn.parentElement.parentElement.style.display = 'none'));
  window.addEventListener('click', e => { if (e.target.classList.contains('modal')) e.target.style.display = 'none'; });

  // Sidebar
  const hamburgerBtn = document.getElementById('hamburgerBtn');
  const sidebar = document.querySelector('.sidebar');
  hamburgerBtn.addEventListener('click', () => sidebar.classList.toggle('show'));
  document.addEventListener('click', e => {
    if (!sidebar.contains(e.target) && !hamburgerBtn.contains(e.target)) sidebar.classList.remove('show');
  });

  // Edit logic
  const editForm = document.getElementById('editForm');
  const deleteBtn = document.getElementById('deleteBtn');
  let currentEditId = null;

  document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      editModal.style.display = 'flex';
      currentEditId = btn.dataset.id;
      editForm.id.value = btn.dataset.id;
      editForm.title.value = btn.dataset.title;
      editForm.link.value = btn.dataset.link;
      editForm.current_image.value = btn.dataset.image;
      editForm.date_posted.value = btn.dataset.date; // set date in edit modal
    });
  });

  // ✅ Handle edit via AJAX
  editForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(editForm);
    const response = await fetch('', { method: 'POST', body: formData });
    const text = await response.text();

    if (text.trim() === 'success') {
      alert('Issue updated successfully!');
      location.reload();
    } else {
      alert('Error updating issue.');
      console.log(text);
    }
  });

  // ✅ Handle delete with confirmation
  deleteBtn.addEventListener('click', async () => {
    if (!currentEditId) return;
    if (!confirm('Are you sure you want to delete this issue?')) return;

    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', currentEditId);

    const response = await fetch('', { method: 'POST', body: formData });
    const text = await response.text();

    if (text.trim() === 'deleted') {
      alert('Issue deleted successfully!');
      location.reload();
    } else {
      alert('Error deleting issue.');
      console.log(text);
    }
  });
  </script>

</body>
</html>
