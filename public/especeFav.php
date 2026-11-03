    <!--Liste d'espece favorite
    on peut ajouter ou supprimer une espece des favorit
    Récupèrer dans la base de donnée -->
    <?php
    const DATABASE_CONFIGURATION_FILE = __DIR__ . '/../config/database.ini'; //connexion à la BD

    $config = parse_ini_file(DATABASE_CONFIGURATION_FILE, true);

    if (!$config) {
        throw new Exception("Erreur lors de la lecture du fichier de configuration : " . DATABASE_CONFIGURATION_FILE);
    }

    $host = $config['host'];
    $port = $config['port'];
    $database = $config['database'];
    $username = $config['username'];
    $password = $config['password'];

    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    class FavoriRepository {
        public function __construct(private PDO $pdo) {
        }

        public function lister(int $idUtilisateur): array {
            $stmt = $this->pdo->prepare(
                "SELECT e.id, e.Nom_commun, e.Nom_scientifique
             FROM Favori f JOIN Especes e ON e.id = f.id_especes
             WHERE f.id_utilisateur = :u"
            );
            $stmt->execute(['u' => $idUtilisateur]);
            return $stmt->fetchAll();
        }

        public function rechercher(string $terme): array {
            $stmt = $this->pdo->prepare(
                "SELECT id, Nom_commun, Nom_scientifique FROM Especes
             WHERE Nom_commun LIKE :t1 OR Nom_scientifique LIKE :t2"
            );
            $stmt->execute(['t1' => "%$terme%", 't2' => "%$terme%"]);
            return $stmt->fetchAll();
        }

        public function ajouter(int $idUtilisateur, int $idEspece): void {
            $stmt = $this->pdo->prepare(
                "INSERT INTO Favori (id_utilisateur, id_especes)
             SELECT :u, :e FROM DUAL
             WHERE NOT EXISTS (SELECT 1 FROM Favori WHERE id_utilisateur = :u2 AND id_especes = :e2)"
            );
            $stmt->execute(['u' => $idUtilisateur, 'e' => $idEspece, 'u2' => $idUtilisateur, 'e2' => $idEspece]);
        }

        public function supprimer(int $idUtilisateur, int $idEspece): void {
            $stmt = $this->pdo->prepare("DELETE FROM Favori WHERE id_utilisateur = :u AND id_especes = :e");
            $stmt->execute(['u' => $idUtilisateur, 'e' => $idEspece]);
        }
    }

    $idUtilisateur = 1; // temporaire, remplacé par l'utilisateur connecté plus tard
    $repo = new FavoriRepository($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $idEspece = (int)($_POST['id_espece'] ?? 0);
        if (($_POST['action'] ?? '') === 'supprimer') {
            $repo->supprimer($idUtilisateur, $idEspece);
        } elseif (($_POST['action'] ?? '') === 'ajouter') {
            $repo->ajouter($idUtilisateur, $idEspece);
        }
        header('Location: especeFav.php');
        exit;
    }

    $favoris = $repo->lister($idUtilisateur);
    $terme = trim(strip_tags($_GET['recherche'] ?? ''));
    $resultats = $terme !== '' ? $repo->rechercher($terme) : [];
    ?>

    <!DOCTYPE html>
    <html lang="fr">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Espèces favorites</title>
    </head>

    <body>
        <a href="index.php">Accueil</a>
        <h1>Mes espèces favorites</h1>

        <ul>
            <?php foreach ($favoris as $e) { ?>
                <li>
                    <?= htmlspecialchars($e['Nom_commun']) ?>
                    (<em><?= htmlspecialchars($e['Nom_scientifique']) ?></em>)
                    <form method="post" style="display:inline">
                        <input type="hidden" name="action" value="supprimer">
                        <input type="hidden" name="id_espece" value="<?= (int)$e['id'] ?>">
                        <button type="submit">Supprimer</button>
                    </form>
                </li>
            <?php } ?>
        </ul>

        <h2>Ajouter un favori</h2>
        <form method="get">
            <input type="text" name="recherche" value="<?= htmlspecialchars($terme) ?>">
            <button type="submit">Rechercher</button>
        </form>

        <ul>
            <?php foreach ($resultats as $e) { ?>
                <li>
                    <?= htmlspecialchars($e['Nom_commun']) ?>
                    <form method="post" style="display:inline">
                        <input type="hidden" name="action" value="ajouter">
                        <input type="hidden" name="id_espece" value="<?= (int)$e['id'] ?>">
                        <button type="submit">Ajouter</button>
                    </form>
                </li>
            <?php } ?>
        </ul>
    </body>

    </html>
