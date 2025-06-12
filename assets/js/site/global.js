var remaining;
var dzMaxSize = 10; // in MB
var dzUploadUrl = BASE_URL+'/media/file/upload';
var dzDeleteUrl = BASE_URL+'/media/file/delete';
var dzAcceptedFiles = '.jpeg, .jpg, .png, .gif';
var dzPngOnly = '.png';
var dzAcceptedPaymentFiles = '.jpeg, .jpg, .png, .gif, .pdf';
var dzPreviewTemplate = document.querySelector('#dz-tpl-preview').innerHTML;
var elementSlugOutput = $('.slug-output');
var elementDateTime = $('.date-time');
var elementDateOnly = $('.date-only');
var elementTimeOnly = $('.time-only');
var elementLoading = $('.loading');
var closeButton = '<a href="javascript:void(0);" class="sBtn red close-popup">'+LABEL_CLOSE+'</a>';
var flatpickrConfig = {
  altInput: true,
  altFormat: 'd F Y',
  onChange: function(selectedDates, dateStr, instance) {
    $('#'+instance.input.id).next().attr('style', '');
  }
};

Dropzone.autoDiscover = false;

var delay = (function() {
  var timer = 0;

  return function(callback, ms) {
    clearTimeout(timer);
    timer = setTimeout(callback, ms);
  };
})();

var initCKEditorBasic = function(element) {
  CKEDITOR.replace(element, {toolbar: 'Basic', extraPlugins: 'confighelper'});
};

var _tr = function() {
  if (remaining) {
    clearInterval(remaining);
  }

  $.post(BASE_URL+'/_tr', $.extend(true, {}, TOKEN), function(response) {
    if (response.status) {
      var counter = 0;

      remaining = setInterval(function() {
        counter++;

        if (counter === parseInt(response.data)) {
          clearInterval(remaining);
          window.location.href = BASE_URL+'/login'; // Change to logout?
        }
      }, 1000);
    }
  });
};

var passwordChecker = function(password) {
  var strong = true;

  if (password.length < 6) {
    strong = false;
  }

  if (!/\d/.test(password)) {
    strong = false;
  }

  if (!/[a-z]/.test(password)) {
    strong = false;
  }

  if (!/[A-Z]/.test(password)) {
    strong = false;
  }

  if (/[^0-9a-zA-Z]/.test(password)) {
    strong = false;
  }

  return strong;
};

var showGeneralPopup = function(text) {
  $('#popup-general .popup-general-title').html(text);
  $('#popup-general .btn-wrapper').html(closeButton);
  $('#popup-general').show();
};

var hideGeneralPopup = function() {
  $('#popup-general .popup-general-title').html('');
  $('#popup-general .btn-wrapper').html('');
  $('#popup-general').hide();
};

var showConfirmPopup = function(text) {
  $('#popup-confirm .popup-confirm-title').html(text);
  $('#popup-confirm').show();
};

var hideConfirmPopup = function() {
  $('#popup-confirm .popup-confirm-title').html('');
  $('#popup-confirm').hide();
};

var showFormPopup = function(text, formElement) {
  var content = formElement || '';

  $('#popup-form .popup-form-title').html(text);
  $('#popup-form .popup-form-content').html(content);
  $('#popup-form').show();
};

var hideFormPopup = function() {
  $('#popup-form .popup-form-title').html('');
  $('#popup-form .popup-form-content').html('');
  $('#popup-form').hide();
};

var showCancelPopup = function(text, formElement) {
  var content = formElement || '';

  $('#popup-cancel-order .popup-cancel-order-title').html(text);
  $('#popup-cancel-order .popup-cancel-order-content').html(content);
  $('#popup-cancel-order').show();
};

var hideCancelPopup = function() {
  $('#popup-cancel-order .popup-cancel-order-title').html('');
  $('#popup-cancel-order .popup-cancel-order-content').html('');
  $('#popup-cancel-order').hide();
};

var showRecievedComplaintPopup = function(text) {
  // var content = formElement || '';

  $('#popup-received-komplain .popup-received-komplain-title').html(text);
  // $('#popup-received-komplain .popup-received-komplain-content').html(content);
  $('#popup-received-komplain').show();
};

var hideRecievedComplaintPopup = function() {
  $('#popup-received-komplain .popup-received-komplain-title').html('');
  // $('#popup-received-komplain .popup-received-komplain-content').html('');
  $('#popup-received-komplain').hide();
};

