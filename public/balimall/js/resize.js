(function($) {
  $.fn.fullBg = function(){
    var bgImg = $(this);		
    
    function resizeImg() {
      var imgwidth = bgImg.width();
      var imgheight = bgImg.height();
			
      var winwidth = $(window).width();
      var winheight = $(window).height();
		
      var widthratio = winwidth / imgwidth;
      var heightratio = winheight / imgheight;
			
      var widthdiff = heightratio * imgwidth;
      var heightdiff = widthratio * imgheight;
		
      if(heightdiff>winheight) {
        bgImg.css({
          width: winwidth+'px',
          height: heightdiff+'px'
        });
      } else {
        bgImg.css({
          width: widthdiff+'px',
          height: winheight+'px'
        });		
      }
    } 
    resizeImg();
    $(window).resize(function() {
      resizeImg();
    }); 
  };
  
  $.fn.fullMobile = function(){
    var bgImg = $(this);		
    
    function resizeMobile() {
      var imgwidth = bgImg.width();
      var imgheight = bgImg.height();
			
      var winwidth = $(window).width();
      var winheight = $(window).height();
		
      var widthratio = winwidth / imgwidth;
      var heightratio = winheight / imgheight;
			
      var widthdiff = heightratio * imgwidth;
      var heightdiff = widthratio * imgheight;
		
      bgImg.css({
        width: winwidth+'px',
        height: heightdiff+'px'
      });
      
      $(bgImg).parent().height(heightdiff);
    } 
    resizeMobile();
    $(window).resize(function() {
      resizeMobile();
    }); 
  };
})(jQuery)

