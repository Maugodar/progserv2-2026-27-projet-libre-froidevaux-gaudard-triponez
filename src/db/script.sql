-- Active: 1780319547267@@127.0.0.1@3306

CREATE TABLE Especes(
    id INTEGER PRIMARY KEY,
    Nom_commun TEXT, 
    Nom_scientifique VARCHAR(80), 
    Famille VARCHAR(60), 
    Description VARCHAR(500),
    Enjeux VARCHAR(500),
    Habitat VARCHAR(200),
    Profondeur INTEGER,
    Image VARCHAR(300),
    Type_especes VARCHAR(50)
);

CREATE TABLE Personne (
    id INTEGER PRIMARY KEY, 
    Nom VARCHAR(80),
    Prenom VARCHAR (80), 
    Email VARCHAR(60),
    mot_de_passe VARCHAR(100)
);

CREATE TABLE Administrateur (
    id INTEGER PRIMARY KEY,
    id_personne INTEGER REFERENCES Personne(id)
);
 
CREATE TABLE Utilisateur (
    id INTEGER PRIMARY KEY,
    id_personne INTEGER REFERENCES Personne(id)
);
 
CREATE TABLE Association (
    id INTEGER PRIMARY KEY,
    Nom VARCHAR(80),
    Description VARCHAR(500),
    image VARCHAR(300),
    site_web VARCHAR(200),
    id_administrateur INTEGER REFERENCES Administrateur(id)
);
 
CREATE TABLE Site_plongee (
    id INTEGER PRIMARY KEY,
    Nom VARCHAR(80),
    mer_region VARCHAR(80),
    pays VARCHAR(80),
    latitude DECIMAL(9,6),
    longitude DECIMAL(9,6)
);
 
CREATE TABLE Plongee (
    id INTEGER PRIMARY KEY,
    date_plongee DATE,
    profondeur_max INTEGER,
    duree TIME,
    temps_fond TIME,
    temperature FLOAT,
    visibilite FLOAT,
    notes_personnelles VARCHAR(500),
    notation INTEGER,
    id_site_plongee INTEGER REFERENCES Site_plongee(id),
    id_utilisateur INTEGER REFERENCES Utilisateur(id)
);
 
CREATE TABLE Observation (
    id INTEGER PRIMARY KEY,
    date_observation DATE,
    lieu_observation VARCHAR(80),
    id_utilisateur INTEGER REFERENCES Utilisateur(id),
    id_especes INTEGER REFERENCES Especes(id),
    id_plongee INTEGER REFERENCES Plongee(id)
);
 
CREATE TABLE Favori (
    id INTEGER PRIMARY KEY,
    id_utilisateur INTEGER REFERENCES Utilisateur(id),
    id_especes INTEGER REFERENCES Especes(id)
);
 
/*CREATE TABLE Point_carte (
    id INTEGER PRIMARY KEY,
    titre VARCHAR(80),
    description VARCHAR(500),
    longitude GEOMETRY(Point, 4326),
    latitude GEOMETRY(Point, 4326),
    id_utilisateur INTEGER REFERENCES Utilisateur(id),
    id_site_plongee INTEGER REFERENCES Site_plongee(id)
);
*/
CREATE TABLE Point_carte (
    id INTEGER PRIMARY KEY,
    titre VARCHAR(80),
    description VARCHAR(500),
    latitude DECIMAL(9,6),
    longitude DECIMAL(9,6),
    id_utilisateur INTEGER REFERENCES Utilisateur(id),
    id_site_plongee INTEGER REFERENCES Site_plongee(id)
);

CREATE TABLE Poisson (
    id INTEGER PRIMARY KEY, 
    taille_max FLOAT,
    milieu VARCHAR(50),
    regime VARCHAR(100),
    id_poissons INTEGER REFERENCES Poisson(id),
    id_especes INTEGER REFERENCES Especes(id)
);

CREATE TABLE Mammiferes (
    id INTEGER PRIMARY KEY, 
    taille_moyenne FLOAT,
    regime VARCHAR(100),
    statut_conservation VARCHAR(60),
    id_poissons INTEGER REFERENCES Poisson(id),
    id_especes INTEGER REFERENCES Especes(id)
);

CREATE TABLE Coraux (
    id INTEGER PRIMARY KEY, 
    type_squelette VARCHAR(300),
    forme VARCHAR(100),
    couleur VARCHAR(50),
    id_poissons INTEGER REFERENCES Poisson(id),
    id_especes INTEGER REFERENCES Especes(id)
);
