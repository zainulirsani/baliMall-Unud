var elementUserForm = $('#user-form');
var elementPhotoProfile = $('#input-photo-profile');
var elementDateOfBirth = $('#input-dob');
var elementModalAddress = $('#modal-address');
var elementUserProvince = $('#address-province');
var elementUserCity = $('#address-city');
var elementInputRole = $('#input-role');
var elementInputSubRole = $('#input-sub_role');

$(document).ready(function() {
  $('#input-pic').on('change', function (e) {
      if (e.target.value != "") {
          var option = $('#input-pic option[value="'+e.target.value+'"]');
          $("#input-pic_name").val(option.data('name'));
          $("#input-pic-telp").val(option.data('telp'));
          $("#input-pic_email").val(option.data('email'));
          $("#input-pic_unit").val(option.data('unit'));
          $("#input-pic_address").val(option.data('address'));
      } else {
          $("#input-pic_name").val('');
          $("#input-pic-telp").val('');
          $("#input-pic_email").val('');
          $("#input-pic_unit").val('');
          $("#input-pic_address").val('');
      }
  })

  $('#input-ppk').on('change', function (e) {
    if (e.target.value != "") {
        var option = $('#input-ppk option[value="'+e.target.value+'"]');
        $("#input-ppk_name").val(option.data('name'));
        $("#input-ppk_nip").val(option.data('nip'));
        $("#input-ppk_email").val(option.data('email'));
        $("#input-ppk_telp").val(option.data('telp'));
        $('#input-ppk_type_account').val(option.data('type')).trigger('change');
    } else {
        $("#input-ppk_name").val('');
        $("#input-ppk_nip").val('');
        $("#input-ppk_email").val('');
        $("#input-ppk_telp").val('');
        $('#input-ppk_type_account').val('').trigger('change');
    }
  })

  $('#input-treasurer').on('change', function (e) {
    if (e.target.value != "") {
        var option = $('#input-treasurer option[value="'+e.target.value+'"]');
        $("#input-treasurer_name").val(option.data('name'));
        $("#input-treasurer_nip").val(option.data('nip'));
        $("#input-treasurer_email").val(option.data('email'));
        $("#input-treasurer_telp").val(option.data('telp'));
        $('#input-treasurer_type_account').val(option.data('type')).trigger('change');
    } else {
        $("#input-treasurer_name").val('');
        $("#input-treasurer_nip").val('');
        $("#input-treasurer_email").val('');
        $("#input-treasurer_telp").val('');
        $('#input-treasurer_type_account').val('').trigger('change');
    }
  })

  if (elementUserForm.length) {
    if (elementDateOfBirth.length) {
      var today = new Date();
      var min = '1900-01-01';
      var max = today.getFullYear()+'-'+(today.getMonth()+1)+'-'+today.getDate();
      var config = $.extend(flatpickrConfig, {maxDate: max, minDate: min});

      elementDateOfBirth.flatpickr(config);

      if (elementModalAddress.length) {
        var clearAddressFormError = function() {
          var select2RemoveStyle = {
            'border-color': '',
            'box-shadow': ''
          };

          $('.form-group').removeClass('has-error');
          $('.help-block').html('');
          $('#select2-input-country-container').parent('.select2-selection').css(select2RemoveStyle);
          $('#select2-input-province-container').parent('.select2-selection').css(select2RemoveStyle);
        };

        var clearAddressFormInput = function() {
          $('#address-title').val('');
          $('#address-address').val('');
          // $('#address-country').val('').trigger('change');
          $('#address-province').val('').trigger('change');
          $('#address-province-name').val('');
          $('#address-city').val('').trigger('change');
          $('#address-city-name').val('');
          $('#address-district').val('');
          $('#address-post_code').val('');
          $('#address-id').val(0);
          $('#address-lat').val('');
          $('#address-lng').val('');

          if(marker) {
            mymap.removeLayer(marker);
            mymap.removeLayer(popup);

            marker = null;
          }
        };

        $('#add-address').click(function() {
          elementModalAddress.modal('show');
        });

        elementModalAddress.on('hidden.bs.modal', function() {
          clearAddressFormError();
          clearAddressFormInput();
        });

        $(document).on('click', '.btn-edit-address', function() {
          var postData = {
            address_id: parseInt($(this).attr('data-id')),
            user_id: parseInt($('#input-id').val())
          };

          $.ajax({
            url: BASE_URL+'/'+ADMIN_PATH+'/user/address/edit',
            method: 'POST',
            data: $.extend(true, postData, TOKEN),
            dataType: 'json',
            beforeSend: function() {
              clearAddressFormError();
            },
            success: function (response) {
              if (response.status) {
                var data = response.address;

                $('#address-title').val(data.title);
                $('#address-address').val(data.address);
                // $('#address-country').val('').trigger('change');
                $('#address-province').val(data.province_id).trigger('change');
                $('#address-province-name').val(data.province);
                $('#address-city').val(data.city_id).trigger('change');
                $('#address-city-name').val(data.city);
                $('#address-district').val(data.district);
                $('#address-post_code').val(data.post_code);
                $('#address-id').val(data.id);

                if (data.address_lat !== null && data.address_lng !== null) {

                  $('#address-lat').val(data.address_lat)
                  $('#address-lng').val(data.address_lng)

                  marker = L.marker([data.address_lat, data.address_lng], {icon: iconLoc}).addTo(mymap)
                }

                elementModalAddress.modal('show');
              }
            }
          });
        });

        $(document).on('click', '.btn-delete-address', function() {
          var addressId = parseInt($(this).attr('data-id'));

          bootbox.confirm(MSG_CONFIRMATION, function(confirmed) {
            if (confirmed) {
              var postData = {
                address_id: addressId,
                user_id: parseInt($('#input-id').val())
              };

              $.post(BASE_URL+'/'+ADMIN_PATH+'/user/address/delete', $.extend(true, postData, TOKEN), function(response) {
                if (response.deleted) {
                  $('#address-detail-'+response.address_id).remove();
                }

                $('#modal-global .modal-body').html(response.message);
                $('#modal-global').modal();
              });
            }
          });
        });

        $('#submit-address').click(function() {
          var userId = parseInt($('#input-id').val());
          var addressId = parseInt($('#address-id').val());
          var uri = addressId > 0 ? 'update' : 'save';
          var data = $('#form-address').serialize();

          data += '&user_id='+userId;

          $.ajax({
            url: BASE_URL+'/'+ADMIN_PATH+'/user/address/'+uri,
            method: 'POST',
            data: data,
            dataType: 'json',
            beforeSend: function() {
              clearAddressFormError();
            },
            success: function (response) {
              if (response.status) {
                if (response.type === 'save') {
                  $('#content-address').append(response.content);
                } else if (response.type === 'update') {
                  $('#address-detail-'+response.address_id).html(response.content);
                }

                elementModalAddress.modal('hide');
                clearAddressFormInput();

                $('#modal-global .modal-body').html(response.message);
                $('#modal-global').modal();
              } else {
                var errors = response.errors || {};

                Object.keys(errors).forEach(function(key) {
                  var element = $('#address-'+key);
                  var select2AddStyle = {
                    'border-color': '#dd4b39',
                    'box-shadow': 'none'
                  };

                  element.parent('.form-group').addClass('has-error');
                  element.prev('span.help-block').html(errors[key]);

                  if (key === 'country') {
                    $('#select2-input-country-container').parent('.select2-selection').css(select2AddStyle);
                  } else if (key === 'province') {
                    $('#select2-input-province-container').parent('.select2-selection').css(select2AddStyle);
                  }
                });
              }
            }
          });
        });

        elementUserProvince.change(function() {
          var province = $(this).val();
          var provinceName = province > 0 ? $('#address-province option:selected').text() : '';
          var cities = CITY_LIST[province] ? CITY_LIST[province] : [];
          var content = '<option value="">'+SELECT_OPTION_LABEL+'</option>';

          for (var i = 0; i < cities.length; i++) {
            content += '<option value="'+cities[i].city_id+'">'+cities[i].city_name+'</option>';
          }

          $('#address-province-name').val(provinceName);
          elementUserCity.html(content).trigger('change');
        });

        elementUserCity.change(function() {
          var city = $(this).val();
          var cityName = city > 0 ? $('#address-city option:selected').text() : '';

          $('#address-city-name').val(cityName);
        });
      }
    }

    if (elementPhotoProfile.length) {
      var userId = parseInt($('#input-id').val());
      var dirSlug = $('#input-dir-slug').val();
      var dir = userId > 0 ? 'users/'+dirSlug : 'temp/users';

      elementPhotoProfile.dropzone({
        url: BASE_URL+'/'+ADMIN_PATH+'/file/upload',
        paramName: 'file_image',
        maxFilesize: dzMaxSize,
        resizeWidth: 1440,
        acceptedFiles: '.jpeg, .jpg, .png, .gif',
        dictDefaultMessage: '',
        dictFallbackMessage: '',
        // addRemoveLinks: true,
        clickable: '.profile-user-img',
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

            $('.profile-user-img').attr('src', url+'?v'+random).show();
            $('#photo-profile-tmp').val(parse.pathname);
          }
        }
      });
    }

    if ($('#input-description').length) {
      initCKEditorBasic('input-description');
    }

    $(document).on('click', '#activation-act', function(e) {
      e.preventDefault();

      var postData = {id: $(this).data('id')};

      $.post(BASE_URL+'/'+ADMIN_PATH+'/user/send_activation_mail', $.extend(true, postData, TOKEN), function(response) {
        if (response.status) {
          $('#activation-act').remove();
        }

        $('#modal-global .modal-body').html(response.message);
        $('#modal-global').modal();
      });
    });
  }

  if ($('.user-tabs').length) {
    tabStepsFunction('user');
  }

  if ($('#addressMap').length) {
    mymap.on('click', function (e) {
      var addressLat = $('#address-lat');
      var addressLng = $('#address-lng');

      addressLat.val(e.latlng.lat)
      addressLng.val(e.latlng.lng);

      if (marker) {
        mymap.removeLayer(marker)
        marker = L.marker([e.latlng.lat, e.latlng.lng], {icon: iconLoc}).addTo(mymap)
      }else {
        marker = L.marker([e.latlng.lat, e.latlng.lng], {icon: iconLoc}).addTo(mymap);
      }

      popup
        .setLatLng(e.latlng)
        .setContent('Alamat anda')
        .openOn(mymap);

    })
  }

  // if (elementInputSubRole.length) {
    elementInputSubRole.on('change', function(e) {
      if (e.target.value == 'PPK') {
        $('.sub_role_type-input').show();
        $('.sub_role_type-input').removeClass('hide');
        $('.sub_role_type-input').removeAttr('style');
      } else {
        $('.sub_role_type-input').hide();
      }
    });
  // }

  if (elementInputRole.length) {
    toggle()

    elementInputRole.on('change', toggle)

    elementInputRole.on('change', function(e) {
      if (e.target.value == 'ROLE_USER_GOVERNMENT') {
        $('.sub_role-input').show();
        $('.sub_role-input').removeClass('hide');
        $('.sub_role-input').removeAttr('style');
      } else {
        $('.sub_role-input').hide();
      }
    });

    function toggle() {
      if (elementInputRole.val() === 'ROLE_ADMIN_MERCHANT_CABANG') {
        $('.admin-merchant-input').show()
      }else {
        $('.admin-merchant-input').hide()
      }
    }
  }
});
