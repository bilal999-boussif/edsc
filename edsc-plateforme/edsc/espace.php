<?php
require 'config.php';
if (!connecte()) { header('Location: connexion.php'); exit; }   // accès réservé aux membres

// Use the current database role so role changes take effect immediately.
$requeteRole = $pdo->prepare('SELECT role FROM membres WHERE id = ?');
$requeteRole->execute([$_SESSION['membre']['id']]);
$roleActuel = $requeteRole->fetchColumn();
if (!$roleActuel) {
    session_destroy();
    header('Location: connexion.php');
    exit;
}
$_SESSION['membre']['role'] = $roleActuel;

$moi = $_SESSION['membre'];
$titre = estStaff() ? 'Espace employé' : 'Espace membre';

$ongletParametre = $_GET['onglet'] ?? 'calendrier';
$onglet = is_string($ongletParametre) ? $ongletParametre : 'calendrier';
$statutTicketParametre = $_GET['statut_ticket'] ?? 'ouvert';
$statutTicket = is_string($statutTicketParametre) && in_array($statutTicketParametre, ['ouvert', 'resolu'], true)
    ? $statutTicketParametre
    : 'ouvert';
if (!in_array($onglet, ['calendrier', 'tickets'], true)) { $onglet = 'calendrier'; }

$messagesTickets = [
    'created' => 'Ton ticket a été envoyé à l’équipe.',
    'updated' => 'Le statut du ticket a été mis à jour.',
    'not_found' => 'Ce ticket n’existe plus.',
    'forbidden' => 'Tu n’es pas autorisé à effectuer cette action.',
];
if (empty($_SESSION['espace_csrf'])) {
    $_SESSION['espace_csrf'] = bin2hex(random_bytes(32));
}

$moisParametre = $_GET['mois'] ?? date('Y-m');
$moisParametre = is_string($moisParametre) ? $moisParametre : '';
$mois = DateTime::createFromFormat('!Y-m', $moisParametre);
if (!$mois || $mois->format('Y-m') !== $moisParametre) {
    $mois = new DateTime('first day of this month');
}
$debutMois = clone $mois;
$finMois = (clone $mois)->modify('+1 month');
$vueCalendrierParametre = $_GET['vue'] ?? 'calendrier';
$vueCalendrierParametre = is_string($vueCalendrierParametre) ? $vueCalendrierParametre : 'calendrier';
$vueCalendrier = in_array($vueCalendrierParametre, ['calendrier', 'liste'], true)
    ? $vueCalendrierParametre
    : 'calendrier';
