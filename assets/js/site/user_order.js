var elementBuyerTransactionDetail = $('#buyer-trx-detail');
var elementBuyerTransactionSharedDetail = $('#buyer-trx-shared-detail');
var elementBuyerActRate = $('.buyer-act-rate');
var elementB2GActComplain = $('.b2g-act-complain');
var elementSellerTransactionDetail = $('#seller-trx-detail');
var elementSellerDeletePayment = $('#delete-order-payment');
var dzProductReviewAttachmentUploader = $('#dz-pra-uploader');
var negotiationElement = $('#negotiation-element');
var negotiationUrl = negotiationElement.attr('data-negotiate-url') || '';
var approveNegotiationUrl = negotiationElement.attr('data-approve-url') || '';
var buyerOrigin = '4b5771';
var sellerOrigin = '48fcb8';
var filterForm = $('#form-filter');
var cancelOrderButton = $('#cancel-order-by-user');
var confirmedButton = $('#popup-confirm-btn');

if (cancelOrderButton.length) {
  cancelOrderButton.on('click', cancelOrder)
}


function cancelOrder() {
  var sharedInvoice = cancelOrderButton.attr('data-orderId')
  var submitedBy = cancelOrderButton.attr('data-submited-by')
  var origin = submitedBy === 'seller' ? sellerOrigin : buyerOrigin;

  var submit = {
    'shared_invoice': sharedInvoice,
    'origin': origin,
  }

  showConfirmPopup("Yakin ?");

  if (confirmedButton.length) {
    confirmedButton.on('click', function () {
      hideConfirmPopup()

      showLoading()

      $.post(BASE_URL+'/user/order/'+sharedInvoice+'/cancel', $.extend(true, submit, TOKEN), function (response) {
        hideLoading()
        if (response.status) {
          window.location.reload()
        }else {
          showGeneralPopup("Terjadi kesalahan!")
        }
      })
    })
  }
}

function tabDetailOrder(e, id) {

  $.each($('.tablinks'), function(key, val) {
    if ($(val).hasClass('active') == true) {
      $(val).removeClass('active');
    }
  });

  $.each($('.tabcontent'), function(key, val) {
    $(val).attr('style', 'display:none;');
  });

  var idTab  = id.split('-');
  var upper  = idTab[1].charAt(0).toUpperCase() + idTab[1].slice(1);
  var tab    = idTab[0] + upper;


  $('#'+id).addClass('active');
  $('#'+tab).removeAttr('style');

}

var processOrderStatus = function(id, submit) {
  $.post(BASE_URL+'/user/order/'+id, $.extend(true, submit, TOKEN), function(response) {
    if (response.status) {
      if (submit.origin === buyerOrigin && submit.state === 'received') {
        window.location.reload();
      } else if (submit.origin === buyerOrigin && (submit.state === 'pending_payment' || submit.state === 'tax_invoice')) {
        // window.location.href = BASE_URL+'/user/payment-confirmation?invoice='+response.shared_id;
        window.location.reload();
      }else if (submit.origin === sellerOrigin && (submit.state === 'shipped' || submit.state === 'partial_delivery')){
        window.location.reload()
      } else if (submit.state === 'cancel'){
        window.location.reload();
      } else {
        if (elementBuyerTransactionSharedDetail.length) {
          window.location.reload();
        } else {
          $('#order-content-status').html(response.content_status);
          $('#order-content-buttons').html(response.content_buttons);
        }
      }
    }

    hideFormPopup();
  });
};

var productReviewPublishFormShow = function(content) {
  $('#popup-publish-review .popup-publish-review-title').html('Detail');
  $('#popup-publish-review .popup-publish-review-content').html(content);
  $('#popup-publish-review').show();
};

var productReviewPublishFormHide = function() {
  $('#popup-publish-review .popup-publish-review-title').html('');
  $('#popup-publish-review .popup-publish-review-content').html('');
  $('#popup-publish-review').hide();
};

