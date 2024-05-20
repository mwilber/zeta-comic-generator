<?php
/**
 * Provides a database connection using PDO.
 * 
 * This class encapsulates the logic for connecting to a MySQL database using PDO.
 * It provides a `getConnection()` method that returns a PDO connection object.
 * The database connection details are specified using constants defined in 
 * key.php (DB_HOST, DB_NAME, DB_USER, DB_PASS).
 */
class Database{

	// specify your own database credentials
	private $host = DB_HOST;
	private $db_name = DB_NAME;
	private $username = DB_USER;
	private $password = DB_PASS;
	public $conn;

	// get the database connection
	public function getConnection(){

		$this->conn = null;

		try{
			$this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
			$this->conn->exec("set names utf8");
		}catch(PDOException $exception){
			echo "Connection error: " . $exception->getMessage();
		}

		return $this->conn;
	}
}
?>