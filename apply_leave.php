<?php

session_start();

require_once "db.php";

/* =========================================================
   STUDENT LOGIN CHECK
   ========================================================= */

if (
    !isset($_SESSION["user_id"]) ||
    $_SESSION["role"] !== "student"
) {
    header("Location: index.php");
    exit;
}


$student_id   = (int) $_SESSION["user_id"];
$student_name = $_SESSION["name"] ?? "Student";

$message = "";
$success = false;


/* =========================================================
   FORM SUBMISSION
   ========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $department = trim($_POST["department"] ?? "");
    $class_name = trim($_POST["class_name"] ?? "");
    $leave_type = trim($_POST["leave_type"] ?? "");
    $date_from  = trim($_POST["date_from"] ?? "");
    $date_to    = trim($_POST["date_to"] ?? "");
    $reason     = trim($_POST["reason"] ?? "");


    /* =====================================================
       REQUIRED FIELDS
       ===================================================== */

    if (
        $department === "" ||
        $class_name === "" ||
        $leave_type === "" ||
        $date_from === "" ||
        $date_to === "" ||
        $reason === ""
    ) {

        $message = "All fields are required.";

    }


    /* =====================================================
       DATE VALIDATION
       ===================================================== */

    elseif ($date_to < $date_from) {

        $message =
            "Date To cannot be before Date From.";

    }


    else {

        try {

            $fromDate = new DateTime($date_from);
            $toDate   = new DateTime($date_to);

            $difference =
                $fromDate->diff($toDate);

            $no_of_days =
                $difference->days + 1;

        }

        catch (Exception $e) {

            $message =
                "Invalid date selected.";

        }


        /* =================================================
           OD 2-DAY ADVANCE RULE
           ================================================= */

        if ($message === "") {

            $normalized_type =
                strtolower(
                    str_replace(
                        ["-", "_"],
                        " ",
                        trim($leave_type)
                    )
                );

            $is_od =
                in_array(
                    $normalized_type,
                    [
                        "od",
                        "on duty"
                    ],
                    true
                );


            if ($is_od) {

                $today =
                    new DateTime(
                        date("Y-m-d")
                    );

                $minimum_od_date =
                    (clone $today)
                    ->modify("+2 days");


                if (
                    $fromDate <
                    $minimum_od_date
                ) {

                    $message =
                        "OD must be applied at least 2 days before the OD date. " .
                        "The earliest allowed date is " .
                        $minimum_od_date->format("d-m-Y") .
                        ".";

                }

            }

        }


        /* =================================================
           PROOF CHECK
           ================================================= */

        if ($message === "") {

            if (
                !isset($_FILES["proof"]) ||
                $_FILES["proof"]["error"] !== UPLOAD_ERR_OK
            ) {

                $message =
                    "Proof image is required.";

            }

        }


        /* =================================================
           PROCESS PROOF
           ================================================= */

        if ($message === "") {

            $file_name =
                $_FILES["proof"]["name"];

            $file_tmp =
                $_FILES["proof"]["tmp_name"];

            $file_size =
                (int) $_FILES["proof"]["size"];


            /* =============================================
               EXTENSION
               ============================================= */

            $extension =
                strtolower(
                    pathinfo(
                        $file_name,
                        PATHINFO_EXTENSION
                    )
                );


            $allowed_extensions = [
                "jpg",
                "jpeg",
                "png",
                "webp"
            ];


            if (
                !in_array(
                    $extension,
                    $allowed_extensions,
                    true
                )
            ) {

                $message =
                    "Only JPG, JPEG, PNG and WEBP images are allowed.";

            }


            /* =============================================
               FILE SIZE
               ============================================= */

            elseif (
                $file_size > 5 * 1024 * 1024
            ) {

                $message =
                    "Proof image must be less than 5 MB.";

            }


            else {

                /* =========================================
                   UPLOAD DIRECTORY
                   ========================================= */

                $upload_folder =
                    __DIR__ .
                    DIRECTORY_SEPARATOR .
                    "uploads";


                if (
                    !is_dir(
                        $upload_folder
                    )
                ) {

                    if (
                        !mkdir(
                            $upload_folder,
                            0777,
                            true
                        )
                    ) {

                        $message =
                            "Unable to create uploads folder.";

                    }

                }


                /* =========================================
                   WRITABLE CHECK
                   ========================================= */

                if (
                    $message === "" &&
                    !is_writable(
                        $upload_folder
                    )
                ) {

                    $message =
                        "Uploads folder is not writable.";

                }


                /* =========================================
                   CREATE UNIQUE FILE NAME
                   ========================================= */

                if ($message === "") {

                    $new_file_name =
                        time() .
                        "_" .
                        bin2hex(
                            random_bytes(5)
                        ) .
                        "." .
                        $extension;


                    $upload_path =
                        $upload_folder .
                        DIRECTORY_SEPARATOR .
                        $new_file_name;


                    /* =====================================
                       MOVE FILE
                       ===================================== */

                    if (
                        move_uploaded_file(
                            $file_tmp,
                            $upload_path
                        )
                    ) {


                        /* =================================
                           INSERT APPLICATION
                           ================================= */

                        $sql = "
                            INSERT INTO leaves
                            (
                                student_id,
                                student_name,
                                department,
                                class_name,
                                leave_type,
                                date_from,
                                date_to,
                                no_of_days,
                                reason,
                                proof,
                                advisor_status,
                                hod_status,
                                applied_at,
                                approval_deadline,
                                od_status
                            )
                            VALUES
                            (
                                ?,
                                ?,
                                ?,
                                ?,
                                ?,
                                ?,
                                ?,
                                ?,
                                ?,
                                ?,
                                'Pending',
                                'Pending',
                                NOW(),
                                DATE_ADD(
                                    NOW(),
                                    INTERVAL 2 DAY
                                ),
                                'Active'
                            )
                        ";


                        $stmt =
                            mysqli_prepare(
                                $conn,
                                $sql
                            );


                        if (!$stmt) {

                            if (
                                file_exists(
                                    $upload_path
                                )
                            ) {

                                unlink(
                                    $upload_path
                                );

                            }


                            $message =
                                "Database error: " .
                                mysqli_error(
                                    $conn
                                );

                        }


                        else {



                            /* =================================
                               PREPARE AGAIN WITH CORRECT TYPES
                               ================================= */

                            $stmt =
                                mysqli_prepare(
                                    $conn,
                                    $sql
                                );


                            if (!$stmt) {

                                if (
                                    file_exists(
                                        $upload_path
                                    )
                                ) {

                                    unlink(
                                        $upload_path
                                    );

                                }


                                $message =
                                    "Database error: " .
                                    mysqli_error(
                                        $conn
                                    );

                            }


                            else {

                                /*
                                 * i = student_id
                                 * s = student_name
                                 * s = department
                                 * s = class_name
                                 * s = leave_type
                                 * s = date_from
                                 * s = date_to
                                 * i = no_of_days
                                 * s = reason
                                 * s = proof
                                 */

                                mysqli_stmt_bind_param(
                                    $stmt,
                                    "issssssiss",
                                    $student_id,
                                    $student_name,
                                    $department,
                                    $class_name,
                                    $leave_type,
                                    $date_from,
                                    $date_to,
                                    $no_of_days,
                                    $reason,
                                    $new_file_name
                                );


                                if (
                                    mysqli_stmt_execute(
                                        $stmt
                                    )
                                ) {

                                    $success = true;

                                }


                                else {

                                    if (
                                        file_exists(
                                            $upload_path
                                        )
                                    ) {

                                        unlink(
                                            $upload_path
                                        );

                                    }


                                    $message =
                                        "Database error: " .
                                        mysqli_stmt_error(
                                            $stmt
                                        );

                                }


                                mysqli_stmt_close(
                                    $stmt
                                );

                            }

                        }

                    }

                    else {

                        $message =
                            "Failed to upload proof.";

                    }

                }

            }

        }

    }

}


