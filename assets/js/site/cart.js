var elementBuyWrapper = $('.buy-wrapper');
var elementInputCartQty = $('#input-cart-qty');
var elementCartForm = $('#cart-form');
var elementCheckoutForm = $('#checkout-form');
var elementGrandTotal = $('#input-grand-total');
var elementTaxInvoiceBtn = $('#tax-invoice-btn');
var elementSelfPickUp = $('.self-pick-up');
var elementInputVoucher = $('#input-voucher');
var elementApplyVoucher = $('#apply-voucher');
var cartWithTax = 'without';
var cartFreeDelivery = 'no';
var checkoutVersion = elementCheckoutForm.attr('data-version') || 'v1';
var PKPHasBeenClicked = false;
var PKPWithPPNEachShipping = [];
var PKPPriceShipping = 0;
var PKPShipping = document.getElementsByClassName('pkp-shipping');
var PKPShippingCost = document.getElementsByClassName('pkp-shipping-cost');
var governmentCheckout = document.getElementById('government-checkout') ? document.getElementById('government-checkout') : undefined;
var PKPShippingCostNew = 0;
var taxCheckByHuman = false;
var elementNegoShipping = document.getElementsByClassName('negotiation-shipping negotiated-shipping-price');
var elementNegoShippingShow = document.getElementsByClassName('negotiation-shipping shipping-price-show');
var totalPPN = $('#ppn-grand-total');
var ppnPercentage = parseFloat(totalPPN.attr('data-ppn-percentage'));
var b2gNonPkp = false;
var totalForPkpStore = 0;
var totalForNonPkpStore = 0;
var voucherAmount = 0;
var NonPKPShipping = document.getElementsByClassName('non-pkp-shipping');
var totalTaxNonPkpShipping = 0;
var isGovernmentCheckout = governmentCheckout !== undefined && governmentCheckout.value === '1';
var isPKPTransaction = $('#is-pkp-trx').val() || '0';
var orderStatusWithoutTax = '';
var orderStatusWithTax = '';
var freeTaxNominal = 0;
var isFetched = false;

if (elementTaxInvoiceBtn.is(':checked')) {
  cartWithTax = 'with';
}
// console.log(isGovernmentCheckout,'gv');
if (isGovernmentCheckout) {
  taxCheckByHuman = true;
}


