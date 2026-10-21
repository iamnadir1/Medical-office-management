
CREATE DATABASE IF NOT EXISTS cabinet_medical CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cabinet_medical;

CREATE TABLE IF NOT EXISTS utilisateur(
  id_utilisateur INT PRIMARY KEY AUTO_INCREMENT,
  nom VARCHAR(50) NOT NULL,
  prenom VARCHAR(50) NOT NULL,
  login VARCHAR(50) UNIQUE NOT NULL,
  mot_de_passe VARCHAR(255) NOT NULL,   
  role VARCHAR(15) NOT NULL DEFAULT 'secretary'  -- admin | doctor | secretary
);

CREATE TABLE IF NOT EXISTS medicament(
  id_medicament INT PRIMARY KEY AUTO_INCREMENT,
  nom VARCHAR(100) NOT NULL,
  dosage VARCHAR(50),
  forme VARCHAR(50)
);


CREATE TABLE IF NOT EXISTS patient(
  id_patient INT PRIMARY KEY AUTO_INCREMENT,
  nom VARCHAR(50) NOT NULL,
  prenom VARCHAR(50) NOT NULL,
  date_naissance DATE,
  adress VARCHAR(150),
  telephone VARCHAR(20),
  groupe_sanguin VARCHAR(4),
  allergies VARCHAR(255),
  antecedents VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS rendezvous(
  id_rdv INT PRIMARY KEY AUTO_INCREMENT,
  date_heur DATETIME NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'Scheduled',
  id_patient INT NOT NULL,
  FOREIGN KEY(id_patient) REFERENCES patient(id_patient) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS consultation(
  id_consultation INT PRIMARY KEY AUTO_INCREMENT,
  date_consultation DATE NOT NULL,
  diagnostic TEXT,
  id_patient INT NOT NULL,
  id_rdv INT NULL,
  FOREIGN KEY (id_patient) REFERENCES patient(id_patient) ON DELETE CASCADE,
  FOREIGN KEY (id_rdv) REFERENCES rendezvous(id_rdv) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS ordonnance(
  id_ordonnance INT PRIMARY KEY AUTO_INCREMENT,
  date_emission DATE NOT NULL,
  duree_traitement INT,
  id_consultation INT NOT NULL,
  FOREIGN KEY (id_consultation) REFERENCES consultation(id_consultation) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS certificat(
  id_certificat INT PRIMARY KEY AUTO_INCREMENT,
  type_certificat VARCHAR(30) NOT NULL,
  date_emission DATE NOT NULL,
  contenu TEXT,
  date_debut DATE NULL,
  date_fin DATE NULL,
  id_patient INT NOT NULL,
  FOREIGN KEY(id_patient) REFERENCES patient(id_patient) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS historique(
  id_historique INT PRIMARY KEY AUTO_INCREMENT,
  date_modification DATE,
  champ_modifie VARCHAR(50),
  ancienne_valeur TEXT,
  nouvelle_valeur TEXT,
  id_patient INT NOT NULL,
  FOREIGN KEY (id_patient) REFERENCES patient(id_patient) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS ordonnance_medicament(
  id INT PRIMARY KEY AUTO_INCREMENT,
  id_ordonnance INT NOT NULL,
  id_medicament INT NOT NULL,
  posologie VARCHAR(150),
  FOREIGN KEY (id_ordonnance) REFERENCES ordonnance(id_ordonnance) ON DELETE CASCADE,
  FOREIGN KEY (id_medicament) REFERENCES medicament(id_medicament) ON DELETE CASCADE
);

INSERT INTO utilisateur (nom, prenom, login, mot_de_passe, role)
VALUES ('Halladj', 'Nadir', 'admin', 'admin123', 'admin');
