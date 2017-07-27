<?php

include "MYSQLI_Class.php";

function getCookie($cookie_name){ // получить cookie по имени
	if (!$_COOKIE[$cookie_name])
		return 5;
	else
		return $_COOKIE[$cookie_name];
}

function is_filled($params){ //проверка на заполненность параметров для блока запроса WHERE/ON
	$res = false;
	foreach($params as $key => $value){
		if(is_array($value)){
			if(count($value) > 0)
				$res = is_filled($value);
		}elseif($value != "")
			$res = true;
		if($res == true) break;
	}
	return $res;
}
	
function get_condition($params, $connector, $field = NULL){ // получить блок WHERE/ON для запроса
	$condition = "";
	if (is_array($params) && count($params) > 0){
		if(!is_filled($params))return "";
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
				$arr_params_where[] = $additional . get_condition($value, "", $f);
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

function get_query_text($id, $params, $order_by = "", $limit = 0){ // получить текст запроса
	$query = "";
	switch ($id){
		case 2: $query = "SELECT book_id, title, price, description 
						FROM books";
				$query .= get_condition($params, " WHERE ", "books.book_id");
				if($order_by != "")	$query .= " ORDER BY $order_by";
				if($limit != 0)	$query .= " LIMIT $limit";
				break;
				
		case 3: $query = "SELECT book_author.book_id, author_name.author_id, author_name.author_name
						FROM author_name, book_author
						WHERE book_author.author_id = author_name.author_id";
				$query .= get_condition($params, " AND ", "book_author.book_id");
				break;
				
		case 4: $query = "SELECT book_genre.book_id, genre_name.genre_id, genre_name.genre_name
						FROM genre_name, book_genre 
						WHERE genre_name.genre_id = book_genre.genre_id";
				$query .= get_condition($params, " AND ", "book_genre.book_id");
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
							$query .= get_condition($params, " OR ", "book_genre.genre_id");
				$query .= " GROUP BY book_genre.book_id) GROUP BY genre_name.genre_name ORDER BY genre_name.genre_id";
				break;
						
		case 6: $query = "SELECT books.book_id, books.title, books.description, books.price
						FROM books, book_author, book_genre ";
						$query .= get_condition($params, " WHERE ");
						$query .= " GROUP BY books.book_id ORDER BY books.$order_by LIMIT $limit";
				break;
		case 7: $query = "SELECT count( DISTINCT(books.book_id) ) as quantityBook 
						FROM books, book_author, book_genre";
						$query .= get_condition($params, " WHERE ");
				break;
		case 8: $title 		= isset($params['title'])?$params['title']:"";
				$description= isset($params['description'])?$params['description']:"";
				$price 		= isset($params['price'])?$params['price']:"";
				$query = "INSERT INTO books (title, description, price) 
						VALUES('" . $title . "', '" . $description . "', '" . $price . "')";
				break;
	}
	//$this->query_text = $query;
	return $query;
}
	
function insert($DB, $params = []){ // функция добавления объектов в базу данных, которая возвращает структуру добавленных id
	/*передается
		"book_id"		- id книги. Должно отсутствовать или быть 0, если добавляем новую книгу/жанр/автора.
						Должно быть заполнено, если добавляем авторов или жанры к книге.
		"title"			- наименование книги
		"description"	- описание книги
		"price"			- цена
		"authors"		- простой массив id авторов
		"genres"		- простой массив id жанров
	*/
	$inserted_id = [ //добавленные данные
		"book_id"	=> 0,
		"data_book"	=> [],
		"author_id"	=> [],
		"genre_id"	=> []
	];

	/*Если book_id пустой, тогда добавляем книгу, 
		если заполнен - добавляем авторов и/или жанры к книге,
		если не заполнен, но заполнен один из массивов - добавляем авторов или жанры*/
	$result = true;
	if ((!isset($params["book_id"]) || $params["book_id"] == 0)
		&& isset($params["title"]) && isset($params["description"]) && isset($params["price"])){
		
		$query = get_query_text(8, $params);
		$result = $DB->insert($query);
		$inserted_id["book_id"] = $DB->get_last_inserted();
		$inserted_id["data_book"]["title"] = $params["title"];
		$inserted_id["data_book"]["description"] = $params["description"];
		$inserted_id["data_book"]["price"] = $params["price"];
		$params["book_id"] = $inserted_id["book_id"];
	}
	$is_book_id = isset($params["book_id"]) && ($params["book_id"] > 0);
	$arr = [];
	if (isset($params["authors"]) && is_array($params["authors"]))
		foreach($params["authors"] as $k => $value){
			if ($is_book_id){
				$arr[] = "INSERT INTO book_author (book_id, author_id)
					VALUES ('" . $params['book_id'] . "', '" . $value . "')";
				$inserted_id["author_id"][] = $value;
			}else
				$arr[] = "INSERT INTO author_name (author_name) 
					VALUES ('$value')";
		}
	
	if (isset($params["genres"]) && is_array($params["genres"]))
		foreach($params["genres"] as $k => $value){
			if ($is_book_id){
				$arr[] = "INSERT INTO book_genre (book_id, genre_id)
					VALUES ('" . $params['book_id'] . "', '" . $value . "')";
				$inserted_id["genre_id"][] = $value;
			}else 
				$arr[] = "INSERT INTO genre_name (genre_name) 
					VALUES ('$value')";
		}
			
	foreach($arr as $k => $query){
		$result = $result && $DB->insert($query);
		if($result && !$is_book_id)
			if(strpos($query, "genre_name") === false) 
				$inserted_id["author_id"][$DB->get_last_inserted()] = str_replace("')", "", str_replace("VALUES ('", "", strstr($query, "VALUES")));
			else 
				$inserted_id["genre_id"][$DB->get_last_inserted()] = str_replace("')", "", str_replace("VALUES ('", "", strstr($query, "VALUES")));
	}
	
	return ["result" => $result, "inserted" => $inserted_id];
}

function delete_authors_genres($DB, $source_array, $table, $book_id = NULL, &$deleted_id, &$not_deleted_id){
	/*передаётся:
		"source_array"	- простой массив с id удаляемых объектов
		"table"			- строка. "author" или "genre" - что удаляем
		"book_id"		- id книги, из которой надо удалить данные
	*/
	$result = true;
	if ($book_id !== NULL){
		foreach($source_array as $k => $value){
			$res = $DB->delete("DELETE FROM book_$table WHERE book_id=$book_id AND $table" . "_id=$value");
			$result = $result && $res;
			$deleted_id[$table . "_id"][] = $value;
		}
	}
	else
		foreach($source_array as $k => $value){
			$books_id = $DB->select("SELECT book_id FROM book_" . "$table WHERE $table" . "_id = $value");
			if( count($books_id) == 0){
				$res = $DB->delete("DELETE FROM $table" . "_name WHERE $table" . "_id=$value");
				$result = $result && $res;
				$deleted_id[$table . "_id"][] = $value;
			}else{
				$result = false;
				$not_deleted_id["book_id_$table"][$value] = $books_id;
			}
		}
	
	return $result;
}

function delete($DB, $params = []){ // функция удаления объектов из базы данных, которая возвращает структуру удалённых id или id, которые удалить невозможно
	/*передается
		"params" - массив
			"book_id"		- id книги/массив id книг
			"authors"		- простой массив id авторов
			"genres"		- простой массив id жанров
	*/
	$result = true;
	$deleted_id = [ //удалённые данные
		"book_id"	=> [],
		"author_id"	=> [],
		"genre_id"	=> []
	];
	
	$not_deleted_id = [ //данные, которые не получилось удалить
		"book_id_author"	=> [], //id книг, на которые остались ссылки - массив типа [id автора => [массив id книг]]
		"book_id_genre"		=> []  //id книг, на которые остались ссылки - массив типа [id жанра  => [массив id книг]]
	];
	if(isset($params["book_id"])){
		if(!isset($params["authors"]) && !isset($params["genres"])){ //удаляем книгу со всеми жанрами и авторами
			if(!is_array($params["book_id"]))$params["book_id"] = [$params["book_id"]];
			foreach($params["book_id"] as $k => $book_id){
				$res = $DB->delete("DELETE FROM books WHERE book_id=" . $book_id);
				$result = $result && $res;
				if($result){
					$deleted_id["book_id"][] = $book_id;
					$authors = $DB->select("SELECT author_id FROM book_author WHERE book_id=" . $book_id);
					$res = $DB->delete("DELETE FROM book_author WHERE book_id=" . $book_id);
					$result = $result && $res;
					$genres = $DB->select("SELECT genre_id FROM book_genre WHERE book_id=" . $book_id);
					$res = $DB->delete("DELETE FROM book_genre WHERE book_id=" . $book_id);
					$result = $result && $res;
				}
			}
		}elseif($params["book_id"] != 0 && $params["book_id"] != ""){
			$deleted_id["book_id"][] = $params["book_id"];
			if(isset($params["authors"]) && count($params["authors"]) > 0){ // удаляем авторов из книги
				$res = delete_authors_genres($DB, $params["authors"], "author", $params["book_id"], $deleted_id, $not_deleted_id);
				$result = $result && $res;
			}
			if(isset($params["genres"]) && count($params["genres"]) > 0){ // удаляем жанры из книги
				$res = delete_authors_genres($DB, $params["genres"], "genre", $params["book_id"], $deleted_id, $not_deleted_id);
				$result = $result && $res;
			}
		}				
	}else{
		if(isset($params["authors"]) && count($params["authors"]) > 0){ // удаляем авторов из БД
			$res = delete_authors_genres($DB, $params["authors"], "author", NULL, $deleted_id, $not_deleted_id);
			$result = $result && $res;
		}
		if(isset($params["genres"]) && count($params["genres"]) > 0){ // удаляем жанры из БД
			$res = delete_authors_genres($DB, $params["genres"], "genre", NULL, $deleted_id, $not_deleted_id);
			$result = $result && $res;
		}
	}
	
	return array_merge(
				["result" => $result], 
				["deleted" => $deleted_id], 
				["not_deleted" => $not_deleted_id]
				);
}

function update($DB, $params = []){ // функция изменения объектов в базе данных, которая возвращает структуру измененных параметров
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
	
	$updated = [ //измененные данные
		"books"		=> [],
		"authors"	=> [],
		"genres"	=> []
	];

	
	$result = true;
	if(isset($params["books"])){
		if(isset($params["books"]["book_id"])){
			//получаем старые значения для книги
			$old_book_params = $DB->select("SELECT title, description, price FROM books WHERE book_id=" . $params["books"]["book_id"]);
			$arr = [];
			if(isset($params["books"]["title"]))
				if($params["books"]["title"] != $old_book_params[0]["title"]){
					$arr[] = "title='" . $params["books"]["title"];
					$updated["books"]["title"] 	 = $params["books"]["title"];
					$updated["books"]["old_title"] = $old_book_params[0]["title"];
				}
			if(isset($params["books"]["description"]))
				if($params["books"]["description"] != $old_book_params[0]["description"]){
					$arr[] = "description='" . $params["books"]["description"];
					$updated["books"]["description"]		= $params["books"]["description"];
					$updated["books"]["old_description"]	= $old_book_params[0]["description"];
				}
			if(isset($params["books"]["price"]))
				if($params["books"]["price"] != $old_book_params[0]["price"]){
					$arr[] = "price='" . $params["books"]["price"];
					$updated["books"]["price"] 	 = $params["books"]["price"];
					$updated["books"]["old_price"] = $old_book_params[0]["price"];
				}
			if(count($arr) > 0){
				$res = $DB->update("UPDATE books SET " . implode("', ", $arr) . "' WHERE book_id=" . $params["books"]["book_id"]);
				$result = $result && $res;
			}
			
			$add_authors	= [];
			$remove_authors = [];
			$add_genres		= [];
			$remove_genres	= [];
			if(isset($params["books"]["authors"]) && count($params["books"]["authors"]) > 0){
				$old_authors = [];
				//получаем старые значения авторов для книги
				foreach($DB->select("SELECT author_id FROM book_author WHERE book_id=" . $params["books"]["book_id"]) as $k => $value){
					$old_authors[] = $value["author_id"];
				}
				$add_authors 	= array_diff($params["books"]["authors"], $old_authors); //выбор всех неповоряющихся значений новых авторов, которых нет в старых.
				$remove_authors = array_diff($old_authors, $params["books"]["authors"]); //выбор всех неповоряющихся значений старых авторов, которых нет в новых.
			}
			if(isset($params["books"]["genres"]) && count($params["books"]["genres"]) > 0){
				$old_genres = [];
				foreach($DB->select("SELECT genre_id FROM book_genre WHERE book_id=" . $params["books"]["book_id"]) as $k => $value){
					$old_genres[] = $value["genre_id"];
				}
				
				$add_genres 	= array_diff($params["books"]["genres"], $old_genres); //выбор всех неповоряющихся значений новых жанров, которых нет в старых.
				$remove_genres 	= array_diff($old_genres, $params["books"]["genres"]); //выбор всех неповоряющихся значений старых жанров, которых нет в новых.
				
			}
			
			$res = insert($DB, ["book_id" => $params["books"]["book_id"],
							"authors" => $add_authors,
							"genres"  => $add_genres
							]);
			$result = $result && $res["result"];
			$updated["authors"]["added"] = $res["inserted"]["author_id"];
			$updated["genres"]["added"]  = $res["inserted"]["genre_id"];
			
			$res = delete($DB, ["book_id" => $params["books"]["book_id"],
								"authors" => $remove_authors,
								"genres"  => $remove_genres
						  ]);
						  
			$result = $result && $res["result"];
			$updated["authors"]["removed"] = $res["deleted"]["author_id"];
			$updated["genres"]["removed"]  = $res["deleted"]["genre_id"];
		}
	}else{
		//если не передано значение книги
		if(isset($params["authors"]) && count($params["authors"]) > 0){
			foreach($params["authors"] as $id => $new_name){
				$old_name = $DB->select("SELECT author_name FROM author_name WHERE author_id=$id")[0]["author_name"];
				if($old_name != $new_name){
					$res = $DB->update("UPDATE author_name SET author_name='$new_name' WHERE author_id=$id");
					if($res)
						$updated["authors"]["$id"] = ["$old_name" => "$new_name"];
					else
						$result = false;
				}
			}
		}
		if(isset($params["genres"]) && count($params["genres"]) > 0){
			foreach($params["genres"] as $id => $new_name){
				$old_name = $DB->select("SELECT genre_name FROM genre_name WHERE genre_id=$id")[0]["genre_name"];
				if($old_name != $new_name){
					$res = $DB->update("UPDATE genre_name SET genre_name='$new_name' WHERE genre_id=$id");
					if($res)
						$updated["genres"]["$id"] = ["$old_name" => "$new_name"];
					else
						$result = false;
				}
			}
		}
	}
	
	return ["result" => $result, "updated" => $updated];
	//return $result;
}

function getList($DB, $arrayParams){ // получить список авторов/жанров как полный, так и для определённой(ых) книг(и) в виде json-объекта, массива или строки
	/*в функцию можно передать:
		0. "field" 			- ОБЯЗАТЕЛЬНОЕ ПОЛЕ. Содержит "author" или "genre"
		1. "dataSearch" 	- JSON объект, который может содержать набор параметров:
			a. "get_json" 	- вернуть из функции JSON объект
			b. "book_id"  	- номер(а) книг(и), для которых необходимо отобрать авторов
		2. "book_id"  		- номер(а) книг(и), для которых необходимо отобрать авторов
		3. "get_json"		- вернуть из функции JSON объект
		4. "get_array"		- вернуть из функции массив
		
	возвращается:
		1. get_json = true && get_array = true
			объект JSON вида
			[{"author_id":"1","author_name":"author_1"},
			 {"author_id":"2","author_name":"author_2"}, 
			 ...
			]
			или
			[{"genre_id":"1","genre_name":"genre_1"},
			 {"genre_id":"2","genre_name":"genre_2"}, 
			 ...
			]
			
		2. get_json = false && get_array = true
			массив типа
			[0 => [ "author_id" => 1, "author_name" => "author_1" ]
			 1 => [ "author_id" => 2, "author_name" => "author_2" ], 
			 [...], ...
			]
			или
			[0 => [ "genre_id" => 1, "genre_name" => "genre_1" ]
			 1 => [ "genre_id" => 2, "genre_name" => "genre_2" ], 
			 [...], ...
			]

		3. get_json = false && get_array = false
			строку типа
			"author_1, author_2, author_3, ..."
			или
			"genre_1, genre_2, genre_3, ..."
	*/
	
	if (!isset($arrayParams["field"]))return [];
	$field = $arrayParams["field"];
	
	if ( isset($arrayParams['dataSearch']) ){ // проверка, передан ли JSON объект
		$string = $arrayParams['dataSearch'];
		if (is_string($string) && (is_object(json_decode($string)) || is_array(json_decode($string)))){ 
			// распарсивание JSON объекта
			$json_obj = json_decode($string, true);
			$get_json = $json_obj['get_json'];
			$book_id  = is_array($json_obj['book_id'])?$json_obj['book_id']:array($json_obj['book_id']);
		}
	}
	
	$get_json	= isset($arrayParams['get_json'])?$get_json = $arrayParams['get_json']:false;
	$get_array	= isset($arrayParams['get_array'])?$arrayParams['get_array']:false;
	$book_id 	= isset($arrayParams['book_id'])?
						is_array($arrayParams['book_id'])?
								$arrayParams['book_id']:
								array($arrayParams['book_id']):
						[];

	//3 - получить список авторов для книг(и)
	//4 - получить список жанров для книг(и)
	if(count($book_id)>0)
		$query = $field == "author"?
			get_query_text(3, $book_id):
			get_query_text(4, $book_id);
	else
		$query = $field=="author"?
				"SELECT author_id, author_name FROM author_name":
				"SELECT genre_id,  genre_name  FROM genre_name";
				
	$arr = []; // пустой массив для формирования результата

	foreach( $DB->select($query) as $key => $row ){
		if ( $get_array || $get_json )
			if (count($book_id)>0) //если книга(и) указана(ы)
				$arr[] = ["book_id" => $row['book_id'], $field . "_id" => $row[$field . '_id'], $field . "_name" => $row[$field . '_name']];
			else //если книга(и) не указана(ы)
				$arr[] = [$field . "_id" => $row[$field . '_id'], $field . "_name" => $row[$field . '_name']];
		else
			$arr[] = $row[$field . "_name"];
	}
	
    if($get_json) 
		return json_encode($arr);// получить JSON объект
	elseif($get_array) 
		return $arr; // получить массив
	else 
		return implode(", ", $arr);// получить простым списком
}

function countPage($DB, $arrayParams = []){ // получить количество страниц
	/*передается
		1. "title"		- string
		2. "author_id"	- simple array
		3. "genre_id"	- simple array
	
	возвращается
		массив фиксированной структуры
		["quantityBook" => число]
	*/
	$JSON = [];
	if(isset($arrayParams['searchParams']))
		$JSON = $arrayParams['searchParams'];
	
	return json_encode($DB->select( get_query_text(7, $JSON) )[0]);
}

function getBigGenreList($DB, $arrayParams){ // получить список жанров с количеством книг, которые ещё можно отобразить при отборе
	/*передаётся
		1. "genres" - simple array: выбранные жанры
	
	возвращается json-объект типа
		[0 => ["book_quantity" => 3, "genre_id" => 1, "genre_name" => "Научная фантастика"],
		 1 => ["book_quantity" => 3, "genre_id" => 2, "genre_name" => "Фентези"],
		 2 => [...],
		 ...
		]
	*/
	
	$gen = isset($arrayParams['genres'])?$arrayParams['genres']:[];
	return json_encode( $DB->select( get_query_text(5, $gen) ) );
}

function getBookListForSlider($DB, $amount){ //получить список книг для слайдера, расположенного под картинкой шапки (index.php)
	/*передаётся:
		1. "amount"	- количество книг, которые должны быть отображены в карусели
	
	возвращается массив типа:
		[0 => ["book_id" => 1], 1 => ["book_id" => 2], ["book_id" => ... ], [...], ... ]
	*/
	
	//выбираем все книги
	$rand_arr = $DB->select("SELECT book_id FROM books");
	//удаляем случайные книги
	while( count($rand_arr) > $amount ){
		$rand_num = rand(0, count($rand_arr) - 1);
		unset ( $rand_arr[$rand_num] );
		sort($rand_arr);
	}
	$arr = [];
	foreach ($rand_arr as $key => $value){ // делаем плоский массив
		$arr[] = $value["book_id"];
	}
	
	return $DB->select( get_query_text(2, $arr) ); //возвращаем массив из книг
}

function getBookListForMainPage($DB, $arrayParams){ //получить список книг (определённое количество) для отбражения на главной странице (index.php)
	/*передаётся:
		1. "limit"	- количество книг на странице
		2. "page"	- страница, на которой стоит курсор
		3. "book_id"- простой массив из id книг, которые не нужно отображать
		
	возвращается массив типа:
		[0 => ["book_id" => 1], 1 => ["book_id" => 2], ["book_id" => ... ], [...], ... ]
	*/
	$pageNumber = isset($arrayParams["page"])?$arrayParams["page"]-1: 0; //получаем страницу, на которой установлен курсор
	$quantityOfElements = htmlspecialchars( getCookie("quantityElem") ); //получаем из cookies количество элементов на странице

	$books = [];
	$query = "SELECT book_id FROM books LIMIT " . ($quantityOfElements * $pageNumber);
	
	//получаем массив книг, которые не нужно отображать
	foreach($DB->select($query) as $k => $value)
		$books[] = $value["book_id"];
	
	$c = count($books)>0?implode(", ", $books):"0";
	
	$book_id = [];
	$query = "SELECT book_id FROM books WHERE book_id NOT IN (" . $c . ") LIMIT " . ($quantityOfElements * $pageNumber);
	foreach( $DB->select($query) as $k => $value)
		$book_id[] = $value["book_id"];
	
	$query = get_query_text(2, $book_id, "", $quantityOfElements);
	return $DB->select($query); //возвращаем массив из книг
}

function getBookListForSelectTag($DB, $arrayParams){ //формирует список книг для заполнения SELECT'а из админки на вкладке изменения (addChangeBookPage.php)
	/*передаются:
		1. "book_id"	- простой массив, в котором соджержатся id необходимых книг
		
	возвращает json-объект типа:
		[	"book_id" 		=> $row['book_id'], //integer
			"title" 		=> $row['title'], 	//string
			"authors" 		=> $authors_arr ]	//array [0 => ["author_name" => "Значение"], 1 => [...], ...]
	*/
	$arr = isset($arrayParams["book_id"])?$arrayParams["book_id"]:[]; // получаем массив книг, которые нужно отобразить
	$books 		= $DB->select( get_query_text(2, $arr) ); //получаем из БД массив книг
	
	foreach($books as $key => $value){
		$arr = [];
		foreach(getList($DB, ["field" => "author", "get_array" => true, "book_id" => $value["book_id"]]) as $k => $val) // получаем из БД массив авторов, принадлежащий конкретной книге
			$arr[] = ["author_name" => $val["author_name"]]; //собираем массив авторов
		$books[$key]["authors"] = $arr; // записываем массив авторов к книгам
	}

	return json_encode($books);
}

function getBookListForSelectedBook($DB, $arrayParams){ //формирует список параметров для выбранной книги из админки на вкладке изменения книги (addChangeBookPage.php)
	/*передаём
		book_id_select	- id выбранной книги. ОБЯЗАТЕЛЬНЫЙ ПАРАМЕТР для передачи
		
	возвращает json-объект типа:
		["book_id"		=> 2,
		 "title"		=> "Название",
		 "price"		=> 1,
		 "description"	=> "Описание",
		 "authors"		=> ["author_name" => "Автор"]
		 "genres"		=> ["genre_name" => "Жанр"] 
	*/

	$book_id_select = isset($arrayParams["book_id_select"])?$arrayParams["book_id_select"]:0;
	if($book_id_select == 0) return [];
	
	$book = $DB->select( get_query_text(2, array($book_id_select)) )[0];
	
	$arr = [];
	foreach(getList($DB, ["field" => "author", "get_array" => true, "book_id" => $book["book_id"]]) as $k => $val) // получаем из БД массив авторов, принадлежащий конкретной книге
		$arr[] = ["author_id" => $val["author_id"]]; //собираем массив авторов
	$book["authors"] = $arr; // записываем массив авторов к книгам
	
	$arr = [];
	foreach(getList($DB, ["field" => "genre", "get_array" => true, "book_id" => $book["book_id"]]) as $k => $val) // получаем из БД массив авторов, принадлежащий конкретной книге
		$arr[] = ["genre_id" => $val["genre_id"]]; //собираем массив жанров
	$book["genres"] = $arr; // записываем массив жанров к книгам
	
	return json_encode($book);
}

function getBooksList($DB, $arrayParams){ //получение массивов/json-объектов книг
	
	/*передаётся:
		1. "page"	- номер страницы, на которой установлен курсор
		2. "id"		- тип выборки
	
	
	работа с главной страницей
	$id = 0 - получить список книг для главного слайдера (устарело)
				{выбираются все поля}
	$id = 1 - получить список книг для слайдера, расположенного под картинкой 
				{выбираются все поля}
	$id = 2 - получить список книг (определённое количество) для отбражения на главной странице 
				{выбираются все поля}
	
	работа со страницей добавления/изменения/удаления
	$id = 4 - формирует список книг для заполнения SELECT'а на вкладке изменения книги
				[	"book_id" 		=> $row['book_id'], //integer
					"title" 		=> $row['title'], 	//string
					"authors" 		=> $authors_arr ]	//array [0 => ["author_name" => "Значение"], 1 => [...], ...]
	$id = 5 - формирует массив для заполнения данных выбранной книги на вкладке изменения книги
				[  	"book_id" 		=> $row['book_id'], 
					"title" 		=> $row['title'] , 
					"description"	=> substr($row['description'], 0, 400),
					"price"			=> $row['price'],
					"authors" 		=> $authors_arr,
					"genres"		=> $genres_arr ]
	*/
    $quantityElementsOfSecondarySlider = 5;
	$id = $arrayParams["id"];
	if(!isset($arrayParams["page"]))$arrayParams["page"] = 0;
	
	if ($id == 1)
		return getBookListForSlider($DB, $quantityElementsOfSecondarySlider);
	elseif($id == 2)
		return getBookListForMainPage($DB, $arrayParams);
	elseif($id == 4)
		return getBookListForSelectTag($DB, $arrayParams);
	elseif($id == 5)
		return getBookListForSelectedBook($DB, $arrayParams);
	
}

function withdrawHTMLSection($id, $book_array, $authors = "", $genres = ""){ //отрисовка на странице
	switch($id){
		case 1:
			return '<div class="list-item">
				<div class="list-thumb">
					<div class="title">
						<h4><a style = "color:white" href = "book.php?book_id=' . $book_array["book_id"] . '">' . $book_array["title"] . '</a></h4>
					</div>
					<img src="images/destination_1.jpg" alt="">
				</div>
				<div class="list-content">
					<h5>' . $authors . '</h5>
					<span>' . $genres . '</span>
					<a href="book.php?book_id=' . $book_array["book_id"] . '" class="price-btn">' . $book_array["price"] . ' грн.</a>
				</div>
			</div>
			';
			
		case 2:
			$str = '<div class = "panel panel-warning">
				<div class="row list-item panel-heading"  style="margin:0px">
					<div class="col-xs-12 list-thumb">
						<span class="col-xs-12 title"><h4><a style = "color:white" href = "book.php?book_id=' . $book_array["book_id"] . '">' . $book_array['title'] . '</a></h4></span>
					</div>
					<div class="col-xs-12">
						<dl><dt>Авторы </dt><dd class="authors_list">';
							foreach($authors as $key => $value){
								$str .= '<a href=searchpage.php?author_id=' . $value["author_id"] . ' class = "btn btn-success">' . $value["author_name"] . '</a>';
							}
			$str .= '</dd><dt>Жанры </dt><dd class="genre_list">';
							foreach($genres as $key => $value){
								$str .= '<a href=searchpage.php?genre_id=' . $value["genre_id"] . ' class = "btn btn-warning">' . $value["genre_name"] . '</a>';
							}
			$str .=	'</dd><dt>Описание</dt><dd>'
								. substr($book_array['description'], 0, 400)
							.'...</dd></dl>
						<a class = "col-xs-12 btn btn-info" href = "book.php?book_id=' . $book_array['book_id'] . '">' . $book_array['price'] . ' грн.</a>
					</div>
				</div>
			</div>
			';
			return $str;
	}
}

function search($DB, $arrayParams){ //получение json-объекта для страницы поиска (search.php)
	/*передаём
		"searchParams"	- массив типа
			"title"		- строка поиска по названию
			"author_id" - плоский массив id авторов
			"genre_id"	- плоский массив id жанров
		"page"			- число. Страница, на которой установлен указатель
		"orderBy"		- поле сортировки. Текстовое значение
		
	возвращает json-объект типа:
		["book_id" 	  => $row['book_id'], 
		 "title" 	  => $row['title'], 
		 "description"=> $row['description'], 
		 "price" 	  => $row['price']	
	*/
	
	$searchParams = isset($arrayParams["searchParams"])?$arrayParams["searchParams"]:[];
	$pageNumber = isset($arrayParams["page"])?$arrayParams["page"]-1:0;
	//получить id книг, которые нужно исключить из отбора
	$arr = [];
	$query = get_query_text(6, $searchParams, $arrayParams["orderBy"], getCookie("quantityElem") * $pageNumber);
	foreach( $DB->select($query) as $key => $value)
		$arr[] = $value["book_id"];
	if(count($arr)>0) $searchParams["not_in"] = implode(", ", $arr);
	//получить id книг, которые нужно отобразить из отбора
	$arr = [];
	$query = get_query_text(6, $searchParams, $arrayParams["orderBy"], getCookie("quantityElem"));
	foreach( $DB->select($query) as $key => $value)
		$arr[] = $value["book_id"];
	
	$query = get_query_text(6, $searchParams, $arrayParams["orderBy"], getCookie("quantityElem"));
	return json_encode( $DB->select($query) );
}

function addBookAuthorGenre($DB, $arrayParams){ //добавление книги и/или автора(ов) и/или жанра(ов) в БД
	/*возврат
		json-объект последних добавленных данных
	*/
	return json_encode( insert($DB, $arrayParams) );
}

function deleteBookAuthorGenre($DB, $arrayParams){ //удаление книги/жанра/автора
	/*возврат
		json-объект последних удалённых/не удалённых данных
	*/
	return json_encode( delete($DB, $arrayParams) );
}

function updateBookAuthorGenre($DB, $arrayParams){ //изменение книги/жанра/автора
	/*возврат
		json-объект последних изменённых данных
	*/
	return json_encode(update($DB, $arrayParams));
}

function sendMail($DB, $params){ //отправка письма администратору
	//тело письма
	$message_body = 'Заказана книга "' . $params["title"] . '" в количестве ' . $params["quantity_book"] . ' штук.';
	
	return mail(ADMIN_EMAIL, TITLE_MESSAGE, $message_body,
		"From: От кого письмо <" . ADMIN_EMAIL . ">");
	
}

function return_result($DB, $arrayParams, $result){ //функция возвращения результата
	if($arrayParams["function_name"] == "getBooksList"){
		if($arrayParams["id"] == "1" || $arrayParams["id"] == "2"){ // вывод на экран монитора букв и других символов
			$result_str = "";
			foreach($result as $key => $value){
				if($arrayParams["id"] == "1"){
					$authors = getList($DB, ["field" => "author", "book_id" => $value["book_id"]]);
					$genres  = getList($DB, ["field" => "genre",  "book_id" => $value["book_id"]]);
				}else{
					$authors = getList($DB, ["field" => "author", "book_id" => $value["book_id"], "get_array" => true]);
					$genres  = getList($DB, ["field" => "genre",  "book_id" => $value["book_id"], "get_array" => true]);
				}
			
				$result_str .= withdrawHTMLSection(
					$arrayParams["id"],
					$value,
					$authors,
					$genres
				);
			}
			
			if($arrayParams["id"] == 2){
				$all_books = json_decode(countPage($DB))->quantityBook;
				$elements_quantity = getCookie("quantityElem");
				$page_number = isset($_GET["page"])?$_GET["page"]-1:0;
				$result_str .= '<div class="btn-group" role="group">';
				for($i = 1; $i <= ceil($all_books/$elements_quantity); $i++){
					$active = "";
					if ($i == $page_number + 1) $active = "active";
					$result_str .= '<button type="button" class="btn btn-default ' . $active . '" name="pageBtn' . $i . '" id="btn' . $i . '" onclick="choosePage(' . $i . ')">' . $i . '</button>';
				};
				$result_str .= '</div>';
			}
			
			$result = $result_str;
		}
	}
	echo $result;
}

/*передаются переметры через методы GET, POST и JSON-объект
	"json_data"		- параметры, которые передаются в функцию
*/
function getJSONData($json_data){
	$arr = [];
	foreach($json_data as $key => $value){
		if (is_object($value)) 
			$arr[$key] = getJSONData($value);
		else 
			$arr[$key] = $value;
	}
	return $arr;
}

$arrayParams = [];
if( count($_POST) ) $arrayParams = $_POST;
elseif( count($_GET) ) $arrayParams = $_GET;

if(isset($arrayParams["json_data"])){
	$arrayParams = getJSONData(json_decode($arrayParams["json_data"]));
}
if (isset($arrayParams["function_name"]))
	return_result( $DB, $arrayParams, $arrayParams["function_name"]($DB, $arrayParams) );
