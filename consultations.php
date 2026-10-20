<?php
require_once __DIR__ . '/includes/init.php';
require_login(); check_csrf();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'delete') {
            $pdo->prepare('DELETE FROM consultation WHERE id_consultation=?')->execute([(int)$_POST['id_consultation']]);
            flash('success', 'Consultation deleted.');
        } elseif (in_array($action, ['add', 'update'], true)) {
            $date = $_POST['date_consultation'] ?? ''; $pid = (int)($_POST['id_patient'] ?? 0);
            $rdv = (int)($_POST['id_rdv'] ?? 0) ?: null;      // 0 / empty -> NULL (as in the Java DAO)
            if (!$date || !$pid) { flash('error', 'Date and patient are required.'); redirect('consultations.php'); }
            if ($action === 'add') {
                $pdo->prepare('INSERT INTO consultation (date_consultation,diagnostic,id_patient,id_rdv) VALUES (?,?,?,?)')->execute([$date, trim($_POST['diagnostic'] ?? ''), $pid, $rdv]);
                if ($rdv) $pdo->prepare("UPDATE rendezvous SET status='Completed' WHERE id_rdv=?")->execute([$rdv]);
                flash('success', 'Consultation saved.');
            } else {
                $pdo->prepare('UPDATE consultation SET date_consultation=?,diagnostic=?,id_patient=?,id_rdv=? WHERE id_consultation=?')
                    ->execute([$date, trim($_POST['diagnostic'] ?? ''), $pid, $rdv, (int)$_POST['id_consultation']]);
                flash('success', 'Consultation updated.');
            }
        }
    } catch (PDOException $e) { flash('error', 'Database error: ' . $e->getMessage()); }
    redirect('consultations.php');
}

$list = $pdo->query("SELECT c.*, CONCAT(p.nom,' ',p.prenom) AS patient_name FROM consultation c LEFT JOIN patient p ON p.id_patient=c.id_patient ORDER BY c.date_consultation DESC, c.id_consultation DESC")->fetchAll();
$patients = $pdo->query("SELECT id_patient, CONCAT(nom,' ',prenom) AS name FROM patient ORDER BY nom, prenom")->fetchAll();
$rdvs = $pdo->query("SELECT r.id_rdv, r.id_patient, r.date_heur, CONCAT(p.nom,' ',p.prenom) AS name FROM rendezvous r JOIN patient p ON p.id_patient=r.id_patient ORDER BY r.date_heur DESC")->fetchAll();

$pageTitle = 'Consultations'; $active = 'consultations';
$topActions = '<button class="btn btn-primary" data-open-modal="cModal" data-title="New consultation" data-action="add">＋ Add consultation</button>';
require __DIR__ . '/includes/header.php';
?>
<div class="card">
  <div class="toolbar"><input type="search" placeholder="🔍 Search patient or diagnosis…" data-filter="cTable"></div>
  <div class="table-wrap"><table id="cTable">
    <thead><tr><th>Date</th><th>Patient</th><th>Diagnosis</th><th></th></tr></thead><tbody>
    <?php foreach ($list as $c): ?>
      <tr data-row>
        <td><?= date('d/m/Y', strtotime($c['date_consultation'])) ?></td>
        <td><a href="patient_history.php?id=<?= $c['id_patient'] ?>"><?= e($c['patient_name']) ?></a></td>
        <td><?= nl2br(e($c['diagnostic'])) ?></td>
        <td class="actions">
          <a class="btn btn-sm btn-outline" href="prescriptions.php?new=1&consultation=<?= $c['id_consultation'] ?>">Prescribe</a>
          <button class="btn btn-sm btn-outline" data-open-modal="cModal" data-title="Edit consultation" data-action="update" data-fill="<?= jattr($c) ?>">Edit</button>
          <form method="post" data-confirm="Delete this consultation?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id_consultation" value="<?= $c['id_consultation'] ?>"><button class="btn btn-sm btn-danger">Delete</button></form>
        </td></tr>
    <?php endforeach; if (!$list) echo '<tr><td colspan="4" class="empty">No consultations.</td></tr>'; ?>
    </tbody></table></div>
</div>
<div class="modal" id="cModal"><div class="modal-box">
  <div class="modal-head"><h3>New consultation</h3><button class="modal-close" data-close>&times;</button></div>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="add"><input type="hidden" name="id_consultation">
    <label>Appointment (optional)<select name="id_rdv" id="rdvSelect"><option value="">— None —</option>
      <?php foreach ($rdvs as $r) echo '<option value="' . $r['id_rdv'] . '" data-patient="' . $r['id_patient'] . '">' . date('d/m/Y H:i', strtotime($r['date_heur'])) . ' – ' . e($r['name']) . '</option>'; ?></select></label>
    <label>Patient *<select name="id_patient" id="consPatient" required><option value="">— Select —</option>
      <?php foreach ($patients as $p) echo '<option value="' . $p['id_patient'] . '">' . e($p['name']) . '</option>'; ?></select></label>
    <label>Date *<input type="date" name="date_consultation" value="<?= date('Y-m-d') ?>" required></label>
    <label>Diagnosis<textarea name="diagnostic" rows="5"></textarea></label>
    <div class="modal-foot"><button type="button" class="btn" data-close>Cancel</button><button class="btn btn-primary">Save</button></div>
  </form></div></div>
<?php if (isset($_GET['new'])): $pp = (int)($_GET['patient'] ?? 0); $pr = (int)($_GET['rdv'] ?? 0); ?>
<script>document.addEventListener('DOMContentLoaded',()=>{document.querySelector('[data-open-modal=cModal]').click();
<?php if ($pp) echo "document.getElementById('consPatient').value='$pp';"; ?>
<?php if ($pr) echo "var s=document.getElementById('rdvSelect');s.value='$pr';s.dispatchEvent(new Event('change'));"; ?>})</script><?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
