<?php
/**
 * File: pages/help.php
 * Purpose: Support / Help page — translated to English, safer output, improved markup
 */
declare(strict_types=1);

if (!defined('FastCore')) {
    exit('Oops!');
}

/** Simple escaper (define once globally if you already have it) */
if (!function_exists('e')) {
    function e(string $s): string {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }
}

/** Pull optional contact info from config, if available */
$contactEmail = '';
$communityUrl = '';
if (class_exists('config')) {
    $cfg = new config();
    if (!empty($cfg->email) && filter_var($cfg->email, FILTER_VALIDATE_EMAIL)) {
        $contactEmail = $cfg->email;
    }
    // Reuse VK link from the about page if you keep it consistent
    $communityUrl = 'https://vk.com/fastcore';
}

/** Page meta (used by your layout/header) */
$opt = [
    'title'       => 'Support',
    'keywords'    => 'script, HYIP, games, bonuses, surfing, php8, apache, nginx, mysqli, utf8mb4',
    'description' => 'Fast starter script for PHP 8 with MySQLi and UTF-8/utf8mb4. Support and frequently asked questions.',
];
?>
<section class="container" style="max-width: 900px;">
  <div class="card card-body bg-light" style="border-radius:12px; padding:16px;">
    <h4 style="margin-top:0;">Support &amp; Contact</h4>
    <p>If you need help with installation, configuration, or modules, use the contact options below.</p>

    <ul style="margin:0 0 12px 18px;">
      <?php if ($contactEmail): ?>
        <li>Email: <a href="mailto:<?= e($contactEmail) ?>"><?= e($contactEmail) ?></a></li>
      <?php endif; ?>
      <?php if ($communityUrl && filter_var($communityUrl, FILTER_VALIDATE_URL)): ?>
        <li>Community: <a href="<?= e($communityUrl) ?>" target="_blank" rel="noopener noreferrer"><?= e($communityUrl) ?></a></li>
      <?php endif; ?>
      <li>Server requirements: PHP 8.0+, MySQL/MariaDB (InnoDB, utf8mb4), Apache 2.4 or Nginx</li>
    </ul>
  </div>

  <div class="card card-body" style="border-radius:12px; padding:16px; margin-top:16px;">
    <h5 style="margin:0 0 10px 0;">FAQ</h5>

    <!-- Accessible, no-JS collapse via <details>. Works with or without Bootstrap. -->
    <details style="margin-bottom:8px;" <?php /* open the first item by default */ ?> open>
      <summary><strong>How do I change database settings?</strong></summary>
      <div style="margin-top:6px;">
        Edit <code>core/config.php</code> (or use <code>.env</code> overrides if enabled) to set <code>DB_HOST</code>, <code>DB_USER</code>, <code>DB_PASS</code>, and <code>DB_NAME</code>.
      </div>
    </details>

    <details style="margin-bottom:8px;">
      <summary><strong>How do I secure the admin panel?</strong></summary>
      <div style="margin-top:6px;">
        Change <code>$adm_dir</code> to a unique value, set an <code>ADMIN_PASS_HASH</code> (bcrypt/argon2) in <code>.env</code>,
        and restrict access via server rules (e.g., IP allowlist). Prefer HTTPS and enable strong session settings.
      </div>
    </details>

    <details style="margin-bottom:8px;">
      <summary><strong>Which PHP extensions are required?</strong></summary>
      <div style="margin-top:6px;">
        <code>mysqli</code>, <code>mbstring</code>, and <code>json</code>. For image/file features you may need <code>gd</code> or <code>fileinfo</code>.
      </div>
    </details>

    <details style="margin-bottom:8px;">
      <summary><strong>How do I customize the design?</strong></summary>
      <div style="margin-top:6px;">
        Adjust templates in <code>pages/</code>, shared includes in <code>inc/</code>, and CSS in your theme. Keep logic out of templates for maintainability.
      </div>
    </details>

    <details>
      <summary><strong>Payments don’t credit the balance. What should I check?</strong></summary>
      <div style="margin-top:6px;">
        Verify payment callback (IPN) secrets and signatures, confirm your callback URL is reachable, and check the
        <code>db_insert</code>/<code>db_payout</code> records. Ensure idempotency (unique operation IDs) and correct status handling.
      </div>
    </details>
  </div>
</section>
