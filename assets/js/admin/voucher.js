var voucherForm = $('#voucher-form');
var elementStartAt = $('#input-start-at');
var elementEndAt = $('#input-end-at');

$(document).ready(function() {
  if (voucherForm.length) {
    var fpStartAt = elementStartAt.flatpickr(flatpickrConfig);
    var fpEndAt = elementEndAt.flatpickr(flatpickrConfig);

    fpStartAt.config.onChange.push(function(selectedDates, dateStr, instance) {
      fpEndAt.set('minDate', dateStr);
    });

    fpEndAt.config.onChange.push(function(selectedDates, dateStr, instance) {
      fpStartAt.set('maxDate', dateStr);
    });
  }
});
