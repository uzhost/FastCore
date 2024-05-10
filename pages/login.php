<?php 
if(!defined('FastCore')) {
    exit('Oops!');
}

# Headers
$opt = array(
    'title' => 'Login',
    'keywords' => 'login to the project',
    'description' => 'login, sign in to account, log in'
);

if(isset($_SESSION['uid'])){
    Header('Location: /user/dashboard');
    return;
}
?>
<h4>Login Form</h4>
<?php
# Login form processing
if (isset($_POST['auth'])){

    $login = $func->FLogin($_POST['email']);
    $email = $func->FMail($_POST['email']);
    $pass = $_POST['pass']; // Password in plain text

    # Determine IP address
    $real_ip = $func->get_ip();
    $ip = $func->ip_int($real_ip);

    # If fields are empty
    if(empty($_POST['email']) || empty($_POST['pass'])) {
        $errors[] = 'Not all fields are filled!';
    }

    # Filter data
    if (empty(filter_var($email, FILTER_VALIDATE_EMAIL) !== false) && empty($login)) {
        $errors[] = 'Email or Login is filled incorrectly';
    }

    # Find email / login
    $users = $db->query('SELECT * FROM db_users WHERE email = ? OR login = ? LIMIT 1', array($email, $login))->fetchArray();

    # Check email / login
    if (!isset($users['email']) && $email) {
        $errors[] = 'Email not found!';
    }
    if (!isset($users['login']) && $login) {
        $errors[] = 'Login not found!';
    }

    if (isset($users['pass'])) {
        # Check password
        if (!password_verify($pass, $users['pass'])) {
            $errors[] = 'Password does not match!';
        }
        # If banned
        if ($users['ban'] == 1) {
            $errors[] = 'Your account has been blocked!';
        }
    }

    # Successful login
    if (empty($errors)) {
        $time = time();
        $userID = $users['id'];
        $db->query('UPDATE db_users SET ip = ?, auth = ? WHERE id = ?', array($ip, $time, $userID));
        $_SESSION['uid'] = $users['id'];
        $_SESSION['login'] = $users['login'];
        header('Location: /user/dashboard');
        return;
    } else {
        # Display errors
        echo '<div class="alert alert-danger"><i class="fa fa-warning"></i> ' . array_shift($errors) . '</div>';
    }
}
?>

<form action="" method="POST">
    <div class="form-group mb-1"><input class="form-control" name="email" placeholder="Email or Login"></div>
    <div class="form-group mb-1"><input type="password" class="form-control" name="pass" placeholder="Password"></div>

    <button name="auth" type="submit" class="btn btn-success">LOGIN</button>
    <a class="btn btn-white text-primary" href="/restore">Forgot Password?</a>
</form>
