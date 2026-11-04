<!--Formulaire de connexion
demande adresse e-mail et mdp
si on a pas de compte on doit en crée un
A voir comment liée ça à la base de donnée -->
<?php
session_start();
require_once __DIR__ . '/../src/User.php';
require_once __DIR__ . '/../src/UserManager.php';


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

class Validateur { //vérification de donnée saisie 
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

$repo = new UserManager($pdo);
$erreurs = [];
$message = '';

if (isset($_GET['deconnexion'])) { //Déconnexion
    session_destroy();
    header('Location: connexion.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') { //traitement du formulaire
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
            $user = new User(null, $nom, $prenom, $email, $mdp);
            $id = $repo->inscrire($user);
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