/* =========================================================
   HTML
   ========================================================= */

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Apply OD / Leave | Leave Portal
    </title>


    <link
        rel="icon"
        type="image/png"
        href="image/icons/mkce_s.png"
    >


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
    >


    <script
        src="https://cdn.jsdelivr.net/npm/sweetalert2@11"
    ></script>


    <style>

        :root {

            --primary: #4e73df;

            --success: #1cc88a;

            --secondary: #858796;

        }


        body {

            background: #f8f9fc;

        }


        .main-content {

            margin-left: 250px;

            padding-top: 60px;

            padding-bottom: 85px;

            min-height: 100vh;

            transition:
                margin-left .3s ease;

        }


        .page-content {

            padding: 25px;

        }


        .breadcrumb-area {

            background:
                linear-gradient(
                    90deg,
                    #4e73df,
                    #1cc88a
                );

            padding:
                14px 25px;

            margin-bottom: 25px;

        }


        .breadcrumb {

            margin: 0;

        }


        .breadcrumb-item,
        .breadcrumb-item a {

            color: white;

            text-decoration: none;

        }


        .breadcrumb-item.active {

            color: rgba(
                255,
                255,
                255,
                .8
            );

        }


        .page-title {

            font-weight: 700;

            color: #343a40;

        }


        .form-card {

            border: none;

            border-radius: 12px;

            box-shadow:
                0 4px 15px
                rgba(
                    0,
                    0,
                    0,
                    .08
                );

        }


        .form-card .card-header {

            background:
                linear-gradient(
                    45deg,
                    #4e73df,
                    #1cc88a
                );

            color: white;

            border: none;

            border-radius:
                12px 12px 0 0;

        }


        .form-label {

            font-weight: 600;

        }


        .required {

            color: #e74a3b;

        }


        .form-control,
        .form-select {

            min-height: 45px;

            border-radius: 8px;

        }


        textarea.form-control {

            min-height: 120px;

        }


        .info-box {

            background:
                #eef3ff;

            border-left:
                4px solid #4e73df;

            padding: 15px;

            border-radius: 8px;

            margin-bottom: 20px;

        }


        .od-warning {

            background:
                #fff7df;

            border-left:
                4px solid #f6c23e;

            padding: 15px;

            border-radius: 8px;

            display: none;

        }


        .submit-btn {

            background:
                linear-gradient(
                    45deg,
                    #4e73df,
                    #1cc88a
                );

            border: none;

            color: white;

            font-weight: 600;

            padding:
                10px 22px;

        }


        .submit-btn:hover {

            color: white;

            opacity: .9;

        }


        @media (max-width: 768px) {

            .main-content {

                margin-left: 0;

            }

            .page-content {

                padding: 15px;

            }

        }

    </style>

