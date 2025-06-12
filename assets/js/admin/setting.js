$(document).ready(function() {
  if ($('#settings-data-form').length) {
    var modalSetting = $('#modal-setting');
    var settingSlug = $('#setting-slug');
    var settingFileValue = $('#setting-file-value');
    var clearSettingForm = function() {
      $('.form-group').removeClass('has-error');
      $('.help-block').html('');
      $('#select2-setting-type-container').parent('.select2-selection').css({
        'border-color': '',
        'box-shadow': ''
      });
    };

    $('#new-setting').click(function() {
      modalSetting.modal('show');
    });

    modalSetting.on('hidden.bs.modal', function() {
      clearSettingForm();
    });

    $('#setting-name').on('keyup', function() {
      settingSlug.val(slug($(this).val(), {replacement: '_', lower: true}));
    });

    settingSlug.on('keyup', function() {
      settingSlug.val(slug($(this).val(), {replacement: '_', lower: true}));
    });

    $('#setting-type').change(function() {
      var settingType = $(this).val();
      var typeText = $('#setting-value');
      var imageSource = $('#setting-image-src');

      if (settingType === 'image') {
        typeText.attr('type', 'text').hide();
        settingFileValue.show();
        imageSource.show();
      } else if (settingType === 'password') {
        typeText.attr('type', 'password');
      } else {
        typeText.attr('type', 'text').show();
        settingFileValue.hide();
        imageSource.hide();
      }
    });

    settingFileValue.dropzone({
      url: BASE_URL+'/'+ADMIN_PATH+'/file/upload',
      paramName: 'file_image',
      maxFilesize: dzMaxSize,
      resizeWidth: 1440,
      acceptedFiles: '.jpeg, .jpg, .png, .gif',
      dictDefaultMessage: '',
      dictFallbackMessage: '',
      // addRemoveLinks: true,
      // clickable: '',
      params: $.extend(true, {dir: 'settings'}, TOKEN),
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
        var result = JSON.parse(file.xhr.response);
        var image = result.file_image[0];

        if (typeof image.error !== 'undefined') {
          bootbox.alert(image.error);
        } else {
          var url = decodeURIComponent(image.url);
          var parse = new URL(url);
          var random = Math.floor(Math.random() * 20);

          $('#setting-image-src').attr('src', url+'?v'+random).show();
          $('#setting-image-value').val(parse.pathname);
        }
      }
    });

    $('#save-setting').click(function() {
      $.ajax({
        url: BASE_URL+'/'+ADMIN_PATH+'/setting/save',
        method: 'POST',
        data: $('#form-setting').serialize(),
        dataType: 'json',
        beforeSend: function() {
          clearSettingForm();
        },
        success: function (response) {
          if (response.status) {
            window.location.reload();
          }

          var errors = response.errors || {};

          Object.keys(errors).forEach(function(key) {
            var element = $('#setting-'+key);
            element.parent('.form-group').addClass('has-error');
            element.prev('span.help-block').html(errors[key]);

            if (key === 'type') {
              $('#select2-setting-type-container').parent('.select2-selection').css({
                'border-color': '#dd4b39',
                'box-shadow': 'none'
              });
            }
          });
        }
      });
    });

    $('.setting-dz-upload').each(function() {
      var id = $(this).attr('data-id');

      $(this).dropzone({
        url: BASE_URL+'/'+ADMIN_PATH+'/file/upload',
        paramName: 'file_image',
        maxFilesize: dzMaxSize,
        resizeWidth: 1440,
        acceptedFiles: '.jpeg, .jpg, .png, .gif',
        dictDefaultMessage: '',
        dictFallbackMessage: '',
        // addRemoveLinks: true,
        // clickable: '',
        params: $.extend(true, {dir: 'settings'}, TOKEN),
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

            $('.sid-'+id+'-src').attr('src', url+'?v'+random).show();
            $('#sid-'+id+'-value').val(parse.pathname);
          }
        }
      });
    });
  }
});
