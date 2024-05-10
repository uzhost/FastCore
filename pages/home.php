<?php if (!defined('FastCore')) { exit('Oops!'); }

# Headers
$opt = array(
    'title' => 'Home Page',
    'keywords' => 'script, hyips, games, bonuses, surfings, php8, apache, nginx',
    'description' => 'Superfast script for creating sites php8, mysqli, utf-8, apache, nginx'
);

# Inserting the inviter's ID into cookies /i/123
if (isset($pg->params[1])) {
    $rid = (intval($pg->params[1]) > 0) ? intval($pg->params[1]) : 1;
    setcookie('i', $rid, time() + (60 * 60 * 24 * 14), '/');
    header('Location: /');
    return;
}

# Statistics
$stats = $db->query("SELECT * FROM db_stats WHERE id = '1'")->fetchArray();
?>

<div class="jumbotron mb-2">
    <div class="container">
        <h1 class="display-4"><b><?=$config->sitename;?></b></h1>
        <p class="lead">Superfast script for creating sites on PHP 8</p>
        <hr class="my-4">
        <div class="row mb-4">
            <div class="col-lg-2 col-md-3">
                <p><small>Script</small></p>
                <p>FastCore v0.8.1</p>
            </div>
            <div class="col-lg-2 col-md-3">
                <p><small>Release</small></p>
                <p>10.05.2024</p>
            </div>
            <div class="col-lg-6 col-md-6">
                <p><small>Requirements</small></p>
                <p>PHP 8.0, Mysqli, Utf-8, Apache, FastCGI</p>
            </div>
        </div>
        <div><a href="https://github.com/uzhost/fastcore" class="btn btn-lg btn-primary">Download</a></div>
    </div>
</div>


<div class="row text-center text-uppercase mb-3 mt-3">
    <div class="col-md-3">
        <div class="p-2 border">
            <i class="fa fa-users" style="font-size: 3rem;"></i>
            <div class="mt-2"><b><?=$stats['users'];?> people</b></div>
            <div>Users</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="p-2 border">
            <i class="fa fa-briefcase" style="font-size: 3rem;"></i>
            <div class="mt-2"><b><?=$stats['inserts'];?> rub.</b></div>
            <div>Top-up</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="p-2 border">
            <i class="fa fa-ruble-sign" style="font-size: 3rem;"></i>
            <div class="mt-2"><b><?=$stats['payments'];?> rub.</b></div>
            <div>Paid out</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="p-2 border">
            <i class="fa fa-university" style="font-size: 3rem;"></i>
            <div class="mt-2"><b><?=intval(((time() - $config->start_time) / 86400 ) +1); ?></b></div>
            <div>Days in operation</div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="p-3 border">
            <h4>About the Script</h4>
            <p>This script is designed for creating websites of any complexity. It includes basic functionality necessary for initial use, as well as familiar sections from predecessors. Now everything works much faster and safer. We have made this script as simple and convenient as possible, and scalable. This version of the script requires further development and will undergo some changes. The script cannot be sold in its pure form as it is public. If you have ideas and understand coding, implement them into this engine, create modules, designs, and other developments; they will be useful.</p>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="p-3 border" style="font-size: 110%;">
            <p>Development: Uzhost Group</p>
            <p>Source: Jumast & Kolyaka105.</p>
            <p>Github - <a href="https://github.com/uzhost/fastcore">Github/FastCore</a></p>
            <p><b>Server Requirements:</b></p>
            <ul>
                <li>PHP 8.0 and above</li>
                <li>Mysqli UTF-8 | inno_db</li>
                <li>Apache 2.4, FastCGI, CGI</li>
            </ul>
        </div>
    </div>
</div>
