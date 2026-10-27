<?php

session_start();
include "db.php";

$error = "";

/* =========================================================
   IF ALREADY LOGGED IN
   ========================================================= */

if (isset($_SESSION["user_id"]) && isset($_SESSION["role"])) {

    if ($_SESSION["role"] == "student") {
        header("Location: student_dashboard.php");
        exit();
    }

    if ($_SESSION["role"] == "advisor") {
        header("Location: advisor_dashboard.php");
        exit();
    }

    if ($_SESSION["role"] == "hod") {
        header("Location: hod_dashboard.php");
        exit();
    }
    if ($role === 'faculty') {
    header("Location: faculty_timetable.php");
    exit();
}
}


/* =========================================================
   LOGIN PROCESS
   ========================================================= */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    $role = $_POST["role"] ?? "";


    /* -----------------------------------------------------
       VALIDATION
       ----------------------------------------------------- */

    if ($username == "" || $password == "" || $role == "") {

        $error = "All fields are required.";

    }

    elseif (!in_array($role, ["student", "advisor", "hod","faculty"])) {

        $error = "Invalid login role.";

    }

    else {

        /* -------------------------------------------------
           GET USER
           ------------------------------------------------- */

        $stmt = mysqli_prepare(
            $conn,
            "SELECT id, username, password, name, role
             FROM users
             WHERE username = ?
             AND role = ?
             LIMIT 1"
        );


        if ($stmt) {

            mysqli_stmt_bind_param(
                $stmt,
                "ss",
                $username,
                $role
            );

            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);


            /* ---------------------------------------------
               USER FOUND
               --------------------------------------------- */

            if ($result && mysqli_num_rows($result) == 1) {

                $user = mysqli_fetch_assoc($result);

                $password_correct = false;


                /* -----------------------------------------
                   CHECK HASHED PASSWORD
                   ----------------------------------------- */

                if (
                    password_verify(
                        $password,
                        $user["password"]
                    )
                ) {

                    $password_correct = true;

                }


                /* -----------------------------------------
                   CHECK PLAIN PASSWORD
                   ----------------------------------------- */

                elseif (
                    $password === $user["password"]
                ) {

                    $password_correct = true;

                }


                /* -----------------------------------------
                   LOGIN SUCCESS
                   ----------------------------------------- */

                if ($password_correct) {

                    $_SESSION["user_id"] = $user["id"];

                    $_SESSION["name"] = $user["name"];

                    $_SESSION["role"] = $user["role"];


                    /* -------------------------------------
                       STUDENT
                       ------------------------------------- */

                    if ($user["role"] == "student") {

                        header(
                            "Location: student_dashboard.php"
                        );

                        exit();
                    }


                    /* -------------------------------------
                       ADVISOR
                       ------------------------------------- */

                    if ($user["role"] == "advisor") {

                        header(
                            "Location: advisor_dashboard.php"
                        );

                        exit();
                    }


                    /* -------------------------------------
                       HOD
                       ------------------------------------- */

                    if ($user["role"] == "hod") {

                        header(
                            "Location: hod_dashboard.php"
                        );

                        exit();
                    }
                    if ($user["role"] == "faculty") {
    header("Location: faculty_timetable.php");
    exit();
}

                }

                else {

                    $error = "Incorrect username or password.";

                }

            }

            else {

                $error = "Incorrect username or password.";

            }


            mysqli_stmt_close($stmt);

        }

        else {

            $error = "Unable to process login.";

        }
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>MKCE Leave Portal</title>


    <!-- FAVICON -->

    <link
        rel="icon"
        type="image/png"
        href="image/icons/mkce_s.png"
    >


    <!-- BOOTSTRAP -->

    <link
        href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- FONT AWESOME -->

    <link
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
        rel="stylesheet"
    >


    <!-- SWEET ALERT -->

    <link
        href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-5/bootstrap-5.css"
        rel="stylesheet"
    >

    <script
        src="https://cdn.jsdelivr.net/npm/sweetalert2@11"
    ></script>


    <style>

        :root {

            --primary-color: #4e73df;

            --secondary-color: #858796;

            --success-color: #1cc88a;

            --dark-bg: #1a1c23;

        }


        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            min-height: 100vh;

            font-family:
                "Segoe UI",
                Tahoma,
                Geneva,
                Verdana,
                sans-serif;

            background:
                linear-gradient(
                    135deg,
                    #f8f9fc 0%,
                    #e9f1ff 50%,
                    #eafaf4 100%
                );

            display: flex;

            align-items: center;

            justify-content: center;

            position: relative;

            overflow-x: hidden;

        }


        /* =================================================
           BACKGROUND SHAPES
           ================================================= */

        body::before {

            content: "";

            position: fixed;

            width: 450px;

            height: 450px;

            background:
                linear-gradient(
                    135deg,
                    rgba(78,115,223,0.18),
                    rgba(28,200,138,0.08)
                );

            border-radius: 50%;

            top: -180px;

            right: -120px;

            z-index: 0;

        }


        body::after {

            content: "";

            position: fixed;

            width: 350px;

            height: 350px;

            background:
                linear-gradient(
                    135deg,
                    rgba(28,200,138,0.12),
                    rgba(78,115,223,0.06)
                );

            border-radius: 50%;

            bottom: -150px;

            left: -100px;

            z-index: 0;

        }


        /* =================================================
           MAIN LOGIN WRAPPER
           ================================================= */

        .login-wrapper {

            width: 100%;

            max-width: 1100px;

            padding: 25px;

            position: relative;

            z-index: 1;

        }


        .login-container {

            background: white;

            border-radius: 22px;

            overflow: hidden;

            box-shadow:
                0 15px 45px
                rgba(0, 0, 0, 0.12);

            display: flex;

            min-height: 650px;

        }


        /* =================================================
           LEFT BRANDING SECTION
           ================================================= */

        .branding-section {

            width: 50%;

            padding: 45px;

            background:
                linear-gradient(
                    145deg,
                    #4e73df,
                    #224abe
                );

            color: white;

            display: flex;

            flex-direction: column;

            justify-content: center;

            align-items: center;

            text-align: center;

            position: relative;

            overflow: hidden;

        }


        .branding-section::before {

            content: "";

            position: absolute;

            width: 350px;

            height: 350px;

            border: 1px solid
                rgba(255,255,255,0.15);

            border-radius: 50%;

            top: -170px;

            left: -150px;

        }


        .branding-section::after {

            content: "";

            position: absolute;

            width: 300px;

            height: 300px;

            border: 1px solid
                rgba(255,255,255,0.15);

            border-radius: 50%;

            bottom: -150px;

            right: -130px;

        }


        .logo-wrapper {

            position: relative;

            z-index: 2;

            background: white;

            width: 180px;

            height: 180px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 15px;

            margin-bottom: 25px;

            box-shadow:
                0 10px 30px
                rgba(0,0,0,0.15);

        }


        .logo-wrapper img {

            max-width: 100%;

            max-height: 100%;

            object-fit: contain;

        }


        .branding-section h1 {

            position: relative;

            z-index: 2;

            font-size: 32px;

            font-weight: 700;

            margin-bottom: 8px;

        }


        .branding-section h2 {

            position: relative;

            z-index: 2;

            font-size: 20px;

            font-weight: 500;

            margin-bottom: 20px;

        }


        .branding-section p {

            position: relative;

            z-index: 2;

            max-width: 400px;

            font-size: 15px;

            line-height: 1.7;

            opacity: 0.9;

        }


        .portal-badge {

            position: relative;

            z-index: 2;

            margin-top: 25px;

            padding: 10px 20px;

            border-radius: 30px;

            background:
                rgba(255,255,255,0.15);

            border:
                1px solid
                rgba(255,255,255,0.25);

            font-size: 14px;

        }


        /* =================================================
           LOGIN SECTION
           ================================================= */

        .login-section {

            width: 50%;

            padding: 55px 60px;

            display: flex;

            flex-direction: column;

            justify-content: center;

        }


        .login-title {

            margin-bottom: 35px;

        }


        .login-title h3 {

            font-size: 28px;

            font-weight: 700;

            color: #2e2e2e;

            margin-bottom: 8px;

        }


        .login-title p {

            color: #858796;

            margin: 0;

            font-size: 14px;

        }


        /* =================================================
           FORM
           ================================================= */

        .form-label {

            font-weight: 600;

            color: #444;

            font-size: 14px;

            margin-bottom: 8px;

        }


        .input-group {

            margin-bottom: 20px;

        }


        .input-group-text {

            width: 48px;

            justify-content: center;

            background: #f8f9fc;

            border:
                1px solid #dfe3e8;

            color: var(--primary-color);

        }


        .form-control,
        .form-select {

            height: 50px;

            border:
                1px solid #dfe3e8;

            font-size: 14px;

        }


        .form-control:focus,
        .form-select:focus {

            border-color:
                var(--primary-color);

            box-shadow:
                0 0 0 0.2rem
                rgba(78,115,223,0.15);

        }


        /* =================================================
           LOGIN BUTTON
           ================================================= */

        .login-btn {

            width: 100%;

            height: 52px;

            border: none;

            border-radius: 10px;

            background:
                linear-gradient(
                    45deg,
                    #4e73df,
                    #1cc88a
                );

            color: white;

            font-size: 15px;

            font-weight: 600;

            transition:
                all 0.3s ease;

            box-shadow:
                0 5px 15px
                rgba(78,115,223,0.25);

        }


        .login-btn:hover {

            transform:
                translateY(-2px);

            box-shadow:
                0 8px 20px
                rgba(78,115,223,0.30);

            color: white;

        }


        /* =================================================
           FOOTER TEXT
           ================================================= */

        .login-footer {

            text-align: center;

            margin-top: 30px;

            padding-top: 20px;

            border-top:
                1px solid #eee;

            font-size: 12px;

            color: #858796;

        }


        .login-footer strong {

            color:
                var(--primary-color);

        }
        /* =================================================
           PASSWORD TOGGLE
           ================================================= */
        .password-toggle {

            cursor: pointer;

            color: #858796;

            transition: 0.2s;

        }

        .password-toggle:hover {

            color:
                var(--primary-color);

        }


        /* =================================================
           MOBILE
           ================================================= */

        @media (max-width: 850px) {

            .login-container {

                flex-direction: column;

                min-height: auto;

            }


            .branding-section {

                width: 100%;

                padding: 35px 25px;

            }


            .branding-section h1 {

                font-size: 26px;

            }


            .branding-section h2 {

                font-size: 17px;

            }


            .logo-wrapper {

                width: 130px;

                height: 130px;

            }


            .login-section {

                width: 100%;

                padding: 40px 30px;

            }

        }


        @media (max-width: 480px) {

            .login-wrapper {

                padding: 10px;

            }


            .login-section {

                padding: 35px 22px;

            }


            .branding-section {

                padding: 30px 20px;

            }


            .branding-section p {

                font-size: 13px;

            }

        }

    </style>

