<?php

include "constants.php";

class DB{
	
	private $sqlBase; //подключение к БД
	private $msg_err; //текст ошибки подключения к БД
	private $query_result; //результат последнего запроса SELECT
	private $query_text; //текст последнего запроса
	
	private $inserted_id = [ //добавленные данные
		"book_id"	=> 0,
		"data_book"	=> [],
		"author_id"	=> [],
		"genre_id"	=> []
	];
	
	private $deleted_id = [ //удалённые данные
		"book_id"	=> [],
		"author_id"	=> [],
		"genre_id"	=> []
	];
	
	private $not_deleted_id = [ //данные, которые не получилось удалить
		"book_id_author"	=> [], //id книг, на которые остались ссылки - массив типа [id автора => [массив id книг]]
		"book_id_genre"		=> []  //id книг, на которые остались ссылки - массив типа [id жанра  => [массив id книг]]
	];
	
	private $updated = [ //измененные данные
		"books"		=> [],
		"authors"	=> [],
		"genres"	=> []
	];
	
	private $parameters = [ //параметры подключения к базе
		"host"		=> "",
		"username"	=> "",
		"password"	=> "",
		"dbname"	=> ""
	];
	
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
	
	public function get_params(){ // отобразить параметры подключения к базе данных
		return $this->parameters;
	}

	public function get_query_txt(){ // отобразить текст последнего запроса
		return $this->query_text;
	}

	public function get_query_result(){ // получить результат запроса
		return $this->query_result;
	}
	
	public function get_inserted_id(){ // получить последние добавленные в БД значения
		return $this->inserted_id;
	}
	
	public function get_deleted_id(){ // получить последние удалённые из БД значения
		return $this->deleted_id;
	}
	
	public function get_not_deleted_id(){ // получить последние значения, которые не удалось удалить из БД
		return $this->not_deleted_id;
	}
	
	public function get_updated(){ // получить последние значения измененного объекта
		return $this->updated;
	}
	
	private function clear_inserted_id(){ // очищает массив id последних добавленных записей
		$this->inserted_id["book_id"]	= 0;
		$this->inserted_id["data_book"]	= [];
		$this->inserted_id["author_id"]	= [];
		$this->inserted_id["genre_id"]	= [];
	}
	
	private function clear_deleted_id(){ // очищает массив id последних удалённых записей
		$this->deleted_id["book_id"]	= [];
		$this->deleted_id["author_id"]	= [];
		$this->deleted_id["genre_id"]	= [];
	}

	private function clear_not_deleted_id(){ // очищает массив id последних записей, которые не удалось удалить
		$this->not_deleted_id["book_id_author"]	= [];
		$this->not_deleted_id["book_id_genre"]	= [];
	}

	private function clear_updated(){ // очистить массив изменённых объектов
		$this->updated["books"]		= [];
		$this->updated["authors"]	= [];
		$this->updated["genres"]	= [];
	}
	
	private function is_filled($params){ //проверка на заполненность параметров для блока запроса WHERE/ON
		$res = false;
		foreach($params as $key => $value){
			if(is_array($value)){
				if(count($value) > 0)
					$res = $this->is_filled($value);
			}elseif($value != "")
				$res = true;
			if($res == true) break;
		}
		return $res;
	}
	
