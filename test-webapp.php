<?php
// ---------------------------------------------------------------
// Kara David's Wish Note - single-file PHP web application
// ---------------------------------------------------------------

// Make mysqli report errors as exceptions so we can catch them cleanly.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// --- Database connection (local MariaDB/MySQL, no password) ---
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "birthday_wishnote";

$successMessage = "";
$errorMessage   = "";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    $conn = null;
    $errorMessage = "Database connection failed. Please make sure MySQL is running.";
}

// --- Handle the POST request ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && $conn !== null) {

    // Capture all four variables from the form.
    $FirstName   = trim($_POST["FirstName"]   ?? "");
    $LastName    = trim($_POST["LastName"]    ?? "");
    $GovPosition = trim($_POST["GovPosition"] ?? "");
    $Location    = trim($_POST["Location"]    ?? "");   // captured, but NEVER saved (see flaw below)

    if ($FirstName === "" || $LastName === "" || $GovPosition === "") {
        $errorMessage = "First Name, Last Name and Government Position are required.";
    } else {
        try {
            // ===========================================================
            // INTENTIONAL FLAW (for QA testing purposes):
            // The form collects four fields, including Location, but this
            // INSERT statement completely omits the Location column. The
            // bind_param() call also omits $Location. As a result, Location
            // is silently discarded: the user sees a success message, yet
            // the database row has NULL/empty Location. A tester should
            // catch this by comparing the form data with the table contents.
            // ===========================================================
            $sql  = "INSERT INTO main_corrupt_politicians (FirstName, LastName, GovPosition) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $FirstName, $LastName, $GovPosition);
            $stmt->execute();
            $stmt->close();

            $successMessage = "Success! The wish note has been sent.";
        } catch (mysqli_sql_exception $e) {
            // Fallback error message if the query fails to prepare/execute.
            $errorMessage = "Error: the wish note could not be saved. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kara David's Wish Note</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 20px;
            font-family: "Segoe UI", Arial, sans-serif;
            background: #f0f2f5;
            color: #2c3e50;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card {
            width: 100%;
            max-width: 460px;
            background: #ffffff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12);
        }
        h2 {
            margin: 0 0 22px;
            text-align: center;
            color: #2980b9;
        }
        label {
            display: block;
            margin: 14px 0 6px;
            font-weight: 600;
            font-size: 14px;
        }
        input[type="text"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #c5d0db;
            border-radius: 6px;
            font-size: 15px;
        }
        input[type="text"]:focus {
            outline: none;
            border-color: #2980b9;
            box-shadow: 0 0 0 3px rgba(41, 128, 185, 0.2);
        }
        .btn {
            border: none;
            border-radius: 6px;
            padding: 11px 18px;
            font-size: 15px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-primary { width: 100%; margin-top: 22px; background: #2980b9; color: #fff; }
        .btn-primary:hover { background: #1c5f8a; }
        .btn-secondary { background: #e1e8ee; color: #2c3e50; }
        .btn-secondary:hover { background: #cfd9e1; }
        .btn-confirm { background: #2980b9; color: #fff; }
        .btn-confirm:hover { background: #1c5f8a; }
        .alert {
            padding: 12px 14px;
            border-radius: 6px;
            margin-bottom: 18px;
            font-size: 14px;
        }
        .alert-success { background: #e3f4e8; color: #1e6b3a; border: 1px solid #b6e0c4; }
        .alert-error   { background: #fdeaea; color: #a12a2a; border: 1px solid #f0bcbc; }

        /* Modal */
        .overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
            padding: 20px;
            z-index: 100;
        }
        .overlay.show { display: flex; }
        .modal {
            width: 100%;
            max-width: 420px;
            background: #fff;
            border-radius: 10px;
            padding: 24px;
            box-shadow: 0 8px 28px rgba(0, 0, 0, 0.25);
        }
        .modal h3 { margin: 0 0 14px; color: #2980b9; }
        .modal dl { margin: 0 0 18px; }
        .modal dt { font-size: 12px; color: #6b7c8c; margin-top: 10px; text-transform: uppercase; }
        .modal dd { margin: 2px 0 0; font-size: 16px; word-wrap: break-word; }
        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Kara David's Wish Note</h2>

        <?php if ($successMessage !== ""): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
        <?php endif; ?>
        <?php if ($errorMessage !== ""): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($errorMessage); ?></div>
        <?php endif; ?>

        <form id="wishForm" method="POST" action="">
            <label for="FirstName">Target's First Name</label>
            <input type="text" id="FirstName" name="FirstName" maxlength="30" required>

            <label for="LastName">Target's Last Name</label>
            <input type="text" id="LastName" name="LastName" maxlength="30" required>

            <label for="GovPosition">Target's Government Position</label>
            <input type="text" id="GovPosition" name="GovPosition" maxlength="20" required>

            <label for="Location">Target's Location</label>
            <input type="text" id="Location" name="Location" maxlength="40">

            <button type="submit" class="btn btn-primary">Send Wish Note</button>
        </form>
    </div>

    <!-- Verification modal -->
    <div class="overlay" id="modalOverlay">
        <div class="modal">
            <h3>Verify Target Data</h3>
            <dl>
                <dt>First Name</dt><dd id="mFirstName"></dd>
                <dt>Last Name</dt><dd id="mLastName"></dd>
                <dt>Government Position</dt><dd id="mGovPosition"></dd>
                <dt>Location</dt><dd id="mLocation"></dd>
            </dl>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" id="cancelBtn">Cancel</button>
                <button type="button" class="btn btn-confirm" id="confirmBtn">Confirm &amp; Send</button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var form    = document.getElementById("wishForm");
            var overlay = document.getElementById("modalOverlay");

            // Intercept the submission and show the modal instead.
            form.addEventListener("submit", function (e) {
                e.preventDefault();
                document.getElementById("mFirstName").textContent   = document.getElementById("FirstName").value;
                document.getElementById("mLastName").textContent    = document.getElementById("LastName").value;
                document.getElementById("mGovPosition").textContent = document.getElementById("GovPosition").value;
                document.getElementById("mLocation").textContent    = document.getElementById("Location").value || "(not provided)";
                overlay.classList.add("show");
            });

            // Cancel closes the modal.
            document.getElementById("cancelBtn").addEventListener("click", function () {
                overlay.classList.remove("show");
            });

            // Confirm & Send: form.submit() does not trigger the submit event,
            // so it bypasses the interception above and sends the POST data.
            document.getElementById("confirmBtn").addEventListener("click", function () {
                form.submit();
            });
        })();
    </script>
</body>
</html>
