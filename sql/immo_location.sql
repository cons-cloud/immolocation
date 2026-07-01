-- ============================================================
--  IMMO-LOCATION — Schéma Base de Données Complet v2.0
--  Plateforme de location immobilière & automobile au Maroc
-- ============================================================

CREATE DATABASE IF NOT EXISTS immo_location
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE immo_location;

-- ─────────────────────────────────────────
-- TABLE : utilisateurs
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS utilisateurs (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  prenom          VARCHAR(100) NOT NULL,
  nom             VARCHAR(100) NOT NULL,
  email           VARCHAR(191) NOT NULL UNIQUE,
  telephone       VARCHAR(20),
  mot_de_passe    VARCHAR(255) NOT NULL,
  type_compte     ENUM('client','proprietaire','admin') NOT NULL DEFAULT 'client',
  avatar          VARCHAR(255) DEFAULT NULL,
  bio             TEXT DEFAULT NULL,
  ville           VARCHAR(100) DEFAULT NULL,
  statut          ENUM('actif','suspendu','en_attente') DEFAULT 'actif',
  email_verifie   TINYINT(1) DEFAULT 0,
  token_reset     VARCHAR(255) DEFAULT NULL,
  date_creation   DATETIME DEFAULT CURRENT_TIMESTAMP,
  derniere_connexion DATETIME DEFAULT NULL,
  INDEX idx_email (email),
  INDEX idx_type  (type_compte)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- TABLE : biens (appartements, villas, maisons)
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS biens (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  proprietaire_id INT NOT NULL,
  titre           VARCHAR(255) NOT NULL,
  description     TEXT,
  type_bien       ENUM('appartement','villa','maison') NOT NULL,
  adresse         VARCHAR(255),
  ville           VARCHAR(100),
  prix_nuit       DECIMAL(10,2) NOT NULL,
  surface         INT COMMENT 'en m²',
  nb_chambres     INT DEFAULT 1,
  nb_salles_bain  INT DEFAULT 1,
  nb_personnes    INT DEFAULT 2,
  wifi            TINYINT(1) DEFAULT 0,
  piscine         TINYINT(1) DEFAULT 0,
  parking         TINYINT(1) DEFAULT 0,
  climatisation   TINYINT(1) DEFAULT 0,
  cuisine         TINYINT(1) DEFAULT 0,
  disponible      TINYINT(1) DEFAULT 1,
  note_moyenne    DECIMAL(3,2) DEFAULT 0.00,
  nb_avis         INT DEFAULT 0,
  image_principale VARCHAR(255),
  vues            INT DEFAULT 0,
  statut          ENUM('actif','inactif','en_attente') DEFAULT 'actif',
  date_creation   DATETIME DEFAULT CURRENT_TIMESTAMP,
  date_modification DATETIME ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (proprietaire_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
  INDEX idx_type_bien (type_bien),
  INDEX idx_ville     (ville),
  INDEX idx_prix      (prix_nuit),
  INDEX idx_disponible (disponible)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- TABLE : images_biens
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS images_biens (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  bien_id   INT NOT NULL,
  url       VARCHAR(255) NOT NULL,
  ordre     INT DEFAULT 0,
  FOREIGN KEY (bien_id) REFERENCES biens(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- TABLE : voitures
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS voitures (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  proprietaire_id INT NOT NULL,
  marque          VARCHAR(100) NOT NULL,
  modele          VARCHAR(100) NOT NULL,
  annee           YEAR,
  couleur         VARCHAR(50),
  immatriculation VARCHAR(20) UNIQUE,
  carburant       ENUM('essence','diesel','hybride','electrique') DEFAULT 'essence',
  boite           ENUM('manuelle','automatique') DEFAULT 'manuelle',
  nb_places       INT DEFAULT 5,
  climatisation   TINYINT(1) DEFAULT 1,
  gps             TINYINT(1) DEFAULT 0,
  prix_jour       DECIMAL(10,2) NOT NULL,
  caution         DECIMAL(10,2) DEFAULT 0,
  ville           VARCHAR(100),
  disponible      TINYINT(1) DEFAULT 1,
  note_moyenne    DECIMAL(3,2) DEFAULT 0.00,
  nb_avis         INT DEFAULT 0,
  image_principale VARCHAR(255),
  km              INT DEFAULT 0,
  statut          ENUM('actif','inactif','en_attente') DEFAULT 'actif',
  date_creation   DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (proprietaire_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
  INDEX idx_ville_v   (ville),
  INDEX idx_prix_v    (prix_jour),
  INDEX idx_dispo_v   (disponible)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- TABLE : images_voitures
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS images_voitures (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  voiture_id  INT NOT NULL,
  url         VARCHAR(255) NOT NULL,
  ordre       INT DEFAULT 0,
  FOREIGN KEY (voiture_id) REFERENCES voitures(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- TABLE : reservations
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS reservations (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  client_id       INT NOT NULL,
  type_reservation ENUM('bien','voiture') NOT NULL,
  bien_id         INT DEFAULT NULL,
  voiture_id      INT DEFAULT NULL,
  date_debut      DATE NOT NULL,
  date_fin        DATE NOT NULL,
  nb_jours        INT NOT NULL,
  prix_total      DECIMAL(10,2) NOT NULL,
  statut          ENUM('en_attente','confirmee','annulee','terminee') DEFAULT 'en_attente',
  message_client  TEXT,
  numero_reservation VARCHAR(20) UNIQUE,
  date_creation   DATETIME DEFAULT CURRENT_TIMESTAMP,
  date_modification DATETIME ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
  FOREIGN KEY (bien_id) REFERENCES biens(id) ON DELETE SET NULL,
  FOREIGN KEY (voiture_id) REFERENCES voitures(id) ON DELETE SET NULL,
  INDEX idx_client  (client_id),
  INDEX idx_statut  (statut),
  INDEX idx_dates   (date_debut, date_fin)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- TABLE : paiements
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS paiements (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  reservation_id  INT NOT NULL UNIQUE,
  montant         DECIMAL(10,2) NOT NULL,
  methode         ENUM('carte','virement','especes','paypal') DEFAULT 'carte',
  statut          ENUM('en_attente','valide','rembourse','echoue') DEFAULT 'en_attente',
  reference       VARCHAR(100) UNIQUE,
  derniers_4      VARCHAR(4) COMMENT 'Derniers 4 chiffres carte',
  date_paiement   DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- TABLE : avis
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS avis (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  auteur_id       INT NOT NULL,
  type_cible      ENUM('bien','voiture') NOT NULL,
  bien_id         INT DEFAULT NULL,
  voiture_id      INT DEFAULT NULL,
  note            TINYINT NOT NULL CHECK (note BETWEEN 1 AND 5),
  commentaire     TEXT,
  date_creation   DATETIME DEFAULT CURRENT_TIMESTAMP,
  statut          ENUM('actif','masque') DEFAULT 'actif',
  FOREIGN KEY (auteur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
  FOREIGN KEY (bien_id) REFERENCES biens(id) ON DELETE CASCADE,
  FOREIGN KEY (voiture_id) REFERENCES voitures(id) ON DELETE CASCADE,
  INDEX idx_bien_id (bien_id),
  INDEX idx_voiture_id (voiture_id)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- TABLE : favoris
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS favoris (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  utilisateur_id  INT NOT NULL,
  type_favori     ENUM('bien','voiture') NOT NULL,
  bien_id         INT DEFAULT NULL,
  voiture_id      INT DEFAULT NULL,
  date_ajout      DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_fav (utilisateur_id, type_favori, bien_id, voiture_id),
  FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
  FOREIGN KEY (bien_id) REFERENCES biens(id) ON DELETE CASCADE,
  FOREIGN KEY (voiture_id) REFERENCES voitures(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- TABLE : notifications
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS notifications (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  utilisateur_id  INT NOT NULL,
  titre           VARCHAR(255) NOT NULL,
  message         TEXT NOT NULL,
  type            ENUM('reservation','paiement','avis','systeme','alerte') DEFAULT 'systeme',
  lue             TINYINT(1) DEFAULT 0,
  lien            VARCHAR(255) DEFAULT NULL,
  date_creation   DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
  INDEX idx_user_notif (utilisateur_id, lue)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- TABLE : messages (contact)
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS messages (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  nom             VARCHAR(100) NOT NULL,
  email           VARCHAR(191) NOT NULL,
  sujet           VARCHAR(255),
  message         TEXT NOT NULL,
  lu              TINYINT(1) DEFAULT 0,
  date_envoi      DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- TABLE : disponibilites (dates bloquées)
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS disponibilites (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  type_ressource  ENUM('bien','voiture') NOT NULL,
  ressource_id    INT NOT NULL,
  date_debut      DATE NOT NULL,
  date_fin        DATE NOT NULL,
  raison          ENUM('reservation','maintenance','indisponible') DEFAULT 'reservation',
  INDEX idx_ressource (type_ressource, ressource_id, date_debut)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- DONNÉES DE TEST
-- ─────────────────────────────────────────

-- Admin par défaut (mot de passe: Nourdine1@)
INSERT INTO utilisateurs (prenom, nom, email, mot_de_passe, type_compte, statut, email_verifie)
VALUES ('Nourdine', 'Admin', 'nourdine@gmail.com', '$2y$12$oDPtPdBDKKPwkksSiyDdUuCQrfV80ShESIxp0e6rabqj9e5EG4d0W', 'admin', 'actif', 1);

-- Propriétaire de test (mot de passe: Test123!)
INSERT INTO utilisateurs (prenom, nom, email, telephone, mot_de_passe, type_compte, ville, statut, email_verifie)
VALUES 
('Karim', 'Benali', 'karim@test.ma', '0661234567', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'proprietaire', 'Meknès', 'actif', 1),
('Fatima', 'Zahra', 'fatima@test.ma', '0678901234', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'Fès', 'actif', 1);

-- Biens de test
INSERT INTO biens (proprietaire_id, titre, description, type_bien, adresse, ville, prix_nuit, surface, nb_chambres, nb_salles_bain, nb_personnes, wifi, parking, climatisation, cuisine, image_principale, note_moyenne, nb_avis)
VALUES 
(2, 'Magnifique Villa avec Piscine', 'Villa de luxe avec piscine privée, jardin et vue panoramique sur Meknès. Idéale pour familles ou groupes.', 'villa', 'Route de Fès, km 5', 'Meknès', 1200.00, 350, 5, 3, 12, 1, 1, 1, 1, '../image/villa.jpg', 4.80, 24),
(2, 'Appartement Moderne Centre-Ville', 'Appartement entièrement rénové au cœur de Meknès. Idéal pour séjour professionnel ou touristique.', 'appartement', 'Av. Hassan II, n°42', 'Meknès', 450.00, 90, 2, 1, 4, 1, 0, 1, 1, '../image/apparte.jpg', 4.60, 18),
(2, 'Villa Jardin et Terrasse', 'Belle villa avec grand jardin arborisé, terrasse couverte, et garage double. Quartier résidentiel calme.', 'villa', 'Quartier Hamria', 'Meknès', 900.00, 250, 4, 2, 8, 1, 1, 1, 1, '../image/maison.png', 4.50, 11);

-- Voitures de test
INSERT INTO voitures (proprietaire_id, marque, modele, annee, couleur, carburant, boite, nb_places, climatisation, gps, prix_jour, caution, ville, image_principale, note_moyenne, nb_avis, km)
VALUES 
(2, 'Volkswagen', 'Golf 8', 2023, 'Gris Métallisé', 'essence', 'automatique', 5, 1, 1, 500.00, 5000.00, 'Meknès', '../image/v1.jpg', 4.90, 15, 12000),
(2, 'Dacia', 'Duster', 2022, 'Blanc Nacré', 'diesel', 'manuelle', 5, 1, 0, 350.00, 3000.00, 'Meknès', '../image/dacia.jpg', 4.70, 22, 35000),
(2, 'Mercedes', 'Classe C', 2024, 'Noir Obsidien', 'hybride', 'automatique', 5, 1, 1, 800.00, 8000.00, 'Meknès', '../image/v2.jpg', 5.00, 8, 5000),
(2, 'Toyota', 'Yaris', 2021, 'Bleu Marine', 'essence', 'automatique', 5, 1, 0, 280.00, 2500.00, 'Meknès', '../image/v3.jpg', 4.60, 31, 48000);

-- Avis de test
INSERT INTO avis (auteur_id, type_cible, bien_id, note, commentaire)
VALUES
(3, 'bien', 1, 5, 'Villa absolument magnifique ! Piscine parfaite, très propre, hôte très accueillant. On reviendra !'),
(3, 'bien', 2, 4, 'Appartement bien situé et moderne. Tout était comme décrit. Très bon rapport qualité/prix.'),
(3, 'voiture', 1, 5, 'Volkswagen Golf impeccable, très confortable. Livraison ponctuelle. Je recommande à 100%.');