var dzProductReviewAttachment = function() {
  var today = new Date();
  var month = today.getMonth() + 1;
  var dirSlug = 'product_reviews/'+today.getFullYear()+'-'+(month < 10 ? '0'+month : month)+'-'+today.getDate();

  dzProductReviewAttachmentUploader.dropzone({
    url: dzUploadUrl,
    paramName: 'file_image',
    maxFilesize: dzMaxSize,
    resizeWidth: 720,
    acceptedFiles: dzAcceptedFiles,
    dictDefaultMessage: '',
    dictFallbackMessage: '',
    // addRemoveLinks: true,
    clickable: '.product-review-attachment',
    params: $.extend(true, {type: 'image', dir: dirSlug, overwrite: 'yes'}, TOKEN),
    previewTemplate: dzPreviewTemplate,
    init: function() {
      this.on('error', function(file, response) {
        // $(file.previewElement).find('.dz-error-message').text(response);

        $('#dz-pra-uploader .dz-image-preview').remove();
        $('#dz-pra-uploader .fa-plus-circle').show();
        $('.pra-tools').hide();

        showGeneralPopup(response);
        elementLoading.hide();
      });

      this.on('addedfile', function(file) {
        elementLoading.show();
      });

      this.on('thumbnail', function(file, dataUrl) {
        $('.dz-image img').hide();
      });
    },
    success: function(file, response) {
      var result = JSON.parse(file.xhr.response);
      var img = result.file_image[0];

      if (typeof img.error !== 'undefined') {
        $('#dz-pra-uploader .fa-plus-circle').show();
        $('.pra-tools').hide();

        showGeneralPopup(img.error);
      } else {
        var url = decodeURIComponent(img.url);
        var parse = new URL(url);
        var random = Math.floor(Math.random() * 20);

        $('#buyer-rate-attachment').val(parse.pathname);
        $('#product-review-attachment-src').attr('src', url+'?v'+random).show();
        $('#dz-pra-uploader .fa-plus-circle').hide();
        $('.pra-tools').show();
      }

      elementLoading.hide();
    }
  });
};

