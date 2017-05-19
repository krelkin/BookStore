var arrBooksContent  = {}; //массив книг, которые отображаются на странице после выбора
var arrGenreContent  = {}; //массив жанров, которые соответствуют отображаемым книгам на странице
var arrAuthorContent = {}; //массив авторов, которые соответствуют отображаемым книгам на странице
var allAuthors = {}; //массив, содержащий всех авторов (нужен для отбора)
var allGenres  = {}; //массив, содержащий все жанры (нужен для отбора)
var choosenAuthors = []; //массив выбранных в отбор авторов
var choosenGenres  = []; //массив выбранных в отбор жанров
var GETParams = {}; //массив данных, которые передаются на страницу методом GET
var panelClick = 0; //переменная для нормального отображения панели
var filterChange = true; //Состояние изменения фильтров

//==================================НАЧАЛО РАБОТА С СOOKIES==================================
function setCookie(name, value, days){
    var expires = "";
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days*24*60*60*1000));
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + value + expires + "; path=/";
}

function getCookie(name){
    var nameCookie = name + "=";
    var ca = document.cookie.split(';');
    for(var i=0;i < ca.length;i++) {
		var c = ca[i].trim();
        if (c.indexOf(nameCookie) == 0) return c.substring(nameCookie.length, c.length);
    }
    return undefined;
}

function eraseCookie(name){
    setCookie(name, "", -1);
}
//==================================КОНЕЦ  РАБОТА С СOOKIES==================================

//========================НАЧАЛО ФУНКЦИИ ПРИ НАЧАЛЕ ЗАГРУЗКИ СТРАНИЦЫ========================
$(document).ready(function(){ //При загрузке страницы
	quantityElemCookie = getCookie("quantityElem");
	if( quantityElemCookie == undefined ){
		$("#quantityElem").val(5);
		setCookie("quantityElem", 5, 14);
	}else{ $("#quantityElem").val( quantityElemCookie ); };
	
	orderByCookie = getCookie("orderBy");
	if(orderByCookie == undefined){
		$("#orderBy").val("book_id ASC");
		setCookie("orderBy", "book_id ASC", 14);
	}else{ $("#orderBy").val(orderByCookie); };
	
    getGETParams();
	
	var getBooksForContent = function(msg){
        fillContent( msg, $("#books_content") );
    };
	getListOfAllGenres();
	getListOfAllAuthors();
	
	$("body").click(function(){
		if (!panelClick){
			$("#authors_panel").css("border-bottom", "4px groove black");
			$("#panel_body").hide();
		}
		panelClick = 0;
	});
	
	// работа с панелью авторов
	$("#authors_panel").click(function () {
		if (!panelClick)panelClick = 1;
		if (panelClick != 1)return;
		$("#panel_body").outerWidth( $(this).outerWidth() );
		$("#search_input").show();
		$("#search_input").focus();
		if ( $("#panel_body:hidden").length == 1 ){
			$("#panel_body").show();
			$(this).css("border-bottom", "0px");
		}else{
			$("#panel_body").hide();
			$(this).css("border-bottom", "4px groove black");
		}
		findAuthors();
	});
	$(window).resize(function(){
		$("#panel_body").outerWidth( $("#authors_panel").outerWidth() );
	});
	$("#search_input").on("input", null, function(){
		$("#panel_body:hidden").show();
		$("#buffer").text( $("#search_input").val().replace(/\s/g,'x') );
		$("#search_input").width($("#buffer").width() + 1);
		$(this).css("border-bottom", "0px");
		findAuthors();
	});
	$("#search_input").focus(function(){
		$("#search_input").css("display", "inline");
	});
	$("#panel_body").click(function(){
		panelClick= true;
		$("#authors_panel").css("border-bottom", "4px groove black");
	});
	$("#title").click(function(){
		filterChange = true;
	});
	
	$("#search_input").hide();
	$("#search_input").css("width", "1px");
	$("#panel_body").hide();
});

function getGETParams(genre){ //Получение данных, переданных через GET и построение начального интерфейса
	var gen = genre || false;
	GETParams = window.location.search.replace('?','').split('&').reduce(
        function(p,e){
            var a = e.split('=');
            p[ decodeURIComponent(a[0])] = decodeURIComponent(a[1]);
            return p;
        },
        {}
    );
	if(GETParams['title']	  != undefined)title = $("#title").val(GETParams['title']);
	if(GETParams['genre_id']  != undefined)choosenGenres.push(GETParams['genre_id']);
	if(GETParams['author_id'] != undefined)choosenAuthors.push(GETParams['author_id']);
	search();
}
//========================КОНЕЦ  ФУНКЦИИ ПРИ НАЧАЛЕ ЗАГРУЗКИ СТРАНИЦЫ========================

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
	var data = eval("(" + msg + ")");
	if (!flat)
		$.each(data, function (index, value) {
			array[index] = value;
		});
	else
		$.each(data, function (index, value) {
			array[ value[tableName + "_id"] ] = value[tableName + "_name"];
		});
}
//===============================КОНЕЦ  РАБОТА С AJAX========================================

