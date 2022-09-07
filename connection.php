<?php
namespace DB;
require_once 'configs.php';

class ConnectionDB{

private static $con = null;

	private static function connect(){

		try{
			self::$con=new \PDO(MYSQL_DSN, MYSQL_USERNAME, MYSQL_PASSWORD
			,[   
			     \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
			     \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
			     \PDO::ATTR_EMULATE_PREPARES => false,
			     \PDO::ATTR_PERSISTENT => true
			]);
		}
		catch(\PDOException $e){
			die($e->getMessage());
		}
	}

	public static function getConnection(){
		if(self::$con == null)
		{
			self::connect();
			return self::$con;
		}
		return self::$con;
	}

	public static function closeConnection()
	{ 	if(self::$con == null) return;
		self::$con->query('KILL CONNECTION_ID()');
		self::$con = null;
	}


}