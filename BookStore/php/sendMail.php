<?php 
	echo mail($_POST["to"], $_POST["subject"], $_POST["message"],
			 "From: От кого письмо <admin@bookstore.com>");
?>