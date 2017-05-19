<?php 
	
	$JSON_BOOK = json_decode($_POST["dataUpdate"]);
	
	include "connectToDataBase.php"; //подключились к базе
	
	$book_id	 = $JSON_BOOK->book_id; //получили из POST $book_id

	$get_array = true;
	include "getGenreList.php";  //получили массив авторов по $book_id
	include "getAuthorList.php"; //получили массив жанров по $book_id
	
	function add($sqlBase, $book_id, $arr, $table_name){
		foreach($arr as $value) //добавление новых позиций, которые установил пользователь, в таблицы соответствий
			$sqlBase->query("INSERT INTO book_" . $table_name . "(book_id, " . $table_name . "_id) VALUES (" . $book_id . ", " . $value . ")");
	}
	
	function remove($sqlBase, $book_id, $arr, $table_name){
		foreach($arr as $value) //удаление позиций, которых больше нет в книге, из таблиц соответствий
			$sqlBase->query("DELETE FROM book_" . $table_name . " WHERE book_id = " . $book_id . " AND " . $table_name . "_id = " . $value);
	}
	
	//получили данные из POST запроса
	$newBookInfo = $JSON_BOOK->newBookInfo; 
	$newAuthors	 = $JSON_BOOK->newAuthors;
	$newGenres	 = $JSON_BOOK->newGenres;
	
	//простой запрос к книге по $book_id
	$query = "SELECT book_id, title, description, price FROM books WHERE book_id=" . $book_id;

	$oldParamBook = ""; //визуально вывести старые значение пользователю
	if ($result = $sqlBase->query( $query ))
        while ($row = $result->fetch_assoc() ){ //заполнение измененных визуальных значений пользователю
			$oldBookInfo = $row;
			if( $row["title"] != trim($newBookInfo->title) ) $oldParamBook .= "Название: " . $row["title"] . "<br />";
			if( $row["description"] != trim($newBookInfo->description) ) $oldParamBook .= "Описание: " . $row["description"] . "<br />";
			if( $row["price"] != trim($newBookInfo->price) ) $oldParamBook .= "Цена: " . $row["price"] . "<br />";
		};
	
	$newParamBook = [];
	foreach($newBookInfo as $key => $value){ //изменение необходимых параметров непосредственно в самой книге
		if ( $oldBookInfo[$key] != $value){
			$sqlBase->query("UPDATE books SET " . $key . "='" . $value . "' WHERE book_id=" . $book_id );
			if ($key == "title") $newParamBook["title"] = $value ;
			if ($key == "description") $newParamBook["description"] = $value;
			if ($key == "price") $newParamBook["price"] = $value;
		}
	};

	$oldAuthors = []; //список id авторов, которые сейчас установлены для книги
	foreach($authors_arr as $value) //заполнение id авторов
		$oldAuthors[] = $value["author_id"];
	
	$oldGenres = []; //список id жанров, которые сейчас установлены для книги
	foreach($genres_arr as $value) //заполнение id жанров
		$oldGenres[] = $value["genre_id"];

	$addAuthors 	= array_diff($newAuthors, $oldAuthors); //выбор всех неповоряющихся значений новых авторов, которых нет в старых.
	$removeAuthors 	= array_diff($oldAuthors, $newAuthors); //выбор всех неповоряющихся значений старых авторов, которых нет в новых.
	
	$addGenres 		= array_diff($newGenres, $oldGenres); //выбор всех неповоряющихся значений новых жанров, которых нет в старых.
	$removeGenres 	= array_diff($oldGenres, $newGenres); //выбор всех неповоряющихся значений старых жанров, которых нет в новых.
	
	//добавление позиций в таблицы соответствий
	add($sqlBase, $book_id, $addAuthors, "author");
	add($sqlBase, $book_id, $addGenres,  "genre");
	//удаление позиций из таблицы соответствий
	remove($sqlBase, $book_id, $removeAuthors, "author");
	remove($sqlBase, $book_id, $removeGenres,  "genre");
	
	$result = [];
	$result[] = [
		//"oldParamBook"   => $oldBookInfo, //текст
		"newParamBook"   => $newParamBook, //массив
		"addedAuthors"   => $addAuthors,   //массив индексов
		"addedGenres"  	 => $addGenres,	 //массив индексов
		"removedAuthors" => $removeAuthors,//массив индексов
		"removeGenres"   => $removeGenres //массив индексов
	];
	echo json_encode($result);
	//print_r( $result );
	
