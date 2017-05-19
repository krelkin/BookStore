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

$(document).ready(function(){
	$("#find_books").click(function(){
		if ( $("#find_books_input").val().replace(/\s/g, "") != "" )
			location.href = "searchpage.php?title=" + $("#find_books_input").val();
	});
	
	quantityElemCookies = getCookie("quantityElem");
	if( quantityElemCookies == undefined ){
		$("#quantityElem").val(5);
		setCookie("quantityElem", 5, 14);
	}else{ $("#quantityElem").val(quantityElemCookies); };

	$("#quantityElem").on("change", null, function(){
		setCookie("quantityElem", $(this).val(), 14);
		choosePage(1);
	});
});

function choosePage(nPage){
	location.href = "index.php?page=" + nPage;
}
