<?php

/* =========================================================
   TOPBAR - LEAVE PORTAL
   ========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_name = $_SESSION["name"] ?? "User";
$user_role = $_SESSION["role"] ?? "user";

$role_title = "User";

if ($user_role == "student") {
    $role_title = "Student";
}
elseif ($user_role == "advisor") {
    $role_title = "Class Advisor";
}
elseif ($user_role == "hod") {
    $role_title = "HOD";
}


/* Get first letter for avatar */

$avatar_letter = strtoupper(
    substr(trim($user_name), 0, 1)
);

?>

<style>

/* =========================================================
   TOPBAR
   ========================================================= */

.topbar {

    position: fixed;

    top: 0;
    right: 0;
    left: var(--sidebar-width, 250px);

    height: 60px;

    background:
        linear-gradient(
            90deg,
            rgba(255,255,255,0.96),
            rgba(248,249,252,0.96)
        );

    backdrop-filter: blur(12px);

    -webkit-backdrop-filter: blur(12px);

    border-bottom:
        1px solid #e3e6f0;

    box-shadow:
        0 2px 10px
        rgba(0,0,0,0.05);

    z-index: 1000;

    transition:
        left 0.3s ease;

}


/* =========================================================
   TOPBAR INNER
   ========================================================= */

.topbar-inner {

    height: 100%;

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding:
        0 22px;

}


/* =========================================================
   LEFT AREA
   ========================================================= */

.topbar-left {

    display: flex;

    align-items: center;

    gap: 15px;

}


/* =========================================================
   SIDEBAR TOGGLE
   ========================================================= */

.sidebar-toggle {

    width: 38px;

    height: 38px;

    border: none;

    border-radius: 8px;

    background:
        #f1f3f8;

    color:
        #4e73df;

    display: flex;

    align-items: center;

    justify-content: center;

    cursor: pointer;

    transition:
        all 0.2s ease;

}


.sidebar-toggle:hover {

    background:
        #4e73df;

    color: white;

    transform:
        translateY(-1px);

}


/* =========================================================
   PAGE BRAND
   ========================================================= */

.topbar-brand {

    display: flex;

    align-items: center;

    gap: 10px;

}


.topbar-brand-icon {

    width: 35px;

    height: 35px;

    border-radius: 8px;

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

    font-size: 15px;

}


.topbar-brand-text {

    line-height: 1.2;

}


.topbar-brand-title {

    font-size: 15px;

    font-weight: 700;

    color: #343a40;

}


.topbar-brand-subtitle {

    font-size: 10px;

    color: #858796;

}


/* =========================================================
   RIGHT AREA
   ========================================================= */

.topbar-right {

    display: flex;

    align-items: center;

    gap: 15px;

}


/* =========================================================
   ROLE BADGE
   ========================================================= */

.role-badge {

    padding:
        6px 12px;

    border-radius: 20px;

    background:
        rgba(78,115,223,0.10);

    color:
        #4e73df;

    font-size: 11px;

    font-weight: 600;

    border:
        1px solid
        rgba(78,115,223,0.12);

}


.role-badge i {

    margin-right: 5px;

}


/* =========================================================
   USER PROFILE
   ========================================================= */

.user-profile {

    display: flex;

    align-items: center;

    gap: 10px;

    padding:
        5px 8px 5px 5px;

    border-radius: 30px;

    cursor: pointer;

    transition:
        all 0.2s ease;

}


.user-profile:hover {

    background:
        #f1f3f8;

}


.user-avatar {

    width: 36px;

    height: 36px;

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

    font-size: 14px;

    font-weight: 700;

}


.user-info {

    line-height: 1.2;

}


.user-name {

    font-size: 13px;

    font-weight: 600;

    color: #343a40;

    max-width: 150px;

    white-space: nowrap;

    overflow: hidden;

    text-overflow: ellipsis;

}


.user-role {

    font-size: 10px;

    color: #858796;

}


.user-arrow {

    font-size: 11px;

    color: #858796;

}


/* =========================================================
   DROPDOWN
   ========================================================= */

.user-dropdown {

    position: relative;

}


