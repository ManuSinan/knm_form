<?php
// db.php
// MySQL Database connection and initialization

$host = 'localhost';
$db   = 'knm_form';
$user = 'root';
$pass = 'WordPressDB!234%'; // Local phpMyAdmin usually has empty password
$charset = 'utf8mb4';

try {
    // 1. First connect to MySQL without selecting database to ensure it exists
    $dsnNoDb = "mysql:host=$host;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdoInit = new PDO($dsnNoDb, $user, $pass, $options);
    
    // Create database if not exists
    $pdoInit->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    
    // 2. Reconnect to the specific database
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Drop temporary/unneeded tables we created previously
    $pdo->exec("DROP TABLE IF EXISTS registrations;");
    $pdo->exec("DROP TABLE IF EXISTS districts;");
    $pdo->exec("DROP TABLE IF EXISTS complexes;");
    
    // Create the applicants table if it does not exist
    $createApplicantsSQL = "
        CREATE TABLE IF NOT EXISTS applicants (
            id INT AUTO_INCREMENT PRIMARY KEY,
            applicant_name VARCHAR(255) NOT NULL,
            dob VARCHAR(20) NULL,
            gender VARCHAR(10) NOT NULL,
            address TEXT NULL,
            pin VARCHAR(10) NULL,
            mobile VARCHAR(20) NULL,
            whatsapp VARCHAR(20) NULL,
            education_secular VARCHAR(50) NULL,
            education_religious VARCHAR(50) NULL,
            payment_info TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $pdo->exec($createApplicantsSQL);

    // Create the children table if it does not exist
    $createChildrenSQL = "
        CREATE TABLE IF NOT EXISTS children (
            id INT AUTO_INCREMENT PRIMARY KEY,
            applicant_id INT NOT NULL,
            child_name VARCHAR(255) NULL,
            child_class VARCHAR(50) NULL,
            madrasa VARCHAR(255) NULL,
            complex VARCHAR(255) NULL,
            district VARCHAR(255) NULL,
            relationship VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (applicant_id) REFERENCES applicants(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $pdo->exec($createChildrenSQL);
    
} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}
