<?php 
	
	$JSON_BOOK = json_decode($_POST["dataUpdate"]);
	
	include "connectToDataBase.php"; //������������ � ����
	
	$book_id	 = $JSON_BOOK->book_id; //�������� �� POST $book_id

	$get_array = true;
	include "getGenreList.php";  //�������� ������ ������� �� $book_id
	include "getAuthorList.php"; //�������� ������ ������ �� $book_id
	
	function add($sqlBase, $book_id, $arr, $table_name){
		foreach($arr as $value) //���������� ����� �������, ������� ��������� ������������, � ������� ������������
			$sqlBase->query("INSERT INTO book_" . $table_name . "(book_id, " . $table_name . "_id) VALUES (" . $book_id . ", " . $value . ")");
	}
	
	function remove($sqlBase, $book_id, $arr, $table_name){
		foreach($arr as $value) //�������� �������, ������� ������ ��� � �����, �� ������ ������������
			$sqlBase->query("DELETE FROM book_" . $table_name . " WHERE book_id = " . $book_id . " AND " . $table_name . "_id = " . $value);
	}
	
	//�������� ������ �� POST �������
	$newBookInfo = $JSON_BOOK->newBookInfo; 
	$newAuthors	 = $JSON_BOOK->newAuthors;
	$newGenres	 = $JSON_BOOK->newGenres;
	
	//������� ������ � ����� �� $book_id
	$query = "SELECT book_id, title, description, price FROM books WHERE book_id=" . $book_id;

	$oldParamBook = ""; //��������� ������� ������ �������� ������������
	if ($result = $sqlBase->query( $query ))
        while ($row = $result->fetch_assoc() ){ //���������� ���������� ���������� �������� ������������
			$oldBookInfo = $row;
			if( $row["title"] != trim($newBookInfo->title) ) $oldParamBook .= "��������: " . $row["title"] . "<br />";
			if( $row["description"] != trim($newBookInfo->description) ) $oldParamBook .= "��������: " . $row["description"] . "<br />";
			if( $row["price"] != trim($newBookInfo->price) ) $oldParamBook .= "����: " . $row["price"] . "<br />";
		};
	
	$newParamBook = [];
	foreach($newBookInfo as $key => $value){ //��������� ����������� ���������� ��������������� � ����� �����
		if ( $oldBookInfo[$key] != $value){
			$sqlBase->query("UPDATE books SET " . $key . "='" . $value . "' WHERE book_id=" . $book_id );
			if ($key == "title") $newParamBook["title"] = $value ;
			if ($key == "description") $newParamBook["description"] = $value;
			if ($key == "price") $newParamBook["price"] = $value;
		}
	};

	$oldAuthors = []; //������ id �������, ������� ������ ����������� ��� �����
	foreach($authors_arr as $value) //���������� id �������
		$oldAuthors[] = $value["author_id"];
	
	$oldGenres = []; //������ id ������, ������� ������ ����������� ��� �����
	foreach($genres_arr as $value) //���������� id ������
		$oldGenres[] = $value["genre_id"];

	$addAuthors 	= array_diff($newAuthors, $oldAuthors); //����� ���� �������������� �������� ����� �������, ������� ��� � ������.
	$removeAuthors 	= array_diff($oldAuthors, $newAuthors); //����� ���� �������������� �������� ������ �������, ������� ��� � �����.
	
	$addGenres 		= array_diff($newGenres, $oldGenres); //����� ���� �������������� �������� ����� ������, ������� ��� � ������.
	$removeGenres 	= array_diff($oldGenres, $newGenres); //����� ���� �������������� �������� ������ ������, ������� ��� � �����.
	
	//���������� ������� � ������� ������������
	add($sqlBase, $book_id, $addAuthors, "author");
	add($sqlBase, $book_id, $addGenres,  "genre");
	//�������� ������� �� ������� ������������
	remove($sqlBase, $book_id, $removeAuthors, "author");
	remove($sqlBase, $book_id, $removeGenres,  "genre");
	
	$result = [];
	$result[] = [
		//"oldParamBook"   => $oldBookInfo, //�����
		"newParamBook"   => $newParamBook, //������
		"addedAuthors"   => $addAuthors,   //������ ��������
		"addedGenres"  	 => $addGenres,	 //������ ��������
		"removedAuthors" => $removeAuthors,//������ ��������
		"removeGenres"   => $removeGenres //������ ��������
	];
	echo json_encode($result);
	//print_r( $result );
	
