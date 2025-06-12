var elementDateOfBirth = $("#input-dob");
var elementUserEmail = $("#valid-email");
var elementPhotoProfileTemp = $("#photo-profile-temp");
var elementUserSignatureTemp = $("#user-signature-temp");
var elementUserStampTemp = $("#user-stamp-temp");
var elementBannerProfileTemp = $("#banner-profile-temp");
var elementNPWPFileTemp = $("#npwp-file-temp");
var elementUserSlug = $("#user-slug");
var elementOverwrite = $("#overwrite");
var elementInputProvince = $("#input-province");
var elementInputCity = $("#input-city");
var registrationForm = $("#registration-form");
var userProfileForm = $("#user-profile-form");
var dzPhotoProfileUploader = $("#dz-pp-uploader");
var dzSignatureUploader = $("#dz-signature-uploader");
var dzStampUploader = $("#dz-stamp-uploader");
var dzBannerProfileUploader = $("#dz-bp-uploader");
var dzNPWPUploader = $("#dz-npwp-uploader");

var dzPhotoProfile = function () {
  var dirSlug = elementUserSlug.length
    ? "users/" + elementUserSlug.val()
    : "temp/users";
  var overwrite = elementOverwrite.length ? "yes" : "no";

  dzPhotoProfileUploader.dropzone({
    url: dzUploadUrl,
    paramName: "file_image",
    maxFilesize: dzMaxSize,
    resizeWidth: 720,
    acceptedFiles: dzAcceptedFiles,
    dictDefaultMessage: "",
    dictFallbackMessage: "",
    // addRemoveLinks: true,
    clickable: ".user-photo-profile",
    params: $.extend(
      true,
      { type: "image", dir: dirSlug, overwrite: overwrite },
      TOKEN
    ),
    previewTemplate: dzPreviewTemplate,
    init: function () {
      this.on("error", function (file, response) {
        // $(file.previewElement).find('.dz-error-message').text(response);

        $("#dz-pp-uploader .dz-image-preview").remove();
        $("#dz-pp-uploader .fa-plus-circle").show();
        $(".pp-tools").hide();

        showGeneralPopup(response);
        elementLoading.hide();
      });

      this.on("addedfile", function (file) {
        elementLoading.show();
      });

      this.on("thumbnail", function (file, dataUrl) {
        $(".dz-image img").hide();
      });
    },
    success: function (file, response) {
      var result = JSON.parse(file.xhr.response);
      var img = result.file_image[0];

      if (typeof img.error !== "undefined") {
        $("#dz-pp-uploader .fa-plus-circle").show();
        $(".pp-tools").hide();

        showGeneralPopup(img.error);
      } else {
        var url = decodeURIComponent(img.url);
        var parse = new URL(url);
        var random = Math.floor(Math.random() * 20);

        elementPhotoProfileTemp.val(parse.pathname);
        $("#user-photo-profile-src")
          .attr("src", url + "?v" + random)
          .show();
        $("#dz-pp-uploader .fa-plus-circle").hide();
        $(".pp-tools").show();
      }

      elementLoading.hide();
    },
  });
};

var dzSignature = function () {
  var dirSlug = elementUserSlug.length
    ? "users/" + elementUserSlug.val()
    : "temp/users";
  var overwrite = elementOverwrite.length ? "yes" : "no";

  dzSignatureUploader.dropzone({
    url: dzUploadUrl,
    paramName: "file_image",
    maxFilesize: dzMaxSize,
    resizeWidth: 720,
    acceptedFiles: dzPngOnly,
    dictDefaultMessage: "",
    dictFallbackMessage: "",
    // addRemoveLinks: true,
    clickable: ".user-signature",
    params: $.extend(
      true,
      { type: "image", dir: dirSlug, overwrite: overwrite },
      TOKEN
    ),
    previewTemplate: dzPreviewTemplate,
    init: function () {
      this.on("error", function (file, response) {
        // $(file.previewElement).find('.dz-error-message').text(response);

        $("#dz-signature-uploader .dz-image-preview").remove();
        $("#dz-signature-uploader .fa-plus-circle").show();
        $(".signature-tools").hide();

        showGeneralPopup(response);
        elementLoading.hide();
      });

      this.on("addedfile", function (file) {
        elementLoading.show();
      });

      this.on("thumbnail", function (file, dataUrl) {
        $(".dz-image img").hide();
      });
    },
    success: function (file, response) {
      var result = JSON.parse(file.xhr.response);
      var img = result.file_image[0];

      if (typeof img.error !== "undefined") {
        $("#dz-signature-uploader .fa-plus-circle").show();
        $(".signature-tools").hide();

        showGeneralPopup(img.error);
      } else {
        var url = decodeURIComponent(img.url);
        var parse = new URL(url);
        var random = Math.floor(Math.random() * 20);

        elementUserSignatureTemp.val(parse.pathname);
        $("#user-signature-src")
          .attr("src", url + "?v" + random)
          .show();
        $("#dz-signature-uploader .fa-plus-circle").hide();
        $(".signature-tools").show();
      }

      elementLoading.hide();
    },
  });
};

