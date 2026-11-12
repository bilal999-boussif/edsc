<?php
require 'config.php';

if (!connecte()) {
    header('Location: connexion.php');
    exit;
}
if (!estAdmin()) {
    header('Location: espace.php');
    exit;
}

$titre = 'Administration';
$roles = ['utilisateur', 'employe', 'admin'];
$resultats = [
    'role_updated' => 'Le rôle a été mis à jour.',
    'member_deleted' => 'Le membre a été supprimé.',
    'not_found' => 'Ce membre n’existe plus.',
    'last_admin' => 'Impossible de retirer le rôle du dernier administrateur.',
    'invalid_role' => 'Le rôle sélectionné est invalide.',
];

if (empty($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jetonCsrf = $_POST['csrf_token'] ?? '';
    if (!is_string($jetonCsrf) || !hash_equals($_SESSION['admin_csrf'], $jetonCsrf)) {
        http_response_code(403);
        exit('Action non autorisée.');
    }

    $membreId = filter_var($_POST['membre_id'] ?? null, FILTER_VALIDATE_INT);
    $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
    $role = is_string($_POST['role'] ?? null) ? $_POST['role'] : '';
    $recherchePost = $_POST['recherche'] ?? '';
    $recherche = is_string($recherchePost) ? trim($recherchePost) : '';
    $resultat = 'not_found';

    if ($membreId && $membreId !== (int) $_SESSION['membre']['id']) {
        if ($action === 'role' && !in_array($role, $roles, true)) {
            $resultat = 'invalid_role';
        } elseif ($action === 'role' || $action === 'delete') {
            try {
                $pdo->beginTransaction();
                $admins = $pdo->query("SELECT id FROM membres WHERE role = 'admin' FOR UPDATE")
                    ->fetchAll(PDO::FETCH_COLUMN);
                $requete = $pdo->prepare('SELECT role FROM membres WHERE id = ? FOR UPDATE');
                $requete->execute([$membreId]);
                $membre = $requete->fetch(PDO::FETCH_ASSOC);

                if ($membre) {
                    $retireAdmin = $membre['role'] === 'admin'
                        && ($action === 'delete' || $role !== 'admin');

                    if ($retireAdmin && count($admins) <= 1) {
                        $resultat = 'last_admin';
                    } elseif ($action === 'delete') {
                        $requete = $pdo->prepare('DELETE FROM tickets WHERE membre_id = ?');
                        $requete->execute([$membreId]);
                        $requete = $pdo->prepare('DELETE FROM membres WHERE id = ?');
                        $requete->execute([$membreId]);
                        $resultat = 'member_deleted';
                    } else {
                        $requete = $pdo->prepare('UPDATE membres SET role = ? WHERE id = ?');
                        $requete->execute([$role, $membreId]);
                        $resultat = 'role_updated';
                    }
                }

                $pdo->commit();
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('Échec de la gestion d’un membre dans admin.php : ' . $e->getMessage());
                $resultat = 'database_error';
            }
        }
    }

    $parametres = ['resultat' => $resultat];
    if ($recherche !== '') {
        $parametres['q'] = $recherche;
    }
    header('Location: admin.php?' . http_build_query($parametres));
    exit;
}

$rechercheGet = $_GET['q'] ?? '';
$recherche = is_string($rechercheGet) ? trim($rechercheGet) : '';
$sql = 'SELECT id, nom, prenom, email, telephone, annee, paiement, role,
               CASE WHEN mot_de_passe IS NULL THEN 0 ELSE 1 END AS compte_actif
        FROM membres';
$parametres = [];
if ($recherche !== '') {
    $sql .= ' WHERE nom LIKE ? OR prenom LIKE ? OR email LIKE ? OR telephone LIKE ?';
    $terme = '%' . $recherche . '%';
    $parametres = [$terme, $terme, $terme, $terme];
}
$sql .= ' ORDER BY nom, prenom';
$requete = $pdo->prepare($sql);
$requete->execute($parametres);
$membres = $requete->fetchAll(PDO::FETCH_ASSOC);

$resultatGet = $_GET['resultat'] ?? '';
$resultat = is_string($resultatGet) ? $resultatGet : '';
$message = $resultats[$resultat] ?? '';
if ($resultat === 'database_error') {
    $message = 'La modification a échoué. Vérifie que le membre ne possède pas de données liées.';
}

include 'includes/header.php';
?>
<main class="admin">
  <h1>Administration</h1>
  <p class="petit"><?= count($membres) ?> membre<?= count($membres) === 1 ? '' : 's' ?> affiché<?= count($membres) === 1 ? '' : 's' ?></p>
  <?php if ($message): ?><div class="message <?= $resultat === 'role_updated' || $resultat === 'member_deleted' ? 'ok' : 'ko' ?>"><?= e($message) ?></div><?php endif; ?>
  <form method="get" class="recherche-membres">
    <label for="recherche-membres">Rechercher un membre</label>
    <div>
      <input id="recherche-membres" type="search" name="q" value="<?= e($recherche) ?>" placeholder="Nom, prénom, e-mail ou téléphone">
      <button class="bouton" type="submit">Rechercher</button>
      <?php if ($recherche !== ''): ?><a href="admin.php">Effacer</a><?php endif; ?>
    </div>
  </form>
  <?php if ($membres): ?>
    <div class="tableau-defilement">
      <table class="tableau-admin">
        <caption>Liste des membres</caption>
        <thead>
          <tr>
            <th scope="col">Nom</th>
            <th scope="col">E-mail</th>
            <th scope="col">Téléphone</th>
            <th scope="col">Année</th>
            <th scope="col">Paiement</th>
            <th scope="col">Rôle</th>
            <th scope="col">Compte</th>
            <th scope="col">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($membres as $membre): ?>
            <tr>
              <td><?= e($membre['prenom'] . ' ' . $membre['nom']) ?></td>
              <td><?= e($membre['email']) ?></td>
              <td><?= e($membre['telephone']) ?></td>
              <td><?= e($membre['annee']) ?></td>
              <td><?= e($membre['paiement']) ?></td>
              <td>
                <?php if ((int) $membre['id'] === (int) $_SESSION['membre']['id']): ?>
                  <?= e($membre['role']) ?> (vous)
                <?php else: ?>
                  <form method="post" class="action-membre">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['admin_csrf']) ?>">
                    <input type="hidden" name="membre_id" value="<?= (int) $membre['id'] ?>">
                    <input type="hidden" name="action" value="role">
                    <input type="hidden" name="recherche" value="<?= e($recherche) ?>">
                    <label class="sr-only" for="role-<?= (int) $membre['id'] ?>">Rôle de <?= e($membre['prenom'] . ' ' . $membre['nom']) ?></label>
                    <select id="role-<?= (int) $membre['id'] ?>" name="role">
                      <?php foreach ($roles as $role): ?>
                        <option value="<?= e($role) ?>" <?= $membre['role'] === $role ? 'selected' : '' ?>>
                          <?= $role === 'utilisateur' ? 'Utilisateur' : ($role === 'employe' ? 'Employé' : 'Administrateur') ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                    <button class="bouton bouton-petit" type="submit">Enregistrer</button>
                  </form>
                <?php endif; ?>
              </td>
              <td><?= $membre['compte_actif'] ? 'Activé' : 'En attente' ?></td>
              <td>
                <?php if ((int) $membre['id'] !== (int) $_SESSION['membre']['id']): ?>
                  <form method="post" class="action-membre" onsubmit="return confirm('Supprimer définitivement ce membre, ainsi que ses tickets ?');">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['admin_csrf']) ?>">
                    <input type="hidden" name="membre_id" value="<?= (int) $membre['id'] ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="recherche" value="<?= e($recherche) ?>">
                    <button class="bouton bouton-danger bouton-petit" type="submit">Supprimer</button>
                  </form>
                <?php else: ?>
                  —
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p><?= $recherche !== '' ? 'Aucun membre ne correspond à cette recherche.' : 'Aucun membre pour le moment.' ?></p>
  <?php endif; ?>
</main>
<?php include 'includes/footer.php'; ?>
