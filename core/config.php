<?php
if (!defined('FastCore')) {
    exit('Oops!');
}

# База данных
const dbHost = 'localhost';
const dbUser = 'db_user';
const dbPass = 'db_pass';
const dbName = 'db_name';

# Подключение к БД
include('classes/db.php');
$db = new db(dbHost, dbUser, dbPass, dbName);

class config
{
    # Настройки сайта
    public string $start_time = '1715363489';
    public string $sitename = 'FASTCOIN'; // Название
    public string $email = 'support@fastcoin.info'; // Почта
    public string $email_domain = 'fastcore.info'; // domain. Eg: domain.com

    # Админка
    public string $adm_dir = 'admin'; // Директория
    public string $adm_name = 'admin'; // Логин
    public string $adm_pass = '123456'; // Пароль

    # PAYEER
    public string $py_shop = '1111'; // ID магазина
    public string $py_secret = '1111'; // SECRET ключ магазина
    public string $py_NUM = 'P1234567'; // Номер кошелька
    public string $py_apiID = '1234567890'; // API ID
    public string $py_apiKEY = '9876543210'; // API KEY

    # FREEKASSA
    public string $fk_id = '1111'; // ID магазина FK
    public string $fk_key = '1111'; // SECRET 1
    public string $fk_key2 = '2222'; // SECRET 2
}

?>
