var elementProductForm = $('#product-form');
var elementProductOwnedBy = $('#input-owned-by');
var elementProductImagesInput = $('#product-images-input');

function downloadFile(response) {
  var blob = new Blob([response], {type: 'application/pdf'})
  var url = URL.createObjectURL(blob);
  location.assign(url);
}
var generateProductQRCode = function(submit) {
  $.ajax({
    type: 'POST',
    url: BASE_URL+'/'+ADMIN_PATH+'/product/qrcode?ses='+makeId(8),
    data: $.extend(true, submit, TOKEN),
    xhrFields: {
      responseType: 'blob' // to avoid binary data being mangled on charset conversion
    },
    success: function(blob, status, xhr) {
      // check for a filename
      var filename = '';
      var disposition = xhr.getResponseHeader('Content-Disposition');

      if (disposition && disposition.indexOf('attachment') !== -1) {
        var filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
        var matches = filenameRegex.exec(disposition);

        if (matches != null && matches[1]) {
          filename = matches[1].replace(/['"]/g, '');
        }
      }

      if (typeof window.navigator.msSaveBlob !== 'undefined') {
        // IE workaround for "HTML7007: One or more blob URLs were revoked by closing the blob for which they were created.
        // These URLs will no longer resolve as the data backing the URL has been freed."
        window.navigator.msSaveBlob(blob, filename);
      } else {
        var URL = window.URL || window.webkitURL;
        var downloadUrl = URL.createObjectURL(blob);

        if (filename) {
          // use HTML5 a[download] attribute to specify filename
          var linkElement = document.createElement('a');

          // safari doesn't support this yet
          if (typeof linkElement.download === 'undefined') {
            window.location.href = downloadUrl;
          } else {
            linkElement.href = downloadUrl;
            linkElement.download = filename;
            document.body.appendChild(linkElement);
            linkElement.click();
          }
        } else {
          window.location.href = downloadUrl;
        }

        setTimeout(function() { URL.revokeObjectURL(downloadUrl); }, 100); // cleanup
      }
    }
  });
};

var downloadFile = function(response) {
  var blob = new Blob([response], {type: 'application/pdf'});

  location.assign(URL.createObjectURL(blob));
}

$(document).ready(function() {
  if (elementProductForm.length) {
    if (elementProductOwnedBy.length) {
      elementProductOwnedBy.select2({
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

    if ($('#input-description').length) {
      initCKEditorBasic('input-description');
    }

    $(document).on('click', '.grid-item-delete', function(e) {
      e.preventDefault();

      var $this = $(this).parent('figure');
      var postData = {
        id: $(this).data('id'),
        path: $(this).data('path'),
        src: 'product_file'
      };

      $.post(BASE_URL+'/'+ADMIN_PATH+'/file/delete', $.extend(true, postData, TOKEN), function(response) {
        if (response.deleted) {
          $this.remove();
        }
      });
    });

    if (elementProductImagesInput.length) {
      var productId = parseInt($('#input-id').val());
      var dirSlug = $('#input-dir-slug').val();
      var dir = productId > 0 ? 'products/'+dirSlug : 'temp/products';

      elementProductImagesInput.dropzone({
        url: BASE_URL+'/'+ADMIN_PATH+'/file/upload',
        paramName: 'file_image',
        maxFilesize: dzMaxSize,
        resizeWidth: 1440,
        acceptedFiles: '.jpeg, .jpg, .png, .gif',
        dictDefaultMessage: '',
        dictFallbackMessage: '',
        params: $.extend(true, {dir: dir}, TOKEN),
        previewTemplate: dzEmptyTemplate.html(),
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
          buttonConfirm.textContent = "Confirm";
          buttonConfirm.className += "sBtn red upBtn right";
          buttonClose.textContent = "Close";
          buttonClose.className += "sBtn red upBtn left";
          editor.appendChild(buttonConfirm);
          editor.appendChild(buttonClose);
          buttonClose.addEventListener("click", function () {
            document.body.removeChild(wrapper);
          });
          buttonConfirm.addEventListener("click", function () {
            // elementLoading.show();
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
          // elementLoading.show();

          const image = new Image();
          image.src = URL.createObjectURL(file);
          editor.appendChild(image);
          const cropper = new Cropper(image, {
            aspectRatio: 4/3,
            viewMode: 1,
            ready: function () {
              editor.style.visibility = "visible";
              // elementLoading.hide()
              //;
            },
          });
        },
        init: function() {
          this.on('error', function(file, response) {
            bootbox.alert(response);
          });

          this.on('addedfile', function(file) {
            Pace.restart();
          });

          this.on('thumbnail', function(file, dataUrl) {
            $('.dz-image img').hide();
          });
        },
        success: function(file, response) {
          response = JSON.parse(response);
          var image = response.file_image[0];

          if (typeof image.error !== 'undefined') {
            bootbox.alert(image.error);
          } else {
            var url = decodeURIComponent(image.url);
            var parse = new URL(url);
            var random = Math.floor(Math.random() * 20);
            var content = parseAndRender('template-item-image', {
              id: productId > 0 ? productId : 0,
              url: url+'?v'+random,
              path_name: parse.pathname,
              file_name: image.name,
              file_mime: image.type
            });

            $('#tab-images-content').append(content);
            $('.cbox-gallery').colorbox({rel: 'cbox-gallery', width: '80%'});
          }
        }
      });
    }
  }

  if ($('.product-tabs').length) {
    tabStepsFunction('product');
  }

  $(document).on('click', '.product-quick-save', function(e) {
    e.preventDefault();

    var id = $(this).attr('data-id');
    var price = $('#p-'+id).val();
    var basePrice = $('#bp-'+id).val();
    var quantity = $('#q-'+id).val();
    var status = $('#s-'+id).val();

    if (parseFloat(price) < 1) {
      $('#modal-global .modal-body').html(QS_SELLING_PRICE);
      $('#modal-global').modal();

      return false;
    }

    if (parseFloat(basePrice) < 1) {
      $('#modal-global .modal-body').html(QS_BASE_PRICE);
      $('#modal-global').modal();

      return false;
    }

    if (parseInt(quantity) < 0) {
      $('#modal-global .modal-body').html(QS_QUANTITY);
      $('#modal-global').modal();

      return false;
    }

    if (price !== '' && basePrice !== '' && quantity !== '' && status !== '') {
      $.post(BASE_URL+'/'+ADMIN_PATH+'/product/quick_save', $.extend(true, {
        'id': id,
        'price': price,
        'base_price': basePrice,
        'quantity': quantity,
        'status': status
      }, TOKEN), function(response) {
        if (response.message !== '') {
          $('#modal-global .modal-body').html(response.message);
          $('#modal-global').modal();
        }
      });
    }
  });

  $(document).on('click', '.product-qrcode', function(e) {
    e.preventDefault();

    var id = $(this).attr('data-id');
    var productUrl = $(this).attr('data-url');
    var productName = $(this).attr('data-product');
    var price = $(this).attr('data-price');

    if (id !== '' && productUrl !== '') {
      generateProductQRCode({
        'id': id,
        'url': productUrl,
        'product': productName,
        'price': price,
        'multiple': 0
      });
    }
  });

  $('#product-qrcode-batch').click(function(e) {
    e.preventDefault();

    var idValues = $('input[name="id[]"]:checked').map(function() { return $(this).val(); }).get();

    if (idValues !== '') {
      generateProductQRCode({
        'id': idValues,
        'url': '',
        'product': '',
        'price': '',
        'multiple': 1
      });
    }
  });
});