var showOrderReviewPopup = function(text, formElement) {
  var content = formElement || '';

  $('#popup-order-review .popup-order-review-title').html(text);
  $('#popup-order-review .popup-order-review-content').html(content);
  $('#popup-order-review').show();
};

var hideOrderReviewPopup = function() {
  $('#popup-order-review .popup-order-review-title').html('');
  $('#popup-order-review .popup-order-review-content').html('');
  $('#popup-order-review').hide();
};

var showCartPopup = function(text) {
  $('#popup-cart .popup-cart-title').html(text);
  $('#popup-cart').show();
};

var hideCartPopup = function() {
  $('#popup-cart .popup-cart-title').html('');
  $('#popup-cart').hide();
};

var showLoading = function() {
  elementLoading.show();
};

var hideLoading = function() {
  elementLoading.hide();
};

var arrayUnique = function(data) {
  var set = [];

  for (var i = 0; i < data.length; i++) {
    if (set.indexOf(data[i]) === -1 && data[i] !== '') {
      set.push(data[i]);
    }
  }

  return set;
};

/**
 * Pseudo-random string generator
 * http://stackoverflow.com/a/27872144/383904
 * Default: return a random alpha-numeric string
 *
 * @param {Number} len Desired length
 * @param {String} an Optional (alphanumeric), "a" (alpha), "n" (numeric)
 * @return {String}
 */
var randomString = function(len, an) {
  an = an && an.toLowerCase();

  var str = '',
    i = 0,
    min = an === 'a' ? 10 : 0,
    max = an === 'n' ? 10 : 62;

  for (; i++ < len;) {
    var r = Math.random() * (max - min) + min << 0;
    str += String.fromCharCode(r += r > 9 ? r < 36 ? 55 : 61 : 48);
  }

  return str;
}

/*var lazyLoadInstance = new LazyLoad({
  elements_selector: '.lazy'
});

var updateLazyLoad = function() {
  lazyLoadInstance.update();
};*/

// Usage: setTimeout(callout, 30000);
/*var callout = function() {
  $.ajax({
    url: BASE_URL,
    method: 'POST',
    data: $.extend(true, {}, TOKEN),
    dataType: 'json',
    global: false,
    beforeSend: function() {
      // If needed to do something before sending the request
    }
  }).done(function(response) {
    // This is the main action
  }).always(function() {
    setTimeout(callout, 30000);
  });
};*/

$(document).ajaxStart(function() {
  // _tr();
});

$(document).ajaxStop(function() {
  //
});

$(document).ready(function() {
  // _tr();

  $(document).on('keyup', '.slug-input', function(e) {
    var input = $(this).val();

    elementSlugOutput.val(slug(input, {lower: true}));
  });

  $(document).on('keyup', '.slug-output', function(e) {
    var input = $(this).val();

    elementSlugOutput.val(slug(input, {lower: true}));
  });

  // Flatpickr
  if (elementDateOnly.length) {
    elementDateOnly.flatpickr(flatpickrConfig);
  }

  if (elementTimeOnly.length) {
    elementTimeOnly.flatpickr({
      enableTime: true,
      noCalendar: true,
      dateFormat: 'H:i'
    });
  }

  if (elementDateTime.length) {
    elementDateOnly.flatpickr($.extend(flatpickrConfig, {enableTime: true}));
  }
  // Flatpickr

  // Source: https://github.com/developit/tags-input
  if ($('input[type="tags"]').length) {
    [].forEach.call(document.querySelectorAll('input[type="tags"]'), tagsInput);
  }

  $(document).on('click', '.close-popup', function(e) {
    e.preventDefault();

    $('.popup').hide();
  });

  if (TRACKING_ID !== '') {
    $(document).on('click', '.gtag-js', function() {
      var title = $(this).data('title');
      var type = $(this).data('type');
      var label = type+' view';

      if (typeof title !== 'undefined') {
        label = type+' view "'+title+'"';
      }

      gtag('event', 'click', {
        'event_category': type+' link',
        'event_label': label
      });
    });
  }

  $('.act-logout').click(function(e) {
    e.preventDefault();

    $('#popup-logout').show();
  });

  $('.locale-picker').click(function(e) {
    e.preventDefault();

    $.post(BASE_URL+'/_sl', $.extend(true, {locale: $(this).attr('data-locale')}, TOKEN), function(response) {
      if (response.status) {
        window.location.reload();
      }
    });
  });
});
