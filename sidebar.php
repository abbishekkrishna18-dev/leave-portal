<?php
/* =========================================================
   SIDEBAR - LEAVE PORTAL
   ========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER["PHP_SELF"]);

$user_name = $_SESSION["name"] ?? "User";
$user_role = $_SESSION["role"] ?? "";


/* =========================================================
   ROLE DETAILS
   ========================================================= */

$role_title = "User";
$role_icon = "fa-user";

if ($user_role == "student") {

    $role_title = "Student";
    $role_icon = "fa-user-graduate";

}
elseif ($user_role == "advisor") {

    $role_title = "Class Advisor";
    $role_icon = "fa-user-tie";

}
elseif ($user_role == "hod") {

    $role_title = "HOD";
    $role_icon = "fa-user-shield";

}
elseif ($user_role == "faculty") {

    $role_title = "Faculty";
    $role_icon = "fa-chalkboard-user";

}

?>

<style>

/* =========================================================
   SIDEBAR VARIABLES
   ========================================================= */

:root {

    --sidebar-width: 250px;
    --sidebar-collapsed-width: 70px;

    --sidebar-bg: #1a1c23;

    --primary-color: #4e73df;
    --success-color: #1cc88a;

    --sidebar-text: #d1d3e2;

}


/* =========================================================
   SIDEBAR
   ========================================================= */

.sidebar {

    position: fixed;

    top: 0;
    left: 0;

    width: var(--sidebar-width);

    height: 100vh;

    background:
        linear-gradient(
            180deg,
            rgba(26,28,35,0.97),
            rgba(20,22,28,0.99)
        );

    background-image:
        linear-gradient(
            rgba(26,28,35,0.96),
            rgba(26,28,35,0.96)
        ),
        url("image/pattern_h.png");

    background-size: cover;
    background-position: center;

    color: white;

    z-index: 1100;

    overflow-y: auto;

    box-shadow:
        3px 0 15px
        rgba(0,0,0,0.12);

    transition:
        width 0.3s ease;

}


/* =========================================================
   SCROLLBAR
   ========================================================= */

.sidebar::-webkit-scrollbar {

    width: 5px;

}

.sidebar::-webkit-scrollbar-track {

    background: transparent;

}

.sidebar::-webkit-scrollbar-thumb {

    background:
        rgba(255,255,255,0.2);

    border-radius: 10px;

}


/* =========================================================
   LOGO AREA
   ========================================================= */

.sidebar .logo {

    height: 75px;

    display: flex;

    align-items: center;

    justify-content: center;

    padding: 10px 15px;

    border-bottom:
        1px solid
        rgba(255,255,255,0.08);

}


.sidebar .logo img {

    max-height: 55px;

    max-width: 200px;

    object-fit: contain;

}


.sidebar .logo .logo-small {

    display: none;

    width: 45px;

    height: 45px;

    object-fit: contain;

}


/* =========================================================
   USER INFORMATION
   ========================================================= */

.sidebar-user {

    padding: 18px 18px;

    border-bottom:
        1px solid
        rgba(255,255,255,0.08);

}


.sidebar-user-content {

    display: flex;

    align-items: center;

    gap: 12px;

}


.sidebar-user-avatar {

    width: 42px;

    height: 42px;

    min-width: 42px;

    border-radius: 50%;

    background:
        linear-gradient(
            135deg,
            #4e73df,
            #1cc88a
        );

    display: flex;

    align-items: center;

    justify-content: center;

    color: white;

    font-size: 17px;

}


.sidebar-user-info {

    min-width: 0;

}


.sidebar-user-info strong {

    display: block;

    color: white;

    font-size: 14px;

    white-space: nowrap;

    overflow: hidden;

    text-overflow: ellipsis;

}


.sidebar-user-info small {

    display: block;

    color: #a8aab3;

    font-size: 11px;

    margin-top: 2px;

}


/* =========================================================
   SECTION TITLE
   ========================================================= */

.sidebar-section-title {

    padding:
        18px 18px 8px;

    color: #858796;

    font-size: 10px;

    font-weight: 700;

    text-transform: uppercase;

    letter-spacing: 1px;

}


/* =========================================================
   MENU
   ========================================================= */

