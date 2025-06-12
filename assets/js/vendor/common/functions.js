$(document).ready(function() {
    $(document).on('click', '.cke-img-picker', function() {
        var image = $(this).data('image');
        var func = $(this).data('func');

        window.opener.CKEDITOR.tools.callFunction(func, image);
        window.close();
    });
});
