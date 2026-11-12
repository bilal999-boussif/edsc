CREATE TABLE membres (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(60) NOT NULL,
  prenom VARCHAR(60) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  telephone VARCHAR(20),
  annee VARCHAR(10) NOT NULL,
  motivation TEXT,
  paiement ENUM('annuel','semestriel') NOT NULL DEFAULT 'annuel',
  mot_de_passe VARCHAR(255) NULL,            -- NULL tant que le compte n'est pas créé
  role ENUM('utilisateur','employe','admin') NOT NULL DEFAULT 'utilisateur',
  date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE evenements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  titre VARCHAR(120) NOT NULL,
  description TEXT,
  date_event DATETIME NOT NULL,
  lieu VARCHAR(120)
);

CREATE TABLE tickets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  membre_id INT NOT NULL,
  sujet VARCHAR(150) NOT NULL,
  message TEXT NOT NULL,
  statut ENUM('ouvert','resolu') DEFAULT 'ouvert',
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (membre_id) REFERENCES membres(id)
);


INSERT INTO evenements (titre, description, date_event, lieu) VALUES
('Atelier Git & GitHub', 'Premiers pas avec le versionnage.', '2026-11-12 17:00:00', 'Salle B2'),
('Réunion mensuelle du club', 'Bilan et prochains projets.', '2026-11-20 18:00:00', 'Amphi 1');