</head>


<body>


<?php

include "sidebar.php";

include "topbar.php";

?>


<main class="main-content">


    <!-- =================================================
         BREADCRUMB
         ================================================= -->

    <div class="breadcrumb-area">

        <nav aria-label="breadcrumb">

            <ol class="breadcrumb">

                <li class="breadcrumb-item">

                    <a
                        href="student_dashboard.php"
                    >

                        Home

                    </a>

                </li>

                <li
                    class="breadcrumb-item active"
                    aria-current="page"
                >

                    Apply OD / Leave

                </li>

            </ol>

        </nav>

    </div>


    <div class="page-content">


        <!-- =================================================
             PAGE TITLE
             ================================================= -->

        <div
            class="
                d-flex
                justify-content-between
                align-items-center
                flex-wrap
                gap-3
                mb-4
            "
        >

            <div>

                <h2 class="page-title mb-1">

                    <i
                        class="fas fa-file-circle-plus
                               text-primary
                               me-2"
                    ></i>

                    Apply OD / Leave

                </h2>

                <p class="text-muted mb-0">

                    Submit your OD or Leave application.

                </p>

            </div>


            <a
                href="student_dashboard.php"
                class="btn btn-secondary"
            >

                <i
                    class="fas fa-arrow-left me-1"
                ></i>

                Dashboard

            </a>

        </div>


        <!-- =================================================
             INFORMATION
             ================================================= -->

        <div class="info-box">

            <div class="d-flex gap-3">

                <i
                    class="
                        fas
                        fa-circle-info
                        text-primary
                        mt-1
                    "
                ></i>

                <div>

                    <strong>
                        Application Information
                    </strong>

                    <div class="small mt-1">

                        Your application will first be sent
                        to the Advisor. After Advisor approval,
                        it will move to the HOD.

                    </div>

                </div>

            </div>

        </div>


        <!-- =================================================
             OD RULE
             ================================================= -->

        <div
            class="od-warning mb-4"
            id="odWarning"
        >

            <div class="d-flex gap-3">

                <i
                    class="
                        fas
                        fa-triangle-exclamation
                        text-warning
                        mt-1
                    "
                ></i>

                <div>

                    <strong>
                        OD Application Rule
                    </strong>

                    <div class="small mt-1">

                        OD must be applied at least
                        <strong>2 days before</strong>
                        the OD date.

                        <br>

                        Earliest allowed date:

                        <strong
                            id="minimumODDate"
                        ></strong>

                    </div>

                </div>

            </div>

        </div>


        <!-- =================================================
             ERROR
             ================================================= -->

        <?php if ($message !== ""): ?>

            <div
                class="alert alert-danger"
                role="alert"
            >

                <i
                    class="fas fa-circle-exclamation me-2"
                ></i>

                <?php
                echo htmlspecialchars(
                    $message,
                    ENT_QUOTES,
                    "UTF-8"
                );
                ?>

            </div>

        <?php endif; ?>


        <!-- =================================================
             FORM
             ================================================= -->

        <div class="card form-card">


            <div class="card-header py-3">

                <h5 class="mb-0">

                    <i
                        class="fas fa-file-pen me-2"
                    ></i>

                    Leave Application Form

                </h5>

            </div>


            <div class="card-body p-4">


                <form
                    method="POST"
                    enctype="multipart/form-data"
                    id="leaveForm"
                >


                    <!-- =====================================
                         STUDENT DETAILS
                         ===================================== -->

                    <div class="row">

                        <div class="col-md-6 mb-3">

                            <label class="form-label">

                                Student ID

                            </label>

                            <input
                                type="text"
                                class="form-control"
                                value="<?php
                                    echo htmlspecialchars(
                                        $student_id,
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );
                                ?>"
                                readonly
                            >

                        </div>


                        <div class="col-md-6 mb-3">

                            <label class="form-label">

                                Student Name

                            </label>

                            <input
                                type="text"
                                class="form-control"
                                value="<?php
                                    echo htmlspecialchars(
                                        $student_name,
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );
                                ?>"
                                readonly
                            >

                        </div>

                    </div>


                    <!-- =====================================
                         DEPARTMENT + CLASS
                         ===================================== -->

                    <div class="row">


                        <div class="col-md-6 mb-3">

                            <label class="form-label">

                                Department

                                <span class="required">
                                    *
                                </span>

                            </label>


                            <select
                                name="department"
                                class="form-select"
                                required
                            >

                                <option value="">
                                    Select Department
                                </option>

                                <option value="CSE">
                                    CSE
                                </option>

                                <option value="ECE">
                                    ECE
                                </option>

                                <option value="EEE">
                                    EEE
                                </option>

                                <option value="MECH">
                                    Mechanical
                                </option>

                                <option value="CIVIL">
                                    Civil
                                </option>

                            </select>

                        </div>


                        <div class="col-md-6 mb-3">

                            <label class="form-label">

                                Class

                                <span class="required">
                                    *
                                </span>

                            </label>


                            <input
                                type="text"
                                name="class_name"
                                class="form-control"
                                placeholder="Example: CSE-A"
                                required
                            >

                        </div>


                    </div>


                    <!-- =====================================
                         LEAVE TYPE
                         ===================================== -->

                    <div class="mb-3">

                        <label class="form-label">

                            Application Type

                            <span class="required">
                                *
                            </span>

                        </label>


                        <select
                            name="leave_type"
                            id="leave_type"
                            class="form-select"
                            required
                        >

                            <option value="">
                                Select Type
                            </option>

                            <option value="Medical">
                                Medical Leave
                            </option>

                            <option value="Personal">
                                Personal Leave
                            </option>

                            <option value="Emergency">
                                Emergency Leave
                            </option>

                            <option value="On Duty">
                                On Duty / OD
                            </option>

                            <option value="Other">
                                Other
                            </option>

                        </select>

                    </div>


                    <!-- =====================================
                         DATES
                         ===================================== -->

                    <div class="row">


                        <div class="col-md-4 mb-3">

                            <label class="form-label">

                                Date From

                                <span class="required">
                                    *
                                </span>

                            </label>


                            <input
                                type="date"
                                name="date_from"
                                id="date_from"
                                class="form-control"
                                required
                            >

                        </div>


                        <div class="col-md-4 mb-3">

                            <label class="form-label">

                                Date To

                                <span class="required">
                                    *
                                </span>

                            </label>


                            <input
                                type="date"
                                name="date_to"
                                id="date_to"
                                class="form-control"
                                required
                            >

                        </div>


                        <div class="col-md-4 mb-3">

                            <label class="form-label">

                                Number of Days

                            </label>


                            <input
                                type="number"
                                name="no_of_days_display"
                                id="no_of_days"
                                class="form-control"
                                readonly
                                placeholder="Auto calculated"
                            >

                        </div>


                    </div>


                    <!-- =====================================
                         REASON
                         ===================================== -->

                    <div class="mb-4">

                        <label class="form-label">

                            Reason

                            <span class="required">
                                *
                            </span>

                        </label>


                        <textarea
                            name="reason"
                            class="form-control"
                            placeholder="Enter the reason for your OD / Leave..."
                            maxlength="1000"
                            required
                        ></textarea>


                        <div
                            class="form-text"
                        >

                            Maximum 1000 characters.

                        </div>

                    </div>


                    <!-- =====================================
                         PROOF
                         ===================================== -->

                    <div class="mb-4">

                        <label class="form-label">

                            Proof / Supporting Document

                            <span class="required">
                                *
                            </span>

                        </label>


                        <input
                            type="file"
                            name="proof"
                            id="proof"
                            class="form-control"
                            accept="
                                image/jpeg,
                                image/png,
                                image/webp
                            "
                            required
                        >


                        <small class="text-muted">

                            Only JPG, JPEG, PNG and WEBP.

                            Maximum size: 5 MB.

                        </small>


                        <div
                            id="filePreview"
                            class="mt-3"
                            style="display:none;"
                        >

                            <img
                                id="previewImage"
                                src=""
                                alt="Proof Preview"
                                style="
                                    max-width:180px;
                                    max-height:150px;
                                    border-radius:8px;
                                    border:1px solid #ddd;
                                    padding:3px;
                                "
                            >

                        </div>

                    </div>


                    <!-- =====================================
                         SUBMIT
                         ===================================== -->

                    <button
                        type="submit"
                        class="btn submit-btn me-2"
                    >

                        <i
                            class="fas fa-paper-plane me-1"
                        ></i>

                        Submit Application

                    </button>


                    <a
                        href="student_dashboard.php"
                        class="btn btn-secondary"
                    >

                        <i
                            class="fas fa-xmark me-1"
                        ></i>

                        Cancel

                    </a>


                </form>

            </div>

        </div>


    </div>

