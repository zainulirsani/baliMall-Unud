var elementCancelOrder = $('#btn-cancel-order');
var elementOpenChatHistory = $('.open-chat-history');
var elementDeleteOrderPayment = $('#delete-order-payment');

$(document).ready(function() {
  if ($('.order-tabs').length) {
    tabStepsFunction('order');
  }

  $("#input-dokumen-npwp").inputmask({
    mask: "99.999.999.9-999.999",
    placeholder: " ",
    showMaskOnHover: false,
    showMaskOnFocus: false,
    onBeforePaste: function (pastedValue, opts) {
      return pastedValue;
    },
  });

  $('.cbox-gallery').colorbox({rel: 'cbox-gallery', width: '80%'});

  if (elementCancelOrder.length) {
    elementCancelOrder.click(function(e) {
      e.preventDefault();

      var id = $(this).attr('data-id');

      bootbox.confirm(CONFIRM_MSG, function(confirmed) {
        if (confirmed) {
          $.post(BASE_URL+'/'+ADMIN_PATH+'/order/'+id+'/cancel', $.extend(true, {}, TOKEN), function(response) {
            if (response.status) {
              window.location.reload();
            }
          });
        }
      });
    });
  }

  if (elementOpenChatHistory.length) {
    elementOpenChatHistory.click(function(e) {
      e.preventDefault();

      var room = $(this).attr('data-room') || '';
      var initiator = $(this).attr('data-initiator') || 0;

      if (room !== '') {
        $.post(BASE_URL+'/'+ADMIN_PATH+'/user/chat/'+room, $.extend(true, {initiator: initiator}, TOKEN), function(response) {
          if (response.status && response.content !== '') {
            $('#modal-order-chat-content').html(response.content);
            $('#modal-order-chat').modal();
          }
        });
      }
    });
  }

  if (elementDeleteOrderPayment.length) {
    elementDeleteOrderPayment.click(function(e) {
      e.preventDefault();

      var submit = {
        oid: $(this).attr('data-oid'),
        pid: $(this).attr('data-pid'),
      };

      bootbox.confirm(CONFIRM_MSG, function(confirmed) {
        if (confirmed) {
          $.post(BASE_URL+'/'+ADMIN_PATH+'/order/delete_payment', $.extend(true, submit, TOKEN), function(response) {
            if (response.deleted) {
              window.location.reload();
            }
          });
        }
      });
    });
  }

  $(document).on('click', '.btn-restore-order', function(e) {
    e.preventDefault();

    var submit = {
      sid: $(this).attr('data-shared-id'),
    };

    bootbox.confirm(CONFIRM_MSG, function(confirmed) {
      if (confirmed) {
        $.post(BASE_URL+'/'+ADMIN_PATH+'/order/restore', $.extend(true, submit, TOKEN), function(response) {
          if (response.restored) {
            window.location.reload();
          }
        });
      }
    });
  });


  $('.reupload-btn').click(function () {
    var type = $(this).data('file');
    $("#reupload-" + type).trigger('click');
    $("#reupload-" + type).change(function () {
      gambarValid($(this), type);
    });

  });

});

function changeButton(btn, classBtn, has) {
  if (classBtn == 'success') {
    btn.html('<i class="fa fa-check" aria-hidden="true"></i> Draft')
  } else if (classBtn == 'primary') {
    btn.html('<i class="fa fa-upload" aria-hidden="true"></i> ' + btn.data('text'))
  }
  
  if (btn.hasClass('btn-' + has)) {
    btn.removeClass('btn-' + has)
  }
  if (!btn.hasClass('btn-' + has)) {
    btn.addClass('btn-' + classBtn)
  }
}

function gambarValid(data, type) {
  var ekstensi = /(\.jpg|\.jpeg|\.png|\.pdf|\.doc|\.docx|\.gif)$/i;
  if (!ekstensi.exec(data.val())) {
    $('#text-upload-' + type).html('Ekstensi file tidak valid!');
    $('#text-upload-' + type).show();
    data.replaceWith( data.val('').clone( true ) );
    changeButton($('#btn-reupload-' + type), 'primary' , 'success');
  } else {
    if (data[0].files[0].size>10000000) {
      $('#text-upload-' + type).html('Ukuran file diatas 10Mb');
      $('#text-upload-' + type).show();
      data.replaceWith( data.val('').clone( true ) );
      changeButton($('#btn-reupload-' + type), 'primary' , 'success');
    } else {
      $('#text-upload-' + type).html('');
      $('#text-upload-' + type).hide();
      changeButton($('#btn-reupload-' + type), 'success' , 'primary');
    }
  }
}
