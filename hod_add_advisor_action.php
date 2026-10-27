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


// --------------------------------------------------
// ONLY POST REQUESTS
// --------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: hod_add_advisor.php");
    exit();
}


// --------------------------------------------------
// GET FORM DATA
// --------------------------------------------------

$name = isset($_POST['name'])
    ? trim($_POST['name'])
    : '';

$username = isset($_POST['username'])
    ? trim($_POST['username'])
    : '';

$email = isset($_POST['email'])
    ? trim($_POST['email'])
    : '';

$password = isset($_POST['password'])
    ? $_POST['password']
    : '';

$confirm_password = isset($_POST['confirm_password'])
    ? $_POST['confirm_password']
    : '';


// --------------------------------------------------
// BASIC VALIDATION
// --------------------------------------------------

if (
    $name === '' ||
    $username === '' ||
    $email === '' ||
    $password === '' ||
    $confirm_password === ''
) {
    $_SESSION['error'] = "Please fill all fields.";
    header("Location: hod_add_advisor.php");
    exit();
}


// --------------------------------------------------
// NAME VALIDATION
// --------------------------------------------------

if (strlen($name) > 100) {
    $_SESSION['error'] = "Name is too long.";
    header("Location: hod_add_advisor.php");
    exit();
}


// --------------------------------------------------
// USERNAME VALIDATION
// --------------------------------------------------

if (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) {

    $_SESSION['error'] =
        "Username must contain only letters, numbers and underscore, and be 3-50 characters.";

    header("Location: hod_add_advisor.php");
    exit();
}


// --------------------------------------------------
// EMAIL VALIDATION
// --------------------------------------------------

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    $_SESSION['error'] =
        "Please enter a valid email address.";

    header("Location: hod_add_advisor.php");
    exit();
}


// --------------------------------------------------
// PASSWORD VALIDATION
// --------------------------------------------------

if (strlen($password) < 6) {

    $_SESSION['error'] =
        "Password must contain at least 6 characters.";

    header("Location: hod_add_advisor.php");
    exit();
}


// --------------------------------------------------
// CONFIRM PASSWORD
// --------------------------------------------------

if ($password !== $confirm_password) {

    $_SESSION['error'] =
        "Passwords do not match.";

    header("Location: hod_add_advisor.php");
    exit();
}


// --------------------------------------------------
// CHECK USERNAME ALREADY EXISTS
// --------------------------------------------------

$sql = "
    SELECT id
    FROM users
    WHERE username = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {

    $_SESSION['error'] =
        "Database error while checking username.";

    header("Location: hod_add_advisor.php");
    exit();
}

mysqli_stmt_bind_param(
    $stmt,
    "s",
    $username
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$existing_user = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


if ($existing_user) {

    $_SESSION['error'] =
        "This username is already in use. Please choose another username.";

    header("Location: hod_add_advisor.php");
    exit();
}


// --------------------------------------------------
// CHECK EMAIL ALREADY EXISTS
// --------------------------------------------------

$sql = "
    SELECT id
    FROM users
    WHERE email = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {

    $_SESSION['error'] =
        "Database error while checking email.";

    header("Location: hod_add_advisor.php");
    exit();
}

mysqli_stmt_bind_param(
    $stmt,
    "s",
    $email
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$existing_email = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


if ($existing_email) {

    $_SESSION['error'] =
        "This email address is already registered.";

    header("Location: hod_add_advisor.php");
    exit();
}


// --------------------------------------------------
// HASH PASSWORD
// --------------------------------------------------

$hashed_password = password_hash(
    $password,
    PASSWORD_DEFAULT
);


// --------------------------------------------------
// INSERT NEW ADVISOR
// --------------------------------------------------

$role = 'advisor';

$sql = "
    INSERT INTO users
    (
        username,
        password,
        name,
        email,
        role
    )
    VALUES (?, ?, ?, ?, ?)
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {

    $_SESSION['error'] =
        "Unable to create advisor account.";

    header("Location: hod_add_advisor.php");
    exit();
}

mysqli_stmt_bind_param(
    $stmt,
    "sssss",
    $username,
    $hashed_password,
    $name,
    $email,
    $role
);


if (mysqli_stmt_execute($stmt)) {

    $_SESSION['success'] =
        "Advisor account created successfully. Username: " .
        $username;

} else {

    $_SESSION['error'] =
        "Failed to create advisor account.";
}

mysqli_stmt_close($stmt);


// --------------------------------------------------
// RETURN TO ADD ADVISOR PAGE
// --------------------------------------------------

header("Location: hod_add_advisor.php");
exit();

?>