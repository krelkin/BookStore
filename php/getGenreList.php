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

    $genres_arr = [];
	
	$is_book_id = false;
    $query = "Select genre_name.genre_id, genre_name.genre_name from genre_name";
	
	if( isset($book_id) )$is_book_id = true;
    else if( isset($_GET ['book_id'])){ $book_id = $_GET['book_id'];  $is_book_id = true;}
    else if( isset($_POST['book_id'])){ $book_id = $_POST['book_id']; $is_book_id = true;}
	
	if ($is_book_id){
	    $query = "Select book_genre.book_id, genre_name.genre_id, genre_name.genre_name from genre_name";
		if ( gettype($book_id) != "array" )
			{$query .= ", book_genre WHERE genre_name.genre_id = book_genre.genre_id AND book_genre.book_id=" . $book_id;}
		else{ 
			$query .= ", book_genre WHERE ";
			$i = 0;
			foreach($book_id as $value){
				if ($i > 0) $query .= " OR ";
				$query .= "genre_name.genre_id = book_genre.genre_id AND book_genre.book_id=" . $value;
				$i = 1;
			}
		}
	}
	
	if ($resultGenre = $sqlBase->query( $query ) )
	    while ($rowGenre = $resultGenre->fetch_assoc() ){
			if ( $get_array || $get_json )
				if ($is_book_id)
					$genres_arr[] = ["book_id" => $rowGenre['book_id'], "genre_id" => $rowGenre['genre_id'], "genre_name" => $rowGenre['genre_name']];
				else
					$genres_arr[] = ["genre_id" => $rowGenre['genre_id'], "genre_name" => $rowGenre['genre_name']];
			else
				$genres_arr[] = $rowGenre['genre_name'];
		}
		
	if ($get_json)
		echo json_encode($genres_arr);
	else if (!$get_array)
		$genres = implode(", ", $genres_arr);
