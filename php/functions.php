<?php

include "MYSQLI_Class.php";

function getCookie($cookie_name){ // получить cookie по имени
	return $_COOKIE[$cookie_name];
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

	//$id = 3 - получить список авторов для книг(и)
	//$id = 4 - получить список жанров для книг(и)
	if(count($book_id)>0)
		$id = $field == "author"?3:4;
	else
		$id = $field=="author"?
				"SELECT author_id, author_name FROM author_name":
				"SELECT genre_id,  genre_name  FROM genre_name";
				
	$arr = []; // пустой массив для формирования результата

	foreach( $DB->select($id, $book_id) as $key => $row ){
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
	
	return json_encode($DB->select(7, $JSON)[0]);
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
	return json_encode($DB->select(5, $gen));
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
	while( count($rand_arr) > $amount )
		unset ( $rand_arr[ rand(0, count($rand_arr) - 1) ] ); 
	$arr = [];
	foreach ($rand_arr as $key => $value){ // делаем плоский массив
		$arr[] = $value["book_id"];
	}
	
	return $DB->select(2, $arr); //возвращаем массив из книг
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
	//получаем массив книг, которые не нужно отображать
	foreach($DB->select("SELECT book_id FROM books LIMIT " . $quantityOfElements * $pageNumber) as $k => $value)
		$books[] = $value["book_id"];
	
	$c = count($books)>0?implode(", ", $books):"0";
	
	$book_id = [];
	foreach( $DB->select("SELECT book_id FROM books WHERE book_id NOT IN (" . $c . ") LIMIT " . $quantityOfElements * $pageNumber) as $k => $value)
		$book_id[] = $value["book_id"];
		
	return $DB->select(2, $book_id, "", $quantityOfElements); //возвращаем массив из книг
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
	$books 		= $DB->select(2, $arr); //получаем из БД массив книг
	
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
	
	$book = $DB->select(2, array($book_id_select))[0];
	
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
	foreach( $DB->select(6, $searchParams, $arrayParams["orderBy"], getCookie("quantityElem") * $pageNumber ) as $key => $value)
		$arr[] = $value["book_id"];
	if(count($arr)>0) $searchParams["not_in"] = implode(", ", $arr);
	//получить id книг, которые нужно отобразить из отбора
	$arr = [];
	foreach( $DB->select(6, $searchParams, $arrayParams["orderBy"], getCookie("quantityElem") ) as $key => $value)
		$arr[] = $value["book_id"];
	
	return json_encode( $DB->select(6, $searchParams, $arrayParams["orderBy"], getCookie("quantityElem") ) );
}

function addBookAuthorGenre($DB, $arrayParams){ //добавление книги и/или автора(ов) и/или жанра(ов) в БД
	/*возврат
		json-объект последних добавленных данных
	*/
	
	return json_encode(["result" => $DB->insert($arrayParams), "inserted" => $DB->get_inserted_id()]);
}

function deleteBookAuthorGenre($DB, $arrayParams){ //удаление книги/жанра/автора
	/*возврат
		json-объект последних удалённых/не удалённых данных
	*/
	
	return json_encode(array_merge(["result" => $DB->delete($arrayParams)], ["deleted" => $DB->get_deleted_id()], ["not_deleted" => $DB->get_not_deleted_id()]));
}

function updateBookAuthorGenre($DB, $arrayParams){ //изменение книги/жанра/автора
	/*возврат
		json-объект последних изменённых данных
	*/
	
	return json_encode(["result" => $DB->update($arrayParams), "updated" => $DB->get_updated()]);
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
//echo print_r($arrayParams) . "<br><br><br><br>";
if (isset($arrayParams["function_name"]))
	return_result( $DB, $arrayParams, $arrayParams["function_name"]($DB, $arrayParams) );
