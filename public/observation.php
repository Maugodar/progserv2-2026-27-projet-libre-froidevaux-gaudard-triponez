<?php
/*
 * Page "Observations" : espèces observées par l'utilisateur pendant ses plongées.
 * - Liste les observations, formulaire pour en ajouter, bouton pour en supprimer.
 * Données lues et écrites dans la table Observation.
 * POO : classe Observation + ObservationRepository.
 */
session_start();
require_once __DIR__ . '/Database.php';

class Observation {
    public function __construct(
        public int $id,
        public string $date,
        public string $lieu,
        public string $espece,
        public string $site,
    ) {
    }
}

class ObservationRepository {
    public function __construct(private PDO $pdo) {
        
    }

    public function lister([int $_SESSION['id_utilisateur']]): array {
        $stmt = $this->pdo->prepare(
            "SELECT o.id, o.date_observation, o.lieu_observation, e.Nom_commun, s.Nom AS site
             FROM Observation o
             JOIN Especes e ON e.id = o.id_especes
             JOIN Plongee p ON p.id = o.id_plongee
             JOIN Site_plongee s ON s.id = p.id_site_plongee
             WHERE o.id_utilisateur = :u
             ORDER BY o.date_observation DESC"
        );
        $stmt->execute(['u' => $_SESSION['id_utilisateur']]);

        $observations = [];
        foreach ($stmt->fetchAll() as $l) {
            $observations[] = new Observation($l['id'], $l['date_observation'], $l['lieu_observation'] ?? '', $l['Nom_commun'], $l['site']);
        }
        return $observations;
    }

    public function especes(): array {
        return $this->pdo->query("SELECT id, Nom_commun FROM Especes ORDER BY Nom_commun")->fetchAll();
    }

    public function plongees(int $_SESSION['id_utilisateur']): array {
        $stmt = $this->pdo->prepare(
            "SELECT p.id, p.date_plongee, s.Nom AS site
             FROM Plongee p JOIN Site_plongee s ON s.id = p.id_site_plongee
             WHERE p.id_utilisateur = :u ORDER BY p.date_plongee DESC"
        );
        $stmt->execute(['u' => $_SESSION['id_utilisateur']]);
        return $stmt->fetchAll();
    }

    /* AVANT Sécurité
    public function ajouter(string $date, string $lieu, int $idUtilisateur, int $idEspece, int $idPlongee): void {
        $stmt = $this->pdo->prepare(
            "INSERT INTO Observation (date_observation, lieu_observation, id_utilisateur, id_especes, id_plongee)
             VALUES (:d, :l, :u, :e, :p)"
        );
        $stmt->execute(['d' => $date, 'l' => $lieu, 'u' => $idUtilisateur, 'e' => $idEspece, 'p' => $idPlongee]);
    }  APRèS*/
        public function ajouter(string $date, string $lieu, int $_SESSION['id_utilisateur'], int $idEspece, int $idPlongee): void {
                $check = $this->pdo->prepare("SELECT 1 FROM Plongee WHERE id = :p AND id_utilisateur = :u");
                $check->execute(['p' => $idPlongee, 'u' => $_SESSION['id_utilisateur']]);
            if (!$check->fetchColumn()) {
                return; // plongée inexistante ou d'un autre utilisateur
        }

        $stmt = $this->pdo->prepare(
            "INSERT INTO Observation (date_observation, lieu_observation, id_utilisateur, id_especes, id_plongee)
             VALUES (:d, :l, :u, :e, :p)"
        );
        $stmt->execute(['d' => $date, 'l' => $lieu, 'u' => $_SESSION['id_utilisateur'], 'e' => $idEspece, 'p' => $idPlongee]);
    }

    public function supprimer(int $id, int $_SESSION['id_utilisateur']): void {
        $stmt = $this->pdo->prepare("DELETE FROM Observation WHERE id = :id AND id_utilisateur = :u");
        $stmt->execute(['id' => $id, 'u' => $_SESSION['id_utilisateur']]);
    }
}

$idUtilisateur = 1; // temporaire, remplacé par l'utilisateur connecté plus tard
$repo = new ObservationRepository(Database::getConnexion());

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'supprimer') {
        $repo->supprimer((int)($_POST['id'] ?? 0), $_SESSION['id_utilisateur']);
        } else {
        $date = is_string($_POST['date'] ?? null) ? $_POST['date'] : '';
        $d = DateTime::createFromFormat('Y-m-d', $date);
        $dateValide = $d && $d->format('Y-m-d') === $date; // date réelle au format aaaa-mm-jj
        $lieu = is_string($_POST['lieu'] ?? null) ? mb_substr(trim(strip_tags($_POST['lieu'])), 0, 80) : ''; // nettoyé et limité à 80 caractères

        if ($dateValide) {
            $repo->ajouter($date, $lieu, $_SESSION['id_utilisateur'], (int)($_POST['espece'] ?? 0), (int)($_POST['plongee'] ?? 0));
        }
    }
    header('Location: observation.php');
    exit;
}

$observations = $repo->lister($_SESSION['id_utilisateur']);
$especes = $repo->especes();
$plongees = $repo->plongees($_SESSION['id_utilisateur']);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Observations</title>
</head>

<body>
    <a href="index.php">Accueil</a>
    <a href="connexion.php?deconnexion=1">Se déconnecter</a>
    <h1>Mes observations</h1>

    <h2>Nouvelle observation</h2>
    <form method="post">
        <label>Plongée
            <select name="plongee" required>
                <?php foreach ($plongees as $p) { ?>
                    <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['date_plongee']) ?> - <?= htmlspecialchars($p['site']) ?></option>
                <?php } ?>
            </select>
        </label><br>
        <label>Espèce
            <select name="espece" required>
                <?php foreach ($especes as $e) { ?>
                    <option value="<?= (int)$e['id'] ?>"><?= htmlspecialchars($e['Nom_commun']) ?></option>
                <?php } ?>
            </select>
        </label><br>
        <label>Date <input type="date" name="date" required></label><br>
        <label>Lieu <input type="text" name="lieu" maxlength="80"></label><br>
        <button type="submit">Enregistrer</button>
    </form>

    <table border="1">
        <tr>
            <th>Date</th>
            <th>Espèce</th>
            <th>Site</th>
            <th>Lieu</th>
            <th></th>
        </tr>
        <?php foreach ($observations as $o) { ?>
            <tr>
                <td><?= htmlspecialchars($o->date) ?></td>
                <td><?= htmlspecialchars($o->espece) ?></td>
                <td><?= htmlspecialchars($o->site) ?></td>
                <td><?= htmlspecialchars($o->lieu) ?></td>
                <td>
                    <form method="post">
                        <input type="hidden" name="action" value="supprimer">
                        <input type="hidden" name="id" value="<?= (int)$o->id ?>">
                        <button type="submit">Supprimer</button>
                    </form>
                </td>
            </tr>
        <?php } ?>
    </table>
</body>

</html>
