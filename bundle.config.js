var lazypipe = require("lazypipe");
var babel = require("gulp-babel");

module.exports = {
  bundle: {
    jquery: {
      scripts: "./assets/js/vendor/jquery/jquery.js",
      styles: [],
      options: {
        rev: false,
        maps: false,
        minCSS: true,
        uglify: true,
      },
    },
    // FE assets start
    site: {
      scripts: [
        // Styling
        "./public/balimall/js/jquery.mCustomScrollbar.concat.min.js",
        "./public/balimall/js/jquery.mCustomScrollbar.min.js",
        "./public/balimall/js/swiper.min.js",
        "./public/balimall/js/jquery.selectric.js",
        "./public/balimall/js/select2.js",
        "./public/balimall/js/custom.js",
        "./public/balimall/js/jquery.inputmask.bundle.js",
        "./public/balimall/js/cropper.min.js",
        "./public/balimall/js/leaflet.js",

        // Custom
        "./assets/js/vendor/common/functions.js",
        "./assets/js/vendor/common/tags-input.js",
        "./assets/js/vendor/common/node-slug.js",
        "./assets/js/vendor/common/hashids.js",
        "./assets/js/vendor/common/dropzone.js",
        "./assets/js/vendor/common/datepicker.js",
        "./assets/js/site/global.js",
        "./assets/js/site/site.js",
        "./assets/js/site/cart.js",
        "./assets/js/site/user.js",
        "./assets/js/site/user_address.js",
        "./assets/js/site/user_chat.js",
        "./assets/js/site/user_order.js",
        "./assets/js/site/user_product.js",
        "./assets/js/site/user_store.js",
        "./assets/js/site/user_tax.js",
        // './assets/js/site/user_store_register.js',
      ],
      styles: [
        // Styling
        "./public/balimall/css/fontawesome-all.min.css",
        "./public/balimall/css/jquery.mCustomScrollbar.css",
        "./public/balimall/css/swiper.min.css",
        "./public/balimall/css/selectric.css",
        "./public/balimall/css/select2.css",
        "./public/balimall/css/style.css",
        "./public/balimall/css/cropper.min.css",
        "./public/balimall/css/leaflet.css",

        // Custom
        "./assets/css/site/tags-input.css",
        "./assets/css/site/datepicker.css",
        "./assets/css/site/additional.css",
      ],
      options: {
        rev: false,
        maps: false,
        minCSS: true,
        uglify: true,
        // transforms: {
        //   scripts: lazypipe().pipe(babel, {
        //       presets: ['@babel/preset-env']
        //     }
        //   )
        // }
      },
    },
    // FE assets end
    // BE assets start
    app: {
      scripts: [
        // AdminLTE
        "./assets/js/adminLTE/bootstrap.js",
        "./assets/js/adminLTE/fastclick.js",
        "./assets/js/adminLTE/jquery.slimscroll.js",
        "./assets/js/adminLTE/jquery.colorbox.js",
        "./assets/js/adminLTE/bootstrap-tagsinput.js",
        "./assets/js/adminLTE/bootstrap-colorpicker.js",
        "./assets/js/adminLTE/pace.js",
        "./assets/js/adminLTE/bootbox.js",
        "./assets/js/adminLTE/node-slug.js",
        "./assets/js/adminLTE/dropzone.js",
        "./assets/js/adminLTE/select2.js",
        // './assets/js/adminLTE/chart.js',
        "./assets/js/adminLTE/chart-2.9.0.js",
        "./assets/js/adminLTE/adminLTE.js",
        "./public/balimall/js/jquery.inputmask.bundle.js",
        "./public/balimall/js/leaflet.js",
        "./public/balimall/js/cropper.min.js",


        // Custom
        "./assets/js/admin/global.js",
        "./assets/js/admin/newsletter.js",
        "./assets/js/admin/order.js",
        "./assets/js/admin/product.js",
        "./assets/js/admin/product_category.js",
        "./assets/js/admin/setting.js",
        "./assets/js/admin/user.js",
        "./assets/js/admin/store.js",
        "./assets/js/admin/voucher.js",
        "./assets/js/admin/disbursement.js",
        "./assets/js/admin/mastertax.js",
      ],
      styles: [
        // AdminLTE
        "./assets/css/adminLTE/bootstrap.css",
        "./assets/css/adminLTE/font-awesome.css",
        "./assets/css/adminLTE/ionicons.css",
        "./assets/css/adminLTE/colorbox.css",
        "./assets/css/adminLTE/bootstrap-tagsinput.css",
        "./assets/css/adminLTE/bootstrap-colorpicker.css",
        "./assets/css/adminLTE/pace.css",
        "./assets/css/adminLTE/select2.css",
        "./assets/css/adminLTE/adminLTE.css",
        "./assets/css/adminLTE/skin-blue.css",
        "./public/balimall/css/leaflet.css",
        "./public/balimall/css/cropper.min.css",


        // Custom
        "./assets/css/admin/additional.css",
      ],
      options: {
        rev: false,
        maps: false,
        minCSS: true,
        uglify: true,
      },
    },
    login: {
      scripts: [],
      styles: "./assets/css/admin/login.css",
      options: {
        rev: false,
        maps: false,
        minCSS: true,
        uglify: true,
      },
    },
    app_dt_simple: {
      scripts: [
        "./assets/js/adminLTE/jquery.datatable.js",
        "./assets/js/adminLTE/datatable.bootstrap.js",
      ],
      styles: ["./assets/css/adminLTE/datatable.bootstrap.css"],
      options: {
        rev: false,
        maps: false,
        minCSS: true,
        uglify: true,
      },
    },
    app_dt_advance: {
      scripts: [
        // Data tables with export button
        "./assets/js/vendor/datatables/jquery.dataTables.js",
        "./assets/js/vendor/datatables/dataTables.bootstrap.js",
        "./assets/js/vendor/datatables/dataTables.buttons.js",
        "./assets/js/vendor/datatables/dataTables.colReorder.js",
        // './assets/js/vendor/datatables/dataTables.responsive.js',
        "./assets/js/vendor/datatables/dataTables.rowReorder.js.js",
        "./assets/js/vendor/datatables/buttons.colVis.js",
        "./assets/js/vendor/datatables/jszip.min.js",
        // './assets/js/vendor/datatables/pdfmake.min.js',
        "./assets/js/vendor/datatables/buttons.html5.js",
        "./assets/js/vendor/datatables/buttons.bootstrap.js",
        "./assets/js/vendor/datatables/buttons.print.js",
        "./assets/js/vendor/datatables/dataTables.scroller.js",
      ],
      styles: [
        // Data tables with export button
        "./assets/css/vendor/datatables/dataTables.bootstrap.css",
        "./assets/css/vendor/datatables/buttons.bootstrap.css",
        "./assets/css/vendor/datatables/colReorder.bootstrap.css",
        "./assets/css/vendor/datatables/rowReorder.bootstrap.css",
        "./assets/css/vendor/datatables/scroller.bootstrap.css",
      ],
      options: {
        rev: false,
        maps: false,
        minCSS: true,
        uglify: true,
      },
    },
    // BE assets end
  },
  copy: [],
};
