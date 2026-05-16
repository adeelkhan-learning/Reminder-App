<?php
session_start();
require 'db.php';

// --- ADMIN ONLY: Create New Family Member ---
if (isset($_POST['create_user']) && $_SESSION['role'] == 'admin') {
    $new_user = $_POST['new_username'];
    $new_pass = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    $new_name = $_POST['new_fullname'];
    $new_email = $_POST['new_email']; // Add this

    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, email, role) VALUES (?, ?, ?, ?, 'user')");
    $stmt->execute([$new_user, $new_pass, $new_name, $new_email]);
    try {
        $user_msg = "<div class='alert alert-success'>User created successfully!</div>";
    } catch (Exception $e) {
        $user_msg = "<div class='alert alert-danger'>Username already exists.</div>";
    }
}

// 1. LOGOUT LOGIC
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_destroy();
    header("Location: index.php");
    exit;
}

// 2. LOGIN LOGIC
$error = ""; // Keep this!
if (isset($_POST['login'])) {
    $user_input = $_POST['user'];
    $pass_input = $_POST['pass'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$user_input]);
    $user = $stmt->fetch();

    // SECURE CHECK: Uses mathematical verification instead of plain text
    if ($user && password_verify($pass_input, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role']; // Add this line
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}

// 3. SHOW LOGIN PAGE IF NOT AUTHENTICATED
// 1. Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. THE INSERT LOGIC (Place it right here)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nickname'])) {
    
    // Collect data from the form
    $nickname = $_POST['nickname'];
    $relation = $_POST['relation'];
    $doc_type = $_POST['doc_type'];
    $country  = $_POST['country'];
    $expiry   = $_POST['expiry_date'];
    $r1_days  = $_POST['reminder_1_days'];
    $r2_days  = $_POST['reminder_2_days'];
    $user_id  = $_SESSION['user_id'];

    $doc_type = $_POST['doc_type'];

    // If 'Other Insurance' is picked, swap the value out with their specific custom name
    if ($doc_type === 'Other Insurance' && !empty($_POST['other_insurance_name'])) {
        $doc_type = trim($_POST['other_insurance_name']);
    }
    
    // SQL Query to include the new reminder columns
    $sql = "INSERT INTO expiry_reminders (user_id, nickname, relation, doc_type, country, expiry_date, reminder_1_days, reminder_2_days) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    try {
        $stmt = $pdo->prepare($sql);
        // Ensure there are 8 values in this array to match the 8 question marks
        $stmt->execute([$user_id, $nickname, $relation, $doc_type, $country, $expiry, $r1_days, $r2_days]);
        
        // Redirect back to the same page to prevent double-posting on refresh
        header("Location: index.php?status=success");
        exit();
    } catch (PDOException $e) {
        // If there is an error, it will show you exactly what is wrong (e.g., missing columns)
        die("Database Error: " . $e->getMessage());
    }
}

// DELETE LOGIC
if (isset($_POST['bulk_delete']) && !empty($_POST['ids'])) {
    $placeholders = implode(',', array_fill(0, count($_POST['ids']), '?'));
    $stmt = $pdo->prepare("DELETE FROM expiry_reminders WHERE id IN ($placeholders) AND user_id = ?");
    $params = array_merge($_POST['ids'], [$_SESSION['user_id']]);
    $stmt->execute($params);
    header("Location: index.php");
    exit;
}

// 3. THE FETCH LOGIC (To show the list below the form)
$stmt = $pdo->prepare("SELECT * FROM expiry_reminders WHERE user_id = ? ORDER BY expiry_date ASC");
$stmt->execute([$_SESSION['user_id']]);
$reminders = $stmt->fetchAll();
?>
<?php


// 4. LOGGED IN - HANDLE DATA ACTIONS
$current_user_id = $_SESSION['user_id'];

// Delete Entry (Security Check: Only delete if it belongs to current user)
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM expiry_reminders WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['delete'], $current_user_id]);
    header("Location: index.php");
}

