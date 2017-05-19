;
//комментарий!!!
var books = {};		 //ассоциативный массив всех книг, которые есть в базе с id, названием и списком авторов
var chooseBook = 0;  //id выбранной книги при изменении
var allAuthors = {}; //массив, содержащий всех авторов (нужен для отбора)
var allGenres  = {}; //массив, содержащий все жанры (нужен для отбора)
var choosenAuthors = []; //массив выбранных в отбор авторов
var choosenGenres  = []; //массив выбранных в отбор жанров
var GETParams = {}; //массив данных, которые передаются на страницу методом GET
var panelClick = 0; //переменная для нормального отображения панели
var titleClick = false; //переменная для нормального сворачивания/разворачивания select'а с наименованиями книг

$(document).ready(function(){ //При загрузке страницы (пока только для SEARCHPAGE.PHP)
	getListOfAllGenres();
	getListOfAllAuthors();
	getAllBooks();
	refreshMenu(null, null, true);

	$("#deleteBook").hide();
	$("#genresGroup").hide();
	$("#authorsGroup").hide();
	$("#panel_body").outerWidth( $("#authors_panel").outerWidth() );
	$("#books").width( $("#title").width() );
	$("#search_input").css("display", "none");
	$("#search_input").css("width", "1px");
	$("#panel_body").hide();
	$("#books").hide();
	$("#add").addClass("active");
	$("#result").hide();

	$(window).resize(function(){
		$("#panel_body").outerWidth( $("#authors_panel").outerWidth() );
		$("#books").width( $("#title").width() );
		$("#changeGenresGroup input[type=button]").outerWidth( $("#changeGenresGroup input[type=text]").outerWidth() );
		$("#changeAuthorsGroup input[type=button]").outerWidth( $("#changeAuthorsGroup input[type=text]").outerWidth() );
		$("#deleteBooks input[type=button]").outerWidth( $("#deleteBooks input[type=text]").outerWidth() );
		refreshMenu(null, null, true);
	});
	$("body").click(function(){
		if (!panelClick) {
			$("#authors_panel").css("border-bottom", "4px groove black");
			$("#panel_body").hide(); 
			$("#search_input").val("");
		}
		panelClick = 0;
		if (!titleClick) $("#books").hide();
		titleClick = false;
	});
	
	$("#authors_panel").click(function () {
		if (!panelClick)panelClick = 1;
		if (panelClick != 1) return;
		$("#search_input").show();
		$("#search_input").focus();
		
		if ( $("#panel_body:hidden").length == 1 ){
			$("#panel_body").show();
			$(this).css("border-bottom", "0px");
		}else{
			$("#panel_body").hide();
			$(this).css("border-bottom", "4px groove black");
		}
	});
	$("#search_input").on("input", null, function(){
		$("#buffer").text( $("#search_input").val().replace(/\s/g,'x') );
		$("#search_input").width($("#buffer").width() + 1);
		$("#panel_body:hidden").show();
		find(0, allAuthors, "#panel_body li", $(this).val().toLowerCase() );
		$("#authors_panel").css("border-bottom", "0px");
	});
	$("#author_name").on("input", null, function(){
		find(1, allAuthors, "#changeAuthorsGroup div", $(this).val().toLowerCase() );
	})
	$("#title").on("input", null, function(){
		$(this).css("background-color", "#FFFFFF");
		find(2, books, "#books option", $(this).val().toLowerCase());
	});
	$("#genre_name").on("input", null, function(){
		find(1, allGenres, "#changeGenresGroup div", $(this).val().toLowerCase() );
	});
	$("#book_title").on("input", null, function(){
		find(1, books, "#deleteBooks div", $(this).val().toLowerCase() );
	});
	$("#title").focus(function(){
		find(2, books, "#books option", $(this).val().toLowerCase());
	});
	$("#search_input").focus(function(){
		$("#search_input").css("display", "inline");
		find(0, allAuthors, "#authors_list_search li", $(this).val().toLowerCase() );
	});
	$("#panel_body").click(function(){
		panelClick = true;
		$("#authors_panel").css("border-bottom", "4px groove black");
	});
	$("#title").focus(function(){
		if( $("#change").hasClass("active") ){
			$("#books").show();
			titleClick = true;
		}
	});
	
});

