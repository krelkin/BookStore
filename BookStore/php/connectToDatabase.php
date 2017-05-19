<?php
	
	$sqlBase = new mysqli("localhost", "root", "", "bookstore");

    if ($sqlBase->connect_errno) {
        echo "Не удалось подключиться: %s\n";
        exit();
    };

    if (!$sqlBase->set_charset("utf8")) {
        echo "Ошибка при загрузке набора символов utf8: ". $sqlBase->error;
        exit();
    }
?>