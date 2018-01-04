require(["jquery"], function ($) {
    function getUrl(path) {
        return require.toUrl('').split('static/')[0] + path.replace(/^\//g, '');
    };

    $.ajax({
        url: getUrl('/mailchimp/script/get'),
        type: 'POST',
        dataType: 'json',
        showLoader: false
    }).done(function (data) {
        var imported = document.createElement('script');
        imported.src = data.url;
        document.head.appendChild(imported);

    });
});