function refreshMenu(th, id, chShape){
	var changeShape = chShape || false;
	
	if (chShape){
		if( $(".container").width() < 940){
			$("#menu").removeClass("btn-group");
			$("#menu").removeClass("btn-group-justified");
			$("#menu").addClass("btn-group-vertical");
		}else{
			$("#menu").addClass("btn-group");
			$("#menu").removeClass("btn-group-vertical");
			$("#menu").addClass("btn-group-justified");
		}
		return;
	}
	
	$("#menu button").removeClass("active");
	$(th).addClass("active");
	$("#title").val("");
	$("#deleteBook").hide();
	$("#addChange").removeClass("btn-success");
	$("#addChange").removeClass("btn-warning");
	$("#addChange").removeClass("btn-danger");
	
	if(id == 1 || id == 2){
		$("#bookGroup:hidden").show();
		$("#genresGroup").hide();
		$("#authorsGroup").hide();
		if (id == 1){
			$("#books").hide();
			$("#addChange").addClass("btn-success");
			$("#addChange").val("Добавить книгу");
		}else if (id == 2){
			$("#title").attr("list", "books");
			$("#addChange").addClass("btn-warning");
			$("#addChange").val("Изменить книгу");
		}
	}
	else
	if(id == 3 || id == 4){
		$("#bookGroup").hide();
		$("#addChange").addClass("btn-danger");
		if (id == 3){
			$("#authorsGroup").hide();
			$("#genresGroup:hidden").show();
			$("#addChange").val("Удалить жанр");
			fillGenresAuthorsBooks("#changeGenresGroup", "genre", allGenres);
		}
		else {
			$("#genresGroup").hide();
			$("#authorsGroup:hidden").show();
			$("#addChange").val("Удалить автора");
			fillGenresAuthorsBooks("#changeAuthorsGroup", "author", allAuthors);
		}
	}
	else
	if(id == 5){
			$("#bookGroup").hide();
			$("#genresGroup").hide();
			$("#authorsGroup").hide();
			$("#deleteBook").show();
			$("#addChange").addClass("btn-danger");
			$("#addChange").val("Удалить книгу");
			fillGenresAuthorsBooks("#deleteBooks", "book", books);
		};
}

//==============================НАЧАЛО РАБОТА С AJAX=========================================
function getFromAjax(method, url_, data, successFunction){ //AJAX запрос
    var func = successFunction || alert("Не передана функция!");
	
    $.ajax({
        type: method,
        url: url_,
        data: data,
        success: func,
        error: function(XHR, textStatus, errorThrown){
            alert ("Error из запроса");
        }
    });
}

function fillContentArrayFromJSON(msg, array, flatArray, tableName){ //распарсивание JSON объекта в произвольный ассоциативный массив
	var flat = false || flatArray;
	var data = JSON.parse(msg);
	if (!flat)
		$.each(data, function (index, value) {
			array[index] = value;
		});
	else
		$.each(data, function (index, value) {
			array[ value[tableName + "_id"] ] = value[tableName + "_name"];
		});
}
//===============================КОНЕЦ  РАБОТА С AJAX=========================================

//==============================НАЧАЛО ДОБАВЛЕНИЕ КОНТЕНТА===================================
function createAllGenresList(jQObject){ //Заполнение всех жанров для фильтров
	$.each(allGenres, function(index, value){
		var c = "btn btn-danger block";
		$.each(choosenGenres, function(index, valueChooseGenre){
			if (valueChooseGenre == value["genre_id"]) c = "btn btn-success col-md-2 block";
		}); 
		jQObject.
			append( $("<a>", {
						id: "genres" + index, 
						class: c, 
						text: value["genre_name"], 
						onclick: "chooseGenre(this)"}) 
					);
	});
}

function getListOfAllGenres(){ //AJAX запрос по всем жанрам
	var fillAllGenres = function(msg){
		$("#genres_list_search").html("");
		fillContentArrayFromJSON(msg, allGenres);
		createAllGenresList( $("#genres_list_search") );
	}
	getFromAjax("POST", "php/getBigGenreList.php", 'genres=' + JSON.stringify(choosenGenres),  fillAllGenres);
}

