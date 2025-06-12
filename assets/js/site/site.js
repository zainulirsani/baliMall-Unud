var paymentConfirmationForm = $('#payment-confirmation-form');
var elementDate = $('#input-date');
var elementSearchForm = $('#search-form');
var elementHeaderSearchForm = $('#header-search-form');
var elementHeaderSearchFormFields = $('#header-search-form-fields');
var elementProductDetailPage = $('#pdp');
var elementInputInvoice = $('#input-invoice');
var elementInputNominal = $('#input-nominal');
var elementListRegion = $('#homepage-list-region');
var dzAttachmentFile = $('#input-attachment');
var searchableProvinceInput = $('.searchable-province input');

var resetDateInput = function() {
  $('.flatpickr-input').css('margin-bottom', '0');
};

var showInvoicePrice = function(invoice) {
  if (typeof INVOICE_NOMINAL !== 'undefined' && invoice !== '') {
    if (typeof INVOICE_NOMINAL[invoice] !== 'undefined') {
      elementInputNominal.val(INVOICE_NOMINAL[invoice]).attr('readonly', 'readonly');
    } else {
      elementInputNominal.val('').removeAttr('readonly');
    }
  } else {
    elementInputNominal.removeAttr('readonly');
  }
};

var dzAttachmentFileUpload = function() {
  dzAttachmentFile.dropzone({
    url: dzUploadUrl,
    paramName: 'file_payment',
    maxFilesize: dzMaxSize,
    resizeWidth: 1440,
    acceptedFiles: dzAcceptedPaymentFiles,
    dictDefaultMessage: '',
    dictFallbackMessage: '',
    // addRemoveLinks: true,
    clickable: '.upload-attachment',
    params: $.extend(true, {type: 'payment', dir: 'payment/'+$('#input-attachment-slug').val(), overwrite: 'no'}, TOKEN),
    previewTemplate: dzPreviewTemplate,
    init: function() {
      this.on('error', function(file, response) {
        // $(file.previewElement).find('.dz-error-message').text(response);

        $('#input-attachment .dz-file-preview').remove();
        $('#input-attachment-tools').hide();

        hideLoading();
        showGeneralPopup(response);
      });

      this.on('addedfile', function(file) {
        showLoading();
      });

      this.on('thumbnail', function(file, dataUrl) {
        $('.dz-image img').hide();
      });
    },
    success: function(file, response) {
      var result = JSON.parse(file.xhr.response);
      var img = result.file_payment[0];

      if (typeof img.error !== 'undefined') {
        $('#input-attachment-tools').hide();

        showGeneralPopup(img.error);
      } else {
        var url = decodeURIComponent(img.url);
        var parse = new URL(url);
        var random = Math.floor(Math.random() * 20);
        var source = img.type.match('/pdf') ? BASE_URL+'/dist/img/doc-placeholder.jpg' : url+'?v'+random;

        $('#img-attachment').attr('src', source).show();
        $('#input-attachment-temp').val(parse.pathname);
        $('#input-attachment-tools').show();
      }

      $('.loading').hide();
    }
  });
};

var previousHistoryPage = function() {
  window.history.back();
};

var searchFunctionCheckSubCategories = function() {
  $('.f-sub-category').each(function(index) {
    if ($(this).prop('checked')) {
      var parentCategory = $(this).attr('data-parent');

      $('#p-cat-sub-'+parentCategory).click();
    }
  });
}

var searchFunctionCheckChildCategories = function() {
  $('.f-child-category').each(function(index) {
    if ($(this).prop('checked')) {
      var parentCategory = $(this).attr('data-parent');

      $('#p-cat-child-'+parentCategory).click();
    }
  });

  searchFunctionCheckSubCategories();
}

