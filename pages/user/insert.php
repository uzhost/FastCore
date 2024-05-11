<?php

if (!defined('FastCore')) {
    exit('Oops!');
}

$opt['title'] = 'Top up balance';

include('inc/akcii.php'); // Bonuses

# Payment method
$paymentMethod = $pg->segment[2] ?? NULL;

if ($paymentMethod === 'payeer') {
    $paymentList = 'MAKE A TRANSFER OF ANY AMOUNT TO NUMBER <b>' . $config->py_NUM . '</b>, COPY THE TRANSACTION ID, PASTE IT IN THE LEFT FIELD AND PRESS DEPOSIT.';
    $paymentCommission = '0';
}

if ($paymentMethod === 'freekassa') {
    $paymentList = 'FKWALLET, Yandex Money, Qiwi, Payeer, Advcash, Perfect Money, VISA, BITCOIN, ETHEREUM, Monero, Dogecoin, DASH, LITECOIN, Steam Pay, Exmo, MTS, TELE2, MEGAFON, BEELINE, Sberbank Online.';
    $paymentCommission = '0';
}

# Payment method selected
if ($paymentMethod) {

    $paymentSystemArr = array('payeer' => 'Payeer', 'freekassa' => 'FreeKassa');
    $paymentSystem = $paymentSystemArr[$pg->segment[2]] ?? FALSE;
    $opt['title'] = 'Top up via ' . $paymentSystem . '';

# Payment via Payeer
    if (!empty($_POST['txn']) && $paymentMethod === 'payeer') {
        $txn = (int)$_POST['txn'];
        $db->query("SELECT `id` FROM `db_insert` WHERE `operation_id` = '$txn'");
        if ($db->numRows() == 0) {
            $payeer = new rfs_payeer($config->py_NUM, $config->py_apiID, $config->py_apiKEY);
            if ($payeer->isAuth()) {
                $data = $payeer->getHistoryInfo($txn);
                if (empty($data['auth_error']) && !empty($data['info'])) {
                    if ($config->py_NUM == $data['info']['to']) {
                        if ($data['info']['status'] === 'execute') {
                            if ($data['info']['type'] === 'transfer') {
                                if ($data['info']['protect'] === 'N') {
                                    if ($data['info']['curOut'] === 'RUB') {
                                        $db->query("SELECT `id` FROM `db_insert` WHERE `operation_id` = '$txn'");
                                        if ($db->numRows() == 0) {
                                            $amount = $data['info']['sumOut'];
                                            # Bonus calculation
                                            $sum_x = 0;
                                            $bonus = 0;
                                            $bonuses = $db->query("SELECT * FROM `db_percent` WHERE `type` = '1' ORDER BY `sum_a` BETWEEN {$amount} AND {$amount} OR {$amount} BETWEEN `sum_a` AND `sum_b`")->fetchArray();
                                            if ($bonuses) {
                                                $bonus = $bonuses['sum_x'];
                                                $sum_x = ($amount + ($amount * $bonus));
                                            }
                                            $db->query("INSERT INTO `db_insert` (`uid`,`login`,`sum`,`sum_x`,`sys`,`status`,`operation_id`,`add`,`end`) VALUES ('$uid','$login','$amount','$sum_x','$paymentSystem','1','$txn','" . strtotime($data['info']['dateCreate']) . "','" . time() . "')");
                                            # Referrer
                                            $user_data = $db->query("SELECT rid FROM db_users WHERE id = '$uid' LIMIT 1")->fetchArray();
                                            $referrerId = $user_data["rid"];
                                            $income = ($amount * 0.05);
                                            # Update referrer
                                            $db->query("UPDATE `db_users` SET `money_p` = `money_p` + '$income', `income` = `income` + '$income' WHERE `id` = '$referrerId'");
                                            # Update user
                                            $db->query("UPDATE `db_users` SET `sum_in` = `sum_in` + '$amount', `ref_to` = `ref_to` + '$income', `money_b` = `money_b` + '$sum_x' WHERE `id` = '$uid'");
                                            # Update statistics
                                            $db->query("UPDATE `db_stats` SET `inserts` = `inserts` + '$amount' WHERE `id` = '1'");
                                            echo '<center class="alert alert-success">Balance successfully topped up!</center>';
                                        } else {
                                            echo '<center class="alert alert-danger">Invalid transaction ID!</center>';
                                        }
                                    } else {
                                        echo '<center class="alert alert-danger">Send transfers only in RUB!</center>';
                                    }
                                } else {
                                    echo '<center class="alert alert-danger">Send transfers without protection!</center>';
                                }
                            } else {
                                echo '<center class="alert alert-danger">Invalid transaction ID!</center>';
                            }
                        } else {
                            echo '<center class="alert alert-danger">Invalid transaction ID!</center>';
                        }
                    } else {
                        echo '<center class="alert alert-danger">Invalid transaction ID!</center>';
                    }
                } else {
                    echo '<center class="alert alert-danger">Error 632! Contact the administrator!</center>';
                }
            } else {
                echo '<center class="alert alert-danger">Error 631! Contact the administrator!</center>';
            }
        } else {
            echo '<center class="alert alert-danger">Invalid transaction ID!</center>';
        }
    }

# Payment via FK
    if (isset($_POST['sum']) && $paymentMethod === 'freekassa') {

        $sum = round((float)$_POST["sum"], 2);
        $sys = 'freekassa';
        $sum_x = '0';

# Add to DB
        $db->query("INSERT INTO db_insert (uid, login, sum, sum_x, sys, `add`, status) VALUES ('$uid','$login','$sum','$sum_x','$sys','" . time() . "','0')");

        $order_id = $db->lastInsert();
        $fk_merchant_id = $config->fk_id;
        $fk_merchant_key = $config->fk_key;

# This is the salt
        $hash = md5($fk_merchant_id . ':' . $sum . ':' . $fk_merchant_key . ':' . $order_id);
        ?>
        <center>
            <form method="GET" action="https://www.free-kassa.ru/merchant/cash.php">
                <input type="hidden" name="m" value="<?= $fk_merchant_id ?>">
                <input type="hidden" name="oa" value="<?= $sum ?>">
                <input type="hidden" name="s" value="<?= $hash ?>">
                <input type="hidden" name="us_id" value="<?= $uid ?>">
                <input type="hidden" name="o" value="<?= $order_id ?>"/>
                <input type="submit" value="Pay via FreeKassa" class="btn btn-lg btn-success">
            </form>
        </center>

        <?php
        return;
    }
    ?>

    <div class="row text-center text-uppercase">
        <div class="col-lg-6">
            <div class="card">
                <h5 class="card-header">Top up balance via <b><?= $paymentSystem ?></b></h5>
                <div class="card-body">
                    <?php
                    if ($paymentMethod === 'payeer') {
                        ?>
                        <form action="" method="post">
                            <div class="input-group mb-2">
                                <div class="input-group-prepend">
            <span class="input-group-text">
                <i class="fa fa-ruble-sign"></i>
            </span>
                                </div>
                                <input type="text" class="form-control" name="txn" placeholder="Transaction ID"/>
                            </div>
                            <input type="submit" value="Proceed to payment" class="btn btn-lg btn-success"/>
                        </form>
                    <?php
                    } else {
                    ?>
                        <script type="text/javascript">
                            let cf = 1;

                            function generateThis() {
                                let sum = document.getElementById("getsum").value;
                                let mn = sum * cf;
                                let pro = 0;
                                <?php
                                $bonuses = $db->query('SELECT * FROM db_percent WHERE type = 1  ORDER BY sum_a < sum_a DESC LIMIT 7')->fetchAll();
                                foreach ($bonuses as $bonus) {
                                ?>
                                if (sum > <?=$bonus['sum_a'] ?>) {
                                    mn = sum * cf;
                                    pro = <?=$bonus['sum_x'] ?>;
                                }
                                <?php } ?>
                                $("#d1").html(pro * 100);
                                $("#d2").html((mn = sum * pro).toFixed(2));
                                $("#d3").html((sum * 1).toFixed(2));
                            }
                        </script>
                        <form action="" method="post">
                            <div class="input-group mb-2">
                                <div class="input-group-prepend"><span class="input-group-text"><i
                                            class="fa fa-ruble-sign"></i></span></div>
                                <input type="number" class="form-control" value="100" min="1" max="15000" name="sum"
                                       onkeyup="generateThis();" id="getsum"/>
                            </div>

                            <div class="card bg-light mb-2">
                                <div class="p-2">
                                    Receive: <b id="d3"></b> <small>RUB</small>
                                    <span class="badge badge-warning p-1" style="font-size: 100%"><small>Bonus:</small> <b
                                            id="d2"></b> <small>RUB</small> (+<b id="d1"></b>%)</span><br/>
                                </div>
                            </div>
                            <input type="submit" value="Proceed to payment" class="btn btn-lg btn-success"/>
                        </form>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <h5 class="card-header">Payment methods:</h5>
                <div class="card-body"><?= $paymentList ?>
                    <hr>
                    <b>Commission for topping up <?= $paymentCommission ?>%</b>
                </div>
            </div>
        </div>

        <script>
            let sum = document.getElementById("getsum").value;
            let pro = 0.1;
            let mn = sum * pro;
            $("#d1").html(pro * 100);
            $("#d2").html((mn = sum * pro).toFixed(2));
            $("#d3").html((sum * 1).toFixed(2));
        </script>
    </div>
    <?php
    return;
}
?>

<div class="card pb-0">
    <h5 class="card-header text-center text-uppercase">Choose the method to top up your game balance:</h5>
    <div class="row m-2 ">
        <div class="col-lg-6">
            <a href="/user/insert/payeer" class="card p-5 bg-light mb-1"
               style="background: url(/img/pay/payeer.png) no-repeat center center;background-size: 240px;"><br/><br/><br/></a>
        </div>
        <div class="col-lg-6">
            <a href="/user/insert/freekassa" class="card p-5 bg-light mb-1"
               style="background: url(/img/pay/free.png) no-repeat center center;background-size: 240px;"><br/><br/><br/></a>
        </div>
    </div>

</div>