	private function get_condition($params, $connector, $field = NULL){ // получить блок WHERE/ON для запроса
		$condition = "";
		if (is_array($params) && count($params) > 0){
			if(!$this->is_filled($params))return "";
			$condition .= $connector;
			$arr_params_where = [];
			$additional = "";
			foreach($params as $key => $value){
				if (is_array($value) && is_string($key) ){
					if(count($value) == 0)continue;
					if($key === "author_id"){
						$f = "book_author.author_id";
						$additional = "book_author.book_id = books.book_id AND ";
					}elseif($key === "genre_id"){
						$f = "book_genre.genre_id";
						$additional = "book_genre.book_id = books.book_id AND ";
					}
					// если в значениях массив, вызываем функцию ещё раз.
					$arr_params_where[] = $additional . $this->get_condition($value, "", $f);
				}else{
					if ($value == "")continue;
					if($key === "title")
						$arr_params_where[] = "LCASE(books.title) LIKE LCASE('%$value%')";
					elseif($key === "not_in")
						if(count($arr_params_where) != 0)
							$arr_params_where[] = ") AND books.book_id NOT IN ($value";
						else
							$arr_params_where[] = "books.book_id NOT IN ($value)";
					else
						$arr_params_where[] = "$field = $value";
				}
			}
			$condition .= " (" . implode(" OR ", $arr_params_where) . ")";
		}
		return str_replace("OR )", ")", $condition); //да, это костыль :)
	}
	
	private function delete_authors_genres($source_array, $table, $book_id = NULL){
		/*передаётся:
			"source_array"	- простой массив с id удаляемых объектов
			"table"			- строка. "author" или "genre" - что удаляем
			"book_id"		- id книги, из которой надо удалить данные
		*/
		$result = true;
		if ($book_id !== NULL){
			foreach($source_array as $k => $value){
				$result = $result && $this->exec_query("DELETE FROM book_$table WHERE book_id=$book_id AND $table" . "_id=$value");
				$this->deleted_id[$table . "_id"][] = $value;
			}
		}
		else
			foreach($source_array as $k => $value){
				$books_id = $this->select("SELECT book_id FROM book_" . "$table WHERE $table" . "_id = $value");
				if( count($books_id) == 0){
					$result = $result && $this->exec_query("DELETE FROM $table" . "_name WHERE $table" . "_id=$value");
					$this->deleted_id[$table . "_id"][] = $value;
				}else{
					$result = false;
					$this->not_deleted_id["book_id_$table"][$value] = $books_id;
				}
			}
		
		return $result;
	}
	
