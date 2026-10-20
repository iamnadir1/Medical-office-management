<?php
$pageTitle = 'Patient history'; $active = 'patients';
require __DIR__ . '/includes/header.php';
$pdo = db();
$id = (int)($_GET['id'] ?? 0);
$q = $pdo->prepare('SELECT * FROM patient WHERE id_patient=?'); $q->execute([$id]); $p = $q->fetch();
if (!$p) { echo '<div class="alert alert-error">Patient not found.</div><a href="patients.php">← Back</a>'; require __DIR__ . '/includes/footer.php'; exit; }
$get = function ($sql) use ($pdo, $id) { $s = $pdo->prepare($sql); $s->execute([$id]); return $s->fetchAll(); };
$cons  = $get('SELECT * FROM consultation WHERE id_patient=? ORDER BY date_consultation DESC');
$rdvs  = $get('SELECT * FROM rendezvous WHERE id_patient=? ORDER BY date_heur DESC');
$certs = $get('SELECT * FROM certificat WHERE id_patient=? ORDER BY date_emission DESC');
$ords  = $get('SELECT o.*, c.date_consultation, (SELECT GROUP_CONCAT(CONCAT(m.nom," ",IFNULL(m.dosage,"")) SEPARATOR ", ")
               FROM ordonnance_medicament om JOIN medicament m ON m.id_medicament=om.id_medicament WHERE om.id_ordonnance=o.id_ordonnance) AS meds
               FROM ordonnance o JOIN consultation c ON c.id_consultation=o.id_consultation WHERE c.id_patient=? ORDER BY o.date_emission DESC');
$log   = $get('SELECT * FROM historique WHERE id_patient=? ORDER BY id_historique DESC');
$fmt = fn($d) => $d ? date('d/m/Y', strtotime($d)) : '—';
?>
<div class="top-actions no-print" style="margin-bottom:16px">
  <a class="btn btn-outline" href="patients.php">← Patients</a>
  <a class="btn btn-primary" href="print.php?type=history&id=<?= $id ?>" target="_blank">🖨 Print full history</a>
  <a class="btn btn-outline" href="consultations.php?new=1&patient=<?= $id ?>">＋ Consultation</a>
  <a class="btn btn-outline" href="appointments.php?new=1&patient=<?= $id ?>">＋ Appointment</a>
  <a class="btn btn-outline" href="certificates.php?new=1&patient=<?= $id ?>">＋ Certificate</a>
</div>
<div class="card"><h3>🧑 <?= e($p['nom'] . ' ' . $p['prenom']) ?></h3>
  <div class="info-grid">
    <div><span>Date of birth</span><b><?= $fmt($p['date_naissance']) ?></b></div>
    <div><span>Phone</span><b><?= e($p['telephone']) ?: '—' ?></b></div>
    <div><span>Address</span><b><?= e($p['adress']) ?: '—' ?></b></div>
    <div><span>Blood group</span><b><?= e($p['groupe_sanguin']) ?: '—' ?></b></div>
    <div><span>Allergies</span><b><?= e($p['allergies']) ?: '—' ?></b></div>
    <div><span>Medical history</span><b><?= e($p['antecedents']) ?: '—' ?></b></div>
  </div></div>

<div class="grid two">
<div class="card"><h3>🩺 Consultations (<?= count($cons) ?>)</h3>
  <?php if (!$cons) echo '<p class="empty">No consultations recorded.</p>'; else { echo '<table><tbody>'; foreach ($cons as $c)
    echo '<tr><td>' . $fmt($c['date_consultation']) . '</td><td>' . nl2br(e($c['diagnostic'])) . '</td><td><a href="prescriptions.php?new=1&consultation=' . $c['id_consultation'] . '">Prescribe</a></td></tr>'; echo '</tbody></table>'; } ?></div>
<div class="card"><h3>📅 Appointments (<?= count($rdvs) ?>)</h3>
  <?php if (!$rdvs) echo '<p class="empty">No appointments recorded.</p>'; else { echo '<table><tbody>'; foreach ($rdvs as $r)
    echo '<tr><td>' . date('d/m/Y H:i', strtotime($r['date_heur'])) . '</td><td><span class="badge b-' . e($r['status']) . '">' . e($r['status']) . '</span></td></tr>'; echo '</tbody></table>'; } ?></div>
<div class="card"><h3>📝 Prescriptions (<?= count($ords) ?>)</h3>
  <?php if (!$ords) echo '<p class="empty">No prescriptions.</p>'; else { echo '<table><tbody>'; foreach ($ords as $o)
    echo '<tr><td>' . $fmt($o['date_emission']) . '</td><td>' . e($o['meds']) . '</td><td><a target="_blank" href="print.php?type=ordonnance&id=' . $o['id_ordonnance'] . '">Print</a></td></tr>'; echo '</tbody></table>'; } ?></div>
<div class="card"><h3>📄 Certificates (<?= count($certs) ?>)</h3>
  <?php if (!$certs) echo '<p class="empty">No certificates issued.</p>'; else { echo '<table><tbody>'; foreach ($certs as $c)
    echo '<tr><td>' . $fmt($c['date_emission']) . '</td><td>' . e($c['type_certificat']) . '</td><td><a target="_blank" href="print.php?type=certificate&id=' . $c['id_certificat'] . '">Print</a></td></tr>'; echo '</tbody></table>'; } ?></div>
</div>
<div class="card"><h3>🕘 Record modification log</h3>
  <?php if (!$log) echo '<p class="empty">No modifications recorded.</p>'; else { echo '<div class="table-wrap"><table><thead><tr><th>Date</th><th>Field</th><th>Old value</th><th>New value</th></tr></thead><tbody>'; foreach ($log as $l)
    echo '<tr><td>' . $fmt($l['date_modification']) . '</td><td>' . e($l['champ_modifie']) . '</td><td>' . e($l['ancienne_valeur']) . '</td><td>' . e($l['nouvelle_valeur']) . '</td></tr>'; echo '</tbody></table></div>'; } ?></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
