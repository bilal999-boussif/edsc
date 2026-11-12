<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($titre ?? 'EDSC') ?> – Emsi Developer Student Club</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="entete">
  <a class="logo" href="index.php" aria-label="EDSC – accueil">
    <svg viewBox="0 0 64 40" width="40" height="26" aria-hidden="true">
      <path d="M24 5 L7 20 L24 35" fill="none" stroke="#2f6fe4" stroke-width="9" stroke-linecap="round" stroke-linejoin="round"/>
      <path d="M40 5 L57 20 L40 35" fill="none" stroke="#1f9d57" stroke-width="9" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    <span>EDSC</span>
  </a>
  <button class="burger" id="burger" aria-label="Ouvrir le menu" aria-controls="menu" aria-expanded="false">☰</button>
  <nav id="menu">
    <button class="theme-toggle" id="theme-toggle" type="button" aria-label="Activer le mode sombre" aria-pressed="false">
      <svg class="theme-icon theme-icon-lune" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.2 15.8A8 8 0 0 1 8.2 3.8a8.2 8.2 0 1 0 12 12Z"/></svg>
      <svg class="theme-icon theme-icon-soleil" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/></svg>
    </button>
    <a href="index.php#activites">Activités</a>
    <a href="index.php#faq">FAQ</a>
    <?php if (connecte()): ?>
      <?php if (estAdmin()): ?>
        <a href="admin.php">Espace admin</a>
      <?php elseif (estStaff()): ?>
        <a href="espace.php">Espace employé</a>
      <?php else: ?>
        <a href="espace.php">Espace membre</a>
      <?php endif; ?>
      <?php if (estStaff()): ?><a href="evenements.php">Événements</a><?php endif; ?>
      <a class="bouton bouton-clair" href="deconnexion.php">Déconnexion</a>
    <?php else: ?>
      <a href="connexion.php">Se connecter</a>
      <a class="bouton" href="inscription.php">Devenir membre</a>
    <?php endif; ?>
  </nav>
</header>
