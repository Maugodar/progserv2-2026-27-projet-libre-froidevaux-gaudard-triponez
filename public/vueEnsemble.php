<?php
/*
 * Page "Vue d'ensemble" : résumé chiffré de l'activité de l'utilisateur.
 * - Nombre de plongées, d'observations, d'espèces observées, de favoris et d'espèces au catalogue.
 * Données calculées depuis la base (tables Plongee, Observation, Favori, Especes).
 * POO : classe VueEnsemble qui regroupe les statistiques d'un utilisateur.
 */
session_start();

if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: connexion.php');
    exit;
}
$idUtilisateur = (int)$_SESSION['id_utilisateur']; //récupère l'id de l'utilisateur connecter

require_once __DIR__ . '/Database.php';

class VueEnsemble {
    public function __construct(private PDO $pdo, private int $idUtilisateur) {
    }

    private function compter(string $sql): int {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['u' => $this->idUtilisateur]);
        return (int)$stmt->fetchColumn();
    }

    public function nbPlongees(): int {
        return $this->compter("SELECT COUNT(*) FROM Plongee WHERE id_utilisateur = :u");
    }

    public function nbObservations(): int {
        return $this->compter("SELECT COUNT(*) FROM Observation WHERE id_utilisateur = :u");
    }

    public function nbEspecesObservees(): int {
        return $this->compter("SELECT COUNT(DISTINCT id_especes) FROM Observation WHERE id_utilisateur = :u");
    }

    public function nbFavoris(): int {
        return $this->compter("SELECT COUNT(*) FROM Favori WHERE id_utilisateur = :u");
    }

    public function nbEspeces(): int {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM Especes")->fetchColumn();
    }
}

$vue = new VueEnsemble(Database::getConnexion(), $idUtilisateur);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vue d'ensemble</title>
</head>

<body>
    <a href="index.php">Accueil</a>
    <a href="connexion.php?deconnexion=1">Se déconnecter</a>
    <h1>Vue d'ensemble</h1>

    <ul>
        <li>Plongées : <?= $vue->nbPlongees() ?></li>
        <li>Observations : <?= $vue->nbObservations() ?></li>
        <li>Espèces observées : <?= $vue->nbEspecesObservees() ?> sur <?= $vue->nbEspeces() ?></li>
        <li>Espèces favorites : <?= $vue->nbFavoris() ?></li>
    </ul>
</body>

</html>
