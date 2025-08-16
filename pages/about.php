<?php
/**
 * File: pages/user/about.php
 * Purpose: "About" page — translated to English, safer output, improved markup
 */
declare(strict_types=1);

if (!defined('FastCore')) {
    exit('Oops!');
}

/** Simple escaper (only defined once) */
if (!function_exists('e')) {
    function e(string $s): string {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }
}

/** Site name & community link */
$siteName = isset($config->sitename) ? (string)$config->sitename : 'Our Project';
$vkUrl    = 'https://vk.com/fastcore';
if (!filter_var($vkUrl, FILTER_VALIDATE_URL)) {
    $vkUrl = '#';
}

/** Page meta (used by your layout/header) */
$opt = [
    'title'       => 'About the Project',
    'keywords'    => 'script, HYIP, games, bonuses, surfing, php8, apache, nginx, mysqli, utf8mb4',
    'description' => 'A fast starter script for PHP 8 with MySQLi and UTF-8/utf8mb4, designed to be simple, scalable, and secure.',
];
?>

<section>
  <h4>About the script — <?= e($siteName) ?></h4>

  <p>
    This script is designed to build websites of varying complexity. It includes a basic feature set to get you started and
    the familiar sections from earlier engines — now faster and more secure. We worked to keep it as simple, convenient, and
    scalable as possible. This version will continue to evolve and may change over time. The script must not be sold in its
    original form because it is public. If you have ideas and coding experience, feel free to extend the engine — create
    modules, themes, and other improvements; they will be useful to the community.
  </p>

  <div class="alert bg-light" style="font-size:110%;padding:12px; border-radius:10px;">
    <strong>Authors & Contributors:</strong> Jumast &amp; Kolyaka105<br>
    Community: <a href="<?= e($vkUrl) ?>" target="_blank" rel="noopener noreferrer">vk.com/fastcore</a><br><br>

    <strong>Server Requirements:</strong>
    <ul style="margin:8px 0 0 18px;">
      <li>PHP 8.0 or higher</li>
      <li>MySQLi with UTF-8/utf8mb4, storage engine: InnoDB</li>
      <li>Apache 2.4 (FastCGI/CGI) or Nginx as an alternative</li>
    </ul>
  </div>
</section>