function createAllAuthorsList(jQObject){ //Заполнение всех авторов для фильтров
	$.each(allAuthors, function(index, value){
		var li = $("<li>", {
				id: "author" + index,
				class: "btn btn-default",
				style:"width: inherit; display:block",
				onclick: "chooseAutor(this)"
				})
			.append( $("<a>", {
							href: "#!", 
							class: "", 
							text: value
						}) 
					);
		
		jQObject.append( li );
	});
	showArrayAutors($("#authors_panel_list"));
}

function getListOfAllAuthors(){ //AJAX запрос по всем авторам
	var fillAllAuthors = function(msg){
		$("#authors_list_search").html("");
		fillContentArrayFromJSON(msg, allAuthors, true, "author");
		createAllAuthorsList( $("#authors_list_search") );
	}
	getFromAjax("POST", "php/getAuthorList.php", "get_json=true", fillAllAuthors);
}

function createDataList(jQObject){ //Заполнение select книгами
	jQObject.html("");
	$.each(books, function(index, value){
		autStr = "";
		$.each(value["authors"], function(ind, val){
				autStr += val["author_name"] + ", ";
				});
		jQObject.append( $("<option>", {
								text: value["title"] + " [" + autStr.substr(0, autStr.length - 2) + "]",
								id: "book" + value["book_id"],
								onclick: "selectBook(this)"
									}) );
	});
}

function getAllBooks(){ //AJAX запрос по всем книгам
	var fillDataList = function(msg){
		fillContentArrayFromJSON(msg, books);
		createDataList( $("#books") );
	}
	getFromAjax("POST", "php/getBooksList.php", "id=4", fillDataList);
}
//==============================КОНЕЦ  ДОБАВЛЕНИЕ КОНТЕНТА====================================

//==========================НАЧАЛО ФУНКЦИИ ПО РАБОТЕ С ОТБОРАМИ===============================
function showArrayAutors(jQObject){ //Отобразить выбранных авторов
    jQObject.html("");
	$.each(choosenAuthors, function (index, value) {
		var head = 	$("<div>", 		{name: "author" + value, class: "btn btn-primary", onclick: "outOfArrayAutors(this)"})
			.append($("<span />", 	{class: "glyphicon glyphicon-remove"}))
			.append($("<label />", 	{name: index, text: allAuthors[value]}));

		jQObject.append(head);
	});
}

function outOfArrayAutors(elem){ //Удаление автора из списка выбранных авторов
	$("#" + elem.attributes.name.value).show();
	var author_id = elem.attributes.name.value.slice(6);
    choosenAuthors = choosenAuthors.filter(function(el){
		return el != author_id;
	});
    showArrayAutors( $("#authors_panel_list") );
	panelClick = 2;
}

function chooseAutor(elem){ //Добавление автора в список выбранных авторов
	$("#panel_body").hide();
	$("#" + elem.id).hide();
	$("#search_input").val("");
	$("#search_input").hide();
    choosenAuthors.push( elem.id.slice(6) );
    showArrayAutors( $("#authors_panel_list") );
}

function searchElements(selector, autBool, arr, str){ //Поиск по значению
	var name = "";
	var id = 0;
	$(selector).each(function(index, value){
		name = "";
		switch (autBool){
			case 0:	id = value.id.slice(6);
					name = arr[id];
					break;
			case 1:	name = value.children[1].value;
					break;
			case 2:	id = value.id.slice(4);
					$.each(arr, function(ind, val){
						if (val["book_id"] == id) {name = val["title"]; }
					});
					break;
		}
		
		if (name.toLowerCase().indexOf(str) != 0) 
			$(this).hide();
		else
			switch (autBool){
				case 0: if (choosenAuthors.find(function(ind, val){return ind == id}) == undefined)	
							$(this).show();
						break;
				case 1: $(this).show();
						break;
				case 2: if (chooseBook != id) 
							$(this).show();
						else 
							$(this).hide();
						break;
			}
	});
}

