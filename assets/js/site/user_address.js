var elementUserAddressList = $('#user-address-list');
var elementUserAddressForm = $('#user-address-form');
var elementUserProvince = $('#user-province');
var elementUserCity = $('#user-city');

$(document).ready(function() {
  if (elementUserAddressList.length) {
    $(document).on('click', '.act-delete', function(e) {
      e.preventDefault();

      var addressId = $(this).attr('data-id');
      var element = '<input id="user-address-id" type="hidden" value="'+addressId+'">';

      showFormPopup(MSG_CONFIRMATION, element);
    });

    $(document).on('click', '#popup-form-btn', function(e) {
      e.preventDefault();

      var submit = {
        id: $('#user-address-id').val(),
        user_id: elementUserAddressList.attr('data-id')
      };

      $.post(BASE_URL+'/user/address/delete', $.extend(true, submit, TOKEN), function(response) {
        if (response.deleted) {
          window.location.reload();
        } else {
          $('#popup-form').hide();
        }
      });
    });
  }

  if (elementUserAddressForm.length) {
    elementUserProvince.change(function() {
      var province = $(this).val();
      var provinceName = province > 0 ? $('#user-province option:selected').text() : '';
      var cities = CITY_LIST[province] ? CITY_LIST[province] : [];
      var content = '<option value="">'+LABEL_SELECT_OPTION+'</option>';

      for (var i = 0; i < cities.length; i++) {
        content += '<option value="'+cities[i].city_id+'">'+cities[i].city_name+'</option>';
      }

      $('#input-province-name').val(provinceName);
      elementUserCity.html(content).selectric('refresh');
    });

    elementUserCity.change(function() {
      var city = $(this).val();
      var cityName = city > 0 ? $('#user-city option:selected').text() : '';
      
      $('#input-city-name').val(cityName);
    });
  }
});
