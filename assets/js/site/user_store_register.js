var tncMerchantRead = false;
var tncPernyataanRead = false;
var tncKerjasamaRead = false;
var inputDistrictClicked = false;

$(function () {
  $(
    "#input-post-code, #no_npwp, #nama_npwp, #nik, #nomor_rekening, #total_manpower, #modal_usaha, #user-post-code, #user-phone-number"
  ).on("input", function () {
    $(this).val(
      $(this)
        .val()
        .replace(/[^0-9]/g, "")
    );
  });
});

$("#input-city").on("change", function () {
  elementLoading.show();
  getSubdistrict();
});

if (
  inputDistrictClicked === false &&
  $("#input-city").val() !== "" &&
  $("#input-district").val() === "" &&
  completeForm === 1
) {
  getSubdistrict();
  inputDistrictClicked = true;
}

function getSubdistrict() {
  const cityId = $("#input-city").val();

  $("#input-district").val("");

  $.get(BASE_URL+'/user/store/subdistrict/'+cityId)
    .done(function (data) {
      if (data.length > 0) {
        $("#kecamatan_lists").empty();

        [...data].forEach(({subdistrict_name}) => {
          $("#kecamatan_lists").append(
            $("<option>", {
              value: subdistrict_name,
              text: subdistrict_name,
            })
          );
        });
      }

      elementLoading.hide();
    })
    .fail(function (res) {
      elementLoading.hide();
      showGeneralPopup("Pilihan Kabupaten tidak valid");
    });
}

const elSelectCriteria = $("#kriteria_usaha");
const elDeskripsiCriteria = $("#deskripsi_kriteria");

const tabSyaratKetentuan = $("#tabTnc");
const tabDataUsaha = $("#tabDataUsaha");
const tabToko = $("#tabDataToko");
const tabPemilikUsaha = $("#tabPemilikUsaha");
const tabKeuangan = $("#tabKeuangan");
const tabPajak = $("#tabPerpajakan");
const tabKerjasama = $("#tabKerjasama");
const tabSuratPernyataan = $("#tabSuratPernyataan");
const tabPerjanjianKerjasama = $("#tabPerjanjianKerjasama");
const tabStatus = $("#tabStatus");

const tabContentTncMerchant = $("#tncMercant");
const tabContentToko = $("#toko");
const tabContentPemilik = $("#pemilikUsaha");
const tabContentKeuangan = $("#keuangan");
const tabContentPerpajakan = $("#perpajakan");
const tabContentKerjasama = $("#kerjasama");
const tabContentSuratPernyataan = $("#suratPernyataan");
const tabContentPerjanjianKerjasama = $("#perjanjianKerjasama");
const tabContentStatus = $("#statusMerchant");
const tabContentFinish = $("#finish");

const elementTncMerchant = $(".input-tnc-merchant");
const elementsToko = $(".input-toko");
const elementsTokoImg = $(".input-toko-img");
const elementsTokoKurir = $(".input-toko-kurir");
const elementProductCategory = $('.input-toko-product-category');
const elementsPemilikUsaha = $(".input-pemilik");
const elementsPemilikUsahaImg = $(".input-pemilik-img");
const elementsKeuangan = $(".input-keuangan");
const elementsKeuanganImg = $(".input-keuangan-img");
const elementsPajak = $(".input-pajak");
const elementPajakImg = $(".input-pajak-img");
const elementPernyataan = $(".input-surat-pernyataan");
const elementKerjasama = $(".input-perjanjian-kerjasama");

