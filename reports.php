<?php
session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "student") {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reports - Leave Portal</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <style>

        body {
            margin: 0;
            background: #f5f6fa;
            font-family: Arial, sans-serif;
        }

        /* MAIN CONTENT */

        .main-content {
            margin-left: 250px;
            min-height: 100vh;
            padding: 30px;
            transition: margin-left 0.3s ease;
        }

        /* PAGE HEADER */

        .page-header {
            background: white;
            border-radius: 12px;
            padding: 25px 30px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        }

        .page-header h2 {
            margin: 0;
            font-size: 26px;
            font-weight: 700;
            color: #222;
        }

        .page-header p {
            margin: 6px 0 0;
            color: #777;
            font-size: 14px;
        }

        /* REPORT CARDS */

        .report-card {
            background: white;
            border-radius: 12px;
            padding: 35px;
            height: 100%;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            transition: all 0.2s ease;
        }

        .report-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 18px rgba(0,0,0,0.10);
        }

        .report-icon {
            width: 75px;
            height: 75px;
            margin: 0 auto 20px;
            border-radius: 12px;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 36px;
        }

        .excel-icon {
            background: #e8f7ef;
            color: #198754;
        }

        .pdf-icon {
            background: #fdeaea;
            color: #dc3545;
        }

        .report-card h4 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .report-card p {
            color: #777;
            font-size: 14px;
            min-height: 42px;
            margin-bottom: 25px;
        }

        .report-btn {
            padding: 10px 22px;
            border-radius: 7px;
            text-decoration: none;
            color: white;
            font-size: 14px;
            font-weight: 600;
            display: inline-block;
        }

        .excel-btn {
            background: #198754;
        }

        .excel-btn:hover {
            background: #157347;
            color: white;
        }

        .pdf-btn {
            background: #dc3545;
        }

        .pdf-btn:hover {
            background: #bb2d3b;
            color: white;
        }

        /* MOBILE */

        @media (max-width: 768px) {

            .main-content {
                margin-left: 0;
                padding: 20px;
            }

        }

    </style>

</head>

<body>

    <?php include "sidebar.php"; ?>


    <!-- MAIN CONTENT -->

    <main class="main-content">


        <!-- PAGE HEADER -->

        <div class="page-header">

            <h2>
                <i class="fa-solid fa-chart-column me-2"></i>
                Reports
            </h2>

            <p>
                Download and manage your OD application reports.
            </p>

        </div>


        <!-- REPORT OPTIONS -->

        <div class="row g-4">


            <!-- EXCEL -->

            <div class="col-lg-6">

                <div class="report-card">

                    <div class="report-icon excel-icon">

                        <i class="fa-solid fa-file-excel"></i>

                    </div>


                    <h4>
                        Excel Report
                    </h4>


                    <p>
                        Download your OD application report
                        in Excel format.
                    </p>


                    <a
                        href="download_od_excel.php"
                        class="report-btn excel-btn"
                    >

                        <i class="fa-solid fa-download me-2"></i>

                        Download Excel

                    </a>

                </div>

            </div>



            <!-- PDF -->

            <div class="col-lg-6">

                <div class="report-card">

                    <div class="report-icon pdf-icon">

                        <i class="fa-solid fa-file-pdf"></i>

                    </div>


                    <h4>
                        PDF Report
                    </h4>


                    <p>
                        Download your OD application report
                        in PDF format.
                    </p>


                    <a
                        href="download_od_pdf.php"
                        class="report-btn pdf-btn"
                    >

                        <i class="fa-solid fa-download me-2"></i>

                        Download PDF

                    </a>

                </div>

            </div>


        </div>


    </main>


</body>

</html>