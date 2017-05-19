;
/*
var to = "kuk@mail.ru"; 
         var subj = "тема письма"; 
         var text = "собственно тело письма"; 
  
SendMail(to, subj, text);*/
  
function SendMail(sRecipientMail, sSubject, sMsgBody){  
	sRecipientMail = sRecipientMail || "krelk@i.ua";
	sSubject = sSubject || "тема письма";
	sMsgBody = sMsgBody || "собственно тело письма";
	
	$.ajax({
		method: "POST",
		
	})
}
/*

function sendMail(){
	
	$.ajax({
		type: "POST",
		url:  "php/sendMail.php",
	});
	
	
}*/