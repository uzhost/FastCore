<?php if(!defined('FastCore')){exit('Oops!');}

# Title
$opt['title'] = 'Settings';
?>

<div class="row">
<div class="col-lg-6">
<div class="card">
<div class="card-header">Change Password</div>
<div class="p-3">
<?
if(isset($_POST['new_pass'])){
$pass = $func->FPass($_POST['pass']);
$pass_new = $func->FPass($_POST['pass_new']);
if($pass !== false AND strtolower($pass) == strtolower($user['pass'])){
	if($pass_new !== false){
		$db->query("UPDATE db_users SET pass = '$pass_new' WHERE id = '$uid'");        
		echo '<div class="alert alert-success">Password changed successfully!</div>';
	}else echo '<div class="alert alert-warning">New password has an invalid format!</div>';
}else echo '<div class="alert alert-danger">Old password is incorrect!</div>';
}
?>

<form action="" method="POST">
<input type="password" class="form-control" name="pass" placeholder="Old Password">
<input type="password" class="form-control mt-2" name="pass_new" placeholder="New Password">
<center>
<button class="btn btn-success mt-2" name="new_pass" type="submit">CHANGE PASSWORD</button>
</center>
</form>
</div>
</div>
</div>

<div class="col-lg-6">
<div class="card">
<div class="card-header">Payment Details</div>
<div class="p-3">
<?php
# Wallet class
$wallet = new wallets();

# Wallets and payment password
$ps = $db->query('SELECT * FROM db_purse WHERE id = ?',$uid)->fetchArray();

# Bind Wallet
if(isset($_POST['save_wallet'])) {

$payeer = $wallet->payeer_wallet($_POST['payeer']);
$yandex = $wallet->yandex_wallet($_POST['yandex']);
$qiwi = $wallet->qiwi_wallet($_POST['qiwi']);

# PAYEER
if($payeer !== false) {
$ok = $db->query('SELECT * FROM db_purse WHERE payeer = ?',array($payeer))->numRows();
	if ($ok == 0 && $ps['payeer'] == '0'){
		$db->query('UPDATE db_purse SET payeer = ? WHERE id = ?',array($payeer, $uid));
		$save_p[]='PAYEER Wallet saved';
	} else { $err[] = 'This wallet is already in use!'; }
}

# QIWI
if($qiwi !== false) {
$ok = $db->query('SELECT * FROM db_purse WHERE qiwi = ?',array($qiwi))->numRows();
	if ($ok == 0 && $ps['qiwi'] == '0'){
		$db->query('UPDATE db_purse SET qiwi = ? WHERE id = ?',array($qiwi, $uid));
		$save_p[]='QIWI Wallet saved';
	} else { $err[] = 'This wallet is already in use!'; }
}

# YANDEX
if($yandex !== false) {
$ok = $db->query('SELECT * FROM db_purse WHERE yandex = ?',array($yandex))->numRows();
	if ($ok == 0 && $ps['yandex'] == '0'){
		$db->query('UPDATE db_purse SET yandex = ? WHERE id = ?',array($yandex, $uid));
		$save_p[]='YANDEX Wallet saved';
	} else { $err[] = 'This wallet is already in use!'; }
}

# Errors
if (!empty($err)) {
	echo '<div class="alert alert-danger">'.array_shift($err).'</div>';
}

# Success
if (!empty($save_p)) {
	echo '<div class="alert alert-success">'.array_shift($save_p).'</div>'; }
else {
	echo '<div class="alert alert-danger">Wallet entered incorrectly!</div>'; }
}
?>

<form action="" method="POST">
<?php
if ($ps['payeer'] == '0') {
?>
<div class="input-group mb-2">
	<div class="input-group-prepend"><span class="input-group-text">PAYEER</span></div>
	<input class="form-control" type="text" name="payeer" placeholder="Example P111111111" value=""/>
</div>
<?php
} else {
	echo '<div class="alert alert-info p-2"><b>YOUR PAYEER:</b> '.$ps['payeer'].'</div>';
}
if ($ps['qiwi'] == '0') {
?>
<div class="input-group mb-2">
	<div class="input-group-prepend"><span class="input-group-text">QIWI</span></div>
	<input class="form-control" type="text" name="qiwi" placeholder="Example +79201234567" value="" />
</div>
<?php
} else {
	echo '<div class="alert alert-info p-2"><b>YOUR QIWI:</b> '.$ps['qiwi'].'</div>';
}
if ($ps['yandex'] == '0') {
?>
<div class="input-group">
	<div class="input-group-prepend"><span class="input-group-text">YANDEX</span></div>
	<input class="form-control" type="text" name="yandex" placeholder="Example 40012345600000" value="" />
</div>
<?php
} else {
	echo '<div class="alert alert-info p-2"><b>YOUR YANDEX:</b> '.$ps['yandex'].'</div>';
}
?>
<?php
If ($ps['payeer'] && $ps['qiwi'] && $ps['yandex'] > 0) { } else {
?>
<center>
<button class="btn btn-success mt-2" name="save_wallet" type="submit">Save Details</button>
</center>
<? } ?>

</form>
</div>

</div>
</div>
</div>
