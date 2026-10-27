<?php

session_start();
require_once "db.php";

// --------------------------------------------------
// HOD LOGIN CHECK
// --------------------------------------------------

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hod') {
    header("Location: index.php");
    exit();
}

$success = $_SESSION['success'] ?? '';
$error   = $_SESSION['error'] ?? '';

unset($_SESSION['success'], $_SESSION['error']);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Add Advisor | MKCE Leave Portal</title>

    <link rel="icon" href="image/mkce.png">

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
    >

    <!-- Common CSS -->
    <link rel="stylesheet" href="style.css">

    <style>

        .page-header {
            background: linear-gradient(
                135deg,
                #4e73df,
                #1cc88a
            );

            color: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
        }

        .page-header h2 {
            margin: 0;
            font-weight: 700;
        }

        .page-header p {
            margin: 5px 0 0;
            opacity: 0.9;
        }

        .form-card {
            max-width: 750px;
            margin: 0 auto;

            background: white;

            border-radius: 15px;

            box-shadow:
                0 5px 20px rgba(0,0,0,0.08);

            border: none;
        }

        .card-title {
            font-weight: 700;
            color: #333;
        }

        .form-label {
            font-weight: 600;
            color: #444;
        }

        .form-control {
            min-height: 46px;
            border-radius: 8px;
        }

        .form-control:focus {
            border-color: #4e73df;

            box-shadow:
                0 0 0 0.2rem
                rgba(78,115,223,0.15);
        }

        .advisor-icon {
            width: 70px;
            height: 70px;

            margin: 0 auto 20px;

            border-radius: 50%;

            background:
                linear-gradient(
                    135deg,
                    #4e73df,
                    #1cc88a
                );

            color: white;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 28px;
        }

        .add-btn {
            background: #4e73df;
            border: none;
            color: white;

            border-radius: 8px;

            padding: 12px 25px;

            font-weight: 600;

            transition: 0.2s;
        }

        .add-btn:hover {
            background: #375dcc;
            color: white;
            transform: translateY(-1px);
        }

        .back-btn {
            border-radius: 8px;
            padding: 11px 20px;
            font-weight: 600;
        }

        .info-box {
            background: #f8f9fc;

            border-left:
                4px solid #4e73df;

            border-radius: 8px;

            padding: 15px;

            margin-top: 20px;
        }

        .info-box i {
            color: #4e73df;
        }

    </style>

</head>

<body>

<?php include "sidebar.php"; ?>

<?php include "topbar.php"; ?>


<div class="main-content">

    <div class="container-fluid py-4">


        <!-- PAGE HEADER -->

        <div class="page-header">

            <h2>

                <i class="fa-solid fa-user-plus me-2"></i>

                Add New Class Advisor

            </h2>

            <p>

                Create a new Class Advisor account for the portal.

            </p>

        </div>


        <!-- SUCCESS MESSAGE -->

        <?php if ($success): ?>

            <div
                class="alert alert-success alert-dismissible fade show"
            >

                <i class="fa-solid fa-circle-check me-2"></i>

                <?= htmlspecialchars($success) ?>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert">
                </button>

            </div>

        <?php endif; ?>


        <!-- ERROR MESSAGE -->

        <?php if ($error): ?>

            <div
                class="alert alert-danger alert-dismissible fade show"
            >

                <i class="fa-solid fa-circle-exclamation me-2"></i>

                <?= htmlspecialchars($error) ?>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert">
                </button>

            </div>

        <?php endif; ?>


        <!-- FORM CARD -->

        <div class="card form-card">

            <div class="card-body p-4 p-md-5">


                <!-- ICON -->

                <div class="advisor-icon">

                    <i class="fa-solid fa-user-tie"></i>

                </div>


                <h4 class="card-title text-center mb-1">

                    Create Advisor Account

                </h4>

                <p class="text-muted text-center mb-4">

                    Enter the details of the new Class Advisor.

                </p>


                <!-- FORM -->

                <form
                    action="hod_add_advisor_action.php"
                    method="POST"
                    autocomplete="off"
                >


                    <!-- NAME -->

                    <div class="mb-3">

                        <label
                            for="name"
                            class="form-label"
                        >

                            Full Name

                        </label>

                        <input
                            type="text"
                            id="name"
                            name="name"
                            class="form-control"
                            placeholder="Enter advisor full name"
                            maxlength="100"
                            required
                        >

                    </div>


                    <!-- USERNAME -->

                    <div class="mb-3">

                        <label
                            for="username"
                            class="form-label"
                        >

                            Username

                        </label>

                        <input
                            type="text"
                            id="username"
                            name="username"
                            class="form-control"
                            placeholder="Enter login username"
                            maxlength="50"
                            required
                        >

                        <small class="text-muted">

                            This username will be used by the advisor to log in.

                        </small>

                    </div>


                    <!-- EMAIL -->

                    <div class="mb-3">

                        <label
                            for="email"
                            class="form-label"
                        >

                            Email Address

                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control"
                            placeholder="advisor@gmail.com"
                            maxlength="150"
                            required
                        >

                        <small class="text-muted">

                            This email can be used for future portal notifications.

                        </small>

                    </div>


                    <!-- PASSWORD -->

                    <div class="mb-3">

                        <label
                            for="password"
                            class="form-label"
                        >

                            Login Password

                        </label>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            placeholder="Enter login password"
                            minlength="6"
                            maxlength="100"
                            required
                        >

                        <small class="text-muted">

                            Minimum 6 characters.

                        </small>

                    </div>


                    <!-- CONFIRM PASSWORD -->

                    <div class="mb-4">

                        <label
                            for="confirm_password"
                            class="form-label"
                        >

                            Confirm Password

                        </label>

                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            class="form-control"
                            placeholder="Re-enter password"
                            minlength="6"
                            maxlength="100"
                            required
                        >

                    </div>


                    <!-- INFORMATION -->

                    <div class="info-box">

                        <i class="fa-solid fa-circle-info me-2"></i>

                        <strong>Important:</strong>

                        The account created through this form will
                        automatically have the role

                        <strong>Advisor</strong>.

                        The HOD does not need to manually insert
                        the account into the database.

                    </div>


                    <!-- BUTTONS -->

                    <div
                        class="d-flex justify-content-between
                               align-items-center gap-2 mt-4"
                    >

                        <a
                            href="hod_class_advisor.php"
                            class="btn btn-outline-secondary back-btn"
                        >

                            <i class="fa-solid fa-arrow-left me-2"></i>

                            Back

                        </a>


                        <button
                            type="submit"
                            class="btn add-btn"
                        >

                            <i class="fa-solid fa-user-plus me-2"></i>

                            Create Advisor

                        </button>

                    </div>


                </form>

            </div>

        </div>

    </div>

</div>


<?php include "footer.php"; ?>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js">
</script>


<script>

// --------------------------------------------------
// PASSWORD MATCH CHECK
// --------------------------------------------------

document.querySelector("form").addEventListener(
    "submit",
    function(event) {

        const password =
            document.getElementById("password").value;

        const confirmPassword =
            document.getElementById("confirm_password").value;

        if (password !== confirmPassword) {

            event.preventDefault();

            alert("Passwords do not match.");

        }

    }
);

</script>

</body>

</html>