var filterFunction = function(that, event) {
  var liElement;
  var container = $(that).closest('.searchable-province');
  var inputVal = container.find('input').val().toUpperCase();

  if (['ArrowDown', 'ArrowUp', 'Enter'].indexOf(event.key) !== -1) {
    keyControl(event, container);
  } else {
    liElement = container.find('ul li');
    liElement.each(function (i, obj) {
      if ($(this).text().toUpperCase().indexOf(inputVal) > -1) {
        $(this).show();
      } else {
        $(this).hide();
      }
    });

    container.find('ul li').removeClass('selected');

    setTimeout(function () {
      container.find('ul li:visible').first().addClass('selected');
    }, 100);
  }
}

var keyControl = function(e, container) {
  if (e.key === 'ArrowDown') {
    if (container.find('ul li').hasClass('selected')) {
      if (container.find('ul li:visible').index(container.find('ul li.selected')) + 1 < container.find('ul li:visible').length) {
        container
          .find('ul li.selected')
          .removeClass('selected')
          .nextAll()
          .not('[style*="display: none"]')
          .first()
          .addClass('selected');
      }
    } else {
      container.find('ul li:first-child').addClass('selected');
    }
  } else if (e.key === 'ArrowUp') {
    if (container.find('ul li:visible').index(container.find('ul li.selected')) > 0) {
      container
        .find('ul li.selected')
        .removeClass('selected')
        .prevAll()
        .not('[style*="display: none"]')
        .first()
        .addClass('selected');
    }
  } else if (e.key === 'Enter') {
    container.find('input').val(container.find('ul li.selected').text()).blur();
    onSelect(container.find('ul li.selected').data('id'));
  }

  container.find('ul li.selected')[0].scrollIntoView({behavior: 'smooth'});
}

var onSelect = function(val) {
  $.ajax({
    url: 'https://dev.farizdotid.com/api/daerahindonesia/kota?id_provinsi='+val,
    success: function(result) {
      var regionList = '';

      for (var i = 0; i < result.kota_kabupaten.length; i++) {
        var regionData = result.kota_kabupaten[i].nama;
        var regionName = regionData.toLowerCase();

        if (regionName.includes('kabupaten ')) {
          regionName = regionName.replace('kabupaten ', '');
        }

        if (regionName.includes('kota ')) {
          regionName = regionName.replace('kota ', '');
        }

        regionList += '<div class="dc4 mc12">';
        regionList += '<a href="javascript:void(0);" class="find-region" data-region="'+regionName+'">'+capitalize(regionName)+'</a>';
        regionList += '</div>';
      }

      elementListRegion.html(regionList);
    }
  });
}

var capitalize = function(str) {
  return str.charAt(0).toUpperCase()+str.slice(1);
}