.sidebar-menu {

    padding:
        5px 10px 20px;

}


.sidebar-menu a {

    display: flex;

    align-items: center;

    gap: 13px;

    min-height: 45px;

    padding:
        10px 13px;

    margin-bottom: 4px;

    color: var(--sidebar-text);

    text-decoration: none;

    border-radius: 8px;

    font-size: 14px;

    transition:
        all 0.2s ease;

}


.sidebar-menu a i {

    width: 20px;

    min-width: 20px;

    text-align: center;

    font-size: 15px;

}


.sidebar-menu a:hover {

    background:
        rgba(255,255,255,0.08);

    color: white;

    transform:
        translateX(2px);

}


.sidebar-menu a.active {

    background:
        linear-gradient(
            90deg,
            #4e73df,
            #5b7ce5
        );

    color: white;

    box-shadow:
        0 4px 12px
        rgba(78,115,223,0.25);

}


/* =========================================================
   MENU ICON BACKGROUND
   ========================================================= */

.sidebar-menu a.active i {

    color: white;

}


/* =========================================================
   SUBMENU
   ========================================================= */

.sidebar-submenu {

    display: none;

    padding-left: 22px;

}


.sidebar-submenu.show {

    display: block;

}


.sidebar-submenu a {

    font-size: 13px;

    min-height: 40px;

}


.sidebar-submenu a i {

    font-size: 12px;

}


/* =========================================================
   ARROW
   ========================================================= */

.has-submenu {

    position: relative;

}


.has-submenu::after {

    content:
        "\f107";

    font-family:
        "Font Awesome 6 Free";

    font-weight: 900;

    position: absolute;

    right: 15px;

    transition:
        transform 0.2s ease;

}


.has-submenu.open::after {

    transform:
        rotate(180deg);

}


/* =========================================================
   LOGOUT
   ========================================================= */

.sidebar-menu .logout-link {

    margin-top: 15px;

    color: #ff9b9b;

}


.sidebar-menu .logout-link:hover {

    background:
        rgba(255,80,80,0.12);

    color: #ffb3b3;

}


/* =========================================================
   COLLAPSED SIDEBAR
   ========================================================= */

.sidebar.collapsed {

    width:
        var(--sidebar-collapsed-width);

}


.sidebar.collapsed .logo {

    padding: 10px;

}


.sidebar.collapsed .logo .logo-large {

    display: none;

}


.sidebar.collapsed .logo .logo-small {

    display: block;

}


.sidebar.collapsed .sidebar-user {

    padding:
        15px 10px;

}


.sidebar.collapsed .sidebar-user-content {

    justify-content: center;

}


.sidebar.collapsed .sidebar-user-info {

    display: none;

}


.sidebar.collapsed
.sidebar-section-title {

    display: none;

}


.sidebar.collapsed .sidebar-menu {

    padding:
        10px 8px;

}


.sidebar.collapsed .sidebar-menu a {

    justify-content: center;

    padding:
        11px 5px;

}


.sidebar.collapsed .sidebar-menu a span {

    display: none;

}


.sidebar.collapsed
.sidebar-menu a::after {

    display: none;

}


.sidebar.collapsed .sidebar-submenu {

    display: none !important;

}


/* =========================================================
   MOBILE OVERLAY
   ========================================================= */

.mobile-overlay {

    display: none;

    position: fixed;

    inset: 0;

    background:
        rgba(0,0,0,0.5);

    z-index: 1050;

}


.mobile-overlay.show {

    display: block;

}


/* =========================================================
   MOBILE
   ========================================================= */

@media (max-width: 768px) {

    .sidebar {

        transform:
            translateX(-100%);

        width:
            var(--sidebar-width);

        transition:
            transform 0.3s ease;

    }


    .sidebar.mobile-open {

        transform:
            translateX(0);

    }


    .sidebar.collapsed {

        width:
            var(--sidebar-width);

    }


    .sidebar.collapsed .logo .logo-large {

        display: block;

    }


    .sidebar.collapsed .logo .logo-small {

        display: none;

    }


    .sidebar.collapsed .sidebar-user-info {

        display: block;

    }


    .sidebar.collapsed .sidebar-menu a {

        justify-content: flex-start;

        padding:
            10px 13px;

    }


    .sidebar.collapsed .sidebar-menu a span {

        display: inline;

    }


    .sidebar.collapsed
    .sidebar-section-title {

        display: block;

    }

}

