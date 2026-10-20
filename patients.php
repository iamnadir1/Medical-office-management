<?php
require_once __DIR__ . '/includes/init.php';
require_login(); check_csrf();
$pdo = db();
$fields = ['nom','prenom','date_naissance','adress','telephone','groupe_sanguin','allergies','antecedents'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'delete') {
            $pdo->prepare('DELETE FROM patient WHERE id_patient=?')->execute([(int)$_POST['id_patient']]);
            flash('success', 'Patient deleted.');
        } elseif (in_array($action, ['add', 'update'], true)) {
            $nom = trim($_POST['nom'] ?? ''); $prenom = trim($_POST['prenom'] ?? '');
            if ($nom === '' || $prenom === '') { flash('error', 'Last name and first name are required.'); redirect('patients.php'); }
            $vals = [$nom, $prenom, nullable($_POST['date_naissance']), nullable($_POST['adress']), nullable($_POST['telephone']),
                     nullable($_POST['groupe_sanguin']), nullable($_POST['allergies']), nullable($_POST['antecedents'])];
            if ($action === 'add') {
                $pdo->prepare('INSERT INTO patient (nom,prenom,date_naissance,adress,telephone,groupe_sanguin,allergies,antecedents) VALUES (?,?,?,?,?,?,?,?)')->execute($vals);
                flash('success', 'Patient added.');
            } else {
                $id = (int)$_POST['id_patient'];
                $old = $pdo->prepare('SELECT * FROM patient WHERE id_patient=?'); $old->execute([$id]); $old = $old->fetch();
                $pdo->prepare('UPDATE patient SET nom=?,prenom=?,date_naissance=?,adress=?,telephone=?,groupe_sanguin=?,allergies=?,antecedents=? WHERE id_patient=?')
                    ->execute([...$vals, $id]);
                // Record every modified field in the "historique" table
                $log = $pdo->prepare('INSERT INTO historique (date_modification,champ_modifie,ancienne_valeur,nouvelle_valeur,id_patient) VALUES (CURDATE(),?,?,?,?)');
                foreach ($fields as $i => $f) {
                    if ((string)($old[$f] ?? '') !== (string)($vals[$i] ?? '')) $log->execute([$f, $old[$f], $vals[$i], $id]);
                }
                flash('success', 'Patient updated.');
            }
        }
    } catch (PDOException $e) { flash('error', 'Database error: ' . $e->getMessage()); }
    redirect('patients.php');
}

$q = trim($_GET['q'] ?? '');
$st = $pdo->prepare("SELECT * FROM patient WHERE CONCAT_WS(' ',nom,prenom,telephone) LIKE ? ORDER BY nom, prenom");
$st->execute(["%$q%"]);
$patients = $st->fetchAll();

$pageTitle = 'Patients'; $active = 'patients';
$topActions = '<button class="btn btn-primary" data-open-modal="pModal" data-title="New patient" data-action="add">＋ Add patient</button>';
require __DIR__ . '/includes/header.php';
$groups = ['A+','A-','B+','B-','AB+','AB-','O+','O-'];
?>
<div class="card">
  <div class="toolbar"><input type="search" placeholder="🔍 Search by name or phone…" data-filter="pTable" value="<?= e($q) ?>"></div>
  <div class="table-wrap"><table id="pTable">
    <thead><tr><th>#</th><th>Name</th><th>Birth date</th><th>Phone</th><th>Blood</th><th>Allergies</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($patients as $p): ?>
      <tr data-row>
        <td><?= $p['id_patient'] ?></td>
        <td><a href="patient_history.php?id=<?= $p['id_patient'] ?>"><b><?= e($p['nom'] . ' ' . $p['prenom']) ?></b></a></td>
        <td><?= $p['date_naissance'] ? date('d/m/Y', strtotime($p['date_naissance'])) : '—' ?></td>
        <td><?= e($p['telephone']) ?></td><td><?= e($p['groupe_sanguin']) ?></td><td><?= e($p['allergies']) ?></td>
        <td class="actions">
          <a class="btn btn-sm btn-outline" href="patient_history.php?id=<?= $p['id_patient'] ?>">History</a>
          <button class="btn btn-sm btn-outline" data-open-modal="pModal" data-title="Edit patient" data-action="update" data-fill="<?= jattr($p) ?>">Edit</button>
          <form method="post" data-confirm="Delete this patient and ALL linked records?"><?= csrf_field() ?>
            <input type="hidden" name="action" value="delete"><input type="hidden" name="id_patient" value="<?= $p['id_patient'] ?>">
            <button class="btn btn-sm btn-danger">Delete</button></form>
        </td>
      </tr>
    <?php endforeach; if (!$patients): ?><tr><td colspan="7" class="empty">No patients found.</td></tr><?php endif; ?>
    </tbody></table></div>
</div>

<div class="modal" id="pModal"><div class="modal-box">
  <div class="modal-head"><h3>New patient</h3><button class="modal-close" data-close>&times;</button></div>
  <form method="post"><?= csrf_field() ?>
    <input type="hidden" name="action" value="add"><input type="hidden" name="id_patient">
    <div class="form-grid">
      <label>Last name *<input name="nom" maxlength="50" required></label>
      <label>First name *<input name="prenom" maxlength="50" required></label>
      <label>Date of birth<input type="date" name="date_naissance"></label>
      <label>Phone<input name="telephone" maxlength="20"></label>
      <label class="full">Address<input name="adress" maxlength="150"></label>
      <label>Blood group<select name="groupe_sanguin"><option value="">—</option><?php foreach ($groups as $g) echo "<option>$g</option>"; ?></select></label>
      <label>Allergies<input name="allergies" maxlength="255"></label>
      <label class="full">Medical history<textarea name="antecedents" rows="3" maxlength="255"></textarea></label>
    </div>
    <div class="modal-foot"><button type="button" class="btn" data-close>Cancel</button><button class="btn btn-primary">Save</button></div>
  </form>
</div></div>
<?php if (isset($_GET['new'])): ?><script>document.addEventListener('DOMContentLoaded',()=>document.querySelector('[data-open-modal=pModal]').click())</script><?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
