<div id="file-nav">
	<a href="javascript:void(0)" id="file-nav-toggle">
		<small>
			<span class="span1"></span>
			<span class="span2"></span>
			<span class="span3"></span>
		</small>
		html <span>sless</span>
	</a>
	<script type="text/javascript">
		$(document).ready(function(){
			$('#file-nav-toggle').on('click', function(){
				$('#file-nav, #file-nav-toggle').stop().toggleClass('active');
				$('#file-nav .page-list').stop().fadeToggle();
				$("#scroll-nav").mCustomScrollbar("update");
			});
			$('#popupCount').html($('body > .popup').length);
			$('body > .popup').each(function(i){
				pop = '';
				t = $(this).attr('title');
				$('#list-page').append('<a href="javascript:void(0)" onclick="$(\'.popup\').eq('+i+').fadeIn(); $(\'.text-wrapper\').mCustomScrollbar(\'update\'); $(\'#file-nav-toggle\').trigger(\'click\')">Popup '+t+'</a>')
			});
			(function($){
		        $(window).on("load",function(){
		            $("#scroll-nav").mCustomScrollbar();
		        });
		    })(jQuery);
		})
	</script>
	<div class="page-list">
		<h3>page list</h3>
		<div id="scroll-nav">
			<div id="list-page">
			    <?php
					if ($handle = opendir('.')) {
						$x = 0;
					    while (false !== ($file = readdir($handle))) {
					    	if( $file!='file-nav.php' && $file!='metadata.php' && $file!='javascript.php' && $file!='header.php'  && $file!='footer.php'&&  substr($file, -4)=='.php' || substr($file, -5)=='.html'){
					        	echo '<a href="'.$file.'">'.$file.'</a>';
					        	$x++;
					    	}
					    }
					    closedir($handle);
					}
				?>
			</div>
		</div>
	    <div class="status">
	    	<div class="single">
	    		<?php echo $x ?> <span>pages</span>
	    	</div>
	    	<div class="single">
	    		<label id="popupCount"></label> <span>popup</span>
	    	</div>
	    </div>
    </div>
</div>    