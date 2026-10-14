USE my_database; 


CREATE TABLE Especes(
    id INTEGER PRIMARY KEY,
    Nom_commun TEXT, 
    Nom_scientifique VARCHAR(80), 
    Famille VARCHAR(60), 
    Description VARCHAR(100),
    Enjeux VARCHAR(100),
    Habitat VARCHAR(100),
    Profondeur INTEGER,
    Image VARCHAR(300),
    Type_especes VARCHAR(50)
)