$premierJourSemaine = (int) $mois->format('N') - 1;
$nbJours = (int) $mois->format('t');
$joursCalendrier = array_merge(array_fill(0, $premierJourSemaine, null), range(1, $nbJours));
while (count($joursCalendrier) % 7 !== 0) {
    $joursCalendrier[] = null;
}
$evenementsParJour = [];
$listeEvenements = [];
if ($onglet === 'calendrier') {
    $requeteEvenements = $pdo->prepare('SELECT id, titre, description, date_event, lieu FROM evenements WHERE date_event >= ? AND date_event < ? ORDER BY date_event');
    $requeteEvenements->execute([$debutMois->format('Y-m-d H:i:s'), $finMois->format('Y-m-d H:i:s')]);
    $listeEvenements = $requeteEvenements->fetchAll(PDO::FETCH_ASSOC);
    foreach ($listeEvenements as $evenement) {
        $jour = (int) date('j', strtotime($evenement['date_event']));
        $evenementsParJour[$jour][] = $evenement;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jetonCsrf = $_POST['csrf_token'] ?? '';
    if (!is_string($jetonCsrf) || !hash_equals($_SESSION['espace_csrf'], $jetonCsrf)) {
        http_response_code(403);
        exit('Action non autorisée.');
    }

    $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
    $statutPost = $_POST['statut_ticket'] ?? 'ouvert';
    $statutTicket = is_string($statutPost) && in_array($statutPost, ['ouvert', 'resolu'], true)
        ? $statutPost
        : 'ouvert';
    $resultat = '';
    if ($action === 'ticket_create' && !estStaff()) {
        $sujet = is_string($_POST['sujet'] ?? null) ? trim($_POST['sujet']) : '';
        $messageTicket = is_string($_POST['message'] ?? null) ? trim($_POST['message']) : '';
        if ($sujet !== '') {
            $pdo->prepare('INSERT INTO tickets (membre_id, sujet, message) VALUES (?, ?, ?)')
                ->execute([$moi['id'], $sujet, $messageTicket]);
            $resultat = 'created';
        }
    } elseif ($action === 'ticket_status' && estStaff()) {
        $ticketId = filter_var($_POST['ticket_id'] ?? null, FILTER_VALIDATE_INT);
        $statut = is_string($_POST['statut'] ?? null) ? $_POST['statut'] : '';
        if ($ticketId && in_array($statut, ['ouvert', 'resolu'], true)) {
            $majTicket = $pdo->prepare('UPDATE tickets SET statut = ? WHERE id = ?');
            $majTicket->execute([$statut, $ticketId]);
            if ($majTicket->rowCount()) {
                $resultat = 'updated';
            } else {
                $verifTicket = $pdo->prepare('SELECT id FROM tickets WHERE id = ?');
                $verifTicket->execute([$ticketId]);
                $resultat = $verifTicket->fetchColumn() ? 'updated' : 'not_found';
            }
        } else {
            $resultat = 'not_found';
        }
    } else {
        $resultat = 'forbidden';
    }

    $parametres = ['onglet' => $onglet];
    if ($onglet === 'tickets') {
        $parametres['statut_ticket'] = $statutTicket;
    }
    if ($resultat !== '') {
        $parametres['resultat'] = $resultat;
    }
    header('Location: espace.php?' . http_build_query($parametres));
    exit;
}
$resultatParametre = $_GET['resultat'] ?? '';
$resultat = is_string($resultatParametre) ? $resultatParametre : '';
$messageTicket = $messagesTickets[$resultat] ?? '';
include 'includes/header.php';
?>
<main class="espace">
  <?php if (estStaff()): ?>
    <h1>Espace employé</h1>
    <p class="petit">Bonjour <?= e($moi['prenom']) ?>, gérez les événements et les tickets du club.</p>
  <?php else: ?>
    <h1>Bonjour <?= e($moi['prenom']) ?></h1>
  <?php endif; ?>
  <nav class="onglets">
    <a href="?onglet=calendrier" class="<?= $onglet === 'calendrier' ? 'actif' : '' ?>">Calendrier</a>
    <a href="?onglet=tickets" class="<?= $onglet === 'tickets' ? 'actif' : '' ?>">Tickets d’entraide</a>
  </nav>

  <?php if ($onglet === 'tickets' && $messageTicket): ?>
    <div class="message <?= in_array($resultat, ['created', 'updated'], true) ? 'ok' : 'ko' ?>"><?= e($messageTicket) ?></div>
  <?php endif; ?>

  <?php if ($onglet === 'calendrier'): ?>
    <?php
      $moisNoms = [1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
      $moisPrecedent = (clone $mois)->modify('-1 month')->format('Y-m');
      $moisSuivant = (clone $mois)->modify('+1 month')->format('Y-m');
      $nomMois = ucfirst($moisNoms[(int) $mois->format('n')]) . ' ' . $mois->format('Y');
    ?>
    <div class="calendrier-entete">
      <a class="bouton bouton-clair" href="?onglet=calendrier&amp;vue=<?= e($vueCalendrier) ?>&amp;mois=<?= e($moisPrecedent) ?>" aria-label="Mois précédent">‹</a>
      <h2><?= e($nomMois) ?></h2>
      <a class="bouton bouton-clair" href="?onglet=calendrier&amp;vue=<?= e($vueCalendrier) ?>&amp;mois=<?= e($moisSuivant) ?>" aria-label="Mois suivant">›</a>
    </div>
    <nav class="vue-calendrier" aria-label="Mode d’affichage des événements">
      <a href="?onglet=calendrier&amp;vue=calendrier&amp;mois=<?= e($mois->format('Y-m')) ?>" class="<?= $vueCalendrier === 'calendrier' ? 'actif' : '' ?>" <?= $vueCalendrier === 'calendrier' ? 'aria-current="page"' : '' ?>>Calendrier</a>
      <a href="?onglet=calendrier&amp;vue=liste&amp;mois=<?= e($mois->format('Y-m')) ?>" class="<?= $vueCalendrier === 'liste' ? 'actif' : '' ?>" <?= $vueCalendrier === 'liste' ? 'aria-current="page"' : '' ?>>Liste</a>
    </nav>
    <?php if ($vueCalendrier === 'calendrier'): ?>
      <div class="calendrier-defilement">
        <div class="calendrier" role="grid" aria-label="Calendrier des événements du club">
          <?php foreach (['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'] as $jourSemaine): ?>
            <div class="calendrier-jour-semaine" role="columnheader"><?= e($jourSemaine) ?></div>
          <?php endforeach; ?>
          <?php foreach ($joursCalendrier as $jour): ?>
            <?php if ($jour === null): ?>
              <div class="calendrier-case calendrier-vide" role="gridcell" aria-hidden="true"></div>
            <?php else: ?>
              <div class="calendrier-case" role="gridcell">
                <span class="calendrier-numero"><?= $jour ?></span>
                <?php foreach ($evenementsParJour[$jour] ?? [] as $evenement): ?>
                  <article class="evenement-calendrier">
                    <strong><?= e(date('H:i', strtotime($evenement['date_event']))) ?> · <?= e($evenement['titre']) ?></strong>
                    <?php if ($evenement['lieu']): ?><span><?= e($evenement['lieu']) ?></span><?php endif; ?>
                    <?php if ($evenement['description']): ?><p><?= nl2br(e($evenement['description'])) ?></p><?php endif; ?>
                  </article>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      </div>
    <?php else: ?>
      <?php foreach ($listeEvenements as $evenement): ?>
        <article class="carte evenement-liste">
          <h3><?= e($evenement['titre']) ?></h3>
          <p><?= e(date('d/m/Y à H:i', strtotime($evenement['date_event']))) ?><?= $evenement['lieu'] ? ' – ' . e($evenement['lieu']) : '' ?></p>
          <?php if ($evenement['description']): ?><p><?= nl2br(e($evenement['description'])) ?></p><?php endif; ?>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>
    <?php if (!$listeEvenements): ?><p>Aucun événement prévu pour ce mois.</p><?php endif; ?>

  <?php elseif ($onglet === 'tickets'): ?>
    <?php if (!estStaff()): ?>
      <form method="post" class="formulaire">
        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['espace_csrf']) ?>">
        <input type="hidden" name="action" value="ticket_create">
        <input type="hidden" name="statut_ticket" value="<?= e($statutTicket) ?>">
        <label>Sujet <input type="text" name="sujet" maxlength="150" required></label>
        <label>Décris ton problème <textarea name="message" rows="3" required></textarea></label>
        <button class="bouton" type="submit">Ouvrir un ticket</button>
      </form>
    <?php else: ?>
      <p class="petit">Gère les demandes des membres en mettant à jour leur statut.</p>
    <?php endif; ?>
    <?php
      $sqlTickets = 'SELECT t.*, m.prenom, m.nom FROM tickets t JOIN membres m ON m.id = t.membre_id';
      if (!estStaff()) {
          $sqlTickets .= ' WHERE t.membre_id = ?';
      }
      $sqlTickets .= (estStaff() ? ' WHERE' : ' AND') . ' t.statut = ? ORDER BY t.date_creation DESC';
      $requeteTickets = $pdo->prepare($sqlTickets);
      $requeteTickets->execute(estStaff() ? [$statutTicket] : [$moi['id'], $statutTicket]);
      $tickets = $requeteTickets->fetchAll(PDO::FETCH_ASSOC);

      $sqlComptageTickets = 'SELECT statut, COUNT(*) AS total FROM tickets';
      if (!estStaff()) {
          $sqlComptageTickets .= ' WHERE membre_id = ?';
      }
      $sqlComptageTickets .= ' GROUP BY statut';
      $requeteComptageTickets = $pdo->prepare($sqlComptageTickets);
      $requeteComptageTickets->execute(estStaff() ? [] : [$moi['id']]);
      $comptageTickets = ['ouvert' => 0, 'resolu' => 0];
      foreach ($requeteComptageTickets->fetchAll(PDO::FETCH_ASSOC) as $comptage) {
          $comptageTickets[$comptage['statut']] = (int) $comptage['total'];
      }
    ?>
    <nav class="filtre-tickets" aria-label="Filtrer les tickets">
      <a href="?onglet=tickets&amp;statut_ticket=ouvert" class="<?= $statutTicket === 'ouvert' ? 'actif' : '' ?>" <?= $statutTicket === 'ouvert' ? 'aria-current="page"' : '' ?>>
        Tickets ouverts <span><?= $comptageTickets['ouvert'] ?></span>
      </a>
      <a href="?onglet=tickets&amp;statut_ticket=resolu" class="<?= $statutTicket === 'resolu' ? 'actif' : '' ?>" <?= $statutTicket === 'resolu' ? 'aria-current="page"' : '' ?>>
        Anciens tickets (résolus) <span><?= $comptageTickets['resolu'] ?></span>
      </a>
    </nav>
    <?php foreach ($tickets as $t): ?>
      <article class="carte">
        <h3><?= e($t['sujet']) ?> <small>(<?= $t['statut'] === 'resolu' ? 'Résolu' : 'Ouvert' ?>)</small></h3>
        <p><?= e($t['message']) ?></p>
        <p class="petit">Par <?= e($t['prenom'] . ' ' . $t['nom']) ?>, le <?= date('d/m/Y', strtotime($t['date_creation'])) ?></p>
        <?php if (estStaff()): ?>
          <form method="post" class="gestion-ticket">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['espace_csrf']) ?>">
            <input type="hidden" name="action" value="ticket_status">
            <input type="hidden" name="ticket_id" value="<?= (int) $t['id'] ?>">
            <input type="hidden" name="statut_ticket" value="<?= e($statutTicket) ?>">
            <label for="statut-ticket-<?= (int) $t['id'] ?>">Statut</label>
            <select id="statut-ticket-<?= (int) $t['id'] ?>" name="statut">
              <option value="ouvert" <?= $t['statut'] === 'ouvert' ? 'selected' : '' ?>>Ouvert</option>
              <option value="resolu" <?= $t['statut'] === 'resolu' ? 'selected' : '' ?>>Résolu</option>
            </select>
            <button class="bouton bouton-petit" type="submit">Mettre à jour</button>
          </form>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
    <?php if (!$tickets): ?><p><?= estStaff() ? 'Aucun ticket à traiter.' : 'Tu n’as pas encore ouvert de ticket.' ?></p><?php endif; ?>

  <?php endif; ?>
</main>
<?php include 'includes/footer.php'; ?>
