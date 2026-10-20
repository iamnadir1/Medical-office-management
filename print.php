<?php
require_once __DIR__ . '/includes/init.php';
require_login();
$pdo = db();
$type = $_GET['type'] ?? ''; $id = (int)($_GET['id'] ?? 0);
$fmt = fn($d) => $d ? date('d/m/Y', strtotime($d)) : '—';
$title = ''; $date = date('Y-m-d'); $patient = null; $body = '';

function one(PDO $pdo, string $sql, int $id) { $s = $pdo->prepare($sql); $s->execute([$id]); return $s->fetch(); }
function all(PDO $pdo, string $sql, int $id) { $s = $pdo->prepare($sql); $s->execute([$id]); return $s->fetchAll(); }

if ($type === 'ordonnance') {
    $o = one($pdo, 'SELECT o.*, c.id_patient FROM ordonnance o JOIN consultation c ON c.id_consultation=o.id_consultation WHERE o.id_ordonnance=?', $id);
    if ($o) {
        $title = 'MEDICAL PRESCRIPTION'; $date = $o['date_emission'];
        $patient = one($pdo, 'SELECT * FROM patient WHERE id_patient=?', (int)$o['id_patient']);
        $meds = all($pdo, 'SELECT m.*, om.posologie FROM ordonnance_medicament om JOIN medicament m ON m.id_medicament=om.id_medicament WHERE om.id_ordonnance=?', $id);
        $body = '<p><b>Prescribed medications:</b></p><ol>';
        foreach ($meds as $m) $body .= '<li><b>' . e($m['nom']) . '</b> ' . e($m['dosage']) . ' (' . e($m['forme']) . ')' . ($m['posologie'] ? '<br><i>' . e($m['posologie']) . '</i>' : '') . '</li>';
        $body .= '</ol><p><b>Treatment duration:</b> ' . ($o['duree_traitement'] ? (int)$o['duree_traitement'] . ' days' : 'As per prescription') . '</p>';
    }
} elseif ($type === 'certificate') {
    $c = one($pdo, 'SELECT * FROM certificat WHERE id_certificat=?', $id);
    if ($c) {
        $patient = one($pdo, 'SELECT * FROM patient WHERE id_patient=?', (int)$c['id_patient']); $date = $c['date_emission'];
        $name = e($patient['nom'] . ' ' . $patient['prenom']);
        if ($c['type_certificat'] === 'Work Leave') {
            $title = 'WORK LEAVE CERTIFICATE';
            $body = "<p>I, the undersigned Doctor, certify that the health condition of the patient <b>$name</b> requires sick leave from <b>" . $fmt($c['date_debut']) . '</b> to <b>' . $fmt($c['date_fin']) . '</b>.</p>'
                  . '<p><b>Medical reason:</b> ' . e($c['contenu']) . '</p><p>The patient must rest and avoid any professional activity during this period.</p>'
                  . '<p>Possible extension to be re-assessed on ' . $fmt($c['date_fin']) . '.</p>';
        } else {
            $title = 'MEDICAL CERTIFICATE';
            $body = "<p>I, the undersigned Doctor, certify that I have examined the patient <b>$name</b>.</p><p><b>Reason:</b> " . e($c['contenu']) . '</p>'
                  . '<p>The patient is fit to resume normal activities.</p><p>Issued to serve and be valid for all legal purposes.</p>';
        }
    }
} elseif ($type === 'history') {
    $patient = one($pdo, 'SELECT * FROM patient WHERE id_patient=?', $id);
    if ($patient) {
        $title = 'PATIENT MEDICAL HISTORY';
        $body = '<p><b>Allergies:</b> ' . e($patient['allergies'] ?: '—') . '<br><b>Medical history:</b> ' . e($patient['antecedents'] ?: '—') . '</p>';
        $cons = all($pdo, 'SELECT * FROM consultation WHERE id_patient=? ORDER BY date_consultation DESC', $id);
        $body .= '<h3>Consultations</h3>';
        if (!$cons) $body .= '<p>No consultations recorded.</p>'; else { $body .= '<table width="100%" border="1" cellspacing="0" cellpadding="6"><tr><th width="110">Date</th><th>Diagnosis</th></tr>'; foreach ($cons as $c) $body .= '<tr><td>' . $fmt($c['date_consultation']) . '</td><td>' . nl2br(e($c['diagnostic'])) . '</td></tr>'; $body .= '</table>'; }
        $r = all($pdo, 'SELECT * FROM rendezvous WHERE id_patient=? ORDER BY date_heur DESC', $id);
        $body .= '<h3>Appointments</h3>';
        if (!$r) $body .= '<p>No appointments recorded.</p>'; else { $body .= '<ul>'; foreach ($r as $x) $body .= '<li>' . date('d/m/Y H:i', strtotime($x['date_heur'])) . ' – ' . e($x['status']) . '</li>'; $body .= '</ul>'; }
        $ce = all($pdo, 'SELECT * FROM certificat WHERE id_patient=? ORDER BY date_emission DESC', $id);
        $body .= '<h3>Medical certificates</h3>';
        if (!$ce) $body .= '<p>No certificates issued.</p>'; else { $body .= '<ul>'; foreach ($ce as $x) $body .= '<li>' . e($x['type_certificat']) . ' – ' . $fmt($x['date_emission']) . '</li>'; $body .= '</ul>'; }
    }
}
?><!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><title><?= e($title ?: 'Print') ?></title><link rel="stylesheet" href="assets/css/style.css"></head>
<body>
<?php if (!$patient): ?><div class="print-sheet"><p>Document not found.</p></div><?php else: ?>
<div class="print-bar no-print"><button class="btn btn-primary" onclick="window.print()">🖨 Print</button><button class="btn btn-outline" onclick="window.close();history.back()">← Back</button></div>
<div class="print-sheet">
  <div class="print-head"><h2><?= e(CLINIC_NAME) ?></h2><?= e(CLINIC_ADDRESS) ?><br>Tel: <?= e(CLINIC_PHONE) ?></div>
  <div class="print-title"><?= e($title) ?></div>
  <p><b>Date:</b> <?= $fmt($date) ?><br>
     <b>Patient:</b> <?= e($patient['nom'] . ' ' . $patient['prenom']) ?><br>
     <?= e('Born: ' . $fmt($patient['date_naissance']) . ' | Phone: ' . ($patient['telephone'] ?: '—') . ' | Blood group: ' . ($patient['groupe_sanguin'] ?: '—')) ?></p>
  <hr>
  <?= $body ?>
  <div class="print-sig">Doctor's signature<br><br><br>Dr ____________________</div>
</div>
<?php endif; ?>
</body></html>
