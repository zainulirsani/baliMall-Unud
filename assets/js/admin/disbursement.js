var totalAfterProductFee = $('#total-after-product-fee');
var subtotal = $('#sub-total-without-tax');
var subTotalWithShip = $('#sub-total-with-shipping');
var pph = $('#input-pph');
var ppn = $('#input-ppn');
var bankFee = $('#input-bank-fee');
var managementFee = $('#input-management-fee');
var otherFee = $('#input-other-fee');
var orderShippingPrice = $('#input-order-shipping-price');
var totalDisbursement = $('#input-total-disbursement');


$(document).ready(function () {
  if ($('.disbursement-ctl').length) {
    setNominal($('#input-persentase-ppn'));
    setNominal($('#input-persentase-pph'));
    setFeePersentase();
    setTotal();
  }

  
  $(document).on('click', '.btn-upload-proof', function() {
    $("#id-modal-done").val($(this).data('id'));
    $("#role-modal-done").val($(this).data('role'));
    $("#from-click-done").val($(this).data('from'));
  });

  $(document).on('click', '.btn-edit-status', function() {
    $("#id-modal-edit-status").val($(this).data('id'));
    $("#role-modal-edit-status").val($(this).data('role'));
    $("#from-click-edit-status").val($(this).data('from'));
  });

  $("#btn-submit-disbursement").click(function(e) {
    e.preventDefault();
    var id = $(this).attr('data-id');

    bootbox.confirm(CONFIRM_MSG, function(confirmed) {
      if (confirmed) {
        $("#disbursement-form").submit();
      }
    });
  });

  $("#disbursement-form").keypress(function(e) {
    if (e.which == 13) {
      return false;
    }
  });

  $('#payment_date_image_alt').on('change', function () {
    gambarValid($(this),'pesan-error-file-big');
  });
  
  $('.disbursement-ctl').on('keyup', function () {
    setNominal(this);
  });

  $("#input-order-shipping-price").on('keyup', function() {
    $("#input-order-shipping-price").val(setToRp($(this).val().replace(/[,]/g, "")))
    setTotal();
  });

  $(".input-nominal-fee").on('keyup', function() {
    $(this).val(setToRp(isNaN(parseFloat($(this).val().replace(/[,]/g, ""))) ? '' : parseFloat($(this).val().replace(/[,]/g, ""))))
  });

  $('.input-persentase-nominal').on('keyup', function(e) {
    if (e.which == 13) {
      return false;
    } else {
      if ($(this).data('input') == 'persentase') {
        setFeePersentase();
      } else {
        setProductFee('input');
      }
    }
    
  });


  function setFeePersentase() {
    var input        = $('.input-persentase-fee');
    $.each(input, function (key,val) {
      var no         = key + 1;
      var value      = isNaN($(val).val()) ? 0 : $(val).val();
      var price      = isNaN(parseFloat($("#price_" + no).data('value'))) ? 0 : parseFloat($("#price_" + no).data('value'));
      
      var fee = (value * price) / 100;
      $("#fee_nominal_" + no).val(setToRp(fee));
      
      setProductFee('');
    });
  }
  
  function setProductFee(param) {
    var input       = $('.input-nominal-fee');
    var price_w_tax = isNaN(parseFloat($('#total_product_price').data('value'))) ? 0 : parseFloat($('#total_product_price').data('value'));
    var product_fee = 0;

    $.each(input, function (key,val) {
      var no          = key + 1;
      var value       = isNaN(parseFloat($(val).val().replace(/[,]/g, ""))) ? 0 : parseFloat($(val).val().replace(/[,]/g, ""));
      var price       = isNaN(parseFloat($("#price_" + no).data('value'))) ? 0 : parseFloat($("#price_" + no).data('value'));
      var fee_nominal = value;
      var fee         = (value / price) * 100;
      product_fee += fee_nominal;
      if (param != '') {
        $("#fee_persentase_" + no).val(fee);
      }
    });
    
    var grand_total = price_w_tax - product_fee;
    $('#input-product-fee').val(product_fee.toFixed(2));
    $('#product-fee').html(setToRp(product_fee.toFixed(2)));
    $('#grand-total').html(setToRp(grand_total.toFixed(2)));
    totalAfterProductFee.val(grand_total.toFixed(2));
    setTotal();
  }

  function setNominal(input) {
    var totalProduct = isNaN(parseFloat(subTotalWithShip.val().replace(/[,]/g, ""))) ? 0 : parseFloat(subTotalWithShip.val().replace(/[,]/g, ""));
    var jenis        = $(input).data('jenis');
    var separator_val= jenis == 'nominal' ? isNaN(parseFloat($(input).val().replace(/[,]/g, ""))) ? '' : parseFloat($(input).val().replace(/[,]/g, "")): parseFloat($(input).val());
    var value        = isNaN(separator_val) ? 0 : separator_val;
    var key          = $(input).data('idnominal');
    var val          = jenis == 'nominal' ? ((value / totalProduct) * 100).toFixed(2) : setToRp(parseFloat((totalProduct * value) / 100).toFixed(2));

    $('#input-' + key).val(val);
    if (jenis == 'nominal') {
      $(input).val(setToRp(separator_val));
    }
    setTotal();
  }

  function setToRp(number) {
    return number.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g,  "$1,");// "$1."
  }

  function gambarValid(data,id_pesan) {
    if (data[0].files[0].size>10000000) {
      $("#" + id_pesan).show();
      data.replaceWith( data.val('').clone( true ) );
    } else {
      $("#" + id_pesan).hide();
    }
  }

  function setTotal() {
    var totalProduct = isNaN(parseFloat(totalAfterProductFee.val().replace(/[,]/g, ""))) ? 0 : parseFloat(totalAfterProductFee.val().replace(/[,]/g, ""));
    var ppnValue =  isNaN(parseFloat(ppn.val().replace(/[,]/g, ""))) ? 0 : parseFloat(ppn.val().replace(/[,]/g, ""));
    var pphValue =  isNaN(parseFloat(pph.val().replace(/[,]/g, ""))) ? 0 : parseFloat(pph.val().replace(/[,]/g, ""));
    var bankFeeValue = isNaN(parseFloat(bankFee.val().replace(/[,]/g, ""))) ? 0 : parseFloat(bankFee.val().replace(/[,]/g, ""));
    var managementFeeValue = isNaN(parseFloat(managementFee.val().replace(/[,]/g, ""))) ? 0 : parseFloat(managementFee.val().replace(/[,]/g, ""));
    var otherFeeValue = isNaN(parseFloat(otherFee.val().replace(/[,]/g, ""))) ? 0 : parseFloat(otherFee.val().replace(/[,]/g, ""));
    var orderShippingPriceValue = isNaN(parseFloat(orderShippingPrice.val().replace(/[,]/g, ""))) ? 0 : parseFloat(orderShippingPrice.val().replace(/[,]/g, ""));
    

    var total = totalProduct + orderShippingPriceValue - (ppnValue + pphValue + bankFeeValue + managementFeeValue + otherFeeValue);
    

    totalDisbursement.val(setToRp(total.toFixed(2)));
  }
})
