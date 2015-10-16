function startAp() {
	setLeft();
	showAp();
}

function hideAp() { 
	if (document.layers) document.layers.pa.visibility = 'hide';
	else if (document.all) document.all.pa.style.visibility = 'hidden';
	else if (document.getElementById) document.getElementById("pa").style.visibility = 'hidden';
}

function showAp() { 
	state=typeof tPos;
	if(state=='undefined') tPos = h;
	if(tPos < t) { 
		tPos+=25;
		if (document.layers) document.layers.pa.top = tPos+"px";
		else if (document.all) document.all.pa.style.top = tPos+"px";
		else if (document.getElementById) document.getElementById("pa").style.top = tPos+"px";
	}

	if(timer!=null) clearInterval(timer);
	timer = setTimeout("showAp()",20);
}

function getoPos() {
	if (document.layers) alert(document.layers.pa.top);
	else if (document.all) alert(document.all.pa.style.top);
	else if (document.getElementById) alert(document.getElementById("pa").style.top);
}

function setLeft() {
	if (document.layers) document.layers.pa.left = ((window.innerWidth / 2) - (w / 2))+"px";
	else if (document.all) document.all.pa.style.left = ((document.body.offsetWidth / 2) - (w / 2))+"px";
	else if (document.getElementById) document.getElementById("pa").style.left = ((window.innerWidth / 2) - (w / 2))+"px";
}