var dzStamp = function () {
  var dirSlug = elementUserSlug.length
    ? "users/" + elementUserSlug.val()
    : "temp/users";
  var overwrite = elementOverwrite.length ? "yes" : "no";

  dzStampUploader.dropzone({
    url: dzUploadUrl,
    paramName: "file_image",
    maxFilesize: dzMaxSize,
    resizeWidth: 720,
    acceptedFiles: dzPngOnly,
    dictDefaultMessage: "",
    dictFallbackMessage: "",
    // addRemoveLinks: true,
    clickable: ".user-stamp",
    params: $.extend(
      true,
      { type: "image", dir: dirSlug, overwrite: overwrite },
      TOKEN
    ),
    previewTemplate: dzPreviewTemplate,
    init: function () {
      this.on("error", function (file, response) {
        // $(file.previewElement).find('.dz-error-message').text(response);

        $("#dz-stamp-uploader .dz-image-preview").remove();
        $("#dz-stamp-uploader .fa-plus-circle").show();
        $(".stamp-tools").hide();

        showGeneralPopup(response);
        elementLoading.hide();
      });

      this.on("addedfile", function (file) {
        elementLoading.show();
      });

      this.on("thumbnail", function (file, dataUrl) {
        $(".dz-image img").hide();
      });
    },
    success: function (file, response) {
      var result = JSON.parse(file.xhr.response);
      var img = result.file_image[0];

      if (typeof img.error !== "undefined") {
        $("#dz-stamp-uploader .fa-plus-circle").show();
        $(".stamp-tools").hide();

        showGeneralPopup(img.error);
      } else {
        var url = decodeURIComponent(img.url);
        var parse = new URL(url);
        var random = Math.floor(Math.random() * 20);

        elementUserStampTemp.val(parse.pathname);
        $("#user-stamp-src")
          .attr("src", url + "?v" + random)
          .show();
        $("#dz-stamp-uploader .fa-plus-circle").hide();
        $(".stamp-tools").show();
      }

      elementLoading.hide();
    },
  });
};

var dzBannerProfile = function () {
  var dirSlug = elementUserSlug.length
    ? "users/" + elementUserSlug.val()
    : "temp/users";
  var overwrite = elementOverwrite.length ? "yes" : "no";

  dzBannerProfileUploader.dropzone({
    url: dzUploadUrl,
    paramName: "file_image",
    maxFilesize: dzMaxSize,
    resizeWidth: 1440,
    acceptedFiles: dzAcceptedFiles,
    dictDefaultMessage: "",
    dictFallbackMessage: "",
    // addRemoveLinks: true,
    clickable: ".user-banner-profile",
    params: $.extend(
      true,
      { type: "image", dir: dirSlug, overwrite: overwrite },
      TOKEN
    ),
    previewTemplate: dzPreviewTemplate,
    init: function () {
      this.on("error", function (file, response) {
        $("#dz-br-uploader .dz-image-preview").remove();
        $("#dz-br-uploader .fa-plus-circle").show();
        $(".bp-tools").hide();

        elementLoading.hide();
        showGeneralPopup(response);
      });

      this.on("addedfile", function (file) {
        elementLoading.show();
      });

      this.on("thumbnail", function (file, dataUrl) {
        $(".dz-image img").hide();
      });
    },
    success: function (file, response) {
      var result = JSON.parse(file.xhr.response);
      var img = result.file_image[0];

      if (typeof img.error !== "undefined") {
        $("#dz-br-uploader .fa-plus-circle").show();
        $(".bp-tools").hide();

        showGeneralPopup(img.error);
      } else {
        var url = decodeURIComponent(img.url);
        var parse = new URL(url);
        var random = Math.floor(Math.random() * 20);

        elementBannerProfileTemp.val(parse.pathname);
        $("#user-banner-profile-src")
          .attr("src", url + "?v" + random)
          .show();
        $("#dz-br-uploader .fa-plus-circle").hide();
        $(".bp-tools").show();
      }

      elementLoading.hide();
    },
  });
};

