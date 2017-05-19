<?php
	
	if ( isset($_POST['dataSearch']) )
	{
		$string = $_POST['dataSearch'];
		if (is_string($string) && (is_object(json_decode($string)) || is_array(json_decode($string))))
		{
			$json_obj = json_decode($string, true);
			$get_json = $json_obj['get_json'];
			$book_id  = $json_obj['book_id'];
			
		}
	}

	if ( !isset($get_json) ) { $get_json = false;
		if( isset($_GET['get_json']) ){$get_json = $_GET['get_json'];}	
		else if( isset($_POST['get_json']) ){$get_json = $_POST['get_json'];} 
	}
	
	$get_list_for_jQuery = !isset($sqlBase);
	if( $get_list_for_jQuery ){
        include "connectToDataBase.php";
    }
	
	if( !isset($get_array) )$get_array = false;
	
	$authors_arr = [];
	
	$is_book_id = false;
    $query = "Select author_name.author_id, author_name.author_name from author_name";
	
	if( isset($book_id) )$is_book_id = true;
    else if( isset($_GET ['book_id'])){ $book_id = $_GET['book_id'];  $is_book_id = true;}
    else if( isset($_POST['book_id'])){ $book_id = $_POST['book_id']; $is_book_id = true;}

	if ($is_book_id){
	    $query = "Select book_author.book_id, author_name.author_id, author_name.author_name from author_name, book_author ";
		if ( gettype($book_id) != "array" )
			{$query .= " WHERE book_author.author_id = author_name.author_id AND book_author.book_id=" . $book_id;}
		else{ 
			$query .= " WHERE ";
			$i = 0;
			foreach($book_id as $value){
				if ($i > 0) $query .= " OR ";
				$query .= " book_author.author_id = author_name.author_id AND book_author.book_id=" . $value;
				$i = 1;
			}
		}
	}
	
	if ($resultAuthor = $sqlBase->query( $query ) )
	    while ($rowAuthor = $resultAuthor->fetch_assoc() ){
			if ( $get_array || $get_json )
				if ($is_book_id)
					$authors_arr[] = ["book_id" => $rowAuthor['book_id'], "author_id" => $rowAuthor['author_id'], "author_name" => $rowAuthor['author_name']];
				else
					$authors_arr[] = ["author_id" => $rowAuthor['author_id'], "author_name" => $rowAuthor['author_name']];
			else
				$authors_arr[] = $rowAuthor['author_name'];
		}
	
    if ($get_json)
		echo json_encode($authors_arr);
	else if (!$get_array)
		$authors = implode(", ", $authors_arr);