function find(autBool, arr, selector, str){ //Отбор по авторам/книгам
	if ( str == "" ) {
		$(selector + ":hidden").each(function(index, value){ 
			if (choosenAuthors.find(function(ind,val){
				return ind == value.id.slice(6)
			}) == undefined) $(this).show(); 
		});
	}else searchElements(selector, autBool, arr, str);
}

function chooseGenre(elem){ //Отбор по жанрам
	var jQObject = $("#" + elem.id);
    if( jQObject.hasClass("btn-success") ){
        jQObject.removeClass("btn-success");
        jQObject.addClass("btn-danger");
		var genre_id = allGenres[elem.id.slice(6)]["genre_id"];
		
        choosenGenres = choosenGenres.filter(function(el){
			return el != genre_id;
		});
    }else{
        jQObject.addClass("btn-success");
        jQObject.removeClass("btn-danger");
        choosenGenres.push( allGenres[elem.id.slice(6)]["genre_id"] );
    }
}

function selectBook(elem){ //выбор книги
	$("#title").val( $(elem).html().split(" [")[0] );
	chooseBook = elem.id.slice(4);
	$("#books").hide();
	fillBookAttr();
}

function fillBookAttr(){ //заполнения авторов/жанров значениями выбранной книги
	var func = function(msg){
		var data = JSON.parse(msg)[0];
		$("#description").html( data["description"] );
		$("#price").val( data["price"] );
		choosenGenres = [];
		$.each(data["genres"], function(index, value){
			choosenGenres.push(value["genre_id"]);
		});
		$("#genres_list_search").html("");
		createAllGenresList( $("#genres_list_search") );
		
		choosenAuthors = [];
		$.each(data["authors"], function(index, value){
			choosenAuthors.push(value["author_id"]);
		});
		showArrayAutors( $("#authors_panel_list") );

	};
	getFromAjax("POST", "php/getBooksList.php", "id=5&book_id_select=" + chooseBook, func);
}
//==========================КОНЕЦ  ФУНКЦИИ ПО РАБОТЕ С ОТБОРАМИ===============================

//========================НАЧАЛО ДОБАВЛЕНИЕ/ИЗМЕНЕНИЕ КНИГИ В БД==============================
function buttonClick(){ //нажатие на кнопку добавления/изменения/удаления
	if( $("#add").hasClass("active") )
		addBook();
	else if( $("#change").hasClass("active") )
		changeBook();
	else if( $("#changeGenre").hasClass("active") )
		$('#genresGroup span input:checked').each(function(index, value){
			changeGenreAuthor(1, 
						this.id.slice(8), 
						getName(allGenres, "genre", this.id.slice(8)), 
						"genre",
						index > 0);
		});
	else if( $("#changeAuthor").hasClass("active") )
		$('#authorsGroup span input:checked').each(function(index, value){
			changeGenreAuthor(1, 
						this.id.slice(9), 
						getName(allAuthors, "author", this.id.slice(9)), 
						"author", 
						index > 0);
		});
	else if( $("#delete").hasClass("active") )
		if( confirm("Удалить выбранные книги? (" + $('#deleteBook span input:checked').length + ")") )
			deleteBook();

}

function checkBooksParams(){ //Проверка заполненности параметров
	var msg = "";
	var result = true;
	if( $("#title").val().trim() == "" ){
		msg += 'Название книги не заполнено!';
		$("#title").css("background-color", "#F49393");
		result = false;
	}
	
	if(choosenAuthors.length == 0){
		if(!result) msg += "\n";
			else result = false;
		msg += 'Книгу должен написать хотя бы один автор!!!';
	}
	
	if(choosenGenres.length == 0){
		if(!result) msg += "\n"; 
			else result = false;
		msg += 'Книга должна содержать хотя бы один жанр!!!';
	}
	
	if(!result)
		alert(msg);
	
	return result;
}