$('#approve-negotiation-budget-ceiling').on('keyup', function() {
  $(this).val($(this).val().toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1."));
});

// $('#approve-negotiation-budget-ceiling').on('change', function() {
//   var nominal = parseInt($(this).val().replace(/\./g,''));
//   var total   = elementGrandTotal.data('grand-total') + totalPPN.data('ppn-grand-total');
//   console.log(nominal, total, 'pagu');
//   if (nominal < total) {
//     showCartPopup(MSG_PAGU_ANGGARAN);
//     $(this).val(total.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1."));
    
//     setTimeout(function () {
//       $('#popup-cart').fadeOut();
//     }, 1000);
//   } 
// });

/**
 * Source: https://blog.abelotech.com/posts/number-currency-formatting-javascript/
 */
var formatNumber = function (num) {

  if (isNaN(num)) {
    num = 0
  }

  var tmpNum = parseFloat(num)
    .toFixed(1)
    .toString()
    .replace(/\.0+$/, '')
    .replace('.', ',')
    .replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
  return tmpNum
};

var getPPN = function (total_price) {
  return total_price - Math.round(total_price / 1.1);
}

var generatePPN = function(total_price, ppn_percentage){
  // return Math.round(total_price * 0.1);
  return parseFloat(total_price * ppn_percentage).toFixed(1)
}

var addPPN = function (total_price) {
  //add ppn from base price,
  // return Math.round(total_price * (0.1 + 1))
  return parseFloat(total_price * (ppnPercentage + 1)).toFixed(1)
}

var setAll = function (arr, val) {
  for (var i = 0; i < arr.length; ++i) {
    arr[i] = val;
  }
}

var getValueShipping = function (index, className) {
  if (className === undefined) {
    className = 'pkp-shipping'
  }

  var elementId = document.getElementsByClassName(className)[index].id;
  // console.log(elementId);
  var hashId = elementId.split('-')[2];
  // console.log(hashId);
  var radioName = 'shp_service[' + hashId + ']';
  // console.log(radioName);
  var radioValue = document.querySelector('input[name="' + radioName + '"]:checked').value;
  // console.log(radioValue);

  return radioValue.split('|')[1];
}

var sumAllArray = function (total, num) {
  return total + num;
}

var actionOnRemove = function (hash, response) {
  $('#cart-item-detail-' + hash).remove();
  $('#header-cart').html(response.template);
  $('#cart-total-items').html(response.total_items);
  $('#input-grand-total').html(response.grand_total_formatted);

  if (response.total_items < 1) {
    elementCartForm.remove();
    elementCheckoutForm.remove();
    $('#box-cart').append('<h5 class="sgd-text-center">' + MSG_NO_PRODUCT + '</h5>');
  }
};

var actionShippingCourier = function (submit) {
  var packageElement = $('#shipping-package-' + submit.hash);
  var content = '<option value="">' + LABEL_SELECT_OPTION + '</option>';

  if (checkoutVersion === 'v2') {
    content = '';
    packageElement.html(content);
  } else {
    packageElement.html(content).selectric('refresh');
    packageElement.val('').selectric('refresh');
  }
};

var getShippingServices = function (hash, address, courier) {
  elementLoading.show();

  $('#shipping-service-' + hash).val('');
  $('#shipping-price-' + hash).val('');

  if (checkoutVersion === 'v2') {
    $('#shipping-cost-' + hash).html('<b>Rp. 0</b>');
  } else {
    $('#shipping-cost-' + hash).html('<i class="fas fa-wallet"></i> Rp. 0');
  }

  var addressElement = $('#adr-detail-' + address + '-' + hash);
  var packageElement = $('#shipping-package-' + hash);
  var submit = {
    'hash': hash,
    'address': address,
    'courier': courier,
    'city': addressElement.attr('data-cid'),
    'province': addressElement.attr('data-pid'),
    'store': addressElement.attr('data-sid'),
    'origin': addressElement.attr('data-origin'),
    'weight': addressElement.attr('data-weight'),
  };

  console.log(submit,'sasasasas');

  $('#shipping-name-' + hash).val(courier);

  $.post(BASE_URL + '/order/shipping', $.extend(true, submit, TOKEN), function (response) {
    if (!response.error) {
      var costs = response.data[0].costs;
      var content = checkoutVersion === 'v2' ? '' : '<option value="">' + LABEL_SELECT_OPTION + '</option>';
      if (costs.length < 1) {
        showGeneralPopup(MSG_NO_COURIER);
        packageElement.html(content);


      } else {
        for (var i = 0; i < costs.length; i++) {
          var key = costs[i].service + '|' + costs[i].cost[0].value;
          var label = costs[i].service + ' (' + costs[i].cost[0].value + ')';
          if (checkoutVersion === 'v2') {
            if (costs[i].cost[0].etd.includes('HOURS')) {
              content += '<tr><td><input type="radio" name="shp_service[' + hash + ']" class="shipping-package" value="' + key + '" title="" data-hash="' + hash + '">&nbsp;' + costs[i].service + ' - ' + costs[i].description + '</td><td>' + LABEL_ESTIMATION + ': <b>' + costs[i].cost[0].etd + '</b></td><td>' + LABEL_PRICE + ': <b>Rp. ' + formatNumber(costs[i].cost[0].value) + '</b></td></tr>';
            } else {
              content += '<tr><td><input type="radio" name="shp_service[' + hash + ']" class="shipping-package" value="' + key + '" title="" data-hash="' + hash + '">&nbsp;' + costs[i].service + ' - ' + costs[i].description + '</td><td>' + LABEL_ESTIMATION + ': <b>' + costs[i].cost[0].etd.replace('HARI', '') + ' ' + LABEL_DAY + '</b></td><td>' + LABEL_PRICE + ': <b>Rp. ' + formatNumber(costs[i].cost[0].value) + '</b></td></tr>';
            }
          } else {
            content += '<option value="' + key + '">' + label + '</option>';
          }
        }

        if (checkoutVersion === 'v2') {
          packageElement.html(content);
        } else {
          packageElement.html(content).selectric('refresh');
        }

      }
    } else {
      if (response.message) {
        showGeneralPopup(response.message);
      } else {
        showGeneralPopup(MSG_COURIER_NETWORK_ERROR);
      }
    }

    elementLoading.hide();
  });
};

var calculateCartAmount = function (postData) {
  elementLoading.show();

  orderStatusWithoutTax = '';
  orderStatusWithTax = '';

  // Berfungsi menghilangkan error Total Value ketika Faktur Pajak di centang lalu voucher di remove.
  // Jadi seperti di reverse dulu, misal Faktur Pajak tercentang maka kalkulasi perhitungan shipping PPN with_tax jadi without
  // begitu juga sebaliknya (nilainya nanti dikembalikan setelah selesai kalkulasi -- line 155)
  setTimeout(function () {
    if (elementTaxInvoiceBtn.is(':checked') && PKPHasBeenClicked === true) {
      cartWithTax = 'without';
    } else {
      cartWithTax = 'with';
    }
  }, 500);
  console.log('postData',postData);
  $.post(BASE_URL + '/cart/calculate', $.extend(true, postData, TOKEN), function (response) {
    console.log('response',response);
    if (response.status) {
      var totalCalculation = response.with_tax === 'with' ? response.grand_total : response.grand_total;
      if (response.with_tax === 'with' && !isGovernmentCheckout) {
        if (totalTaxNonPkpShipping > 0) {
          totalCalculation -= totalTaxNonPkpShipping;
        }
      }

      if (parseFloat(response.free_tax_nominal) > 0) {
        freeTaxNominal = parseFloat(response.free_tax_nominal);
        totalCalculation -= freeTaxNominal;
        isFetched = true
      }

      elementGrandTotal.html('Rp. ' + formatNumber(totalCalculation));
      elementGrandTotal.attr('data-grand-total', response.grand_total);
      elementGrandTotal.attr('data-grand-total-with-tax', (response.grand_total_with_tax - freeTaxNominal));
      elementGrandTotal.attr('data-voucher-status', '');

      if (response.with_tax === 'without' && response.status_grand_total === 'CR') {
        // Voucher nominal is bigger than grand total

        orderStatusWithoutTax = response.status_grand_total;

        totalCalculation = 0;

        elementGrandTotal.html('Rp. ' + formatNumber(totalCalculation));
        elementGrandTotal.attr('data-grand-total', 0);
        elementGrandTotal.attr('data-grand-total-with-tax', 0);
        elementGrandTotal.attr('data-voucher-status', 'CR');
      }

      if (response.with_tax === 'with' && response.status_grand_total_with_tax === 'CR') {
        // Voucher nominal is bigger than grand total with tax

        orderStatusWithTax = response.status_grand_total_with_tax;

        totalCalculation = 0;

        elementGrandTotal.html('Rp. ' + formatNumber(totalCalculation));
        elementGrandTotal.attr('data-grand-total', 0);
        elementGrandTotal.attr('data-grand-total-with-tax', 0);
        elementGrandTotal.attr('data-voucher-status', 'CR');
      }

      if (response.b2gNonPkp) {
        b2gNonPkp = response.b2gNonPkp;
      }

      voucherAmount = 0

      if (parseInt(response.voucher_amount) > 0) {
        voucherAmount = parseInt(response.voucher_amount);
        elementInputVoucher.val('');
      }

      if (response.with_tax === 'with') {
        var shippingTaxCheck = 0;

        for (var p = 0; p < PKPShipping.length; p++) {
          if (parseInt(PKPShipping[p].value) > 0) {
            shippingTaxCheck += (parseInt(getValueShipping(p)) * ppnPercentage);
          }
        }

        var tax2 = parseFloat(response.tax_nominal) + parseFloat(shippingTaxCheck);

        tax2 -= freeTaxNominal;
        tax2 = tax2 < 0 ? 0 : tax2;

        totalPPN.attr('data-free-tax-nominal', freeTaxNominal)
        totalPPN.attr('data-ppn-grand-total', tax2);
        totalPPN.attr('data-ppn-grand-total-with-tax', tax2);
        totalPPN.html('Rp. ' + formatNumber(tax2));
      }

      if (parseInt(response.voucher_amount) > 0) {
        voucherAmount = parseInt(response.voucher_amount);
        elementInputVoucher.val('');

        shippingTaxCheck = 0;

        for (p = 0; p < PKPShipping.length; p++) {
          if (parseInt(PKPShipping[p].value) > 0) {
            shippingTaxCheck += (parseInt(getValueShipping(p)) * ppnPercentage);
          }
        }

        tax2 = parseFloat(response.tax_nominal) + parseFloat(shippingTaxCheck);

        tax2 -= freeTaxNominal;
        tax2 = tax2 < 0 ? 0 : tax2;

        totalPPN.attr('data-ppn-grand-total', tax2);
        totalPPN.attr('data-ppn-grand-total-with-tax', tax2);
        totalPPN.html('Rp. ' + formatNumber(tax2));
      }

    } else {
      if (response.message) {
        elementLoading.hide();
        showGeneralPopup(response.message);
        return
      }
    }

    // Cek juga apakah Faktur Pajak di centang atau tidak saat input/remove voucher
    // Karena ini mempengaruhi total belanja yang disebabkan rule -> jika centang Faktur Pajak dan Merchant PKP maka Ongkir + PPN 10% (di set di JS nya)
    setTimeout(function () {
      if (elementTaxInvoiceBtn.is(':checked') && (PKPHasBeenClicked === true || PKPHasBeenClicked === false) || response.with_tax === 'with') {
        cartWithTax = 'with';
        toggleCartTaxValue(cartWithTax);
      } else {
        cartWithTax = 'without';
        toggleCartTaxValue(cartWithTax);
      }
    }, 1000);

    elementLoading.hide();
  });
};

var hasBeenClickedPKP = false;
var ppnEachShippingPKP = [];
var toggleCartTaxValue = function (type) {
  // Fungsi: menghandle customer yang berbelanja di merchant PKP lalu mencentang faktur pajak,
  // maka ongkir + PPN 10% (sesuai revisi checkout)
  if (type === 'with' && PKPShipping !== undefined && PKPShippingCost !== undefined) {
    PKPHasBeenClicked = true;
    PKPPriceShipping = 0;
    totalTaxNonPkpShipping = 0;
    ppnPercentage = parseFloat(totalPPN.attr('data-ppn-percentage'));

    if (PKPShipping.length > 0 && taxCheckByHuman === true) {
      for (var i = 0; i < PKPShipping.length; i++) {
        // Cek apakah pkp-shipping terisi?
        // Jika terisi maka ambil value dari radio button shipping service, kemudian lakukan kalkulasi
        console.log(parseInt(PKPShipping[i].value) > 0);
        if (parseInt(PKPShipping[i].value) > 0) {
          PKPShippingCostNew = parseInt(getValueShipping(i)) + (parseInt(getValueShipping(i)) * ppnPercentage); // Ongkir + PPN
          PKPWithPPNEachShipping[i] = parseInt(getValueShipping(i)) * ppnPercentage; // PPN saja
        } else {
          PKPShippingCostNew = parseInt(PKPShipping[i].value) + (parseInt(PKPShipping[i].value) * ppnPercentage); // Ongkir + PPN
          PKPWithPPNEachShipping[i] = parseInt(PKPShipping[i].value) * ppnPercentage; // PPN saja
        }

        PKPShippingCost[i].innerHTML = '<b>Rp. '+formatNumber(Math.round(PKPShippingCostNew / (ppnPercentage + 1)) > 0 ? Math.round(PKPShippingCostNew / (ppnPercentage + 1)) : 0)+'</b>'; // Menampilkan ongkir baru
        var value_nego_price = PKPShippingCostNew > 0 ? PKPShippingCostNew : 0;
        if (elementNegoShippingShow.length > 0){
          elementNegoShippingShow[i].value = formatNumber(Math.round(PKPShippingCostNew / (ppnPercentage + 1)));
        }
        if (elementNegoShipping.length > 0) {
          elementNegoShipping[i].value = value_nego_price;
          
          // console.log('elementNegoShipping : ', elementNegoShipping[i].value);
        }

        PKPShipping[i].value = PKPShippingCostNew > 0 ? PKPShippingCostNew : 0;
        PKPPriceShipping = PKPPriceShipping + parseInt(PKPShipping[i].value);
      }
    } else {
      var shippingTax = 0;
      var ppnPercentage = parseFloat(totalPPN.attr('data-ppn-percentage'));
      var taxTotalTmp   = generatePPN(parseFloat(elementGrandTotal.attr('data-grand-total')), parseFloat(ppnPercentage))

      // taxTotalTmp -= freeTaxNominal;

      for (var l = 0; l < PKPShipping.length; l++) {
        PKPWithPPNEachShipping[l] = 0;

        if (parseInt(PKPShipping[l].value) > 0) {
          shippingTax += (parseInt(getValueShipping(l)) * ppnPercentage);
        }
      }

      taxTotalTmp = parseFloat(taxTotalTmp) + parseFloat(shippingTax);

      PKPPriceShipping = 0;
    }

    if (!isGovernmentCheckout) {
      if (NonPKPShipping.length > 0 && taxCheckByHuman === true) {
        for (var idx = 0; idx < NonPKPShipping.length; idx++) {
          if (parseInt(NonPKPShipping[idx].value) > 0) {
            totalTaxNonPkpShipping += parseInt(getValueShipping(idx, 'non-pkp-shipping')) * ppnPercentage; // PPN saja
          }
        }
      }
    }
    autoChangePrice()
  }

  if (type === 'without' && PKPShipping !== undefined && PKPShippingCost !== undefined) {
    if (PKPHasBeenClicked === true && taxCheckByHuman === false) {
      for (var j = 0; j < PKPShipping.length; j++) {
        PKPShippingCostNew = parseInt(PKPShipping[j].value) - PKPWithPPNEachShipping[j];
        // console.log('PKPShippingCostNew', PKPShippingCostNew);
        PKPShippingCost[j].innerHTML = '<b>Rp. ' + formatNumber(PKPShippingCostNew > 0 ? PKPShippingCostNew : 0) + '</b>';
        var value_nego_price = PKPShippingCostNew > 0 ? PKPShippingCostNew : 0;
        if (elementNegoShippingShow.length > 0) {
          elementNegoShippingShow[j].value = formatNumber(value_nego_price);
        }
        if (elementNegoShipping.length > 0) {
          elementNegoShipping[j].value = value_nego_price;

        }

        PKPShipping[j].value = PKPShippingCostNew > 0 ? PKPShippingCostNew : 0;
      }
      setAll(PKPWithPPNEachShipping, 0);
      PKPHasBeenClicked = false;
    } else {
      for (var k = 0; k < PKPShipping.length; k++) {
        PKPShippingCostNew = parseInt(PKPShipping[k].value);
        PKPShippingCost[k].innerHTML = '<b>Rp. ' + formatNumber(PKPShippingCostNew > 0 ? PKPShippingCostNew : 0) + '</b>';
        var value_nego_price = PKPShippingCostNew > 0 ? PKPShippingCostNew : 0;
        if (elementNegoShippingShow.length > 0) {
          elementNegoShippingShow[k].value = formatNumber(value_nego_price);
          // console.log('elementNegoShippingShow : ', elementNegoShipping[k].value);
        }
        if (elementNegoShipping.length > 0) {
          elementNegoShipping[k].value = value_nego_price;
          // console.log('elementNegoShipping : ', elementNegoShipping[k].value);
        }

        PKPShipping[k].value = PKPShippingCostNew > 0 ? PKPShippingCostNew : 0;
      }
    }
    PKPPriceShipping = 0;
  }

  totalForPkpStore = 0;
  totalForNonPkpStore = 0;

  $('.item-sub-total').each(function () {
    var subTotal = $(this).attr('data-sub-total');
    var subTotalWithTax = $(this).attr('data-sub-total-with-tax');

    var pkp = $(this).attr('data-pkp')

    if (type === 'with') {

      if (!isGovernmentCheckout) {
        if (pkp === '1') {
          totalForPkpStore += parseInt(subTotalWithTax);
        } else if (pkp === '0') {
          totalForNonPkpStore += parseInt(subTotal);
        }
      }

      if (parseInt(subTotalWithTax) > 0) {
        $(this).html('Rp. ' + formatNumber(subTotal));
      }
    } else {
      $(this).html('Rp. ' + formatNumber(subTotal));
    }
  });

  // totalForPkpStore -= freeTaxNominal;
  // totalForNonPkpStore -= freeTaxNominal;

  var grandTotal = parseFloat(elementGrandTotal.attr('data-grand-total'));
  var taxTotal = parseFloat(totalPPN.attr('data-ppn-grand-total'));
  var ppnPercentage = parseFloat(totalPPN.attr('data-ppn-percentage'));

  // taxTotal -= freeTaxNominal;
  // grandTotal += taxTotal;

  if (type === 'with') {
    if (voucherAmount > 0) {
      grandTotal = grandTotal + parseFloat(voucherAmount);
    }

    var totalTmp = parseFloat(grandTotal) - parseFloat(voucherAmount)

    if (!isGovernmentCheckout) {
      if (PKPPriceShipping > 0) {
        totalForPkpStore += PKPPriceShipping / 1.1
      }

      if (totalForNonPkpStore > 0) {
        totalTmp -= totalForNonPkpStore * ppnPercentage
      }

      if (totalTaxNonPkpShipping > 0) {
        totalTmp -= totalTaxNonPkpShipping;
      }
    }

    if (totalTmp < 0) {
      totalTmp = 0;
    }

    if (grandTotal < 0) {
      grandTotal = 0;
    }

    // totalTmp -= freeTaxNominal;
    // grandTotal -= freeTaxNominal;

    if (isPKPTransaction === '1') {
      elementGrandTotal.html('Rp. ' + formatNumber(totalTmp));
    } else {
      elementGrandTotal.html('Rp. ' + formatNumber(grandTotal));
    }

    if (!isGovernmentCheckout) {
      if (PKPWithPPNEachShipping.reduce(sumAllArray, 0) > 0 && totalForPkpStore > 0) {
        totalTmp = totalForPkpStore;

      } else if (totalForNonPkpStore > 0) {
        totalTmp = totalForNonPkpStore;
      }
    }

    if (PKPWithPPNEachShipping.reduce(sumAllArray, 0) > 0) {
      // taxTotal -= freeTaxNominal
      taxTotal = taxTotal < 0 ? 0 : taxTotal;
      totalPPN.html('Rp. ' + formatNumber(taxTotal));
    } else {
      if (voucherAmount > 0) {
        var shippingTaxDoubleCheck = 0;
        var grandTotalCheck = parseFloat(elementGrandTotal.attr('data-grand-total'));
        var grandTotalWithTaxCheck = parseFloat(elementGrandTotal.attr('data-grand-total-with-tax'));
        var ppnPercentage = parseFloat(totalPPN.attr('data-ppn-percentage'));

        for (var p = 0; p < PKPShipping.length; p++) {
          if (parseInt(PKPShipping[p].value) > 0) {
            shippingTaxDoubleCheck += (parseInt(getValueShipping(p)) * ppnPercentage);
          }
        }

        taxTotal -= shippingTaxDoubleCheck;
        grandTotalCheck += shippingTaxDoubleCheck;
        grandTotalWithTaxCheck += shippingTaxDoubleCheck;

        if (orderStatusWithoutTax === 'CR') {
          grandTotalCheck = 0;
        }

        if (orderStatusWithTax === 'CR') {
          grandTotalWithTaxCheck = 0
        }

        grandTotalWithTaxCheck -= freeTaxNominal;
        taxTotal -= freeTaxNominal;

        totalPPN.attr('data-ppn-grand-total', taxTotal);
        totalPPN.attr('data-ppn-grand-total-with-tax', taxTotal);

        elementGrandTotal.attr('data-grand-total', grandTotalCheck);
        elementGrandTotal.attr('data-grand-total-with-tax', grandTotalWithTaxCheck);
        elementGrandTotal.html('Rp. ' + formatNumber(grandTotalWithTaxCheck));

      } else {

        grandTotal = parseFloat(elementGrandTotal.attr('data-grand-total'))
        // taxTotal = generatePPN(grandTotal, ppnPercentage);
        // grandTotalWithTax = grandTotal + parseFloat(taxTotal);
        taxTotal = getPPN(grandTotal);
        grandTotalWithTax = grandTotal;

        if (isFetched === false) {
          freeTaxNominal = parseFloat(elementGrandTotal.attr('data-free-tax-nominal'))
        }

        taxTotal -= freeTaxNominal;
        if (taxTotal < 0) {
          taxTotal = 0;
        } else {
          grandTotalWithTax -= freeTaxNominal;
        }

        totalPPN.attr('data-ppn-grand-total', taxTotal);
        totalPPN.attr('data-ppn-grand-total-with-tax', taxTotal);
        totalPPN.html('Rp. ' + formatNumber(taxTotal));

        elementGrandTotal.attr('data-grand-total', grandTotal);
        elementGrandTotal.attr('data-grand-total-with-tax', grandTotalWithTax);
        elementGrandTotal.html('Rp. ' + formatNumber(grandTotalWithTax));
      }
    }
  } else {
    elementGrandTotal.html('Rp. ' + formatNumber(grandTotal));
    if (PKPWithPPNEachShipping.reduce(sumAllArray, 0) > 0) {
      taxTotal = taxTotal < 0 ? 0 : taxTotal;
      totalPPN.html('Rp. ' + formatNumber(taxTotal));
    } else {
      taxTotal = taxTotal < 0 ? 0 : taxTotal;
      totalPPN.html('Rp. ' + formatNumber(taxTotal));
    }
  }
};

var renderVoucherList = function (vouchers) {
  var element = $('#order-coupon-template').html();
  var content = '';

  Object.keys(vouchers).forEach(function (key, index) {
    content += element
      .replace('**code1**', key)
      .replace('**code2**', key)
      .replace('**code3**', key)
      .replace('**amount**', vouchers[key].amount_formatted)
    ;
  });

  $('#voucher-element').html(content);
};

$(document).ready(function () {
  if (elementTaxInvoiceBtn.is(':checked')) {
    cartWithTax = 'with';
  }

  if (elementBuyWrapper.length) {
    $(document).on('click', '.plus-qty', function (e) {
      e.preventDefault();

      var input = elementInputCartQty.val();
      var max = $(this).attr('data-max');

      input++;

      if (input >= max) {
        input = max;
      }

      elementInputCartQty.val(input);
    });

    $(document).on('click', '.minus-qty', function (e) {
      e.preventDefault();

      var input = elementInputCartQty.val();
      var min = $(this).attr('data-min');

      input--;

      if (input <= min) {
        input = min;
      }

      elementInputCartQty.val(input);
    });

    $(document).on('click', '#btn-add-to-cart', function (e) {
      e.preventDefault();

      elementLoading.show();

      var submit = {
        'hash': $(this).attr('data-hash'),
        'quantity': elementInputCartQty.val(),
      };

      $.post(BASE_URL + '/cart/add', $.extend(true, submit, TOKEN), function (response) {
        if (response.status) {
          $('#header-cart').html(response.template);
          $('#cart-total-items').html(response.total_items);

          showCartPopup(MSG_ADD_TO_CART);

          setTimeout(function () {
            $('#popup-cart').fadeOut();
          }, 1000);
        } else {

          if (response.message) {
            showCartPopup(response.message)

            setTimeout(function () {
              $('#popup-cart').fadeOut();
            }, 1000);
          }
        }

        elementLoading.hide();
      });
    });
  }

  if (elementCartForm.length) {
    $(document).on('click', '.toggle-edit-cart', function (e) {
      e.preventDefault();

      var hash = $(this).attr('data-hash');

      $('#cart-item-detail-' + hash).toggleClass('on-edit');
    });

    $(document).on('click', '.cart-plus-qty', function (e) {
      e.preventDefault();

      var hash = $(this).attr('data-hash');
      var element = $('#cart-item-qty-' + hash);
      var input = element.val();
      var max = $(this).attr('data-max');

      input++;

      if (input >= max) {
        input = max;
      }

      element.val(input);
    });

    $(document).on('click', '.cart-minus-qty', function (e) {
      e.preventDefault();

      var hash = $(this).attr('data-hash');
      var element = $('#cart-item-qty-' + hash);
      var input = element.val();
      var min = $(this).attr('data-min');

      input--;

      if (input <= min) {
        input = min;
      }

      element.val(input);
    });

    $(document).on('click', '.btn-update-cart-item', function (e) {
      e.preventDefault();

      var hash = $(this).attr('data-hash');
      var submit = {
        'hash': hash,
        'quantity': $('#cart-item-qty-' + hash).val(),
      };

      $.post(BASE_URL + '/cart/update', $.extend(true, submit, TOKEN), function (response) {
        if (response.status) {
          $('#cart-item-qty2-' + hash).html('QTY <b>' + response.item.quantity + '</b>');
          $('#cart-item-total-' + hash).html(response.item.attributes.total_price);
          $('#input-grand-total').html(response.grand_total_formatted);
          $('#cart-item-detail-' + hash).toggleClass('on-edit');
          $('#header-cart').html(response.template);
        } else {
          if (response.message) {
            showCartPopup(response.message);

            setTimeout(function () {
              $('#popup-cart').fadeOut();
            }, 1000);
          }
        }
      });
    });

    $(document).on('click', '.btn-remove-cart-item', function (e) {
      e.preventDefault();

      showLoading();

      var submit = {
        'hash': $(this).attr('data-hash'),
      };

      $.post(BASE_URL + '/cart/remove', $.extend(true, submit, TOKEN), function (response) {
        if (response.status) {
          actionOnRemove(submit.hash, response);
        }

        hideLoading();
      });
    });
  }

  if (elementCheckoutForm.length) {
    $(document).on('change', '.adr-opt', function (e) {
      // if ($(this).prop('checked')) {
      if (e.target.value) {
        // var address = $(this).val();
        var address = e.target.value;
        // console.log($(e.target),$('option:selected', this).data('hash'));
        var hash = $('option:selected', this).data('hash');
        var pkp = $('option:selected', this).data('pkp') || '0';
        var element = $('#adr-detail-' + address + '-' + hash);
        var courier = $('#shipping-courier-' + hash).val();
        var submit = {
          'hash': hash,
          'address': address,
          'city': element.attr('data-cid'),
          'province': element.attr('data-pid'),
          'store': element.attr('data-sid'),
          'origin': element.attr('data-origin'),
          'weight': element.attr('data-weight'),
        };

        console.log(submit);

        actionShippingCourier(submit);

        if (courier !== '') {
          getShippingServices(hash, address, courier);
        }

        if (isPKPTransaction === '1') {
          cartWithTax = 'with';
        }

        if (pkp === '0') {
          cartWithTax = 'without';
        }

        calculateCartAmount({
          hash: hash,
          cost: 0,
          // with_tax: cartWithTax,
          with_tax: 'with',
          grand_total: elementGrandTotal.attr('data-grand-total'),
          grand_total_with_tax: elementGrandTotal.attr('data-grand-total-with-tax'),
          skip_voucher: 'yes',
        });
      }
    });

    $('.shipping-courier').change(function () {
      var id = $(this).attr('id');
      var hash = id.replace('shipping-courier-', '');
      var address = $('select[name="address[' + hash + ']"]').val() || 0;
      console.log(address);
      if ($(this).val() === '' && checkoutVersion === 'v2') {
        $('#shipping-package-' + hash).html('');
      }

      if ($(this).val() !== '' && address > 0) {
        getShippingServices(hash, address, $(this).val());
      }

      if (isPKPTransaction === '1') {
        cartWithTax = 'with';
      }

      calculateCartAmount({
        hash: hash,
        cost: 0,
        // with_tax: cartWithTax,
        with_tax: 'with',
        grand_total: elementGrandTotal.attr('data-grand-total'),
        grand_total_with_tax: elementGrandTotal.attr('data-grand-total-with-tax'),
        skip_voucher: 'yes',
      });
    });

    if (checkoutVersion === 'v2') {
      $(document).on('click', '.shipping-package', function () {
        var hash = $(this).attr('data-hash');
        var value = $(this).is(':checked') ? $(this).val() : '';

        if (hash !== '' && value !== '') {
          if (isPKPTransaction === '1') {
            cartWithTax = 'with';
          } else {
            cartWithTax = 'without';
          }

          var oldGrandTotal = elementGrandTotal.attr('data-grand-total');
          var oldGrandTotalWithTax = elementGrandTotal.attr('data-grand-total-with-tax');
          var postData = {
            hash: hash,
            cost: 0,
            // with_tax: cartWithTax,
            with_tax: 'with',
            grand_total: oldGrandTotal,
            grand_total_with_tax: oldGrandTotalWithTax,
            skip_voucher: 'yes',
          };

          if (value !== '') {
            var data = value.split('|');

            postData.cost = data[1];
            // console.log(data);
            $('#shipping-service-' + hash).val(data[0]);
            $('#shipping-price-' + hash).val(data[1]);
            $('#shipping-cost-' + hash).html('<b>Rp. ' + formatNumber(data[1]) + '</b>');
          } else {
            $('#shipping-service-' + hash).val('');
            $('#shipping-price-' + hash).val('');
            $('#shipping-cost-' + hash).html('<b>Rp. 0</b>');
          }

          calculateCartAmount(postData);
        }
      });
    } else {
      $('.shipping-package').change(function () {
        var id = $(this).attr('id');
        var hash = id.replace('shipping-package-', '');

        if (hash !== '') {
          if (isPKPTransaction === '1') {
            cartWithTax = 'with';
          }

          var value = $(this).val();
          var oldGrandTotal = elementGrandTotal.attr('data-grand-total');
          var oldGrandTotalWithTax = elementGrandTotal.attr('data-grand-total-with-tax');
          var postData = {
            hash: hash,
            cost: 0,
            // with_tax: cartWithTax,
            with_tax: 'with',
            grand_total: oldGrandTotal,
            grand_total_with_tax: oldGrandTotalWithTax,
            skip_voucher: 'yes',
          };

          if (value !== '') {
            var data = value.split('|');

            postData.cost = data[1];

            $('#shipping-service-' + hash).val(data[0]);
            $('#shipping-price-' + hash).val(data[1]);
            $('#shipping-cost-' + hash).html('<i class="fas fa-wallet"></i> Rp. ' + formatNumber(data[1]));
          } else {
            $('#shipping-service-' + hash).val('');
            $('#shipping-price-' + hash).val('');
            $('#shipping-cost-' + hash).html('<i class="fas fa-wallet"></i> Rp. 0');
          }

          calculateCartAmount(postData);
        }
      });
    }

    $('.check-negotiated').click(function () {
      var hash = $(this).attr('data-hash');

      if ($(this).prop('checked')) {
        $('.negotiated-item-' + hash).attr('required', 'required');
        $('.negotiated-column-' + hash).show();
      } else {
        $('.negotiated-column-' + hash).hide();
        $('.negotiated-item-' + hash).removeAttr('required');
      }
    });

    $('#checkout-now').click(function(e) {
      e.preventDefault()

      var addressValid = false;
      var shippingValid = false;
      var packageValid = false;
      var tncValid = false;
      var taxValid = false;
      var phoneValid = false;
      var hashes = [];
      var methodPayment = false;

      if (cartFreeDelivery === 'no') {

        if ($('.adr-opt').val() == '') {
          showGeneralPopup(MSG_SELECT_ADDRESS);

          return false;
        }

        $('.shipping-courier').each(function () {
          if ($(this).val() !== '') {
            shippingValid = true;
          }
        });

        if (shippingValid === false) {
          showGeneralPopup(MSG_SELECT_COURIER);

          return false;
        }

        $('.shipping-package').each(function () {
          if (checkoutVersion === 'v2') {
            hashes.push($(this).attr('data-hash'));
            hashes = arrayUnique(hashes);

            for (var i = 0; i < hashes.length; i++) {
              if ($('input[name="shp_service[' + hashes[i] + ']"]').is(':checked')) {
                packageValid = true;
              }
            }
          } else {
            if ($(this).val() !== '') {
              packageValid = true;
            }
          }
        });

        if (packageValid === false) {
          showGeneralPopup(MSG_SELECT_SERVICE);

          return false;
        }
      }

      if ($('.satker-opt').length) {
        var satkerChecked = 0;
        var satkerValid = false;

        $('.satker-opt').each(function () {
          if ($(this).is(':checked')) {
            satkerChecked++;
          }
        })

        if (satkerChecked > 0) {
          satkerValid = true;
        }

        // if (satkerValid === false) {
        //   showGeneralPopup(MSG_MISSING_WORKUNIT)
        //
        //   return false;
        // }
      }

      if ($('input[name="tnc"]').is(':checked')) {
        tncValid = true;
      }

      if (tncValid === false) {
        showGeneralPopup(MSG_ACCEPT_TNC);

        return false;
      }

      if (cartWithTax === 'with' && !b2gNonPkp && elementTaxInvoiceBtn.is(':checked')) {
        var taxSelected = 0;

        $('.tax-opt').each(function () {
          if ($(this).is(':checked')) {
            taxSelected++;
          }
        });

        if (taxSelected > 0) {
          taxValid = true;
        }

        if (taxValid === false) {
          showGeneralPopup(MSG_SELECT_TAX_DOCUMENT);

          return false;
        }
      }

      if ($('#no-phone').length) {
        //
      } else {
        phoneValid = true;
      }

      if (phoneValid === false) {
        showGeneralPopup(MSG_NO_PHONE);

        return false;
      }

      if ($('.check-negotiated').length) {
        var negotiatedValid = true;

        $('.negotiation-price').each(function () {
          if ($(this).val() === '' || $(this).val() === '0') {
            negotiatedValid = false;
          }
        });

        $('.negotiation-time').each(function () {
          if ($(this).val() === '') {
            negotiatedValid = false;
          }
        });

        $('.negotiation-shipping').each(function () {
          if ($(this).val() === '') {
            negotiatedValid = false;
          }
        });

        if (negotiatedValid === false) {
          showGeneralPopup(MSG_CHECK_NEGOTIATION_FIELDS);

          return false;
        }
      }

      // Safe to assume that all validations passed -- hopefully
      $.ajax({
        method: 'POST',
        url: BASE_URL + '/order/pre-process',
        data: $.extend(true, {}, TOKEN),
        beforeSend: function (xhr) {
          elementLoading.show();
        }
      })
        .done(function (response) {
          if (response.status) {
            $('#popup-approve-negotiation').show();
            
            $('#popup-approve-negotiation-btn').on('click', function () {
              e.preventDefault();

              // showConfirmPopup(B2G_MSG_CHECKOUT);

              // $('#popup-confirm-btn').on('click', function () {
                // e.preventDefault();

                if ($('#approve-negotiation-payment-method').val() == "" || $('#approve-negotiation-payment-method').val() == null) {
                  showGeneralPopup(MSG_SELECT_METHOD_PAY);

                  $('.close-popup').on('click', function (e){
                    e.preventDefault()
                    e.stopPropagation()
                    $(this).parents('.popup').fadeOut();
                  })

                  $('#popup-confirm').fadeOut();

                  $('#popup-approve-negotiation').show();

                  return false;
                }

                if ($('#approve-negotiation-source-of-fund').val() == "" || $('#approve-negotiation-source-of-fund').val() == null) {
                  showGeneralPopup(MSG_SELECT_SOURCE);

                  $('.close-popup').on('click', function (e){
                    e.preventDefault()
                    e.stopPropagation()
                    $(this).parents('.popup').fadeOut();
                  })

                  $('#popup-confirm').fadeOut();

                  $('#popup-approve-negotiation').show();

                  return false;
                }


                if ($('#approve-negotiation-ppk-select').val() == "" || $('#approve-negotiation-ppk-select').val() == null) {
                  showGeneralPopup(MSG_SELECT_PPK);

                  $('.close-popup').on('click', function (e){
                    e.preventDefault()
                    e.stopPropagation()
                    $(this).parents('.popup').fadeOut();
                  })

                  $('#popup-confirm').fadeOut();

                  $('#popup-approve-negotiation').show();

                  return false;
                }

                if ($('#approve-negotiation-treasurer-select').val() == "" || $('#approve-negotiation-treasurer-select').val() == null) {
                  showGeneralPopup(MSG_SELECT_TREASURER);

                  $('.close-popup').on('click', function (e){
                    e.preventDefault()
                    e.stopPropagation()
                    $(this).parents('.popup').fadeOut();
                  })

                  $('#popup-confirm').fadeOut();

                  $('#popup-approve-negotiation').show();

                  return false;
                }

                if ($('#approve-negotiation-satker-select').val() == "" || $('#approve-negotiation-satker-select').val() == null) {
                  showGeneralPopup('Harap memilih Data Satker');

                  $('.close-popup').on('click', function (e){
                    e.preventDefault()
                    e.stopPropagation()
                    $(this).parents('.popup').fadeOut();
                  })

                  $('#popup-confirm').fadeOut();

                  $('#popup-approve-negotiation').show();

                  return false;
                }

                // if ($('#approve-negotiation-pic-select').val() == "" || $('#approve-negotiation-pic-select').val() == null) {
                //   showGeneralPopup(MSG_SELECT_PIC);

                //   $('.close-popup').on('click', function (e){
                //     e.preventDefault()
                //     e.stopPropagation()
                //     $(this).parents('.popup').fadeOut();
                //   })

                //   $('#popup-approve-negotiation').show();

                //   return false;
                // }

                // hideConfirmPopup();
                showLoading();

                elementCheckoutForm.submit();
  
              // })
            })

          } else {
            showGeneralPopup(response.message);
          }
        })
        .fail(function (jqXHR, textStatus, errorThrown) {
          //
        })
        .always(function () {
          elementLoading.hide();
        });
    });

    $("#approve-unit-name").on('keyup', function() {
      // console.log($(this).val())
      if ($(this).val() != '') {
        $(this).attr('required', true);
        $("#approve-unit-pic").attr('required', true);
        $("#approve-unit-email").attr('required', true);
      } else {
        $(this).removeAttr('required');
        $("#approve-unit-pic").removeAttr('required');
        $("#approve-unit-email").removeAttr('required');
      }
    });


    if (elementTaxInvoiceBtn.is(':checked')) {
      cartWithTax = 'with';

      toggleCartTaxValue(cartWithTax);
    }

    elementTaxInvoiceBtn.on('change', function (e) {
      e.preventDefault();

      if ($(this).is(':checked')) {
        $('#with-tax').show();
        $('.with-tax').show();
        $('#without-tax').hide();
        $('.without-tax').hide();
        $('#tax-documents').show();
        taxCheckByHuman = true;
        cartWithTax = 'with';
        taxCheckByHuman = true;
      } else {
        $('#with-tax').hide();
        $('.with-tax').hide();
        $('#without-tax').show();
        $('.without-tax').show();
        $('#tax-documents').hide();

        cartWithTax = 'without';
        taxCheckByHuman = false;

        $('.tax-opt').each(function () {
          $(this).prop('checked', false);
        });
      }

      // toggleCartTaxValue(cartWithTax);
    });

    if (elementSelfPickUp.length) {
      elementSelfPickUp.on('change', function (e) {
        e.preventDefault();

        if (isPKPTransaction === '1') {
          cartWithTax = 'with';
        } else {
          cartWithTax = 'without'
        }

        var hash = $(this).attr('data-hash');
        var shippingCourier = $('#shipping-courier-' + hash);
        var shippingPackage = $('#shipping-package-' + hash);
        var shippingCost = $('#shipping-cost-' + hash);
        var courierChoices = $('#courier-choices-' + hash);
        var postData = {
          hash: hash,
          cost: 0,
          // with_tax: cartWithTax,
          with_tax: 'with',
          grand_total: elementGrandTotal.attr('data-grand-total'),
          grand_total_with_tax: elementGrandTotal.attr('data-grand-total-with-tax'),
          skip_voucher: 'yes',
        };

        if ($(this).is(':checked')) {
          cartFreeDelivery = 'yes';
          shippingCourier.attr('disabled', 'disabled').selectric('refresh');
          $('#address-element-' + hash + ' .adr-opt').attr('disabled', 'disabled');

          if (checkoutVersion === 'v2') {
            $('input[name="shp_service[' + hash + ']"]').attr('disabled', 'disabled');
            shippingCost.html('<b>Rp. 0</b>');
          } else {
            shippingPackage.attr('disabled', 'disabled').selectric('refresh');
            // courierChoices.hide();
            shippingCost.html('<i class="fas fa-wallet"></i> Rp. 0');
          }
        } else {
          cartFreeDelivery = 'no';
          shippingCourier.removeAttr('disabled').selectric('refresh');
          postData.cost = parseInt($('#shipping-price-' + hash).val());
          $('#address-element-' + hash + ' .adr-opt').removeAttr('disabled');

          if (checkoutVersion === 'v2') {
            $('input[name="shp_service[' + hash + ']"]').removeAttr('disabled');

            if (postData.cost > 0) {
              shippingCost.html('<b>Rp. ' + formatNumber(postData.cost) + '</b>');
            }
          } else {
            shippingPackage.removeAttr('disabled').selectric('refresh');
            // courierChoices.show();

            if (postData.cost > 0) {
              shippingCost.html('<i class="fas fa-wallet"></i> Rp. ' + formatNumber(postData.cost));
            }
          }
        }

        calculateCartAmount(postData);
      });
    }

    if (elementApplyVoucher.length) {
      elementApplyVoucher.click(function (e) {
        e.preventDefault();

        elementLoading.show();

        var code = elementInputVoucher.val();
        var hash = randomString(10, 'an');

        if (code !== '') {
          $.get(BASE_URL + '/cart/voucher/' + code, function (response) {
            if (response.status) {
              renderVoucherList(response.vouchers);

              if (isPKPTransaction === '1') {
                cartWithTax = 'with';
              } else {
                cartWithTax = 'without';
              }


              calculateCartAmount({
                hash: hash,
                cost: 0,
                // with_tax: cartWithTax,
                with_tax: 'with',
                grand_total: elementGrandTotal.attr('data-grand-total'),
                grand_total_with_tax: elementGrandTotal.attr('data-grand-total-with-tax'),
                skip_voucher: 'no',
              });
            } else {
              showGeneralPopup(response.message);
            }
          });
        }

        elementLoading.hide();
      });

      $(document).on('click', '.remove-voucher', function (e) {
        e.preventDefault();

        elementLoading.show();

        var code = $(this).attr('data-code');
        var hash = randomString(10, 'an');
        var submit = {
          code: code,
          status: elementGrandTotal.attr('data-voucher-status'),
        };

        if (code !== '') {
          $.post(BASE_URL + '/cart/remove-voucher', $.extend(true, submit, TOKEN), function (response) {
            if (response.deleted) {
              $('#ov-' + code).remove();

              if (isPKPTransaction === '1') {
                cartWithTax = 'with';
              }

              calculateCartAmount({
                hash: hash,
                cost: 0,
                // with_tax: cartWithTax,
                with_tax: 'with',
                grand_total: elementGrandTotal.attr('data-grand-total'),
                grand_total_with_tax: elementGrandTotal.attr('data-grand-total-with-tax'),
                skip_voucher: 'yes',
              });
            }
          });
        }

        elementLoading.hide();
      });

      if ($('.remove-voucher').length > 0) {
        if (isPKPTransaction === '1') {
          cartWithTax = 'with';
        }

        calculateCartAmount({
          hash: randomString(10, 'an'),
          cost: 0,
          // with_tax: cartWithTax,
          with_tax: 'with',
          grand_total: elementGrandTotal.attr('data-grand-total'),
          grand_total_with_tax: elementGrandTotal.attr('data-grand-total-with-tax'),
          skip_voucher: 'no',
        });
      }
    }
  }

  $(document).on('click', '.btn-remove-from-cart', function (e) {
    e.preventDefault();

    showLoading();

    var submit = {
      'hash': $(this).attr('data-hash'),
    };

    $.post(BASE_URL + '/cart/remove', $.extend(true, submit, TOKEN), function (response) {
      if (response.status) {
        actionOnRemove(submit.hash, response);

        showGeneralPopup(MSG_REMOVE_FROM_CART);
      }

      hideLoading();
    });
  });

  $('.cart-need-login').click(function (e) {
    e.preventDefault();

    showGeneralPopup(MSG_NON_USER_CART_INFO);
  });

  $('.cart-incomplete').click(function (e) {
    e.preventDefault();

    showGeneralPopup(MSG_CART_NO_ADDRESS);
  });
});
