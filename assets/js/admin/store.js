var elementStoreForm = $("#store-form");
var elementStoreOwnedBy = $("#input-owned-by");
var elementStoreProvince = $("#input-province");
var elementStoreCity = $("#input-city");
var elementUserName = $("#input-name");
var elementUserEmail = $("#input-email");
var elementUserPhoneNumber = $("#input-phone-number");
var elementNpwp = $("#input-npwp");
var elementInputModalUsahaShow = $("#input-modal-usaha-show");

$(document).ready(function () {
  if (elementStoreForm.length) {
    if (elementStoreOwnedBy.length) {
      elementStoreOwnedBy.select2({
        ajax: {
          url: BASE_URL + "/" + ADMIN_PATH + "/user/fetch_select",
          dataType: "json",
          delay: 250,
          minimumInputLength: 3,
          data: function (params) {
            return {
              search: params.term,
              role: "ROLE_USER_SELLER",
            };
          },
          processResults: function (data) {
            return {
              results: data.items,
            };
          },
        },
      });
    }

    if ($("#input-description").length) {
      initCKEditorBasic("input-description");
    }

    $(function () {
      $(
        "#input-post_code, #input-modal-usaha, #input-noRekening, #input-nik"
      ).on("input", function () {
        $(this).val(
          $(this)
            .val()
            .replace(/[^0-9]/g, "")
        );
      });
    });

    elementNpwp.inputmask({
      mask: "99.999.999.9-999.999",
      placeholder: " ",
      showMaskOnHover: false,
      showMaskOnFocus: false,
      onBeforePaste: function (pastedValue, opts) {
        return pastedValue;
      },
    });

    elementInputModalUsahaShow.inputmask("integer", {
      radixPoint: ",",
      groupSeparator: ".",
      digits: 0,
      autoGroup: true,
      prefix: "Rp. ",
      rightAlign: false,
    });

    elementInputModalUsahaShow.on("input", function () {
      var masked_value = $(this).val();
      var unmasked_rp =
        masked_value.split("Rp. ")[masked_value.split("Rp. ").length - 1];
      var array_unmask = "";
      var unmasked_value = "";
      try {
        array_unmask = unmasked_rp.split(".");
        unmasked_value = array_unmask.join("");
      } catch (error) {
        unmasked_value = unmasked_rp;
      }
      $("#input-modal-usaha").val(unmasked_value);
    });

    elementStoreOwnedBy.on("select2:select", function (e) {
      var data = e.params.data;
      if (data !== null) {
        elementUserName.val(data.text);
        elementUserEmail.val(data.email);
        elementUserPhoneNumber.val(data.phoneNumber);
      }
    });

    elementStoreProvince.change(function () {
      var province = $(this).val();
      var provinceName =
        province > 0 ? $("#input-province option:selected").text() : "";
      var cities = CITY_LIST[province] ? CITY_LIST[province] : [];
      var content = '<option value="">' + SELECT_OPTION_LABEL + "</option>";

      for (var i = 0; i < cities.length; i++) {
        content +=
          '<option value="' +
          cities[i].city_id +
          '">' +
          cities[i].city_name +
          "</option>";
      }

      $("#input-province-name").val(provinceName);
      elementStoreCity.html(content).trigger("change");
    });

    elementStoreCity.change(function () {
      var city = $(this).val();
      var cityName = city > 0 ? $("#input-city option:selected").text() : "";

      $("#input-city-name").val(cityName);
    });
  }

  if ($(".store-tabs").length) {
    tabStepsFunction("store");
  }
});
