jQuery(document).ready(function($){  
	
	var displaySite = $("#selectedElement").val();

	$("select option[value='"+ displaySite +"']").attr("selected","selected");
	
});