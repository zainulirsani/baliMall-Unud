function change() {
  let text = $(".vendor-info .product-name").text();

  let set_text_length = 25;

  let browser_width = $(window).width();

  $(".vendor-info .product-name").each(function (i, e) {
    text = $(e).text();
    if (browser_width <= 415) {
      set_text_length = 19;
    }

    if (browser_width <= 375) {
      set_text_length = 15;
    }

    if (text.length > set_text_length) {
      text = $(e).text().substr(0, set_text_length) + "...";
    }

    $(this).text(text);
  });

  // $(window).resize(function(){
  //     browser_width = $(window).width()
  //     this.handleText(text, browser_width, set_text_length)
  // })
}

function handleRangePrice(min_price, max_price) {
  let price_max_input = $("#f-max-price");
  let price_min_input = $("#f-min-price");

  let lowest_price = min_price;
  let heighest_price = max_price;

  $("#slider-range").slider({
    range: true,
    min: lowest_price,
    max: heighest_price,
    values: [
      price_min_input.val() == ""
        ? lowest_price
        : Number(price_min_input.val()),
      price_max_input.val() == ""
        ? heighest_price
        : Number(price_max_input.val()),
    ],
    slide: function (event, ui) {
      price_min_input.val(ui.values[0]).keyup();
      price_max_input.val(ui.values[1]).keyup();
      $("#amount").val("Rp. " + ui.values[0] + " - Rp. " + ui.values[1]);
    },
  });
  $("#amount").val(
    "Rp. " +
      $("#slider-range").slider("values", 0) +
      " - Rp. " +
      $("#slider-range").slider("values", 1)
  );
}

function handleSelectCity(city_data, prov_id, region) {
  let province_id = prov_id;
  let cities = city_data[province_id];

  handleCity(cities);

  $("#input-province").change(function () {
    province_id = $(this).val();
    cities = city_data[province_id];
    handleCity(cities);
  });

  //select prov
  $("#input-province").prop("selectedIndex", prov_id).selectric("refresh");

  //select city
  let city_id = 0;

  if (province_id != 0) {
    city_data[prov_id].forEach(function (v, i) {
      if (v.city_name === region) {
        city_id = i;
      }
    });
  }

  $("#input-city").prop("selectedIndex", city_id).selectric("refresh");
}

function handleCity(cities) {
  const city_label = $(".city-label").text();
  let option = [`<option value="0">${city_label}</option>`];
  if (cities != null) {
    option = cities.map(function (v) {
      return `<option value="${v.city_name}">${v.city_name}</option>`;
    });
  }

  $("#input-city").html(option).selectric("refresh");

  $("#input-city").change(function () {
    if ($(this).val() != 0) {
      $("#search-form").submit();
    }
  });
}

function handleSelectCity(city_data, prov_id, region) {
  let province_id = prov_id;
  let cities = city_data[province_id];

  handleCity(cities);

  $("#input-province").change(function () {
    province_id = $(this).val();
    cities = city_data[province_id];
    handleCity(cities);
  });

  //select prov
  $("#input-province").prop("selectedIndex", prov_id).selectric("refresh");

  //select city
  let city_id = 0;

  if (province_id != 0) {
    city_data[prov_id].forEach(function (v, i) {
      if (v.city_name === region) {
        city_id = i;
      }
    });
  }

  $("#input-city").prop("selectedIndex", city_id).selectric("refresh");
}

function handleCity(cities) {
  const city_label = $(".city-label").text();
  let option = [`<option value="0">${city_label}</option>`];
  if (cities != null) {
    option = cities.map(function (v) {
      return `<option value="${v.city_name}">${v.city_name}</option>`;
    });
  }

  $("#input-city").html(option).selectric("refresh");

  $("#input-city").change(function () {
    if ($(this).val() != 0) {
      $("#search-form").submit();
    }
  });
}

function handlePostMessage(data) {
  window.postMessage(data);
  window.ReactNativeWebView && window.ReactNativeWebView.postMessage(data);
}

function handleNotification() {
  const user_id = $("#user_id").data("user");
  if (typeof EventSource !== "undefined") {
    if (user_id) {
      let url = BASE_URL + "/notification";
      const es = new EventSource(url);

      es.onmessage = (event) => {
        if ("END-OF-STREAM" == event.data) {
          es.close(); // stop retry
        }
        const data = event.data;
        handlePostMessage(data);
      };
    }
  }
}