$(document).ready(function() {
  if (paymentConfirmationForm.length) {
    if (dzAttachmentFile.length) {
      dzAttachmentFileUpload();
    }


    $(document).on('change', '#input-bank-method', function(e) {
      // $('#input-bank-name option').hide();
      var value = e.target.value;

      $.each($('#input-bank-name option'), function (key,val) {
        $(this).removeAttr('disabled');
      })

      $.each($('#input-bank-name option'), function (key,val) {
        if (!$(this).hasClass('bank_'+value)) {
          $(this).attr('disabled', true);
        }
      })
      $('#input-bank-name').selectric('refresh');
    });

    $(document).on('click', '.delete-attachment', function(e) {
      e.preventDefault();

      var submit = {
        id: 0,
        path: $('#input-attachment-temp').val()
      };

      $.post(dzDeleteUrl, $.extend(true, submit, TOKEN), function(response) {
        if (response.deleted) {
          $('#img-attachment').attr('src', BASE_URL+'/dist/img/no-image.png').hide();
          $('#input-attachment-tools').hide();
          $('#input-attachment .dz-file-preview').remove();
          $('#input-attachment-temp').val('');
        }
      });
    });

    elementDate.flatpickr($.extend(flatpickrConfig, {
      minDate: new Date(2018, 0, 1),
      maxDate: 'today',
    }));

    elementDate.on('change', function() {
      resetDateInput();
    });

    if (elementInputInvoice.val() !== '') {
      showInvoicePrice(elementInputInvoice.val());
    }

    elementInputInvoice.change(function() {
      showInvoicePrice($(this).val());
    });

    resetDateInput();
  }

  $(document).on('click', '.find-category', function(e) {
    e.preventDefault();

    var value = $(this).attr('data-id');
    var index = $(this).attr('data-index');
    var parent = parseInt($(this).attr('data-parent'));
    var level = parseInt($(this).attr('data-level'));
    var mainParentElement = null;
    var mainParentValue = null;
    var mainParentIndex = null;
    var content = '<input type="hidden" name="category1['+index+']" value="'+value+'">';

    if (level === 1) {
      mainParentElement = $('#parent-category-'+parent);
      mainParentValue = mainParentElement.attr('data-id');
      mainParentIndex = mainParentElement.attr('data-index');

      // content = '<input type="hidden" name="category1['+mainParentIndex+']" value="'+mainParentValue+'">';
      // content += '<input type="hidden" name="category2['+index+']" value="'+value+'">';
      content = '<input type="hidden" name="category2['+index+']" value="'+value+'">';
    } else if (level === 2) {
      mainParentElement = $('#sub-category-'+parent);
      mainParentValue = mainParentElement.attr('data-id');
      mainParentIndex = mainParentElement.attr('data-index');

      var subParent = parseInt(mainParentElement.attr('data-parent'));
      var subParentElement = $('#parent-category-'+subParent);
      var subParentValue = subParentElement.attr('data-id');
      var subParentIndex = subParentElement.attr('data-index');

      // content = '<input type="hidden" name="category1['+mainParentIndex+']" value="'+mainParentValue+'">';
      // content += '<input type="hidden" name="category2['+subParentIndex+']" value="'+subParentValue+'">';
      // content += '<input type="hidden" name="category3['+index+']" value="'+value+'">';
      content = '<input type="hidden" name="category3['+index+']" value="'+value+'">';
    }

    elementHeaderSearchFormFields.html(content);
    elementHeaderSearchForm.submit();
  });

  //--- Duplicate of above - reformat later
  $(document).on('click', '.find-category-mb', function(e) {
    e.preventDefault();

    var value = $(this).attr('data-id');
    var index = $(this).attr('data-index');
    var parent = parseInt($(this).attr('data-parent'));
    var level = parseInt($(this).attr('data-level'));
    var mainParentElement = null;
    var mainParentValue = null;
    var mainParentIndex = null;
    var content = '<input type="hidden" name="category1['+index+']" value="'+value+'">';

    if (level === 1) {
      mainParentElement = $('#parent-category-mb-'+parent);
      mainParentValue = mainParentElement.attr('data-id');
      mainParentIndex = mainParentElement.attr('data-index');

      // content = '<input type="hidden" name="category1['+mainParentIndex+']" value="'+mainParentValue+'">';
      // content += '<input type="hidden" name="category2['+index+']" value="'+value+'">';
      content = '<input type="hidden" name="category2['+index+']" value="'+value+'">';
    } else if (level === 2) {
      mainParentElement = $('#sub-category-mb-'+parent);
      mainParentValue = mainParentElement.attr('data-id');
      mainParentIndex = mainParentElement.attr('data-index');

      var subParent = parseInt(mainParentElement.attr('data-parent'));
      var subParentElement = $('#parent-category-mb-'+subParent);
      var subParentValue = subParentElement.attr('data-id');
      var subParentIndex = subParentElement.attr('data-index');

      // content = '<input type="hidden" name="category1['+mainParentIndex+']" value="'+mainParentValue+'">';
      // content += '<input type="hidden" name="category2['+subParentIndex+']" value="'+subParentValue+'">';
      // content += '<input type="hidden" name="category3['+index+']" value="'+value+'">';
      content = '<input type="hidden" name="category3['+index+']" value="'+value+'">';
    }

    elementHeaderSearchFormFields.html(content);
    elementHeaderSearchForm.submit();
  });
  //--- Duplicate of above - reformat later

  $(document).on('click', '.find-region', function(e) {
    e.preventDefault();

    var region = $(this).attr('data-region');
    var content = '<input type="hidden" name="region" value="'+region+'">';

    elementHeaderSearchFormFields.html(content);
    elementHeaderSearchForm.submit();
  });

  if (elementProductDetailPage.length) {
    var submit = {
      main: $('#pdp-main').val(),
      sub: $('#pdp-sub').val(),
    };

    $.post(BASE_URL+'/view-count', $.extend(true, submit, TOKEN));
  }

  if (elementSearchForm.length) {
    $(document).on('change', '.f-category', function(e) {
      e.preventDefault();

      var category = $(this).val();

      if ($(this).is(':checked')) {
        $('#p-cat-sub-'+category).click();
        $('#p-cat-child-'+category).click();
        $('.s-cat-'+category).prop('checked', true);
      } else {
        $('.s-cat-'+category).prop('checked', false);
      }

      delay(function() {
        elementSearchForm.submit();
      }, 1200);
    });

    $(document).on('change', '.f-sub-category', function(e) {
      e.preventDefault();

      var subValue = $(this).val();
      var parentCategory = $(this).attr('data-parent');
      var parentElement = $('#p-cat-'+parentCategory);
      var childrenChecked = $('.s-cat-'+parentCategory+':checked').length;

      if (childrenChecked < 1) {
        // parentElement.prop('checked', false);
      } else {
        // $('#p-cat-child-'+parentCategory).click();
        $('.c-cat-'+subValue).prop('checked', true);
        // parentElement.prop('checked', true);
      }

      delay(function() {
        elementSearchForm.submit();
      }, 1200);
    });

    $(document).on('change', '.f-child-category', function(e) {
      e.preventDefault();

      var parentCategory = $(this).attr('data-parent');
      var parentElement = $('#p-cat-sub-'+parentCategory);
      var childrenChecked = $('.c-cat-'+parentCategory+':checked').length;

      if (childrenChecked < 1) {
        // parentElement.prop('checked', false);
      } else {
        var mainParentElement = $('#s-cat-'+parentCategory);
        var mainParentCategory = mainParentElement.attr('data-parent');

        // $('#p-cat-'+mainParentCategory).prop('checked', true);
        // mainParentElement.prop('checked', true);
        // parentElement.prop('checked', true);
      }

      delay(function() {
        elementSearchForm.submit();
      }, 1200);
    });

    $(document).on('keyup blur', '#f-min-price, #f-max-price', function() {
      delay(function() {
        elementSearchForm.submit();
      }, 1200);
    });

    $('#f-sort').change(function() {
      elementSearchForm.submit();
    });

    $('#f-reset').click(function() {
      window.location.href = window.location.href.split('?')[0];
    });
  }

  $('#state-search').click(function(e) {
    e.preventDefault();

    previousHistoryPage();
  });

  // if (elementListRegion.length > 0) {
  //   $.ajax({
  //     url: 'https://dev.farizdotid.com/api/daerahindonesia/provinsi',
  //     success: function(result) {
  //       var optionProvinces = '';

  //       for (var i = 0; i < result.provinsi.length; i++) {
  //         if (parseInt(result.provinsi[i].id) === 51) {
  //           optionProvinces += '<option value="'+result.provinsi[i].id+'" selected>'+result.provinsi[i].nama+'</option>';
  //         } else {
  //           optionProvinces += '<option value="'+result.provinsi[i].id+'">'+result.provinsi[i].nama+'</option>';
  //         }
  //       }

  //       $('#homepage-select-province').append(optionProvinces).selectric();
  //     }
  //   });

  //   function capitalize(str) {
  //     return str.charAt(0).toUpperCase() + str.slice(1);
  //   }

  //   $('#homepage-select-province').change(function(e) {
  //     e.preventDefault();

  //     $.ajax({
  //       url: 'https://dev.farizdotid.com/api/daerahindonesia/kota?id_provinsi='+$(this).val(),
  //       success: function(result) {
  //         var regionList = '';

  //         for (var i = 0; i < result.kota_kabupaten.length; i++) {
  //           var regionData = result.kota_kabupaten[i].nama;
  //           var regionName = regionData.toLowerCase();

  //           if (regionName.includes('kabupaten ')) {
  //             regionName = regionName.replace('kabupaten ','');
  //           }

  //           if (regionName.includes('kota ')) {
  //             regionName = regionName.replace('kota ','');
  //           }

  //           regionList += '<div class="dc4 mc12">';
  //           regionList += '<a href="javascript:void(0);" class="find-region" data-region="'+regionName+'">'+capitalize(regionName)+'</a>';
  //           regionList += '</div>';
  //         }

  //         elementListRegion.html(regionList);
  //       }
  //     });
  //   });
  // }



  // if (elementListRegion.length > 0) {
  //   $.ajax({
  //     url: 'https://dev.farizdotid.com/api/daerahindonesia/provinsi',
  //     success: function(result) {
  //       var optionProvinces = '';

  //       for (var i = 0; i < result.provinsi.length; i++) {
  //         if (parseInt(result.provinsi[i].id) === 51) {
  //           optionProvinces += '<li data-id="'+result.provinsi[i].id+'" class="selected">'+result.provinsi[i].nama+'</li>';
  //         } else {
  //           optionProvinces += '<li data-id="'+result.provinsi[i].id+'">'+result.provinsi[i].nama+'</li>';
  //         }
  //       }

  //       $('#homepage-list-province').append(optionProvinces);
  //     }
  //   });

  //   searchableProvinceInput.focus(function() {
  //     $(this).closest('.searchable-province').find('ul').show();
  //     $(this).closest('.searchable-province').find('ul li').show();
  //   });

  //   searchableProvinceInput.blur(function() {
  //     var that = this;

  //     setTimeout(function() {
  //       $(that).closest('.searchable-province').find('ul').hide();
  //     }, 300);
  //   });

  //   $(document).on('click', '.searchable-province ul li', function() {
  //     $(this).closest('.searchable-province').find('input').val($(this).text()).blur();
  //     onSelect($(this).data('id'));
  //   });

  //   $('.searchable-province ul li').hover(function() {
  //     $(this).closest('.searchable-province').find('ul li.selected').removeClass('selected');
  //     $(this).addClass('selected');
  //   });
  // }

  $(document).on('keyup', '#product1-input, #product2-input, #product3-input', function() {
    var submit = {
      term: $(this).val(),
      index: $(this).attr('data-index')
    };

    var comparisonResult = $('#product'+submit.index+'-list');
    comparisonResult.html('').hide();

    delay(function() {
      $.post(BASE_URL+'/find-product', $.extend(true, submit, TOKEN), function(response) {
        if (response.status) {
          var content = '';
          var href = new URL(window.location.href);

          for (var i = 0; i < response.data.length; i++) {
            href.searchParams.set('product'+response.index, response.data[i].id);
            content += '<li class="compare-products" data-href="'+BASE_URL+'/compare'+href.search+'">'+response.data[i].name+'</li>';
          }

          comparisonResult.html(content).show();
        }
      });
    }, 1000);
  });

  $('#product1-input, #product2-input, #product3-input').blur(function() {
    var that = this;

    setTimeout(function() {
      $(that).closest('.searchable-content').find('ul').hide();
    }, 300);
  });

  $(document).on('click', '.compare-products', function() {
    window.location.href = $(this).attr('data-href');
  });
});
