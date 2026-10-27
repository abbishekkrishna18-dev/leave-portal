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


$student_id = (int) $_SESSION["user_id"];

$leave_id = isset($_GET["id"])
    ? (int) $_GET["id"]
    : 0;


if ($leave_id <= 0) {

    header("Location: student_dashboard.php");
    exit;

}


$message = "";

$success = false;


/* =========================================================
   GET APPLICATION
   ========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT *
     FROM leaves
     WHERE id = ?
     AND student_id = ?
     LIMIT 1"
);


mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $leave_id,
    $student_id
);


mysqli_stmt_execute($stmt);

$result =
    mysqli_stmt_get_result($stmt);

$leave =
    mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


/* =========================================================
   APPLICATION NOT FOUND
   ========================================================= */

if (!$leave) {

    header(
        "Location: student_dashboard.php"
    );

    exit;

}


/* =========================================================
   CHECK ADVISOR STATUS
   ========================================================= */

$advisor_status =
    $leave["advisor_status"]
    ?? "Pending";


if (
    strtolower(
        trim($advisor_status)
    ) !== "pending"
) {

    header(
        "Location: leave_details.php?id="
        . $leave_id
    );

    exit;

}


/* =========================================================
   CURRENT VALUES
   ========================================================= */

$department =
    $leave["department"]
    ?? "";

$class_name =
    $leave["class_name"]
    ?? "";

$leave_type =
    $leave["leave_type"]
    ?? "";

$date_from =
    $leave["date_from"]
    ?? "";

$date_to =
    $leave["date_to"]
    ?? "";

$reason =
    $leave["reason"]
    ?? "";

$old_proof =
    $leave["proof"]
    ?? "";


/* =========================================================
   FORM SUBMISSION
   ========================================================= */

