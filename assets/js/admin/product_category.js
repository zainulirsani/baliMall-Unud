var elementProductCategoryForm = $('#product-category-form');
var elementDesktopImage = $('#input-desktop-image');
var elementMobileImage = $('#input-mobile-image');

var initFileUpload = function(fileElement, fileInput) {
  var productCategoryId = parseInt($('#input-id').val());
  var dirSlug = $('#input-dir-slug').val();
  var dir = productCategoryId > 0 ? 'product_categories/'+dirSlug : 'temp/product_categories';

  fileElement.dropzone({
    url: BASE_URL+'/'+ADMIN_PATH+'/file/upload',
    paramName: 'file_image',
    maxFilesize: dzMaxSize,
    resizeWidth: 1440,
    acceptedFiles: '.jpeg, .jpg, .png, .gif',
    dictDefaultMessage: '',
    dictFallbackMessage: '',
    // addRemoveLinks: true,
    // clickable: '.input-img',
    params: $.extend(true, {dir: dir}, TOKEN),
    previewTemplate: dzEmptyTemplate.html(),
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

        $('.'+fileInput+'-img').attr('src', url+'?v'+random).show();
        $('#'+fileInput+'-image-tmp').val(parse.pathname);
      }
    }
  });
};

function readURL(input, fileInput) {
  if (input.files && input.files[0]) {
    var productCategoryId = parseInt($('#input-id').val());
    var dirSlug = $('#input-dir-slug').val();
    var dir = productCategoryId > 0 ? 'product_categories/'+dirSlug : 'temp/product_categories';
    $('#input-dir').val(dir);
    
    var reader = new FileReader();
    reader.onload = function (e) {
       console.log(e.target.result, 'ppppp');
       $('.'+fileInput+'-img').attr('src', e.target.result).show();
    };
    reader.readAsDataURL(input.files[0]);
  }
}

$(document).ready(function() {
  if (elementProductCategoryForm.length) {
    if ($('#input-description').length) {
      initCKEditorBasic('input-description');
    }

    elementDesktopImage.on('change', function () {
      readURL(this, 'desktop');
    })

    elementMobileImage.on('change', function () {
      readURL(this, 'mobile');
    })

    // if (elementMobileImage.length) {
    //   initFileUpload(elementMobileImage, 'mobile');
    // }
  }
});
