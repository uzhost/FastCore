error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!defined('FastCore')) {
    exit('Attempted hacking detected!');
}

/**
 * Headers
 */
$opt = array(
    'title' => 'Registration',
    'keywords' => 'registration in the project',
    'description' => 'registration, create an account, sign in'
);

if (isset($_SESSION['uid'])) {
    header('Location: /user/dashboard');
    exit();
}
?>

<h4>Registration Form</h4>

<?php
# Registration form processing
if (isset($_POST['reg'])) {
    // Filtering inputs
    $login = $func->FLogin($_POST["login"]);
    $email = $func->FMail($_POST["email"]);
    $pass = $func->FPass($_POST["pass"]);

    // Hashing password
    $pass = password_hash($pass, PASSWORD_DEFAULT);

    $time = time();
    $site = $func->getDomain(); // Referral source

    // Referrer
    $rid = (isset($_COOKIE["i"])) ? (int)$_COOKIE["i"] : 1;
    $referer = $rid == 0 ? 0 : $db->query('SELECT login FROM db_users WHERE id = ? LIMIT 1', array($rid))->fetchArray();
    if ($referer === null) {
        $rid = 1;
        $referer = "Admin";
    }

    // IP address
    $real_ip = $func->get_ip();
    $ip = $func->ip_int($real_ip);

    // Validation
    $errors = array();
    if (empty($login)) {
        $errors[] = 'Login is required!';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email!';
    }
    if (empty($pass)) {
        $errors[] = 'Password is required!';
    }

    // Check uniqueness
    $users = $db->query('SELECT * FROM db_users WHERE ip = ? OR login = ? OR email = ?', array($ip, $login, $email))->fetchArray();
    if ($users && isset($users['ip']) && $users['ip'] === $ip) {
        $errors[] = 'Registration from this IP (' . $real_ip . ') has already been made!';
    }
    if ($users && isset($users['login']) && $users['login'] === $login) {
        $errors[] = 'Login already exists!';
    }
    if ($users && isset($users['email']) && $users['email'] === $email) {
        $errors[] = 'Email is already registered!';
    }

    // Successful registration
    if (empty($errors)) {
        // Insert user into database
        $db->query('INSERT INTO db_users (login, email, pass, reg, ip, rid, referer, refsite) VALUES (?,?,?,?,?,?,?,?)', array($login, $email, $pass, $time, $ip, $rid, $referer, $site));
        $lid = $db->LastInsert();

        // Create wallet table
        $db->query('INSERT INTO db_purse (id,uid,time) VALUES (?,?,?)', array($lid, $lid, $time));

        // Increment referrer count
        $db->query('UPDATE `db_users` SET `refs` = `refs` + 1 WHERE `id` = ?', array($rid));

        // Update statistics
        $db->query("UPDATE `db_stats` SET `users` = `users` + 1 WHERE `id` = '1'");

        echo '<div class="alert alert-success"><b>Registration successful!</b><br/>You will be redirected to the login page shortly.</div>';
        header('Location: /login');
        exit();
    }

    // Display errors
    echo '<div class="alert alert-danger"><i class="fa fa-warning"></i> ' . implode('<br>', $errors) . '</div>';
}
?>

<form action="" method="POST">
    <div class="form-group mb-1"><input class="form-control" name="login" type="text" placeholder="Login" value=""></div>
    <div class="form-group mb-1"><input class="form-control" name="email" type="email" placeholder="Email" value=""></div>
    <div class="form-group mb-1"><input class="form-control" name="pass" type="password" placeholder="Password" value=""></div>
    <button name="reg" type="submit" class="btn btn-success">REGISTER</button>
</form>
