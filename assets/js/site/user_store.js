var userStoreForm = $('#user-store-form');
var elementStoreProvince = $('#store-province');
var elementStoreCity = $('#store-city');

$(document).ready(function() {
  if (userStoreForm.length) {
    initCKEditorBasic('input-description');

    elementStoreProvince.change(function() {
      var province = $(this).val();
      var provinceName = province > 0 ? $('#store-province option:selected').text() : '';
      var cities = CITY_LIST[province] ? CITY_LIST[province] : [];
      var content = '<option value="">'+LABEL_SELECT_OPTION+'</option>';

      for (var i = 0; i < cities.length; i++) {
        content += '<option value="'+cities[i].city_id+'">'+cities[i].city_name+'</option>';
      }

      $('#input-province-name').val(provinceName);
      elementStoreCity.html(content).selectric('refresh');
    });

    elementStoreCity.change(function() {
      var city = $(this).val();
      var cityName = city > 0 ? $('#store-city option:selected').text() : '';

      $('#input-city-name').val(cityName);
    });
  }
});
