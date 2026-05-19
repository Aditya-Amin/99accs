(function ($) {
	"use strict";


/*===========================================
	=    		Mobile Menu			      =
=============================================*/
//SubMenu Dropdown Toggle
if ($('.tgmenu__wrap li.menu-item-has-children ul').length) {
	$('.tgmenu__wrap .navigation li.menu-item-has-children').append('<div class="dropdown-btn"><span class="plus-line"></span></div>');
}
if ($('.tgmenu__categories .dropdown.children .dropdown-menu').length) {
	$('.tgmenu__categories .list-wrap .dropdown.children').append('<div class="dropdown-btn"><span class="plus-line"></span></div>');
}

//Mobile Nav Hide Show
if ($('.tgmobile__menu').length) {

	var mobileMenuContent = $('.tgmenu__wrap .tgmenu__main-menu').html();
    var categoriesContent = $('.tgmenu__categories').prop('outerHTML');

    $('.tgmobile__menu .tgmobile__menu-box .tgmobile__menu-outer').append(categoriesContent);
    $('.tgmobile__menu .tgmobile__menu-box .tgmobile__menu-outer').append(mobileMenuContent);

	//Dropdown Button
	$('.tgmobile__menu li.menu-item-has-children .dropdown-btn, .tgmobile__menu .tgmenu__categories .dropdown.children .dropdown-btn').on('click', function () {
		$(this).toggleClass('open');
		$(this).prev('ul').slideToggle(300);
	});

	//Menu Toggle Btn
	$('.mobile-nav-toggler').on('click', function () {
		$('body').addClass('mobile-menu-visible');
	});

	//Menu Toggle Btn
	$('.tgmobile__menu-backdrop, .tgmobile__menu .close-btn').on('click', function () {
		$('body').removeClass('mobile-menu-visible');
	});
};


/*===========================================
	=     Menu sticky & Scroll to top      =
=============================================*/
$(window).on('scroll', function () {
	var scroll = $(window).scrollTop();
	if (scroll < 245) {
		$("#sticky-header").removeClass("sticky-menu");
		$('.scroll-to-target').removeClass('open');
        $("#header-fixed-height").removeClass("active-height");

	} else {
		$("#sticky-header").addClass("sticky-menu");
		$('.scroll-to-target').addClass('open');
        $("#header-fixed-height").addClass("active-height");
	}
});


/*===========================================
	=           Scroll Up  	         =
=============================================*/
$(document).on('click', '.scroll-to-target', function (e) {
  e.preventDefault();

  var target = $(this).data('target');

  if ($(target).length) {

    var headerHeight = $('.header').outerHeight() || 0;

    $('html, body').animate({
      scrollTop: $(target).offset().top - headerHeight
    }, 600);

  }
});


/*===========================================
	=          Data Background    =
=============================================*/
$("[data-background]").each(function () {
	$(this).css("background-image", "url(" + $(this).attr("data-background") + ")")
});

$("[data-bg-color]").each(function () {
	$(this).css("background-color", $(this).attr("data-bg-color"));
});



/*=============================================
	=        Testimonial Active		      =
=============================================*/
var testimonialSwiper = new Swiper('.testimonial-active', {
    // Optional parameters
    slidesPerView: 4,
    spaceBetween: 0,
    loop: true,
    breakpoints: {
        '1500': {
            slidesPerView: 4,
        },
        '1200': {
            slidesPerView: 4,
        },
        '992': {
            slidesPerView: 3,
        },
        '768': {
            slidesPerView: 2,
        },
        '576': {
            slidesPerView: 2,
        },
        '0': {
            slidesPerView: 1,
        },
    },
    pagination: {
        el: '.testimonial-pagination',
        clickable: true,
    },
});

/*=============================================
	=        Related Active		      =
=============================================*/
var relatedSwiper = new Swiper('.related-post-active', {
    // Optional parameters
    slidesPerView: 4,
    spaceBetween: 24,
    loop: true,
    breakpoints: {
        '1500': {
            slidesPerView: 4,
        },
        '1200': {
            slidesPerView: 4,
        },
        '992': {
            slidesPerView: 3,
        },
        '768': {
            slidesPerView: 2,
        },
        '576': {
            slidesPerView: 2,
        },
        '0': {
            slidesPerView: 1,
        },
    },
    pagination: {
        el: '.testimonial-pagination',
        clickable: true,
    },
});

/*=============================================
	=        Related Active		      =
=============================================*/
var thumbNav = new Swiper(".navSwiper", {
    spaceBetween: 16,
    slidesPerView: 3,
    watchSlidesProgress: true,
    slideToClickedSlide: true,

    breakpoints: {
        0: { slidesPerView: 3 },
        768: { slidesPerView: 4 }
    }
});

var thumbTab = new Swiper(".thumbTab", {
    spaceBetween: 10,
    effect: "fade",

    navigation: {
        nextEl: ".thumb-button-next",
        prevEl: ".thumb-button-prev"
    },

    // 🔥 MAIN MAGIC
    thumbs: {
        swiper: thumbNav
    }
});

/*=============================================
	=        dropdown 	       =
=============================================*/
// All `.dropdown-toggle` elements in this app are React-controlled
// (components/layout/Header.tsx, components/shop/ShopFilters.tsx,
// components/ui/FilterDropdown.tsx). The legacy jQuery slideToggle handler
// has been removed because it fought React's state — after slideToggle set
// inline `display: block`, React's `style={{display:'none'}}` updates were
// being clobbered by the in-flight jQuery animation, leaving dropdowns
// stuck open after route changes / outside clicks.


/*=============================================
	=        Faq  	       =
=============================================*/
let currentIndex = 0;
const items = $(".work__item");
const images = $(".work__img");

function showItem(index) {
    // item & content hide
    $(".work__item").removeClass("active");
    $(".work__content").slideUp();

    images.removeClass("active").hide();

    const currentItem = items.eq(index);
    const currentImage = images.eq(index);

    // active item show
    currentItem.addClass("active");
    currentItem.find(".work__content").slideDown();

    // related image show
    currentImage.addClass("active").fadeIn();
}


showItem(currentIndex);

setInterval(function () {
    currentIndex++;

    if (currentIndex >= items.length) {
        currentIndex = 0;
    }

    showItem(currentIndex);
}, 6000);

$(".work__item-button").on("click", function () {
    const clickedItem = $(this).parent(".work__item");
    currentIndex = items.index(clickedItem);

    showItem(currentIndex);
});


/*=============================================
	=        Faq  	       =
=============================================*/
(function ($) {
    "use strict";

    $.fn.customParticles = function () {
        return this.each(function () {
            const particleId = $(this).attr("id");

            particlesJS(particleId, {
                particles: {
                    number: {
                        value: 80,
                        density: {
                            enable: true,
                            value_area: 800
                        }
                    },
                    color: {
                        value: "#00FC70"
                    },
                    shape: {
                        type: "circle",
                        stroke: {
                            width: 0,
                            color: "#000000"
                        },
                        polygon: {
                            nb_sides: 3
                        },
                    },
                    opacity: {
                        value: 0.5,
                        random: false,
                        anim: {
                            enable: false,
                            speed: 1,
                            opacity_min: 0.1,
                            sync: false
                        }
                    },
                    size: {
                        value: 3,
                        random: true,
                        anim: {
                            enable: false,
                            speed: 40,
                            size_min: 0.1,
                            sync: false
                        }
                    },
                    line_linked: {
                        enable: false,
                        distance: 150,
                        color: "#00FC70",
                        opacity: 0.4,
                        width: 1
                    },
                    move: {
                        enable: true,
                        speed: 3,
                        direction: "none",
                        random: false,
                        straight: false,
                        out_mode: "out",
                        bounce: false,
                        attract: {
                            enable: false,
                            rotateX: 600,
                            rotateY: 1200
                        }
                    }
                },
                interactivity: {
                    detect_on: "canvas",
                    events: {
                        onhover: {
                            enable: false,
                            mode: "repulse"
                        },
                        onclick: {
                            enable: true,
                            mode: "push"
                        },
                        resize: true
                    },
                    modes: {
                        grab: {
                            distance: 400,
                            line_linked: {
                                opacity: 1
                            }
                        },
                        bubble: {
                            distance: 400,
                            size: 40,
                            duration: 2,
                            opacity: 8,
                            speed: 3
                        },
                        repulse: {
                            distance: 200,
                            duration: 0.4
                        },
                        push: {
                            particles_nb: 4
                        },
                        remove: {
                            particles_nb: 2
                        }
                    }
                },
                retina_detect: true
            });
        });
    };

    $(document).ready(function () {
        $("#banner-particles").customParticles();
        $("#cta-particles").customParticles();
    });

})(jQuery);


/*=============================================
	=        modal Up 	       =
=============================================*/
(function ($) {
    "use strict";

    // Open Modal
    $('[data-tg-modal]').on('click', function (e) {
        e.preventDefault();

        let targetModal = $(this).data('tg-modal');


        $('.tg-modal__wrap').removeClass('show').fadeOut(200);
        $('body').removeClass('tg-modal-open');

        // target modal show
        $('#' + targetModal).fadeIn(200, function () {
            $(this).addClass('show');
        });

        $('body').addClass('tg-modal-open');
    });

    // Close Modal
    $('.tg-modal-close').on('click', function () {
        $(this).closest('.tg-modal__wrap').removeClass('show').fadeOut(200);
        $('body').removeClass('tg-modal-open');
    });

    // Outside Click Close
    $('.tg-modal__wrap').on('click', function (e) {
        if ($(e.target).is('.tg-modal__wrap')) {
            $(this).removeClass('show').fadeOut(200);
            $('body').removeClass('tg-modal-open');
        }
    });

    // ESC key Close
    $(document).on('keyup', function (e) {
        if (e.key === 'Escape') {
            $('.tg-modal__wrap.show').removeClass('show').fadeOut(200);
            $('body').removeClass('tg-modal-open');
        }
    });

})(jQuery);

/*=============================================
	=        check active 	       =
=============================================*/
$('.dropdown-check').on('click', function (e) {

    if ($(e.target).is('input, label, label img')) return;

    let checkbox = $(this).find('input[type="checkbox"]');


    checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
});


$('.dropdown-check input').on('change', function () {
    let parent = $(this).closest('.dropdown-check');

    if ($(this).is(':checked')) {
        parent.addClass('active');
    } else {
        parent.removeClass('active');
    }
});

// Shop filter dropdown-arrow `.active` class is React-controlled
// (see components/shop/ShopFilters.tsx — `dropdown-arrow${isOpen ? ' active' : ''}`).
// The legacy jQuery toggle handler was removed because it ran in parallel
// with React's class updates and could flip the arrow state on its own.


/*=============================================
	=        Price active 	       =
=============================================*/
let minVal = 0;
let maxVal = 430;
let maxLimit = 1000;
let minGap = 10;

const slider = $('.slider');
const progress = $('.progress');
const thumbLeft = $('.thumb-left');
const thumbRight = $('.thumb-right');

const minInput = $('#minPrice');
const maxInput = $('#maxPrice');

const minText = $('#minVal');
const maxText = $('#maxVal');

function format(val) {
    return "$" + val.toLocaleString();
}

function updateUI() {
    let percent1 = (minVal / maxLimit) * 100;
    let percent2 = (maxVal / maxLimit) * 100;

    progress.css({
        left: percent1 + "%",
        width: (percent2 - percent1) + "%"
    });

    thumbLeft.css("left", percent1 + "%");
    thumbRight.css("left", percent2 + "%");

    minInput.val(format(minVal));
    maxInput.val(format(maxVal));

    minText.text(format(minVal));
    maxText.text(format(maxVal));
}

// drag logic
function handleDrag(e, isLeft) {
    let offset = slider.offset().left;
    let width = slider.width();

    let clientX = e.type.includes('touch')
        ? e.originalEvent.touches[0].clientX
        : e.clientX;

    let percent = (clientX - offset) / width;
    percent = Math.max(0, Math.min(1, percent));

    let value = Math.round(percent * maxLimit);

    if (isLeft) {
        if (value >= maxVal - minGap) value = maxVal - minGap;
        minVal = value;
    } else {
        if (value <= minVal + minGap) value = minVal + minGap;
        maxVal = value;
    }

    updateUI();
}

// drag events
thumbLeft.on('mousedown touchstart', function () {
    $(document).on('mousemove.drag touchmove.drag', function (e) {
        handleDrag(e, true);
    });
});

thumbRight.on('mousedown touchstart', function () {
    $(document).on('mousemove.drag touchmove.drag', function (e) {
        handleDrag(e, false);
    });
});

$(document).on('mouseup touchend', function () {
    $(document).off('.drag');
});

// input control
function getNumber(val) {
    return parseInt(val.replace(/\D/g, '')) || 0;
}

minInput.on('input', function () {
    let val = getNumber($(this).val());
    if (val >= maxVal - minGap) val = maxVal - minGap;
    minVal = val;
    updateUI();
});

maxInput.on('input', function () {
    let val = getNumber($(this).val());
    if (val <= minVal + minGap) val = minVal + minGap;
    maxVal = val;
    updateUI();
});

// init
updateUI();



/*=============================================
	=        shop filter menu 	       =
=============================================*/
$(document).ready(function(){

  // open submenu
  $('.menu-item').click(function(){
    let target = $(this).data('target');

    if(target){
      $('#' + target).addClass('active');
      $('.shop__filter-menu-wrap').addClass('hide');

      $('.shop__filter-menu').addClass('active');
    }
  });

  // back button
  $('.back-btn').click(function(){
    $('.menu-sub').removeClass('active');
    $('.shop__filter-menu-wrap').removeClass('hide');

    $('.shop__filter-menu').removeClass('active');
  });

});

/*=============================================
	=        Agent Tab	       =
=============================================*/
$(document).ready(function(){

  $('.shop__details-agent-nav li, .support__table-nav button, .shop__details-nav button, .account__dashboard-sidebar  button').click(function(){

    let tab_id = $(this).data('tab');

    // nav active
    $('.shop__details-agent-nav li, .support__table-nav button, .shop__details-nav button, .account__dashboard-sidebar  button').removeClass('active');
    $(this).addClass('active');

    // content show
    $('.tab-pane, .account-pane').removeClass('active');
    $('#' + tab_id).addClass('active');

  });

});


$(document).ready(function(){

  // Inner Tab Only
  $(document).on('click', '.locker-nav [data-tab]', function(){

    let tab_id = $(this).data('tab');

    // closest inner wrapper
    let wrapper = $(this).closest('.inner-tab-wrapper');

    // nav active
    wrapper.find('.locker-nav [data-tab]').removeClass('active');
    $(this).addClass('active');

    // content show
    wrapper.find('.locker-tab').removeClass('active');
    wrapper.find('#' + tab_id).addClass('active');

  });

});

$(document).ready(function () {

  $('.support__table-wrap-two button').on('click', function () {

    let tab_id = $(this).data('tab');
    let parent = $(this).closest('.support__table-wrap-two');

    // শুধু এই wrapper-এর button active হবে
    parent.find('button').removeClass('active');
    $(this).addClass('active');

    // শুধু এই wrapper-এর content show হবে
    parent.find('.table-pane').removeClass('active');
    parent.find('#' + tab_id).addClass('active');

  });

});


/*=============================================
	=        color active 	       =
=============================================*/
$(document).ready(function(){

  $('.skin__card-color li button').click(function(){

    $('.skin__card-color li').removeClass('active');

    $(this).closest('li').addClass('active');

  });

});


/*=============================================
	=        dropdown active 	       =
=============================================*/
$(document).ready(function(){

  $('.dropdown-check-right .arrow').click(function(){

    let parentLi = $(this).closest('li');
    let dropdown = parentLi.find('.inner-dropdown-check');

    // toggle slide
    dropdown.slideToggle(200);

    // arrow active toggle
    $(this).toggleClass('active');

  });

});

/*=============================================
	=        payment active 	       =
=============================================*/
$('.payment-list li').on('click', function () {
    if ($(this).hasClass('active')) {
        $(this).removeClass('active');
    } else {
        $('.payment-list li').removeClass('active');
        $(this).addClass('active');
    }
});

/*=============================================
	=        searches active 	       =
=============================================*/
$(document).on('click', '.shop__popular-searches li', function(){

  $(this).toggleClass('active');

});

/*=============================================
	=        Counter Up 	       =
=============================================*/
$(".counter-number").counterUp({
    delay: 10,
    time: 2000,
});


/*=============================================
	=          Jarallax Active         =
=============================================*/
$('.tg-jarallax').jarallax({
    speed: 0.2,
});

/*===========================================
	=    		 Cart Active  	         =
=============================================*/
$(document).ready(function () {

    // SVG icons
    var minusSVG = `
    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M14.25 9.75C14.25 9.94891 14.171 10.1397 14.0303 10.2803C13.8897 10.421 13.6989 10.5 13.5 10.5H6C5.80109 10.5 5.61033 10.421 5.46967 10.2803C5.32902 10.1397 5.25 9.94891 5.25 9.75C5.25 9.55109 5.32902 9.36032 5.46967 9.21967C5.61033 9.07902 5.80109 9 6 9H13.5C13.6989 9 13.8897 9.07902 14.0303 9.21967C14.171 9.36032 14.25 9.55109 14.25 9.75ZM19.5 9.75C19.5 11.6784 18.9282 13.5634 17.8568 15.1668C16.7855 16.7702 15.2627 18.0199 13.4812 18.7578C11.6996 19.4958 9.73919 19.6889 7.84787 19.3127C5.95656 18.9365 4.21928 18.0079 2.85571 16.6443C1.49215 15.2807 0.563554 13.5434 0.187348 11.6521C-0.188858 9.76082 0.00422452 7.80042 0.742179 6.01884C1.48013 4.23726 2.72982 2.71451 4.33319 1.64317C5.93657 0.571828 7.82164 0 9.75 0C12.335 0.00272983 14.8134 1.03084 16.6413 2.85872C18.4692 4.68661 19.4973 7.16498 19.5 9.75ZM18 9.75C18 8.1183 17.5161 6.52325 16.6096 5.16655C15.7031 3.80984 14.4146 2.75242 12.9071 2.12799C11.3997 1.50357 9.74085 1.34019 8.14051 1.65852C6.54017 1.97685 5.07016 2.76259 3.91637 3.91637C2.76259 5.07015 1.97685 6.54016 1.65853 8.1405C1.3402 9.74085 1.50358 11.3996 2.128 12.9071C2.75242 14.4146 3.80984 15.7031 5.16655 16.6096C6.52326 17.5161 8.11831 18 9.75 18C11.9373 17.9975 14.0343 17.1275 15.5809 15.5809C17.1275 14.0343 17.9975 11.9373 18 9.75Z" fill="currentColor"/>
    </svg>`;

    var plusSVG = `
    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M9.75 0C7.82164 0 5.93657 0.571828 4.33319 1.64317C2.72982 2.71451 1.48013 4.23726 0.742179 6.01884C0.00422452 7.80042 -0.188858 9.76082 0.187348 11.6521C0.563554 13.5434 1.49215 15.2807 2.85571 16.6443C4.21928 18.0079 5.95656 18.9365 7.84787 19.3127C9.73919 19.6889 11.6996 19.4958 13.4812 18.7578C15.2627 18.0199 16.7855 16.7702 17.8568 15.1668C18.9282 13.5634 19.5 11.6784 19.5 9.75C19.4973 7.16498 18.4692 4.68661 16.6413 2.85872C14.8134 1.03084 12.335 0.00272983 9.75 0ZM9.75 18C8.11831 18 6.52326 17.5161 5.16655 16.6096C3.80984 15.7031 2.75242 14.4146 2.128 12.9071C1.50358 11.3996 1.3402 9.74085 1.65853 8.1405C1.97685 6.54016 2.76259 5.07015 3.91637 3.91637C5.07016 2.76259 6.54017 1.97685 8.14051 1.65852C9.74085 1.34019 11.3997 1.50357 12.9071 2.12799C14.4146 2.75242 15.7031 3.80984 16.6096 5.16655C17.5161 6.52325 18 8.1183 18 9.75C17.9975 11.9373 17.1275 14.0343 15.5809 15.5809C14.0343 17.1275 11.9373 17.9975 9.75 18ZM14.25 9.75C14.25 9.94891 14.171 10.1397 14.0303 10.2803C13.8897 10.421 13.6989 10.5 13.5 10.5H10.5V13.5C10.5 13.6989 10.421 13.8897 10.2803 14.0303C10.1397 14.171 9.94892 14.25 9.75 14.25C9.55109 14.25 9.36033 14.171 9.21967 14.0303C9.07902 13.8897 9 13.6989 9 13.5V10.5H6C5.80109 10.5 5.61033 10.421 5.46967 10.2803C5.32902 10.1397 5.25 9.94891 5.25 9.75C5.25 9.55109 5.32902 9.36032 5.46967 9.21967C5.61033 9.07902 5.80109 9 6 9H9V6C9 5.80109 9.07902 5.61032 9.21967 5.46967C9.36033 5.32902 9.55109 5.25 9.75 5.25C9.94892 5.25 10.1397 5.32902 10.2803 5.46967C10.421 5.61032 10.5 5.80109 10.5 6V9H13.5C13.6989 9 13.8897 9.07902 14.0303 9.21967C14.171 9.36032 14.25 9.55109 14.25 9.75Z" fill="currentColor"/>
    </svg>`;

    // append buttons
    $(".cart-plus-minus").each(function () {
        $(this).prepend('<div class="dec qtybutton">' + minusSVG + '</div>');
        $(this).append('<div class="inc qtybutton">' + plusSVG + '</div>');
    });

    // click event
    $(".cart-plus-minus").on("click", ".qtybutton", function () {
        var $button = $(this);
        var $input = $button.parent().find("input");
        var oldValue = parseFloat($input.val()) || 0;

        var newVal;

        if ($button.hasClass("inc")) {
            newVal = oldValue + 1;
        } else {
            newVal = oldValue > 0 ? oldValue - 1 : 0;
        }

        $input.val(newVal);
    });

});



/*===========================================
	=        Magnific Popup    =
=============================================*/
$('.popup-image').magnificPopup({
	type: 'image',
	gallery: {
		enabled: true
	}
});

/* magnificPopup video view */
$('.popup-video').magnificPopup({
	type: 'iframe'
});

$(document).ready(function(){

  $('.shop__thumb-popup').click(function(){

    let gallery = $(this).siblings('.hidden-gallery');

    gallery.magnificPopup({
      delegate: 'a',
      type: 'image',
      gallery: {
        enabled: true
      }
    }).magnificPopup('open');

  });

});

/*=============================================
	=    		 Wow Active  	         =
=============================================*/
function wowAnimation() {
	var wow = new WOW({
		boxClass: 'wow',
		animateClass: 'animated',
		offset: 0,
		mobile: false,
		live: true
	});
	wow.init();
}
wowAnimation();


})(jQuery);