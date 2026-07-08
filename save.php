<?php
// save.php
session_start();

// Include database connection
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Verify CSRF Token
if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    header('Location: index.php?status=error&msg=' . urlencode('സെഷൻ കാലാവധി കഴിഞ്ഞു. ദയവായി വീണ്ടും ശ്രമിക്കുക. (Invalid CSRF)'));
    exit;
}

// Extract and Sanitize Input Data
$applicant_name     = trim($_POST['applicant_name'] ?? '');
$age                = filter_input(INPUT_POST, 'age', FILTER_VALIDATE_INT) ?: null;
$dob                = trim($_POST['dob'] ?? '') ?: null;
$gender             = trim($_POST['gender'] ?? '');
$address            = trim($_POST['address'] ?? '') ?: null;
$pin                = trim($_POST['pin'] ?? '') ?: null;
$mobile             = trim($_POST['mobile'] ?? '') ?: null;
$whatsapp           = trim($_POST['whatsapp'] ?? '') ?: null;
$education_secular   = trim($_POST['education_secular'] ?? '') ?: null;
$education_religious = trim($_POST['education_religious'] ?? '') ?: null;
$child_name         = trim($_POST['child_name'] ?? '') ?: null;
$child_class        = trim($_POST['child_class'] ?? '') ?: null;
$madrasa            = trim($_POST['madrasa'] ?? '') ?: null;
$complex            = trim($_POST['complex'] ?? '') ?: null;
$district           = trim($_POST['district'] ?? '') ?: null;
$relationship       = trim($_POST['relationship'] ?? '') ?: null;
$payment_info       = trim($_POST['payment_info'] ?? '') ?: null;

// Basic validation
if (empty($applicant_name)) {
    header('Location: index.php?status=error&msg=' . urlencode('അപേക്ഷകന്റെ പേര് നിർബന്ധമാണ്.'));
    exit;
}

if (empty($gender)) {
    header('Location: index.php?status=error&msg=' . urlencode('ലിംഗം തിരഞ്ഞെടുക്കുന്നത് നിർബന്ധമാണ്.'));
    exit;
}

try {
    // Start transaction to ensure both inserts happen together
    $pdo->beginTransaction();

    // 1. Insert into applicants table
    $sqlApplicant = "INSERT INTO applicants (
                applicant_name, age, dob, gender, address, pin, mobile, whatsapp, 
                education_secular, education_religious, payment_info
            ) VALUES (
                :applicant_name, :age, :dob, :gender, :address, :pin, :mobile, :whatsapp, 
                :education_secular, :education_religious, :payment_info
            )";
            
    $stmtApp = $pdo->prepare($sqlApplicant);
    $stmtApp->execute([
        ':applicant_name'     => $applicant_name,
        ':age'                => $age,
        ':dob'                => $dob,
        ':gender'             => $gender,
        ':address'            => $address,
        ':pin'                => $pin,
        ':mobile'             => $mobile,
        ':whatsapp'           => $whatsapp,
        ':education_secular'   => $education_secular,
        ':education_religious' => $education_religious,
        ':payment_info'       => $payment_info
    ]);
    
    // Get unique ID of the inserted applicant
    $applicant_id = $pdo->lastInsertId();

    // 2. Insert into children table
    $sqlChild = "INSERT INTO children (
                applicant_id, child_name, child_class, madrasa, complex, district, relationship
            ) VALUES (
                :applicant_id, :child_name, :child_class, :madrasa, :complex, :district, :relationship
            )";
            
    $stmtChild = $pdo->prepare($sqlChild);
    $stmtChild->execute([
        ':applicant_id' => $applicant_id,
        ':child_name'   => $child_name,
        ':child_class'  => $child_class,
        ':madrasa'      => $madrasa,
        ':complex'      => $complex,
        ':district'     => $district,
        ':relationship' => $relationship
    ]);

    // Commit transaction
    $pdo->commit();
    
    // Clear CSRF token to prevent double-submits
    unset($_SESSION['csrf_token']);
    
    header('Location: index.php?status=success');
    exit;
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Log the actual error internally in production, display friendly error here
    error_log("Registration Save Error: " . $e->getMessage());
    header('Location: index.php?status=error&msg=' . urlencode('ഡാറ്റാബേസിൽ സേവ് ചെയ്യാൻ സാധിച്ചില്ല. ദയവായി വീണ്ടും ശ്രമിക്കുക.'));
    exit;
}
