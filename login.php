<?php
require_once __DIR__ . '/includes/init.php';
if (!empty($_SESSION['user'])) redirect('index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $st = db()->prepare('SELECT * FROM utilisateur WHERE login = ?');
    $st->execute([$login]);
    $u = $st->fetch();
    $ok = false;
    if ($u) {
        $stored = $u['mot_de_passe'];
        if (str_starts_with($stored, '$2y$') || str_starts_with($stored, '$argon')) {
            $ok = password_verify($pass, $stored);
        } elseif (hash_equals($stored, $pass)) {          // legacy plain-text password from the old app
            $ok = true;
            db()->prepare('UPDATE utilisateur SET mot_de_passe=? WHERE id_utilisateur=?')
                ->execute([password_hash($pass, PASSWORD_DEFAULT), $u['id_utilisateur']]);
        }
    }
    if ($ok) {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => $u['id_utilisateur'], 'nom' => $u['nom'], 'prenom' => $u['prenom'],
                             'login' => $u['login'], 'role' => $u['role']];
        redirect('index.php');
    }
    $error = 'Invalid username or password.';
}
?><!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login · Cabinet Medical</title><link rel="stylesheet" href="assets/css/style.css"></head>
<body class="login-page">
  <form class="login-card" method="post" autocomplete="off">
    <div class="login-logo">⚕️</div>
    <h2>Cabinet Medical</h2>
    <p class="muted">Sign in to manage your practice</p>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <?= csrf_field() ?>
    <label>Username<input type="text" name="login" required autofocus></label>
    <label>Password
      <div class="pw"><input type="password" name="password" id="pw" required>
      <button type="button" class="pw-toggle" onclick="var p=document.getElementById('pw');p.type=p.type==='password'?'text':'password'">👁</button></div>
    </label>
    <button class="btn btn-primary btn-block" type="submit">Log in</button>
  </form>
</body></html>
