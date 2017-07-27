<?php

include "constants.php";

class DB{
	
	private $sqlBase; //подключение к БД
	private $msg_err; //текст ошибки подключения к БД
	private $query_result; //результат последнего запроса SELECT
	private $query_text; //текст последнего запроса
	private $last_added_id;
	
	public function get_last_inserted(){
		return $this->sqlBase->insert_id;
	}

	public function __construct(...$params){ //конструктор
	/*	в конструкторе используется только первых четыре параметра:
		1 - host
		2 - username
		3 - password
		4 - dbname
	*/
		if(count($params) == 0)
			if(!$this->get_params_from_file()){
				$this->parameters["host"]	  = DB_HOST;
				$this->parameters["username"] = DB_USERNAME;
				$this->parameters["password"] = DB_PASSWORD;
				$this->parameters["dbname"]	  = DB_NAME;
			}
		
		$i = 0;
		foreach($this->parameters as $key => $value){
			if ($i >= min(count($params), 4))break;
			$this->parameters[$key] = $params[$i];
			$i++;
		}
		
		$this->sqlBase = new mysqli(
					$this->parameters["host"],
					$this->parameters["username"], 
					$this->parameters["password"], 
					$this->parameters["dbname"]
							);

		if ($this->sqlBase->connect_errno) {
			$msg_err = $this->sqlBase->connect_error;
		};

		if (!$this->sqlBase->set_charset("utf8")) {
			$msg_err = "Ошибка при загрузке набора символов utf8: " . $sqlBase->error;
		}
	}

	private function get_params_from_file(){ //считываем данные из файла констант json
		$file_name = "constant.json";
		if(!file_exists($file_name))$file_name = "php/$file_name";
		if(!file_exists($file_name))return false;
		$str = file_get_contents($file_name);
		$json = json_decode($str, true);
		
		$this->parameters["host"] 		= $json["host"];
		$this->parameters["username"] 	= $json["username"];
		$this->parameters["password"] 	= $json["password"];
		$this->parameters["dbname"] 	= $json["dbname"];
		
		return true;
	}

	private function exec_query($query){ // выполнить запрос и вернуть массив в качестве результата
		$this->query_text = $query;
		$res_array = [];
		if ($result = $this->sqlBase->query( $query ))
			while ($row = $result->fetch_assoc() ){
				$res_array[] = $row;
			}
		$this->query_result = $res_array;
		return $res_array;
	}
	
	public function select($query_id, $params = [], $order_by = "", $limit = 0){ // базовая функция select, которая возвращает массив в качестве результата
		if ( is_string($query_id) ){
			return $this->exec_query( $query_id );
		}
		else 
			return $this->exec_query( 
					$this->get_query_text($query_id, $params, $order_by, $limit) 
				   );
	}
	
	public function insert($query){ // базовая функция insert, которая возвращает true или false
		return $this->sqlBase->query($query);
	}

	public function delete($query){ // базовая функция delete, которая возвращает true или false
		return $this->sqlBase->query($query);
	}

	public function update($query){ // базовая функция update, которая возвращает true или false
		return $this->sqlBase->query($query);
	}
}
	
$DB = new DB();

?>