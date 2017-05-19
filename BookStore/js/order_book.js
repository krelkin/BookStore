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
//===============================КОНЕЦ  РАБОТА С AJAX=========================================

function SendMail(){ // отправка e-mail администратору
	var sRecipientMail = "krelk@i.ua"; 	//ящик админа
	var sSubject = "Сайт BookStore";	//заголовок
	var sMsgBody = 'Вы заказали книгу "' + $(".title h4").html() + '" в количестве ' + $("quantity_book").val() + ' штук.'; //тело письма
	
	var func = function(msg){
		$("#result").removeClass("alert-success");
		$("#result").removeClass("alert-danger");
		if (msg == "1") {
			$("#result").addClass("alert-success");
			$("#result").html("Заказ успешно отправлен.");
		}else{
			$("#result").addClass("alert-danger");
			$("#result").html("Заказ непринят.");
		}
	}
	
	getFromAjax("POST", "php/sendMail.php", "to=" + sRecipientMail + "&subject=" + sSubject + "&message=" + sMsgBody, func);
}