<?php
require_once __DIR__ . '/includes/init.php';
require_login(); check_csrf();
$pdo = db();
$statuses = ['Scheduled', 'Completed', 'Cancelled'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'delete') {
            $pdo->prepare('DELETE FROM rendezvous WHERE id_rdv=?')->execute([(int)$_POST['id_rdv']]);
            flash('success', 'Appointment deleted.');
        } elseif ($action === 'status') {
            if (in_array($_POST['status'], $statuses, true))
                $pdo->prepare('UPDATE rendezvous SET status=? WHERE id_rdv=?')->execute([$_POST['status'], (int)$_POST['id_rdv']]);
            flash('success', 'Status updated.');
        } elseif (in_array($action, ['add', 'update'], true)) {
            $date = str_replace('T', ' ', $_POST['date_heur'] ?? '');
            $status = in_array($_POST['status'] ?? '', $statuses, true) ? $_POST['status'] : 'Scheduled';
            $pid = (int)($_POST['id_patient'] ?? 0);
            if (!$date || !$pid) { flash('error', 'Date and patient are required.'); redirect('appointments.php'); }
            if ($action === 'add') {
                $pdo->prepare('INSERT INTO rendezvous (date_heur,status,id_patient) VALUES (?,?,?)')->execute([$date, $status, $pid]);
                flash('success', 'Appointment added.');
            } else {
                $pdo->prepare('UPDATE rendezvous SET date_heur=?,status=?,id_patient=? WHERE id_rdv=?')->execute([$date, $status, $pid, (int)$_POST['id_rdv']]);
                flash('success', 'Appointment updated.');
            }
        }
    } catch (PDOException $e) { flash('error', 'Database error: ' . $e->getMessage()); }
    redirect('appointments.php');
}

$rdvs = $pdo->query("SELECT r.*, CONCAT(p.nom,' ',p.prenom) AS patient_name FROM rendezvous r LEFT JOIN patient p ON p.id_patient=r.id_patient ORDER BY r.date_heur DESC")->fetchAll();
$patients = $pdo->query("SELECT id_patient, CONCAT(nom,' ',prenom) AS name FROM patient ORDER BY nom, prenom")->fetchAll();

$pageTitle = 'Appointments'; $active = 'appointments';
$topActions = '<button class="btn btn-primary" data-open-modal="rModal" data-title="New appointment" data-action="add">＋ Add appointment</button>';
require __DIR__ . '/includes/header.php';
?>
<div class="card">
  <div class="toolbar">
    <input type="search" placeholder="🔍 Search…" data-filter="rTable">
    <select data-filter-select="rTable"><option value="">All statuses</option><?php foreach ($statuses as $s) echo "<option>$s</option>"; ?></select>
  </div>
  <div class="table-wrap"><table id="rTable">
    <thead><tr><th>Date &amp; time</th><th>Patient</th><th>Status</th><th></th></tr></thead><tbody>
    <?php foreach ($rdvs as $r): $fill = $r; $fill['date_heur'] = date('Y-m-d\TH:i', strtotime($r['date_heur'])); ?>
      <tr data-row data-status="<?= e($r['status']) ?>">
        <td><?= date('d/m/Y H:i', strtotime($r['date_heur'])) ?></td>
        <td><a href="patient_history.php?id=<?= $r['id_patient'] ?>"><?= e($r['patient_name']) ?></a></td>
        <td><form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="action" value="status"><input type="hidden" name="id_rdv" value="<?= $r['id_rdv'] ?>">
          <select name="status" onchange="this.form.submit()" style="width:auto;padding:4px 8px"><?php foreach ($statuses as $s) echo '<option' . ($s === $r['status'] ? ' selected' : '') . ">$s</option>"; ?></select></form></td>
        <td class="actions">
          <a class="btn btn-sm btn-outline" href="consultations.php?new=1&rdv=<?= $r['id_rdv'] ?>">Start consultation</a>
          <button class="btn btn-sm btn-outline" data-open-modal="rModal" data-title="Edit appointment" data-action="update" data-fill="<?= jattr($fill) ?>">Edit</button>
          <form method="post" data-confirm="Delete this appointment?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id_rdv" value="<?= $r['id_rdv'] ?>"><button class="btn btn-sm btn-danger">Delete</button></form>
        </td></tr>
    <?php endforeach; if (!$rdvs) echo '<tr><td colspan="4" class="empty">No appointments.</td></tr>'; ?>
    </tbody></table></div>
</div>
<div class="modal" id="rModal"><div class="modal-box">
  <div class="modal-head"><h3>New appointment</h3><button class="modal-close" data-close>&times;</button></div>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="add"><input type="hidden" name="id_rdv">
    <label>Patient *<select name="id_patient" required><option value="">— Select —</option>
      <?php foreach ($patients as $p) echo '<option value="' . $p['id_patient'] . '">' . e($p['name']) . '</option>'; ?></select></label>
    <label>Date &amp; time *<input type="datetime-local" name="date_heur" required></label>
    <label>Status<select name="status"><?php foreach ($statuses as $s) echo "<option>$s</option>"; ?></select></label>
    <div class="modal-foot"><button type="button" class="btn" data-close>Cancel</button><button class="btn btn-primary">Save</button></div>
  </form></div></div>
<?php if (isset($_GET['new'])): $pre = (int)($_GET['patient'] ?? 0); ?>
<script>document.addEventListener('DOMContentLoaded',()=>{document.querySelector('[data-open-modal=rModal]').click();<?php if ($pre) echo "document.querySelector('#rModal [name=id_patient]').value='$pre';"; ?>})</script><?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
