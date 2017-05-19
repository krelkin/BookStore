<?php include "header.php" ?>
        <!--  -->
		<script src="js/add_change_script.js" charset="utf-8"></script>
		<!-- <script src="js/my_script.js"></script> -->

        <div class="page-top" id="templatemo_about">
        </div> <!-- /.page-header -->

        <div class="middle-content">
            <div class="container">
			
				<div class="btn-group block col-md-12" id = "menu"  role="group" style = "width: 100%">
					<div class="btn-group" role="group">
						<button type="button" class="btn btn-default" id="add" onclick = "refreshMenu(this, 1)">Добавить книгу</button>
					</div>
					<div class="btn-group" role="group">
						<button type="button" class="btn btn-default" id="change" onclick = "refreshMenu(this, 2)">Изменить книгу</button>
					</div>
					<div class="btn-group" role="group">
						<button type="button" class="btn btn-default" id="delete" onclick = "refreshMenu(this, 5)">Удалить книгу</button>
					</div>
					<div class="btn-group" role="group">
						<button type="button" class="btn btn-default" id="changeGenre" onclick = "refreshMenu(this, 3)">Доб./изм. жанр</button>
					</div>
					<div class="btn-group" role="group">
						<button type="button" class="btn btn-default" id="changeAuthor" onclick = "refreshMenu(this, 4)">Доб./изм. автора</button>
					</div>
				</div>
				<div class="block"></div>
                <div class="">
                
                	<div class="col-md-12">
                        <div id = "bookGroup">
							<div class="input-group">
								<span class="input-group-addon">Название книги</span>
								<div>
									<input type = "text" class = "form-control" name = "title" id = "title" />
									<select multiple id="books" style = "position:absolute; z-index:99; top:34px; height:500%"></select>
								</div>
							</div><br />
							
							<div>ЖАНРЫ </div>
							<div class="" id = "genres_list_search"></div>
							<br />
							<div>АВТОРЫ </div>
							<div class="panel panel-warning">
								<div class="panel-heading" id="authors_panel">
									<div id="buffer"></div>
									<div style="display: inline;" id="authors_panel_list"></div>
									<input type="text" style="border:none; outline:none; margin:0px; padding:0px; font-family:Arial, Verdana, sans-serif; font-size: 14px; letter-spacing: 0.05em;" id="search_input">
								</div>
								<div class = "panel-body" id = "panel_body" style = "position:absolute; z-index: 99; padding: 0px; overflow-y: auto; max-height: 50%;">
									<ul id = "authors_list_search">
									</ul>
								</div>
							</div>
							
							<div class="input-group">
								<span class="input-group-addon">Краткое описание</span>
								<textarea id = "description" class = "form-control" name = "description"></textarea>
							</div><br />
							
							<div class="input-group">
								<span class="input-group-addon">Цена книги</span>
								<input type = "number" id = "price" class = "form-control" name = "price"></textarea>
							</div><br />
                        </div> 
						
						<div id = "deleteBook">
							<div class="input-group">
								<span class="input-group-addon">Поиск</span>
								<div>
									<input type = "text" class = "form-control" name = "book_title" id = "book_title" />
								</div>
							</div><br />
							
							<div id = "deleteBooks">
								
							</div>
							
						</div>
						
						<div id = "genresGroup">
							<div class="input-group">
								<span class="input-group-addon">Название жанра</span>
								<div>
									<input type = "text" class = "form-control" name = "genre_name" id = "genre_name" />
								</div>
								<span class="input-group-btn">
									<button class="btn btn-success" type="button" onclick = "changeGenreAuthor(2, 0, '', 'genre')">Добавить жанр</button>
								</span>
							</div><br />
							
							<div id = "changeGenresGroup">
								
							</div>
							
						</div>

						<div id = "authorsGroup">						
							<div class="input-group">
								<span class="input-group-addon">Поиск по названию</span>
								<div>
									<input type = "text" class = "form-control" name = "author_name" id = "author_name" />
								</div>
								<span class="input-group-btn">
									<button class="btn btn-success" type="button" onclick = "changeGenreAuthor(2, 0, '', 'author')">Добавить автора</button>
								</span>
							</div><br />
							
							<div id = "changeAuthorsGroup">
								
							</div>

						
						</div>

						<br />
						<input id = "addChange" type = "button" class="btn btn-success block" onclick = "buttonClick()" value = "Добавить книгу">
						<br />
						<p class="alert alert-success block" id = "result"></p>
                    </div> 

                    
                </div> 
                
                
                
            </div> 
        </div> 



<?php include "footer.php" ?>