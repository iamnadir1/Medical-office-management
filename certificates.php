<?php
require_once __DIR__ . '/includes/init.php';
require_login(); check_csrf();
$pdo = db();
$types = ['Medical Certificate', 'Work Leave'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'delete') {
            $pdo->prepare('DELETE FROM certificat WHERE id_certificat=?')->execute([(int)$_POST['id_certificat']]);
            flash('success', 'Certificate deleted.');
        } elseif ($action === 'add') {
            $pid = (int)($_POST['id_patient'] ?? 0); $type = $_POST['type_certificat'] ?? '';
            if (!$pid || !in_array($type, $types, true)) { flash('error', 'Patient and type are required.'); redirect('certificates.php'); }
            $isLeave = $type === 'Work Leave';
            $d1 = $isLeave ? nullable($_POST['date_debut']) : null; $d2 = $isLeave ? nullable($_POST['date_fin']) : null;
            if ($isLeave && (!$d1 || !$d2 || $d2 < $d1)) { flash('error', 'Please provide a valid start and end date.'); redirect('certificates.php'); }
            $pdo->prepare('INSERT INTO certificat (type_certificat,date_emission,contenu,date_debut,date_fin,id_patient) VALUES (?,?,?,?,?,?)')
                ->execute([$type, $_POST['date_emission'] ?: date('Y-m-d'), trim($_POST['contenu'] ?? ''), $d1, $d2, $pid]);
            flash('success', 'Certificate created.');
            redirect('print.php?type=certificate&id=' . $pdo->lastInsertId());
        }
    } catch (PDOException $e) { flash('error', 'Database error: ' . $e->getMessage()); }
    redirect('certificates.php');
}

$list = $pdo->query("SELECT c.*, CONCAT(p.nom,' ',p.prenom) AS patient_name FROM certificat c JOIN patient p ON p.id_patient=c.id_patient ORDER BY c.date_emission DESC, c.id_certificat DESC")->fetchAll();
$patients = $pdo->query("SELECT id_patient, CONCAT(nom,' ',prenom) AS name FROM patient ORDER BY nom, prenom")->fetchAll();
$pageTitle = 'Certificates'; $active = 'certificates';
$topActions = '<button class="btn btn-primary" data-open-modal="kModal">＋ New certificate</button>';
require __DIR__ . '/includes/header.php';
?>
<div class="card">
  <div class="toolbar"><input type="search" placeholder="🔍 Search…" data-filter="kTable"></div>
  <div class="table-wrap"><table id="kTable"><thead><tr><th>Date</th><th>Patient</th><th>Type</th><th>Period</th><th>Reason</th><th></th></tr></thead><tbody>
  <?php foreach ($list as $c): ?>
    <tr data-row><td><?= date('d/m/Y', strtotime($c['date_emission'])) ?></td>
    <td><a href="patient_history.php?id=<?= $c['id_patient'] ?>"><?= e($c['patient_name']) ?></a></td>
    <td><?= e($c['type_certificat']) ?></td>
    <td><?= $c['date_debut'] ? date('d/m/Y', strtotime($c['date_debut'])) . ' → ' . date('d/m/Y', strtotime($c['date_fin'])) : '—' ?></td>
    <td><?= e(mb_strimwidth($c['contenu'] ?? '', 0, 60, '…')) ?></td>
    <td class="actions">
      <a class="btn btn-sm btn-outline" target="_blank" href="print.php?type=certificate&id=<?= $c['id_certificat'] ?>">🖨 Print</a>
      <form method="post" data-confirm="Delete this certificate?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id_certificat" value="<?= $c['id_certificat'] ?>"><button class="btn btn-sm btn-danger">Delete</button></form>
    </td></tr>
  <?php endforeach; if (!$list) echo '<tr><td colspan="6" class="empty">No certificates yet.</td></tr>'; ?>
  </tbody></table></div>
</div>
<div class="modal" id="kModal"><div class="modal-box">
  <div class="modal-head"><h3>New certificate</h3><button class="modal-close" data-close>&times;</button></div>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="add">
    <label>Patient *<select name="id_patient" id="kPatient" required><option value="">— Select —</option>
      <?php foreach ($patients as $p) echo '<option value="' . $p['id_patient'] . '">' . e($p['name']) . '</option>'; ?></select></label>
    <div class="form-grid">
      <label>Type *<select name="type_certificat" id="kType"><?php foreach ($types as $t) echo "<option>$t</option>"; ?></select></label>
      <label>Issue date<input type="date" name="date_emission" value="<?= date('Y-m-d') ?>"></label>
    </div>
    <div class="form-grid" id="leaveDates" style="display:none">
      <label>Leave from<input type="date" name="date_debut"></label>
      <label>Leave until<input type="date" name="date_fin"></label>
    </div>
    <label>Reason / medical grounds<textarea name="contenu" rows="4"></textarea></label>
    <div class="modal-foot"><button type="button" class="btn" data-close>Cancel</button><button class="btn btn-primary">Save &amp; print</button></div>
  </form></div></div>
<script>
document.addEventListener('DOMContentLoaded',()=>{
  const t=document.getElementById('kType'),l=document.getElementById('leaveDates');
  const sync=()=>l.style.display=t.value==='Work Leave'?'grid':'none'; t.addEventListener('change',sync); sync();
  <?php if (isset($_GET['new'])): $pp=(int)($_GET['patient'] ?? 0); ?>document.querySelector('[data-open-modal=kModal]').click();<?php if ($pp) echo "document.getElementById('kPatient').value='$pp';"; endif; ?>
});
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
