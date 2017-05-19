<?php include "header.php"?>
		<script src = "js/js_for_index.js" type = "text/javascript"></script>
		<img src="images/head_books.jpg" alt="Special 1" draggable="false" class="page-top">
        <div class="flexslider">
            <!--<img src="images/head_books.jpg" alt="Special 1" draggable="false">-->
            <ul class="slides" id = "slider">
                <?php
                    /*$id = 0;
                    include "php/getBooksList.php";*/
                ?>
            </ul>
            <!-- <ul class="flex-direction-nav"><li><a class="flex-prev" href="#">←</a></li><li><a class="flex-next" href="#">→</a></li></ul> -->
        </div>

        <div class="container">
            <div class="row">
                <div class="our-listing owl-carousel">
                    <?php
                        $id = 1;
                        include "php/getBooksList.php";
                    ?>
                </div>
            </div>
        </div>

		<div class="middle-content container">

            <div style = "margin-bottom:15px">
				<label for="find_books">Найти книгу: </label>
				
				<div class="input-group">
					<input type="text" class="form-control" placeholder="Найти" id ="find_books_input">
					<span class="input-group-btn">
						<button class="btn btn-default" type="button" id="find_books">Найти!</button>
					</span>
				</div>
            </div>
			
			<select id = "quantityElem">
				<option name = "quantity5" value = "5" checked = "checked">5</option>
				<option name = "quantity10" value = "10">10</option>
				<option name = "quantity20" value = "20">20</option>
			</select>
			
            <div id="books_content">
                <?php
                    $id = 2;
                    include "php/getBooksList.php";
                ?>
			</div>
			
		</div>
<?php include "footer.php" ?>
		