</main>


<?php include "footer.php"; ?>


<!-- =====================================================
     JAVASCRIPT
     ===================================================== -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
></script>


<script>

/* =========================================================
   ELEMENTS
   ========================================================= */

const leaveType =
    document.getElementById(
        "leave_type"
    );

const dateFrom =
    document.getElementById(
        "date_from"
    );

const dateTo =
    document.getElementById(
        "date_to"
    );

const noOfDays =
    document.getElementById(
        "no_of_days"
    );

const odWarning =
    document.getElementById(
        "odWarning"
    );

const minimumODDate =
    document.getElementById(
        "minimumODDate"
    );


/* =========================================================
   TODAY
   ========================================================= */

const today =
    new Date();


const todayString =
    today.toISOString()
         .split("T")[0];


/*
 * Normal leave can start from today.
 */

dateFrom.min =
    todayString;

dateTo.min =
    todayString;


/* =========================================================
   MINIMUM OD DATE
   ========================================================= */

const minimumOD =
    new Date();

minimumOD.setDate(
    minimumOD.getDate() + 2
);


const minimumODString =
    minimumOD.toISOString()
             .split("T")[0];


minimumODDate.textContent =
    minimumOD.toLocaleDateString(
        "en-GB"
    );


/* =========================================================
   UPDATE DATE LIMIT
   ========================================================= */

