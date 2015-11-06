(function(jQuery) {

    jQuery.fn.printPage = function() {
       return this.each(function() {
            // Wrap each element in a <a href="#">...</a> tag
            var $current = jQuery(this);
            $current.wrapInner('<a href="#" rel="for_print"></a>');
            $current = $('.pdf');
            $current.wrapInner('<a href="#" rel="for_print"></a>');
            
            
            jQuery('span.print > a').click(function() {
                var container = $(this).attr('rel');
			    var productDesc = $('.'+container).html();
			    var header = $('.header').html() + $('.footer').html();
			    //alert(container);
			    //забираем контент нужного нам блока 
			    $('body').addClass('printSelected');//добавляем класс к body
			    $('body').append('<div class="printSelection">' + header + productDesc + '</div>');//создаем новый блок внутри body
			    window.print();//печатаем
			    
			    window.setTimeout(pageCleaner, 0); //очищаем нашу страницу от "мусора"
			    
			    return false;//баним переход по ссылке, чтобы она не пыталась перейти по адресу, указанному внутри аттрибута href 
  
            });
            
            jQuery('span.pdf > a').click(function() {
			    return false;//баним переход по ссылке, чтобы она не пыталась перейти по адресу, указанному внутри аттрибута href 
            });
       });
    }
})(jQuery);

/*$(function() {
	

	
	
	$(window).scroll(function() {
		set_sticky();
	});
	
	$(window).resize(function() {
		set_sticky();
	});
	
});*/

function set_sticky()
{   var sticky_name = '#'+$('.span3 nav').attr('id');
	var headWidth = $(window).outerWidth();
    var footerTop = $('#footer_').offset().top;
	var headTop = $(sticky_name).offset().top;
	var headHeight = $(sticky_name).height();
  if (($(window).scrollTop() > headTop) && (footerTop > headTop + headHeight ) && ( headWidth > 768) ) {
	 // if the window scrolls, make the menu stick
		$(sticky_name).addClass('sticky');
  } else {
	 // everything's normal
	 $(sticky_name).removeClass('sticky');
  }
}

function pageCleaner()
{
    $('body').removeClass('printSelected');//убираем класс у body
    $('.printSelection').remove();//убиваем наш только что созданный блок для печати
}