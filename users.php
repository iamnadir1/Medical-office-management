<?php
require_once __DIR__ . '/includes/init.php';
require_role('admin'); check_csrf();
$pdo = db();
$roles = ['admin', 'doctor', 'secretary'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? ''; $id = (int)($_POST['id_utilisateur'] ?? 0);
    try {
        if ($action === 'delete') {
            if ($id === (int)$_SESSION['user']['id']) flash('error', 'You cannot delete your own account.');
            else { $pdo->prepare('DELETE FROM utilisateur WHERE id_utilisateur=?')->execute([$id]); flash('success', 'User deleted.'); }
        } elseif (in_array($action, ['add', 'update'], true)) {
            $nom = trim($_POST['nom'] ?? ''); $prenom = trim($_POST['prenom'] ?? ''); $login = trim($_POST['login'] ?? '');
            $role = in_array($_POST['role'] ?? '', $roles, true) ? $_POST['role'] : 'secretary'; $pw = $_POST['mot_de_passe'] ?? '';
            if (!$nom || !$prenom || !$login) { flash('error', 'Name and username are required.'); redirect('users.php'); }
            if ($action === 'add') {
                if (strlen($pw) < 6) { flash('error', 'Password must be at least 6 characters.'); redirect('users.php'); }
                $pdo->prepare('INSERT INTO utilisateur (nom,prenom,login,mot_de_passe,role) VALUES (?,?,?,?,?)')->execute([$nom, $prenom, $login, password_hash($pw, PASSWORD_DEFAULT), $role]);
                flash('success', 'User created.');
            } else {
                $pdo->prepare('UPDATE utilisateur SET nom=?,prenom=?,login=?,role=? WHERE id_utilisateur=?')->execute([$nom, $prenom, $login, $role, $id]);
                if ($pw !== '') {
                    if (strlen($pw) < 6) { flash('error', 'Password must be at least 6 characters (other changes saved).'); redirect('users.php'); }
                    $pdo->prepare('UPDATE utilisateur SET mot_de_passe=? WHERE id_utilisateur=?')->execute([password_hash($pw, PASSWORD_DEFAULT), $id]);
                }
                flash('success', 'User updated.');
            }
        }
    } catch (PDOException $e) { flash('error', $e->getCode() == 23000 ? 'That username already exists.' : 'Database error: ' . $e->getMessage()); }
    redirect('users.php');
}
$list = $pdo->query('SELECT id_utilisateur,nom,prenom,login,role FROM utilisateur ORDER BY nom')->fetchAll();
$pageTitle = 'Users'; $active = 'users';
$topActions = '<button class="btn btn-primary" data-open-modal="uModal" data-title="New user" data-action="add">＋ Add user</button>';
require __DIR__ . '/includes/header.php';
?>
<div class="card"><div class="table-wrap"><table><thead><tr><th>Name</th><th>Username</th><th>Role</th><th></th></tr></thead><tbody>
<?php foreach ($list as $u): ?>
  <tr><td><b><?= e($u['nom'] . ' ' . $u['prenom']) ?></b></td><td><?= e($u['login']) ?></td><td><span class="badge b-<?= e($u['role']) ?>"><?= e($u['role']) ?></span></td>
  <td class="actions"><button class="btn btn-sm btn-outline" data-open-modal="uModal" data-title="Edit user" data-action="update" data-fill="<?= jattr($u) ?>">Edit</button>
  <form method="post" data-confirm="Delete this user?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id_utilisateur" value="<?= $u['id_utilisateur'] ?>"><button class="btn btn-sm btn-danger">Delete</button></form></td></tr>
<?php endforeach; ?></tbody></table></div></div>
<div class="modal" id="uModal"><div class="modal-box">
  <div class="modal-head"><h3>New user</h3><button class="modal-close" data-close>&times;</button></div>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="add"><input type="hidden" name="id_utilisateur">
    <div class="form-grid"><label>Last name *<input name="nom" maxlength="50" required></label><label>First name *<input name="prenom" maxlength="50" required></label></div>
    <label>Username *<input name="login" maxlength="50" required></label>
    <label>Role<select name="role"><?php foreach ($roles as $r) echo "<option>$r</option>"; ?></select></label>
    <label>Password <small class="muted">(leave empty to keep the current one when editing)</small><input type="password" name="mot_de_passe" autocomplete="new-password"></label>
    <div class="modal-foot"><button type="button" class="btn" data-close>Cancel</button><button class="btn btn-primary">Save</button></div>
  </form></div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