.user-dropdown-menu {

    position: absolute;

    right: 0;

    top: calc(100% + 8px);

    width: 210px;

    background: white;

    border:
        1px solid #e3e6f0;

    border-radius: 10px;

    box-shadow:
        0 8px 25px
        rgba(0,0,0,0.12);

    padding: 8px;

    display: none;

    z-index: 1200;

}


.user-dropdown-menu.show {

    display: block;

    animation:
        dropdownIn 0.15s ease;

}


@keyframes dropdownIn {

    from {

        opacity: 0;

        transform:
            translateY(-5px);

    }

    to {

        opacity: 1;

        transform:
            translateY(0);

    }

}


/* =========================================================
   DROPDOWN HEADER
   ========================================================= */

.dropdown-user-header {

    padding:
        10px;

    border-bottom:
        1px solid #eee;

    margin-bottom: 5px;

}


.dropdown-user-name {

    font-size: 13px;

    font-weight: 700;

    color: #343a40;

}


.dropdown-user-role {

    font-size: 11px;

    color: #858796;

    margin-top: 2px;

}


/* =========================================================
   DROPDOWN LINKS
   ========================================================= */

.user-dropdown-menu a {

    display: flex;

    align-items: center;

    gap: 10px;

    padding:
        10px 11px;

    border-radius: 7px;

    color: #555;

    text-decoration: none;

    font-size: 13px;

    transition:
        all 0.2s ease;

}


.user-dropdown-menu a:hover {

    background:
        #f8f9fc;

    color:
        #4e73df;

}


.user-dropdown-menu a i {

    width: 18px;

    text-align: center;

}


/* =========================================================
   LOGOUT
   ========================================================= */

.user-dropdown-menu .logout {

    color:
        #e74a3b;

}


.user-dropdown-menu .logout:hover {

    background:
        rgba(231,74,59,0.08);

    color:
        #c0392b;

}


/* =========================================================
   COLLAPSED SIDEBAR
   ========================================================= */

.sidebar.collapsed ~ .content .topbar,
.sidebar.collapsed + .content .topbar {

    left:
        var(--sidebar-collapsed-width, 70px);

}


/* =========================================================
   MOBILE
   ========================================================= */

@media (max-width: 768px) {

    .topbar {

        left: 0 !important;

    }


    .topbar-inner {

        padding:
            0 15px;

    }


    .topbar-brand-subtitle {

        display: none;

    }


    .topbar-brand-title {

        font-size: 13px;

    }


    .role-badge {

        display: none;

    }


    .user-info {

        display: none;

    }


    .user-arrow {

        display: none;

    }

}

</style>


<!-- =========================================================
     TOPBAR HTML
     ========================================================= -->