function updateDateLimit()
{

    const value =
        leaveType.value
            .toLowerCase()
            .replace("-", " ")
            .replace("_", " ");


    const isOD =
        value === "on duty" ||
        value === "od";


    if (isOD) {

        dateFrom.min =
            minimumODString;

        dateTo.min =
            minimumODString;

        odWarning.style.display =
            "block";

    }

    else {

        dateFrom.min =
            todayString;

        dateTo.min =
            todayString;

        odWarning.style.display =
            "none";

    }

    validateODDate();

}

/* =========================================================
   OD DATE VALIDATION
   ========================================================= */

function validateODDate()
{

    const value =
        leaveType.value
            .toLowerCase()
            .replace("-", " ")
            .replace("_", " ");


    const isOD =
        value === "on duty" ||
        value === "od";


    if (
        isOD &&
        dateFrom.value &&
        dateFrom.value <
        minimumODString
    ) {

        Swal.fire({

            icon: "warning",

            title: "OD Date Not Allowed",

            text:
                "OD must be applied at least 2 days before the OD date."

        });


        dateFrom.value = "";

        noOfDays.value = "";

        return false;

    }


    return true;

}


/* =========================================================
   CALCULATE DAYS
   ========================================================= */

function calculateDays()
{

    if (
        !dateFrom.value ||
        !dateTo.value
    ) {

        noOfDays.value = "";

        return;

    }


    if (
        !validateODDate()
    ) {

        return;

    }


    const from =
        new Date(
            dateFrom.value
        );

    const to =
        new Date(
            dateTo.value
        );


    if (to < from) {

        Swal.fire({

            icon: "error",

            title: "Invalid Dates",

            text:
                "Date To cannot be before Date From."

        });


        dateTo.value = "";

        noOfDays.value = "";

        return;

    }


    const difference =
        to.getTime() -
        from.getTime();


    const days =
        Math.floor(
            difference /
            (1000 * 60 * 60 * 24)
        ) + 1;


    noOfDays.value =
        days;

}