//==============================НАЧАЛО ДОБАВЛЕНИЕ КОНТЕНТА===================================

function addAttrToBooks(arr, attr){ //Добавление жанров или авторов к книгам
	$.each(arr, function(index, value){
		$("#" + attr + "_id" + value['book_id'])
			.append( $("<a>",{ class: "btn btn-" + (attr == "genre"?"warning":"success"),  //2-й класс
				  href: "searchpage.php?" + attr + "_id=" + value[attr + '_id'],//2
				  text: value[attr + '_name']//3
				}) );
	});
}

function getList(book_id, 	//массив с id книг для отображения
				arr, 		//массив жанров или авторов
				url, 		//адрес php файла
				attr		//"genre" или "author"
				){ //AJAX запрос по жанрам или авторам к книгам
	var getSearchContent = function(msg){
        fillContentArrayFromJSON(msg, arr);
		addAttrToBooks(arr, attr);
	}
	
	var json = 'dataSearch=' + JSON.stringify({get_json:true, book_id: book_id});
	getFromAjax("POST", url, json, getSearchContent);
}

function fillSearchContent(msg, jQObject) { //Добавление основного контента в массив
	fillContentArrayFromJSON(msg, arrBooksContent);
	createContent(jQObject);
}

function createContent(jQObject){ //Создание основного контента

	var arr = new Array(); //содержит book_id всех выбранных книг
	var i = 0;
	
    $.each(arrBooksContent, function(item, value) {
		
		var elemDL = $("<dl>")
            .append( $("<dt>", {text: "Авторы"}) )
			.append( $("<dd>", {class: "authors_list", id: "author_id" + value['book_id']}) ) //сюда добавить список авторов <a href="" class = "btn btn-success"> АВТОР, БЛИАТЬ!!! </a>
			.append( $("<dt>", {text: "Жанры"}) )
			.append( $("<dd>", {class: "genre_list", id: "genre_id" + value['book_id']}) ) //сюда добавить список жанров <a href="" class = "btn btn-warning"> ЖАНР, БЛИАТЬ!!! </a>
			.append( $("<dt>", {text: "Описание"}) )
			.append( $("<dd>", {text: value['description']}) );
		
		elemDL = $("<div>", {class: "col-xs-12"} )
			.append(elemDL);
			
		spanA = $("<h4>").append( $("<a>", {text: value['title'], href: "book.php?book_id=" + value["book_id"], style: "color:white"}));
		var spanTITLE = $("<span>", {class: "col-xs-12 title"})
			.append( spanA );
			
		spanTITLE = $("<div>", {class: "col-xs-12 list-thumb"})
			.append(spanTITLE);
			
		aButton = $("<a>", {class: "btn btn-info col-xs-6",
							 href:  "book.php?book_id=" + value['book_id'],
							 text:  value['price'] + " грн."});
		
		var div = $("<div>", {class: "row list-item panel-heading",
						  style: "margin: 0px"})
			.append(spanTITLE)
			.append(elemDL)
			.append($("<div>", {class: "col-xs-3"}))
			.append(aButton);
		
		div = $("<div>", {class: "panel panel-warning"})
			.append(div);

        jQObject.append(div);
		
		arr.push(value['book_id']);
		i++;
    });

	getList(arr, arrGenreContent,  "php/getGenreList.php",  "genre");
	getList(arr, arrAuthorContent, "php/getAuthorList.php", "author");
}

function createAllGenresList(jQObject){ //Заполнение всех жанров для фильтров
	$.each(allGenres, function(index, value){
		var c = "btn btn-danger block";
		$.each(choosenGenres, function(index, valueChooseGenre){
			if (valueChooseGenre == value["genre_id"]) c = "btn btn-success block";
		}); 
		jQObject.
			append( $("<a>", {
						id: "genres" + index, 
						class: c,
						text: value["genre_name"] + " [+" + value["book_quantity"] + "]", 
						onclick: "chooseGenre(this)",
						style: "display:inline-block"}) 
					);
	});
}

function getListOfAllGenres(){ // AJAX запрос по всем жанрам
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
				class: "btn btn-default col-xs-12",
				onclick: "chooseAutor(this)"
				})
			.append( $("<a>", {
							href: "#!", 
							class: "", 
							text: value
						}) 
					);
		
		jQObject.append( li );
		if ( choosenAuthors.find(function(el){return el == index;}) != undefined )
			$("#author" + index).hide();

	});
	showArrayAutors($("#authors_panel_list"));
}

function getListOfAllAuthors(){ // AJAX запрос по всем авторам
	var fillAllAuthors = function(msg){
		fillContentArrayFromJSON(msg, allAuthors, true, "author");
		createAllAuthorsList( $("#authors_list_search") );
	}
	getFromAjax("POST", "php/getAuthorList.php", "get_json=true", fillAllAuthors);
}
//===============================КОНЕЦ ДОБАВЛЕНИЕ КОНТЕНТА====================================

