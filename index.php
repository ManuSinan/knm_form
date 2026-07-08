<?php
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch districts and complexes from DB
require_once __DIR__ . '/db.php';
$districts = [];
try {
    $stmt = $pdo->query("SELECT district_name FROM district ORDER BY district_name ASC");
    $districts = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("Failed to fetch districts: " . $e->getMessage());
}

$complexes = [];
try {
    $stmt = $pdo->query("SELECT complex_name FROM complex ORDER BY complex_name ASC");
    $complexes = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("Failed to fetch complexes: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ml">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KNM Education Board - Registration</title>
    <!-- Custom Style Sheet -->
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <div class="card">
        
        <!-- Header -->
        <div class="header">
            <h1>സർട്ടിഫിക്കറ്റ് കോഴ്സ് ഇൻ ഇസ്ലാമിക് സ്റ്റഡീസ്</h1>
            <h2>Run by: KNM Education Board</h2>
            <h3>രജിസ്ട്രേഷൻ ഫോം</h3>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_GET['status'])): ?>
            <?php if ($_GET['status'] === 'success'): ?>
                <div class="alert alert-success">
                    രജിസ്ട്രേഷൻ വിജയകരമായി പൂർത്തിയായി! വിവരങ്ങൾ സേവ് ചെയ്തിട്ടുണ്ട്.
                </div>
            <?php elseif ($_GET['status'] === 'error'): ?>
                <div class="alert alert-danger">
                    അപേക്ഷ സമർപ്പിക്കുന്നതിൽ ഒരു തകരാറുണ്ടായി: <?php echo htmlspecialchars($_GET['msg'] ?? 'അജ്ഞാതമായ പിശക്'); ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Registration Form -->
        <form action="save.php" method="POST" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <!-- Applicant Information -->
            <div class="section-title">അപേക്ഷകന്റെ വിവരങ്ങൾ</div>
            <div class="form-grid">
                
                <div class="form-group col-8">
                    <label for="applicant_name">അപേക്ഷകന്റെ പേര് <span style="color: var(--error-color)">*</span></label>
                    <input type="text" id="applicant_name" name="applicant_name" class="form-control" placeholder="മുഴുവൻ പേര് എഴുതുക" required>
                </div>

                <div class="form-group col-4">
                    <label for="dob">ജനന തീയതി</label>
                    <input type="date" id="dob" name="dob" class="form-control">
                </div>

                <div class="form-group col-12">
                    <label>ലിംഗം <span style="color: var(--error-color)">*</span></label>
                    <div class="gender-selector">
                        <label class="gender-option">
                            <input type="radio" name="gender" value="Male" required>
                            <span>ആൺ</span>
                        </label>
                        <label class="gender-option">
                            <input type="radio" name="gender" value="Female">
                            <span>പെൺ</span>
                        </label>
                    </div>
                </div>

                <div class="form-group col-12">
                    <label for="address">പൂർണ്ണ വിലാസം</label>
                    <textarea id="address" name="address" rows="3" class="form-control" placeholder="വീട്ടുപേര്, സ്ഥലം, പോസ്റ്റ് ഓഫീസ് വിവരങ്ങൾ അടങ്ങുന്ന വിലാസം"></textarea>
                </div>

                <div class="form-group col-4">
                    <label for="pin">പിൻ കോഡ്</label>
                    <input type="text" id="pin" name="pin" class="form-control" pattern="[0-9]{6}" placeholder="6 അക്ക നമ്പർ" title="ദയവായി 6 അക്ക പിൻ കോഡ് നൽകുക">
                </div>

                <div class="form-group col-4">
                    <label for="mobile">മൊബൈൽ നമ്പർ</label>
                    <input type="text" id="mobile" name="mobile" class="form-control" pattern="[0-9]{10,12}" placeholder="ഫോൺ നമ്പർ" title="സാധുവായ ഫോൺ നമ്പർ നൽകുക">
                </div>

                <div class="form-group col-4">
                    <label for="whatsapp">വാട്സാപ്പ് നമ്പർ</label>
                    <input type="text" id="whatsapp" name="whatsapp" class="form-control" pattern="[0-9]{10,12}" placeholder="വാട്സാപ്പ് നമ്പർ" title="സാധുവായ വാട്സാപ്പ് നമ്പർ നൽകുക">
                </div>

            </div>

            <!-- Educational Qualification -->
            <div class="section-title">വിദ്യാഭ്യാസ യോഗ്യത</div>
            
            <div class="form-group mb-3">
                <label>ഭൗതികം (Secular Qualification)</label>
                <div class="radio-group">
                    <input type="radio" id="edu_sslc" name="education_secular" value="SSLC" class="radio-btn-input">
                    <label for="edu_sslc" class="radio-btn-label">SSLC</label>

                    <input type="radio" id="edu_plus2" name="education_secular" value="+2" class="radio-btn-input">
                    <label for="edu_plus2" class="radio-btn-label">+2</label>

                    <input type="radio" id="edu_degree" name="education_secular" value="Degree" class="radio-btn-input">
                    <label for="edu_degree" class="radio-btn-label">Degree</label>

                    <input type="radio" id="edu_pg" name="education_secular" value="PG" class="radio-btn-input">
                    <label for="edu_pg" class="radio-btn-label">PG</label>
                </div>
            </div>

            <div class="form-group">
                <label>മതപരം (Religious Qualification)</label>
                <div class="radio-group">
                    <input type="radio" id="rel_5" name="education_religious" value="5" class="radio-btn-input">
                    <label for="rel_5" class="radio-btn-label">5</label>

                    <input type="radio" id="rel_7" name="education_religious" value="7" class="radio-btn-input">
                    <label for="rel_7" class="radio-btn-label">7</label>

                    <input type="radio" id="rel_10" name="education_religious" value="10" class="radio-btn-input">
                    <label for="rel_10" class="radio-btn-label">10</label>
                </div>
            </div>

            <!-- Child Details -->
            <div class="section-title">കുട്ടിയുടെ വിവരങ്ങൾ</div>
            <div class="form-grid">
                
                <div class="form-group col-6">
                    <label for="child_name">കുട്ടിയുടെ പേര്</label>
                    <input type="text" id="child_name" name="child_name" class="form-control" placeholder="കുട്ടിയുടെ പേര്">
                </div>

                <div class="form-group col-6">
                    <label for="child_class">ക്ലാസ്</label>
                    <input type="text" id="child_class" name="child_class" class="form-control" placeholder="ക്ലാസ്">
                </div>

                <div class="form-group col-12">
                    <label for="madrasa">മദ്റസയുടെ പേരും സ്ഥലവും</label>
                    <input type="text" id="madrasa" name="madrasa" class="form-control" placeholder="മദ്റസയുടെ വിവരങ്ങൾ">
                </div>

                <div class="form-group col-6">
                    <label>കോംപ്ലക്സ്</label>
                    <div class="searchable-select-container">
                        <input type="text" class="form-control searchable-select-input" placeholder="തിരഞ്ഞെടുക്കുക..." readonly>
                        <input type="hidden" name="complex" class="searchable-select-value">
                        <div class="searchable-select-dropdown">
                            <div class="searchable-select-search-box">
                                <input type="text" class="form-control searchable-select-search-input" placeholder="തിരയുക...">
                            </div>
                            <ul class="searchable-select-options">
                                <li data-value="" class="searchable-select-option empty-option">തിരഞ്ഞെടുക്കുക...</li>
                                <?php foreach ($complexes as $c): ?>
                                    <li data-value="<?php echo htmlspecialchars($c); ?>" class="searchable-select-option"><?php echo htmlspecialchars($c); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="form-group col-6">
                    <label>ജില്ല</label>
                    <div class="searchable-select-container">
                        <input type="text" class="form-control searchable-select-input" placeholder="തിരഞ്ഞെടുക്കുക..." readonly>
                        <input type="hidden" name="district" class="searchable-select-value">
                        <div class="searchable-select-dropdown">
                            <div class="searchable-select-search-box">
                                <input type="text" class="form-control searchable-select-search-input" placeholder="തിരയുക...">
                            </div>
                            <ul class="searchable-select-options">
                                <li data-value="" class="searchable-select-option empty-option">തിരഞ്ഞെടുക്കുക...</li>
                                <?php foreach ($districts as $d): ?>
                                    <li data-value="<?php echo htmlspecialchars($d); ?>" class="searchable-select-option"><?php echo htmlspecialchars($d); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="form-group col-12">
                    <label for="relationship">കുട്ടിയുമായി അപേക്ഷകനുള്ള ബന്ധം</label>
                    <input type="text" id="relationship" name="relationship" class="form-control" placeholder="ബന്ധം (ഉദാ: പിതാവ്, മാതാവ്, രക്ഷിതാവ്...)">
                </div>

            </div>

            <!-- Payment Information -->
            <div class="section-title">പണമടച്ച വിവരം</div>
            <div class="form-grid">
                <div class="form-group col-12">
                    <label for="payment_info">പണമടച്ച വിവരം (Payment details, transaction ID, bank...)</label>
                    <textarea id="payment_info" name="payment_info" rows="3" class="form-control" placeholder="ഫീസടച്ച തീയതി, ബാങ്ക് അക്കൗണ്ട്/UPI ട്രാൻസാക്ഷൻ നമ്പർ തുടങ്ങിയ വിവരങ്ങൾ ഇവിടെ രേഖപ്പെടുത്തുക."></textarea>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">രജിസ്റ്റർ ചെയ്യുക</button>

        </form>

        <div class="footer-text">
            &copy; <?php echo date('Y'); ?> KNM Education Board. All rights reserved.
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const dropdowns = document.querySelectorAll('.searchable-select-container');

    dropdowns.forEach(container => {
        const input = container.querySelector('.searchable-select-input');
        const hiddenInput = container.querySelector('.searchable-select-value');
        const dropdown = container.querySelector('.searchable-select-dropdown');
        const searchInput = container.querySelector('.searchable-select-search-input');
        const optionsList = container.querySelector('.searchable-select-options');
        const options = container.querySelectorAll('.searchable-select-option');

        // Open/Close dropdown
        input.addEventListener('click', function (e) {
            e.stopPropagation();
            
            // Close other open dropdowns
            dropdowns.forEach(other => {
                if (other !== container) {
                    other.classList.remove('open');
                }
            });
            
            container.classList.toggle('open');
            if (container.classList.contains('open')) {
                searchInput.value = '';
                // reset filtering
                options.forEach(opt => opt.classList.remove('hidden'));
                searchInput.focus();
            }
        });

        // Search options
        searchInput.addEventListener('input', function (e) {
            const query = e.target.value.toLowerCase().trim();
            options.forEach(opt => {
                const text = opt.textContent.toLowerCase();
                if (text.includes(query) || opt.classList.contains('empty-option')) {
                    opt.classList.remove('hidden');
                } else {
                    opt.classList.add('hidden');
                }
            });
        });

        // Prevent click in search box from closing dropdown
        searchInput.addEventListener('click', function (e) {
            e.stopPropagation();
        });

        // Select option
        optionsList.addEventListener('click', function (e) {
            const target = e.target.closest('.searchable-select-option');
            if (!target) return;

            e.stopPropagation();

            const value = target.getAttribute('data-value');
            const label = target.textContent;

            // Update selected class
            options.forEach(opt => opt.classList.remove('selected'));
            target.classList.add('selected');

            // Update inputs
            hiddenInput.value = value;
            input.value = label;
            
            // If empty-option is selected, show placeholder/empty
            if (target.classList.contains('empty-option')) {
                input.value = '';
            }

            container.classList.remove('open');
        });
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function () {
        dropdowns.forEach(container => {
            container.classList.remove('open');
        });
    });
});
</script>

</body></html>
