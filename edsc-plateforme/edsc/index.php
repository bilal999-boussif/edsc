<?php
require 'config.php';
$titre = 'Accueil';
$lienEspace = estAdmin() ? 'admin.php' : 'espace.php';
$libelleEspace = estAdmin() ? 'Accéder à l’administration' : 'Accéder à mon espace';
include 'includes/header.php';
?>
<main>
  <section class="hero">
    <div class="hero-texte">
      <?php if (connecte()): ?>
        <h1>Ravi de te retrouver, <?= e($_SESSION['membre']['prenom']) ?> !</h1>
        <p>Continue à apprendre, échanger et faire avancer les projets de l’EDSC.</p>
      <?php else: ?>
        <h1>Le club où les développeurs de l’EMSI Tanger apprennent ensemble.</h1>
        <p>Ateliers, projets d’équipe, entraide entre promotions : rejoins l’EDSC et construis de vrais projets avant même ton diplôme.</p>
      <?php endif; ?>
      <div class="actions">
        <?php if (connecte()): ?>
          <a class="bouton" href="<?= e($lienEspace) ?>"><?= e($libelleEspace) ?></a>
        <?php else: ?>
          <a class="bouton" href="inscription.php">Devenir membre</a>
        <?php endif; ?>
        <a class="bouton bouton-clair" href="#activites">Voir nos activités</a>
      </div>
    </div>
    <div class="hero-visuel" aria-hidden="true">
      <div class="chevron bleu"></div><div class="chevron vert"></div>
    </div>
  </section>

  <section class="bloc" id="pourquoi">
    <h2><?= connecte() ? 'Continue à progresser avec l’EDSC' : 'Ce que tu perds en apprenant seul' ?></h2>
    <div class="grille">
      <?php if (connecte()): ?>
        <article><h3>Des ateliers concrets</h3><p>Mets tes connaissances en pratique sur des projets tout au long de l’année.</p></article>
        <article><h3>De l’entraide au quotidien</h3><p>Partage tes questions et aide les autres membres à avancer.</p></article>
        <article><h3>Une communauté active</h3><p>Rencontre des étudiants d’autres promotions et construis ton réseau.</p></article>
      <?php else: ?>
        <article><h3>Des cours sans pratique</h3><p>On voit la théorie, mais rarement un projet complet du début à la fin.</p></article>
        <article><h3>Des blocages sans réponse</h3><p>Une erreur à 23h, personne vers qui se tourner.</p></article>
        <article><h3>Un réseau qui se construit trop tard</h3><p>Les anciens et les promos supérieures ont déjà fait le chemin.</p></article>
      <?php endif; ?>
    </div>
  </section>

  <section class="bloc bloc-teinte" id="activites">
    <h2>Ce que l’EDSC fait à la place</h2>
    <div class="grille">
      <article><h3>Ateliers et projets</h3><p>Des sessions pratiques et des projets en équipe tout au long de l’année.</p></article>
      <article><h3>Tickets d’entraide</h3><p>Pose ta question : un étudiant d’une année supérieure vient t’aider.</p></article>
      <article><h3>Calendrier du club</h3><p>Toutes les réunions et tous les événements au même endroit.</p></article>
    </div>
  </section>

  <section class="bloc bloc-teinte" id="faq">
    <h2>Questions fréquentes</h2>
    <details><summary>Comment devenir membre officiel ?</summary><p>Remplis le formulaire d’adhésion. Tu reçois ensuite une attestation officielle par e-mail, puis tu peux créer ton compte.</p></details>
    <details><summary>Que contient l’espace membre ?</summary><p>Le calendrier, les tickets d’entraide.</p></details>
    <details><summary>Je suis en première année, est-ce pour moi ?</summary><p>Oui. Le club est ouvert à toutes les années, et l’entraide est justement faite pour toi.</p></details>
  </section>

  <section class="cta">
    <?php if (connecte()): ?>
      <h2>Continue à faire vivre l’EDSC !</h2>
      <a class="bouton bouton-blanc" href="<?= e($lienEspace) ?>"><?= e($libelleEspace) ?></a>
    <?php else: ?>
      <h2>Prêt à rejoindre le club ?</h2>
      <a class="bouton bouton-blanc" href="inscription.php">Devenir membre</a>
    <?php endif; ?>
  </section>
</main>
<?php include 'includes/footer.php'; ?>
