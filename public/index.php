<?php
const DATABASE_CONFIGURATION_FILE = __DIR__ . '/../config/database.ini';

$config = parse_ini_file(DATABASE_CONFIGURATION_FILE, true);

if (!$config) {
    throw new Exception("Erreur lors de la lecture du fichier de configuration : " . DATABASE_CONFIGURATION_FILE);
}

$host = $config['host'];
$port = $config['port'];
$database = $config['database'];
$username = $config['username'];
$password = $config['password'];


$pdo = new PDO("mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4", $username, $password);

$sql = "SELECT * FROM Especes";

$stmt = $pdo->prepare($sql);

$stmt->execute();

$results = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProgServ2 Course PHP Template</title>
</head>

<body>
    <h1>ProgServ2 Course PHP Template</h1>

    <p>Bienvenue dans votre projet PHP pour le cours ProgServ2 à la HEIG-VD !</p>

    <h2>Résultats de la base de données :</h2>

    <ul>
        <?php foreach ($results as $row) { ?>
            <li>ID: <?= htmlspecialchars($row['id']) ?>
                <ul>
                    li>Nom commun : <?= htmlspecialchars($row['Nom_commun']) ?>
            </li>
            <li>Nom scientifique : <?= htmlspecialchars($row['Nom_scientifique']) ?></li>
    </ul>
    </li>
<?php } ?>
</ul>
</body>

</html>