</style>


<!-- =========================================================
     SIDEBAR HTML
     ========================================================= -->

<aside
    class="sidebar"
    id="mainSidebar"
>


    <!-- =====================================================
         LOGO
         ===================================================== -->

    <div class="logo">

        <img
            src="image/mkce.png"
            class="logo-large"
            alt="MKCE"
            onerror="
                this.src='image/mkce_s.png';
            "
        >

        <img
            src="image/mkce_s.png"
            class="logo-small"
            alt="MKCE"
        >

    </div>


    <!-- =====================================================
         USER
         ===================================================== -->

    <div class="sidebar-user">

        <div class="sidebar-user-content">


            <div class="sidebar-user-avatar">

                <i
                    class="fa-solid <?php echo htmlspecialchars($role_icon); ?>"
                ></i>

            </div>


            <div class="sidebar-user-info">

                <strong>

                    <?php
                    echo htmlspecialchars($user_name);
                    ?>

                </strong>


                <small>

                    <?php
                    echo htmlspecialchars($role_title);
                    ?>

                </small>

            </div>


        </div>

    </div>


    <!-- =====================================================
         MAIN MENU
         ===================================================== -->

    <div class="sidebar-section-title">

        Main Menu

    </div>


    <nav class="sidebar-menu">


        <!-- =================================================
             STUDENT MENU
             ================================================= -->

        <?php if ($user_role == "student") { ?>


            <a
                href="student_dashboard.php"
                class="<?php
                    echo ($current_page == "student_dashboard.php")
                    ? "active"
                    : "";
                ?>"
            >

                <i class="fa-solid fa-gauge-high"></i>

                <span>
                    Dashboard
                </span>

            </a>


            <a
                href="apply_leave.php"
                class="<?php
                    echo ($current_page == "apply_leave.php")
                    ? "active"
                    : "";
                ?>"
            >

                <i class="fa-solid fa-file-circle-plus"></i>

                <span>
                    Apply OD / Leave
                </span>

            </a>


            <a
                href="student_dashboard.php"
            >

                <i class="fa-solid fa-folder-open"></i>

                <span>
                    My Applications
                </span>

            </a>


            <a
                href="reports.php"
                class="<?php
                    echo ($current_page == "reports.php")
                    ? "active"
                    : "";
                ?>"
            >

                <i class="fa-solid fa-chart-column"></i>

                <span>
                    Reports
                </span>

            </a>


        <?php } ?>


        <!-- =================================================
             ADVISOR MENU
             ================================================= -->

        <?php if ($user_role == "advisor") { ?>


            <a
                href="advisor_dashboard.php"
                class="<?php
                    echo ($current_page == "advisor_dashboard.php")
                    ? "active"
                    : "";
                ?>"
            >

                <i class="fa-solid fa-gauge-high"></i>

                <span>
                    Dashboard
                </span>

            </a>


            <a
                href="advisor_dashboard.php"
            >

                <i class="fa-solid fa-file-signature"></i>

                <span>
                    Leave Applications
                </span>

            </a>


            <a
                href="advisor_courses.php"
                class="<?php
                    echo ($current_page == "advisor_courses.php")
                    ? "active"
                    : "";
                ?>"
            >

                <i class="fa-solid fa-book-open"></i>

                <span>
                    My Courses
                </span>

            </a>


            <!-- ADVISOR TIMETABLE -->

            <a
                href="advisor_timetable.php"
                class="<?php
                    echo ($current_page == "advisor_timetable.php")
                    ? "active"
                    : "";
                ?>"
            >

                <i class="fa-solid fa-calendar-days"></i>

                <span>
                    Timetable
                </span>

            </a>


            <!-- ASSIGN STUDENTS -->

            <a
                href="advisor_assign_students.php"
                class="<?php
                    echo ($current_page == "advisor_assign_students.php")
                    ? "active"
                    : "";
                ?>"
            >

                <i class="fa-solid fa-users"></i>

                <span>
                    Assign Students
                </span>

            </a>


            <a
                href="advisor_dashboard.php#notifications"
            >

                <i class="fa-solid fa-bell"></i>

                <span>
                    Notifications
                </span>

            </a>


        <?php } ?>


        <!-- =================================================
             HOD MENU
             ================================================= -->

        <?php if ($user_role == "hod") { ?>


            <a
                href="hod_dashboard.php"
                class="<?php
                    echo ($current_page == "hod_dashboard.php")
                    ? "active"
                    : "";
                ?>"
            >

                <i class="fa-solid fa-gauge-high"></i>

                <span>
                    Dashboard
                </span>

            </a>


            <a
                href="hod_class_advisor.php"
                class="<?php
                    echo ($current_page == "hod_class_advisor.php")
                    ? "active"
                    : "";
                ?>"
            >

                <i class="fa-solid fa-user-tie"></i>

                <span>
                    Class Advisors
                </span>

            </a>


            <a
                href="hod_add_advisor.php"
                class="<?php
                    echo ($current_page == "hod_add_advisor.php")
                    ? "active"
                    : "";
                ?>"
            >

                <i class="fa-solid fa-user-plus"></i>

                <span>
                    Add Advisor
                </span>

            </a>


            <a
                href="hod_dashboard.php"
            >

                <i class="fa-solid fa-file-signature"></i>

                <span>
                    Advisor Approved
                </span>

            </a>


        <?php } ?>


        <!-- =================================================
             FACULTY MENU
             ================================================= -->

        <?php if ($user_role == "faculty") { ?>


            <a
                href="faculty_timetable.php"
                class="<?php
                    echo ($current_page == "faculty_timetable.php")
                    ? "active"
                    : "";
                ?>"
            >

                <i class="fa-solid fa-calendar-days"></i>

                <span>
                    My Timetable
                </span>

            </a>


        <?php } ?>


        <!-- =================================================
             LOGOUT
             ================================================= -->

        <a
            href="logout.php"
            class="logout-link"
        >

            <i class="fa-solid fa-right-from-bracket"></i>

            <span>
                Logout
            </span>

        </a>


    </nav>


