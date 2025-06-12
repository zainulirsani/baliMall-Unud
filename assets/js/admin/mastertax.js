var select_umkm_category = $("#select-umkm-category");
var input_ppn            = $("#input-ppn");
var input_pph            = $("#input-pph");
select_umkm_category.on('change',function (e) {
    var value = e.target.value;
    $.get(BASE_URL+'/'+ADMIN_PATH+'/mastertax/'+value, function(response) {
        if (response.status) {
            input_ppn.val(response.ppn);
            input_pph.val(response.pph);
        }
    });
})