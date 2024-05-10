<?php 
if (!defined('FastCore')) { 
    exit('Oops!'); 
}

# Headers
$opt = array(
    'title' => 'Password Recovery',
    'keywords' => 'Password Recovery in the project',
    'description' => 'Password Recovery, remember password, reset password'
);

if (isset($_SESSION['uid'])) { 
    Header('Location: /user/dashboard'); 
    return; 
}
?>
<h4>Enter the Email you want to recover</h4>

<?php

# Registration Form
if (isset($_POST['restore']) ){

    # Filtering
    $email = $func->FMail($_POST['email']);
    $time = time();
    $tdel = $time + 60*15;

    $db->query("DELETE FROM db_restore WHERE date_del < ?",$time);

    # Determine IP Address
    $real_ip = $func->get_ip();
    $ip = $func->ip_int($real_ip);

    # Email error
    if (!empty(filter_var($email, FILTER_VALIDATE_EMAIL) !== false)) {
        
        # Check if user exists
        $uml = $db->query("SELECT * FROM db_users WHERE email = ?",$email)->numRows();
        if ($uml == 1) {

            # Check if password was recovered within 15 minutes
            $restore = $db->query("SELECT * FROM db_restore WHERE ip = ? OR email = ?",$ip,$email)->numRows();
            if ($restore == 0) {

                $new_pass = rand(11111111,99999999);
                # Hash the new password
                $hashed_new_pass = password_hash($new_pass, PASSWORD_DEFAULT);

                # Insert record into the database
                $db->query("INSERT INTO db_restore (email, ip, date_add, date_del) VALUES (?,?,?,?)",$email,$ip,$time,$tdel);
                $db->query('UPDATE db_users SET pass = ? WHERE email = ?',array($hashed_new_pass,$email));

                $mail = new send_mail;
                $mail->send(''.$email.'', 'Password Recovery', 'Your new password - '.$new_pass.'');
                echo '<div class="alert alert-success">A message has been sent to your E-Mail address.</div>';

            } else { 
                $errors[] = 'Password recovery from this IP ('.$real_ip.') has already been done in the last 15 minutes!'; 
            }
        } else { 
            $errors[] = 'User with such email not found!'; 
        }
    } else { 
        $errors[] = 'Email field is empty or invalid!'; 
    }

    # Display errors
    if (!empty($errors)) {
        echo '<div class="alert alert-danger"><i class="fa fa-warning"></i> '.array_shift($errors).'</div>';
    }
}
?>
<form action="" method="POST">
    <div class="form-group mb-1"><input class="form-control" name="email" type="email" placeholder="Email" value=""></div>

    <button name="restore" type="submit" class="btn btn-success">RECOVER</button>
</form>
