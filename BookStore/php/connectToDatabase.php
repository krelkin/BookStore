<?php
	
	$sqlBase = new mysqli("localhost", "root", "", "bookstore");

    if ($sqlBase->connect_errno) {
        echo "�� ������� ������������: %s\n";
        exit();
    };

    if (!$sqlBase->set_charset("utf8")) {
        echo "������ ��� �������� ������ �������� utf8: ". $sqlBase->error;
        exit();
    }
?>