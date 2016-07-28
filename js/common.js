// (c) DairyWindow Ltd 2014
window.onload = function () {
	compactMenu('someID', true, "<img src='images/arrow.png'/>");
	stateToFromStr('someID', retrieveCookie('menuState'));
	
	//showallcookies();
	//var insertrow = document.getElementById('insertrow');
	//var resultwidth = getPosition(insertrow);
	var insertfield = document.getElementById('insertfield');
	if(typeof insertfield!='undefined' && insertfield!=null && insertfield!=""){
		var resultwidth = insertfield.offsetWidth;
		//alert('Cookie is currently ' + retrieveCookie('resultwidth') + '\n\r resultwidth is ' + resultwidth);
		if(resultwidth > retrieveCookie('viewportwidth')) setCookie('resultwidth', resultwidth);
		else setCookie('resultwidth', retrieveCookie('viewportwidth'));
	}
	setInterval(
		function(){
			var innerDivs = document.getElementsByTagName("div");
			for(var i=0; i<innerDivs.length; i++){
				var divid=innerDivs[i].id;
				if(divid.substr(0,9) == "popuptext" && document.getElementById(divid).style.display != 'none'){
					hidediv(divid);
					return; //pretty much the same as die();
				}
			}
		}, 5000 // hidediv every 5 seconds
	);
	//hidediv('loading');
}
window.onunload = function () {
	setCookie('menuState', stateToFromStr('someID'));
}
function hidediv(arg) {
	if(typeof arg!='undefined' && arg!=null && arg!=""){
		try{ document.getElementById(arg).style.display = 'none'; }
		catch(err){ /* arg is probably a null value */ }
	}
}
function sendform(formid, inputid){
	//document.getElementById('loading').style.display = 'block';
	if(typeof inputid != 'undefined' && inputid != null){
		var elem1 = document.getElementById(inputid);
		elem1.value = "Please wait...";
	}
	if(typeof formid != 'undefined' && formid != null){
		var elem2 = document.getElementById(formid);
		elem2.submit();
	}
}
function showloading(){
	//document.getElementById('loading').style.display = 'block';
	var loadingform = document.getElementById('loadingform');
	loadingform.submit();
}
function retrieveCookie(cname) { //http://www.w3schools.com/js/js_cookies.asp
    var name = cname + "=";
    var ca = document.cookie.split(';');
    for(var i=0; i<ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1);
        if (c.indexOf(name) != -1) return c.substring(name.length,c.length);
    }
    return "";
}
function setCookie(cname, cvalue, exdays) { //http://www.w3schools.com/js/js_cookies.asp
    var d = new Date();
	if(typeof exdays != 'undefined' && exdays != null) exdays=1;
    d.setTime(d.getTime() + (exdays*24*60*60*1000));
    var expires = "expires="+d.toUTCString();
    document.cookie = cname + "=" + cvalue + "; " + expires;
}
function showallcookies() {
    alert(document.cookie);
}
function redrawscreen(){
	//http://andylangton.co.uk/blog/development/get-viewport-size-width-and-height-javascript
	var viewportwidth;
	var viewportheight;
	// the more standards compliant browsers (mozilla/netscape/opera/IE7) use window.innerWidth and window.innerHeight
	if (typeof window.innerWidth != 'undefined'){
		viewportwidth = window.innerWidth,
		viewportheight = window.innerHeight
	}
	// IE6 in standards compliant mode (i.e. with a valid doctype as the first line in the document)
	else if (typeof document.documentElement != 'undefined' && typeof document.documentElement.clientWidth != 'undefined' && document.documentElement.clientWidth != 0){
		viewportwidth = document.documentElement.clientWidth,
		viewportheight = document.documentElement.clientHeight
	}
	// older versions of IE
	else{
		viewportwidth = document.getElementsByTagName('body')[0].clientWidth,
		viewportheight = document.getElementsByTagName('body')[0].clientHeight
	}
	
	var d = new Date();
	d.setTime(d.getTime() + (30*24*60*60*1000)); //30 days
	var expires = 'expires='+d.toGMTString();
	document.cookie = 'viewportwidth=' + viewportwidth + '; ' + expires;
	document.cookie = 'viewportheight=' + viewportheight + '; ' + expires;
	//alert('Your viewport width is ' + viewportwidth + ' x ' + viewportheight);
	return viewportwidth, viewportheight;
}
function keypress(e,formid){
	//http://bytes.com/topic/javascript/answers/600837-how-fire-js-event-when-enter-key-pressed
    var Ucode=e.keyCode? e.keyCode : e.charCode
	//alert('Ucode: ' + Ucode);
    if (Ucode == 13){
        var form = document.getElementById(formid);
		form.submit();
    }
}
function getPosition(element) {
	//http://www.kirupa.com/html5/get_element_position_using_javascript.htm
    var xPosition = 0;
    var yPosition = 0;
  
    while(element) {
        xPosition += (element.offsetLeft - element.scrollLeft + element.clientLeft);
        yPosition += (element.offsetTop - element.scrollTop + element.clientTop);
        element = element.offsetParent;
    }
    return { x: xPosition, y: yPosition };
}
function droprowq(item1, value1, item2, value2){
	var droprowquestion = document.getElementById('dropquestion');
	droprowquestion.style.display = 'block';
	droprowquestion.innerHTML = "Are you sure you want to DELETE this row?<br>" + item1 + " is " + value1 + " and " + item2 + " is " + value2 + "<br><form><input type='button' name='yes' value='Yes' onclick=\"document.getElementById('droprowform" + item1 + value1 + "').submit();\" />&nbsp;<input type='button' name='no' value='No' onclick=\"hidediv('droprowquestion');\" /></form>";
}
function dropcolq(formid, colname){
	var dropcolquestion = document.getElementById('dropquestion');
	dropcolquestion.style.display = 'block';
	dropcolquestion.innerHTML = "Are you sure you want to DELETE " + colname.replace('dropcol','') + " and all the data in it?<br><form><input type='button' name='yes' value='Yes' onclick=\"document.getElementById('dropquestion').innerHTML='Please wait...';sendform('" + formid + "','" + colname + "');\" />&nbsp;<input type='button' name='no' value='No' onclick=\"hidediv('dropquestion');\" /></form>";
}