<?php
// admin.php
// Admin Dashboard for KNM Registrations

require_once __DIR__ . '/db.php';

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="knm_registrations_' . date('Y-m-d_H-i') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for Malayalam/Excel compatibility
    fwrite($output, "\xEF\xBB\xBF");
    
    // CSV headers
    fputcsv($output, [
        'ID',
        'Applicant Name (അപേക്ഷകന്റെ പേര്)',
        'DOB (ജനന തീയതി)',
        'Gender (ലിംഗം)',
        'Address (വിലാസം)',
        'PIN (പിൻ)',
        'Mobile (മൊബൈൽ)',
        'WhatsApp (വാട്സാപ്പ്)',
        'Secular Edu (ഭൗതിക വിദ്യാഭ്യാസം)',
        'Religious Edu (മതപരം)',
        'Child Name (കുട്ടിയുടെ പേര്)',
        'Child Class (ക്ലാസ്)',
        'Madrasa (മദ്റസ)',
        'Complex (കോംപ്ലക്സ്)',
        'District (ജില്ല)',
        'Relationship (ബന്ധം)',
        'Payment Details (പണമടച്ച വിവരം)',
        'Registration Date (രജിസ്റ്റർ ചെയ്ത തീയതി)'
    ]);
    
    try {
        $stmt = $pdo->query("
            SELECT a.*, c.child_name, c.child_class, c.madrasa, c.complex, c.district, c.relationship 
            FROM applicants a
            LEFT JOIN children c ON a.id = c.applicant_id
            ORDER BY a.id DESC
        ");
        while ($row = $stmt->fetch()) {
            fputcsv($output, [
                $row['id'],
                $row['applicant_name'],
                $row['dob'],
                $row['gender'],
                $row['address'],
                $row['pin'],
                $row['mobile'],
                $row['whatsapp'],
                $row['education_secular'],
                $row['education_religious'],
                $row['child_name'],
                $row['child_class'],
                $row['madrasa'],
                $row['complex'],
                $row['district'],
                $row['relationship'],
                $row['payment_info'],
                $row['created_at']
            ]);
        }
    } catch (PDOException $e) {
        error_log("CSV Export Error: " . $e->getMessage());
    }
    
    fclose($output);
    exit;
}

// Fetch stats
$totalCount = 0;
$maleCount = 0;
$femaleCount = 0;
try {
    $totalCount = $pdo->query("SELECT COUNT(*) FROM applicants")->fetchColumn();
    $maleCount = $pdo->query("SELECT COUNT(*) FROM applicants WHERE gender = 'Male'")->fetchColumn();
    $femaleCount = $pdo->query("SELECT COUNT(*) FROM applicants WHERE gender = 'Female'")->fetchColumn();
} catch (PDOException $e) {
    error_log("Stats Fetch Error: " . $e->getMessage());
}

// Handle Search and Filter
$search = trim($_GET['search'] ?? '');
$registrants = [];
try {
    if (!empty($search)) {
        $sql = "SELECT a.*, c.child_name, c.child_class, c.madrasa, c.complex, c.district, c.relationship 
                FROM applicants a
                LEFT JOIN children c ON a.id = c.applicant_id
                WHERE a.applicant_name LIKE :search 
                   OR a.mobile LIKE :search 
                   OR a.whatsapp LIKE :search 
                   OR c.district LIKE :search 
                   OR c.child_name LIKE :search 
                ORDER BY a.id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':search' => "%$search%"]);
        $registrants = $stmt->fetchAll();
    } else {
        $sql = "SELECT a.*, c.child_name, c.child_class, c.madrasa, c.complex, c.district, c.relationship 
                FROM applicants a
                LEFT JOIN children c ON a.id = c.applicant_id
                ORDER BY a.id DESC";
        $stmt = $pdo->query($sql);
        $registrants = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    error_log("Fetch Registrants Error: " . $e->getMessage());
}

