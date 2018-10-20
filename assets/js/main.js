$('.js-remove-product, .js-remove-category').on('click', function() {
    $(this).closest('td').find('.delete-form').submit();
});
