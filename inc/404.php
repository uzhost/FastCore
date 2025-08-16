<?php
/**
 * File: inc/404.php
 * Purpose: 404 Not Found page (self-contained, safe headers, a11y-friendly)
 */
declare(strict_types=1);

if (!headers_sent()) {
    http_response_code(404);
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    header('X-Robots-Tag: noindex, nofollow', true);
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
}

$path = isset($_SERVER['REQUEST_URI'])
    ? htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8')
    : '';

$home = '/';
$referer = isset($_SERVER['HTTP_REFERER']) && filter_var($_SERVER['HTTP_REFERER'], FILTER_VALIDATE_URL)
    ? $_SERVER['HTTP_REFERER']
    : '';

$email = '';
if (class_exists('config')) {
    // If your core/config.php defines class config with an email property, use it
    $cfg = new config();
    if (isset($cfg->email) && filter_var($cfg->email, FILTER_VALIDATE_EMAIL)) {
        $email = $cfg->email;
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <title>404 — Страница не найдена</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    :root { --fg:#0f172a; --muted:#475569; --bg:#f8fafc; --card:#ffffff; --btn:#0ea5e9; --btn-fg:#fff; }
    @media (prefers-color-scheme: dark) {
      :root { --fg:#e5e7eb; --muted:#94a3b8; --bg:#0b1220; --card:#0f172a; --btn:#38bdf8; --btn-fg:#0b1220; }
    }
    * { box-sizing: border-box; }
    html,body { height:100%; }
    body { margin:0; font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, "Helvetica Neue", Arial, "Noto Sans", "Apple Color Emoji", "Segoe UI Emoji"; background: var(--bg); color: var(--fg); }
    .wrap { min-height:100%; display:grid; place-items:center; padding: 32px 16px; }
    .card { width:100%; max-width:720px; background:var(--card); border-radius:16px; box-shadow: 0 10px 30px rgba(0,0,0,.08); padding:32px; text-align:center; }
    .code { font-size: clamp(64px, 18vw, 140px); line-height: 1; font-weight: 800; letter-spacing: -0.04em; }
    .subtitle { margin-top: 8px; font-size: 18px; color: var(--muted); }
    .path { margin-top: 6px; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace; font-size: 14px; color: var(--muted); word-break: break-all; }
    .actions { margin-top: 24px; display:flex; gap:12px; justify-content:center; flex-wrap:wrap; }
    .btn { display:inline-block; padding: 12px 18px; border-radius: 10px; text-decoration:none; border:1px solid transparent; background:var(--btn); color:var(--btn-fg); font-weight:600; }
    .btn.outline { background:transparent; color:var(--fg); border-color: rgba(148,163,184,.4); }
    .help { margin-top:18px; font-size:14px; color: var(--muted); }
  </style>
</head>
<body>
  <main class="wrap" role="main" aria-label="Страница не найдена">
    <section class="card" aria-labelledby="nf-title">
      <div class="code" id="nf-title" aria-hidden="true">404</div>
      <h1 style="margin: 8px 0 0; font-size: 24px;">Страница не найдена</h1>
      <p class="subtitle">Запрошенный адрес не существует или был перемещён.</p>
      <?php if ($path): ?>
        <div class="path">Путь: <code><?= $path ?></code></div>
      <?php endif; ?>

      <div class="actions">
        <a class="btn" href="<?= htmlspecialchars($home, ENT_QUOTES, 'UTF-8') ?>">На главную</a>
        <?php if ($referer): ?>
          <a class="btn outline" href="<?= htmlspecialchars($referer, ENT_QUOTES, 'UTF-8') ?>">Назад</a>
        <?php else: ?>
          <a class="btn outline" href="javascript:history.back()">Назад</a>
        <?php endif; ?>
      </div>

      <?php if ($email): ?>
        <p class="help">Считаете, что это ошибка? Напишите нам: <a href="mailto:<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></a></p>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>