var dzNPWP = function () {
  var dirSlug = elementUserSlug.length
    ? "users/" + elementUserSlug.val()
    : "temp/users";
  var overwrite = elementOverwrite.length ? "yes" : "no";

  dzNPWPUploader.dropzone({
    url: dzUploadUrl,
    paramName: "file_image",
    maxFilesize: dzMaxSize,
    resizeWidth: 720,
    acceptedFiles: dzAcceptedFiles,
    dictDefaultMessage: "",
    dictFallbackMessage: "",
    // addRemoveLinks: true,
    clickable: ".upload-npwp-file",
    params: $.extend(
      true,
      { type: "image", dir: dirSlug, overwrite: overwrite },
      TOKEN
    ),
    previewTemplate: dzPreviewTemplate,
    init: function () {
      this.on("error", function (file, response) {
        // $(file.previewElement).find('.dz-error-message').text(response);

        $("#dz-pp-uploader .dz-image-preview").remove();
        $("#dz-pp-uploader .fa-plus-circle").show();

        showGeneralPopup(response);
        elementLoading.hide();
      });

      this.on("addedfile", function (file) {
        elementLoading.show();
      });

      this.on("thumbnail", function (file, dataUrl) {
        $(".dz-image img").hide();
      });
    },
    success: function (file, response) {
      var result = JSON.parse(file.xhr.response);
      var img = result.file_image[0];

      if (typeof img.error !== "undefined") {
        $("#dz-pp-uploader .fa-plus-circle").show();

        showGeneralPopup(img.error);
      } else {
        var url = decodeURIComponent(img.url);
        var parse = new URL(url);
        var random = Math.floor(Math.random() * 20);

        elementNPWPFileTemp.val(parse.pathname);
        $("#img-npwp-file-src")
          .attr("src", url + "?v" + random)
          .show();
        $("#dz-pp-uploader .fa-plus-circle").hide();
      }

      elementLoading.hide();
    },
  });
};

