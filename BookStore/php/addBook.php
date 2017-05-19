<?php

    $JSON_String = $_POST['dataRequest'];

  	$JSON = json_decode($JSON_String, true);

  	$title         = trim($JSON['title']);
  	$description   = trim($JSON['description']);
  	$price         = trim($JSON['price']);
  	$autors        = $JSON['authors'];
  	$genres        = $JSON['genres'];
	$arr = [];

    //$nameTable = 'genre' || 'author'
    function insert_book_($nameTable, $id_book, $JSON_table, $sqlBase){
        $string = "";
        $table_idGenres = "SELECT " . $nameTable . "_id, " . $nameTable . "_name FROM " . $nameTable . "_name WHERE ";
        $i = 0;
        foreach ( $JSON_table as $value)
            if ($i == 0)
                {
                    $table_idGenres .= $nameTable . "_id='" . $value . "'";
                    $i++;
                }
                else
                    $table_idGenres .= " OR " . $nameTable . "_id = '" . $value . "'";

        if ($result = $sqlBase->query( $table_idGenres ) ) {
            while($row = $result->fetch_assoc() ){
                $query_book_genre =
                    "INSERT INTO book_" . $nameTable . " (book_id, " . $nameTable . "_id) VALUES ('"
                        . $id_book . "', '" . $row[$nameTable . '_id'] . "')";
                $string .= " " . $row [$nameTable . "_name"] . ",";
                $sqlBase->query( $query_book_genre );
            }
        }

        $string = substr($string, 0, -1) . ".<br />";
        return $string;
    }


	$sqlBase = new mysqli("localhost", "root", "", "bookstore");

    if ($sqlBase->connect_errno) {
        echo "Не удалось подключиться: %s\n";
        exit();
    };

    if (!$sqlBase->set_charset("utf8")) {
        echo "Ошибка при загрузке набора символов utf8: ". $sqlBase->error;
        exit();
    };


    $query_addBook = "INSERT INTO books (title, description, price) VALUES ('"
        . $title . "', '"
        . $description . "', '"
        . $price . "')";
	$sqlBase->query( $query_addBook );
	$id_book = $sqlBase->insert_id;
	//$arr[] = "success add boook: " . $title . " " . " last id: " . ;


	$string = 'Добавлена книга. Название: ' . $title . "<br />";
    $string .= "Авторы: " . insert_book_ ("author", $id_book, $autors, $sqlBase);
    $string .= "Жанры: " . insert_book_ ("genre", $id_book, $genres, $sqlBase);
    $string .= "Цена: " . $price;

	$sqlBase -> close();

	echo $string;

?>