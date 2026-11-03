<?php
class Database {
    private const DATABASE_CONFIGURATION_FILE = __DIR__ . '/../config/database.ini';
    private static ?PDO $pdo = null;

    public static function getConnexion(): PDO {
        if (self::$pdo === null) {
            $config = parse_ini_file(self::DATABASE_CONFIGURATION_FILE, true);

            if (!$config) {
                throw new Exception("Erreur lors de la lecture du fichier de configuration : " . self::DATABASE_CONFIGURATION_FILE);
            }

            $host = $config['host'];
            $port = $config['port'];
            $database = $config['database'];
            $username = $config['username'];
            $password = $config['password'];

            self::$pdo = new PDO(
                "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        }
        return self::$pdo;
    }
}
