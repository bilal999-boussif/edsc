<?php
require 'config.php';
$titre = 'Connexion';
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requete = $pdo->prepare('SELECT * FROM membres WHERE email = ?');
    $requete->execute([trim($_POST['email'] ?? '')]);
    $membre = $requete->fetch(PDO::FETCH_ASSOC);

    if ($membre && $membre['mot_de_passe'] && password_verify($_POST['mot_de_passe'] ?? '', $membre['mot_de_passe'])) {
        $_SESSION['membre'] = ['id' => $membre['id'], 'prenom' => $membre['prenom'], 'role' => $membre['role']];
        header('Location: ' . ($membre['role'] === 'admin' ? 'admin.php' : 'espace.php'));
        exit;
    }
    $erreur = 'E-mail ou mot de passe incorrect.';
}
include 'includes/header.php';
?>
<main class="page-form">
  <h1>Se connecter</h1>
  <?php if (isset($_GET['cree'])): ?><div class="message ok">Compte créé. Tu peux te connecter.</div><?php endif; ?>
  <?php if ($erreur): ?><div class="message ko"><?= e($erreur) ?></div><?php endif; ?>
  <form method="post" class="formulaire">
    <label>E-mail <input type="email" name="email" required></label>
    <label>Mot de passe <input type="password" name="mot_de_passe" required></label>
    <button class="bouton" type="submit">Se connecter</button>
  </form>
  <p>Pas encore membre ? <a href="inscription.php">Remplir le formulaire d’adhésion</a></p>
</main>
<?php include 'includes/footer.php'; ?>
