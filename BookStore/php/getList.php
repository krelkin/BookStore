<?php

    $JSON_String = $_POST['data'];
    $JSON = json_decode($JSON_String, true);

    $tableName = $JSON['tableName'];
    $book_id = $JSON['book_id'];

	$sqlBase = new mysqli("localhost", "root", "", "bookstore");

    if ($sqlBase->connect_errno) {
        echo "Не удалось подключиться: %s\n";
        exit();
    };

    if (!$sqlBase->set_charset("utf8")) {
        echo "Ошибка при загрузке набора символов utf8: ". $sqlBase->error;
        exit();
    }

    $result_arr = [];
    if($book_id == "")
        if ($tableName == "books")
            $query = "SELECT book_id FROM books ORDER BY book_id";
        else
            $query = "SELECT " . $tableName . "_id AS id, " . $tableName . "_name AS name FROM " . $tableName . "_name";
    else
        $query = "SELECT " . $tableName. "_name." . $tableName . "_name AS name, " . $tableName. "_name." . $tableName . "_id AS id FROM " . $tableName . "_name, book_" . $tableName
            . " WHERE " . $tableName . "_name." . $tableName . "_id = book_" . $tableName . "." . $tableName . "_id "
            . "AND book_" . $tableName . ".book_id = " . $book_id;

	if ($result = $sqlBase->query( $query ) ){
        while ($row = $result->fetch_assoc()) {
            if ($tableName == "books")
                $result_arr[] = $row['id'];
            else
                $result_arr["name".$row['id']] = $row['name'];
        }
    }

    echo json_encode($result_arr);

?>