<?php class UserManager { //Permet d'empecher des injection sql
    public function __construct(private PDO $pdo) {
    }

    public function emailExiste(string $email): bool {
        $stmt = $this->pdo->prepare("SELECT 1 FROM Personne WHERE Email = :e");
        $stmt->execute(['e' => $email]);
        return (bool)$stmt->fetchColumn();
    }

    public function inscrire(User $user): int {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO Personne (Nom, Prenom, Email, mot_de_passe) VALUES (:n, :p, :e, :m)"
            );
            $stmt->execute([
                'n' => $user->getNom(),
                'p' => $user->getPrenom(),
                'e' => $user->getEmail(),
                'm' => password_hash($user->getMotDePasse(), PASSWORD_DEFAULT),
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
//Faire un tableau pr récup les user et le retourner 
