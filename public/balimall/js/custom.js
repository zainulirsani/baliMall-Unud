$(document).ready(function(){
	$('a.user-btn').on('click', function(){
		$('a.user-btn, .user-info .panel').toggleClass('active');
	})
	$('.btn-mobile-filter').on('click', function(){
		$('.mobile-filter').toggleClass('active');
	})
	$('.acc__button').on('click', function(){
		$(this).closest('.acc').toggleClass('active');
	})
	$('.mobile-filter .close-btn').on('click', function(){
		$('.mobile-filter').removeClass('active');
	})
	$('.user-info .panel').on('mouseleave', function(){
		$('a.user-btn, .user-info .panel').removeClass('active');	
	})
	$('select:not(.not-use-selectric)').selectric({
	  	disableOnMobile: false,
	  	nativeOnMobile: false
	});
	$('.use-select2').select2();
	$('#toggle').on('click', function(){
		$('#toggle, header .mobile-wrapper').toggleClass('menu-active');
		$('.header-cart').removeClass('active-cart');
	});
	$('#toggle-cat').on('click', function(){
		$('header .ctwr').toggleClass('active');
	});
	$('.notif button').on('click', function(){
		$('header .notif').toggleClass('active');
	});
	$('.cat-mobile-btn').on('click', function(){
		$('.cat-mobile-wrapper').toggleClass('active');
	});
	$('.ctwr__main a').on('mouseover', function(){
		cat = $(this).attr('data-cat');
		$(this).parents('.ctwr').find('.ctwr__child div.active').removeClass('active');
		$(this).parents('.ctwr').find('.ctwr__child div.'+cat).addClass('active');
	});
	$('.ctwr .container').on('mouseleave', function(){
		$(this).parents('.ctwr').removeClass('active');
	})
    var galleryThumbs = new Swiper('.dtl-t.swiper-container', {
      slidesPerView: 4,
      spaceBetween: 8,
      watchSlidesVisibility: true,
      watchSlidesProgress: true,
    });
    var galleryTop = new Swiper('.dtl-s.swiper-container', {
      thumbs: {
        swiper: galleryThumbs
      }
    });
	$('.tabs .tab-title a').on('click', function(){
		tab = $(this).attr('data-tab');
		$(this).parents('.tabs').find('.tab-title a.active, .tab-content div.active').removeClass('active');
		$(this).parents('.tabs').find('.tab-title a[data-tab="'+tab+'"], .tab-content div.'+tab).addClass('active');
		$(this).parents('.tabs').addClass('active-mobile');
	})
	$('.tabs .close-msg').on('click', function(){
		$(this).parents('.tabs').removeClass('active-mobile');
	})
	$('#toggle-cart').on('click', function(){
		$('.header-cart').toggleClass('active-cart');
		$('#toggle, header .mobile-wrapper').removeClass('menu-active');
	})
})