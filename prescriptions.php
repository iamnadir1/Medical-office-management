<?php
require_once __DIR__ . '/includes/init.php';
require_login(); check_csrf();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'delete') {
            $pdo->prepare('DELETE FROM ordonnance WHERE id_ordonnance=?')->execute([(int)$_POST['id_ordonnance']]);
            flash('success', 'Prescription deleted.');
        } elseif ($action === 'add') {
            $cid = (int)($_POST['id_consultation'] ?? 0);
            $meds = $_POST['med'] ?? []; $pos = $_POST['posologie'] ?? [];
            $valid = [];
            foreach ($meds as $i => $mid) if ((int)$mid > 0) $valid[] = [(int)$mid, trim($pos[$i] ?? '')];
            if (!$cid || !$valid) { flash('error', 'Choose a consultation and at least one medication.'); redirect('prescriptions.php'); }
            $pdo->beginTransaction();
            $pdo->prepare('INSERT INTO ordonnance (date_emission,duree_traitement,id_consultation) VALUES (?,?,?)')
                ->execute([$_POST['date_emission'] ?: date('Y-m-d'), (int)($_POST['duree_traitement'] ?? 0) ?: null, $cid]);
            $oid = (int)$pdo->lastInsertId();
            $ins = $pdo->prepare('INSERT INTO ordonnance_medicament (id_ordonnance,id_medicament,posologie) VALUES (?,?,?)');
            foreach ($valid as [$mid, $po]) $ins->execute([$oid, $mid, $po]);
            $pdo->commit();
            flash('success', 'Prescription created.');
            redirect('print.php?type=ordonnance&id=' . $oid);
        }
    } catch (PDOException $e) { if ($pdo->inTransaction()) $pdo->rollBack(); flash('error', 'Database error: ' . $e->getMessage()); }
    redirect('prescriptions.php');
}

$list = $pdo->query("SELECT o.*, c.date_consultation, CONCAT(p.nom,' ',p.prenom) AS patient_name, p.id_patient,
    (SELECT GROUP_CONCAT(CONCAT(m.nom,' ',IFNULL(m.dosage,'')) SEPARATOR ', ') FROM ordonnance_medicament om JOIN medicament m ON m.id_medicament=om.id_medicament WHERE om.id_ordonnance=o.id_ordonnance) AS meds
    FROM ordonnance o JOIN consultation c ON c.id_consultation=o.id_consultation JOIN patient p ON p.id_patient=c.id_patient
    ORDER BY o.date_emission DESC, o.id_ordonnance DESC")->fetchAll();
$cons = $pdo->query("SELECT c.id_consultation, c.date_consultation, CONCAT(p.nom,' ',p.prenom) AS name FROM consultation c JOIN patient p ON p.id_patient=c.id_patient ORDER BY c.date_consultation DESC, c.id_consultation DESC")->fetchAll();
$medList = $pdo->query('SELECT * FROM medicament ORDER BY nom')->fetchAll();

$pageTitle = 'Prescriptions'; $active = 'prescriptions';
$topActions = '<button class="btn btn-primary" data-open-modal="oModal">＋ New prescription</button>';
require __DIR__ . '/includes/header.php';
?>
<div class="card">
  <div class="toolbar"><input type="search" placeholder="🔍 Search…" data-filter="oTable"></div>
  <div class="table-wrap"><table id="oTable"><thead><tr><th>Date</th><th>Patient</th><th>Medications</th><th>Duration</th><th></th></tr></thead><tbody>
  <?php foreach ($list as $o): ?>
    <tr data-row><td><?= date('d/m/Y', strtotime($o['date_emission'])) ?></td>
    <td><a href="patient_history.php?id=<?= $o['id_patient'] ?>"><?= e($o['patient_name']) ?></a></td>
    <td><?= e($o['meds']) ?></td><td><?= $o['duree_traitement'] ? (int)$o['duree_traitement'] . ' days' : '—' ?></td>
    <td class="actions">
      <a class="btn btn-sm btn-outline" target="_blank" href="print.php?type=ordonnance&id=<?= $o['id_ordonnance'] ?>">🖨 Print</a>
      <form method="post" data-confirm="Delete this prescription?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id_ordonnance" value="<?= $o['id_ordonnance'] ?>"><button class="btn btn-sm btn-danger">Delete</button></form>
    </td></tr>
  <?php endforeach; if (!$list) echo '<tr><td colspan="5" class="empty">No prescriptions yet.</td></tr>'; ?>
  </tbody></table></div>
</div>

<div class="modal" id="oModal"><div class="modal-box wide">
  <div class="modal-head"><h3>New prescription</h3><button class="modal-close" data-close>&times;</button></div>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="add">
    <label>Consultation *<select name="id_consultation" id="oCons" required><option value="">— Select —</option>
      <?php foreach ($cons as $c) echo '<option value="' . $c['id_consultation'] . '">' . date('d/m/Y', strtotime($c['date_consultation'])) . ' – ' . e($c['name']) . '</option>'; ?></select></label>
    <div class="form-grid">
      <label>Issue date<input type="date" name="date_emission" value="<?= date('Y-m-d') ?>"></label>
      <label>Treatment duration (days)<input type="number" min="1" name="duree_traitement"></label>
    </div>
    <label style="margin-bottom:6px">Medications *</label>
    <div id="medRows"></div>
    <button type="button" class="btn btn-sm btn-outline" id="addMedRow">＋ Add medication</button>
    <div class="modal-foot"><button type="button" class="btn" data-close>Cancel</button><button class="btn btn-primary">Save &amp; print</button></div>
  </form>
  <template id="medRowTpl"><div class="med-row">
    <select name="med[]"><option value="">— Medication —</option>
      <?php foreach ($medList as $m) echo '<option value="' . $m['id_medicament'] . '">' . e($m['nom'] . ' ' . $m['dosage'] . ' (' . $m['forme'] . ')') . '</option>'; ?></select>
    <input name="posologie[]" placeholder="Dosage instructions (e.g. 1 tab × 3/day)" maxlength="150">
    <button type="button" class="btn btn-sm btn-danger rm-row">✕</button></div></template>
</div></div>
<?php if (isset($_GET['new'])): $pc = (int)($_GET['consultation'] ?? 0); ?>
<script>document.addEventListener('DOMContentLoaded',()=>{document.querySelector('[data-open-modal=oModal]').click();<?php if ($pc) echo "document.getElementById('oCons').value='$pc';"; ?>})</script><?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
