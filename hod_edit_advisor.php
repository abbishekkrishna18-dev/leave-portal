<?php
session_start();

require_once "db.php";

/* =========================
   HOD LOGIN CHECK
========================= */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hod') {
    header("Location: index.php");
    exit;
}

$advisor_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($advisor_id <= 0) {
    $_SESSION['error'] = "Invalid advisor ID.";
    header("Location: hod_class_advisor.php");
    exit;
}

/* =========================
   FETCH ADVISOR
========================= */
$stmt = $conn->prepare("
    SELECT id, username, name, email
    FROM users
    WHERE id = ? AND role = 'advisor'
    LIMIT 1
");

$stmt->bind_param("i", $advisor_id);
$stmt->execute();

$result = $stmt->get_result();
$advisor = $result->fetch_assoc();
$stmt->close();

if (!$advisor) {
    $_SESSION['error'] = "Advisor not found.";
    header("Location: hod_class_advisor.php");
    exit;
}

$current_page = "hod_edit_advisor.php";

include "sidebar.php";
include "topbar.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Edit Advisor | MKCE Leave Portal</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
    >

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>

        body {
            background: #f8f9fc;
        }

        .main-content {
            margin-left: 250px;
            padding: 90px 30px 30px;
        }

        .edit-card {
            max-width: 750px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .page-title {
            font-weight: 700;
            color: #1a1c23;
        }

        .form-label {
            font-weight: 600;
        }

        .btn-primary {
            background: #4e73df;
            border-color: #4e73df;
        }

        .btn-primary:hover {
            background: #2e59d9;
            border-color: #2e59d9;
        }

        .info-box {
            background: #f1f5ff;
            border-left: 4px solid #4e73df;
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 25px;
        }

    </style>

</head>

<body>

<div class="main-content">

    <div class="edit-card">

        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>
                <h3 class="page-title mb-1">
                    <i class="fa-solid fa-user-pen me-2"></i>
                    Edit Advisor
                </h3>

                <p class="text-muted mb-0">
                    Update advisor account information
                </p>
            </div>

            <a href="hod_class_advisor.php"
               class="btn btn-outline-secondary">

                <i class="fa-solid fa-arrow-left me-1"></i>
                Back

            </a>

        </div>


        <div class="info-box">

            <i class="fa-solid fa-circle-info me-2"></i>

            You can update the advisor's name, username and email.
            Leave the password fields empty if you do not want to change
            the password.

        </div>


        <form action="hod_edit_advisor_action.php"
              method="POST"
              id="editAdvisorForm">

            <input
                type="hidden"
                name="advisor_id"
                value="<?php echo $advisor['id']; ?>"
            >


            <!-- NAME -->

            <div class="mb-3">

                <label class="form-label">
                    Full Name
                </label>

                <input
                    type="text"
                    name="name"
                    class="form-control"
                    value="<?php echo htmlspecialchars($advisor['name']); ?>"
                    required
                    maxlength="100"
                >

            </div>


            <!-- USERNAME -->

            <div class="mb-3">

                <label class="form-label">
                    Username
                </label>

                <input
                    type="text"
                    name="username"
                    class="form-control"
                    value="<?php echo htmlspecialchars($advisor['username']); ?>"
                    required
                    maxlength="50"
                    pattern="[A-Za-z0-9_]{3,50}"
                >

                <small class="text-muted">
                    Only letters, numbers and underscore are allowed.
                </small>

            </div>


            <!-- EMAIL -->

            <div class="mb-3">

                <label class="form-label">
                    Email Address
                </label>

                <input
                    type="email"
                    name="email"
                    class="form-control"
                    value="<?php echo htmlspecialchars($advisor['email'] ?? ''); ?>"
                    required
                    maxlength="150"
                >

            </div>


            <hr class="my-4">


            <h5 class="mb-3">
                <i class="fa-solid fa-lock me-2"></i>
                Change Password
            </h5>


            <!-- PASSWORD -->

            <div class="mb-3">

                <label class="form-label">
                    New Password
                </label>

                <input
                    type="password"
                    name="password"
                    id="password"
                    class="form-control"
                    minlength="6"
                    placeholder="Leave blank to keep current password"
                >

            </div>


            <!-- CONFIRM PASSWORD -->

            <div class="mb-4">

                <label class="form-label">
                    Confirm New Password
                </label>

                <input
                    type="password"
                    name="confirm_password"
                    id="confirm_password"
                    class="form-control"
                    minlength="6"
                    placeholder="Leave blank to keep current password"
                >

            </div>


            <div class="d-flex justify-content-end gap-2">

                <a
                    href="hod_class_advisor.php"
                    class="btn btn-secondary"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="btn btn-primary"
                >

                    <i class="fa-solid fa-save me-1"></i>
                    Save Changes

                </button>

            </div>

        </form>

    </div>

</div>


<script>

document.getElementById("editAdvisorForm").addEventListener("submit", function(e) {

    const password = document.getElementById("password").value;
    const confirmPassword =
        document.getElementById("confirm_password").value;

    if (password !== "" || confirmPassword !== "") {

        if (password.length < 6) {

            e.preventDefault();

            Swal.fire({
                icon: "error",
                title: "Invalid Password",
                text: "Password must contain at least 6 characters."
            });

            return;
        }

        if (password !== confirmPassword) {

            e.preventDefault();

            Swal.fire({
                icon: "error",
                title: "Passwords Do Not Match",
                text: "Please enter the same password in both fields."
            });

            return;
        }

    }

});

</script>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js">
</script>

</body>
</html>