$(document).ready(function() {

  $(".fake-scroll-div").scroll(function(){
    $(".real-scroll-div")
        .scrollLeft($(".fake-scroll-div").scrollLeft());
  });

  $(".real-scroll-div").scroll(function(){
      $(".fake-scroll-div")
          .scrollLeft($(".real-scroll-div").scrollLeft());
  });
  
  $('.fake-scroll-content').attr('style','width:' + $('#table-dashboard').width() + 'px');

  $(document).on('click', '.seller-act-cancel', function(e) {
    cancelPopUp($(this));
  });
  

  $(document).on('click', '#popup-cancel-order-btn', function(e) {
    e.preventDefault();

    var id = $('#seller-cancel-id').val();
    var state = $('#seller-cancel-state').val();
    var reason = $('#popup-cancel-reason').val();
    var user_type = $('#seller-order-user-type').val();
    var origin = user_type == 'seller' ? sellerOrigin : buyerOrigin;
    var submit = {
      state: state,
      origin: origin,
      user_type: user_type,
      reason: reason,
    };

    processOrderStatus(id, submit);
  });

  // if (elementBuyerTransactionDetail.length || elementBuyerTransactionSharedDetail.length) {
    $(document).on('click', '.buyer-act-order', function(e) {
      e.preventDefault();

      var id = 0;

      if (elementBuyerTransactionDetail.length) {
        id = elementBuyerTransactionDetail.attr('data-id');
      } else if (elementBuyerTransactionSharedDetail.length) {
        id = $(this).attr('data-id');
      }

      if (id < 1) {
        showGeneralPopup('Invalid form!');

        return false;
      }

      var state = $(this).attr('data-state');
      var element = '<input id="buyer-order-id" type="hidden" value="'+id+'"><input id="buyer-order-state" type="hidden" value="'+state+'">';

      showFormPopup(MSG_CONFIRMATION, element);
    });

    $(document).on('click', '#popup-form-btn', function(e) {
      e.preventDefault();

      var id = $('#buyer-order-id').val();
      var submit = {
        state: $('#buyer-order-state').val(),
        origin: buyerOrigin,
      };

      processOrderStatus(id, submit);
    });

    if (elementB2GActComplain.length) {
      elementB2GActComplain.click(function(e) {
        e.preventDefault();
        hideRecievedComplaintPopup();
        console.log($(this));
        $('#popup-order-complain').show();
      });

      $('#popup-order-complain-btn').click(function(e) {
        e.preventDefault();

        var description = $('#buyer-order-complain-description').val();
        var descriptionError = $('#buyer-order-complain-description-error');

        descriptionError.html('');

        if (description === '') {
          descriptionError.html(LABEL_MSG_EMPTY);
        } else {
          $('#buyer-order-complain-form').submit();
        }
      });
    }

    $(document).on('click', '.b2g-act-order', function(e) {
      e.preventDefault();

      var id = 0;

      if (elementBuyerTransactionDetail.length) {
        id = elementBuyerTransactionDetail.attr('data-id');
      } else if (elementBuyerTransactionSharedDetail.length) {
        id = $(this).attr('data-id');
      }

      if (id < 1) {
        showGeneralPopup('Invalid form!');

        return false;
      }

      var state = $(this).attr('data-state');
      var element = '<input id="buyer-order-id" type="hidden" value="'+id+'"><input id="buyer-order-state" type="hidden" value="'+state+'">';

      if (state === 'pending_payment' && $('#bast-file').length) {
        if ($('#tax-invoice-file').length) {
          showFormPopup(LABEL_CONTINUE_ORDER, element);
        } else {
          showGeneralPopup(MSG_MISSING_TAX_INVOICE);
        }
      } else if (state === 'received') {
        showRecievedComplaintPopup('Konfirmasi Penerimaan Barang');
      } else if (state === 'missing_bast') {
        showGeneralPopup(MSG_MISSING_BAST);
      } else if (state === 'missing_delivery_paper') {
        showGeneralPopup(MSG_MISSING_DELIVERY_PAPER);
      } else if (state === 'has_complain') {
        showGeneralPopup(MSG_HAS_COMPLAIN);
      } else if (state === 'missing_tax_invoice') {
        showGeneralPopup(MSG_MISSING_TAX_INVOICE);
      } else if (state === 'paid') {
        // showGeneralPopup(MSG_CONFIRMATION);
      }else if (state === 'tax_invoice') {
        showFormPopup(LABEL_CONTINUE_ORDER, element);
      } else {
        showGeneralPopup(MSG_ORDER_INCOMPLETE);
      }
    });

    $('#buyer-to-negotiate').click(function(e) {
      e.preventDefault();

      var submit = {
        'prices': $(':input.negotiated-price').serializeArray(),
        'time': $('#negotiated-time').val(),
        'note': $('#negotiated-note').val(),
        'shipping': $('#shipping-cost').val(),
        'submitted_as': 'buyer',
      };

      $.ajax({
        url: negotiationUrl,
        method: 'POST',
        data: $.extend(true, submit, TOKEN),
        dataType: 'json',
        beforeSend: function() {
          showLoading();
        },
        success: function(response) {
          hideLoading();

          if (response.status) {
            window.location.reload();
          } else {
            showGeneralPopup(response.message);
          }
        }
      });
    });

    // $('#buyer-to-approve').click(function(e) {
      $(document).on('click', '#buyer-to-approve', function(e) {
      e.preventDefault();

      var submit = {
        'origin': buyerOrigin,
      };


      $.ajax({
        url: approveNegotiationUrl,
        method: 'POST',
        data: $.extend(true, submit, TOKEN),
        dataType: 'json',
        beforeSend: function() {
          showLoading();
          $('#approve-negotiation-error').html('');
        },
        success: function(response) {
          hideLoading();

          if (response.status) {
            window.location.reload();
          } else {
            $('#approve-negotiation-error').html(response.message);
          }
        }
      });

    });
  // }

  function gambarValid(data) {
    var type_img = ['image/png','image/jpg','image/jpeg'];
    if (type_img.includes(data[0].files[0].type) == false) {
      data.replaceWith( data.val('').clone( true ) );
      showGeneralPopup('Harap memilih file dengan format gambar');
    } 
  }

  // if (elementSellerTransactionDetail.length) {

    $('#state-img').on('change', function () {
      gambarValid($(this));
    });

    $('.shipped-product-img').on('change', function () {
      gambarValid($(this));
    });

    $(document).on('change', '#shipped-method', function(e) {
      if (e.target.value != '') {
        if (e.target.value == 'normal_shipped') {
          $('.normal_shipped').show();
          $('.self_shipped').hide();
          $('#state-img').attr('required', true)
          $('#shipped_product_img_1').removeAttr('required');
          $('#shipped_product_img_2').removeAttr('required');
          $('#shipped_product_img_3').removeAttr('required');
        } else {
          $('.normal_shipped').hide();
          $('.self_shipped').show();
          $('#shipped_product_img_1').attr('required', true)
          $('#shipped_product_img_2').attr('required', true)
          $('#shipped_product_img_3').attr('required', true)
          $('#state-img').removeAttr('required');
        }
      } else {
        $('.normal_shipped').hide();
        $('.self_shipped').hide();
        $('#shipped_product_img_1').removeAttr('required');
        $('#shipped_product_img_2').removeAttr('required');
        $('#shipped_product_img_3').removeAttr('required');
        $('#state-img').removeAttr('required');
      }
    });

    

    $(document).on('click', '.seller-act-order', function(e) {
      e.preventDefault();
      // var is_downloaded = true;
      
      // if ($(this).data('ppkpayment') == 'pembayaran_langsung') {
      //   $(".btn-download-document").each(function () {
      //     if ($(this).hasClass('terdownload') === false) {
      //       showGeneralPopup('Mohon untuk mengunduh semua dokumen sebelum kirim pesanan');
      //       is_downloaded = false;
      //     }
      //   })
      // }

      e.preventDefault();

      $('#popup-approve-pengiriman-merchant').show();

      
      
      // if (is_downloaded) {
      //   shippedPopUp($(this));
      // }

    });

    $('#approve-pengiriman-merchant-btn').click(function(e) {
        e.preventDefault();

        $('#popup-approve-pengiriman-merchant').hide();

        var state = $('#seller-order-state').val();
        if (state == 'shipped') {

          if ($("#shipped-method").val() == '') {
              showGeneralPopup(MSG_SELECT_METHOD_SHIP);
          }
          var is_valid = false

          if ($("#shipped-method").val() == 'self_courier') {
            if ($('#self-courier-name').val() != '' && $('#self-courier-position').val() != '' && $('#self-courier-telp').val() != '' && $('#shipped_product_img_1').val() != '' && $('#shipped_product_img_2').val() != '' && $('#shipped_product_img_3').val() != '') {
              is_valid = true;
            } else {
              is_valid = false;
            }
          } else {
            if ($('#seller-waybill').val() != '' && $('#state-img').val() != '') {
              is_valid = true;
            }
          }

          if (is_valid) {
            $('#seller-order-origin').val(sellerOrigin);
            $('#form-shipped-order').submit();
          } else {
            showGeneralPopup('Harap mengisi data dengan lengkap');
          }
        }
        
    });

    $(document).on('click', '#popup-form-btn', function(e) {
      e.preventDefault();

      var id = $('#seller-order-id').val();
      var state = $('#seller-order-state').val();
      var submit = {
        state: state,
        origin: sellerOrigin,
      };

      if (state === 'shipped') {
        submit.waybill = $('#seller-waybill').val();
        submit.delivery_qty = parsePartialDeliveryInput('delivery_qty')
        submit.delivery_pid = parsePartialDeliveryInput('delivery_pid')
      }
      processOrderStatus(id, submit);
    });

    // if ($('.publish-review').length) {
      $(document).on('click', '.publish-review', function(e) {
        e.preventDefault();

        var id = $(this).attr('id');
        var content = $('#'+id+'-template').html();

        productReviewPublishFormShow(content);
      });

      $('#popup-publish-review-btn').click(function(e) {
        e.preventDefault();

        var submit = {
          id: $('#publish-review-id').val(),
          pid: $('#publish-review-pid').val(),
          oid: $('#publish-review-oid').val(),
          origin: sellerOrigin,
        };

        $.post(BASE_URL+'/user/order/publish-review', $.extend(true, submit, TOKEN), function(response) {
          if (response.status) {
            $('#pr-'+submit.pid+'-'+submit.oid).remove();

            productReviewPublishFormHide();
          }
        });
      });
    // }

    if (elementSellerDeletePayment.length) {
      /*elementSellerDeletePayment.click(function(e) {
        e.preventDefault();

        var oid = $(this).attr('data-oid');
        var pid = $(this).attr('data-pid');

        $('#delete-payment-oid').val(oid);
        $('#delete-payment-pid').val(pid);
        $('#popup-delete-payment').show();
      });

      $('#popup-delete-payment-btn').click(function(e) {
        e.preventDefault();

        var submit = {
          oid: $('#delete-payment-oid').val(),
          pid: $('#delete-payment-pid').val(),
          origin: buyerOrigin,
        };

        $.post(BASE_URL+'/user/order/delete-payment', $.extend(true, submit, TOKEN), function(response) {
          if (response.deleted) {
            window.location.reload();
          }
        });
      });*/
    }

    $('#seller-to-approve').click(function(e) {
      e.preventDefault();

      $('#popup-approve-order').show();
    });

    $('#popup-approve-order-btn').click(function(e) {
      e.preventDefault();

      $('#popup-approve-order').hide();

      $.ajax({
        url: approveNegotiationUrl,
        method: 'POST',
        data: $.extend(true, {'origin': sellerOrigin}, TOKEN),
        dataType: 'json',
        beforeSend: function() {
          showLoading();
        },
        success: function(response) {
          hideLoading();

          if (response.status) {
            window.location.reload();
          } else {
            showGeneralPopup(response.message);
          }
        }
      });
    });

    $('#seller-to-negotiate').click(function(e) {
      e.preventDefault();

      var submit = {
        'prices': $(':input.negotiated-price').serializeArray(),
        'time': $('#negotiated-time').val(),
        'note': $('#negotiated-note').val(),
        'shipping': $('#shipping-cost').val(),
        'submitted_as': 'seller',
      };

      $.ajax({
        url: negotiationUrl,
        method: 'POST',
        data: $.extend(true, submit, TOKEN),
        dataType: 'json',
        beforeSend: function() {
          showLoading();
        },
        success: function(response) {
          hideLoading();

          if (response.status) {
            window.location.reload();
          } else {
            showGeneralPopup(response.message);
          }
        }
      });
    });
  // }

  // if (elementBuyerActRate.length) {
    elementBuyerActRate.click(function(e) {
      e.preventDefault();

      var action = $(this).attr('data-action');
      var title = decodeURIComponent($(this).attr('data-title'));
      var parts = action.split('|');
      var pid = parts[0] || 0;
      var oid = parts[1] || 0;
      var element = $('#product-review-form-template').html();
      var content = element.replace('**pid**', pid).replace('**oid**', oid);

      showOrderReviewPopup(LABEL_RATE+' "'+title+'"', content);

      try {
        dzProductReviewAttachment();
      } catch (e) {
        dzProductReviewAttachmentUploader[0].dropzone.destroy();
        dzProductReviewAttachment();
      }
    });

    $(document).on('click', '.rate-star', function(e) {
      var value = $(this).attr('data-value');

      $('.star').css('color', 'black');

      for (var i = 1; i <= value; i++) {
        $('#star-'+i).css('color', 'red');
      }

      $('#buyer-rate-rating').val(value);
    });

    $(document).on('click', '.delete-pra', function(e) {
      e.preventDefault();

      elementLoading.show();

      var input = $('#buyer-rate-attachment');
      var submit = {
        id: 0,
        path: input.val(),
        src: 'product_review'
      };

      $.post(dzDeleteUrl, $.extend(true, submit, TOKEN), function(response) {
        if (response.deleted) {
          input.val('');
          $('#product-review-attachment-src').attr('src', BASE_URL+'/dist/img/user.jpg');
          $('#dz-pra-uploader .dz-image-preview').remove();
          $('#dz-pra-uploader .fa-plus-circle').show();
          $('.pra-tools').hide();
        }

        elementLoading.hide();
      });
    });

    $(document).on('click', '#popup-order-review-btn', function(e) {
      e.preventDefault();

      elementLoading.show();

      var elementReviewError = $('#review-error');
      var elementRatingError = $('#rating-error');
      var elementAttachmentError = $('#attachment-error');
      var submit = {
        pid: $('#buyer-rate-pid').val(),
        oid: $('#buyer-rate-oid').val(),
        review: $('#buyer-rate-review').val(),
        rating: $('#buyer-rate-rating').val(),
        attachment: $('#buyer-rate-attachment').val(),
        origin: buyerOrigin,
      };

      elementReviewError.html('');
      elementRatingError.html('');
      elementAttachmentError.html('');

      $.post(BASE_URL+'/user/order/review', $.extend(true, submit, TOKEN), function(response) {
        if (response.status) {
          // elementBuyerActRate.remove();
          // $('#opr-'+submit.pid+'-'+submit.oid).html(response.content);

          // hideOrderReviewPopup();
          window.location.reload();
        } else {
          elementReviewError.html(response.errors.review || '');
          elementRatingError.html(response.errors.rating || '');
          elementAttachmentError.html(response.errors.attachment || '');
        }

        elementLoading.hide();
      });
    });
  // }

  // Disable input alphabet just allowed numeric input
  $('#approve-negotiation-budget-ceiling, #approve-negotiation-ppk-nip, #approve-negotiation-treasurer-nip').on('input', function() {
    $(this).val($(this).val().replace(/[^0-9]/g, ''));
  });

  $('#approve-negotiation-source-of-fund').on('change', function (e) {
    if (e.target.value == 'LAINNYA') {
      $('#approve-negotiation-other-source-of-fund').removeClass('hide');
    } else {
      $('#approve-negotiation-other-source-of-fund').addClass('hide');
    }
  });

  $('#approve-negotiation-fiscal-year').on('input', function() {
    $(this).val($(this).val().replace(/[^0-9]/g, ''));
  });

  $('#negotiated-time').on('input', function() {
    $(this).val($(this).val().replace(/[^0-9]/g, ''));
  });

  $('#self-courier-telp').on('input', function() {
    $(this).val($(this).val().replace(/[^0-9]/g, ''));
  });

  
  $('.btn-test-ppk').on('click', function(e) {
    $("#myModal").show();
    $("#myModal").hide();
  });

  $('.btn-received-ppk').on('click', function(e) {
    var key = $(this).data('key');
    var id  = $(this).data('id');
    var document  = $(this).data('document');

    console.log(document)

    $('#popup-received-ppk-'+key + ' iframe').attr('src' , document);
    $('#popup-received-ppk-'+key).show();


    $('#popup-received-btn-'+key).on('click', function (e) {
      e.preventDefault();

      var submit = {
        id: id,
      };
      $.post(BASE_URL+'/user/ppk/approve', $.extend(true, submit, TOKEN), function(response) {
        if (response.status) {
          window.location.reload();
        } else {
          $('#popup-received-ppk-'+key).hide();
        }
      });
    });
  });

  $('.btn-confirm-order-ppk').on('click', function(e) {
    var key = $(this).data('key');
    var id  = $(this).data('id');
    var document  = $(this).data('document');

    console.log(document)
    $('#popup-confirm-order-ppk-'+key+' iframe').attr( 'src', document);
    $('#popup-confirm-order-ppk-'+key).show();
    
    $('#popup-confirm-order-btn-'+key).on('click', function (e) {
      e.preventDefault();
      
      var submit = {
        order_id: id,
      };
      $.post(BASE_URL+'/order/confirmation-ppk', $.extend(true, submit, TOKEN), function(response) {
        if (response.status) {
          window.location.reload();
        } else {
          $('#popup-confirm-order-ppk-'+key).hide();
        }
      });
    });
  });


  $('.btn-approve-ppk').on('click', function(e) {
    var key = $(this).data('key');
    var id  = $(this).data('id');
    $('#popup-approve-ppk-'+key).show();

    $('#popup-approve-btn-'+key).on('click', function (e) {
      e.preventDefault();

      var submit = {
        id: id,
      };
      $.post(BASE_URL+'/user/ppk/approve', $.extend(true, submit, TOKEN), function(response) {
        if (response.approved) {
          window.location.reload();
        } else {
          $('#popup-approve-ppk-'+key).hide();
        }
      });
    });
  });

  $('.ppk-complain').on('click', function (e) {
    e.preventDefault();
    var key = $(this).data('key');
    $('#popup-received-ppk-'+key).hide();
    $('#popup-order-complain-'+key).show();
    $('#popup-order-complain-'+key+'-btn').click(function(e) {
      e.preventDefault();
  
      var description = $('#buyer-order-complain-'+key+'-description').val();
      var descriptionError = $('#buyer-order-complain-'+key+'-description-error');
  
      descriptionError.html('');
  
      if (description === '') {
        descriptionError.html(LABEL_MSG_EMPTY);
      } else {
        $('#buyer-order-complain-'+key+'-form').submit();
      }
    });
  });

  

  $('.btn-approve-ppk').on('click', function(e) {
    e.preventDefault();

    var ppkId = $(this).data('id');
    var element = '<input id="user-ppk-treasurer-id" type="hidden" value="'+ppkId+'">';

    showFormPopup(MSG_CONFIRMATION, element);
    $('#popup-form-btn').html("Ya")
    $('#closeButton').html("Tidak")
    $(document).on('click', '#popup-form-btn', function(e) {
      e.preventDefault();

      var submit = {
        id: $('#user-ppk-treasurer-id').val(),
      };
      $.post(BASE_URL+'/user/ppk/approve', $.extend(true, submit, TOKEN), function(response) {
        if (response.approved) {
          window.location.reload();
        } else {
          $('#popup-form').hide();
        }
      });

    });
  });

  if (filterForm.length) {
    $('#btn-clear-filter').on('click', function (e){
      e.preventDefault();

      $('#filter-keyword').val('')
      $('#filter-status').val('')

      elementLoading.show()
      filterForm.submit()
    })

    $('#btn-submit-filter').on('click', function (e) {
      e.preventDefault()

      elementLoading.show()
      filterForm.submit()
    })

    $('#search-ppk-btn').on('click', function (e) {
      e.preventDefault()

      elementLoading.show()
      $('#filter-ppk-form').submit()
    })

    $('#btn-export-filter').on('click', function (e) {
      window.open( $(this).data('href') + '?keyword=' + encodeURIComponent($('#filter-keyword').val()) + '&status=' + encodeURIComponent($('#filter-status').val()));
    });
    
  }
  
  $('#export-ppk-btn').on('click', function (e) {
    window.open($(this).data('href') + '?keyword=' + encodeURIComponent($('#ppk-search-invoice').val()) + '&status=' + encodeURIComponent($('#search-filter-status').val()) + '&filter_status_order=' + encodeURIComponent($('#search-filter-status-order').val()));
  });

  function shippedPopUp(input) {
    
    var id = elementSellerTransactionDetail.attr('data-id');
    var state = input.attr('data-state');
    var delivery = input.attr('data-delivery') || 'pay';
    var element = '<input id="seller-order-id" type="hidden" value="'+id+'"><input id="seller-order-state" type="hidden" value="'+state+'">';
    var tax_invoice_file = $('#tax-invoice-file')
    var tax_invoice_state = $('#tax-invoice-state')
    var b2g = $('#downloadFilesB2g')

    if ((tax_invoice_file !== undefined && tax_invoice_file.val() !== "" && tax_invoice_state.length) || b2g.length || tax_invoice_state.attr('data-state') === "1") {
      if (delivery === 'free') {
        element += '</form>';
        showFormPopup(MSG_ORDER_PROCESSED, element);
      } else {
        // if (state === 'shipped') {
        //   element += '<div class="input"><input id="seller-waybill" type="text" placeholder="'+LABEL_INPUT_WAYBILL+'" required></div><label style="float:left">Foto Resi</label><br><div class="input"><input id="state-img" type="file" name="state-img" style="heigth: 48px;width: 100%;padding: 16px;border-radius: 8px;background: #EDEDED;color: #000;" required></div>';
        //   element += '</form>';
        //   if (shippedMethod == 'self_courier') {
        //     element += '<label style="float:left">Nama Penerima</label><br><div class="input"><input id="self-courier-name" type="text" placeholder="Nama Penerima" required></div>';
        //     element += '<label style="float:left">Jabatan Penerima</label><br><div class="input"><input id="self-courier-position" type="text" placeholder="Nama Penerima" required></div>';
        //     element += '<label style="float:left">Alamat Penerima</label><br><div class="input"><input id="self-courier-address" type="text" placeholder="Nama Penerima" required></div>';
        //     element += '<label style="float:left">Foto Produk</label><br><div class="input"><input id="shipped-product-img" type="file" style="heigth: 48px;width: 100%;padding: 16px;border-radius: 8px;background: #EDEDED;color: #000;" required></div>';
        //     element += '</form>';
        //   } 
        // }

        showFormPopup(MSG_CONFIRMATION, element);
      }
    }else if (state === 'shipped' && delivery === 'partial_delivery'){

      element += '<div class="input"><input id="seller-waybill" type="text" placeholder="'+LABEL_INPUT_WAYBILL+'" required></div>';
      showFormPopup(MSG_CONFIRMATION, element);
    } else {
      showGeneralPopup('Mohon melengkapi faktur pajak terlebih dahulu')
    }
  }

  function cancelPopUp(input) {
    
    var id = input.data('order-id');
    var state = input.data('state');
    var user_type = input.data('user');
    var element = '<input id="seller-cancel-id" type="hidden" value="'+id+'"><input id="seller-cancel-state" type="hidden" value="'+state+'"><input id="seller-order-user-type" type="hidden" value="'+user_type+'">';
    
    element += '<div class="input"><input id="popup-cancel-reason" type="text" placeholder="'+MSG_REASON_CANCEL+'" required></div>'

    showCancelPopup(MSG_REASON_CANCEL,element);
  }

  function parsePartialDeliveryInput(name) {
    var parsedStr = '';
    var selector = "input[name='"+name+"[]']"

    if ($(selector).length) {
      var tmp = $(selector).map(function () {
        return $(this).val();
      }).get();

      parsedStr = tmp.join('|')
    }

    return parsedStr;
  }
  
});



