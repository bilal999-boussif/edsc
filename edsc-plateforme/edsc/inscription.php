<?php
require 'config.php';
$titre = 'Inscription';
$erreur = '';
$succes = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Récupérer et nettoyer les champs
    $nom        = trim($_POST['nom'] ?? '');
    $prenom     = trim($_POST['prenom'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $telephone  = trim($_POST['telephone'] ?? '');
    $annee      = $_POST['annee'] ?? '';
    $motivation = trim($_POST['motivation'] ?? '');
    $paiement   = $_POST['paiement'] ?? 'annuel';
    $motDePasse = is_string($_POST['mot_de_passe'] ?? null) ? $_POST['mot_de_passe'] : '';
    $confirmation = is_string($_POST['confirmation'] ?? null) ? $_POST['confirmation'] : '';

    // 2. Vérifier
    if ($nom === '' || $prenom === '' || $email === '' || $annee === '') {
        $erreur = 'Remplis tous les champs obligatoires.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = 'Adresse e-mail invalide.';
    } elseif (strlen($motDePasse) < 8) {
        $erreur = 'Le mot de passe doit contenir au moins 8 caractères.';
    } elseif ($motDePasse !== $confirmation) {
        $erreur = 'Les deux mots de passe ne sont pas identiques.';
    } else {
        // 3. Vérifier que l'e-mail n'existe pas déjà
        $verif = $pdo->prepare('SELECT id FROM membres WHERE email = ?');
        $verif->execute([$email]);
        if ($verif->fetch()) {
            $erreur = 'Cet e-mail est déjà inscrit.';
        } else {
            // Enregistrer le compte dès l’adhésion sans stocker le mot de passe en clair.
            $ajout = $pdo->prepare('INSERT INTO membres (nom, prenom, email, telephone, annee, motivation, paiement, mot_de_passe)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $ajout->execute([$nom, $prenom, $email, $telephone, $annee, $motivation, $paiement, password_hash($motDePasse, PASSWORD_DEFAULT)]);
            $succes = true;
            // TODO (backend) : générer le PDF d'attestation et l'envoyer par e-mail
        }
    }
}
include 'includes/header.php';
?>
<main class="page-form">
  <h1>Devenir membre de l’EDSC</h1>
  <?php if ($succes): ?>
    <div class="message ok">
      Bienvenue au club, <?= e($prenom) ?> ! Ton attestation sera envoyée à <?= e($email) ?>.
      Ton compte est créé. <a href="connexion.php">Connecte-toi</a>.
    </div>
  <?php else: ?>
    <?php if ($erreur): ?><div class="message ko"><?= e($erreur) ?></div><?php endif; ?>
    <form method="post" class="formulaire" id="form-inscription">
      <label>Nom * <input type="text" name="nom" required value="<?= e($_POST['nom'] ?? '') ?>"></label>
      <label>Prénom * <input type="text" name="prenom" required value="<?= e($_POST['prenom'] ?? '') ?>"></label>
      <label>E-mail * <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"></label>
      <label>Mot de passe * <input type="password" name="mot_de_passe" required minlength="8" autocomplete="new-password"></label>
      <label>Confirmer le mot de passe * <input type="password" name="confirmation" required minlength="8" autocomplete="new-password"></label>
      <label>Téléphone <input type="tel" name="telephone" value="<?= e($_POST['telephone'] ?? '') ?>"></label>
      <label>Année d’études *
        <select name="annee" required>
          <option value="">Choisir…</option>
          <?php foreach (['1AP', '2AP', '3IIR', '4IIR', '5IIR'] as $a): ?>
            <option value="<?= $a ?>"><?= $a ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Pourquoi veux-tu rejoindre le club ?
        <textarea name="motivation" rows="4"><?= e($_POST['motivation'] ?? '') ?></textarea>
      </label>
      <fieldset>
        <legend>Cotisation : 200 DH par an</legend>
        <label class="ligne"><input type="radio" name="paiement" value="annuel" checked> En une fois (200 DH)</label>
        <label class="ligne"><input type="radio" name="paiement" value="semestriel"> En deux fois (100 DH par semestre)</label>
      </fieldset>
      <button class="bouton" type="submit">Envoyer mon adhésion</button>
    </form>
  <?php endif; ?>
</main>
<?php include 'includes/footer.php'; ?>