// Handle Single Registrant View
$viewRegistrant = null;
if (isset($_GET['view_id'])) {
    $viewId = filter_input(INPUT_GET, 'view_id', FILTER_VALIDATE_INT);
    if ($viewId) {
        try {
            $stmt = $pdo->prepare("
                SELECT a.*, c.child_name, c.child_class, c.madrasa, c.complex, c.district, c.relationship 
                FROM applicants a
                LEFT JOIN children c ON a.id = c.applicant_id
                WHERE a.id = :id
            ");
            $stmt->execute([':id' => $viewId]);
            $viewRegistrant = $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Fetch Single Registrant Error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ml">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KNM Education Board - Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Extra styles specific to Admin Dashboard */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.25rem;
            box-shadow: var(--shadow-sm);
            text-align: center;
            border: 1px solid var(--border-color);
        }
        .stat-number {
            font-family: var(--font-num);
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        .stat-label {
            font-size: 0.9rem;
            color: var(--text-muted);
            font-weight: 600;
        }
        .flex-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1.5rem;
        }
        .text-center-empty {
            text-align: center;
            padding: 2rem;
            color: var(--text-muted);
            font-weight: 500;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 1.5rem;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        @media (max-width: 600px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .admin-header {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>

<div class="container-admin">

    <!-- View Registrant Modal/Section -->
    <?php if ($viewRegistrant): ?>
        <div class="card">
            <a href="admin.php<?php echo !empty($search) ? '?search=' . urlencode($search) : ''; ?>" class="back-link">&larr; Back to List</a>
            <div class="section-title">Applicant Complete Details (ID: <?php echo $viewRegistrant['id']; ?>)</div>
            
            <div class="details-grid">
                
                <div class="detail-item">
                    <div class="detail-label">Applicant Name</div>
                    <div class="detail-value"><?php echo htmlspecialchars($viewRegistrant['applicant_name']); ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Gender</div>
                    <div class="detail-value"><?php echo htmlspecialchars($viewRegistrant['gender']); ?></div>
                </div>



                <div class="detail-item">
                    <div class="detail-label">Date of Birth</div>
                    <div class="detail-value detail-value-num"><?php echo htmlspecialchars($viewRegistrant['dob'] ?? '-'); ?></div>
                </div>

                <div class="detail-item col-12" style="grid-column: span 2;">
                    <div class="detail-label">Address</div>
                    <div class="detail-value"><?php echo nl2br(htmlspecialchars($viewRegistrant['address'] ?? '-')); ?></div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">PIN Code</div>
                    <div class="detail-value detail-value-num"><?php echo htmlspecialchars($viewRegistrant['pin'] ?? '-'); ?></div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Mobile Number</div>
                    <div class="detail-value detail-value-num"><?php echo htmlspecialchars($viewRegistrant['mobile'] ?? '-'); ?></div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">WhatsApp Number</div>
                    <div class="detail-value detail-value-num"><?php echo htmlspecialchars($viewRegistrant['whatsapp'] ?? '-'); ?></div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Secular Qualification</div>
                    <div class="detail-value"><?php echo htmlspecialchars($viewRegistrant['education_secular'] ?? '-'); ?></div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Religious Qualification</div>
                    <div class="detail-value detail-value-num"><?php echo htmlspecialchars($viewRegistrant['education_religious'] ?? '-'); ?></div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Child's Name</div>
                    <div class="detail-value"><?php echo htmlspecialchars($viewRegistrant['child_name'] ?? '-'); ?></div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Class</div>
                    <div class="detail-value"><?php echo htmlspecialchars($viewRegistrant['child_class'] ?? '-'); ?></div>
                </div>

                <div class="detail-item" style="grid-column: span 2;">
                    <div class="detail-label">Madrasa Name and Place</div>
                    <div class="detail-value"><?php echo htmlspecialchars($viewRegistrant['madrasa'] ?? '-'); ?></div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Complex</div>
                    <div class="detail-value"><?php echo htmlspecialchars($viewRegistrant['complex'] ?? '-'); ?></div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">District</div>
                    <div class="detail-value"><?php echo htmlspecialchars($viewRegistrant['district'] ?? '-'); ?></div>
                </div>

                <div class="detail-item" style="grid-column: span 2;">
                    <div class="detail-label">Relationship with Child</div>
                    <div class="detail-value"><?php echo htmlspecialchars($viewRegistrant['relationship'] ?? '-'); ?></div>
                </div>

                <div class="detail-item col-12" style="grid-column: span 2;">
                    <div class="detail-label">Payment Details</div>
                    <div class="detail-value"><?php echo nl2br(htmlspecialchars($viewRegistrant['payment_info'] ?? '-')); ?></div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Registration Date</div>
                    <div class="detail-value detail-value-num"><?php echo htmlspecialchars($viewRegistrant['created_at']); ?></div>
                </div>

            </div>
        </div>
    <?php endif; ?>

    <!-- Main Dashboard Card -->
    <div class="card">
        
        <div class="admin-header">
            <div>
                <h1 class="admin-title">Admin Dashboard</h1>
                <p style="color: var(--text-muted)">Certificate Course in Islamic Studies - Registrations</p>
            </div>
            <div>
                <a href="index.php" class="btn btn-secondary" style="margin-right: 0.5rem;">Registration Form</a>
                <a href="admin.php?export=csv" class="btn btn-accent">&darr; Export to CSV</a>
            </div>
        </div>

        <!-- Statistics Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $totalCount; ?></div>
                <div class="stat-label">Total Registrations</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $maleCount; ?></div>
                <div class="stat-label">Males</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $femaleCount; ?></div>
                <div class="stat-label">Females</div>
            </div>
        </div>

        <!-- Search Bar -->
        <form method="GET" action="admin.php" class="search-bar">
            <input type="text" name="search" class="form-control search-input" placeholder="Search by Name, Phone, District, Child's Name..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn btn-primary" style="margin-top: 0; padding: 0.75rem 1.5rem;">Search</button>
            <?php if (!empty($search)): ?>
                <a href="admin.php" class="btn btn-secondary" style="display: flex; align-items: center; justify-content: center; height: 100%;">Clear</a>
            <?php endif; ?>
        </form>

        <!-- Registrants Table -->
        <div class="table-responsive">
            <table class="table-registrants">
                <thead>
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th>Applicant Name</th>
                        <th style="width: 80px;">Gender</th>
                        <th>Mobile</th>
                        <th>District</th>
                        <th>Child's Name</th>
                        <th>Secular / Religious</th>
                        <th>Date</th>
                        <th style="width: 100px; text-align: center;">Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($registrants)): ?>
                        <tr>
                            <td colspan="9" class="text-center-empty">No registrations found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($registrants as $row): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td style="font-weight: 600;"><?php echo htmlspecialchars($row['applicant_name']); ?></td>
                                <td>
                                    <?php if ($row['gender'] === 'Male'): ?>
                                        <span class="badge badge-gender-male">Male</span>
                                    <?php else: ?>
                                        <span class="badge badge-gender-female">Female</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['mobile'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($row['district'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($row['child_name'] ?? '-'); ?></td>
                                <td>
                                    <span style="font-weight: 500;"><?php echo htmlspecialchars($row['education_secular'] ?? '-'); ?></span> / 
                                    <span style="font-weight: 500;"><?php echo htmlspecialchars($row['education_religious'] ?? '-'); ?></span>
                                </td>
                                <td><?php echo date('Y-m-d', strtotime($row['created_at'])); ?></td>
                                <td style="text-align: center;">
                                    <a href="admin.php?view_id=<?php echo $row['id']; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.4rem 0.8rem; border-radius: 6px;">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="footer-text">
            &copy; <?php echo date('Y'); ?> KNM Education Board. All rights reserved.
        </div>

    </div>

</div>

</body>
</html>
