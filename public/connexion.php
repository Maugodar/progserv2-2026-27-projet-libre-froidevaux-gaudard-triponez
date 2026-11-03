<!--Formulaire de connexion
demande adresse e-mail et mdp
si on a pas de compte on doit en crée un
A voir comment liée ça à la base de donnée -->
<?php
session_start();

const DATABASE_CONFIGURATION_FILE = __DIR__ . '/../config/database.ini'; //connexion a la BD

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

class Validateur {
    public static function nettoyer(string $valeur): string {
        return trim(strip_tags($valeur));
    }

    public static function emailValide(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function motDePasseValide(string $mdp): bool {
        return strlen($mdp) >= 8;
    }
}

class UtilisateurRepository {
    public function __construct(private PDO $pdo) {
    }

    public function emailExiste(string $email): bool {
        $stmt = $this->pdo->prepare("SELECT 1 FROM Personne WHERE Email = :e");
        $stmt->execute(['e' => $email]);
        return (bool)$stmt->fetchColumn();
    }

    public function inscrire(string $nom, string $prenom, string $email, string $mdp): int {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO Personne (Nom, Prenom, Email, mot_de_passe) VALUES (:n, :p, :e, :m)"
            );
            $stmt->execute([
                'n' => $nom,
                'p' => $prenom,
                'e' => $email,
                'm' => password_hash($mdp, PASSWORD_DEFAULT),
            ]);
            $idPersonne = (int)$this->pdo->lastInsertId();

            $stmt = $this->pdo->prepare(
                "INSERT INTO Utilisateur (niveau_plongee, date_inscription, id_personne) VALUES (1, :d, :p)"
            );
            $stmt->execute(['d' => date('Y-m-d'), 'p' => $idPersonne]);
            $idUtilisateur = (int)$this->pdo->lastInsertId();

            $this->pdo->commit();
            return $idUtilisateur;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /** Retourne l'id de l'utilisateur si les identifiants sont corrects, sinon null. */
    public function connecter(string $email, string $mdp): ?int {
        $stmt = $this->pdo->prepare(
            "SELECT p.mot_de_passe, u.id AS id_utilisateur
             FROM Personne p
             JOIN Utilisateur u ON u.id_personne = p.id
             WHERE p.Email = :e"
        );
        $stmt->execute(['e' => $email]);
        $ligne = $stmt->fetch();

        if ($ligne && password_verify($mdp, $ligne['mot_de_passe'])) {
            return (int)$ligne['id_utilisateur'];
        }
        return null;
    }
}

$repo = new UtilisateurRepository($pdo);
$erreurs = [];
$message = '';

if (isset($_GET['deconnexion'])) {
    session_destroy();
    header('Location: connexion.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $email = Validateur::nettoyer($_POST['email'] ?? '');
    $mdp = $_POST['mot_de_passe'] ?? '';

    if (!Validateur::emailValide($email)) {
        $erreurs[] = "Adresse e-mail invalide.";
    }
    if (!Validateur::motDePasseValide($mdp)) {
        $erreurs[] = "Le mot de passe doit contenir au moins 8 caractères.";
    }

    if ($action === 'inscription') {
        $nom = Validateur::nettoyer($_POST['nom'] ?? '');
        $prenom = Validateur::nettoyer($_POST['prenom'] ?? '');
        if ($nom === '' || $prenom === '') {
            $erreurs[] = "Nom et prénom obligatoires.";
        }
        if (empty($erreurs) && $repo->emailExiste($email)) {
            $erreurs[] = "Cette adresse e-mail est déjà utilisée.";
        }
        if (empty($erreurs)) {
            $id = $repo->inscrire($nom, $prenom, $email, $mdp);
            session_regenerate_id(true);
            $_SESSION['id_utilisateur'] = $id;
            header('Location: carnetPlonge.php');
            exit;
        }
    } elseif ($action === 'connexion' && empty($erreurs)) {
        $id = $repo->connecter($email, $mdp);
        if ($id !== null) {
            session_regenerate_id(true);
            $_SESSION['id_utilisateur'] = $id;
            header('Location: carnetPlonge.php');
            exit;
        }
        $erreurs[] = "E-mail ou mot de passe incorrect.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
</head>

<body>
    <a href="index.php">Accueil</a>
    <?php if (isset($_SESSION['id_utilisateur'])) { ?>
        <a href="connexion.php?deconnexion=1">Se déconnecter</a>
    <?php } ?>
    <h1>Connexion</h1>

    <?php foreach ($erreurs as $e) { ?>
        <p style="color:red"><?= htmlspecialchars($e) ?></p>
    <?php } ?>

    <form method="post">
        <input type="hidden" name="action" value="connexion">
        <label>E-mail <input type="email" name="email" required></label><br>
        <label>Mot de passe <input type="password" name="mot_de_passe" required></label><br>
        <button type="submit">Se connecter</button>
    </form>

    <h2>Pas de compte ? Inscris-toi</h2>
    <form method="post">
        <input type="hidden" name="action" value="inscription">
        <label>Nom <input type="text" name="nom" required></label><br>
        <label>Prénom <input type="text" name="prenom" required></label><br>
        <label>E-mail <input type="email" name="email" required></label><br>
        <label>Mot de passe <input type="password" name="mot_de_passe" required></label><br>
        <button type="submit">S'inscrire</button>
    </form>
</body>

</html>
