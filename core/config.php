<?php if(!defined('FastCore')){ exit('Oops!'); }

# База данных
const dbHost = '45.81.226.183';
const dbUser = 'pligin_fastcore';
const dbPass = 'W{pJjqPk(ogL';
const dbName = 'pligin_fastcore'; 

# Подключение к БД
include('classes/db.php');
$db = new db(dbHost, dbUser, dbPass, dbName);

class config {

	# Настройки сайта
	public $start_time = '1596834000';
	public $sitename = 'FastCore v0.8'; // Название
	public $email = 'support@fastcore.ml'; // Почта

	# Админка 
	public $adm_dir = 'admin'; // Директория
	public $adm_name = 'admin'; // Логин
	public $adm_pass = '123456'; // Пароль
	
	# PAYEER
	public $py_shop = '1111'; // ID магазина
	public $py_secret = '1111'; // SECRET ключ магазина
	public $py_NUM = 'P8706145'; // Номер кошелька
	public $py_apiID = '1635281052'; // API ID
	public $py_apiKEY = '8cDT9O79gO4dR7I9';// API KEY

	# FREEKASSA
	public $fk_id = '1111'; // ID магазина FK
	public $fk_key = '1111'; // SECRET 1
	public $fk_key2 = '2222'; // SECRET 2
}

?>