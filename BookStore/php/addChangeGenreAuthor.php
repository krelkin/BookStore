<?php
	include "connectToDataBase.php";
	
	$JSON = json_decode($_POST["data"]);
	$id_operation 	= $JSON->id_operation;
	$_id 			= $JSON->_id;
	$_change_name 	= $JSON->_name;
	$table			= $JSON->_table; //genre OR author
	if($table == "genre") $name = "жанр";
		else $name = "автор";

	$arr 				= [];
	$res 				= false;
	$message 			= "";
	
	if($id_operation == 1){ //удаление
		$query = "SELECT 
				book_" . $table . ".book_id
			FROM 
				book_" . $table . ", 
				" . $table . "_name 
			WHERE 
				" . $table . "_name." . $table . "_id = " . $_id . " 
			AND book_" . $table . "." . $table . "_id = " . $table . "_name." . $table . "_id";
	}
	else if($id_operation == 2){ //добавление
		$query = "INSERT INTO 
				" . $table . "_name (" . $table . "_name) 
			VALUES 
				('" . $_change_name . "')";
	}
	else { //изменение
		$query = "UPDATE 
				" . $table . "_name 
			SET 
				" . $table . "_name='" . $_change_name . "' 
			WHERE 
				" . $table . "_id = " . $_id;
	}
		
	$row = [];
	if ($result = $sqlBase->query( $query ) )
	{
		if($result === true) {
			$res = true;
			if($id_operation == 2){
				$message = 'Успешно добавлен ' . $name . ' <b>"' . $_change_name . '"</b>';
				$arr = $sqlBase->insert_id;
			}
			else if($id_operation == 3)
				$message = 'Успешно изменен ' . $name . ' <b>"' . $_change_name . '"</b>';}
		else if ($result === false){
			if($id_operation == 2)
				$message = $name . ' <b>"' . $_change_name . '"</b> не был добавлен';
			else if($id_operation == 3)
				$message = $name . ' <b>"' . $_change_name . '"</b> не был изменен';}
		else {
			while( $row = $result->fetch_assoc() )
				$arr[] = $row["book_id"];
		}
	}
	
	if ($id_operation == 1){
		if ( count($arr) == 0 ){
			$res = $sqlBase->query( "DELETE FROM " . $table . "_name WHERE " . $table . "_name." . $table . "_id = " . $_id );
			if ($res)
				$message = $name . " <b>'" . $_change_name . "'</b> успешно удалён";
			else 
				$message = $name . " <b>'" . $_change_name . "'</b> не был удалён";
			}
		else {
			$message = 'Невозможно удалить ' . $name . ' <b>"' . $_change_name . '"</b>';
			$res = false;
		}
	}
	
	echo json_encode( ["res" => $res, "arr_id" => $arr, "message" => $message] );

?>