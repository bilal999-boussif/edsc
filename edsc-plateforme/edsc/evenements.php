<?php
require 'config.php';

if (!connecte()) {
    header('Location: connexion.php');
    exit;
}
$requeteRole = $pdo->prepare('SELECT role FROM membres WHERE id = ?');
$requeteRole->execute([$_SESSION['membre']['id']]);
$roleActuel = $requeteRole->fetchColumn();
if (!$roleActuel) {
    session_destroy();
    header('Location: connexion.php');
    exit;
}
$_SESSION['membre']['role'] = $roleActuel;
if (!estStaff()) {
    header('Location: espace.php');
    exit;
}

$titre = 'Gestion des événements';
$erreur = '';
$messages = [
    'created' => 'L’événement a été ajouté au calendrier.',
    'updated' => 'L’événement a été modifié.',
    'deleted' => 'L’événement a été supprimé.',
];

if (empty($_SESSION['evenements_csrf'])) {
    $_SESSION['evenements_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jetonCsrf = $_POST['csrf_token'] ?? '';
    if (!is_string($jetonCsrf) || !hash_equals($_SESSION['evenements_csrf'], $jetonCsrf)) {
        http_response_code(403);
        exit('Action non autorisée.');
    }

    $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
    $identifiant = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

    if ($action === 'delete' && $identifiant) {
        $requete = $pdo->prepare('DELETE FROM evenements WHERE id = ?');
        $requete->execute([$identifiant]);
        header('Location: evenements.php?resultat=' . ($requete->rowCount() ? 'deleted' : 'not_found'));
        exit;
    }

    if ($action === 'create' || $action === 'update') {
        $titreEvenement = trim(is_string($_POST['titre'] ?? null) ? $_POST['titre'] : '');
        $description = trim(is_string($_POST['description'] ?? null) ? $_POST['description'] : '');
        $lieu = trim(is_string($_POST['lieu'] ?? null) ? $_POST['lieu'] : '');
        $dateSaisie = is_string($_POST['date_event'] ?? null) ? $_POST['date_event'] : '';
        $dateEvent = DateTime::createFromFormat('!Y-m-d\TH:i', $dateSaisie);
        $erreursDate = DateTime::getLastErrors();
        $dateValide = $dateEvent
            && $dateEvent->format('Y-m-d\TH:i') === $dateSaisie
            && ($erreursDate === false || ($erreursDate['warning_count'] === 0 && $erreursDate['error_count'] === 0));

        if ($titreEvenement === '' || strlen($titreEvenement) > 120) {
            $erreur = 'Le titre est obligatoire et ne doit pas dépasser 120 caractères.';
        } elseif (!$dateValide) {
            $erreur = 'Choisis une date et une heure valides.';
        } elseif (strlen($lieu) > 120) {
            $erreur = 'Le lieu ne doit pas dépasser 120 caractères.';
        } elseif ($action === 'update' && !$identifiant) {
            $erreur = 'L’événement à modifier est introuvable.';
        } else {
            $datePourDb = $dateEvent->format('Y-m-d H:i:s');
            if ($action === 'create') {
                $requete = $pdo->prepare('INSERT INTO evenements (titre, description, date_event, lieu) VALUES (?, ?, ?, ?)');
                $requete->execute([$titreEvenement, $description, $datePourDb, $lieu]);
                $resultat = 'created';
            } else {
                $requete = $pdo->prepare('UPDATE evenements SET titre = ?, description = ?, date_event = ?, lieu = ? WHERE id = ?');
                $requete->execute([$titreEvenement, $description, $datePourDb, $lieu, $identifiant]);
                if ($requete->rowCount() === 0) {
                    $existe = $pdo->prepare('SELECT id FROM evenements WHERE id = ?');
                    $existe->execute([$identifiant]);
                    $resultat = $existe->fetchColumn() ? 'updated' : 'not_found';
                } else {
                    $resultat = 'updated';
                }
            }
            header('Location: evenements.php?resultat=' . $resultat);
            exit;
        }
    }
}

$modification = null;
$modifierSource = $_GET['modifier'] ?? ($_POST['id'] ?? null);
$modifierId = filter_var($modifierSource, FILTER_VALIDATE_INT);
if ($modifierId) {
    $requete = $pdo->prepare('SELECT id, titre, description, date_event, lieu FROM evenements WHERE id = ?');
    $requete->execute([$modifierId]);
    $modification = $requete->fetch(PDO::FETCH_ASSOC);
}
$titreFormulaire = is_string($_POST['titre'] ?? null) ? $_POST['titre'] : ($modification['titre'] ?? '');
$dateFormulaire = is_string($_POST['date_event'] ?? null)
    ? $_POST['date_event']
    : ($modification ? date('Y-m-d\TH:i', strtotime($modification['date_event'])) : '');
$lieuFormulaire = is_string($_POST['lieu'] ?? null) ? $_POST['lieu'] : ($modification['lieu'] ?? '');
$descriptionFormulaire = is_string($_POST['description'] ?? null) ? $_POST['description'] : ($modification['description'] ?? '');

$liste = $pdo->query('SELECT id, titre, description, date_event, lieu FROM evenements ORDER BY date_event')->fetchAll(PDO::FETCH_ASSOC);
$resultat = $_GET['resultat'] ?? '';
$message = is_string($resultat) ? ($messages[$resultat] ?? '') : '';
if ($resultat === 'not_found') {
    $message = 'Cet événement n’existe plus.';
}

include 'includes/header.php';
?>
<main class="admin gestion-evenements">
  <h1>Gestion des événements</h1>
  <p>Les événements publiés ici apparaissent dans le calendrier des membres.</p>

  <?php if ($message): ?><div class="message <?= $resultat === 'not_found' ? 'ko' : 'ok' ?>"><?= e($message) ?></div><?php endif; ?>
  <?php if ($erreur): ?><div class="message ko"><?= e($erreur) ?></div><?php endif; ?>

  <section class="form-evenement">
    <h2><?= $modification ? 'Modifier un événement' : 'Ajouter un événement' ?></h2>
    <form method="post" class="formulaire">
      <input type="hidden" name="csrf_token" value="<?= e($_SESSION['evenements_csrf']) ?>">
      <input type="hidden" name="action" value="<?= $modification ? 'update' : 'create' ?>">
      <?php if ($modification): ?><input type="hidden" name="id" value="<?= (int) $modification['id'] ?>"><?php endif; ?>
      <label>Titre *
        <input type="text" name="titre" maxlength="120" required value="<?= e($titreFormulaire) ?>">
      </label>
      <label>Date et heure *
        <input type="datetime-local" name="date_event" required value="<?= e($dateFormulaire) ?>">
      </label>
      <label>Lieu
        <input type="text" name="lieu" maxlength="120" value="<?= e($lieuFormulaire) ?>">
      </label>
      <label>Description
        <textarea name="description" rows="4"><?= e($descriptionFormulaire) ?></textarea>
      </label>
      <div class="actions">
        <button class="bouton" type="submit"><?= $modification ? 'Enregistrer les modifications' : 'Publier l’événement' ?></button>
        <?php if ($modification): ?><a class="bouton bouton-clair" href="evenements.php">Annuler</a><?php endif; ?>
      </div>
    </form>
  </section>

  <section>
    <h2>Événements programmés</h2>
    <?php if ($liste): ?>
      <?php foreach ($liste as $evenement): ?>
        <article class="carte carte-evenement">
          <div>
            <h3><?= e($evenement['titre']) ?></h3>
            <p><?= e(date('d/m/Y à H:i', strtotime($evenement['date_event']))) ?><?= $evenement['lieu'] ? ' – ' . e($evenement['lieu']) : '' ?></p>
            <?php if ($evenement['description']): ?><p><?= nl2br(e($evenement['description'])) ?></p><?php endif; ?>
          </div>
          <div class="actions">
            <a class="bouton bouton-clair bouton-petit" href="evenements.php?modifier=<?= (int) $evenement['id'] ?>">Modifier</a>
            <form method="post" onsubmit="return confirm('Supprimer cet événement du calendrier ?');">
              <input type="hidden" name="csrf_token" value="<?= e($_SESSION['evenements_csrf']) ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int) $evenement['id'] ?>">
              <button class="bouton bouton-danger bouton-petit" type="submit">Supprimer</button>
            </form>
          </div>
        </article>
      <?php endforeach; ?>
    <?php else: ?>
      <p>Aucun événement programmé pour le moment.</p>
    <?php endif; ?>
  </section>
</main>
<?php include 'includes/footer.php'; ?>
