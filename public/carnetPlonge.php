<!--Carnet de plongée :
- liste les plongées de l'utilisateur connecté (données de la BD)
- formulaire pour ajouter une plongée (site, date, prof. max, durée, temps au fond, température, visibilité, note /5, notes perso)
- liens vers les autres pages -->

<?php
session_start();

if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: connexion.php');
    exit;
}
$idUtilisateur = (int)$_SESSION['id_utilisateur'];

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

class PlongeeRepository {
    public function __construct(private PDO $pdo) {
    }

    public function lister(int $idUtilisateur): array {
        $stmt = $this->pdo->prepare(
            "SELECT p.*, s.Nom AS site
             FROM Plongee p
             JOIN Site_plongee s ON s.id = p.id_site_plongee
             WHERE p.id_utilisateur = :u
             ORDER BY p.date_plongee DESC"
        );
        $stmt->execute(['u' => $idUtilisateur]);
        return $stmt->fetchAll();
    }

    public function sites(): array {
        return $this->pdo->query("SELECT id, Nom FROM Site_plongee ORDER BY Nom")->fetchAll();
    }

    public function siteExiste(int $idSite): bool {
        $stmt = $this->pdo->prepare("SELECT 1 FROM Site_plongee WHERE id = :id");
        $stmt->execute(['id' => $idSite]);
        return (bool)$stmt->fetchColumn();
    }

    public function ajouter(array $d, int $idUtilisateur): void {
        $stmt = $this->pdo->prepare(
            "INSERT INTO Plongee (date_plongee, profondeur_max, duree, temps_fond, temperature,
                                  visibilite, notes_personnelles, notation, id_site_plongee, id_utilisateur)
             VALUES (:date, :prof, :duree, :fond, :temp, :visi, :notes, :note, :site, :u)"
        );
        $stmt->execute($d + ['u' => $idUtilisateur]);
    }
}

$repo = new PlongeeRepository($pdo);
$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $site = (int)($_POST['site'] ?? 0);

    if (!$repo->siteExiste($site)) {
        $erreurs[] = "Site de plongée invalide.";
    } else {
        $repo->ajouter([
            'date'  => $_POST['date'] ?? '',
            'prof'  => (int)($_POST['prof'] ?? 0),
            'duree' => $_POST['duree'] ?? '00:00:00',
            'fond'  => $_POST['fond'] ?? '00:00:00',
            'temp'  => (float)($_POST['temp'] ?? 0),
            'visi'  => (float)($_POST['visi'] ?? 0),
            'notes' => trim(strip_tags($_POST['notes'] ?? '')),
            'note'  => max(0, min(5, (int)($_POST['note'] ?? 0))),
            'site'  => $site,
        ], $idUtilisateur);
        header('Location: carnetPlonge.php');
        exit;
    }
}

$plongees = $repo->lister($idUtilisateur);
$afficherForm = isset($_GET['ajouter']) || !empty($erreurs);
$sites = $afficherForm ? $repo->sites() : [];
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carnet de plongée</title>
</head>

<body>
    <a href="index.php">Accueil</a>
    <a href="especeFav.php">Espèces favorites</a>
    <a href="connexion.php?deconnexion=1">Se déconnecter</a>
    <h1>Mon carnet de plongée</h1>

    <?php foreach ($erreurs as $e) { ?>
        <p style="color:red"><?= htmlspecialchars($e) ?></p>
    <?php } ?>

    <?php if (!$afficherForm) { ?>
        <a href="carnetPlonge.php?ajouter=1"><button>Ajouter une plongée</button></a>
    <?php } else { ?>
        <h2>Nouvelle plongée</h2>
        <form method="post">
            <label>Site
                <select name="site" required>
                    <?php foreach ($sites as $s) { ?>
                        <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['Nom']) ?></option>
                    <?php } ?>
                </select>
            </label><br>
            <label>Date <input type="date" name="date" required></label><br>
            <label>Profondeur max (m) <input type="number" name="prof" min="0" required></label><br>
            <label>Durée <input type="time" name="duree" step="1" required></label><br>
            <label>Temps au fond <input type="time" name="fond" step="1" required></label><br>
            <label>Température (°C) <input type="number" step="0.1" name="temp" required></label><br>
            <label>Visibilité (m) <input type="number" step="0.1" name="visi" required></label><br>
            <label>Note /5 <input type="number" name="note" min="0" max="5" required></label><br>
            <label>Notes <textarea name="notes" maxlength="500"></textarea></label><br>
            <button type="submit">Enregistrer</button>
            <a href="carnetPlonge.php">Annuler</a>
        </form>
    <?php } ?>

    <table border="1">
        <tr>
            <th>Site</th>
            <th>Date</th>
            <th>Prof. max (m)</th>
            <th>Durée</th>
            <th>Temps au fond</th>
            <th>Temp. (°C)</th>
            <th>Visibilité (m)</th>
            <th>Note /5</th>
            <th>Notes</th>
        </tr>
        <?php foreach ($plongees as $p) { ?>
            <tr>
                <td><?= htmlspecialchars($p['site']) ?></td>
                <td><?= htmlspecialchars($p['date_plongee']) ?></td>
                <td><?= htmlspecialchars($p['profondeur_max']) ?></td>
                <td><?= htmlspecialchars($p['duree']) ?></td>
                <td><?= htmlspecialchars($p['temps_fond']) ?></td>
                <td><?= htmlspecialchars($p['temperature']) ?></td>
                <td><?= htmlspecialchars($p['visibilite']) ?></td>
                <td><?= htmlspecialchars($p['notation']) ?>/5</td>
                <td><?= htmlspecialchars($p['notes_personnelles'] ?? '') ?></td>
            </tr>
        <?php } ?>
    </table>
</body>

</html>
