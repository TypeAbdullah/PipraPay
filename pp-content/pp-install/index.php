<?php
    if (!defined('PipraPay_INIT')) {
        http_response_code(403);
        exit('Direct access not allowed');
    }

    if(isset($_POST['import_schema_request'])){
        header('Content-Type: application/json');

        if($requriemntnoneedchecked == false){
            echo json_encode([
                'status'  => 'false',
                'title'   => 'Server Requirements Not Met',
                'message' => 'Your server does not meet the minimum requirements. Please enable the required PHP extensions and try again.'
            ]);
            exit;
        }

        if (!class_exists('MongoDB\Client')) {
            echo json_encode(['status' => 'false', 'title' => 'Driver Missing', 'message' => 'MongoDB PHP extension is not loaded on this server.']);
            exit;
        }

        $host = pp_env('DB_HOST') ?: pp_env('MONGODB_URI') ?: pp_env('MONGO_URL'); // MongoDB URI
        if ($host === null || $host === '') {
            echo json_encode([
                'status'  => 'false',
                'title'   => 'Database Not Configured',
                'message' => 'No MongoDB URI is configured. Set DB_HOST and DB_NAME in your .env file and reload this page.'
            ]);
            exit;
        }

        $dbname = pp_env('DB_NAME', 'piprapay');

        try {
            // Test MongoDB Connection
            $client = new MongoDB\Client($host);
            $db = $client->selectDatabase($dbname);
            // Ping the database to verify connection
            $db->command(['ping' => 1]);

            echo json_encode(['status' => 'true', 'title' => 'Connected successfully', 'message' => 'Database connection verified successfully.']);
        } catch (Throwable $e) {
            echo json_encode(['status' => 'false', 'title' => 'Database Error', 'message' => $e->getMessage()]);
        }
        exit;
    }

        
    if(isset($_POST['adminName'])){
        $adminName = $_POST['adminName'];
        $adminEmail = $_POST['adminEmail'];
        $adminUsername = $_POST['adminUsername'];
        $adminPassword = $_POST['adminPassword'];
        $confirmPassword = $_POST['confirmPassword'];

        if($requriemntnoneedchecked == false){
            echo json_encode([
                'status'  => 'false',
                'title'   => 'Server Requirements Not Met',
                'message' => 'Your server does not meet the minimum requirements. Please enable the required PHP extensions and try again.'
            ]);
            exit;
        }

        if($adminName == "" || $adminEmail == "" || $adminUsername == "" || $adminPassword == "" || $confirmPassword == ""){
            echo json_encode(['status' => "false", 'message' => 'Enter all info before process.']);
        }else{
            if($adminPassword == $confirmPassword){
                if (filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                    $new_temp_password = generateStrongPassword(8);

                    $hashedPass = password_hash($adminPassword, PASSWORD_BCRYPT);
                    $temp_password = password_hash($new_temp_password, PASSWORD_BCRYPT);

                    $a_id = generateItemID();
                    $brand_id = generateItemID();

                    $adminDoc = [
                        'a_id' => $a_id, 
                        'full_name' => $adminName, 
                        'username' => $adminUsername, 
                        'email' => $adminEmail, 
                        'password' => $hashedPass, 
                        'temp_password' => $temp_password, 
                        'created_date' => getCurrentDatetime('Y-m-d H:i:s'), 
                        'updated_date' => getCurrentDatetime('Y-m-d H:i:s')
                    ];
                    insertData($db_prefix.'admin', $adminDoc);

                    $permDoc = [
                        'brand_id' => $brand_id, 
                        'a_id' => $a_id, 
                        'permission' => json_encode(permissionSchema()), 
                        'created_date' => getCurrentDatetime('Y-m-d H:i:s'), 
                        'updated_date' => getCurrentDatetime('Y-m-d H:i:s')
                    ];
                    insertData($db_prefix.'permission', $permDoc);

                    $brandDoc = [
                        'brand_id' => $brand_id, 
                        'created_date' => getCurrentDatetime('Y-m-d H:i:s'), 
                        'updated_date' => getCurrentDatetime('Y-m-d H:i:s')
                    ];
                    insertData($db_prefix.'brands', $brandDoc);

                    $currDoc = [
                        'brand_id' => $brand_id, 
                        'code' => 'BDT', 
                        'symbol' => '৳', 
                        'created_date' => getCurrentDatetime('Y-m-d H:i:s'), 
                        'updated_date' => getCurrentDatetime('Y-m-d H:i:s')
                    ];
                    insertData($db_prefix.'currency', $currDoc);

                    echo json_encode(['status' => "true", 'message' => 'Install Completed.']);
                }else{
                    echo json_encode(['status' => "false", 'message' => 'Invalid email address.']);
                }
            }else{
                echo json_encode(['status' => "false", 'message' => 'Password and Confirm Password must be the same.']);
            }
        }

        exit();
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="author" content="QubePlug Bangladesh">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Installer - PipraPay</title>
    <link rel="shortcut icon" href="<?= $piprapay_favicon ?? '' ?>">

    <link rel="stylesheet" href="<?php echo $site_url ?>assets/css/tabler.min.css?v=1.5" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/css/tabler-vendors.min.css" />

    <style>
      @import url("<?php echo $site_url ?>assets/css/inter.css");
    </style>
    <style>
        :root{
            --tblr-font-monospace: Monaco, Consolas, Liberation Mono, Courier New, monospace;
            --tblr-font-sans-serif: Inter Var, Inter, -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif;
            --tblr-font-serif: Georgia, Times New Roman, times, serif;
            --tblr-font-comic: Comic Sans MS, Comic Sans, Chalkboard SE, Comic Neue, sans-serif, cursive;
        }
    </style>

    <style>
        .all-pages .card{
            display: none;
        }
        .all-pages .card.active{
            display: block;
        }
    </style>
</head>
<body>
    <div class="container p-2 p-sm-4">
        <div class="text-center mb-5">
            <div class="brand-logo mb-1">
                <a href="#" class="logo-link">
                    <div class="logo-wrap">
                        <img src="<?= $piprapay_logo_light ?? '' ?>" alt="" style=" height: 40px; ">
                    </div>
                </a>
            </div>
        </div>

        <div class="col-lg-5 mx-auto">
            <ul class="steps steps-primary steps-counter p-0 m-0 mb-5 border-0">
                <li class="step-item active" data-step="1">Requirements</li>
                <li class="step-item" data-step="2">Database</li>
                <li class="step-item" data-step="3">Admin Setup</li>
                <li class="step-item" data-step="4">Complete</li>
            </ul>
        </div>

        <div class="col-lg-5 mx-auto all-pages">
            <!-- Page 1: Requirements Check -->
            <div class="card active" id="page1">
                <div class="card-header d-grid">
                    <h3 class="card-title mb-1">System Requirements Check</h3>
                    <p class="card-subtitle">Please wait while we check your server requirements.</p>
                </div>

                <div class="card-body">
                    <div class="requirements-grid">
                        <div class="requirement-groups">
                            <div id="phpRequirements">
                                <?php
                                    $satisfied_btn = true;

                                    foreach ($requirements as $req) {

                                        if (!$req['check']) {
                                            $satisfied_btn = false;
                                        }

                                        // Set status classes and icons
                                        $statusClass = $req['check'] ? 'text-success' : 'text-danger';
                                        $statusIcon  = $req['check'] ? 'bi-check-circle-fill' : 'bi-x-circle-fill'; // using Bootstrap Icons
                                        $statusText  = $req['check'] ? 'Passed' : 'Failed';

                                    ?>
                                        <div class="d-flex justify-content-between align-items-center border rounded p-3 mb-2">
                                            <div>
                                                <strong><?= $req['name'] ?></strong>
                                                <div class="small text-muted">
                                                    Required: <?= $req['required'] ?> | Current: <?= $req['current'] ?>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="<?= $statusIcon ?> <?= $statusClass ?>" style="font-size: 1.25rem;"></i>
                                                <span class="<?= $statusClass ?> fw-bold"><?= $statusText ?></span>
                                            </div>
                                        </div>
                                <?php
                                    }
                                ?>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <button class="btn btn-light" disabled>Previous</button>

                                <?php
                                    if($satisfied_btn == false){
                                ?>
                                        <button class="btn btn-danger" onclick="location.reload()">
                                            <span class="btn-text">Check Again</span>
                                        </button>
                                <?php
                                    }else{
                                ?>
                                        <button class="btn btn-primary" id="btnCheckRequirements">
                                            <span class="btn-text">Continue</span>
                                        </button>
                                <?php
                                    }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Page 2: Database Schema Import -->
            <div class="card" id="page2">
                <div class="card-header d-grid">
                    <h3 class="card-title mb-1">Database Setup</h3>
                    <p class="card-subtitle">Your database credentials are read from the environment (.env).</p>
                </div>

                <div class="card-body">
                    <?php
                        $env_db_host   = pp_env('DB_HOST') ?: pp_env('MONGODB_URI') ?: pp_env('MONGO_URL');
                        $env_db_name   = pp_env('DB_NAME', '');
                        $env_db_prefix = pp_env('DB_PREFIX', 'pp_');
                        $env_db_ready  = !($env_db_host === null || $env_db_host === '');
                    ?>

                    <?php if($env_db_ready){ ?>
                        <div class="d-flex justify-content-between align-items-center border rounded p-3 mb-2">
                            <div><strong>Database Host</strong></div>
                            <span class="text-muted"><?= htmlspecialchars($env_db_host) ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center border rounded p-3 mb-2">
                            <div><strong>Database Name</strong></div>
                            <span class="text-muted"><?= htmlspecialchars($env_db_name) ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center border rounded p-3 mb-2">
                            <div><strong>Table Prefix</strong></div>
                            <span class="text-muted"><?= htmlspecialchars($env_db_prefix) ?></span>
                        </div>

                        <div class="alert alert-info mt-3 mb-3">
                            Click below to connect to this database and import the PipraPay schema. Existing tables with the same prefix will be reused.
                        </div>

                        <div class="col-12">
                            <button type="button" class="btn btn-outline-primary w-100 import-schema-btn" id="btnImportSchema">Check &amp; Import Schema</button>
                        </div>
                    <?php }else{ ?>
                        <div class="alert alert-danger mb-0">
                            <h4 class="mb-1">No database configured</h4>
                            <p class="mb-0">No MongoDB database is configured. Set <code>MONGODB_URI</code> (or <code>DB_HOST</code>) and <code>DB_NAME</code> in your <code>.env</code> file (or your hosting dashboard, e.g. Railway) and reload this page.</p>
                        </div>
                    <?php } ?>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <button class="btn btn-light" id="btnPrevToRequirements">Previous</button>
                                <button class="btn btn-primary" id="btnNextToAdmin" disabled>
                                    Continue
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Page 3: Admin Setup -->
            <div class="card" id="page3">
                <div class="card-header d-grid">
                    <h3 class="card-title mb-1">Administrator Account Setup</h3>
                    <p class="card-subtitle">Create your administrator account.</p>
                </div>

                <div class="card-body">
                    <form id="adminForm" action="">
                        <div class="row gy-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="adminName" class="form-label">Full Name</label>
                                    <div class="form-control-wrap">
                                        <input type="text" class="form-control" id="adminName" name="adminName"
                                                placeholder="Enter your full name" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="adminUsername" class="form-label">Username</label>
                                    <div class="form-control-wrap">
                                        <input type="text" class="form-control" id="adminUsername" name="adminUsername"
                                                placeholder="Enter username" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="adminEmail" class="form-label">Email Address</label>
                                    <div class="form-control-wrap">
                                        <input type="email" class="form-control" id="adminEmail" name="adminEmail"
                                                placeholder="Enter email address" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="adminPassword" class="form-label">Password</label>
                                    <div class="form-control-wrap">
                                        <input type="password" class="form-control" id="adminPassword" name="adminPassword"
                                                placeholder="Enter password" required>
                                        <div class="password-strength mt-1">
                                            <small class="text-muted">Password strength: <strong><span id="passwordStrength">None</span></strong></small>
                                            <div class="strength-meter mt-2">
                                                <div class="strength-fill" id="passwordStrengthMeter" style="height: 2px; border-radius: 5px;"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="confirmPassword" class="form-label">Confirm Password</label>
                                    <div class="form-control-wrap">
                                        <input type="password" class="form-control" id="confirmPassword" name="confirmPassword"
                                                placeholder="Confirm password" required>
                                        <div class="mt-1" id="passwordMatch"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="d-flex justify-content-between">
                                    <button class="btn btn-light d-none" id="btnPrevToDatabase">Previous</button>
                                    <div class="w-100"></div>
                                    <button class="btn btn-primary" id="btnCompleteInstall">
                                        Finish
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Page 4: Installation Complete -->
            <div class="card card-gutter-lg rounded-4 card-auth installer-page" id="page4">
                <div class="card-body text-center mt-2">
                    <div class="m-2">
                        <svg xmlns="http://www.w3.org/2000/svg" style="width: 50px; height: 50px;" viewBox="0 0 24 24" fill="none" stroke="#5f38f9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-circle-check"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M9 12l2 2l4 -4" /></svg>
                    </div>
                    <h3 class="nk-block-title mb-2">Installation Complete!</h3>
                    <p class="mb-4">PipraPay has been successfully installed and configured.</p>
                    
                    <div class="installation-log mb-4" id="installationLog">
                        <!-- Installation log will appear here -->
                    </div>

                    <div class="alert alert-warning mb-4">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0"><em class="icon ni ni-alert-fill"></em></div>
                            <div class="flex-grow-1 ms-2">
                                <h4 class="mb-1">Important Security Notice</h4>
                                <p class="mb-0">For security reasons, please delete or rename the <code>pp-install</code> directory after installation.</p>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <a href="login" class="btn btn-primary" id="btnGoToDashboard">
                            <em class="icon ni ni-dashboard me-1"></em>
                            Go to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script src="<?php echo $site_url ?>assets/js/tabler.min.js"></script>
    <script src="<?php echo $site_url ?>assets/js/jquery-3.6.4.min.js"></script>
    <script src="<?php echo $site_url ?>assets/js/custom-toast.js?v=1.2"></script>

    <script data-cfasync="false">
        let currentStep = 1;
        const totalSteps = 4;

        function showStep(step) {
            // Remove active from all pages inside .all-pages
            document.querySelectorAll('.all-pages').forEach(card => {
                card.querySelectorAll('.active').forEach(child => {
                    child.classList.remove('active');
                });
            });

            // Activate current page
            const page = document.getElementById('page' + step);
            if (page) {
                page.classList.add('active');
            }

            // Update step indicators
            document.querySelectorAll('.steps .step-item').forEach(item => {
                item.classList.remove('active', 'completed');

                const itemStep = parseInt(item.dataset.step);

                if (itemStep < step) {
                    item.classList.add('completed');
                } else if (itemStep === step) {
                    item.classList.add('active');
                }
            });

            currentStep = step;
        }

        document.getElementById('btnCheckRequirements')?.addEventListener('click', () => {
            showStep(2);
        });

        document.getElementById('btnPrevToRequirements')?.addEventListener('click', () => {
            showStep(1);
        });

        document.getElementById('btnNextToAdmin')?.addEventListener('click', () => {
            showStep(3);
        });

        document.getElementById('btnPrevToDatabase')?.addEventListener('click', () => {
            showStep(2);
        });

        $(document).ready(function() {
            $('#btnImportSchema').on('click', function () {

                let btn = $('.import-schema-btn');

                btn.html('<span class="spinner-border spinner-border-sm"></span>');

                $.ajax({
                    url: 'install',
                    type: 'POST',
                    data: { import_schema_request: true },
                    dataType: 'json',
                    success: function (response) {
                        btn.text('Check & Import Schema');

                        if (response.status === true || response.status === 'true') {
                            $('#btnNextToAdmin').prop('disabled', false);

                            createToast({
                                title: `${response.title}`,
                                description: `${response.message}`,
                                svg: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#5f38f9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-circle-check"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M9 12l2 2l4 -4" /></svg>`,
                                timeout: 6000,
                                top: 20
                            });
                        } else {
                            createToast({
                                title: `${response.title || 'Action Required'}`,
                                description: `${response.message}`,
                                svg: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#d63939" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-exclamation-circle"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 9v4" /><path d="M12 16v.01" /></svg>`,
                                timeout: 6000,
                                top: 20
                            });
                        }
                    },
                    error: function (xhr, status, error) {
                        btn.text('Check & Import Schema');

                        createToast({
                            title: 'Action Required',
                            description: 'Something went wrong.',
                            svg: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#d63939" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-exclamation-circle"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 9v4" /><path d="M12 16v.01" /></svg>`,
                            timeout: 6000,
                            top: 20
                        });
                    }
                });
            });

            const passwordInput = document.getElementById('adminPassword');
            const strengthText = document.getElementById('passwordStrength');
            const strengthMeter = document.getElementById('passwordStrengthMeter');

            passwordInput.addEventListener('input', function() {
                const value = passwordInput.value;
                let score = 0;

                if (value.length >= 8) score++; // length check
                if (/[A-Z]/.test(value)) score++; // uppercase
                if (/[a-z]/.test(value)) score++; // lowercase
                if (/[0-9]/.test(value)) score++; // number
                if (/[\W]/.test(value)) score++; // special character

                let strength = 'None';
                let color = 'red';
                let width = (score / 5) * 100 + '%';

                switch(score) {
                    case 1: strength = 'Very Weak'; color = 'red'; break;
                    case 2: strength = 'Weak'; color = 'orange'; break;
                    case 3: strength = 'Medium'; color = 'yellow'; break;
                    case 4: strength = 'Strong'; color = 'lightgreen'; break;
                    case 5: strength = 'Very Strong'; color = 'green'; break;
                    default: strength = 'None'; color = 'red';
                }

                strengthText.textContent = strength;
                strengthText.style.color = color;
                strengthMeter.style.width = width;
                strengthMeter.style.background = color;
            });

            $('#adminForm').submit(function(e) {
                e.preventDefault(); 

                var btn = document.querySelector("#btnCompleteInstall").innerHTML;

                document.querySelector("#btnCompleteInstall").innerHTML = '<div class="spinner-border spinner-border-sm" role="status"> <span class="visually-hidden">Loading...</span> </div>';

                var formData = $(this).serialize();

                $.ajax({
                    type: 'POST',
                    url: 'install', 
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        document.querySelector("#btnCompleteInstall").innerHTML = btn;

                        if (response.status == 'true') {
                            showStep(4);
                        } else {
                            createToast({
                                title: 'Action Required',
                                description: `${response.message}`,
                                svg: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#d63939" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-exclamation-circle"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 9v4" /><path d="M12 16v.01" /></svg>`,
                                timeout: 6000,
                                top: 20
                            });
                        }
                    },
                    error: function (xhr, status, error) {
                        createToast({
                            title: 'Action Required',
                            description: 'Something went wrong.',
                            svg: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#d63939" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-exclamation-circle"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 9v4" /><path d="M12 16v.01" /></svg>`,
                            timeout: 6000,
                            top: 20
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>