$(document).ready(function () {
  $(document).on("click", ".delete-stamp", function (e) {
    e.preventDefault();

    var submit = {
      id: $(this).attr("data-id") || 0,
      path: elementUserStampTemp.val(),
      src: "photo_profile",
    };

    $.post(dzDeleteUrl, $.extend(true, submit, TOKEN), function (response) {
      if (response.deleted) {
        elementUserStampTemp.val("");
        $("#user-photo-profile-src")
          .attr("src", BASE_URL + "/dist/img/user.jpg")
          .hide();
        $("#dz-stamp-uploader .dz-image-preview").remove();
        $("#dz-stamp-uploader .fa-plus-circle").show();
        $(".stamp-tools").hide();
      }
    });
  });

  $(document).on("click", ".delete-signature", function (e) {
    e.preventDefault();

    var submit = {
      id: $(this).attr("data-id") || 0,
      path: elementUserSignatureTemp.val(),
      src: "photo_profile",
    };

    $.post(dzDeleteUrl, $.extend(true, submit, TOKEN), function (response) {
      if (response.deleted) {
        elementUserSignatureTemp.val("");
        $("#user-photo-profile-src")
          .attr("src", BASE_URL + "/dist/img/user.jpg")
          .hide();
        $("#dz-signature-uploader .dz-image-preview").remove();
        $("#dz-signature-uploader .fa-plus-circle").show();
        $(".signature-tools").hide();
      }
    });
  });

  $(document).on("click", ".delete-pp", function (e) {
    e.preventDefault();

    var submit = {
      id: $(this).attr("data-id") || 0,
      path: elementPhotoProfileTemp.val(),
      src: "photo_profile",
    };

    $.post(dzDeleteUrl, $.extend(true, submit, TOKEN), function (response) {
      if (response.deleted) {
        elementPhotoProfileTemp.val("");
        $("#user-photo-profile-src")
          .attr("src", BASE_URL + "/dist/img/user.jpg")
          .hide();
        $("#dz-pp-uploader .dz-image-preview").remove();
        $("#dz-pp-uploader .fa-plus-circle").show();
        $(".pp-tools").hide();
      }
    });
  });

  $(document).on("click", ".delete-bp", function (e) {
    e.preventDefault();

    var submit = {
      id: 1,
      path: elementBannerProfileTemp.val(),
      src: "banner_profile",
    };

    $.post(dzDeleteUrl, $.extend(true, submit, TOKEN), function (response) {
      if (response.deleted) {
        elementBannerProfileTemp.val("");
        $("#user-banner-profile-src")
          .attr("src", BASE_URL + "/dist/img/bg.jpg")
          .hide();
        $("#dz-bp-uploader .dz-image-preview").remove();
        $("#dz-bp-uploader .fa-plus-circle").show();
        $(".bp-tools").hide();
      }
    });
  });

  if (dzPhotoProfileUploader.length) {
    dzPhotoProfile();
  }

  if (dzSignatureUploader.length) {
    dzSignature();
  }

  if (dzStampUploader.length) {
    dzStamp();
  }

  if (dzBannerProfileUploader.length) {
    dzBannerProfile();
  }

  $(document).on("click", "#validate-reg-input", function (e) {
    e.preventDefault();

    if (isFormCompleted()) {
      showConfirmPopup($(this).data("message"));
    }
  });

  function isFormCompleted() {
    var fields = $(
      "#input-full-name, #valid-email, #input-password, #input-confirm-password, #input-phone"
    );
    var isComplete = true;
    var msg = "<p class='error error-req'>"+ requiredMsg +"</p>";

    $(".error-req").remove();

    fields.each(function (index, element) {
      if ($(element).val() === "") {
        $(element).parent().append(msg);
        isComplete = false;
      }
    });

    var isEmailValid = validateEmail($("#valid-email").val());
    var isPhoneValid = validatePhoneNumber($("#input-phone").val());
    var isPasswordValid =
      passwordChecker($("#input-password").val()) &&
      $("#input-password").val() === $("#input-confirm-password").val();

    if (!isPhoneValid && $("#input-phone").val() !== "") {
      $("#input-phone")
        .parent()
        .append(
          "<p class='error error-req'>"+ phoneFormat +"</p>"
        );
    }

    if (!isPasswordValid && $('#input-password').val() !== "") {
      $('#input-password').parent().append("<p class='error error-req'>"+ passwordFormat +"</p>")
    }

    return isComplete && isEmailValid && isPhoneValid && isPasswordValid;
  }

  function validateEmail(email) {
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

  $(
    "#input-full-name, #valid-email, #input-password, #input-confirm-password, #input-phone"
  ).on("input", function () {
    $(".error-req").remove();
  });

  $(document).on("click", "#popup-confirm-btn", function (e) {
    e.preventDefault();

    registrationForm.submit();
  });

  $(document).on("keyup blur", "#input-password", function () {
    var password = $(this).val();
    var passwordError = $("#input-password-error");
    var passwordWeak = $("#input-password-weak");
    var confirm = $("#input-confirm-password").val();
    var strength = passwordChecker(password);

    passwordError.hide();
    passwordWeak.hide();

    if (password !== "" && !strength) {
      passwordWeak.show();
    }

    delay(function () {
      if (confirm !== "" && confirm !== password) {
        passwordError.show();
      }
    }, 1000);
  });

  $(document).on("keyup", "#input-confirm-password", function () {
    var password = $("#input-password").val();
    var passwordError = $("#input-password-error");
    var confirm = $(this).val();

    passwordError.hide();

    delay(function () {
      if (password !== "" && password !== confirm) {
        passwordError.show();
      }
    }, 1000);
  });

  if (elementUserEmail.length) {
    elementUserEmail.focusout(function () {
      var element = $("#valid-email-error");
      var submit = {
        email: $(this).val(),
        user_id: $(this).data("id"),
      };

      if (submit.email !== "") {
        $.post(
          BASE_URL + "/email-check",
          $.extend(true, submit, TOKEN),
          function (result) {
            element.html("").hide();

            if (result.status === false) {
              element.html(result.message).show();
            }
          }
        );
      }
    });
  }

  if (elementDateOfBirth.length) {
    var ageLimit = new Date();
    ageLimit.setFullYear(ageLimit.getFullYear() - 17);

    elementDateOfBirth.flatpickr(
      $.extend(flatpickrConfig, { maxDate: ageLimit })
    );
  }

  $(document).on("click", ".user-review-rating", function () {
    var rating = $(this).data("rating");

    for (var i = 1; i <= 5; i++) {
      $(".rt-" + i).html('<i class="far fa-star"></i>');
    }

    for (var j = 1; j <= rating; j++) {
      $(".rt-" + j).html('<i class="fas fa-star"></i>');
    }

    $("#user-review-rating").val(rating);
  });

  if (userProfileForm.length) {
    $(document).on("click", ".remove-pp", function (e) {
      e.preventDefault();

      var submit = {
        id: 999,
        path: elementPhotoProfileTemp.val(),
      };

      $.post(dzDeleteUrl, $.extend(true, submit, TOKEN), function (response) {
        if (response.deleted) {
          $("#user-photo-profile-src").attr(
            "src",
            BASE_URL + "/dist/img/no-image.png"
          );
          $("#dz-pp-uploader .dz-image-preview").remove();
          elementPhotoProfileTemp.val("");
        }
      });
    });

    $(document).on("click", ".remove-stamp", function (e) {
      e.preventDefault();
  
      var submit = {
        id: $(this).attr("data-id") || 0,
        path: elementUserStampTemp.val(),
        src: "user_stamp",
      };
  
      $.post(dzDeleteUrl, $.extend(true, submit, TOKEN), function (response) {
        if (response.deleted) {
          elementUserStampTemp.val("");
          $("#user-stamp-src")
            .attr("src", BASE_URL + "/dist/img/user.jpg")
            .hide();
          $("#dz-stamp-uploader .dz-image-preview").remove();
          $("#dz-stamp-uploader .fa-plus-circle").show();
          $(".stamp-tools").hide();
        }
      });
    });
  
    $(document).on("click", ".remove-signature", function (e) {
      e.preventDefault();
  
      var submit = {
        id: $(this).attr("data-id") || 0,
        path: elementUserSignatureTemp.val(),
        src: "user_signature",
      };
  
      $.post(dzDeleteUrl, $.extend(true, submit, TOKEN), function (response) {
        if (response.deleted) {
          elementUserSignatureTemp.val("");
          $("#user-signature-src")
            .attr("src", BASE_URL + "/dist/img/user.jpg")
            .hide();
          $("#dz-signature-uploader .dz-image-preview").remove();
          $("#dz-signature-uploader .fa-plus-circle").show();
          $(".signature-tools").hide();
        }
      });
    });

    $(document).on("click", ".remove-bp", function (e) {
      e.preventDefault();

      var submit = {
        id: 999,
        path: elementBannerProfileTemp.val(),
      };

      $.post(dzDeleteUrl, $.extend(true, submit, TOKEN), function (response) {
        if (response.deleted) {
          $("#user-banner-profile-src").attr(
            "src",
            BASE_URL + "/dist/img/bg.jpg"
          );
          $("#dz-bp-uploader .dz-image-preview").remove();
          elementBannerProfileTemp.val("");
        }
      });
    });
  }

  if (registrationForm.length) {
    elementInputProvince.change(function () {
      var province = $(this).val();
      var provinceName =
        province > 0 ? $("#input-province option:selected").text() : "";
      var cities = CITY_LIST[province] ? CITY_LIST[province] : [];
      var content = '<option value="">' + LABEL_SELECT_OPTION + "</option>";

      for (var i = 0; i < cities.length; i++) {
        content +=
          '<option value="' +
          cities[i].city_id +
          '">' +
          cities[i].city_name +
          "</option>";
      }

      $("#input-province-name").val(provinceName);
      elementInputCity.html(content).selectric("refresh");
    });

    elementInputCity.change(function () {
      var city = $(this).val();
      var cityName = city > 0 ? $("#input-city option:selected").text() : "";

      $("#input-city-name").val(cityName);
    });

    if (dzNPWPUploader.length) {
      dzNPWP();
    }
  }

  $("#input-phone,#input-pic-telp").on("input", function () {
    $(this).val(
      $(this)
        .val()
        .replace(/[^0-9]/g, "")
    );
  });

  $("#input-nip").on("input", function () {
    $(this).val(
      $(this)
        .val()
        .replace(/[^0-9]/g, "")
    );
  });

});
