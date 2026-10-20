<?php
$pageTitle = 'Dashboard'; $active = 'index';
require __DIR__ . '/includes/header.php';
$pdo = db();
$count = fn($sql) => (int)$pdo->query($sql)->fetchColumn();
$stats = [
  ['🧑‍🤝‍🧑', 'Patients',               $count('SELECT COUNT(*) FROM patient')],
  ['📅', "Today's appointments",   $count("SELECT COUNT(*) FROM rendezvous WHERE DATE(date_heur)=CURDATE() AND status<>'Cancelled'")],
  ['🩺', 'Consultations',          $count('SELECT COUNT(*) FROM consultation')],
  ['📝', 'Prescriptions',          $count('SELECT COUNT(*) FROM ordonnance')],
  ['📄', 'Certificates',           $count('SELECT COUNT(*) FROM certificat')],
  ['💊', 'Medications',            $count('SELECT COUNT(*) FROM medicament')],
];
$today = $pdo->query("SELECT r.*, CONCAT(p.nom,' ',p.prenom) AS patient FROM rendezvous r JOIN patient p ON p.id_patient=r.id_patient
         WHERE DATE(r.date_heur)=CURDATE() ORDER BY r.date_heur")->fetchAll();
$upcoming = $pdo->query("SELECT r.*, CONCAT(p.nom,' ',p.prenom) AS patient FROM rendezvous r JOIN patient p ON p.id_patient=r.id_patient
         WHERE r.date_heur>NOW() AND DATE(r.date_heur)<>CURDATE() AND r.status='Scheduled' ORDER BY r.date_heur LIMIT 6")->fetchAll();
$recent = $pdo->query("SELECT c.*, CONCAT(p.nom,' ',p.prenom) AS patient FROM consultation c JOIN patient p ON p.id_patient=c.id_patient
         ORDER BY c.date_consultation DESC, c.id_consultation DESC LIMIT 6")->fetchAll();
?>
<div class="grid stats">
<?php foreach ($stats as [$i, $l, $n]): ?>
  <div class="stat"><div class="ico"><?= $i ?></div><div><b><?= $n ?></b><span><?= e($l) ?></span></div></div>
<?php endforeach; ?>
</div>

<div class="top-actions" style="margin-bottom:18px">
  <a class="btn btn-primary" href="patients.php?new=1">＋ New patient</a>
  <a class="btn btn-outline" href="appointments.php?new=1">＋ New appointment</a>
  <a class="btn btn-outline" href="consultations.php?new=1">＋ New consultation</a>
</div>

<div class="grid two">
  <div class="card"><h3>📅 Today's appointments</h3>
    <?php if (!$today): ?><p class="empty">No appointments today.</p><?php else: ?>
    <div class="table-wrap"><table><tbody>
    <?php foreach ($today as $r): ?>
      <tr><td><b><?= date('H:i', strtotime($r['date_heur'])) ?></b></td><td><?= e($r['patient']) ?></td>
      <td><span class="badge b-<?= e($r['status']) ?>"><?= e($r['status']) ?></span></td></tr>
    <?php endforeach; ?></tbody></table></div><?php endif; ?>
    <?php if ($upcoming): ?><h3 style="margin-top:18px">Upcoming</h3><div class="table-wrap"><table><tbody>
    <?php foreach ($upcoming as $r): ?>
      <tr><td><?= date('d/m/Y H:i', strtotime($r['date_heur'])) ?></td><td><?= e($r['patient']) ?></td></tr>
    <?php endforeach; ?></tbody></table></div><?php endif; ?>
  </div>
  <div class="card"><h3>🩺 Recent consultations</h3>
    <?php if (!$recent): ?><p class="empty">No consultations yet.</p><?php else: ?>
    <div class="table-wrap"><table><tbody>
    <?php foreach ($recent as $c): ?>
      <tr><td><?= date('d/m/Y', strtotime($c['date_consultation'])) ?></td>
      <td><a href="patient_history.php?id=<?= $c['id_patient'] ?>"><?= e($c['patient']) ?></a></td>
      <td class="muted"><?= e(mb_strimwidth($c['diagnostic'] ?? '', 0, 50, '…')) ?></td></tr>
    <?php endforeach; ?></tbody></table></div><?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