	private function get_query_text($id, $params, $order_by = "", $limit = 0){ // получить текст запроса
		$query = "";
		switch ($id){
			case 2: $query = "SELECT book_id, title, price, description 
							FROM books";
					$query .= $this->get_condition($params, " WHERE ", "books.book_id");
					if($order_by != "")	$query .= " ORDER BY $order_by";
					if($limit != 0)	$query .= " LIMIT $limit";
					break;
					
			case 3: $query = "SELECT book_author.book_id, author_name.author_id, author_name.author_name
							FROM author_name, book_author
							WHERE book_author.author_id = author_name.author_id";
					$query .= $this->get_condition($params, " AND ", "book_author.book_id");
					break;
					
			case 4: $query = "SELECT book_genre.book_id, genre_name.genre_id, genre_name.genre_name
							FROM genre_name, book_genre 
							WHERE genre_name.genre_id = book_genre.genre_id";
					$query .= $this->get_condition($params, " AND ", "book_genre.book_id");
					break;
					
			case 5: /*ДОБАВИТЬ!!!
						Возможность отсеивать книги дополнительно по авторам.
					*/
					$query = "SELECT count( book_genre.book_id ) AS book_quantity, genre_name.genre_id, genre_name.genre_name
							FROM genre_name
							LEFT JOIN book_genre ON book_genre.genre_id = genre_name.genre_id AND book_genre.book_id NOT IN (
								SELECT book_genre.book_id 
								FROM book_genre 
								WHERE book_genre.genre_id = 0";
								$query .= $this->get_condition($params, " OR ", "book_genre.genre_id");
					$query .= " GROUP BY book_genre.book_id) GROUP BY genre_name.genre_name ORDER BY genre_name.genre_id";
					break;
							
			case 6: $query = "SELECT books.book_id, books.title, books.description, books.price
							FROM books, book_author, book_genre ";
							$query .= $this->get_condition($params, " WHERE ");
							$query .= " GROUP BY books.book_id ORDER BY books.$order_by LIMIT $limit";
					break;
			case 7: $query = "SELECT count( DISTINCT(books.book_id) ) as quantityBook 
							FROM books, book_author, book_genre";
							$query .= $this->get_condition($params, " WHERE ");
					break;
			case 8: $title 		= isset($params['title'])?$params['title']:"";
					$description= isset($params['description'])?$params['description']:"";
					$price 		= isset($params['price'])?$params['price']:"";
					$query = "INSERT INTO books (title, description, price) 
							VALUES('" . $title . "', '" . $description . "', '" . $price . "')";
					break;
		}
		$this->query_text = $query;
		return $query;
	}
	
	private function exec_query($query){ // выполнить запрос и вернуть массив в качестве результата
		$this->query_text = $query;
		if (debug_backtrace()[1]["function"] == "select"){
			$res_array = [];
			if ($result = $this->sqlBase->query( $query ))
				while ($row = $result->fetch_assoc() ){
					$res_array[] = $row;
				}
			$this->query_result = $res_array;
			return $res_array;
		}else{
			return $this->sqlBase->query( $query );
		}
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
	
	public function insert($params = []){ // базовая функция insert, которая возвращает true или false
		/*передается
			"book_id"		- id книги. Должно отсутствовать или быть 0, если добавляем новую книгу/жанр/автора.
							Должно быть заполнено, если добавляем авторов или жанры к книге.
			"title"			- наименование книги
			"description"	- описание книги
			"price"			- цена
			"authors"		- простой массив id авторов
			"genres"		- простой массив id жанров
		*/
		$this->clear_inserted_id();
		/*Если book_id пустой, тогда добавляем книгу, 
			если заполнен - добавляем авторов и/или жанры к книге,
			если не заполнен, но заполнен один из массивов - добавляем авторов или жанры*/
		$result = true;
		if ((!isset($params["book_id"]) || $params["book_id"] == 0)
			&& isset($params["title"]) && isset($params["description"]) && isset($params["price"])){
			
			$query = $this->get_query_text(8, $params);
			$result = $this->exec_query($query);
			$this->query_result = $result;
			$this->inserted_id["book_id"] = $this->sqlBase->insert_id;
			$this->inserted_id["data_book"]["title"] = $params["title"];
			$this->inserted_id["data_book"]["description"] = $params["description"];
			$this->inserted_id["data_book"]["price"] = $params["price"];
			$params["book_id"] = $this->sqlBase->insert_id;
			$this->query_text = $query;
		}
		$is_book_id = isset($params["book_id"]) && ($params["book_id"] > 0);
		$arr = [];
		if (isset($params["authors"]) && is_array($params["authors"]))
			foreach($params["authors"] as $k => $value){
				if ($is_book_id){
					$arr[] = "INSERT INTO book_author (book_id, author_id)
						VALUES ('" . $params['book_id'] . "', '" . $value . "')";
					$this->inserted_id["author_id"][] = $value;
				}else
					$arr[] = "INSERT INTO author_name (author_name) 
						VALUES ('$value')";
			}
		
		if (isset($params["genres"]) && is_array($params["genres"]))
			foreach($params["genres"] as $k => $value){
				if ($is_book_id){
					$arr[] = "INSERT INTO book_genre (book_id, genre_id)
						VALUES ('" . $params['book_id'] . "', '" . $value . "')";
					$this->inserted_id["genre_id"][] = $value;
				}else 
					$arr[] = "INSERT INTO genre_name (genre_name) 
						VALUES ('$value')";
			}
				
		foreach($arr as $k => $query){
			$result = $result && $this->exec_query($query);
			$this->query_text = $query;
			if($result && !$is_book_id)
				if(strpos($query, "genre_name") === false) $this->inserted_id["author_id"][$this->sqlBase->insert_id] = str_replace("')", "", str_replace("VALUES ('", "", strstr($query, "VALUES")));
				else $this->inserted_id["genre_id"][$this->sqlBase->insert_id] = str_replace("')", "", str_replace("VALUES ('", "", strstr($query, "VALUES")));
		}
		
		return $result;
	}

	public function delete($params = []){ // базовая функция delete, которая возвращает true или false
		/*передается
			"params" - массив
				"book_id"		- id книги/массив id книг
				"authors"		- простой массив id авторов
				"genres"		- простой массив id жанров
		*/
		$result = true;
		$this->clear_deleted_id();
		$this->clear_not_deleted_id();
		if(isset($params["book_id"])){
			if(!isset($params["authors"]) && !isset($params["genres"])){ //удаляем книгу со всеми жанрами и авторами
				if(!is_array($params["book_id"]))$params["book_id"] = [$params["book_id"]];
				foreach($params["book_id"] as $k => $book_id){
					$res = $this->exec_query("DELETE FROM books WHERE book_id=" . $book_id);
					$result = $result && $res;
					if($result){
						$this->deleted_id["book_id"][] = $book_id;
						$authors = $this->select("SELECT author_id FROM book_author WHERE book_id=" . $book_id);
						$res = $this->exec_query("DELETE FROM book_author WHERE book_id=" . $book_id);
						$result = $result && $res;
						$genres = $this->select("SELECT genre_id FROM book_genre WHERE book_id=" . $book_id);
						$result = $result && $this->exec_query("DELETE FROM book_genre WHERE book_id=" . $book_id);
					}
				}
			}elseif($params["book_id"] != 0 && $params["book_id"] != ""){
				$this->deleted_id["book_id"][] = $params["book_id"];
				if(isset($params["authors"]) && count($params["authors"]) > 0){ // удаляем авторов из книги
					$res = $this->delete_authors_genres($params["authors"], "author", $params["book_id"]);
					$result = $result && $res;
				}
				if(isset($params["genres"]) && count($params["genres"]) > 0){ // удаляем жанры из книги
					$res = $this->delete_authors_genres($params["genres"], "genre", $params["book_id"]);
					$result = $result && $res;
				}
			}				
		}else{
			if(isset($params["authors"]) && count($params["authors"]) > 0){ // удаляем авторов из БД
				$res = $this->delete_authors_genres($params["authors"], "author");
				$result = $result && $res;
			}
			if(isset($params["genres"]) && count($params["genres"]) > 0){ // удаляем жанры из БД
				$res = $this->delete_authors_genres($params["genres"], "genre");
				$result = $result && $res;
			}
		}
		return $result;
	}

	public function update($params = []){ // базовая функция update, которая возвращает true или false
		/*передаётся:
			books => массив вида:
					["book_id" 		=> 	- id книги, которую необходимо изменить
					 "title"		=>	- новое наименование
					 "description"	=> 	- новое описание
					 "price"		=>	- новая цена
					 "authors"		=>	- простой массив id авторов
					 "genres"		=>	- простой массив id жанров
					]
			authors => массив вида:
				1. если не указан "book_id"
					["id_автора" => "Новое наименование"
					 "id автора" => "Новое наименование"
					]
			genres => массив вида:
				1. если не указан "book_id"
					["id_жанра" => "Новое наименование"
					 "id_жанра" => "Новое наименование"
					]
		*/
		$this->clear_updated();
		$result = true;
		if(isset($params["books"])){
			if(isset($params["books"]["book_id"])){
				//получаем старые значения для книги
				$old_book_params = $this->select("SELECT title, description, price FROM books WHERE book_id=" . $params["books"]["book_id"]);
				$arr = [];
				if(isset($params["books"]["title"]))
					if($params["books"]["title"] != $old_book_params[0]["title"]){
						$arr[] = "title='" . $params["books"]["title"];
						$this->updated["books"]["title"] 	 = $params["books"]["title"];
						$this->updated["books"]["old_title"] = $old_book_params[0]["title"];
					}
				if(isset($params["books"]["description"]))
					if($params["books"]["description"] != $old_book_params[0]["description"]){
						$arr[] = "description='" . $params["books"]["description"];
						$this->updated["books"]["description"]		= $params["books"]["description"];
						$this->updated["books"]["old_description"]	= $old_book_params[0]["description"];
					}
				if(isset($params["books"]["price"]))
					if($params["books"]["price"] != $old_book_params[0]["price"]){
						$arr[] = "price='" . $params["books"]["price"];
						$this->updated["books"]["price"] 	 = $params["books"]["price"];
						$this->updated["books"]["old_price"] = $old_book_params[0]["price"];
					}
				if(count($arr) > 0){
					$res = $this->exec_query("UPDATE books SET " . implode("', ", $arr) . "' WHERE book_id=" . $params["books"]["book_id"]);
					$result = $result && $res;
				}
				
				$add_authors	= [];
				$remove_authors = [];
				$add_genres		= [];
				$remove_genres	= [];
				if(isset($params["books"]["authors"]) && count($params["books"]["authors"]) > 0){
					$old_authors = [];
					//получаем старые значения авторов для книги
					foreach($this->select("SELECT author_id FROM book_author WHERE book_id=" . $params["books"]["book_id"]) as $k => $value){
						$old_authors[] = $value["author_id"];
					}
					$add_authors 	= array_diff($params["books"]["authors"], $old_authors); //выбор всех неповоряющихся значений новых авторов, которых нет в старых.
					$remove_authors = array_diff($old_authors, $params["books"]["authors"]); //выбор всех неповоряющихся значений старых авторов, которых нет в новых.
				}
				if(isset($params["books"]["genres"]) && count($params["books"]["genres"]) > 0){
					$old_genres = [];
					foreach($this->select("SELECT genre_id FROM book_genre WHERE book_id=" . $params["books"]["book_id"]) as $k => $value){
						$old_genres[] = $value["genre_id"];
					}
					
					$add_genres 	= array_diff($params["books"]["genres"], $old_genres); //выбор всех неповоряющихся значений новых жанров, которых нет в старых.
					$remove_genres 	= array_diff($old_genres, $params["books"]["genres"]); //выбор всех неповоряющихся значений старых жанров, которых нет в новых.
					
				}
				
				$res = $this->insert(["book_id" => $params["books"]["book_id"],
									"authors" => $add_authors,
									"genres"  => $add_genres
									]);
				$result = $result && $res;
				$this->updated["authors"]["added"] = $this->get_inserted_id()["author_id"];
				$this->updated["genres"]["added"]  = $this->get_inserted_id()["genre_id"];
				
				$res = $this->delete(["book_id" => $params["books"]["book_id"],
									"authors" => $remove_authors,
									"genres"  => $remove_genres
							  ]);
				$result = $result && $res;
				$this->updated["authors"]["removed"] = $this->get_deleted_id()["author_id"];
				$this->updated["genres"]["removed"]  = $this->get_deleted_id()["genre_id"];
			}
		}else{
			//если не передано значение книги
				if(isset($params["authors"]) && count($params["authors"]) > 0){
					foreach($params["authors"] as $id => $new_name){
						$old_name = $this->select("SELECT author_name FROM author_name WHERE author_id=$id")[0]["author_name"];
						if($old_name != $new_name){
							$res = $this->exec_query("UPDATE author_name SET author_name='$new_name' WHERE author_id=$id");
							if($res)
								$this->updated["authors"]["$id"] = ["$old_name" => "$new_name"];
							else
								$result = false;
						}
					}
				}
				if(isset($params["genres"]) && count($params["genres"]) > 0){
					foreach($params["genres"] as $id => $new_name){
						$old_name = $this->select("SELECT genre_name FROM genre_name WHERE genre_id=$id")[0]["genre_name"];
						if($old_name != $new_name){
							$res = $this->exec_query("UPDATE genre_name SET genre_name='$new_name' WHERE genre_id=$id");
							if($res)
								$this->updated["genres"]["$id"] = ["$old_name" => "$new_name"];
							else
								$result = false;
						}
					}
				}
		}
		
		return $result;
	}
}
	
$DB = new DB();

?>