$(document).ready(function () {
    var showSubMenu          = false,
        toggleSubmenu        = false,
        userProfileContainer = $('.user-profile'),
        userProfileSubmenu   = $('.user-profile-submenu'),
        overlay              = $('.overlay'),
        timerId;

    var RequestHandler = {};

    userProfileContainer.click(function () {
        userProfileSubmenu.slideToggle(200);
        toggleSubmenu = !toggleSubmenu;
    });

    userProfileContainer.on('mouseleave', function () {
        showSubMenu = true;
    });

    userProfileContainer.on('mouseenter', function () {
        showSubMenu = false;
    });

    $(document).click(function() {
        if (showSubMenu && toggleSubmenu) {
            userProfileSubmenu.slideToggle(200);
            showSubMenu   = false;
            toggleSubmenu = false;
        }
    });

    var userName = $('.user-meta-name').text().split(' '),
        shortCutName = '';

    userName.forEach(function(item){
        shortCutName += item.charAt(0).toUpperCase();
    });

    $('.user-avatar p').text(shortCutName);

    function findGetParameter(parameterName) {
        var result = null,
            tmp = [];
        var items = location.search.substr(1).split("&");
        for (var index = 0; index < items.length; index++) {
            tmp = items[index].split("=");
            if (tmp[0] === parameterName) result = decodeURIComponent(tmp[1]);
        }
        return result;
    }

    /*TODO left this code for old functionality*/
    $('.sales-report-buttons .btn-submit').click(function(e){
        if ($(this).hasClass('edit-report-cancel')) {
            e.preventDefault();
        }
        if (overlay.hasClass('not-active') && typeof global_overlay_template === "undefined" || global_overlay_template.length < 1) {
            overlay.fadeIn(200).removeClass('not-active');
        }
    });

    $('.popup-buttons .btn-cancel').click(function(){
        if (!overlay.hasClass('not-active')) {
            overlay.fadeOut(200).addClass('not-active');
            restorePopup();
        }
    });

    $('.fa-trash-alt').click(function() {
        if (overlay.hasClass('not-active')) {
            overlay.find('h2').html($(this).data('popup-header'));
            overlay.find('p').html($(this).data('popup-text'));
            overlay.fadeIn(200).removeClass('not-active');
            var id = $(this).data('id');
            var path = $(this).data('delete-route');

            $('.popup-buttons .btn-submit').data('funcName', 'deleteEntity');

            var that = $(this),
                lastElement = $(this).parents('tbody').find('tr').length === 1 ? true : false,
                lastPage = $('.pagination-nav>*').last().prev().text();

            RequestHandler.deleteEntity = function() {
                $.ajax({
                    type: 'DELETE',
                    url:  that.data("delete-route"),
                    data: JSON.stringify({
                        'id' : that.data("id")
                    }),
                    dataType: 'JSON',
                    success: function (data) {
                        if (data.result === 'success') {
                            var page = findGetParameter('page');

                            if (page !== null && page === lastPage && lastElement) {
                                var newPage = --page;

                                if (newPage !== 0) {
                                    location.href = location.href.slice(0, -1) + newPage;
                                } else {
                                    location.reload();
                                }

                            } else {
                                location.reload();
                            }
                        }
                    }
                });
            };
        }
    });

    $('.reopen-report').click(function() {
        if (overlay.hasClass('not-active')) {
            overlay.find('h2').html($(this).data('popup-header'));
            overlay.find('p').html($(this).data('popup-text'));
            overlay.fadeIn(200).removeClass('not-active');

            $('.popup-buttons .btn-submit').data('funcName', 'reopenReport');

            var that = $(this);

            RequestHandler.reopenReport = function() {
                $.ajax({
                    type: 'POST',
                    url:  that.data("route"),
                    success: function(data) {
                        if (data.result) {
                            location.reload();
                        } else {
                            overlay.fadeOut(0).addClass('not-active');
                            $('.reopen-report-popup').removeClass('hide').find('.reopen-error-message').text(data.error);

                            timerId = setTimeout(function() {
                                $('.reopen-report-popup').addClass('hide');
                            }, 5000);
                        }
                    }
                });
            };
        }
    });

    $('.reopen-report-popup .fa-times').click(function () {
        $(this).parent().addClass('hide');
        clearTimeout(timerId);
    });

    //TODO: refactor next ajax calls:
    $('.popup-buttons .btn-submit').not('.form-data').click(function(e) {
        e.preventDefault();

        overlay.find('.btn-text').addClass('not-active');
        overlay.find('.btn-preloader').removeClass('not-active');

        var funcName = $(this).data('funcName');
        RequestHandler[funcName]();
    });

    $('.popup-buttons .btn-submit.form-data, .save-check-report button').click(function() {
        overlay.find('.btn-text').addClass('not-active');
        overlay.find('.btn-preloader').removeClass('not-active');

        var dateChanged = $('#date-changed').val();

        if (dateChanged) {
            var url = window.location.href;

            window.location = url.substring(0, url.lastIndexOf('/')) + '/' + dateChanged;
        } else {
            $.ajax({
                type: 'POST',
                url:  $(location).attr('pathname'),
                data: $('form').serialize(),
                dataType: 'JSON',
                success: function (data) {
                    if (data.result === 'success') {
                        if (typeof(data.path) !== 'undefined') {
                            location.replace(data.path);
                        } else {
                            location.reload();
                        }
                    }
                }
            });
        }
    });

    $('.return-to-overview').on('click', function(){
        var newUrl,
            url = window.location.href,
            urlArr = url.split('/');
        urlArr.splice(-2, 2);
        urlArr.push('overview');
        newUrl = urlArr.join('/');
        window.location.href = newUrl;
    });

    function restorePopup() {
        overlay.find('.btn-text').removeClass('not-active');
        overlay.find('.btn-preloader').addClass('not-active');
    }

    $('.not-active-tenant-user').click(function(e){
        e.preventDefault();
    });

    $('.ribbon').click(function () {
        window.open($(this).find('a').attr('href'), '_blank');
    });
});