<header class="topbar" id="mainTopbar">

    <div class="topbar-inner">


        <!-- =================================================
             LEFT
             ================================================= -->

        <div class="topbar-left">


            <!-- SIDEBAR BUTTON -->

            <button
                type="button"
                class="sidebar-toggle"
                onclick="toggleSidebar()"
                title="Toggle Sidebar"
            >

                <i class="fa-solid fa-bars"></i>

            </button>


            <!-- BRAND -->

            <div class="topbar-brand">

                <div class="topbar-brand-icon">

                    <i class="fa-solid fa-file-signature"></i>

                </div>


                <div class="topbar-brand-text">

                    <div class="topbar-brand-title">

                        Leave Portal

                    </div>


                    <div class="topbar-brand-subtitle">

                        M.Kumarasamy College of Engineering

                    </div>

                </div>

            </div>

        </div>



        <!-- =================================================
             RIGHT
             ================================================= -->

        <div class="topbar-right">


            <!-- ROLE -->

            <div class="role-badge">

                <?php if ($user_role == "student") { ?>

                    <i class="fa-solid fa-user-graduate"></i>

                <?php } elseif ($user_role == "advisor") { ?>

                    <i class="fa-solid fa-user-tie"></i>

                <?php } elseif ($user_role == "hod") { ?>

                    <i class="fa-solid fa-user-shield"></i>

                <?php } else { ?>

                    <i class="fa-solid fa-user"></i>

                <?php } ?>


                <?php
                echo htmlspecialchars($role_title);
                ?>

            </div>



            <!-- USER -->

            <div class="user-dropdown">


                <div
                    class="user-profile"
                    id="userProfileButton"
                    onclick="toggleUserDropdown()"
                >


                    <div class="user-avatar">

                        <?php
                        echo htmlspecialchars(
                            $avatar_letter
                        );
                        ?>

                    </div>


                    <div class="user-info">

                        <div class="user-name">

                            <?php
                            echo htmlspecialchars(
                                $user_name
                            );
                            ?>

                        </div>


                        <div class="user-role">

                            <?php
                            echo htmlspecialchars(
                                $role_title
                            );
                            ?>

                        </div>

                    </div>


                    <div class="user-arrow">

                        <i
                            class="fa-solid fa-chevron-down"
                        ></i>

                    </div>


                </div>



                <!-- DROPDOWN -->

                <div
                    class="user-dropdown-menu"
                    id="userDropdown"
                >


                    <div class="dropdown-user-header">

                        <div class="dropdown-user-name">

                            <?php
                            echo htmlspecialchars(
                                $user_name
                            );
                            ?>

                        </div>


                        <div class="dropdown-user-role">

                            <?php
                            echo htmlspecialchars(
                                $role_title
                            );
                            ?>

                        </div>

                    </div>


                    <!-- DASHBOARD -->

                    <?php if ($user_role == "student") { ?>

                        <a href="student_dashboard.php">

                            <i
                                class="fa-solid fa-gauge-high"
                            ></i>

                            Dashboard

                        </a>

                    <?php } ?>


                    <?php if ($user_role == "advisor") { ?>

                        <a href="advisor_dashboard.php">

                            <i
                                class="fa-solid fa-gauge-high"
                            ></i>

                            Dashboard

                        </a>

                    <?php } ?>


                    <?php if ($user_role == "hod") { ?>

                        <a href="hod_dashboard.php">

                            <i
                                class="fa-solid fa-gauge-high"
                            ></i>

                            Dashboard

                        </a>

                    <?php } ?>


                    <!-- DIVIDER -->

                    <div
                        style="
                            border-top:1px solid #eee;
                            margin:5px 0;
                        "
                    ></div>


                    <!-- LOGOUT -->

                    <a
                        href="logout.php"
                        class="logout"
                    >

                        <i
                            class="fa-solid fa-right-from-bracket"
                        ></i>

                        Logout

                    </a>


                </div>

            </div>

        </div>

    </div>

</header>


<script>

/* =========================================================
   USER DROPDOWN
   ========================================================= */

function toggleUserDropdown() {

    const dropdown =
        document.getElementById(
            "userDropdown"
        );

    if (!dropdown) {
        return;
    }

    dropdown.classList.toggle(
        "show"
    );

}


/* =========================================================
   CLOSE DROPDOWN WHEN CLICKING OUTSIDE
   ========================================================= */

document.addEventListener(
    "click",
    function(event) {

        const profile =
            document.getElementById(
                "userProfileButton"
            );

        const dropdown =
            document.getElementById(
                "userDropdown"
            );


        if (
            profile &&
            dropdown &&
            !profile.contains(event.target) &&
            !dropdown.contains(event.target)
        ) {

            dropdown.classList.remove(
                "show"
            );

        }

    }
);


/* =========================================================
   UPDATE TOPBAR WHEN SIDEBAR COLLAPSES
   ========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    function() {

        const sidebar =
            document.getElementById(
                "mainSidebar"
            );

        const topbar =
            document.getElementById(
                "mainTopbar"
            );


        if (!sidebar || !topbar) {
            return;
        }


        function updateTopbar() {

            if (
                window.innerWidth > 768 &&
                sidebar.classList.contains(
                    "collapsed"
                )
            ) {

                topbar.style.left =
                    "var(--sidebar-collapsed-width)";

            }

            else if (
                window.innerWidth > 768
            ) {

                topbar.style.left =
                    "var(--sidebar-width)";

            }

            else {

                topbar.style.left = "0";

            }

        }


        updateTopbar();


        const observer =
            new MutationObserver(
                updateTopbar
            );


        observer.observe(
            sidebar,
            {
                attributes: true,
                attributeFilter: ["class"]
            }
        );


        window.addEventListener(
            "resize",
            updateTopbar
        );

    }
);

</script>