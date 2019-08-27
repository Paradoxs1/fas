$(document).ready(function () {

    var query = getQueryParams(document.location.search);

    if (!$.isEmptyObject(query) && typeof query.approved !== 'undefined') {
        $('#onlyApproved').attr('checked', true);
        $('.pagination-nav a').each(function() {
            $(this).attr('href', setUrlParam($(this).attr('href'), 'approved', 1));
        });
    }

    $('#onlyApproved').change(function() {
        var url = location.href;

        if (this.checked) {
            if (typeof query.page !== 'undefined') {
                url = setUrlParam(url, 'page', 1)
            }

            location.href = setUrlParam(url, 'approved', 1);
        } else {
            location.href = removeUrlParam(url, 'approved');
        }
    });

    $('.sales-reports-sum span, .tipping-table-money-box .tip-value').formatCurrency({
        digitGroupSymbol: "'",
        symbol: '',
        negativeFormat: '-%n'
    });

    function setUrlParam(uri, key, val) {
        return uri
            .replace(new RegExp("([?&]"+key+"(?=[=&#]|$)[^#&]*|(?=#|$))"), "&"+key+"="+encodeURIComponent(val))
            .replace(/^([^?&]+)&/, "$1?");
    }

    function removeUrlParam(url, parameter) {
        var urlparts= url.split('?');
        if (urlparts.length>=2) {

            var prefix= encodeURIComponent(parameter)+'=';
            var pars= urlparts[1].split(/[&;]/g);

            //reverse iteration as may be destructive
            for (var i= pars.length; i-- > 0;) {
                //idiom for string.startsWith
                if (pars[i].lastIndexOf(prefix, 0) !== -1) {
                    pars.splice(i, 1);
                }
            }
            return urlparts[0] + (pars.length > 0 ? '?' + pars.join('&') : '');
        }
        return url;
    }

    function getQueryParams(qs) {
        qs = qs.split('+').join(' ');

        var params = {},
            tokens,
            re = /[?&]?([^=]+)=([^&]*)/g;

        while (tokens = re.exec(qs)) {
            params[decodeURIComponent(tokens[1])] = decodeURIComponent(tokens[2]);
        }

        return params;
    }

});