function editName(elem){ //изменить имя жанра/автора
						//либо пометить на удаление книгу
	if (elem.id.indexOf("genre") + 1 > 0){
		//var _id = elem.id.slice(14);
		$(elem).css("z-index", 0);
		$("#genre_name" + elem.id.slice(14) ).focus();
	}else if(elem.id.indexOf("author") + 1 > 0){
		//var _id = ;
		$(elem).css("z-index", 0);
		$("#author_name" + elem.id.slice(15) ).focus();
	}else{
		$("input[type='checkbox']").each(function(ind, val){ 
			if(val.id == "book_id" + elem.id.slice(13))
				this.checked = !this.checked;
		})
	}
}

function getBookAuthors(book_id){ //получить строку авторов книги
	var _authors = "";
	$.each(books, function(index, value){
		if (value["book_id"] == book_id){
			$.each(value["authors"], function(ind, val){
				_authors += (_authors.length>0?", ":"");
				_authors += val["author_name"];
			})
		}
	})
	return " [" + _authors + "]";
}

function fillGenresAuthorsBooks(str_object, str_id, arr){ //отобразить жанры/авторы/книги на странице
	$(str_object).html("");
	$.each(arr, function(index, value){
		var _id = (str_id != "author"?value[str_id + "_id"]:_id = index);
		
		var _name = "";
		if(str_id == "genre")
			_name = value[str_id + "_name"];
		else if(str_id == "author")
			_name = value;
		else{
				_name = value["title"] + getBookAuthors(value["book_id"]);
			}
		
		var spanInputGroupAddon = $("<span>", {class: "input-group-addon"})
			.append( $("<input>", {type: "checkbox", 
								   id: str_id + "_id" + _id}) );
			
		var divInputGroup = $("<div>", {class: "input-group block"})
			.append(spanInputGroupAddon)
			.append( $("<input>", {type: "button", 
								  id: str_id + "_name_btn" + _id,
								  name: str_id + "_name_btn",
								  class: "btn btn-warning",
								  value: _name,
								  style: "position:absolute; z-index:99",
								  onclick: "editName(this)"}))
			.append($("<input>", {type: "text", 
								  id: str_id + "_name" + _id,
								  class: "form-control",
								  value: _name,
								  onfocusout: "focusOut(this, '" + str_id + "')"}));
			
		$(str_object)
			.append(divInputGroup);
	});
	$(str_object + " input[type=button]").outerWidth( $(str_object + " input[type=text]").outerWidth() );
	
	if(str_id == "genre")
		getListOfAllGenres();
	else if(str_id == "author")
		getListOfAllAuthors();
	else if(str_id == "book")
		getAllBooks();
}

function focusOut(elem, str_id){ //при уходе фокуса с элемента редактирования имени жанра/автора
	if (str_id == "genre"){
		var arr = allGenres; 
		var id = elem.id.slice(10);
	}else{
		var arr = allAuthors;
		var id = elem.id.slice(11);
	}
	var oldName = getName(arr, str_id, id);
	var newName = $(elem).val().trim();
	if ( oldName != newName )
		if (str_id == "genre"){
			if ( confirm("Изменить название жанра?") ){
				changeGenreAuthor(3, id, newName, "genre");
				$(elem).val("");
				find(3, 
						allGenres, 
						"changeGenresGroup div", 
						$(elem).val().trim().toLowerCase());
			}
		}
		else {
			if ( confirm("Изменить ФИО автора?") ){
				changeGenreAuthor(3, id, newName, "author");
			}
		}
	else {
		$(elem).val(oldName);
	}
	if (str_id == "genre")
		$("#genre_name_btn" + id).css("z-index", 99);
	else 
		$("#author_name_btn" + id).css("z-index", 99);
}

function findAndDisplayBook(book_id){
	var _id;
	$.each(books, function(ind, val){
		if(book_id == val["book_id"]){
			_id = ind;
			$("#result").html( 
				$("#result").html() + 
				"<br/>    <b>" + val["title"] + "</b>"
			); 
		}
	});
	return _id;
}

