<?php

session_start();
require_once "db.php";


/* =========================================================
   ACCESS CONTROL
========================================================= */

if (
    !isset($_SESSION['user_id']) ||
    $_SESSION['role'] !== 'advisor'
) {
    header("Location: index.php");
    exit;
}

$advisor_id = (int) $_SESSION['user_id'];


/* =========================================================
   POST ONLY
========================================================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header("Location: advisor_assign_students.php");
    exit;

}


$class_id = isset($_POST['class_id'])
    ? (int)$_POST['class_id']
    : 0;

$student_ids = $_POST['student_ids'] ?? [];


/* =========================================================
   VALIDATE CLASS
========================================================= */

if ($class_id <= 0) {

    $_SESSION['error'] =
        "Invalid class.";

    header("Location: advisor_assign_students.php");
    exit;

}


/* =========================================================
   VERIFY THAT THIS CLASS BELONGS TO ADVISOR
========================================================= */

$stmt = $conn->prepare("
    SELECT
        c.id,
        c.department,
        c.year,
        c.section
    FROM class_advisors ca

    INNER JOIN classes c
        ON c.id = ca.class_id

    WHERE ca.advisor_id = ?
      AND c.id = ?

    LIMIT 1
");

$stmt->bind_param(
    "ii",
    $advisor_id,
    $class_id
);

$stmt->execute();

$result = $stmt->get_result();

$class = $result->fetch_assoc();

$stmt->close();


if (!$class) {

    $_SESSION['error'] =
        "You are not authorized to manage this class.";

    header("Location: advisor_assign_students.php");
    exit;

}


/* =========================================================
   START TRANSACTION
========================================================= */

$conn->begin_transaction();


try {


    /* =====================================================
       REMOVE CURRENT ASSIGNMENTS
    ===================================================== */

    $stmt = $conn->prepare("
        DELETE FROM student_classes
        WHERE class_id = ?
    ");

    $stmt->bind_param(
        "i",
        $class_id
    );

    if (!$stmt->execute()) {

        throw new Exception(
            "Unable to clear existing assignments."
        );

    }

    $stmt->close();


    /* =====================================================
       ADD SELECTED STUDENTS
    ===================================================== */

    if (is_array($student_ids)) {

        $stmt = $conn->prepare("
            INSERT INTO student_classes
            (
                student_id,
                class_id
            )
            VALUES (?, ?)
        ");


        foreach ($student_ids as $student_id) {

            $student_id = (int)$student_id;


            if ($student_id <= 0) {
                continue;
            }


            /* ---------------------------------------------
               VERIFY STUDENT ROLE
            --------------------------------------------- */

            $check = $conn->prepare("
                SELECT id
                FROM users
                WHERE id = ?
                  AND role = 'student'
                LIMIT 1
            ");

            $check->bind_param(
                "i",
                $student_id
            );

            $check->execute();

            $check_result =
                $check->get_result();

            $student_exists =
                $check_result->fetch_assoc();

            $check->close();


            if (!$student_exists) {
                continue;
            }


            $stmt->bind_param(
                "ii",
                $student_id,
                $class_id
            );


            if (!$stmt->execute()) {

                throw new Exception(
                    "Unable to assign a student."
                );

            }

        }


        $stmt->close();

    }


    /* =====================================================
       COMMIT
    ===================================================== */

    $conn->commit();


    $_SESSION['success'] =
        "Student assignment updated successfully.";

} catch (Exception $e) {


    /* =====================================================
       ROLLBACK
    ===================================================== */

    $conn->rollback();


    $_SESSION['error'] =
        "Unable to update student assignment.";

}


header("Location: advisor_assign_students.php");
exit;

?>