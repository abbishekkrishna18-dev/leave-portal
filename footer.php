<?php
/*
=========================================================
LEAVE PORTAL - FOOTER
M.Kumarasamy College of Engineering
=========================================================
*/
?>

<footer class="main-footer">

    <div class="footer-content">

        <div class="footer-left">

            <span>
                <i class="fa-solid fa-graduation-cap me-1"></i>
                Leave Portal
            </span>

        </div>


        <div class="footer-center">

            <span>
                Technology Innovation Hub - MKCE
            </span>

        </div>


        <div class="footer-right">

            <span>
                &copy; <?php echo date("Y"); ?> MKCE
            </span>

        </div>

    </div>

</footer>


<style>

    .main-footer {

        position: fixed;

        left: 250px;

        right: 0;

        bottom: 0;

        height: 60px;

        background: linear-gradient(
            135deg,
            #4e73df,
            #1cc88a
        );

        color: #ffffff;

        z-index: 999;

        transition: left 0.3s ease;

        box-shadow:
            0 -2px 10px rgba(0, 0, 0, 0.08);

    }


    .footer-content {

        height: 100%;

        display: flex;

        align-items: center;

        justify-content: space-between;

        padding: 0 25px;

        font-size: 13px;

    }


    .footer-left {

        font-weight: 600;

    }


    .footer-center {

        text-align: center;

        opacity: 0.95;

    }


    .footer-right {

        font-size: 12px;

        opacity: 0.9;

    }


    /* =====================================================
       SIDEBAR COLLAPSED
    ===================================================== */

    body.sidebar-collapsed .main-footer {

        left: 70px;

    }


    /* =====================================================
       TABLET / MOBILE
    ===================================================== */

    @media (max-width: 991px) {

        .main-footer {

            left: 0;

        }


        body.sidebar-collapsed .main-footer {

            left: 0;

        }

    }


    @media (max-width: 767px) {

        .footer-content {

            padding: 0 15px;

            font-size: 11px;

        }


        .footer-left {

            display: none;

        }


        .footer-center {

            flex: 1;

            text-align: left;

        }


        .footer-right {

            font-size: 10px;

        }

    }


    @media (max-width: 400px) {

        .footer-content {

            padding: 0 10px;

        }


        .footer-center {

            font-size: 10px;

        }

    }

</style>