function deleteBook(){
	var _arrBookId = [];
	$('#deleteBook span input:checked').each(function(index, value){
		_arrBookId.push( this.id.slice(7) );
	});

	var func = function(msg){
		data = JSON.parse(msg);
		$("#result").show();
		$("#result").html("");
		$("#result").removeClass("alert-success");
		$("#result").removeClass("alert-danger");
		$("#result").removeClass("alert-warning");
		if(data["deleting"].length > 0 && data["notDeleting"].length > 0)
			$("#result").addClass("alert-warning");
		else if(data["deleting"].length > 0)
			$("#result").addClass("alert-success");
		else if(data["notDeleting"].length > 0)
			$("#result").addClass("alert-danger");
		else
			$("#result").hide();
			
		var message = "";
		
		if(data["deleting"].length > 0){
			$("#result").html("Удаленные книги:");
			$.each(data["deleting"], function(index, value){
				delete books[ findAndDisplayBook(value) ];
			});
		}
		
		fillGenresAuthorsBooks("#deleteBooks", "book", books);
		createDataList( $("#books") );
		
		if(data["notDeleting"].length > 0){
			$("#result").html("Книги не удалены:");
			$.each(data["notDeleting"], function(index, value){
				findAndDisplayBook();
			});
		}
	}
	
	getFromAjax("POST", 
		"php/deleteBook.php", 
		'data=' + JSON.stringify( {"book_id": _arrBookId} ), 
		func );
}

function changeGenreAuthor(id_operation, _id, _name, table, rep){ //изменить наименование жанра/автора в БД
	/*	1 - удалить
		2 - добавить
		3 - изменить	*/

	var _id = _id || 0;
	var _name = _name || "";
	if(id_operation == 2)_name = $("#" + table + "_name").val();
	$("#" + table + "_name").val("");
	var repeat = rep || false;
	
	var func = function(msg){
		$("#result").show();
		$("#result").removeClass("alert-success");
		$("#result").removeClass("alert-danger");
		
		var data = JSON.parse(msg);
		if (data["res"]) $("#result").addClass("alert-success");
			else $("#result").addClass("alert-danger");
		if (!repeat)
			$("#result").html( data["message"] );
		else{
			$("#result").append("<br />");
			$("#result").html( $("#result").html() + data["message"] );
		}
			
		if(!data["res"]){
			if(id_operation == 1){
				//в arr_id - номера книг, которые необходимо отобразить
				if (data["arr_id"].length > 0){
					if (table == "genre")
						var name = "Жанр <b>" + getName(allGenres, "genre", _id) + "</b> содержится в книгах";
					else 
						var name = "Автор <b>" + getName(allAuthors, "author", _id) + "</b> написал книги:";
					var h2_text = $("<h2>").html(name);
					$("#result")
							.append( $("<br>") )
							.append( h2_text )
							.append( $("<br>") );
					$.each(data["arr_id"], function(index, value){
						$("#result")
							.append( $("<a>", { href: "book.php?book_id=" + value,
												text: getName(books, "", value)}) )
							.append( $("<br>") );
					});
				}
			}
		}
		else { 
			if(id_operation == 1){
				if (table == "genre")
					$.each(allGenres, function(index, value){
						if(value["genre_id"] == _id){
							delete allGenres[index];
							getListOfAllGenres();
						}
					});
				else 
					$.each(allAuthors, function(index, value){
						if(index == _id){
							delete allAuthors[index];
							getListOfAllAuthors();
						}
					});	
			}
			if(id_operation == 2){
				//в arr_id - номер последней добавленной записи
				if (table == "genre"){
					allGenres[ data["arr_id"] ] = {
													"genre_id": data["arr_id"],
													"genre_name": _name,
													"book_quantity": 0
												};
					getListOfAllGenres();
				}else{
					allAuthors[ data["arr_id"] ] = author_name;
					getListOfAllAuthors();
				}
			}else if(id_operation == 3){
				if (table == "genre")
					$.each(allGenres, function(index, value){
						if (value["genre_id"] == _id){
							value["genre_name"] = _name;
							getListOfAllGenres();
						}
					});	
				else 
					$.each(allAuthors, function(index, value){
						if (index == _id){
							allAuthors[index] = _name;
							getListOfAllAuthors();
						}
					});
			}
		};

		fillGenresAuthorsBooks(
					(table == "genre"?"#changeGenresGroup":"#changeAuthorsGroup"),
					table,
					(table == "genre"?allGenres:allAuthors) );
	};
	
	getFromAjax("POST",
				"php/addChangeGenreAuthor.php",
				'data=' + JSON.stringify( {"id_operation": id_operation,
										   "_id": _id,
										   "_name": _name,
										   "_table": table} ),
				func );
}

