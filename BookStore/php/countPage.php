<?php

	include "connectToDataBase.php";
	
	$JSON_String = $_POST['dataRequest'];

  	$JSON = json_decode($JSON_String, true);
	
	/*print_r($JSON);
	exit();*/
	
	
  	$title		= trim($JSON['title']);//string
  	$authors	= $JSON['author_id'];  //array
  	$genres		= $JSON['genre_id'];   //simple array

	$query = "SELECT count( DISTINCT(books.book_id) ) as quantityBook 
				FROM 
			books";
	
	if ( count($authors) > 0 ) $query .= ", book_author ";
	if ( count($genres)  > 0 ) $query .= ", book_genre ";
	
	if ($title != "" || count($authors) > 0 || count($genres) > 0)$query .= " WHERE ";
	
	$or = " OR ";
	
	$i = false;
	if ( $title != ""){
		$query .= " LCASE(books.title) LIKE LCASE('%" . $title . "%') ";
		$i = true;
	};
	
	foreach($authors as $value){
		$query .= $i ? $or : " ";
		$query .= " book_author.book_id=books.book_id AND book_author.author_id=" . $value;
		$i = true;
	}
	
	foreach($genres as $value){
		$query .= $i ? $or : " ";
		$query .= " book_genre.book_id=books.book_id AND book_genre.genre_id=" . $value;
		$i = true;
	}
			
	//$query .= " GROUP BY books.book_id, books.title, books.description, books.price";
	/***********************************************************************************/
	//echo $query; exit();
	/***********************************************************************************/
	
	if ($result = $sqlBase->query( $query ) )
		while ($row = $result->fetch_assoc() ){
			echo $row['quantityBook'];
		}
