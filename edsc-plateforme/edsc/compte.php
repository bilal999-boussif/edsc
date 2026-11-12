<?php
require 'config.php';
$titre = 'Créer mon compte';
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $mdp   = $_POST['mot_de_passe'] ?? '';
    $mdp2  = $_POST['confirmation'] ?? '';

    $requete = $pdo->prepare('SELECT id, mot_de_passe FROM membres WHERE email = ?');
    $requete->execute([$email]);
    $membre = $requete->fetch(PDO::FETCH_ASSOC);

    if (!$membre) {
        $erreur = 'Aucune adhésion trouvée avec cet e-mail. Remplis d’abord le formulaire d’adhésion.';
    } elseif ($membre['mot_de_passe'] !== null) {
        $erreur = 'Un compte existe déjà pour cet e-mail.';
    } elseif (strlen($mdp) < 8 || $mdp !== $mdp2) {
        $erreur = 'Mot de passe : 8 caractères minimum, identique dans les deux champs.';
    } else {
        // password_hash : on ne stocke jamais un mot de passe en clair
        $maj = $pdo->prepare('UPDATE membres SET mot_de_passe = ? WHERE id = ?');
        $maj->execute([password_hash($mdp, PASSWORD_DEFAULT), $membre['id']]);
        header('Location: connexion.php?cree=1');
        exit;
    }
}
include 'includes/header.php';
?>
<main class="page-form">
  <h1>Créer mon compte</h1>
  <p>Réservé aux membres ayant déjà rempli le formulaire d’adhésion.</p>
  <?php if ($erreur): ?><div class="message ko"><?= e($erreur) ?></div><?php endif; ?>
  <form method="post" class="formulaire" id="form-compte">
    <label>E-mail d’adhésion <input type="email" name="email" required></label>
    <label>Mot de passe <input type="password" name="mot_de_passe" required minlength="8"></label>
    <label>Confirmer le mot de passe <input type="password" name="confirmation" required></label>
    <button class="bouton" type="submit">Créer mon compte</button>
  </form>
</main>
<?php include 'includes/footer.php'; ?>
