// Java Document

// Allow a checkbox to disable/enable 
function toggle_input(domname)
{
	domname.disabled = !domname.disabled;
	if (domname.disabled) {
		domname.value = "";
	}
}

// Allow a button to clear a text field
function clearTag(tagInd)
{	
	if (tagInd==1) { document.getElementById("tag1").value = ""; }
	if (tagInd==2) { document.getElementById("tag2").value = ""; }
	if (tagInd==3) { document.getElementById("tag3").value = ""; }
}

// Allow a link to send a value to a text field
function sendTag(t)
{	
	if (document.getElementById("tag1").value.trim() == "") { document.getElementById("tag1").value = t; }
	else if (document.getElementById("tag2").value.trim() == "") { document.getElementById("tag2").value = t; }
	else if (document.getElementById("tag3").value.trim() == "") { document.getElementById("tag3").value = t; }
}