if ($_SESSION['role'] == 'admin') {
    // Admin sees everything
    $stmt = $pdo->query("SELECT * FROM expiry_reminders ORDER BY expiry_date ASC");
} else {
    // Users see only theirs
    $stmt = $pdo->prepare("SELECT * FROM expiry_reminders WHERE user_id = ? ORDER BY expiry_date ASC");
    $stmt->execute([$current_user_id]);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="apple-touch-icon" href="apple-touch-icon.png?v=2">
    <link rel="icon" type="image/png" sizes="192x192" href="apple-touch-icon.png?v=2">
    <link rel="manifest" href="manifest.json">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reminders</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark mb-4">
        <div class="container">
            <span class="navbar-brand">Hello, <?= htmlspecialchars($_SESSION['username']) ?></span>
            <a href="?action=logout" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="alert alert-warning small py-2">
            <strong>🛡️ Security:</strong> Use nicknames only. No full names or ID numbers.
        </div>
        
        <?php if ($_SESSION['role'] == 'admin'): ?>
        <div class="card mb-4 border-primary shadow-sm">
            <div class="card-header bg-primary text-white">👨‍👩‍👧‍👦 Admin: Add Family Member</div>
            <div class="card-body">
                <?php if(isset($user_msg)) echo $user_msg; ?>
                <form method="POST" class="row g-2">
                    <div class="col-md-3"><input type="text" name="new_username" class="form-control" placeholder="Login Username" required></div>
                    <div class="col-md-3"><input type="password" name="new_password" class="form-control" placeholder="Login Password" required></div>
                    <div class="col-md-4"><input type="text" name="new_fullname" class="form-control" placeholder="Full Name" required></div>
                    <div class="col-md-3"><input type="email" name="new_email" class="form-control" placeholder="Email Address" required></div>
                    <div class="col-md-2"><button type="submit" name="create_user" class="btn btn-outline-primary w-100">Create</button></div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Add Form -->
        <div class="card mb-4 shadow-sm border-0">
            <div class="card-body bg-dark rounded"> <form method="POST" class="row g-2">
                    <div class="col-6">
                        <input type="text" name="nickname" class="form-control" placeholder="Who (e.g. Ali)" required>
                    </div>
                    
                    <div class="col-6">
                        <select name="relation" class="form-select" required>
                            <option value="" disabled selected>Relation</option>
                            <option>Self</option><option>Wife</option><option>Son</option><option>Daughter</option>
                            <option>Mother</option><option>Father</option><option>Brother</option><option>Sister</option>
                            <option>Mother in Law</option><option>Father in Law</option><option>Brother in Law</option><option>Pet</option>
                        </select>
                    </div>
                    
                    <div class="col-6">
                        <select id="doc_type" name="doc_type" class="form-select" onchange="toggleOtherInsurance(this.value)">
                            <option value="Passport">Passport</option>
                            <option value="Visa">Visa</option>
                            <option value="Identity Card">Identity Card</option>
                            <option value="Vehicle Registration">Vehicle Registration</option>
                            <option value="Driving License">Driving License</option>
                            <option value="Motor Insurance">Motor Insurance</option>
                            <option value="Health Insurance">Health Insurance</option>
                            <option value="House Insurance">House Insurance</option>
                            <option value="Other Insurance">Other Insurance</option>
                        </select>
                        
                        <div id="other_insurance_wrapper" class="mt-2" style="display: none;">
                            <label for="other_insurance_name" class="form-label">Specify Insurance Type:</label>
                            <input type="text" id="other_insurance_name" name="other_insurance_name" class="form-control" placeholder="e.g., Travel, Life, Pet">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="countryInput" class="form-label">Country</label>
                        <input class="form-control" list="datalistOptions" id="countryInput" name="country" placeholder="Type to search country..." required>
                        
                        <datalist id="datalistOptions"></datalist>
                    </div>
                                                
                    <div class="col-12">
                        <label class="form-label text-white-50 small">Expiry Date</label>
                        <input type="date" name="expiry_date" class="form-control" required>
                    </div>
        
                    <div class="col-12 mt-3">
                        <h6 class="text-white border-bottom pb-2">Reminder Settings (Days Before Expiry)</h6>
                    </div>
                    
                    <div class="col-6">
                        <label class="form-label text-white-50 small">First Alert</label>
                        <input type="number" name="reminder_1_days" id="reminder_1" class="form-control" value="180" required>
                    </div>
                    
                    <div class="col-6">
                        <label class="form-label text-white-50 small">Second Alert</label>
                        <input type="number" name="reminder_2_days" id="reminder_2" class="form-control" value="30" required>
                    </div>
        
                    <div class="col-12 mt-3">
                        <button type="submit" name="nickname_submit" class="btn btn-primary w-100">Add Reminder</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Export Button -->
        <div class="text-end mb-2">
            <a href="export.php" class="btn btn-sm btn-success shadow-sm">📥 Export to Excel</a>
        </div>
        
        <!-- Search Bar -->
        
        <div class="mb-3 card p-3 shadow-sm">
            <div class="row g-2 align-items-center">
                <div class="col-md-8">
                    <input type="text" id="tableSearch" class="form-control" placeholder="🔍 Search by name, document type, country, or expiry date...">
                </div>
                <div class="col-md-4">
                    <button class="btn btn-secondary w-100" onclick="clearSearch()">Clear Filters</button>
                </div>
            </div>
        </div>

        <!-- Table -->
        <form method="POST" action="index.php">
            <div class="mb-2">
                <button type="submit" name="bulk_delete" class="btn btn-sm btn-danger" onclick="return confirm('Delete selected?')">Delete Selected</button>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="select_all"></th>
                        <th>Who</th>
                        <th>What</th>
                        <th>Expiry</th>
                        <th></th> </tr>
                </thead>
                <tbody>
                    <?php foreach ($reminders as $row): 
                        $expiry_ts = strtotime($row['expiry_date']);
                        $days_left = floor(($expiry_ts - time()) / 86400);
                        $is_urgent = ($days_left <= 30);
                        $text_class = $is_urgent ? 'text-danger fw-bold' : '';
                    ?>
                    <tr>
                        <td><input type="checkbox" name="ids[]" value="<?= $row['id'] ?>" class="delete-checkbox"></td>
                        
                        <td>
                            <strong><?= htmlspecialchars($row['nickname']) ?></strong><br>
                            <small class="text-muted"><?= htmlspecialchars($row['relation']) ?></small>
                        </td>
                        
                        <td>
                            <?= htmlspecialchars($row['doc_type']) ?><br>
                            <span class="badge bg-secondary" style="font-size: 0.75rem;">
                                <?= htmlspecialchars($row['country']) ?>
                            </span>
                        </td>
                        
                        <td class="<?= $text_class ?>">
                            <?= date('d M Y', $expiry_ts) ?><br>
                            <small class="<?= $is_urgent ? 'text-danger' : 'text-muted' ?>">
                                <?= $days_left ?> days left
                            </small>
                        </td>
                        
                        <td>
                            <a href="?delete=<?= $row['id'] ?>" class="text-danger" onclick="return confirm('Delete?')">🗑️</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>
        
        <script>
        document.getElementById('select_all').addEventListener('click', function() {
            document.querySelectorAll('.delete-checkbox').forEach(cb => cb.checked = this.checked);
        });
        </script>
    </div>

    <script>
    // DOCUMENT TYPE DYNAMIC LOGIC TUNER
    document.getElementById('doc_type').addEventListener('change', function() {
        const docType = this.value;
        const r1 = document.getElementById('reminder_1');
        const r2 = document.getElementById('reminder_2');
        
        // 1. First, handle our Business Logic for notification countdown buffers
        if (docType === 'Passport') {
            r1.value = 180; // 6 months for travel validity
            r2.value = 60;  // 2 months final warning
        } 
        else if (docType === 'National ID' || docType === 'Driving License') {
            r1.value = 30;  // 2 months
            r2.value = 15;  // 15 days
        } 
        else if (docType === 'Vehicle Registration') {
            r1.value = 30;  // 1 month
            r2.value = 7;   // 1 week
        } 
        else if (docType === 'Visa') {
            r1.value = 30;  // 1 month
            r2.value = 10;  // 10 days
        }
        else if (docType.includes('Insurance')) {
            // Automatically applies a safe standard 30/7 day buffer for all insurance categories
            r1.value = 30;  // 1 month
            r2.value = 7;   // 1 week
        }
    
        // 2. Second, toggle our custom sub-input text field wrapper if "Other Insurance" is selected
        const wrapper = document.getElementById('other_insurance_wrapper');
        const input = document.getElementById('other_insurance_name');
        
        if (docType === 'Other Insurance') {
            wrapper.style.display = 'block';
            input.setAttribute('required', 'required');
        } else {
            wrapper.style.display = 'none';
            input.removeAttribute('required');
            input.value = '';
        }
    });
        
    </script>

    <script>
    // FETCH ALL COUNTRIES FROM API AND POPULATE DATALIST CORRECTLY
    fetch('https://restcountries.com/v3.1/all?fields=name')
        .then(res => res.json())
        .then(data => {
            const datalist = document.getElementById('datalistOptions');
            
            // Clear out any old initialization leftovers
            datalist.innerHTML = ''; 
            
            // Map and sort the array alphabetically
            const countries = data.map(c => c.name.common).sort();
            
            countries.forEach(country => {
                let opt = document.createElement('option');
                // Datalists require the name inside the 'value' attribute
                opt.setAttribute('value', country); 
                datalist.appendChild(opt);
            });
        }) // Closes the second .then() block cleanly
        .catch(err => {
            console.error("Error fetching countries API:", err);
            
            // EMERGENCY FALLBACK: If the API fails, load core countries manually
            const datalist = document.getElementById('datalistOptions');
            const fallback = ["United Kingdom", "United Arab Emirates", "Pakistan", "Saudi Arabia", "United States"];
            fallback.forEach(c => {
                let opt = document.createElement('option');
                opt.setAttribute('value', c);
                datalist.appendChild(opt);
            });
        }); // Closes the catch block and terminates the entire fetch chain perfectly

    // CONDITIONAL INSURANCES INPUT TOGGLE
    function toggleOtherInsurance(val) {
        const wrapper = document.getElementById('other_insurance_wrapper');
        const input = document.getElementById('other_insurance_name');
        
        if (val === 'Other Insurance') {
            wrapper.style.display = 'block';
            input.setAttribute('required', 'required');
        } else {
            wrapper.style.display = 'none';
            input.removeAttribute('required');
            input.value = '';
        }
    }

    // REAL-TIME LIVE FILTERING ENGINE FOR THE REMINDER TABLE
    document.getElementById('tableSearch').addEventListener('keyup', function() {
        const value = this.value.toLowerCase();
        const rows = document.querySelectorAll('table tbody tr'); 

        rows.forEach(row => {
            const rowText = row.textContent.toLowerCase();
            if (rowText.indexOf(value) > -1) {
                row.style.display = ''; 
            } else {
                row.style.display = 'none'; 
            }
        });
    });

    // Clear function tool
    function clearSearch() {
        const searchInput = document.getElementById('tableSearch');
        searchInput.value = '';
        searchInput.dispatchEvent(new Event('keyup'));
    }
    </script>
</body>
</html>