/* =========================================================
   EVENTS
   ========================================================= */

leaveType.addEventListener(
    "change",
    function () {

        updateDateLimit();

        calculateDays();

    }
);


dateFrom.addEventListener(
    "change",
    function () {

        validateODDate();

        calculateDays();

        if (dateFrom.value) {

            dateTo.min =
                dateFrom.value;

        }

    }
);


dateTo.addEventListener(
    "change",
    function () {

        calculateDays();

    }
);


/* =========================================================
   PROOF PREVIEW
   ========================================================= */

document
    .getElementById("proof")
    .addEventListener(
        "change",
        function (event) {

            const file =
                event.target.files[0];

            const previewBox =
                document.getElementById(
                    "filePreview"
                );

            const previewImage =
                document.getElementById(
                    "previewImage"
                );


            if (!file) {

                previewBox.style.display =
                    "none";

                return;

            }


            if (
                file.size >
                5 * 1024 * 1024
            ) {

                Swal.fire({

                    icon: "error",

                    title: "File Too Large",

                    text:
                        "Proof image must be less than 5 MB."

                });


                event.target.value = "";

                previewBox.style.display =
                    "none";

                return;

            }


            const allowed =
                [
                    "image/jpeg",
                    "image/png",
                    "image/webp"
                ];


            if (
                !allowed.includes(
                    file.type
                )
            ) {

                Swal.fire({

                    icon: "error",

                    title: "Invalid File",

                    text:
                        "Only JPG, JPEG, PNG and WEBP images are allowed."

                });


                event.target.value = "";

                previewBox.style.display =
                    "none";

                return;

            }


            const reader =
                new FileReader();


            reader.onload =
                function (e) {

                    previewImage.src =
                        e.target.result;

                    previewBox.style.display =
                        "block";

                };


            reader.readAsDataURL(
                file
            );

        }
    );


/* =========================================================
   FORM SUBMISSION VALIDATION
   ========================================================= */

document
    .getElementById("leaveForm")
    .addEventListener(
        "submit",
        function (event) {

            if (
                !validateODDate()
            ) {

                event.preventDefault();

                return;

            }


            if (
                dateTo.value &&
                dateFrom.value &&
                dateTo.value <
                dateFrom.value
            ) {

                event.preventDefault();

                Swal.fire({

                    icon: "error",

                    title: "Invalid Dates",

                    text:
                        "Date To cannot be before Date From."

                });

                return;

            }

        }
    );


/* =========================================================
   SUCCESS MESSAGE
   ========================================================= */

<?php if ($success): ?>

Swal.fire({

    icon: "success",

    title: "Application Submitted!",

    text:
        "Your application has been sent to the Advisor for approval.",

    confirmButtonText:
        "Go to Dashboard",

    allowOutsideClick:
        false,

    allowEscapeKey:
        false

}).then(
    function () {

        window.location.href =
            "student_dashboard.php";

    }
);

<?php endif; ?>

</script>


</body>

</html>