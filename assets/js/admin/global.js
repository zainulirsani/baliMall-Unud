var dzMaxSize = 10; // in MB
var dzEmptyTemplate = $('#dz-tpl-empty-preview');
var elementDateOnly = $('.date-only');
var elementDateRange = $('.date-range');
var elementTimeOnly = $('.time-only');
var elementSlugOutput = $('.slug-output');
var filterDateOnly = $('.f-date-only');
var filterDateStart = $('#f_date_start');
var filterDateEnd = $('#f_date_end');
var filterYear = $('#f-year');
var filterFetchSelectStore = $('#f-store');
var flatpickrConfig = {
  altInput: true,
  altFormat: 'd F Y'
};
var flatpickrConfigYear = {
  altInput: true,
  altFormat: 'Y',
  dateFormat: 'Y',
};
var flatpickrConfigFilter = {
  altInput: true,
  altFormat: 'd F Y',
  onReady: function(selectedDates) {
    // Fix bug di Chrome -- bisa di research ulang untuk solusinya
    // Edit @17-01-18 -- berpengaruh ke form, value yang sudah di set langsung di clear lagi
    if (selectedDates.length) {
      /*var dateInput = new Date('1 January 2018');
      var dateInputCheck = dateInput.getFullYear()+'-'+dateInput.getMonth()+'-'+dateInput.getDate();
      var dateBug = selectedDates[0];
      var dateBugCheck = dateBug.getFullYear()+'-'+dateBug.getMonth()+'-'+dateBug.getDate();

      if (dateBugCheck === dateInputCheck) {
        $('.f-date-only.flatpickr-input').val('');
      }*/

      // Edit @25-08-20 -- langsung clear value di filter supaya data tabelnya ga nge-bug
      $('.f-date-only.flatpickr-input').val('');
    }
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
  CKEDITOR.replace(element, {toolbar: 'Basic'});
};

var tabStepsFunction = function(element) {
  $('.'+element+'-tabs li a').on('click', function(e) {
    e.preventDefault();

    var activeTab = $(this).data('tab');

    $('.button-tabs-'+element).addClass('hidden');
    $('#button-tab-'+activeTab).removeClass('hidden');
  });

  $('.btn-tab-step').on('click', function(e) {
    e.preventDefault();

    var step = $(this).data('step');
    var current = $(this).data('current');

    $('#button-tab-'+current).addClass('hidden');
    $('#button-tab-'+step).removeClass('hidden');
    $('li.'+element+'-tab-'+step+' a').click();
  });
};

var parseAndRender = function(element, data, depth) {
  var template = document.getElementById(element);
  var repeat = depth || 5;

  if (typeof template === 'undefined' || template === null) {
    console.error('Template "'+element+'" is missing!');
    console.info('Set one with: <template id="'+element+'">Content...</template>');
    return '';
  }

  template = template.innerHTML || '';

  // Check if HTML attributes with execute function exist
  if (template.match(/on\w+?\w+="?'?/gi)) {
    console.error('Your template may contains malicious attribute(s).');
    console.info(template.match(/on\w+?\w+="?'?/gi));
    return '';
  }

  // Sanitize content
  var temp = document.createElement('div');
  temp.textContent = template;
  temp.innerHTML = template;

  // Remove any script tag if available
  var scripts = temp.getElementsByTagName('script');
  var i = scripts.length;

  while (i--) {
    scripts[i].parentNode.removeChild(scripts[i]);
  }

  var content = temp.innerHTML;

  if (data === Object(data)) {
    Object.keys(data).forEach(function(key) {
      for (var j = 0; j < repeat; j++) {
        content = content.replace('**'+key+'**', data[key]);
      }
    });
  }

  return content;
};

var makeId = function(length) {
  var result = '';
  var characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
  var charactersLength = characters.length;

  for (var i = 0; i < length; i++) {
    result += characters.charAt(Math.floor(Math.random() * charactersLength));
  }

  return result;
};

// Usage: setTimeout(callout, 30000);
/*var callout = function() {
  $.ajax({
    url: BASE_URL,
    method: 'POST',
    data: $.extend(true, {}, TOKEN),
    dataType: 'json',
    global: true,
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
  Pace.restart();
});

$(document).ready(function() {
  Pace.start();

  if (filterYear.length) {
    delay(function() {
      filterYear.yearpicker();
      // Disable input alphabet just allowed numeric input
      filterYear.on('input', function() {
        $(this).val($(this).val().replace(/[^0-9]/g, ''));
      });
    }, 500);
  }

  $('select').select2();
  $('.color-picker').colorpicker();

  if ($('.children-menu').hasClass('active')) {
    var parent = $('.children-menu.active').data('parent');

    $('#'+parent).addClass('active');
  }

  $(document).on('click', '.check-all', function() {
    var checkboxChecked = !!this.checked;

    $('.check-single').each(function() {
      $(this).prop('checked', checkboxChecked);
    });
  });

  $(document).on('keyup', '.slug-input', function(e) {
    elementSlugOutput.val(slug($(this).val(), {lower: true}));
  });

  $(document).on('keyup', '.slug-output', function(e) {
    elementSlugOutput.val(slug($(this).val(), {lower: true}));
  });

  // Flatpickr
  if (elementDateOnly.length) {
    elementDateOnly.flatpickr(flatpickrConfig);
  }

  if (elementDateRange.length) {
    elementDateRange.flatpickr($.extend(flatpickrConfig, {mode: 'range'}));
  }

  if (elementTimeOnly.length) {
    elementTimeOnly.flatpickr({
      enableTime: true,
      noCalendar: true,
      dateFormat: 'H:i'
    });
  }

  if ($('.date-time').length) {
    elementDateOnly.flatpickr($.extend(flatpickrConfig, {enableTime: true}));
  }

  if (filterDateOnly.length) {
    filterDateOnly.flatpickr(flatpickrConfigFilter);
  }

  filterDateStart.on('change', function() {
    filterDateEnd.flatpickr($.extend(flatpickrConfig, {minDate: $(this).val()}));
  });

  filterDateEnd.on('change', function() {
    filterDateStart.flatpickr($.extend(flatpickrConfig, {maxDate: $(this).val()}));
  });
  // Flatpickr

  if (filterFetchSelectStore.length) {
    filterFetchSelectStore.select2({
      ajax: {
        url: BASE_URL+'/'+ADMIN_PATH+'/store/fetch_select',
        dataType: 'json',
        delay: 250,
        minimumInputLength: 3,
        data: function(params) {
          return {
            search: params.term
          };
        },
        processResults: function(data) {
          return {
            results: data.items
          };
        }
      }
    });
  }

  if ($('.filter-checkbox').length) {
    $('.filter-checkbox').on('change', function () {
      $(this).val($(this).prop('checked'))
    })
  }

});