function getName(arr, AutGen, val){ //получить имя автора / название жанра / название книги
	var name = "";
	if (AutGen == "author")
		$.each(arr, function(index, value){
			if (index == val)name = value;
		});
	else if(AutGen == "genre")
		$.each(arr, function(index, value){
			if (value["genre_id"] == val)name = value["genre_name"];
		});
	else 
		$.each(arr, function(index, value){
			if (value["title"] == val || value["book_id"] == val)name = value["title"];
		});
	return name;
}

function parseArrToShowUser(infoArr, arr, AutGen, addRemove){ //информационное сообщение пользователю
	var infoForUser = "";
	if(infoArr.length == 0) 
		return "";
	else{
		infoForUser += "<b>";
		infoForUser += AutGen == "author" ? "Авторы" : "Жанры";
		infoForUser += addRemove ? " добавлены:" : " удалены:";
		infoForUser += "</b><br />";
		
	}
	$.each(infoArr, function(index, val){
		infoForUser += getName(arr, AutGen, val) + ", ";
	});
	infoForUser += "<br />";
	return infoForUser;
}

function changeBook(){ //изменение параметров книги
	
	if(!checkBooksParams())return;
	
	var bookUpdate = {};
	bookUpdate.book_id 		= chooseBook;
	bookUpdate.newBookInfo	= {
		"title":		$("#title").val(),
		"description":	$("#description").val(),
		"price":		$("#price").val()
	};
	bookUpdate.newAuthors	= choosenAuthors;
	bookUpdate.newGenres	= choosenGenres;
	
	var func = function(msg){
		$("#result").show();
		var data = JSON.parse(msg);
		var infoForUser = "";
		$.each(data, function(key, value){
			$.each(value["newParamBook"], function (index, val){
				if(index == "title") infoForUser += "<b>Название</b> изменено на <i><b>" + val + "</b></i><br />";
				if(index == "description") infoForUser += "<b>Описание</b> изменено на <i><b>" + val + "</b></i> грн.<br />";
				if(index == "price") infoForUser += "<b>Цена</b> изменена на <i><b>" + val + "</b></i><br />";
			});
			
			infoForUser += parseArrToShowUser(value["addedAuthors"], 	allAuthors, "author", true);
			infoForUser += parseArrToShowUser(value["removedAuthors"], 	allAuthors, "author", false);
			infoForUser += parseArrToShowUser(value["addedGenres"], 	allGenres, 	"genre",  true);
			infoForUser += parseArrToShowUser(value["removeGenres"], 	allGenres, 	"genre",  false);
		});
		$("#result").html( infoForUser );
	};
	
	getFromAjax("POST", "php/changeBook.php", 'dataUpdate=' + JSON.stringify(bookUpdate), func );
}

function addBook(){ //добавление книги в базу данных
	
	if(!checkBooksParams())return;

	ajaxData = {};
	ajaxData.title 		 = $("#title").val();
	ajaxData.description = $("#description").val();
	ajaxData.price 		 = $("#price").val();
	ajaxData.authors 	 = choosenAuthors;
	ajaxData.genres 	 = choosenGenres;
	
	$("#title").val("");
	$("#description").val("");
	$("#price").val("");
	choosenAuthors = []; //очистили массив
	showArrayAutors( $("#authors_panel_list") ); //очистили выбранных авторов в интерфейсе
	
	choosenGenres  = [];
	$("#genres_list_search .btn-success").each(function(index){
		$(this).removeClass("btn-success");
        $(this).addClass("btn-danger");
	});
	
	var func = function(msg){
		$("#result").html(msg);
		$("#result").show();
		getAllBooks();
	};
	
	getFromAjax("POST", "php/addBook.php", 'dataRequest=' + JSON.stringify(ajaxData), func );
}

//========================КОНЕЦ  ДОБАВЛЕНИЕ/ИЗМЕНЕНИЕ КНИГИ В БД==============================

