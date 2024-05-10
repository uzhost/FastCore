# FastCore Script
![GitHub code size in bytes](https://img.shields.io/github/languages/code-size/uzhost/fastcore)
![GitHub language count](https://img.shields.io/github/languages/count/uzhost/fastcore)
![GitHub top language](https://img.shields.io/github/languages/top/uzhost/fastcore)
![GitHub issues](https://img.shields.io/github/issues/uzhost/fastcore)


This is a modified version of the russian FastCore script.

## Table of contents
* [Requirements](#requirements)
* [Install](#install)
* [Tasks](#tasks)
* [Contribute](#contribute)
* [Donate](#donate)
* [Credits](#credits)


## Requirements
* Apache 2.4
* Mysql 5.7
* PHP 8.0

## Install
1. Upload all files and folders to your hosting(except database.sql file)
2. Open core/config.php and edit all database and settings fields
3. Import database.sql on your phpMyAdmin

#### Default admin credentials:
 - Username: admin
 - Password: 123456

#### Payment url's (POST notifications):
- **Payeer**: /payeer.php
- **FreeKassa**: /freekassa.php

#### Payment url's (return):
- **Success**: /user/success
- **Fail**: /user/fail

## Tasks
- [x] Compatibility with PHP 8.0
- [x] Code re-indentation
- [x] Code cleanup
- [x] Bug Fixes
- [x] Added: Semantic Versioning

## Contribute
You can contribute to this project:

- [Reporting bugs](https://github.com/uzhost/fastcore/issues)
- [Submitting your pull request](https://github.com/uzhost/fastcore/pulls)

## Donate
If you want to contribute to this project, you can send your donation to the wallets below:

 - **Payeer**: P1001110101
 - **TON/USDT(TON NETWORK)**: UQCMPAbHos4iCxyEjRbZNgovwC7oHPXr4khHevoQPqtzJ8H-
 - **BNB/USDT/BUSD(BEP20)**: 000

## Credits

This project wouldn't be possible without our contributors:

- **FastCore Creator**: The creator of FastCore. You can learn more about them on [vk.com/fastcore](https://vk.com/fastcore). 
- **rubensrocha**: A significant contributor to the project. [rubensrocha](https://github.com/rubensrocha/fastcore)

We appreciate their contributions and dedication to the development of this project.
