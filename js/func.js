$(function(){
	$('div.codes input.text').bind('focus click mouseover', function(){
		this.select(0,0);
	});
});