</head>


<body>


<div class="login-wrapper">

    <div class="login-container">


        <!-- =================================================
             LEFT SIDE - MKCE BRANDING
             ================================================= -->

        <div class="branding-section">


            <div class="logo-wrapper">

                <img
                    src="image/mkce.png"
                    alt="MKCE Logo"
                    onerror="
                        this.src='image/mkce_s.png';
                    "
                >

            </div>


            <h1>
                M.Kumarasamy College
            </h1>


            <h2>
                of Engineering
            </h2>


            <p>
                Student Leave &amp; On-Duty
                Management Portal
            </p>


            <div class="portal-badge">

                <i class="fa-solid fa-shield-halved me-2"></i>

                Secure Role-Based Access

            </div>


        </div>



        <!-- =================================================
             RIGHT SIDE - LOGIN
             ================================================= -->

        <div class="login-section">


            <div class="login-title">

                <h3>
                    Welcome Back
                </h3>

                <p>
                    Sign in to access the Leave Portal
                </p>

            </div>


            <form
                method="POST"
                action=""
                autocomplete="off"
            >


                <!-- =================================================
                     ROLE
                     ================================================= -->

                <div class="mb-3">

                    <label
                        for="role"
                        class="form-label"
                    >

                        Login As

                        <span class="text-danger">
                            *
                        </span>

                    </label>


                    <div class="input-group">

                        <span class="input-group-text">

                            <i
                                class="fa-solid fa-user-shield"
                            ></i>

                        </span>


                        <select
                            name="role"
                            id="role"
                            class="form-select"
                            required
                        >

                            <option value="">
                                Select your role
                            </option>

                            <option value="student">
                                Student
                            </option>

                            <option value="advisor">
                                Class Advisor
                            </option>

                            <option value="hod">
                                HOD
                            </option>
                            <option value="faculty">
                                Faculty
                            </option>
                            

                        </select>

                    </div>

                </div>



                <!-- =================================================
                     USERNAME
                     ================================================= -->

                <div class="mb-3">

                    <label
                        for="username"
                        class="form-label"
                    >

                        Username

                        <span class="text-danger">
                            *
                        </span>

                    </label>


                    <div class="input-group">

                        <span class="input-group-text">

                            <i
                                class="fa-solid fa-user"
                            ></i>

                        </span>


                        <input
                            type="text"
                            name="username"
                            id="username"
                            class="form-control"
                            placeholder="Enter your username"
                            required
                        >

                    </div>

                </div>



                <!-- =================================================
                     PASSWORD
                     ================================================= -->

                <div class="mb-4">

                    <label
                        for="password"
                        class="form-label"
                    >

                        Password

                        <span class="text-danger">
                            *
                        </span>

                    </label>


                    <div class="input-group">

                        <span class="input-group-text">

                            <i
                                class="fa-solid fa-lock"
                            ></i>

                        </span>


                        <input
                            type="password"
                            name="password"
                            id="password"
                            class="form-control"
                            placeholder="Enter your password"
                            required
                        >


                        <span
                            class="input-group-text password-toggle"
                            onclick="togglePassword()"
                        >

                            <i
                                class="fa-solid fa-eye"
                                id="passwordIcon"
                            ></i>

                        </span>

                    </div>

                </div>



                <!-- =================================================
                     LOGIN BUTTON
                     ================================================= -->

                <button
                    type="submit"
                    class="login-btn"
                >

                    <i
                        class="fa-solid fa-right-to-bracket me-2"
                    ></i>

                    Login

                </button>


            </form>



            <!-- =================================================
                 FOOTER
                 ================================================= -->

            <div class="login-footer">

                <div>

                    <strong>
                        MKCE Leave Portal
                    </strong>

                </div>

                <div class="mt-1">

                    M.Kumarasamy College of Engineering

                </div>

                <div class="mt-1">

                    Technology Innovation Hub

                </div>

            </div>


        </div>

    </div>

</div>



<!-- =========================================================
     JAVASCRIPT
     ========================================================= -->

<script>

function togglePassword() {

    const password =
        document.getElementById("password");

    const icon =
        document.getElementById("passwordIcon");


    if (password.type === "password") {

        password.type = "text";

        icon.classList.remove(
            "fa-eye"
        );

        icon.classList.add(
            "fa-eye-slash"
        );

    }

    else {

        password.type = "password";

        icon.classList.remove(
            "fa-eye-slash"
        );

        icon.classList.add(
            "fa-eye"
        );

    }

}

</script>



<?php

/* =========================================================
   LOGIN ERROR
   ========================================================= */

if ($error != "") {

?>

<script>

Swal.fire({

    icon: "error",

    title: "Login Failed",

    text:
        <?php
        echo json_encode($error);
        ?>

});

</script>

<?php

}

?>


</body>

</html>