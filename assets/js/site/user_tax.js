var userTaxDocumentForm = $('#user-tax-document-form');
var elementUserTaxDocumentList = $('#user-tax-document-list');
var dzImageUploader = $('#dz-image-uploader');

var dzImage = function() {
  var dirSlug = elementUserSlug.length ? 'users/'+elementUserSlug.val() : 'temp/users';
  var overwrite = elementOverwrite.length ? 'yes' : 'no';

  dzImageUploader.dropzone({
    url: dzUploadUrl,
    paramName: 'file_image',
    maxFilesize: dzMaxSize,
    resizeWidth: 720,
    acceptedFiles: dzAcceptedFiles,
    dictDefaultMessage: '',
    dictFallbackMessage: '',
    // addRemoveLinks: true,
    clickable: '.upload-image',
    params: $.extend(true, {type: 'image', dir: dirSlug, overwrite: overwrite}, TOKEN),
    previewTemplate: dzPreviewTemplate,
    init: function() {
      this.on('error', function(file, response) {
        // $(file.previewElement).find('.dz-error-message').text(response);

        $('#dz-pp-uploader .dz-image-preview').remove();
        $('#dz-pp-uploader .fa-plus-circle').show();

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
        $('#dz-pp-uploader .fa-plus-circle').show();

        showGeneralPopup(img.error);
      } else {
        var url = decodeURIComponent(img.url);
        var parse = new URL(url);
        var random = Math.floor(Math.random() * 20);

        $('#image-temp').val(parse.pathname);
        $('#img-src').attr('src', url+'?v'+random).show();
        $('#dz-pp-uploader .fa-plus-circle').hide();
      }

      elementLoading.hide();
    }
  });
};

$(
  "#no_npwp"
).on("input", function () {
  $(this).val(
    $(this)
      .val()
      .replace(/[^0-9]/g, "")
  );
});

$("#no_npwp").inputmask({
  mask: "99.999.999.9-999.999",
  placeholder: " ",
  showMaskOnHover: false,
  showMaskOnFocus: false,
  onBeforePaste: function (pastedValue, opts) {
    var processedValue = pastedValue;
    //do something with it
    return processedValue;
  },
});

$(document).ready(function() {

  $(document).on('click', '.act-delete-ppk', function(e) {
    e.preventDefault();

    var ppkId = $(this).attr('data-id');
    var element = '<input id="user-ppk-treasurer-id" type="hidden" value="'+ppkId+'">';

    showFormPopup(MSG_CONFIRMATION, element);
    $('#popup-form-btn').html("Ya")
    $('#closeButton').html("Tidak")
    $(document).on('click', '#popup-form-btn', function(e) {
      e.preventDefault();

      var submit = {
        id: $('#user-ppk-treasurer-id').val(),
      };
      $.post(BASE_URL+'/user/ppk/delete', $.extend(true, submit, TOKEN), function(response) {
        if (response.deleted) {
          window.location.reload();
        } else {
          $('#popup-form').hide();
        }
      });

    });
  });

  $(document).on('click', '.act-delete-satker', function(e) {
    e.preventDefault();

    var satkerId = $(this).attr('data-id');
    var element = '<input id="satker-id" type="hidden" value="'+satkerId+'">';

    showFormPopup(MSG_CONFIRMATION, element);
    $('#popup-form-btn').html("Ya")
    $('#closeButton').html("Tidak")
    $(document).on('click', '#popup-form-btn', function(e) {
      e.preventDefault();

      var submit = {
        id: $('#satker-id').val(),
      };
      $.post(BASE_URL+'/user/satker/delete', $.extend(true, submit, TOKEN), function(response) {
        if (response.deleted) {
          window.location.reload();
        } else {
          $('#popup-form').hide();
        }
      });

    });
  });
  
  if (userTaxDocumentForm.length) {
    if (dzImageUploader.length) {
      dzImage();
    }
  }

  $(document).on('click', '.act-delete-pic', function(e) {
    e.preventDefault();

    var picId = $(this).attr('data-id');
    var element = '<input id="user-pic-document-id" type="hidden" value="'+picId+'">';

    showFormPopup(MSG_CONFIRMATION, element);
    $('#popup-form-btn').html("Ya")
    $('#closeButton').html("Tidak")
    $(document).on('click', '#popup-form-btn', function(e) {
      e.preventDefault();

      var submit = {
        id: $('#user-pic-document-id').val(),
        user_id: elementUserPicDocumentList.attr('data-id')
      };

    });
  });


  if (elementUserTaxDocumentList.length) {
    $(document).on('click', '.act-delete', function(e) {
      e.preventDefault();

      var taxId = $(this).attr('data-id');
      var element = '<input id="user-tax-document-id" type="hidden" value="'+taxId+'">';

      showFormPopup(MSG_CONFIRMATION, element);
      $('#popup-form-btn').html("Ya")
      $('#closeButton').html("Tidak")
    });

    $(document).on('click', '#popup-form-btn', function(e) {
      e.preventDefault();

      var page = elementUserTaxDocumentList.attr('data-page');
      var submit = {
        id: $('#user-'+page+'-document-id').val(),
        user_id: elementUserTaxDocumentList.attr('data-id')
      };

      $.post(BASE_URL+'/user/'+page+'/delete', $.extend(true, submit, TOKEN), function(response) {
        if (response.deleted) {
          window.location.reload();
        } else {
          $('#popup-form').hide();
        }
      });
    });
  }
});
