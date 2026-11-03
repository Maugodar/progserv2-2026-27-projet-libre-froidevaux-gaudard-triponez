<?php
/*
 * Page "Espèces" : catalogue des espèces (coraux, mammifères marins, poissons).
 * - Liste les espèces avec recherche par nom et filtre par type.
 * - ?id=X affiche la fiche détaillée d'une espèce.
 * Données lues dans la base (tables Especes, Coraux, Mammiferes, Poisson).
 * POO : héritage (Espece -> Corail / Mammifere / Poisson) et repository.
 */
require_once __DIR__ . '/Database.php';

abstract class Espece {
    protected const CHAMPS = []; // libellé affiché => colonne SQL propre au type

    public function __construct(private array $ligne) {
    }

    public function get(string $colonne): string {
        return (string)($this->ligne[$colonne] ?? '');
    }

    /** Caractéristiques propres au type d'espèce (non vides uniquement). */
    public function caracteristiques(): array {
        $res = [];
        foreach (static::CHAMPS as $libelle => $colonne) {
            if ($this->get($colonne) !== '') {
                $res[$libelle] = $this->get($colonne);
            }
        }
        return $res;
    }
}

class Corail extends Espece {
    protected const CHAMPS = ['Type de squelette' => 'type_squelette', 'Forme' => 'forme', 'Couleur' => 'couleur'];
}

class Mammifere extends Espece {
    protected const CHAMPS = ['Taille moyenne (m)' => 'taille_moyenne', 'Régime' => 'regime_mammifere', 'Statut de conservation' => 'statut_conservation'];
}

class Poisson extends Espece {
    protected const CHAMPS = ['Taille max (m)' => 'taille_max', 'Milieu' => 'milieu', 'Régime' => 'regime_poisson'];
}

class EspecesRepository {
    public const TYPES = ['corail' => 'Corail', 'mammifère' => 'Mammifère marin', 'poisson' => 'Poisson'];

    private const CLASSES = [
        'corail' => Corail::class, 'coraux' => Corail::class,
        'mammifère' => Mammifere::class, 'mammifere' => Mammifere::class,
        'poisson' => Poisson::class, 'poissons' => Poisson::class,
    ];

    private const SELECT = "SELECT e.*,
            c.type_squelette, c.forme, c.couleur,
            m.taille_moyenne, m.regime AS regime_mammifere, m.statut_conservation,
            p.taille_max, p.milieu, p.regime AS regime_poisson
        FROM Especes e
        LEFT JOIN Coraux c ON c.id_especes = e.id
        LEFT JOIN Mammiferes m ON m.id_especes = e.id
        LEFT JOIN Poisson p ON p.id_especes = e.id";

    public function __construct(private PDO $pdo) {
    }

    /** Crée Corail, Mammifere ou Poisson selon la colonne Type_especes. */
    private function creer(array $ligne): Espece {
        $classe = self::CLASSES[mb_strtolower(trim($ligne['Type_especes']))];
        return new $classe($ligne);
    }

    /** @return Espece[] */
    public function lister(string $type = '', string $terme = ''): array {
        $stmt = $this->pdo->prepare(
            self::SELECT . " WHERE (:type = '' OR e.Type_especes = :type2)
                AND (e.Nom_commun LIKE :t1 OR e.Nom_scientifique LIKE :t2)
             ORDER BY e.Nom_commun"
        );
        $stmt->execute(['type' => $type, 'type2' => $type, 't1' => "%$terme%", 't2' => "%$terme%"]);
        return array_map(fn($ligne) => $this->creer($ligne), $stmt->fetchAll());
    }

    public function trouver(int $id): ?Espece {
        $stmt = $this->pdo->prepare(self::SELECT . " WHERE e.id = :id");
        $stmt->execute(['id' => $id]);
        $ligne = $stmt->fetch();
        return $ligne ? $this->creer($ligne) : null;
    }
}

$repo = new EspecesRepository(Database::getConnexion());

$terme = trim(strip_tags($_GET['recherche'] ?? ''));
$type = (string)($_GET['type'] ?? '');
$idDetail = (int)($_GET['id'] ?? 0);

$espece = $idDetail > 0 ? $repo->trouver($idDetail) : null;
$especes = $espece === null ? $repo->lister($type, $terme) : [];
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espèces</title>
</head>

<body>
    <a href="index.php">Accueil</a>

    <?php if ($espece !== null) { ?>
        <a href="especes.php">Retour à la liste</a>
        <h1><?= htmlspecialchars($espece->get('Nom_commun')) ?></h1>
        <p><em><?= htmlspecialchars($espece->get('Nom_scientifique')) ?></em> (<?= htmlspecialchars($espece->get('Type_especes')) ?>)</p>

        <ul>
            <li>Famille : <?= htmlspecialchars($espece->get('Famille')) ?></li>
            <li>Habitat : <?= htmlspecialchars($espece->get('Habitat')) ?></li>
            <li>Profondeur : <?= htmlspecialchars($espece->get('Profondeur')) ?></li>
            <?php foreach ($espece->caracteristiques() as $libelle => $valeur) { ?>
                <li><?= htmlspecialchars($libelle) ?> : <?= htmlspecialchars($valeur) ?></li>
            <?php } ?>
        </ul>

        <h2>Description</h2>
        <p><?= htmlspecialchars($espece->get('Description')) ?></p>

        <h2>Enjeux</h2>
        <p><?= htmlspecialchars($espece->get('Enjeux')) ?></p>
    <?php } else { ?>
        <h1>Espèces</h1>

        <?php if ($idDetail > 0) { ?>
            <p>Espèce introuvable.</p>
        <?php } ?>

        <form method="get">
            <input type="text" name="recherche" value="<?= htmlspecialchars($terme) ?>">
            <select name="type">
                <option value="">Tous les types</option>
                <?php foreach (EspecesRepository::TYPES as $valeur => $libelle) { ?>
                    <option value="<?= htmlspecialchars($valeur) ?>" <?= $type === $valeur ? 'selected' : '' ?>>
                        <?= htmlspecialchars($libelle) ?>
                    </option>
                <?php } ?>
            </select>
            <button type="submit">Rechercher</button>
        </form>

        <p><?= count($especes) ?> espèce(s)</p>

        <ul>
            <?php foreach ($especes as $e) { ?>
                <li>
                    <a href="especes.php?id=<?= (int)$e->get('id') ?>"><?= htmlspecialchars($e->get('Nom_commun')) ?></a>
                    (<em><?= htmlspecialchars($e->get('Nom_scientifique')) ?></em>)
                    - <?= htmlspecialchars($e->get('Type_especes')) ?>
                </li>
            <?php } ?>
        </ul>
    <?php } ?>
</body>

</html>
