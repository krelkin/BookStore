;
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
	jQObject.html("");
	$.each(allGenres, function(index, value){
		var c = "btn btn-danger block";
		$.each(choosenGenres, function(index, valueChooseGenre){
			if (valueChooseGenre == value["genre_id"]) c = "btn btn-success block";
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
		allGenres = {};
		fillContentArrayFromJSON(msg, allGenres);
		createAllGenresList( $("#genres_list_search") );
	}
	var json = 'json_data=' + JSON.stringify({"function_name":"getList","field":"genre","get_json":true});
	getFromAjax("POST", "php/functions.php", json, fillAllGenres);
}

function createAllAuthorsList(jQObject){ //Заполнение всех авторов для фильтров
	jQObject.html("");
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
		allAuthors = {};
		fillContentArrayFromJSON(msg, allAuthors, true, "author");
		createAllAuthorsList( $("#authors_list_search") );
	}
	var json = 'json_data=' + JSON.stringify({"function_name":"getList","field":"author","get_json":true});
	getFromAjax("POST", "php/functions.php", json, fillAllAuthors);
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
		books = {};
		fillContentArrayFromJSON(msg, books);
		createDataList( $("#books") );
	}
	
	var json = "json_data=" + JSON.stringify({"function_name":"getBooksList", "id":"4"});
	getFromAjax("POST", "php/functions.php", json, fillDataList);
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
		var data = JSON.parse(msg);
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
	var json = "json_data=" + JSON.stringify({
					"function_name":"getBooksList",
					"id":"5",
					"book_id_select":chooseBook
					});
	getFromAjax("POST", "php/functions.php", json, func);
}
//==========================КОНЕЦ  ФУНКЦИИ ПО РАБОТЕ С ОТБОРАМИ===============================

//========================НАЧАЛО ДОБАВЛЕНИЕ/ИЗМЕНЕНИЕ КНИГИ В БД==============================
function buttonClick(){ //нажатие на кнопку добавления/изменения/удаления
	if( $("#add").hasClass("active") )
		addBook();
	else if( $("#change").hasClass("active") )
		changeBook();
	else if( $("#changeGenre").hasClass("active") ){
		var ids = new Array();
		$('#genresGroup span input:checked').each(function(ind, el){
			ids.push(this.id.slice(8))
		});
		changeGenreAuthor(1, ids, "", "genre");
	}else if( $("#changeAuthor").hasClass("active") ){
		var ids = new Array();
		$('#authorsGroup span input:checked').each(function(ind, el){
			ids.push(this.id.slice(9))
		});
		changeGenreAuthor(1, ids, "", "author");
	}else if( $("#delete").hasClass("active") )
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
		$(elem).css("z-index", 0);
		$("#genre_name" + elem.id.slice(14) ).focus();
	}else if(elem.id.indexOf("author") + 1 > 0){
		$(elem).css("z-index", 0);
		$("#author_name" + elem.id.slice(15) ).focus();
	}else{
		$("input[type='checkbox']").each(function(ind, val){ 
			if(val.id == "book_id" + elem.id.slice(13))
				this.checked = !this.checked;
		})
	}
}