if (
    $_SERVER["REQUEST_METHOD"]
    === "POST"
) {

    /* =============================================
       RE-CHECK APPLICATION
       ============================================= */

    $check_stmt = mysqli_prepare(
        $conn,
        "SELECT advisor_status, proof
         FROM leaves
         WHERE id = ?
         AND student_id = ?
         LIMIT 1"
    );


    mysqli_stmt_bind_param(
        $check_stmt,
        "ii",
        $leave_id,
        $student_id
    );


    mysqli_stmt_execute(
        $check_stmt
    );


    $check_result =
        mysqli_stmt_get_result(
            $check_stmt
        );


    $current_leave =
        mysqli_fetch_assoc(
            $check_result
        );


    mysqli_stmt_close(
        $check_stmt
    );


    if (!$current_leave) {

        $message =
            "Application not found.";

    }

    elseif (
        strtolower(
            trim(
                $current_leave[
                    "advisor_status"
                ]
            )
        ) !== "pending"
    ) {

        $message =
            "This application can no longer be edited because the Advisor has already acted on it.";

    }

    else {

        /* =========================================
           GET FORM VALUES
           ========================================= */

        $department =
            trim(
                $_POST["department"]
                ?? ""
            );

        $class_name =
            trim(
                $_POST["class_name"]
                ?? ""
            );

        $leave_type =
            trim(
                $_POST["leave_type"]
                ?? ""
            );

        $date_from =
            trim(
                $_POST["date_from"]
                ?? ""
            );

        $date_to =
            trim(
                $_POST["date_to"]
                ?? ""
            );

        $reason =
            trim(
                $_POST["reason"]
                ?? ""
            );


        /* =========================================
           REQUIRED FIELD VALIDATION
           ========================================= */

        if (
            $department === "" ||
            $class_name === "" ||
            $leave_type === "" ||
            $date_from === "" ||
            $date_to === "" ||
            $reason === ""
        ) {

            $message =
                "All fields are required.";

        }


        /* =========================================
           DATE VALIDATION
           ========================================= */

        elseif (
            $date_to < $date_from
        ) {

            $message =
                "Date To cannot be before Date From.";

        }


        else {

            try {

                $fromDate =
                    new DateTime(
                        $date_from
                    );

                $toDate =
                    new DateTime(
                        $date_to
                    );

                $difference =
                    $fromDate->diff(
                        $toDate
                    );

                $no_of_days =
                    $difference->days + 1;

            }

            catch (Exception $e) {

                $message =
                    "Invalid date selected.";

            }


            /* =====================================
               OD DATE RULE
               ===================================== */

            if ($message === "") {

                $normalized_type =
                    strtolower(
                        str_replace(
                            ["-", "_"],
                            " ",
                            trim(
                                $leave_type
                            )
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
                            "OD must be applied at least 2 days before the OD date.";

                    }

                }

            }


            /* =====================================
               PROOF PROCESSING
               ===================================== */

            $new_file_name =
                $current_leave["proof"];


            $new_upload_path =
                "";


            $old_file_to_delete =
                "";


            if (
                $message === "" &&
                isset($_FILES["proof"]) &&
                $_FILES["proof"]["error"]
                !== UPLOAD_ERR_NO_FILE
            ) {

                if (
                    $_FILES["proof"]["error"]
                    !== UPLOAD_ERR_OK
                ) {

                    $message =
                        "There was an error uploading the proof image.";

                }

                else {

                    $file_name =
                        $_FILES["proof"]["name"];

                    $file_tmp =
                        $_FILES["proof"]["tmp_name"];

                    $file_size =
                        (int)
                        $_FILES["proof"]["size"];


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

                    elseif (
                        $file_size >
                        5 * 1024 * 1024
                    ) {

                        $message =
                            "Proof image must be less than 5 MB.";

                    }

                    else {

                        $upload_folder =
                            __DIR__
                            . DIRECTORY_SEPARATOR
                            . "uploads";


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


                        if (
                            $message === "" &&
                            !is_writable(
                                $upload_folder
                            )
                        ) {

                            $message =
                                "Uploads folder is not writable.";

                        }


                        if (
                            $message === ""
                        ) {

                            $new_file_name =
                                time()
                                . "_"
                                . bin2hex(
                                    random_bytes(5)
                                )
                                . "."
                                . $extension;


                            $new_upload_path =
                                $upload_folder
                                . DIRECTORY_SEPARATOR
                                . $new_file_name;


                            if (
                                !move_uploaded_file(
                                    $file_tmp,
                                    $new_upload_path
                                )
                            ) {

                                $message =
                                    "Failed to upload the new proof image.";

                            }

                            else {

                                $old_file_to_delete =
                                    $current_leave[
                                        "proof"
                                    ];

                            }

                        }

                    }

                }

            }


            /* =====================================
               UPDATE DATABASE
               ===================================== */

            if ($message === "") {

                $update_sql = "
                    UPDATE leaves
                    SET
                        department = ?,
                        class_name = ?,
                        leave_type = ?,
                        date_from = ?,
                        date_to = ?,
                        no_of_days = ?,
                        reason = ?,
                        proof = ?
                    WHERE id = ?
                    AND student_id = ?
                    AND advisor_status = 'Pending'
                ";


                $update_stmt =
                    mysqli_prepare(
                        $conn,
                        $update_sql
                    );


                if (!$update_stmt) {

                    if (
                        $new_upload_path !== ""
                        &&
                        file_exists(
                            $new_upload_path
                        )
                    ) {

                        unlink(
                            $new_upload_path
                        );

                    }


                    $message =
                        "Database error: "
                        . mysqli_error(
                            $conn
                        );

                }

                else {

                    mysqli_stmt_bind_param(
                        $update_stmt,
                        "sssssis sii",
                        $department,
                        $class_name,
                        $leave_type,
                        $date_from,
                        $date_to,
                        $no_of_days,
                        $reason,
                        $new_file_name,
                        $leave_id,
                        $student_id
                    );

                    /*
                     * Remove the accidental whitespace
                     * from the type string by using a
                     * corrected prepared statement below.
                     */

                    mysqli_stmt_close(
                        $update_stmt
                    );


                    $update_stmt =
                        mysqli_prepare(
                            $conn,
                            $update_sql
                        );


                    if (!$update_stmt) {

                        if (
                            $new_upload_path !== ""
                            &&
                            file_exists(
                                $new_upload_path
                            )
                        ) {

                            unlink(
                                $new_upload_path
                            );

                        }


                        $message =
                            "Database error: "
                            . mysqli_error(
                                $conn
                            );

                    }

                    else {

                        mysqli_stmt_bind_param(
                            $update_stmt,
                            "sssssis sii",
                            $department,
                            $class_name,
                            $leave_type,
                            $date_from,
                            $date_to,
                            $no_of_days,
                            $reason,
                            $new_file_name,
                            $leave_id,
                            $student_id
                        );

                    }

                }


                /*
                 * Correct final execution using
                 * an explicit bind type string.
                 */

                if (
                    $message === "" &&
                    isset($update_stmt)
                ) {

                    /*
                     * Re-create statement to guarantee
                     * correct parameter typing.
                     */

                    mysqli_stmt_close(
                        $update_stmt
                    );


                    $update_stmt =
                        mysqli_prepare(
                            $conn,
                            $update_sql
                        );


                    if (!$update_stmt) {

                        if (
                            $new_upload_path !== ""
                            &&
                            file_exists(
                                $new_upload_path
                            )
                        ) {

                            unlink(
                                $new_upload_path
                            );

                        }


                        $message =
                            "Database error: "
                            . mysqli_error(
                                $conn
                            );

                    }

                    else {

                        mysqli_stmt_bind_param(
                            $update_stmt,
                            "sssssissii",
                            $department,
                            $class_name,
                            $leave_type,
                            $date_from,
                            $date_to,
                            $no_of_days,
                            $reason,
                            $new_file_name,
                            $leave_id,
                            $student_id
                        );


                        if (
                            mysqli_stmt_execute(
                                $update_stmt
                            )
                        ) {

                            $success = true;


                            /*
                             * Delete old proof only after
                             * successful database update.
                             */

                            if (
                                $old_file_to_delete !== ""
                                &&
                                $old_file_to_delete
                                !== $new_file_name
                            ) {

                                $old_path =
                                    __DIR__
                                    . DIRECTORY_SEPARATOR
                                    . "uploads"
                                    . DIRECTORY_SEPARATOR
                                    . basename(
                                        $old_file_to_delete
                                    );


                                if (
                                    file_exists(
                                        $old_path
                                    )
                                ) {

                                    unlink(
                                        $old_path
                                    );

                                }

                            }

                        }

                        else {

                            if (
                                $new_upload_path !== ""
                                &&
                                file_exists(
                                    $new_upload_path
                                )
                            ) {

                                unlink(
                                    $new_upload_path
                                );

                            }


                            $message =
                                "Database error: "
                                .
                                mysqli_stmt_error(
                                    $update_stmt
                                );

                        }


                        mysqli_stmt_close(
                            $update_stmt
                        );

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
        Edit Application | Leave Portal
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

            padding: 14px 25px;

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

            background: #eef3ff;

            border-left:
                4px solid #4e73df;

            padding: 15px;

            border-radius: 8px;

            margin-bottom: 20px;

        }


        .proof-preview {

            max-width: 180px;

            max-height: 140px;

            object-fit: cover;

            border:
                1px solid #ddd;

            border-radius: 8px;

            padding: 3px;

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

                <li class="breadcrumb-item">

                    <a
                        href="student_dashboard.php"
                    >

                        My Applications

                    </a>

                </li>

                <li
                    class="breadcrumb-item active"
                >

                    Edit Application

                </li>

            </ol>

        </nav>

    </div>


    <div class="page-content">


        <!-- =================================================
             HEADER
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
                        class="
                            fas
                            fa-pen-to-square
                            text-primary
                            me-2
                        "
                    ></i>

                    Edit Application

                </h2>

                <p class="text-muted mb-0">

                    Update your application before
                    Advisor approval.

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
                        Editing Permission
                    </strong>

                    <div class="small mt-1">

                        You can edit this application
                        because its Advisor status is
                        currently

                        <strong>
                            Pending
                        </strong>.

                        Once the Advisor approves or rejects
                        it, editing will no longer be allowed.

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
                    class="
                        fas
                        fa-circle-exclamation
                        me-2
                    "
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
                        class="
                            fas
                            fa-file-pen
                            me-2
                        "
                    ></i>

                    Application Details

                </h5>

            </div>


            <div class="card-body p-4">


                <form
                    method="POST"
                    enctype="multipart/form-data"
                    id="editForm"
                >


                    <!-- =====================================
                         STUDENT
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
                                        $_SESSION["name"]
                                        ?? "",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );
                                ?>"
                                readonly
                            >

                        </div>


                    </div>


                    <!-- =====================================
                         DEPARTMENT / CLASS
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

                                <option
                                    value="CSE"
                                    <?php
                                    echo $department === "CSE"
                                        ? "selected"
                                        : "";
                                    ?>
                                >
                                    CSE
                                </option>

                                <option
                                    value="ECE"
                                    <?php
                                    echo $department === "ECE"
                                        ? "selected"
                                        : "";
                                    ?>
                                >
                                    ECE
                                </option>

                                <option
                                    value="EEE"
                                    <?php
                                    echo $department === "EEE"
                                        ? "selected"
                                        : "";
                                    ?>
                                >
                                    EEE
                                </option>

                                <option
                                    value="MECH"
                                    <?php
                                    echo $department === "MECH"
                                        ? "selected"
                                        : "";
                                    ?>
                                >
                                    Mechanical
                                </option>

                                <option
                                    value="CIVIL"
                                    <?php
                                    echo $department === "CIVIL"
                                        ? "selected"
                                        : "";
                                    ?>
                                >
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
                                value="<?php
                                    echo htmlspecialchars(
                                        $class_name,
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );
                                ?>"
                                required
                            >

                        </div>


                    </div>


                    <!-- =====================================
                         TYPE
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

                            <option
                                value="Medical"
                                <?php
                                echo $leave_type === "Medical"
                                    ? "selected"
                                    : "";
                                ?>
                            >
                                Medical Leave
                            </option>

                            <option
                                value="Personal"
                                <?php
                                echo $leave_type === "Personal"
                                    ? "selected"
                                    : "";
                                ?>
                            >
                                Personal Leave
                            </option>

                            <option
                                value="Emergency"
                                <?php
                                echo $leave_type === "Emergency"
                                    ? "selected"
                                    : "";
                                ?>
                            >
                                Emergency Leave
                            </option>

                            <option
                                value="On Duty"
                                <?php
                                echo in_array(
                                    strtolower($leave_type),
                                    [
                                        "on duty",
                                        "od"
                                    ],
                                    true
                                )
                                    ? "selected"
                                    : "";
                                ?>
                            >
                                On Duty / OD
                            </option>

                            <option
                                value="Other"
                                <?php
                                echo $leave_type === "Other"
                                    ? "selected"
                                    : "";
                                ?>
                            >
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
                                value="<?php
                                    echo htmlspecialchars(
                                        $date_from,
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );
                                ?>"
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
                                value="<?php
                                    echo htmlspecialchars(
                                        $date_to,
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );
                                ?>"
                                required
                            >

                        </div>


                        <div class="col-md-4 mb-3">

                            <label class="form-label">

                                Number of Days

                            </label>


                            <input
                                type="number"
                                id="no_of_days"
                                class="form-control"
                                value="<?php

                                if (
                                    $date_from &&
                                    $date_to
                                ) {

                                    try {

                                        $f =
                                            new DateTime(
                                                $date_from
                                            );

                                        $t =
                                            new DateTime(
                                                $date_to
                                            );

                                        echo
                                            $f->diff(
                                                $t
                                            )->days + 1;

                                    }

                                    catch (
                                        Exception $e
                                    ) {

                                        echo
                                            $leave[
                                                "no_of_days"
                                            ]
                                            ?? "";

                                    }

                                }

                                else {

                                    echo
                                        $leave[
                                            "no_of_days"
                                        ]
                                        ?? "";

                                }

                                ?>"
                                readonly
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
                            maxlength="1000"
                            required
                        ><?php
                        echo htmlspecialchars(
                            $reason,
                            ENT_QUOTES,
                            "UTF-8"
                        );
                        ?></textarea>

                    </div>


                    <!-- =====================================
                         EXISTING PROOF
                         ===================================== -->

                    <?php if ($old_proof !== ""): ?>

                        <div class="mb-4">

                            <label class="form-label">

                                Current Proof

                            </label>

                            <div>

                                <img
                                    src="uploads/<?php
                                        echo htmlspecialchars(
                                            basename(
                                                $old_proof
                                            ),
                                            ENT_QUOTES,
                                            "UTF-8"
                                        );
                                    ?>"
                                    class="proof-preview"
                                    alt="Current proof"
                                >

                            </div>

                        </div>

                    <?php endif; ?>


                    <!-- =====================================
                         NEW PROOF
                         ===================================== -->

                    <div class="mb-4">

                        <label class="form-label">

                            Replace Proof

                            <span class="text-muted fw-normal">
                                (Optional)
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
                        >


                        <small class="text-muted">

                            Leave empty to keep the current
                            proof.

                            JPG, JPEG, PNG or WEBP only.
                            Maximum 5 MB.

                        </small>

                    </div>


                    <!-- =====================================
                         BUTTONS
                         ===================================== -->

                    <button
                        type="submit"
                        class="btn submit-btn me-2"
                    >

                        <i
                            class="fas fa-save me-1"
                        ></i>

                        Save Changes

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


/* =========================================================
   TODAY
   ========================================================= */

const today =
    new Date();

const todayString =
    today.toISOString()
         .split("T")[0];


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


/* =========================================================
   DATE RULE
   ========================================================= */

function updateDateRules()
{

    const type =
        leaveType.value
            .toLowerCase()
            .replace("-", " ")
            .replace("_", " ");


    const isOD =
        type === "od" ||
        type === "on duty";


    if (isOD) {

        dateFrom.min =
            minimumODString;

        dateTo.min =
            minimumODString;

    }

    else {

        dateFrom.min =
            todayString;

        dateTo.min =
            todayString;

    }

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
        dateTo.value <
        dateFrom.value
    ) {

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


    const type =
        leaveType.value
            .toLowerCase()
            .replace("-", " ")
            .replace("_", " ");


    const isOD =
        type === "od" ||
        type === "on duty";


    if (
        isOD &&
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


    const difference =
        to.getTime() -
        from.getTime();


    noOfDays.value =
        Math.floor(
            difference /
            (1000 * 60 * 60 * 24)
        ) + 1;

}


/* =========================================================
   EVENTS
   ========================================================= */

leaveType.addEventListener(
    "change",
    function () {

        updateDateRules();

        calculateDays();

    }
);


dateFrom.addEventListener(
    "change",
    calculateDays
);


dateTo.addEventListener(
    "change",
    calculateDays
);


/* =========================================================
   FILE VALIDATION
   ========================================================= */

document
    .getElementById("proof")
    .addEventListener(
        "change",
        function () {

            const file =
                this.files[0];

            if (!file) {
                return;
            }


            const allowed = [
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


                this.value = "";

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


                this.value = "";

            }

        }
    );


/* =========================================================
   FORM SUBMIT
   ========================================================= */

document
    .getElementById("editForm")
    .addEventListener(
        "submit",
        function (event) {

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


            const type =
                leaveType.value
                    .toLowerCase()
                    .replace("-", " ")
                    .replace("_", " ");


            const isOD =
                type === "od" ||
                type === "on duty";


            if (
                isOD &&
                dateFrom.value <
                minimumODString
            ) {

                event.preventDefault();

                Swal.fire({

                    icon: "warning",

                    title: "OD Date Not Allowed",

                    text:
                        "OD must be applied at least 2 days before the OD date."

                });

            }

        }
    );


/* =========================================================
   INITIALIZE
   ========================================================= */

updateDateRules();


/* =========================================================
   SUCCESS
   ========================================================= */

<?php if ($success): ?>

Swal.fire({

    icon: "success",

    title: "Changes Saved!",

    text:
        "Your application has been updated successfully.",

    confirmButtonText:
        "Go to Dashboard",

    allowOutsideClick:
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