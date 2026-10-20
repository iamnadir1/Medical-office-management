<?php
require_once __DIR__ . '/init.php';
require_login();
check_csrf();
$nav = [
  'index'         => ['Dashboard',     '🏠'],
  'patients'      => ['Patients',      '🧑‍🤝‍🧑'],
  'appointments'  => ['Appointments',  '📅'],
  'consultations' => ['Consultations', '🩺'],
  'prescriptions' => ['Prescriptions', '📝'],
  'certificates'  => ['Certificates',  '📄'],
  'medicaments'   => ['Medications',   '💊'],
];
if ($_SESSION['user']['role'] === 'admin') $nav['users'] = ['Users', '🔐'];
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? 'Cabinet Medical') ?> · Cabinet Medical</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<aside class="sidebar" id="sidebar">
  <div class="brand">⚕️ <span>Cabinet Medical</span></div>
  <nav>
    <?php foreach ($nav as $key => [$label, $icon]): ?>
      <a href="<?= $key ?>.php" class="<?= ($active ?? '') === $key ? 'active' : '' ?>"><?= $icon ?> <span><?= $label ?></span></a>
    <?php endforeach; ?>
  </nav>
  <div class="userbox">
    <div><strong><?= e($_SESSION['user']['prenom'] . ' ' . $_SESSION['user']['nom']) ?></strong>
    <small><?= e(ucfirst($_SESSION['user']['role'])) ?></small></div>
    <a class="btn btn-light btn-sm" href="logout.php">Log out</a>
  </div>
</aside>
<main class="content">
  <header class="topbar">
    <button class="menu-toggle" id="menuToggle" aria-label="Menu">☰</button>
    <h1><?= e($pageTitle ?? '') ?></h1>
    <div class="top-actions"><?= $topActions ?? '' ?></div>
  </header>
  <?php foreach (get_flash() as [$t, $m]): ?>
    <div class="alert alert-<?= e($t) ?>"><?= e($m) ?></div>
  <?php endforeach; ?>
