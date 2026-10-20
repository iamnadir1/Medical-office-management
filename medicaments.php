<?php
require_once __DIR__ . '/includes/init.php';
require_login(); check_csrf();
$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'delete') {
            $pdo->prepare('DELETE FROM medicament WHERE id_medicament=?')->execute([(int)$_POST['id_medicament']]);
            flash('success', 'Medication deleted.');
        } elseif (in_array($action, ['add', 'update'], true)) {
            $nom = trim($_POST['nom'] ?? '');
            if ($nom === '') { flash('error', 'Name is required.'); redirect('medicaments.php'); }
            $v = [$nom, nullable($_POST['dosage']), nullable($_POST['forme'])];
            if ($action === 'add') { $pdo->prepare('INSERT INTO medicament (nom,dosage,forme) VALUES (?,?,?)')->execute($v); flash('success', 'Medication added.'); }
            else { $pdo->prepare('UPDATE medicament SET nom=?,dosage=?,forme=? WHERE id_medicament=?')->execute([...$v, (int)$_POST['id_medicament']]); flash('success', 'Medication updated.'); }
        }
    } catch (PDOException $e) { flash('error', 'Database error: ' . $e->getMessage()); }
    redirect('medicaments.php');
}
$list = $pdo->query('SELECT * FROM medicament ORDER BY nom')->fetchAll();
$forms = ['Tablet','Capsule','Syrup','Injection','Ointment','Drops','Suppository','Powder','Other'];
$pageTitle = 'Medications'; $active = 'medicaments';
$topActions = '<button class="btn btn-primary" data-open-modal="mModal" data-title="New medication" data-action="add">＋ Add medication</button>';
require __DIR__ . '/includes/header.php';
?>
<div class="card">
  <div class="toolbar"><input type="search" placeholder="🔍 Search medication…" data-filter="mTable"></div>
  <div class="table-wrap"><table id="mTable"><thead><tr><th>#</th><th>Name</th><th>Dosage</th><th>Form</th><th></th></tr></thead><tbody>
  <?php foreach ($list as $m): ?>
    <tr data-row><td><?= $m['id_medicament'] ?></td><td><b><?= e($m['nom']) ?></b></td><td><?= e($m['dosage']) ?></td><td><?= e($m['forme']) ?></td>
    <td class="actions">
      <button class="btn btn-sm btn-outline" data-open-modal="mModal" data-title="Edit medication" data-action="update" data-fill="<?= jattr($m) ?>">Edit</button>
      <form method="post" data-confirm="Delete this medication?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id_medicament" value="<?= $m['id_medicament'] ?>"><button class="btn btn-sm btn-danger">Delete</button></form>
    </td></tr>
  <?php endforeach; if (!$list) echo '<tr><td colspan="5" class="empty">No medications yet.</td></tr>'; ?>
  </tbody></table></div>
</div>
<div class="modal" id="mModal"><div class="modal-box">
  <div class="modal-head"><h3>New medication</h3><button class="modal-close" data-close>&times;</button></div>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="add"><input type="hidden" name="id_medicament">
    <label>Name *<input name="nom" maxlength="100" required></label>
    <label>Dosage<input name="dosage" maxlength="50" placeholder="e.g. 500 mg"></label>
    <label>Form<input name="forme" list="forms" maxlength="50"><datalist id="forms"><?php foreach ($forms as $f) echo "<option value=\"$f\">"; ?></datalist></label>
    <div class="modal-foot"><button type="button" class="btn" data-close>Cancel</button><button class="btn btn-primary">Save</button></div>
  </form></div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
