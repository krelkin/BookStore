<?php include "header.php" ?>
	
        <script src="js/my_script.js"></script>

        <div class="page-top" id="templatemo_services">
        </div> <!-- /.page-header -->

        <div class="middle-content">
            <div class="container">
				<div>ЖАНРЫ </div>
				<div class="row" id = "genres_list_search">
				</div>
				<div>АВТОРЫ </div>
				
				<div class="panel panel-warning">
					<div class="panel-heading"  id="authors_panel">
						<div id="buffer"></div>
						<div style="display: inline;" id="authors_panel_list"></div>
						<input type="text" id="search_input" />
					</div>
					<div class = "panel-body" id = "panel_body">
						<ul id = "authors_list_search">
						</ul>
					</div>
				</div>
				
				<input type="text" class="form-control" id="title"/>
				
				<!--
				<div class="row" id = "authors_list_search">
				</div>
				-->
				
				<div>
					<select id = "orderBy" onchange = "selectOption(this)">
						<option value = "book_id ASC" selected = "selected">По дате добавления (старые вначале)</option>
						<option value = "book_id DESC">	По дате добавления (новые вначале)</option>
						<option value = "title ASC">	По наименованию (возр)</option>
						<option value = "title DESC">	По наименованию (убыв)</option>
						<option value = "price ASC">	По цене (от дешевых к дорогим)</option>
						<option value = "price DESC">	По цене (от дорогих к дешевым)</option>
					</select>
					<span id = "search_button" class = "btn btn-success block" onclick = "search()"> ПОИСК! </span>
					
					<select id = "quantityElem" onchange = "selectOption(this)">
						<option value = "5" selected = "selected">5</option>
						<option value = "10">10</option>
						<option value = "20">20</option>
					</select>
				</div>
				
				
                <div class="row">
                    <div class="col-md-12">
						<div id="search_content">
							
						</div>
                    </div> 
                </div>
				
				<div id = "jumpButtons" class="btn-group col-md-12" role="group" style = "margin: 0 auto;">
					
				</div>
            </div> <!-- /.container -->
        </div> <!-- /.middle-content -->




<?php include "footer.php" ?>