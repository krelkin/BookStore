<?php

	include "connectToDataBase.php";
	
	$JSON_String = $_POST['dataRequest'];

  	$JSON = json_decode($JSON_String, true);
	
 	//print_r($JSON);exit();
 	$title		= trim($JSON['title']);//string
  	$authors	= $JSON['author_id']; //array
  	$genres		= $JSON['genre_id'];  //simple array
	$pageNumber	= $JSON['pageNumber'] - 1;  //integer (номер последней страницы, которая было отражена)
	$elemNumber	= $JSON['elemNumber'];  //integer
	$orderBy 	= $JSON['orderBy'];
	
	$queryWHERE = "";
	
	// [НАЧАЛО] Создаём модуль WHERE 
	$i = false;
	$or = " OR ";
	$and = " AND ";
	
	if ( $title != ""){
		$queryWHERE .= " LCASE(books.title) LIKE LCASE('%" . $title . "%') ";
		$i = true;
	};
	
	foreach($authors as $value){
		$queryWHERE .= $i ? $or : " ";
		$queryWHERE .= " book_author.book_id=books.book_id AND book_author.author_id=" . $value;
		$i = true;
	}
	
	foreach($genres as $value){
		$queryWHERE .= $i ? $or : " ";
		$queryWHERE .= " book_genre.book_id=books.book_id AND book_genre.genre_id=" . $value;
		$i = true;
	}
	// [КОНЕЦ] Создаём модуль WHERE 

	function get_used_books_id($sqlBase, $queryWHERE, $orderBy, $limit){
		$where = "";
		if ($queryWHERE != "")$where = " WHERE " . $queryWHERE;
		$query = 
		"SELECT 
			books.book_id as book_id
		FROM
			books,book_author, book_genre
		" . $where . "
		group by 
			books.book_id
		order by
			books." . $orderBy . "
		limit
			" . $limit;
			
		$res = [];
		if ($result = $sqlBase->query( $query ) )
			while ($row = $result->fetch_assoc() ){
				$res[] = $row["book_id"];
			};
		if (count($res) == 0) return "0";
		else return implode(", ", $res);
	};
	
	$query = 
		"SELECT 
			books.book_id,
			books.title,
			books.description,
			books.price
		FROM
			books,	
			book_author, 
			book_genre
		WHERE 
			books.book_id NOT IN (" . get_used_books_id($sqlBase, $queryWHERE, $orderBy ,$pageNumber * $elemNumber) . ")";
	
	if ($queryWHERE != "")$query .= " AND (" . $queryWHERE . ")";

	$query .=
	"group by 
		books.book_id,
		books.title,
		books.description,
		books.price
	order by 
		books." . $orderBy . "
	limit 
		" . $elemNumber;
	
	$books_list = [];
	if ($result = $sqlBase->query( $query ) )
		while ($row = $result->fetch_assoc() ){
			$books_list[] = array( 	'book_id' 	  => $row['book_id'], 
									'title' 	  => $row['title'], 
									'description' => $row['description'], 
									'price' 	  => $row['price'] 
								);
		}
	
	echo json_encode($books_list);