function run() {
  change();
}

$(document).ready(function () {
  run();

  if ($("#swiper1").length) {
    var swiper1 = new Swiper("#swiper1", {
      slidesPerView: 8,
      slidesPerColumn: 2,
      navigation: {
        nextEl: "#category-next",
        prevEl: "#category-prev",
      },
      breakpoints: {
        320: {
          slidesPerView: 2,
          slidesPerColumn: 2,
        },
        480: {
          slidesPerView: 3,
          slidesPerColumn: 2,
        },
        640: {
          slidesPerView: 4,
          slidesPerColumn: 2,
        },
        768: {
          slidesPerView: 4,
          slidesPerColumn: 2,
        },
        1024: {
          slidesPerView: 5,
          slidesPerColumn: 2,
        },
      },
    });
  }

  if ($("#swiper2").length) {
    var swiper2 = new Swiper("#swiper2", {
      slidesPerView: 3,
      spaceBetween: 30,
      pagination: {
        el: ".swiper-pagination",
        clickable: true,
      },
      navigation: {
        nextEl: "#best-seller-next",
        prevEl: "#best-seller-prev",
      },
      // Responsive breakpoints
      breakpoints: {
        520: {
          slidesPerView: 1,
        },
        540: {
          slidesPerView: 2,
        },
        768: {
          slidesPerView: 2,
        },
        1024: {
          slidesPerView: 2,
        },
      },
    });
  }

  if (GTM_TRACKING_ID !== "" && FB_PIXEL_CODE !== "") {
    $(document).on("click", ".btn-gtm-bm", function (e) {
      e.preventDefault();

      var id = $(this).attr("data-id");
      var url = $(this).attr("href");

      dataLayer.push({ ecommerce: null });
      dataLayer.push({
        event: "productClick",
        ecommerce: {
          click: {
            actionField: {},
            products: [
              {
                name: $("#gtm-pc-name-" + id).val(),
                id: $("#gtm-pc-id-" + id).val(),
                price: $("#gtm-pc-price-" + id).val(),
                brand: $("#gtm-pc-brand-" + id).val(),
                category: $("#gtm-pc-category-" + id).val(),
                variant: "",
              },
            ],
          },
        },
        eventCallback: function () {
          document.location = url;
        },
      });

      fbq("track", "ViewContent", {
        content_ids: $("#gtm-pc-id-" + id).val(),
        content_category: "Product View",
        content_name: $("#gtm-pc-name-" + id).val(),
        content_type: "product",
        contents: [
          {
            name: $("#gtm-pc-name-" + id).val(),
            id: $("#gtm-pc-id-" + id).val(),
            price: $("#gtm-pc-price-" + id).val(),
            brand: $("#gtm-pc-brand-" + id).val(),
            category: $("#gtm-pc-category-" + id).val(),
            variant: "",
          },
        ],
        currency: "IDR",
        value: $("#gtm-pc-price-" + id).val(),
      });
    });

    $(document).on("click", ".btn-gtm-bm-pcc", function (e) {
      e.preventDefault();

      var id = $(this).attr("data-id");

      dataLayer.push({ ecommerce: null });
      dataLayer.push({
        event: "productClick",
        ecommerce: {
          click: {
            actionField: { list: "Product Category" },
            products: [
              {
                name: $("#gtm-pcc-name-" + id).val(),
                id: $("#gtm-pcc-id-" + id).val(),
                price: "",
                brand: "",
                category: "",
                variant: "",
              },
            ],
          },
        },
      });

      fbq("trackCustom", "ViewProductCategory", {
        content_ids: $("#gtm-pcc-id-" + id).val(),
        content_category: "Product View",
        content_name: $("#gtm-pcc-name-" + id).val(),
        content_type: "product",
        contents: [
          {
            name: $("#gtm-pcc-name-" + id).val(),
            id: $("#gtm-pcc-id-" + id).val(),
            price: "",
            brand: "",
            category: "",
            variant: "",
          },
        ],
        currency: "IDR",
      });
    });

    $(document).on("click", ".btn-gtm-bm-atc", function () {
      dataLayer.push({ ecommerce: null });
      dataLayer.push({
        event: "addToCart",
        ecommerce: {
          currencyCode: "IDR",
          add: {
            products: [
              {
                name: GTM_PRODUCT_DATA.name || "",
                id: GTM_PRODUCT_DATA.id || "",
                price: GTM_PRODUCT_DATA.price || "",
                brand: GTM_PRODUCT_DATA.brand || "",
                category: GTM_PRODUCT_DATA.category || "",
                variant: "",
                quantity: $("#input-cart-qty").val() || "",
              },
            ],
          },
        },
      });

      const price = GTM_PRODUCT_DATA.price || "";
      const qty = $("#input-cart-qty").val() || "";
      let value = 0;

      if (price !== "" && qty !== "") {
        value = parseInt(price) * parseInt(qty);
      }

      fbq("track", "AddToCart", {
        content_ids: GTM_PRODUCT_DATA.id || "",
        content_name: GTM_PRODUCT_DATA.name || "",
        content_type: "product",
        contents: [
          {
            name: GTM_PRODUCT_DATA.name || "",
            id: GTM_PRODUCT_DATA.id || "",
            price: GTM_PRODUCT_DATA.price || "",
            brand: GTM_PRODUCT_DATA.brand || "",
            category: GTM_PRODUCT_DATA.category || "",
            variant: "",
            quantity: qty,
          },
        ],
        currency: "IDR",
        value,
      });
    });

    $(document).on("click", ".btn-gtm-bm-rfc", function () {
      var hash = $(this).attr("data-hash");

      dataLayer.push({ ecommerce: null });
      dataLayer.push({
        event: "removeFromCart",
        ecommerce: {
          remove: {
            products: [
              {
                name: $("#gtm-bm-name-" + hash).val(),
                id: $("#gtm-bm-id-" + hash).val(),
                price: $("#gtm-bm-price-" + hash).val(),
                brand: $("#gtm-bm-brand-" + hash).val(),
                category: $("#gtm-bm-category-" + hash).val(),
                variant: "",
                quantity: $("#gtm-bm-quantity-" + hash).val(),
              },
            ],
          },
        },
      });

      const price = GTM_PRODUCT_DATA.price || "";
      const qty = $("#input-cart-qty").val() || "";
      let value = 0;

      if (price !== "" && qty !== "") {
        value = parseInt(price) * parseInt(qty);
      }

      fbq("track", "RemoveFromCart", {
        content_ids: $("#gtm-bm-id-" + hash).val(),
        content_name: $("#gtm-bm-name-" + hash).val(),
        content_type: "product",
        contents: [
          {
            name: $("#gtm-bm-name-" + hash).val(),
            id: $("#gtm-bm-id-" + hash).val(),
            price: $("#gtm-bm-price-" + hash).val(),
            brand: $("#gtm-bm-brand-" + hash).val(),
            category: $("#gtm-bm-category-" + hash).val(),
            variant: "",
            quantity: $("#gtm-bm-quantity-" + hash).val(),
          },
        ],
        currency: "IDR",
        value,
      });
    });
  }

  if ($("#bela-access").length && elementHeaderSearchFormFields.html() === "") {
    elementHeaderSearchFormFields.html(
      '<input type="hidden" name="category1[' +
        $("#bela-bcp").val() +
        ']" value="' +
        $("#bela-bcv").val() +
        '">'
    );
  }

  if ($('#swiper3').length) {
    const swiper3 = new Swiper('#swiper3', {
      // Default parameters
      slidesPerView: 3,
      spaceBetween: 10,
      navigation: {
        nextEl: "#product-next",
        prevEl: "#product-prev",
      },
      breakpoints: {
        633: {
          slidesPerView: 2,
          spaceBetween: 10
        },
        300: {
          slidesPerView: 1,
          spaceBetween: 10
        }
      }
    })
  }

  if ($('#swiper4').length) {
    const swiper4 = new Swiper('#swiper4', {
      // Default parameters
      slidesPerView: 3,
      spaceBetween: 2,
      navigation: {
        nextEl: "#m-next",
        prevEl: "#m-prev",
      },
      scrollbar: {
        el: '.swiper-scrollbar',
        draggable: true,
      },
      breakpoints: {
        633: {
          slidesPerView: 2,
          spaceBetween: 10
        },
        300: {
          slidesPerView: 1,
          spaceBetween: 10
        }
      }
    })
  }

});
