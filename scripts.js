jQuery(document).ready(function ($) {
    $("input[name='cat']").focus();

    $("input[name='cat']").autoComplete({
        source: function (term, response) {
            $.getJSON(searchUrl, { q: term }, function (data) {
                response(data);
            });
        },
        minChars: 2
    });

});
