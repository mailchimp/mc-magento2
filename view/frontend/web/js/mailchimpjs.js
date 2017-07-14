require(["jquery"], function ($) {
    $.ajax({
        url: 'mailchimp/script/get',
        type: 'POST',
        dataType: 'json',
        showLoader: false
    }).done(function (data) {
        var imported = document.createElement('script');
        imported.src = data.url;
        document.head.appendChild(imported);

    });
});