elSelectCriteria.on("change", function () {
  const selected = $("option:selected", this).attr("value");
  if (options[selected]) {
    const {description} = options[selected];
    elDeskripsiCriteria.text(`( ${description} )`);
  } else {
    elDeskripsiCriteria.text("");
  }
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

$("#modal_usaha_input").inputmask("integer", {
  radixPoint: ",",
  groupSeparator: ".",
  digits: 0,
  autoGroup: true,
  prefix: "Rp. ",
  rightAlign: false,
});

function maskCurencyToInteger() {
  var masked_value = document.getElementById("modal_usaha_input").value;
  console.log("Masked value: ", masked_value);
  var unmasked_rp =
    masked_value.split("Rp. ")[masked_value.split("Rp. ").length - 1];
  console.log("Unmask RP: ", unmasked_rp);
  var array_unmask = "";
  var unmasked_value = "";
  try {
    array_unmask = unmasked_rp.split(".");
    unmasked_value = array_unmask.join("");
  } catch (error) {
    unmasked_value = unmasked_rp;
  }
  console.log("Unmasked Value: ", unmasked_value);
  document.getElementById("modal_usaha").value = unmasked_value;
}

var checkbox = document.querySelectorAll(".input-toko-kurir");
for (var i = 0; i < checkbox.length; i++) {
  checkbox[i].addEventListener("change", function () {
    if (this.checked) {
      this.setAttribute("checked", true);
    } else {
      this.setAttribute("checked", false);
    }
  });
}

var checkboxCategories = document.querySelectorAll(".input-toko-product-category");
for (var i = 0; i < checkboxCategories.length; i++) {
  checkboxCategories[i].addEventListener("change", function () {
    if (this.checked) {
      this.setAttribute("checked", true);
    } else {
      this.setAttribute("checked", false);
    }
  });
}

var checkbox_tnc_merchant = document.querySelector("input[name=user_tnc]");
checkbox_tnc_merchant.addEventListener("change", function () {
  if (tncMerchantRead) {
    if (this.checked) {
      $(this).val("yes");
    } else {
      $(this).val("no");
    }
  } else {
    this.checked = false;
    return showGeneralPopup(
      "Mohon untuk membaca Syarat dan Ketentuan Umum Merchant"
    );
  }
});

var checkbox_kerjasama = document.querySelector(
  "input[name=tnc_perjanjian_kerjasama]"
);
checkbox_kerjasama.addEventListener("change", function () {
  if (tncKerjasamaRead) {
    if (this.checked) {
      $(this).val("yes");
    } else {
      $(this).val("no");
    }
  } else {
    this.checked = false;
    return showGeneralPopup("Mohon untuk membaca Perjanjian Kerjasama");
  }
});

var checkbox_kesepakatan = document.querySelector(
  "input[name=tnc_surat_pernyataan]"
);
checkbox_kesepakatan.addEventListener("change", function () {
  if (tncPernyataanRead) {
    if (this.checked) {
      $(this).val("yes");
    } else {
      $(this).val("no");
    }
  } else {
    this.checked = false;
    return showGeneralPopup("Mohon untuk membaca Surat Pernyataan");
  }
});

document
  .getElementById("tnc-merchant-file")
  .addEventListener("scroll", function () {
    // console.log(this.scrollHeight - this.scrollTop - this.clientHeight)
    const scrollHeight = this.scrollHeight - this.scrollTop - this.clientHeight;

    if (scrollHeight <= 90 || scrollHeight < 600) {
      tncMerchantRead = true;
    }
  });

document
  .getElementById("tnc-pernyataan-file")
  .addEventListener("scroll", function () {
    if (this.scrollHeight - this.scrollTop - this.clientHeight <= 200) {
      tncPernyataanRead = true;
    }
  });

document
  .getElementById("tnc-kesepakatan-file")
  .addEventListener("scroll", function () {
    if (this.scrollHeight - this.scrollTop - this.clientHeight <= 200) {
      tncKerjasamaRead = true;
    }
  });

function backToTab(tabsId) {
  document.getElementById(tabsId).click();
  window.scrollTo(0, 0);
}

const forms = {
  1: {
    el: tabSyaratKetentuan,
    nextTab: "toko",
    submitted: false,
  },
  2: {
    el: tabToko,
    nextTab: "pemilikUsaha",
    submitted: false,
  },
  3: {
    el: tabPemilikUsaha,
    nextTab: "keuangan",
    submitted: false,
  },
  4: {
    el: tabKeuangan,
    nextTab: "perpajakan",
    submitted: false,
  },
  5: {
    el: tabPajak,
    nextTab: "kerjasama",
    submitted: false,
  },
  6: {
    el: tabSuratPernyataan,
    nextTab: "suratPernyataan",
    submitted: false,
  },
  7: {
    el: tabPerjanjianKerjasama,
    nextTab: "perjanjianKerjasama",
    submitted: false,
  },
};

checkCompletedForm();

function checkCompletedForm() {
  if (completeForm > 0) {
    for (let i = 1; i <= completeForm; i++) {
      if (forms[i]) {
        forms[i].submitted = true;
        forms[i].el.append('<i class="fas fa-check green"></i>');
        forms[i].el.removeClass("disable-click");
        if (i == 1) {
          forms[i].el.addClass('active');
        }else if (i == completeForm  && completeForm < 7) {
          forms[i+1].el.removeAttr('disabled');
        }
      }
    }
  }

  const isDisabled =
    statusMerchant === "ACTIVE" ||
    statusMerchant === "UPDATE" ||
    statusMerchant === "PENDING";

  if (isDisabled) {
    $("#user_tnc").attr("disabled", true);
    $("#tnc_surat_pernyataan").attr("disabled", true);
    $("#tnc_perjanjian_kerjasama").attr("disabled", true);
  }
}

(function checkIfMerchantPending() {
  const isPending = statusMerchant === "PENDING";

  if (isPending) {
    showGeneralPopup(
      "Status merchant anda Pending. Mohon cek tab status untuk keterangan"
    );
    $("#tabSuratPernyataan, #tabPerjanjianKerjasama").each((index, element) => {
      if (!$(element).hasClass("disable-click")) {
        $(element).addClass("disable-click");
      }
    });

    $("#tabStatus").trigger("click");
    window.scrollTo(0, 0);
  }
})();

const prevFormValues = [];
let currentFormValues = [];

function checkIsFormEdited() {
  const elements = $(
    ".input-toko, #input-file-logo, #brand, #input-file-img-dash, .input-toko-img, #file-input-siu, #file-input-dok, #description, .input-pemilik, .input-pemilik-img, .input-keuangan, .input-keuangan-img, .input-pajak, .input-pajak-img, #tnc_surat_pernyataan, #tnc_perjanjian_kerjasama, #user_tnc, #user-phone-number, #user-email, .input-toko-product-category"
  );

  if (prevFormValues.length === 0) {
    elements.each((index, element) => {
      if ($(element).is(':checkbox')) {
        prevFormValues.push({index, value: $(element).prop('checked')})
      }else {
        prevFormValues.push({index, value: $(element).val()})
      }
    });
  } else {
    currentFormValues = [];
    elements.each((index, element) => {
      if ($(element).is(':checkbox')) {
        currentFormValues.push({index, value: $(element).prop('checked')})
      }else {
        currentFormValues.push({index, value: $(element).val()})
      }
    });
  }

  if (prevFormValues.length > 0 && currentFormValues.length > 0) {
    const res = !prevFormValues.every((item, index) =>  item.value === currentFormValues[index].value);

    if (res) {
      showConfirmPopup("Simpan perubahan ?");
    }
  }

  return false;
}

checkIsFormEdited();

function myTabs(evt, tabsName, src) {
  evt.preventDefault();

  var i, tablinks, tabcontent;
  let isSubmit = src === "submit";
  const status = $("#status");

  tabcontent = document.getElementsByClassName("tabcontent");
  tablinks = document.getElementsByClassName("tablinks");

  for (i = 0; i < tabcontent.length; i++) {
    tabcontent[i].style.display = "none";
  }

  for (i = 0; i < tablinks.length; i++) {
    $(tablinks[i]).removeClass("active");
  }

  for (i = 1; i <= 5; i++) {
    if (forms[i].nextTab === tabsName) {
      if (forms[i].submitted === true) {
        isSubmit = false;
      }
    }
  }

  if (tabsName === "tnc") {
    tabSyaratKetentuan.addClass("active");
    tabContentTncMerchant.css("display", "block");
    tabDataUsaha.removeAttr('disabled')
    tabToko.removeAttr('disabled')
  } else if (tabsName === "dataUsaha") {
    tabToko.removeAttr('disabled');
    const isChecked = $("#user_tnc").prop("checked") === true;
    if (isChecked) {
      document.getElementById("tabTnc").innerHTML =
        '<i class="fas fa-check green"></i> Syarat & Ketentuan Merchant';

      if (isSubmit || checkIsFormEdited()) {
        elementLoading.show();
        registrationForm.submit();
      } else {
        tabToko.addClass("active");
        tabToko.removeClass("disable-click");
        tabContentToko.css("display", "block");
        tabPemilikUsaha.removeAttr('disabled');
      }
    } else {
      showValidationErrors([$("#user_tnc")]);
      tabContentTncMerchant.css("display", "block");
      tabSyaratKetentuan.addClass("active");
      tabSyaratKetentuan.remove("i");
      showGeneralPopup("Mohon lengkapi form syarat & ketentuan merchant!");
    }
  } else if (tabsName === "toko") {
    tabToko.removeAttr('disabled');
    const isChecked = $("#user_tnc").prop("checked") === true;
    if (isChecked) {
      document.getElementById("tabTnc").innerHTML =
        '<i class="fas fa-check green"></i> Syarat & Ketentuan Merchant';

      if (isSubmit || checkIsFormEdited()) {
        elementLoading.show();
        registrationForm.submit();
      } else {
        tabToko.addClass("active");
        tabToko.removeClass("disable-click");
        tabContentToko.css("display", "block");
        tabPemilikUsaha.removeAttr('disabled');
      }
    } else {
      showValidationErrors([$("#user_tnc")]);
      tabContentTncMerchant.css("display", "block");
      tabSyaratKetentuan.addClass("active");
      tabSyaratKetentuan.remove("i");
      showGeneralPopup("Mohon lengkapi form syarat & ketentuan merchant!");
    }
  } else if (tabsName === "pemilikUsaha") {
    tabPemilikUsaha.removeAttr('disabled');
    const isFullFilled = [...elementsToko].every((el) => $(el).val() !== "");
    const isImgFilled = [...elementsTokoImg].every(
      (el) =>
        ($(el).val() !== "" && $(el).val() !== "[]") ||
        $(el).data("initial") === 1
    );
    const kurirChecked = [...elementsTokoKurir].some(
      (el) => $(el).prop("checked") === true
    );

    const productCategoryChecked = [...elementProductCategory].some(el => $(el).prop('checked') === true);

    if (isFullFilled && isImgFilled && kurirChecked && productCategoryChecked) {
      document.getElementById("tabDataToko").innerHTML =
        '<i class="fas fa-check green"></i> Data Toko';

      if (isSubmit || checkIsFormEdited()) {
        elementLoading.show();
        registrationForm.submit();
      } else {
        tabContentPemilik.css("display", "block");
        tabPemilikUsaha.addClass("active");
        tabPemilikUsaha.removeClass("disable-click");
        tabKeuangan.removeAttr('disabled');
        window.scrollTo(0, 0);
      }
    } else {
      showValidationErrors(
        [...elementsToko, ...elementsTokoImg, ...elementsTokoKurir, ...elementProductCategory],
        kurirChecked
      );
      tabContentToko.css("display", "block");
      tabToko.addClass("active");
      tabToko.remove("i");
      showGeneralPopup("Mohon lengkapi form data toko!");
    }
  } else if (tabsName === "keuangan") {
    tabKeuangan.removeAttr('disabled');
    const isFullFilled = [...elementsPemilikUsaha].every(
      (el) => $(el).val() !== ""
    );

    const isImgFilled = [...elementsPemilikUsahaImg].every(
      (el) => $(el).val() !== "" || $(el).data("initial") === 1
    );

    const isEmailValid = validateEmail($("#user-email").val());
    const isPhoneNumberValid = validatePhoneNumber(
      $("#user-phone-number").val()
    );

    if (isFullFilled && isImgFilled && isEmailValid && isPhoneNumberValid) {
      document.getElementById("tabPemilikUsaha").innerHTML =
        '<i class="fas fa-check green"></i> Data Pemilik Usaha';

      if (isSubmit || checkIsFormEdited()) {
        elementLoading.show();
        registrationForm.submit();
      } else {
        tabContentKeuangan.css("display", "block");
        tabKeuangan.addClass("active");
        tabKeuangan.removeClass("disable-click");
        tabPajak.removeAttr('disabled');
        window.scrollTo(0, 0);
      }
    } else {
      const errorElements = [...elementsPemilikUsaha];

      if (!isImgFilled) {
        errorElements.push(elementsPemilikUsahaImg);
      }

      if (!isEmailValid) {
        errorElements.push($("#user-email"));
      }

      if (!isPhoneNumberValid) {
        errorElements.push($("#user-phone-number"));
      }
      showValidationErrors(errorElements);

      tabContentPemilik.css("display", "block");
      tabPemilikUsaha.addClass("active");
      tabPemilikUsaha.remove("i");
      showGeneralPopup("Mohon lengkapi form data pemilik usaha!");
    }
  } else if (tabsName === "perpajakan") {
    tabPajak.removeAttr('disabled');
    const isFullFilled = [...elementsKeuangan].every(
      (el) => $(el).val() !== ""
    );

    const isImgFilled = [...elementsKeuanganImg].every(
      (el) => $(el).val() !== "" || $(el).data("initial") === 1
    );

    if (isFullFilled && isImgFilled) {
      document.getElementById("tabKeuangan").innerHTML =
        '<i class="fas fa-check green"></i> Data Keuangan';

      if (isSubmit || checkIsFormEdited()) {
        elementLoading.show();
        registrationForm.submit();
      } else {
        tabContentPerpajakan.css("display", "block");
        tabPajak.addClass("active");
        tabPajak.removeClass("disable-click");
        tabSuratPernyataan.removeAttr('disabled');
        tabKerjasama.removeAttr('disabled');
        window.scrollTo(0, 0);
      }
    } else {
      showValidationErrors([...elementsKeuangan, ...elementsKeuanganImg]);
      tabContentKeuangan.css("display", "block");
      tabKeuangan.addClass("active");
      showGeneralPopup("Mohon lengkapi form data keuangan!");
    }
  } else if (tabsName === "kerjasama") {
    tabKerjasama.removeAttr('disabled');
    tabSuratPernyataan.removeAttr('disabled');
    // const isFullFilled = elementsPajak.val() !== "";
    // const isImgFilled = () => {
    //   if (elementsPajak.val() === "0") {
    //     return true;
    //   } else {
    //     return (
    //       elementPajakImg.val() !== "[]" ||
    //       elementPajakImg.data("initial") === 1
    //     );
    //   }
    // };

    const isFullFilled = [...elementsPajak].every(
      (el) => $(el).val() !== ""
    );

    const isImgFilled = [...elementPajakImg].every(
      (el) => $(el).val() !== "" || $(el).data("initial") === 1
    );

    if (isFullFilled && isImgFilled) {
      document.getElementById("tabPerpajakan").innerHTML =
        '<i class="fas fa-check green"></i> Data Perpajakan';

      if (
        (isSubmit && isDraft) ||
        statusMerchant === "ACTIVE_UPDATE" ||
        checkIsFormEdited()
      ) {
        if (statusMerchant === "ACTIVE_UPDATE") {
          status.val("ACTIVE_UPDATE_COMPLETE");
        } else {
          status.val("COMPLETE");
        }
        elementLoading.show();
        registrationForm.submit();
      } else if (!isPending) {
        tabContentSuratPernyataan.css("display", "block");
        tabSuratPernyataan.addClass("active");
        tabSuratPernyataan.removeClass("disable-click");
        tabPerjanjianKerjasama.removeAttr('disabled');
        setElementKerjasama();
        window.scrollTo(0, 0);
      } else {
        tabContentPerpajakan.css("display", "block");
        tabPajak.addClass("active");
      }
    } else {
      showValidationErrors([elementsPajak, elementPajakImg]);
      tabContentPerpajakan.css("display", "block");
      tabPajak.addClass("active");
      showGeneralPopup("Mohon lengkapi form data perpajakan!");
    }
  } else if (tabsName === "suratPernyataan") {
    tabSuratPernyataan.removeAttr('disabled');
    // const isFullFilled = elementsPajak.val() !== "";
    // const isImgFilled = () => {
    //   if (elementsPajak.val() === "0") {
    //     return true;
    //   } else {
    //     return (
    //       elementPajakImg.val() !== "[]" ||
    //       elementPajakImg.data("initial") === 1
    //     );
    //   }
    // };

    const isFullFilled = [...elementsPajak].every(
      (el) => $(el).val() !== ""
    );

    const isImgFilled = [...elementPajakImg].every(
      (el) => $(el).val() !== "" || $(el).data("initial") === 1
    );

    if (isFullFilled && isImgFilled) {
      document.getElementById("tabPerpajakan").innerHTML =
        '<i class="fas fa-check green"></i> Data Perpajakan';

      if (
        (isSubmit && isDraft) ||
        statusMerchant === "ACTIVE_UPDATE" ||
        checkIsFormEdited()
      ) {
        if (statusMerchant === "ACTIVE_UPDATE") {
          status.val("ACTIVE_UPDATE_COMPLETE");
        } else {
          status.val("COMPLETE");
        }
        elementLoading.show();
        registrationForm.submit();
      } else if (!isPending) {
        tabContentSuratPernyataan.css("display", "block");
        tabSuratPernyataan.addClass("active");
        tabSuratPernyataan.removeClass("disable-click");
        tabPerjanjianKerjasama.removeAttr('disabled');
        setElementKerjasama();
        window.scrollTo(0, 0);
      } else {
        tabContentPerpajakan.css("display", "block");
        tabPajak.addClass("active");
      }
    } else {
      showValidationErrors([elementsPajak, elementPajakImg]);
      tabContentPerpajakan.css("display", "block");
      tabPajak.addClass("active");
      showGeneralPopup("Mohon lengkapi form data perpajakan!");
    }
  } else if (tabsName === "perjanjianKerjasama") {
    const isChecked = $("#tnc_surat_pernyataan").prop("checked") === true;

    if (isChecked) {
      document.getElementById("tabSuratPernyataan").innerHTML =
        '<i class="fas fa-check green"></i> Surat Pernyataan';
      if (isSubmit || checkIsFormEdited()) {
        elementLoading.show();
        registrationForm.submit();
      } else {
        tabContentPerjanjianKerjasama.css("display", "block");
        tabPerjanjianKerjasama.addClass("active");
        tabPerjanjianKerjasama.removeClass("disable-click");
        tabStatus.removeAttr('disabled');

        setElementKerjasama();
        window.scrollTo(0, 0);
      }
    } else {
      showValidationErrors([elementPernyataan]);
      tabContentSuratPernyataan.css("display", "block");
      tabSuratPernyataan.addClass("active");
      showGeneralPopup("Mohon lengkapi form surat pernyataan!");
    }
  } else if (tabsName === "status") {
    tabStatus.removeAttr('disabled');
    tabStatus.addClass("active");
    tabContentStatus.css("display", "block");
  } else if (tabsName === "finish") {
    const isChecked = $("#tnc_perjanjian_kerjasama").prop("checked") === true;

    if (isChecked) {
      if (statusMerchant === "VERIFIED") {
        status.val("ACTIVE");
      }

      elementLoading.show();
      registrationForm.submit();
    } else {
      tabContentPerjanjianKerjasama.css("display", "block");
      tabPerjanjianKerjasama.addClass("active");
      window.scrollTo(0, 0);
      showGeneralPopup("Mohon lengkapi form kerjasama!");
    }
  }
}

const errorMsg = '<p class="error error-validation">* tidak boleh kosong!</p>';

function showValidationErrors(elements, except) {
  let i = true;

  $(".error-validation").remove();

  elements.length > 0 &&
  elements.forEach((el) => {
    let item = $(el);
    let itemValue = item.val();

    if (item.attr("id") === "user-email") {
      item.after(
        '<p class="error error-validation">* email tidak valid!</p>'
      );
    } else if (item.attr("id") === "user-phone-number") {
      item.after(
        '<p class="error error-validation">* no telepon tidak valid! (format: 08xxxxxxxxxx / 0xxxxx)</p>'
      );
    } else if (
      itemValue === "" ||
      itemValue === " " ||
      itemValue === "[]" ||
      itemValue === 0 ||
      itemValue === "0" ||
      (item.is(":checkbox") && item.prop("checked") === false && !except)
    ) {
      if (item.is("select")) {
        item.parent().parent().append(errorMsg);
      } else if (item.is(":checkbox")) {
        i && item.parent().append(errorMsg);
        i = false;
      } else {
        item.after(errorMsg);
      }
    }
  });
}

function validateEmail(email) {
  console.log({email});
  var tester =
    /^[-!#$%&'*+\/0-9=?A-Z^_a-z`{|}~](\.?[-!#$%&'*+\/0-9=?A-Z^_a-z`{|}~])*@[a-zA-Z0-9](-*\.?[a-zA-Z0-9])*\.[a-zA-Z](-?[a-zA-Z0-9])+$/;
  if (!email) return false;

  var emailParts = email.split("@");

  if (emailParts.length !== 2) return false;

  var account = emailParts[0];
  var address = emailParts[1];

  if (account.length > 64) return false;
  else if (address.length > 255) return false;

  var domainParts = address.split(".");
  if (
    domainParts.some(function (part) {
      return part.length > 63;
    })
  )
    return false;

  if (!tester.test(email)) return false;

  return true;
}

function validatePhoneNumber(number) {
  const re = /^(^0|^08)(\d{4,5}){2}$/g;
  return re.test(number);
}

function setElementKerjasama() {
  const elMerchantName = $("#merchant_name").val();
  const elPemilik = $("#full_name").val();
  const elNikPemilik = $("#nik").val();
  const elAddressPemilik = $("#address").val();
  const elJabatan = $("#position option:selected").text();
  const elCity = $("#input-city-name").val();

  const elMerchantNameOnKerjasama = $(".namaMerchant");
  const elPemilikOnKerjasama = $(".namaPemilik");
  const elNikPemilikOnKerjasama = $(".nikPemilik");
  const elAddressMerchantOnKerjasama = $(".alamatPemilik");
  const elJabatanOnKerjasama = $(".jabatanPemilik");
  const elCityPernyataan = $("#pernyataan-city");

  elPemilikOnKerjasama.each((i, el) => $(el).text(` ${elPemilik}`));
  elNikPemilikOnKerjasama.each((i, el) => $(el).text(` ${elNikPemilik}`));
  elMerchantNameOnKerjasama.each((i, el) => $(el).html(elMerchantName));
  elAddressMerchantOnKerjasama.each((i, el) => $(el).html(elAddressPemilik));
  elJabatanOnKerjasama.each((i, el) => $(el).html(elJabatan));
  elCityPernyataan.text(elCity);
}

if (isPending) {
  document.getElementById("tabStatus").click();
} else {
  if (completeForm > 0) {
    if (completeForm === 7) {
      document.getElementById("tabTnc").click();
    } else {
      const tabId = forms[completeForm + 1].el.attr("id");
      document.getElementById(tabId).click();
    }
  } else {
    document.getElementById("tabTnc").click();
  }
}

const url = BASE_URL + "/media/file/upload";
const elementUserDirSlug = $("#user-dir-slug");
const dir = elementUserDirSlug.length
  ? "temp/users/" + elementUserDirSlug.val()
  : "temp/users";
const overwrite = false;

$(document).ready(function () {
  const elementLogoUploader = $(".dz-logo-uploader");
  const elementDashUploader = $(".dz-dash-uploader");
  const elementNpwpUploader = $(".dz-npwp-uploader");
  const elementSiuUploader = $(".dz-siu-uploader");
  const elementDokUploader = $(".dz-dok-uploader");
  const elementKtpUploader = $(".dz-ktp-uploader");
  const elementTtdUploader = $(".dz-ttd-uploader");
  const elementCapUploader = $(".dz-cap-uploader");
  const elementRekUploader = $(".dz-rek-uploader");
  const elementSppkpUploader = $(".dz-sppkp-uploader");

  const elementInputLogo = $("#input-file-logo");
  const elementInputDash = $("#input-file-img-dash");
  const elementInputNpwp = $("#input-file-npwp");
  const elementInputSiu = $("#file-input-siu");
  const elementInputDok = $("#file-input-dok");
  const elementInputKtp = $("#input-file-ktp");
  const elementInputTtd = $("#input-file-ttd");
  const elementInputCap = $("#input-file-cap");
  const elementInputRek = $("#file-input-rekening");
  const elementInputSppkp = $("#file-input-sppkp");

  const elementDeleteSppkp = $(".remove-sppkp");
  const elementInputSppkpTemp = $("#file-input-sppkp-temp");

  const elementDeleteSiu = $(".remove-siu");
  const elementInputSiuTemp = $("#file-input-siu-temp");

  const elementDeleteDok = $(".remove-dok");
  const elementInputDokTemp = $("#file-input-dok-temp");

  const elementEditSiu = $(".edit-siu");
  const elementEditDok = $(".edit-dok");
  const elementEditSppkp = $(".edit-sppkp");

  elementEditSiu.hide();
  elementEditDok.hide();
  elementEditSppkp.hide();

  elementDeleteSiu.hide();
  elementDeleteDok.hide();
  elementDeleteSppkp.hide();

  const elements = [
    {
      el: elementLogoUploader,
      src: elementInputLogo,
      clickable: ".file-input-logo",
      preview: "#previewImg-logo",
      multiple: false,
      editable: true,
      ratio: 1,
      filetype: "image/*",
    },
    {
      el: elementDashUploader,
      src: elementInputDash,
      clickable: ".file-input-dash",
      preview: "#previewImg-dash",
      multiple: false,
      editable: true,
      ratio: 4,
      filetype: "image/*",
    },
    {
      el: elementNpwpUploader,
      src: elementInputNpwp,
      clickable: ".file-input-npwp",
      preview: "#previewImg-npwp",
      multiple: false,
    },
    {
      el: elementKtpUploader,
      src: elementInputKtp,
      clickable: ".file-input-ktp",
      preview: "#previewImg-ktp",
      multiple: false,
    },
    {
      el: elementTtdUploader,
      src: elementInputTtd,
      clickable: ".file-input-ttd",
      preview: "#previewImg-ttd",
      multiple: false,
    },
    {
      el: elementCapUploader,
      src: elementInputCap,
      clickable: ".file-input-cap",
      preview: "#previewImg-cap",
      multiple: false,
    },
    {
      el: elementRekUploader,
      src: elementInputRek,
      clickable: ".file-input-rekening",
      preview: "#previewImg-rek",
      multiple: false,
    },
    {
      el: elementSppkpUploader,
      src: elementInputSppkp,
      clickable: ".file-input-sppkp",
      preview: "#previewImg-sppkp",
      multiple: true,
      tempImage: elementInputSppkpTemp,
      delButton: elementDeleteSppkp,
      editButton: ".edit-sppkp",
      editButtonOnUpdateSelector: ".edit-sppkp-on-edit",
    },
    {
      el: elementSiuUploader,
      src: elementInputSiu,
      clickable: ".file-input-siu",
      preview: "#previewImg-siu",
      multiple: true,
      tempImage: elementInputSiuTemp,
      delButton: elementDeleteSiu,
      editButton: ".edit-siu",
      editButtonOnUpdateSelector: ".edit-siu-on-edit",
    },
    {
      el: elementDokUploader,
      src: elementInputDok,
      clickable: ".file-input-dok",
      preview: "#previewImg-dok",
      multiple: true,
      tempImage: elementInputDokTemp,
      delButton: elementDeleteDok,
      editButton: ".edit-dok",
      editButtonOnUpdateSelector: ".edit-dok-on-edit",
    },
  ];

  let editButtonClicked = false;
  let editButtonDataIndex = null;
  let editButtonOnEdit = false;

  elements.forEach(
    ({
       el,
       src,
       clickable,
       preview,
       multiple,
       filetype = "image/*,application/pdf",
       tempImage,
       editable = false,
       ratio,
       editButton,
       delButton,
       editButtonOnUpdateSelector = null,
     }) => {
      el.dropzone({
        url: url,
        clickable,
        createImageThumbnails: preview,
        paramName: "file_image",
        maxFilesize: dzMaxSize,
        resizeWidth: 720,
        acceptedFiles: filetype,
        dictDefaultMessage: "",
        dictFallbackMessage: "",
        accept: function (file, done) {
          this.options.paramName = "file_image";

          if (file.type.includes("pdf")) {
            this.options.paramName = "file_file";
          }

          done();
        },
        params: (file) => {
          let type = "image";

          if (file[0].type.includes("pdf")) {
            type = "file";
          }

          return $.extend(true, {type, dir, overwrite}, TOKEN);
        },
        transformFile: function (file, done) {
          if (editable) {
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

            const image = new Image();
            image.src = URL.createObjectURL(file);
            editor.appendChild(image);

            const cropper = new Cropper(image, {
              aspectRatio: ratio,
              viewMode: 2,
              ready: function () {
                editor.style.visibility = "visible";
                elementLoading.hide();
              },
            });
          } else {
            done(file);
          }
        },
        renameFile: function (file) {
          const tmpFileName = file.name.split(".");
          const randomStr = randomString(5);
          return tmpFileName[0] + "-" + randomStr + "." + tmpFileName.pop();
        },
        previewTemplate: dzPreviewTemplate,
        init: function () {
          this.on("error", function (file, response, xhr) {
            if (xhr === undefined) {
              showGeneralPopup(response);
            } else {
              if (xhr.status === 413) {
                showGeneralPopup(MSG_MAX_SIZE);
              } else if (xhr.status === 403) {
                showGeneralPopup(MSG_CSRF);
              } else {
                showGeneralPopup(MSG_ERROR_500);
              }
            }

            elementLoading.hide();
          });

          this.on("addedfile", function (file) {
            if (!editable) {
              elementLoading.show();
            }

            $(".dz-preview").remove();
          });
        },
        success: function (file) {
          const result = JSON.parse(file.xhr.response);

          let uploadedFile;

          if (file.type.includes("pdf")) {
            uploadedFile = result.file_file[0];
          } else {
            uploadedFile = result.file_image[0];
          }

          if (typeof uploadedFile.error !== "undefined") {
            showGeneralPopup(uploadedFile.error);
          } else {
            const url = decodeURIComponent(uploadedFile.url);
            const parse = new URL(url);
            const uuid = file.upload.uuid;

            const srcPreview = !file.type.includes("pdf")
              ? BASE_URL + parse.pathname
              : BASE_URL + "/assets/img/pdf-logo.png";

            if (multiple) {
              if (src.val() === "[]") {
                $(preview)
                  .siblings("a")
                  .each((i, el) => {
                    $(el).attr("data-index", uuid).show();
                  });
                $(preview).attr("src", srcPreview);

                if ($(preview).parent("div").hasClass("hide")) {
                  $(preview).parent("div").removeClass("hide");
                }
              } else {
                if (editButtonClicked) {
                  let clickedButton = [...$(editButton)].find(
                    (v) => $(v).attr("data-index") === editButtonDataIndex
                  );

                  if (editButtonOnEdit) {
                    clickedButton = [...$(editButtonOnUpdateSelector)].find(
                      (v) => $(v).attr("data-index") === editButtonDataIndex
                    );
                  }

                  const parentClickedButton = $(clickedButton).parent();

                  $(parentClickedButton)
                    .children("a")
                    .attr("data-index", uuid)
                    .show();
                  $(parentClickedButton)
                    .children("img")
                    .attr("src", srcPreview);
                } else {
                  const newEl = $(preview).parent().clone(true);

                  if (newEl.hasClass("hide")) {
                    newEl.removeClass("hide");
                  }

                  $(newEl).children("a").attr("data-index", uuid).show();
                  $(newEl).children("img").attr("src", srcPreview);

                  el.prepend(newEl);
                }
              }
            } else {
              $(preview).attr("src", srcPreview);
            }

            if (multiple) {
              if (src.val() === "[]") {
                src.val(JSON.stringify([parse.pathname]));
                tempImage.val(JSON.stringify([{path: parse.pathname, uuid}]));
              } else {
                let dataImages = [...JSON.parse(src.val()), parse.pathname];
                let tempImages = [
                  ...JSON.parse(tempImage.val()),
                  {path: parse.pathname, uuid},
                ];

                if (editButtonClicked) {
                  const {path: deleteFile} = tempImages.find(
                    (v) => v.uuid === editButtonDataIndex
                  );
                  dataImages = dataImages.filter((v) => v !== deleteFile);
                  tempImages = tempImages.filter(
                    (v) => v.uuid !== editButtonDataIndex
                  );
                }

                console.log({dataImages});
                console.log({tempImages});

                src.val(JSON.stringify(dataImages));
                tempImage.val(JSON.stringify(tempImages));
              }
            } else {
              src.val(parse.pathname);
            }

            editButton && $(editButton).show();
            delButton && delButton.show();
          }

          editButtonClicked = false;
          editButtonDataIndex = null;
          editButtonOnEdit = false;

          elementLoading.hide();
        },
      });
    }
  );

  elementEditSiu.on("click", function () {
    editButtonClicked = true;
    editButtonDataIndex = $(this).attr("data-index");
    $(".file-input-siu").trigger("click");
  });

  elementEditDok.on("click", function () {
    editButtonClicked = true;
    editButtonDataIndex = $(this).attr("data-index");
    $(".file-input-dok").trigger("click");
  });

  elementEditSppkp.on("click", function () {
    editButtonClicked = true;
    editButtonDataIndex = $(this).attr("data-index");
    $(".file-input-sppkp").trigger("click");
  });

  elementDeleteSiu.on("click", function () {
    deleteImage($(this), elementInputSiu, elementInputSiuTemp);
  });

  elementDeleteDok.on("click", function () {
    deleteImage($(this), elementInputDok, elementInputDokTemp);
  });

  elementDeleteSppkp.on("click", function () {
    deleteImage($(this), elementInputSppkp, elementInputSppkpTemp);
  });

  const deleteImage = (
    el,
    elementInput,
    elementInputTemp,
    isOnEdit = false,
    elDeleteFiles = null
  ) => {
    elementLoading.show();

    const dataTemp = JSON.parse(elementInputTemp.val());
    const imgIdx = el.attr("data-index");
    const {path} = dataTemp.find(({uuid}) => uuid === imgIdx);
    if (path) {
      const submit = {id: 0, path, src: "photo_profile"};

      if (isOnEdit) {
        if (elDeleteFiles.val() === "") {
          elDeleteFiles.val(JSON.stringify([path]));
        } else {
          const prevItems = JSON.parse(elDeleteFiles.val());
          const newItems = [...prevItems, path];
          elDeleteFiles.val(JSON.stringify(newItems));
        }

        console.log({deletedFiles: elDeleteFiles.val()});

        const newDataTemp = dataTemp.filter(({uuid}) => uuid !== imgIdx);
        const newDataImages = newDataTemp.map(({path}) => path);

        console.log({newDataTemp, newDataImages});

        elementInput.val(JSON.stringify(newDataImages));
        elementInputTemp.val(JSON.stringify(newDataTemp));

        if (newDataImages.length === 0) {
          if (isOnEdit) {
            el.parent("div").remove();
          } else {
            el.siblings("img").attr("src", "{{ imgThumbnail }}");
            el.siblings("a").removeAttr("data-index");
            el.siblings("a").hide();
            el.removeAttr("data-index");
            el.hide();
            el.parent().addClass("hide");
          }
        } else {
          el.parent("div").remove();
        }

        elementLoading.hide();
        isOnEdit = false;
      } else {
        $.post(dzDeleteUrl, $.extend(true, submit, TOKEN), function (response) {
          if (response.deleted) {
            const newDataTemp = dataTemp.filter(({uuid}) => uuid !== imgIdx);
            const newDataImages = newDataTemp.map(({path}) => path);

            console.log({newDataTemp, newDataImages});

            elementInput.val(JSON.stringify(newDataImages));
            elementInputTemp.val(JSON.stringify(newDataTemp));

            if (newDataImages.length === 0) {
              el.siblings("img").attr("src", "{{ imgThumbnail }}");
              el.siblings("a").removeAttr("data-index");
              el.siblings("a").hide();
              el.removeAttr("data-index");
              el.hide();
              el.parent().addClass("hide");
            } else {
              el.parent("div").remove();
            }

            elementLoading.hide();
          } else {
            elementLoading.hide();
            showGeneralPopup("Terjadi kesalahan pada sistem");
          }
        });
      }
    } else {
      elementLoading.hide();
      showGeneralPopup("Terjadi kesalahan pada sistem");
    }
  };

  $(".remove-siu-on-edit").on("click", function () {
    const e = $(this);
    const tempValue = $("#file-input-siu-temp");
    const currentValue = $("#file-input-siu");

    deleteImage(e, currentValue, tempValue, true, $("#deleted-siu-files"));
  });

  $(".edit-siu-on-edit").on("click", function () {
    editButtonClicked = true;
    editButtonOnEdit = true;
    editButtonDataIndex = $(this).attr("data-index");
    $(".file-input-siu").trigger("click");
  });

  $(".remove-dok-on-edit").on("click", function () {
    const e = $(this);
    const tempValue = $("#file-input-dok-temp");
    const currentValue = $("#file-input-dok");

    deleteImage(e, currentValue, tempValue, true, $("#deleted-doc-files"));
  });

  $(".edit-dok-on-edit").on("click", function () {
    editButtonClicked = true;
    editButtonOnEdit = true;
    editButtonDataIndex = $(this).attr("data-index");
    $(".file-input-dok").trigger("click");
  });

  $(".remove-sppkp-on-edit").on("click", function () {
    const e = $(this);
    const tempValue = $("#file-input-sppkp-temp");
    const currentValue = $("#file-input-sppkp");

    deleteImage(e, currentValue, tempValue, true, $("#deleted-sppkp-files"));
  });

  $(".edit-sppkp-on-edit").on("click", function () {
    editButtonClicked = true;
    editButtonOnEdit = true;
    editButtonDataIndex = $(this).attr("data-index");
    $(".file-input-sppkp").trigger("click");
  });

  // $('.img').on('click', function () {
  //     window.open($(this).attr('href'), '_blank')
  // })
});

const elPkp = document.getElementById("pkp");

showDiv(elPkp);

function showDiv(select) {
  if (select.value === "0") {
    // document.getElementById("sppkp_div").style.display = "block";
    $("#sppkp_div").attr('style', 'display:block;')
  } else {
    $("#sppkp_div").attr('style', 'display:block;')

    // document.getElementById("sppkp_div").style.display = "block";
  }
}

const jenisUsaha = document.getElementById("jenis_usaha");

rekeningOnChange(jenisUsaha);

function rekeningOnChange(select) {
  if (select.value === "PERSEORANGAN") {
    document.getElementById("pemilikusaha").style.display = "block";
    document.getElementById("perusahaan").style.display = "none";
    document.getElementById("label-npwp").innerHTML =
      '<label for="">No NPWP Pemilik Usaha <span>*</span></label>';
  } else if (select.value === "BADAN_USAHA") {
    document.getElementById("perusahaan").style.display = "block";
    document.getElementById("pemilikusaha").style.display = "none";
    document.getElementById("label-npwp").innerHTML =
      '<label for="">No NPWP Badan Usaha <span>*</span></label>';
  } else {
    document.getElementById("pemilikusaha").style.display = "none";
    document.getElementById("perusahaan").style.display = "none";
  }
}

$("#store-province").on("change", function () {
  const val = $("option:selected", this).text();
  $("#input-province-name").val(val);
});

const addressLat = $('#lat');
const addressLng = $('#lng');
const isAddressLatLngEmpty = addressLat.val() === '' && addressLng.val() === '';
const popup = L.popup();


let mymap;
let marker;

if (isAddressLatLngEmpty) {
  mymap = L.map('addressMap').setView([-8.6725072, 115.1542332], 10);

  mymap.invalidateSize(true);

} else {
  const latLng = [addressLat.val(), addressLng.val()]

  mymap = L.map('addressMap').setView(latLng, 10);

  marker = L.marker(latLng, {icon: iconLoc}).addTo(mymap);
  popup
    .setLatLng(latLng)
    .setContent('Alamat anda')
    .openOn(mymap);

  mymap.invalidateSize(true);

}

L.tileLayer('https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}?access_token={accessToken}', {
  attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, Imagery © <a href="https://www.mapbox.com/">Mapbox</a>',
  maxZoom: 18,
  id: 'mapbox/streets-v11',
  tileSize: 512,
  zoomOffset: -1,
  accessToken: LEAFLET_ACCESS_TOKEN
}).addTo(mymap);

mymap.on('click', function (e) {
  mymap.invalidateSize(true);

  addressLat.val(e.latlng.lat)
  addressLng.val(e.latlng.lng);

  if (marker) {
    mymap.removeLayer(marker)
    marker = L.marker([e.latlng.lat, e.latlng.lng], {icon: iconLoc}).addTo(mymap)
  } else {
    marker = L.marker([e.latlng.lat, e.latlng.lng], {icon: iconLoc}).addTo(mymap);
  }

  popup
    .setLatLng(e.latlng)
    .setContent('Alamat anda')
    .openOn(mymap);

})
