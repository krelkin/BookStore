<?php

	/*ДОБАВИТЬ!!!
		Возможность отсеивать книги дополнительно по авторам.
	*/
    $genres = json_decode($_POST['genres'], true);
	include "connectToDataBase.php";
	
	$query = "SELECT 
				genre_name.genre_id AS genre_id,
				genre_name.genre_name AS genre_name,
				count( book_genre.book_id ) AS book_quantity
			FROM 
				genre_name 

			left join book_genre
				ON book_genre.genre_id = genre_name.genre_id

				AND book_genre.book_id NOT IN (
					SELECT 
						book_genre.book_id 
					FROM 
						book_genre 
					WHERE 
						book_genre.genre_id = 0";


		$query = 
		"SELECT 
			genre_name.genre_id AS genre_id,
			genre_name.genre_name AS genre_name,
			count( book_genre.book_id ) AS book_quantity
		FROM 
			genre_name 

		left join book_genre
			ON book_genre.genre_id = genre_name.genre_id

			AND book_genre.book_id NOT IN (
				SELECT 
					book_genre.book_id 
				FROM 
					book_genre 
				WHERE 
					book_genre.genre_id = 0";	
		
	if(	count($genres) > 0)
		foreach ($genres as $value)
    		$query .= " OR book_genre.genre_id=" . $value;
			
			
			
	
	$query .= "
			 GROUP BY book_genre.book_id)
			GROUP BY genre_name.genre_name
			ORDER by genre_name.genre_id";
	
	
    $result_arr = [];
	
	if ($result = $sqlBase->query( $query ) ){
        while ($row = $result->fetch_assoc()) {
            $result_arr[] = [ "book_quantity" => $row["book_quantity"], "genre_id" => $row["genre_id"], "genre_name" => $row["genre_name"] ];
        }
    }

    echo json_encode($result_arr);

?>