</aside>


<!-- =========================================================
     MOBILE OVERLAY
     ========================================================= -->

<div
    class="mobile-overlay"
    id="mobileOverlay"
></div>


<script>

/* =========================================================
   SIDEBAR JAVASCRIPT
   ========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    function () {


        const sidebar =
            document.getElementById("mainSidebar");


        const overlay =
            document.getElementById("mobileOverlay");


        /* -------------------------------------------------
           DESKTOP SIDEBAR TOGGLE
           ------------------------------------------------- */

        window.toggleSidebar =
            function () {

                if (!sidebar) {
                    return;
                }


                if (window.innerWidth <= 768) {

                    sidebar.classList.toggle(
                        "mobile-open"
                    );

                    overlay.classList.toggle(
                        "show"
                    );

                }

                else {

                    sidebar.classList.toggle(
                        "collapsed"
                    );

                    localStorage.setItem(
                        "sidebarCollapsed",
                        sidebar.classList.contains(
                            "collapsed"
                        )
                    );

                }

            };


        /* -------------------------------------------------
           MOBILE OVERLAY
           ------------------------------------------------- */

        if (overlay) {

            overlay.addEventListener(
                "click",
                function () {

                    sidebar.classList.remove(
                        "mobile-open"
                    );

                    overlay.classList.remove(
                        "show"
                    );

                }
            );

        }


        /* -------------------------------------------------
           REMEMBER SIDEBAR STATE
           ------------------------------------------------- */

        if (
            window.innerWidth > 768 &&
            localStorage.getItem(
                "sidebarCollapsed"
            ) === "true"
        ) {

            sidebar.classList.add(
                "collapsed"
            );

        }


        /* -------------------------------------------------
           WINDOW RESIZE
           ------------------------------------------------- */

        window.addEventListener(
            "resize",
            function () {

                if (window.innerWidth > 768) {

                    sidebar.classList.remove(
                        "mobile-open"
                    );

                    overlay.classList.remove(
                        "show"
                    );

                }

            }
        );


    }
);

</script>