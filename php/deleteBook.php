<?php
	include "connectToDataBase.php";

	$JSON = json_decode($_POST["data"]);
	$books_id = $JSON->book_id;
	
	$query = [];
	$notDeleting = [];
	$deleting = [];
	$message = "";
	$result = true;
	
	$firstQuery = "SELECT title, book_id
				   FROM books 
				   WHERE "; //получить наименование книг до их удаления, чтобы отобразить пользователю
	$WHERE = "";
	
	foreach($books_id as $key => $book_id){
		$query[] = "DELETE FROM books WHERE book_id=" . $book_id;
		$query[] = "DELETE FROM book_author WHERE book_id=" . $book_id;
		$query[] = "DELETE FROM book_genre WHERE book_id=" . $book_id;
		$WHERE .= ( strlen($WHERE)>0?" OR ": "");
		$WHERE .= "book_id =" . $book_id;
	}
	
	$firstQuery .= $WHERE;
	$res = $sqlBase->query($firstQuery);
	while($row = $res->fetch_assoc()){
		$books[ $row["book_id"] ] = $row["title"];
	}
	
	$old_title = "";
	$new_title = "";
	$change = true;
	
	foreach ($query as $key => $q){
		$_id = substr($q, strripos($q, "=") + 1);
		$new_title = $books[$_id];
		if($new_title != $old_title){
			$change = true; 
			$old_title = $new_title;
		}else{
			$change = false; 
			$message .= "<br />";
		};
		
		if($res = $sqlBase->query($q) ){
			
			$result = $res && $result;
			
			if($change)
				if($res === true){
					$message .= "Удалена книга <b> $new_title</b>";
					$deleting[] = $_id;
				}else{
					$message .= "Книга <b> $new_title </b> не удалена!";
					$notDeleting[] = $_id;
				}
		}
	}
		
	echo json_encode(["result" => $result, "message" => $message, "deleting" => $deleting, "notDeleting" => $notDeleting]);
?>