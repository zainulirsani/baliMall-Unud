var userProductTotal = 4;
var elementUserProductForm = $('#user-product-form');
var elementUserProductList = $('#user-product-list');
var elementUserProductSlug = $('#input-dir-slug');
var dzUserProductImage = function(index) {
  var dirSlug = elementUserProductSlug.val() !== '' ? 'products/'+elementUserProductSlug.val() : 'temp/products';

  $('#dz-up-uploader-'+index).dropzone({
    url: dzUploadUrl,
    paramName: 'file_image',
    maxFilesize: dzMaxSize,
    resizeWidth: 1440,
    acceptedFiles: dzAcceptedFiles,
    dictDefaultMessage: '',
    dictFallbackMessage: '',
    // addRemoveLinks: true,
    clickable: '.user-product-img-'+index,
    params: $.extend(true, {type: 'image', dir: dirSlug, overwrite: 'yes'}, TOKEN),
    previewTemplate: dzPreviewTemplate,
    renameFile: function (file) {
      var tmpFile = file.name.split('.');
      var randomStr = randomString(8);
      return  tmpFile[0] + '-' + randomStr + '.' + tmpFile.pop();
    },
    transformFile: function (file, done) {
      const dz = this;
      const wrapper = document.createElement("div");
      wrapper.className += " wrapper-editor";
      const editor = document.createElement("div");
      editor.className += " editor";
      wrapper.appendChild(editor);
      document.body.appendChild(wrapper);
      const buttonConfirm = document.createElement("button");
      const buttonClose = document.createElement("button");
      const buttonFitCrop = document.createElement("button");
      buttonConfirm.textContent = "Confirm";
      buttonConfirm.className += "sBtn red upBtn right";
      buttonClose.textContent = "Close";
      buttonClose.className += "sBtn red upBtn left";
      buttonFitCrop.className += "sBtn red upBtn right-2";
      buttonFitCrop.textContent = '';
      editor.appendChild(buttonConfirm);
      editor.appendChild(buttonClose);
      editor.appendChild(buttonFitCrop);
      buttonClose.addEventListener("click", function () {
        document.body.removeChild(wrapper);
      });
      buttonConfirm.addEventListener("click", function () {
        elementLoading.show();
        const canvas = cropper.getCroppedCanvas({
          width: 1000,
          height: 1000,
        });
        canvas.toBlob(function (blob) {
          dz.createThumbnail(
            blob,
            dz.options.thumbnailWidth,
            dz.options.thumbnailHeight,
            dz.options.thumbnailMethod,
            false,
            function (dataURL) {
              dz.emit("thumbnail", file, dataURL);
              done(blob);
            }
          );
        });

        document.body.removeChild(wrapper);
      });

      elementLoading.show();

      var image = new Image();
      image.src = URL.createObjectURL(file);
      editor.appendChild(image);
      var cropper = new Cropper(image, {
        viewMode: 2,
        ready: function () {
          editor.style.visibility = "visible";
          elementLoading.hide();
        },
      });
      $('.right-2').html('<i class="fa fa-expand" aria-hidden="true"></i>')
      
      buttonFitCrop.addEventListener("click", function () {
        cropper.destroy();
          $('.editor img').remove();
          var image = new Image();
          image.src = URL.createObjectURL(file);
          editor.appendChild(image);
          var p = new Cropper(image, {
            autoCrop: true,
            autoCropArea: 1,
            viewMode: 2,
            ready: function () {
              editor.style.visibility = "visible";
              elementLoading.hide();
            },
          });
    
          cropper = p;
      });

    },
    init: function() {
      this.on('error', function(file, response) {
        // $(file.previewElement).find('.dz-error-message').text(response);

        $('#dz-up-uploader-'+index+' .dz-image-preview').remove();
        $('#dz-up-uploader-'+index+' .fa-plus-circle').show();
        // $('.pp-tools').hide();

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
        $('#dz-up-uploader-'+index+' .fa-plus-circle').show();
        // $('.pp-tools').hide();

        showGeneralPopup(img.error);
      } else {
        var url = decodeURIComponent(img.url);
        var parse = new URL(url);
        var random = Math.floor(Math.random() * 20);
        var fileName = img.name;
        var fileMime = img.type;

        $('#product-img-'+index+'-src').attr('src', url+'?v'+random).show();
        $('#product-img-'+index+'-temp').val(parse.pathname);
        $('#product-img-'+index+'-name').val(fileName);
        $('#product-img-'+index+'-mime').val(fileMime);
        $('#dz-up-uploader-'+index+' .fa-plus-circle').hide();
        // $('.pp-tools').show();
      }

      elementLoading.hide();
    }
  });
};

$(document).ready(function() {
  if (elementUserProductForm.length) {
    initCKEditorBasic('input-description');

    $('#input-category').selectric({
      disableOnMobile: false,
      nativeOnMobile: false,
      multiple: {
        separator: ', ',
        keepMenuOpen: true,
        maxLabelEntries: false
      }
    });

    for (var index = 1; index <= userProductTotal; index++) {
      dzUserProductImage(index);
    }

    $(document).on('click', '.remove-product-img', function(e) {
      e.preventDefault();

      var index = $(this).attr('data-index');
      var path = $('#product-img-'+index+'-temp').val();
      // var backup = $('#product-img-'+index+'-old').val();
      var bg = 'dist/img/bg.jpg';
      var submit = {
        id: parseInt($('#product-img-'+index+'-id').val()),
        path: path
      };

      if (path !== bg) {
        $('#product-img-'+index+'-src').attr('src', BASE_URL+'/'+bg);
        $('#product-img-'+index+'-temp').val('');
        $('#product-img-'+index+'-name').val('');
        $('#product-img-'+index+'-mime').val('');
        $('#dz-up-uploader-'+index+' .dz-image-preview').remove();

        // $.post(dzDeleteUrl, $.extend(true, submit, TOKEN), function(response) {
        //   if (response.deleted) {
        //     $('#product-img-'+index+'-src').attr('src', BASE_URL+'/'+bg);
        //     $('#product-img-'+index+'-temp').val('');
        //     $('#product-img-'+index+'-name').val('');
        //     $('#product-img-'+index+'-mime').val('');
        //     $('#dz-up-uploader-'+index+' .dz-image-preview').remove();
        //   }
        // });
      }
    });
  }

  if (elementUserProductList.length) {
    $(document).on('click', '.act-delete', function(e) {
      e.preventDefault();

      var productId = $(this).attr('data-id');
      var element = '<input id="user-product-id" type="hidden" value="'+productId+'">';

      showFormPopup(MSG_CONFIRMATION, element);
    });

    $(document).on('click', '#popup-form-btn', function(e) {
      e.preventDefault();

      var submit = {
        id: $('#user-product-id').val(),
        user_id: elementUserProductList.attr('data-id')
      };

      $.post(BASE_URL+'/user/product/delete', $.extend(true, submit, TOKEN), function(response) {
        if (response.deleted) {
          window.location.reload();
        } else {
          $('#popup-form').hide();
        }
      });
    });

    $(document).on('keyup blur', '#keywords', function() {
      var keywords = $(this).val();

      delay(function() {
        if (keywords === '') {
          $('#page').val(1);
        }

        $('#user-product-list-form').submit();
      }, 1200);
    });
  }
});
