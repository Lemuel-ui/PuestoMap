<?php
/**
 * login/index.php
 * PuestoMap Login Page
 * Database authentication for all system users.
 */

require_once __DIR__ . '/../includes/functions.php';

$error = '';

// Display messages from redirects
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'session_expired') {
        $error = 'Please log in to continue.';
    } elseif ($_GET['msg'] === 'unauthorized') {
        $error = 'You are not authorized to view that page.';
    }
}

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter both email and password.';
    } else {
        try {
            $pdo = get_db();
            $user = get_user_by_email($pdo, $email);

            if (
                !$user ||
                !isset($user['password_hash']) ||
                !password_verify($password, $user['password_hash'])
            ) {
                $error = 'Invalid email or password.';
            } elseif (empty($user['is_active'])) {
                $error = 'This account has been deactivated. Contact the MISU Administrator.';
            } elseif (
                empty($user['role']) ||
                !isset(ROLE_HOME[$user['role']])
            ) {
                $error = 'Your account role is not configured correctly. Contact the administrator.';
            } else {
                // Prevent session fixation
                session_regenerate_id(true);

                $_SESSION['user_id'] = (int) $user['user_id'];
                $_SESSION['name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                header('Location: ' . ROLE_HOME[$user['role']]);
                exit;
            }
        } catch (Throwable $e) {
            error_log(APP_NAME . ' login error: ' . $e->getMessage());
            $error = 'Unable to log in right now. Please check the database connection and try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Log In · <?php echo APP_NAME; ?></title>

    <link rel="stylesheet" href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/assets/css/auth.css">

    <style>
        .password-wrapper {
            position: relative;
            width: 100%;
        }

        .password-wrapper .form-control {
            width: 100%;
            padding-right: 45px;
            box-sizing: border-box;
        }

        .password-toggle {
            position: absolute;
            top: 50%;
            right: 12px;
            transform: translateY(-50%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4px;
            border: none;
            background: transparent;
            color: #777;
            cursor: pointer;
        }

        .password-toggle:hover {
            color: #E8871E;
        }

        .password-toggle:focus-visible {
            outline: 2px solid #E8871E;
            outline-offset: 2px;
            border-radius: 4px;
        }
    </style>
</head>

<body>

<div class="auth-shell">

    <!-- Left side: PuestoMap introduction -->
    <div class="auth-visual">

        <div class="brand">
            <svg width="34" height="34" viewBox="0 0 24 24"
                 fill="none" stroke="#E8871E" stroke-width="1.8"
                 aria-hidden="true">
                <path d="M12 22s7-7.58 7-12A7 7 0 0 0 5 10c0 4.42 7 12 7 12z"
                      fill="rgba(232,135,30,0.2)"/>
                <circle cx="12" cy="10" r="2.6"
                        fill="#E8871E" stroke="none"/>
            </svg>

            <div>
                <div class="brand-text"><?php echo APP_NAME; ?></div>
                <div class="brand-sub">Municipality of Manolo Fortich</div>
            </div>
        </div>

        <div class="pitch">
            <h1>Know where every vendor is. Know every permit's status.</h1>

            <p>
                <?php echo APP_NAME; ?> brings interactive vendor mapping and business
                permit monitoring into one platform for the Business
                Permits and Licensing Office (BPLO), built with Leaflet.js,
                PHP, and MySQL/MariaDB.
            </p>
        </div>

        <div>
            <div class="pin-legend">
                <span><i style="background:#2E8B57"></i> Active Permit</span>
                <span><i style="background:#E8871E"></i> Expiring Soon</span>
                <span><i style="background:#C1443C"></i> Expired</span>
            </div>

            <p class="footnote" style="margin-top:1.25rem;">
                Capstone Project &middot; Institute for Computer Studies
                &middot; Northern Bukidnon State College
            </p>
        </div>

    </div>

    <!-- Right side: Login form -->
    <div class="auth-form-col">
        <div class="auth-card">

            <div class="kicker">BPLO Portal Access</div>
            <h2>Welcome back</h2>

            <p class="sub">
                Sign in with your <?php echo APP_NAME; ?> account to continue.
            </p>

            <?php if ($error !== ''): ?>
                <div class="auth-alert" role="alert">
                    <?php
                    echo htmlspecialchars(
                        $error,
                        ENT_QUOTES,
                        'UTF-8'
                    );
                    ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <?php echo csrf_field(); ?>

                <div class="form-group">
                    <label class="form-label" for="email">
                        Email address
                    </label>

                    <input
                        class="form-control"
                        type="email"
                        id="email"
                        name="email"
                        placeholder="you@manolofortich.gov.ph"
                        value="<?php
                            echo htmlspecialchars(
                                $_POST['email'] ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            );
                        ?>"
                        autocomplete="username"
                        required
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">
                        Password
                    </label>

                    <div class="password-wrapper">
                        <input
                            class="form-control"
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            id="togglePassword"
                            aria-label="Show password"
                            aria-pressed="false"
                        >
                            <svg
                                id="eyeIcon"
                                xmlns="http://www.w3.org/2000/svg"
                                width="20"
                                height="20"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <button
                    type="submit"
                    class="btn btn-primary"
                    style="width:100%;justify-content:center;"
                >
                    Log In
                </button>

            </form>



        </div>
    </div>

</div>

<script>
    const passwordInput = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');
    const eyeIcon = document.getElementById('eyeIcon');

    togglePassword.addEventListener('click', function () {
        const showPassword = passwordInput.type === 'password';

        passwordInput.type = showPassword ? 'text' : 'password';

        togglePassword.setAttribute(
            'aria-label',
            showPassword ? 'Hide password' : 'Show password'
        );

        togglePassword.setAttribute(
            'aria-pressed',
            String(showPassword)
        );

        if (showPassword) {
            eyeIcon.innerHTML = `
                <path d="M3 3l18 18"/>
                <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/>
                <path d="M9.9 5.2A10.7 10.7 0 0 1 12 5
                         c6.4 0 10 7 10 7
                         a15.8 15.8 0 0 1-4 4.8"/>
                <path d="M6.6 6.6C3.6 8.5 2 12 2 12
                         s3.6 7 10 7
                         c1.1 0 2.1-.2 3-.5"/>
            `;
        } else {
            eyeIcon.innerHTML = `
                <path d="M2 12s3.6-7 10-7 10 7 10 7
                         -3.6 7-10 7S2 12 2 12z"/>
                <circle cx="12" cy="12" r="3"/>
            `;
        }
    });
</script>

</body>
</html>
