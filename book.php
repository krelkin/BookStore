<?php include "header.php"; 
	
	include "php/functions.php";
	
	echo '<div class="page-top" id="templatemo_about">
	</div> <!-- /.page-header -->';
	
	$book = $DB->select(get_query_text(2, ["book_id" => $_GET["book_id"]]))[0];
	$genres_arr = getList($DB, ["field" => "genre",  "book_id" => $_GET["book_id"], "get_array" => true]);
	$authors_arr= getList($DB, ["field" => "author", "book_id" => $_GET["book_id"], "get_array" => true]);
		
	echo
	'
	<script type="text/javascript" src="js/order_book.js"> </script>
	<div class="middle-content">
		<div class = "owl-item">
			<div class="list-item">
				<div class="list-thumb">
					<div class="title">
						<h4>' . $book['title'] . '</h4>
					</div>
				</div>
				<div class="list-content">
					<div class="col-md-3">
						<img src = "images/destination_5.jpg" style = "max-width:200px" />
					</div>
					<div class="col-md-9"><h5>';
									
						foreach($authors_arr as $key => $value){
							echo '<a href = "searchpage.php?author_id=' . $value["author_id"] . '" class = "btn btn-success" style = "display:inline">' . $value["author_name"] . '</a>';
						}
						echo '</h5><div>&nbsp;</div><span>';
					
						foreach($genres_arr as $key => $value){
							echo '<a href = "searchpage.php?genre_id=' . $value["genre_id"] . '" class = "btn btn-warning" style = "display:inline">' . $value["genre_name"] . '</a>';
						}
						echo '</span>
						
						<div>&nbsp;</div>
						
						<div>' . $book['description'] . '</div>
						<div class = "block"> </div>
						<div class = "btn btn-info">' . $book['price'] . ' грн.</div>
					</div>
					<div>&nbsp;</div>
					<div class = "input-group">
						<div class = "input-group-addon">Количество:</div>
						<input class = "form-control" type = "number" id = "quantity_book" value = "1">
					</div>
					<a href="#" class="price-btn col-xs-12" onclick="SendMail()">Заказать</a>
				</div>
			</div>
		</div>
		<div class = "block"></div>
		<div class = "alert block" id = "result"></div>
	</div> ';
	
include "footer.php" 

?>