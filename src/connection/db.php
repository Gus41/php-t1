<?php
 
class Connection {
    private static $instance = null;
    private $conn;

    private function __construct() {
        $host = 'db';
        $db = 'testdb';
        $user = 'root';
        $pass = 'root';

        try {
            $this->conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Erro de conexão: " . $e->getMessage());
        }
    }

     public static function getInstance(): PDO {
        if (self::$instance === null) {
            $obj = new Connection();
            self::$instance = $obj->conn;
        }
        return self::$instance;
    }
}

 