function getObjectLength(Obj){ //получить количество элементов в объекте
	var length = 0;
	$.each(Obj, function(){length++;});
	return length;
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

function showUserMessage(data){ //отобразить сообщение пользователю
	var message = "";
	var arr = new Array();
	
	$("#result").removeClass("alert-success");
	$("#result").removeClass("alert-warning");
	$("#result").removeClass("alert-danger");
	
	if("result" in data){
		if(data["result"]){
			$("#result").addClass("alert-success");
		}else{
			$("#result").addClass("alert-danger");
			if("deleted" in data){
				var deleted = data["deleted"]["book_id"].length > 0 
					|| data["deleted"]["author_id"].length > 0
					|| data["deleted"]["genre_id"].length > 0;
				var not_deleted = getObjectLength(data["not_deleted"]["book_id_author"]) > 0 
					|| getObjectLength(data["not_deleted"]["book_id_genre"]) > 0;
				if(deleted && not_deleted){
					$("#result").removeClass("alert-warning");
					$("#result").addClass("alert-warning");
				}
			}
		}
	}else{
		$("#result").addClass("alert-success");
	}
	
	//добавленные данные
	if("inserted" in data){
		inserted_data = data["inserted"];
		if(inserted_data["book_id"] > 0){ //была добавлена книга
			message += "Была добавлена книга:<br>";
			message += "Наименование: <strong>" + inserted_data["data_book"]["title"] + "</strong><br>";
			message += "Описание: <strong>" + inserted_data["data_book"]["description"] + "</strong><br>";
			message += "Цена: <strong>" + inserted_data["data_book"]["price"] + " грн.</strong><br>";
			
			message += "<strong>Авторы</strong>:<br>";
			message += "<ul>";
			arr = [];
			$.each(inserted_data["author_id"], function(index, value){
				arr.push(getName(allAuthors, "author", value));
			});
			message += "<li>" + arr.join("</li><li>") + "</li>";
			message += "</ul>";
			
			arr = [];
			message += "<strong>Жанры</strong>:<br>";
			message += "<ul>";
			$.each(inserted_data["genre_id"], function(index, value){
				arr.push(getName(allGenres, "genre", value));
			});
			message += "<li>" + arr.join("</li><li>") + "</li>";
			message += "</ul>";
		}else{//если добавлялась не книга, а автор/жанр
			var author_length = 0;
			var genre_length = 0;
			$.each(inserted_data["author_id"], function(){author_length++});
			$.each(inserted_data["genre_id"],  function(){genre_length++});
			if(author_length > 0){//добавлялся автор
				message += "Добавлен автор:<br>";
				$.each(inserted_data["author_id"], function(index, value){
					message += "<strong>" + value + "</strong>";
				});
				message += "<br>";
			}
			if(genre_length > 0){//добавлялся жанр
				message += "Добавлен жанр:<br>";
				$.each(inserted_data["genre_id"], function(index, value){
					message += "<strong>" + value + "</strong>";
				});
				message += "<br>";
			}
		}
	}
	if("deleted" in data){
		var deleted_data = data["deleted"];
		if(deleted_data["book_id"].length > 0){ // если удалили книгу(и) или из книги
			if(deleted_data["author_id"].length == 0 && deleted_data["genre_id"].length == 0){ //удалили непосредственно книгу
				message += "Удалили книгу:<br>";
				message += "<ul>";
				arr = [];
				$.each(deleted_data["book_id"], function(index, value){
					arr.push("<strong>" + getName(books, "book", value) + "</strong>");
				});
				message += "<li>" + arr.join("</li><li>") + "</li>";
				message += "</ul>";
			}else{
				if(deleted_data["author_id"].length > 0){// если удалили авторов из книги
					arr = [];
					message += "Удалили автора:<br>";
					$.each(deleted_data["author_id"], function(index, value){
						arr.push("<strong>" + getName(allAuthors, "author", value) + "</strong>");
					});
					message += "<li>" + arr.join("</li><li>") + "</li>";
					message += "</ul>";
				}
				if(deleted_data["genre_id"].length > 0){// если удалили жанры из книги
					arr = [];
					message += "Удалили жанр:<br>";
					message += "<ul>";
					$.each(deleted_data["genre_id"], function(index, value){
						arr.push("<strong>" + getName(allGenres, "genre", value) + "</strong>");
					});
					message += "<li>" + arr.join("</li><li>") + "</li>";
					message += "</ul>";
				}
			}
		}else{ // если удалили автора(ов)/жанр(ы)
			if(deleted_data["author_id"].length > 0){// если удалили авторов
				arr = [];
				message += "Удалили автора:<br>";
				message += "<ul>";
				$.each(deleted_data["author_id"], function(index, value){
					arr.push("<strong>" + getName(allAuthors, "author", value) + "</strong>");
				});
				message += "<li>" + arr.join("</li><li>") + "</li>";
				message += "</ul>";
			}
			if(deleted_data["genre_id"].length > 0){// если удалили жанры
				arr = [];
				message += "Удалили жанр:<br>";
				message += "<ul>";
				$.each(deleted_data["genre_id"], function(index, value){
					arr.push("<strong>" + getName(allGenres, "genre", value) + "</strong>");
				});
				message += "<li>" + arr.join("</li><li>") + "</li>";
				message += "</ul>";
			}
		}
	}
	if("not_deleted" in data){
		var not_deleted_data = data["not_deleted"];
		var author_length = 0;
		var genre_length = 0;
		$.each(not_deleted_data["book_id_author"], function(){author_length++});
		$.each(not_deleted_data["book_id_genre"],  function(){genre_length++});

		if(author_length > 0){// если не смогли удалить авторов
			message += "<strong>Не удалось удалить авторов</strong>:<br>";
			message += "<ul>";
			var msg = "";
			$.each(not_deleted_data["book_id_author"], function(index, value){
				msg = '<ul>Автор <strong><i>"' + getName(allAuthors, "author", index) + '"</i></strong> прикреплён к книгам:';
				$.each(not_deleted_data["book_id_author"][index], function(ind, val){
					msg += "<li><a href='book.php?book_id=" + val["book_id"] + "'>";
					msg += "<strong>" + getName(books, "book", val["book_id"]) + "</strong>";
					msg += "</a></li>";
				});
				msg += "</ul>";
				arr.push(msg);
			});
			message += "<li>" + arr.join("</li><li>") + "</li>";
			message += "</ul>";
		}
		if(genre_length > 0){// если удалили жанры
			arr = [];
			message += "<strong>Не удалось удалить жанры</strong>:<br>";
			message += "<ul>";
			var msg = "";
			$.each(not_deleted_data["book_id_genre"], function(index, value){
				msg = '<ul>Жанр <strong><i>"' + getName(allGenres, "genre", index) + '"</i></strong> прикреплён к книгам:';
				$.each(not_deleted_data["book_id_genre"][index], function(ind, val){
					msg += "<li><a href='book.php?book_id=" + val["book_id"] + "'>";
					msg += "<strong>" + getName(books, "book", val["book_id"]) + "</strong>";
					msg += "</a></li>";
				});
				msg += "</ul>";
				arr.push(msg);
			});
			message += "<li>" + arr.join("</li><li>") + "</li>";
			message += "</ul>";
		}
	}
	if("updated" in data){
		var updated_data_books 	= data["updated"]["books"];
		var updated_data_authors= data["updated"]["authors"];
		var updated_data_genres	= data["updated"]["genres"];
		
		var books_length = 0;
		var authors_length = 0;
		var genres_length = 0;
		$.each(updated_data_books, function(ind, val){
			books_length++;});
		$.each(updated_data_authors, function(ind, val){
			authors_length++;});
		$.each(updated_data_genres, function(ind, val){
			genres_length++;});
			
		if(books_length > 0){ // если изменяли атрибуты книги
			if(updated_data_books["title"]!==undefined){
				message += 'Название изменено с <strong>"'
				+ updated_data_books["old_title"]
				+ '"</strong> на <strong>"'
				+ updated_data_books["title"]
				+ '"</strong><br>';
			}
			if(updated_data_books["description"]!==undefined){
				message += 'Описание изменено с <strong>"'
				+ updated_data_books["old_description"]
				+ '"</strong> на <strong>"'
				+ updated_data_books["description"]
				+ '"</strong><br>';
			}
			if(updated_data_books["price"]!==undefined){
				message += 'Цена изменена с <strong>"'
				+ updated_data_books["old_price"]
				+ '"</strong> на <strong>"'
				+ updated_data_books["price"]
				+ '"</strong><br>';
			}
		}
		if(message.length > 0) message += "<br><br>";
		if(updated_data_authors["added"] !== undefined){//если изменяли авторов книги
			if(updated_data_authors["added"].length > 0){ //если добавились новые авторы
				arr = [];
				message += "Добавлены авторы:";
				message += "<ul>";
				$.each(updated_data_authors["added"], function(ind, value){
						arr.push("<strong>" + getName(allAuthors, "author", value) + "</strong>");
				});
				message += "<li>" + arr.join("</li><li>") + "</li>";
				message += "</ul><br>";
			}
			if(updated_data_authors["removed"].length > 0){ //если удалили старых авторов
				arr = [];
				message += "Удалены авторы:";
				message += "<ul>";
				$.each(updated_data_authors["removed"], function(ind, value){
						arr.push("<strong>" + getName(allAuthors, "author", value) + "</strong>");
				});
				message += "<li>" + arr.join("</li><li>") + "</li>";
				message += "</ul><br>";
			}
		}else{ // если изменяли авторов
			if(authors_length > 0){
				message += "Название автора изменено с ";
				$.each(updated_data_authors, function(index, value){
					var key = Object.keys(value)[0];
					message += '"<strong>' + key + '</strong>" на "<strong>' + value[key] + '</strong>"';
				});
			}
		}
		if(updated_data_genres["added"] !== undefined){//если изменяли жанры книги
			if(updated_data_genres["added"].length > 0){ //если добавились новые жанры
				arr = [];
				message += "Добавлены жанры:";
				message += "<ul>";
				$.each(updated_data_genres["added"], function(ind, value){
						arr.push("<strong>" + getName(allGenres, "genre", value) + "</strong>");
				});
				message += "<li>" + arr.join("</li><li>") + "</li>";
				message += "</ul><br>";
			}
			if(updated_data_genres["removed"].length > 0){ //если удалили старые жанры
				arr = [];
				message += "Удалены жанры:";
				message += "<ul>";
				$.each(updated_data_genres["removed"], function(ind, value){
						arr.push("<strong>" + getName(allGenres, "genre", value) + "</strong>");
				});
				message += "<li>" + arr.join("</li><li>") + "</li>";
				message += "</ul><br>";
			}
		}else{ // если изменяли жанры
			if(genres_length > 0){
				message += "Название жанра изменено с ";
				$.each(updated_data_genres, function(index, value){
					var key = Object.keys(value)[0];
					message += '"<strong>' + key + '</strong>" на "<strong>' + value[key] + '</strong>"';
				});
			}
		}
	}
	$("#result").html( message );
	$("#result").show();
	
}

function changeArrays(data, book_id = 0){ //изменить массивы после изменения базы данных
	if(!("deleted" in data || data["result"])) return;
	var changed_books	= false;
	var changed_genres	= false;
	var changed_authors	= false;
	
	if("inserted" in data){
		/*{
		"result":true,
		v1. "inserted":
			{"book_id":0,
			 "data_book":[],
			 "author_id":{"inserted id":"inserted name"},
			 "genre_id":{"inserted id":"inserted name"}
			}
		/*****************************************************************
		v2. "inserted":
			{"book_id":28,
			 "data_book":{
				"title":"test",
				"description":"description",
				"price":"1"
			 },
			 "author_id":["id"],
			 "genre_id":["id"]
			}
			
		}
		*/
		
		var inserted_data = data["inserted"];
		if(inserted_data["book_id"] > 0){ //добавили новую книгу
			//необходимо изменять массив books и инициировать перезапись области Select со всеми книгами для выбора
			var book = {};
			book["book_id"]	= inserted_data["book_id"];
			book["title"] 	= inserted_data["data_book"]["title"];
			book["description"] = inserted_data["data_book"]["description"];
			book["price"] 	= inserted_data["data_book"]["price"];
			book["authors"]	= {};
			var i = 0;
			$.each(inserted_data["author_id"], function(index, value){
				book["authors"][i++] = {"author_name": getName(allAuthors, "author", value)};
			});
			i = 0;
			$.each(books, function(){i++;});
			books[i] = book;
			changed_books = true;
		}else{//добавили новый жанр или нового автора
			if(getObjectLength(inserted_data["author_id"]) > 0){
				//изменяем массив allAuthors и запускаем перезапись списков, где есть авторы
				$.each(inserted_data["author_id"], function(index, value){
					allAuthors[index] = value;
				});
				changed_authors = true;
			}
			if(getObjectLength(inserted_data["genre_id"]) > 0){
				//изменяем массив allGenres и запускаем перезапись списков, где есть авторы
				$.each(inserted_data["genre_id"], function(index, value){
					allGenres[getObjectLength(allGenres)] = {"genre_id": index, "genre_name":value};
				});
				changed_genres = true;
			}
		}
		
	}
	if("updated" in data){
		/*
		"updated":{
			"books":{
				"title":"new_title",
				"old_title":"old_title",
				"description":"description",
				"old_description":"old description",
				"price":"8",
				"old_price":"57"
			},
			"authors":{
				"added":["13"],
				"removed":["2","1"]
			},
			"genres":{
				"added":["1"],
				"removed":["9"]}
		}
		
		"updated":{
			"books":[],
			"authors":[],
			"genres":{
				"id":{"old_name":"new_name"}
			}
		}
		*/
		var updated_data = data["updated"];
		if(book_id > 0){ //изменили существующую книгу (передали id выбранной книги)
			//меняем массив books
			object_book_index = 0;
			$.each(books, function(index, value){
				if(value["book_id"] == book_id)object_book_index = index; // получили индекс "ассоциативного" массива
			});
			
			if(!Array.isArray(updated_data["books"])){ // поменяли атрибуты книги
				$.each(updated_data["books"], function(index, value){
					books[object_book_index][index] = value;
				});
			}
			$.each(updated_data["authors"]["added"], function(index, value){
				books[object_book_index]["authors"].push({"author_name": getName(allAuthors, "author", value) });
			});
			$.each(updated_data["authors"]["removed"], function(index, value){
				books[object_book_index]["authors"] = books[object_book_index]["authors"].filter(function(el){
					return el["author_name"] != getName(allAuthors, "author", value);
				});
			});
			changed_books = true;
		}else{//изменили автора либо жанр
			if(getObjectLength(updated_data["authors"]) > 0){
				//меняем массив allAuthors
				$.each(updated_data["authors"], function(index, value){
					allAuthors[index] = value[Object.keys(value)[0]];
				});
				changed_authors = true;
			}
			if(getObjectLength(updated_data["genres"]) > 0){
				//меняем массив allGenres
				$.each(updated_data["genres"], function(index, value){
					var genre_index = 0;
					$.each(allGenres, function(ind, val){
						if(val["genre_id"] == index)genre_index = ind;
					});
					allGenres[genre_index]["genre_name"] = value[Object.keys(value)[0]];
				});
				changed_genres = true;
			}

		}
	}
	if("deleted" in data){
		/*
		"deleted":{
			"book_id":["id"],
			"author_id":["id"],
			"genre_id":["id"]
		}
		*/
		var deleted_data = data["deleted"];
		if(deleted_data["book_id"].length > 0){
			//изменяем массив books
			$.each(deleted_data["book_id"], function(index, value){
				$.each(books, function(ind, val){
					if(val["book_id"] == value){
						delete books[ind];
					}
				});
			});
			changed_books = true;
		}
		if(deleted_data["author_id"].length > 0){
			$.each(deleted_data["author_id"], function(index, value){
				delete allAuthors[value];
			});
			changed_authors = true;
		}
		if(deleted_data["genre_id"].length > 0){
			$.each(deleted_data["genre_id"], function(index, value){
				$.each(allGenres, function(ind, val){
					if(val["genre_id"] == value){
						delete allGenres[ind];
					}
				});
			});
			changed_genres = true;
		}
	}
	
	if(changed_books){
		createDataList( $("#books") );
		fillGenresAuthorsBooks("#deleteBooks", "book", books);
	}
	if(changed_authors){
		createAllAuthorsList( $("#authors_list_search") );
		fillGenresAuthorsBooks("#changeAuthorsGroup", "author", allAuthors);
	}
	if(changed_genres){
		createAllGenresList( $("#genres_list_search") );
		fillGenresAuthorsBooks("#changeGenresGroup", "genre", allGenres);
	}
}

function changeGenreAuthor(id_operation, _id, _name, table, rep){ //изменить наименование жанра/автора в БД
	/*	1 - удалить
		2 - добавить
		3 - изменить	
	*/

	var _id = _id || 0;
	var new_name = _name || "";
	if(id_operation == 2)new_name = $("#" + table + "_name").val();
	$("#" + table + "_name").val("");
	var repeat = rep || false;
	
	var func = function(msg){
		var data = JSON.parse(msg);
		showUserMessage(data);
		changeArrays(data);
		
		/*fillGenresAuthorsBooks(
					(table == "genre"?"#changeGenresGroup":"#changeAuthorsGroup"),
					table,
					(table == "genre"?allGenres:allAuthors) );
	*/};
	
	var json = {};

	switch(id_operation){
		case 1: json.function_name = "deleteBookAuthorGenre";
				json[table+"s"] = _id;
			break;
		case 2: json.function_name = "addBookAuthorGenre";
				json[table+"s"] = _id==0?new Array(new_name):_id;
			break;
		case 3: json.function_name = "updateBookAuthorGenre";
				json[table+"s"] = {};
				json[table+"s"][_id] = new_name;
	}
	
	getFromAjax("POST",
				"php/functions.php",
				'json_data=' + JSON.stringify(json),
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

function changeBook(){ //изменение параметров книги
	
	if(!checkBooksParams())return;
	
	var bookUpdate = {};
	bookUpdate.function_name= "updateBookAuthorGenre";
	bookUpdate.books = {};
	bookUpdate.books.book_id = chooseBook;
	bookUpdate.books.title   = $("#title").val();
	bookUpdate.books.description = $("#description").val();
	bookUpdate.books.price = $("#price").val();
	bookUpdate.books.authors	= choosenAuthors;
	bookUpdate.books.genres	= choosenGenres;
	
	var func = function(msg){
		var data = JSON.parse(msg);
		showUserMessage(data);
		changeArrays(data, chooseBook);
	};
	
	var json = 'json_data=' + JSON.stringify(bookUpdate);
	getFromAjax("POST", "php/functions.php", json, func );
}

function addBook(){ //добавление книги в базу данных
	
	if(!checkBooksParams())return;

	ajaxData = {};
	ajaxData.function_name = "addBookAuthorGenre";
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
		var data = JSON.parse(msg);
		showUserMessage(data);
		changeArrays(data);
	};
	var json = "json_data=" + JSON.stringify(ajaxData);
	getFromAjax("POST", "php/functions.php", json, func );
}

function deleteBook(){ // удаление книги из базы данных
	var _arrBookId = [];
	$('#deleteBook span input:checked').each(function(index, value){
		_arrBookId.push( this.id.slice(7) );
	});

	var func = function(msg){
		data = JSON.parse(msg);
		showUserMessage(data);
		changeArrays(data);
	}
	
	var json = 'json_data=' + JSON.stringify({"function_name":"deleteBookAuthorGenre", "book_id": _arrBookId});
	getFromAjax("POST", "php/functions.php", json, func);
}

//========================КОНЕЦ  ДОБАВЛЕНИЕ/ИЗМЕНЕНИЕ КНИГИ В БД==============================