//==========================НАЧАЛО ФУНКЦИИ ПО РАБОТЕ С ОТБОРАМИ===============================
function selectOption(elem){ //При выборе порядка сортировки и количества отображаемыъ элементов на странице
	if(elem.id == "quantityElem") filterChange = true;
	setCookie(elem.id, $("#" + elem.id).val(), 14);
}

function showArrayAutors(jQObject){ //Отобразить выбранных авторов
    jQObject.html("");
	$.each(choosenAuthors, function (index, value) {
		var head = 	$("<div>", 		{name: "author" + value, class: "btn btn-primary", onclick: "outOfArrayAutors(this)"})
			.append($("<span />", 	{class: "glyphicon glyphicon-remove"}))
			.append($("<label />", 	{name: index, text: allAuthors[value]}));

		jQObject.append(head)
	});
}

function outOfArrayAutors(elem){ //Удаление автора из списка выбранных авторов
	filterChange = true;
	jQObject = $("#" + elem.attributes.name.value);
    jQObject.addClass("isNotActive");
	jQObject.removeClass("isChoosen");
	var author_id = elem.attributes.name.value.slice(6);
    choosenAuthors = choosenAuthors.filter(function(el){
		return el != author_id;
	});
    showArrayAutors( $("#authors_panel_list") );
	panelClick = 2;
}

function chooseAutor(elem){ //Добавление автора в список выбранных авторов
	filterChange = true;
	$("#panel_body").hide();
	$("#" + elem.id).hide();
	$("#search_input").val("");
	$("#search_input").hide();
	choosenAuthors.push( elem.id.slice(6) );
    showArrayAutors( $("#authors_panel_list") );
}

function findAuthors(){ // Отбор по авторам
	if ( $("#search_input").val() == ""){
		$("#panel_body li:hidden").each(function(index, value){ 
			if (choosenAuthors.find(function(ind,val){
				return ind == value.id.slice(6)
			}) == undefined) $(this).show(); 
		});
	}else{
	$("#panel_body li").each(function(index, value){
	    autor = allAuthors[value.id.slice(6)].toLowerCase();
		if (autor.indexOf( $("#search_input").val().toLowerCase() ) != 0)
			$(this).hide();
		else 
			if (choosenAuthors.find(function(ind,val){
				return ind == value.id.slice(6)
			}) == undefined) $(this).show();
	});
	}
}

function chooseGenre(elem){ //Отбор по жанрам
	filterChange = true;
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

function createJumpButton(quantity, jQObject){ //Создание панели с кнопками
	jQObject.html("");
	elemNumber = $("#quantityElem").val();

	for (var i = 1; i <= Math.ceil(quantity / elemNumber); i++)
		jQObject.append($("<button>", {
										type:"button", 
										class:"btn btn-default" + (i==1?" active":""),
										name: "pageBtn" + i,
										id: "btn" + i,
										text: i,
										onclick: "search(" + i + ")"
										}));
	
}

function search(pNumber){ //Поиск в базе данных по отборам
	
	var ajaxParam  = {};
	var pageNumber = "";
	if(pNumber == undefined)pageNumber = 1;
		else pageNumber = pNumber;
	
	$("button[name*=pageBtn]").removeClass("active");
	$("#btn" + pageNumber).addClass("active");
	
	arrBooksContent  = {};
	arrGenreContent  = {};
	arrAuthorContent = {};
	$("#search_content").html("");
	
	ajaxParam.title 		= $("#title").val();
	ajaxParam.pageNumber 	= pageNumber;
	ajaxParam.orderBy 		= $("#orderBy").val();
	ajaxParam.elemNumber 	= $("#quantityElem").val();
	ajaxParam.author_id 	= new Array();
	ajaxParam.genre_id		= new Array();
	
	$.each(choosenAuthors, function(index, value){
		ajaxParam.author_id.push(value);
	});
	
	$.each(choosenGenres, function(index, value){
		ajaxParam.genre_id.push(value);
	});
	
	var getSearchContent = function(msg){
        fillSearchContent( msg, $("#search_content") );
		//$("#search_content").html(msg);
	}
	
	//Получить непосредственно сами книги
    getFromAjax("POST", "php/search.php", 'dataRequest=' + JSON.stringify(ajaxParam), getSearchContent);
	
	//Сформировать кнопки страниц
	var generateJumpButtons = function(msg){
		createJumpButton( msg, $("#jumpButtons") );
	}
	
	//Получить количество страниц, если у нас поменялись фильтры
	if(filterChange){
		getFromAjax("POST", "php/countPage.php", 'dataRequest=' + JSON.stringify(ajaxParam), generateJumpButtons);
		filterChange = false;
	}
	
	getListOfAllGenres();
}
//==========================КОНЕЦ  ФУНКЦИИ ПО РАБОТЕ С ОТБОРАМИ===============================
