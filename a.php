
$(function() {
    var $domainstring = '';
    var $max_results_shown = 20;
    var $check_pending = [];
    var $lookup_pending = 0;
    var $maximum_in_cart = 0;

    $(".addToCart").click(function(e) {
        if ($check_pending.length > 0) {

            // we could wait for a bit to check it again.
            e.preventDefault();

            // trigger another click in 100ms
            setTimeout(function(){ $(".addToCart").trigger("click") }, 200);
        }
    });

    $("#slider").on("slidechange", function( event, ui ) {
        // value of slider
        var $value = $(this).slider("value");
        var $max_chars = $domainstring.length + 3;
        if ($value > 0) {
            $max_chars += ($value / 5);
        }
        $("#max_chars").text($max_chars);

        // get value of checkbox too
        var $checked = $("#locations").prop("checked");

        var $max = $("#countResults").text();
        var $count = 0;

        $(".paid_rows").each(function() {
            $tld = $(this).find(".domainExtension").text();
            $loctld = $(this).attr("location");

            if ($tld.length > ($max_chars - $domainstring.length)) {
                $(this).hide();
            } else if ( $loctld == "false" && $checked ) {
                $(this).hide();
            } else {
                $(this).show();
                $count++;
            }
            if ($count > $max) {
                $(this).hide();
                $count--;
            }
        });
        if ($count > $max_results_shown) {
            $max_results_shown = $count;
        }
        $("#countResults").text($count);
    });

    $("#locations").change(function() {
        var $checkbox = $(this).find(":checkbox");
        var $checked = $(this).prop("checked");
        $checkbox.prop("checked", !$checked);
        $("#countResults").text($max_results_shown);
        $("#slider").trigger("slidechange");
    });

    $("#domainForm").submit(function() {
        $("#submitBtn").trigger("click");
        return false;
    });
    $("#domainForm2").submit(function() {
        // override other inputs with latest domain
        var $domainname = $(this).parent().find("[name='domainname']").val();
        $("[name='domainname']").val($domainname);
        $("#submitBtn").trigger("click");
        return false;
    });
    $("#domainForm3").submit(function() {
        // override other inputs with latest domain
        var $domainname = $(this).parent().find("[name='domainname']").val();
        $("[name='domainname']").val($domainname);
        $("#submitBtn").trigger("click");
        return false;
    });
    $("#submitBtn2").click(function() {
        var $domainname = $(this).parent().find("[name='domainname']").val();
        $("[name='domainname']").val($domainname);
        $("#submitBtn").trigger("click");
        return false;
    });

    $("#showAllResults").click(function(){
        $("#countResults").text(99999);
        $("#slider").trigger("slidechange");
    });

    $("#showMoreResults").click(function(){
        var $max = $("#countResults").text();
        $max = parseInt($max) + 20;
        $("#countResults").text($max);
        $("#slider").trigger("slidechange");
    });

    $(".domainCheck #submitBtn").click(function () {
        if ($lookup_pending == 1) {
            return;
        }

        // return if no value is given.
        var $domainname = $(this).parent().find("[name='domainname']").val().trim();
        if ($domainname == "" || $domainname == "Find your new Domain" || $domainname == "Find your new FREE Domain" || $domainname == "Find your new Free Domain" ) {
            return;
        }

        $lookup_pending = 1;

        // delete unwanted data
        $("#free_domains").find("tr").remove();
        $(".paid_rows").remove();

        // set all input boxes to this value
        $("[name='domainname']").val($domainname);

        var $domain;
        var $tld;
        var $dtest = new RegExp("^[a-zA-Z0-9-]+\.[a-zA-Z0-9]+$");
        var $result = "";

        // check domain syntax
        if ( $domainname.indexOf(".") > -1) {
            $tmp = $domainname;

            if ( !$dtest.test($tmp) ) {
                $result = "invalid_domain";
            }
        }

        if ( $domainname.indexOf(".") > -1 && $result == "") {
            $tmp = $domainname.split(".", 2);
            $domain = $tmp[0];
            $tld = $tmp[1];
        } else {
            $domain = $domainname;
            $tld = "";
        }

        // check syntax
        $tmp_domain = $domain + "." + $tld;
        if ($tld == "") { $tmp_domain += "tk"  };
        if ( !$dtest.test($tmp_domain) || $domain == "-" || $domain.length > 64) {
            $result = "invalid_domain";
        }

        if ($result == "invalid_domain") {
            $(".tmpResults").hide();
            $("iframe").remove();
            $(".dname").text($domain);
            $(".alert").show();
            $(".domainResult.zeroSection").show();
            $(".domainResult.firstSection").hide();
            $(".domainResult.secondSection.otherFreeDomains").hide();
            $(".domainResult.thirdSection.otherCostPriceDomains").hide();
            $(".bottomCart.paginate").hide();
            $(".domainCheck.firstCheck").addClass('idle');
            $(".domainPriceChart").addClass('idle');
            $(".allResults").addClass('active');
            $(".otherCostPriceDomains").hide();

            $lookup_pending = 0;
            return;
        }

        // update the global domainstring
        $domainstring = $domain;

        // do an availability test - AJAX
        $.ajax({
            url:    "https://my.freenom.com/includes/domains/fn-available.php",
            type:   'post',
            data:   { domain: $domain, tld: $tld },
            crossDomain: true,
            beforeSend: function(xhr) {
                xhr.withCredentials = true;
            },
            xhrFields: {
                withCredentials: true
            },
            error: function() {
                $lookup_pending = 0;
                $(".tmpResults").hide();
                $("iframe").remove();
            },
            success: function($data, $status) {
                $lookup_pending = 0;

                // remove iframe
                $(".tmpResults").hide();
                $("iframe").remove();

                // do something
                $tmpl = $.templates("#freedomain_tmpl");
                $html = $tmpl.render($data.free_domains);
                $("#free_domains").html($html);

                // hide text for free domains if special domains are in place
                var $free_count = $("#free_domains").find(".forFree:visible").length;
                var $special_count = $("#free_domains").find(".specialDomain:visible").length;

                // only special
                if ( $free_count == 0 && $special_count > 0) {
                    $("#check_free_domains").hide();
                    $("#check_free_special_domains").hide();
                    $("#check_special_domains").show();

                // only free
                } else if ( $free_count > 0 && $special_count == 0) {
                    $("#check_free_domains").show();
                    $("#check_free_special_domains").hide();
                    $("#check_special_domains").hide();

                // both free and special
                } else if ($free_count > 0 && $special_count > 0) {
                    $("#check_free_domains").hide();
                    $("#check_free_special_domains").show();
                    $("#check_special_domains").hide();

                // none
                } else {
                    $("#check_free_domains").hide();
                    $("#check_free_special_domains").hide();
                    $("#check_special_domains").hide();
                }

                $tmpl = $.templates("#paiddomain_tmpl");
                $html = $tmpl.render($data.paid_domains);
                $($html).insertAfter("#paid_domains");

                // display first 20
                $(".paid_rows").each(function($i, $v) {
                    if ($i < 20) {
                        $(this).show();
                    }
                });

                $(".domainCheck.firstCheck").addClass('idle');
                $(".allResults").addClass('active');

                // display some content
                $(".domainResult.zeroSection").hide();
                $(".domainResult.firstSection").show();
                $(".domainResult.secondSection.otherFreeDomains").show();
                $(".domainResult.thirdSection.otherCostPriceDomains").show();
                $(".bottomCart.paginate").show();
                $(".domainPriceChart").hide();

                // dont show top_domain if not wanted
                if ($data.top_domain["dont_show"] == 1) {
                    $("#top_domain").hide();
                    $(".succes").hide();
                    $(".alert").hide();
                    $(".no_tld_result").show();
                    $(".nrSelectedDomains").text($data["current_in_cart"] + 1);
                    updateCartCount(-1);

                // display the correct tag
                } else if ($data.top_domain['status'] == "AVAILABLE") {
                    // display nr in cart (-1 because we will add one)
                    $(".nrSelectedDomains").text($data["current_in_cart"] - 1);
                    updateCartCount(1);
                    $(".alert").hide();
                    $(".no_tld_result").hide();
                    $(".succes").show();

                    if ($data.top_domain['type'] == "PAID") {
                        $("#top_domain").find(".costPrice").show();
                        $("#top_domain").find(".forFree").hide();
                        $("#top_domain").find(".specialDomain").hide();
                        $("#top_domain").find(".upgradeDomain").hide();
                        $("#top_domain_get_it_text").text($("#top_domain_get_it_now_text").val()).hide();
                        $("#top_domain").find(".addedToCart").show();
                    } else if ($data.top_domain['type'] == "SPECIAL") {
                        $("#top_domain").find(".specialDomain").show();
                        $("#top_domain").find(".forFree").hide();
                        $("#top_domain").find(".upgradeDomain").hide();
                        $("#top_domain").find(".costPrice").hide();
                        $("#top_domain_get_it_text").text($("#top_domain_get_it_now_text").val()).hide();
                        $("#top_domain").find(".addedToCart").hide();
                        $("#top_domain").find(".removeSmall").hide();
                        $("#top_domain").find(".topNotAvailable").hide();
                        $("#top_domain").find(".addTopToCart").show();
                    } else if ($data.top_domain['type'] == "UPGRADE") {
                        $("#top_domain").find(".upgradeDomain").show();
                        $("#top_domain").find(".specialDomain").hide();
                        $("#top_domain").find(".forFree").hide();
                        $("#top_domain").find(".costPrice").hide();
                        $("#top_domain_get_it_text").text($("#top_domain_get_it_now_text").val()).hide();
                        $("#top_domain").find(".addedToCart").hide();
                        $("#top_domain").find(".removeSmall").hide();
                        $("#top_domain").find(".topNotAvailable").hide();
                        $("#top_domain").find(".addTopToCart").show();
                    } else {
                        $("#top_domain").find(".forFree").show();
                        $("#top_domain").find(".specialDomain").hide();
                        $("#top_domain").find(".upgradeDomain").hide();
                        $("#top_domain").find(".costPrice").hide();
                        $("#top_domain_get_it_text").text($("#top_domain_get_it_free_text").val()).hide();
                        $("#top_domain").find(".addedToCart").show();
                    }

                    // max in cart? its not selected
                    if ($data["maximum_reached"]) {
                        $("#top_domain").find(".addedToCart").hide();
                        $("#top_domain").find(".removeSmall").hide();
                        $("#top_domain").find(".topNotAvailable").hide();
                        $("#top_domain").find(".addTopToCart").show();
                    }

                    $("#top_domain").show();

                } else {
                    // display nr in cart - add one because we will remove one
                    $(".nrSelectedDomains").text($data["current_in_cart"] + 1);
                    updateCartCount(-1);

                    $(".no_tld_result").hide();
                    $(".succes").hide();
                    $("#top_domain").hide();
                    $(".alert").show();
                }

                $(".dname").text($data.top_domain['domain'] + $data.top_domain['tld']);
                $("#dname").text($data.top_domain['domain']);
                $("#dcurrency").text($data.top_domain['currency']);
                $("#dtld").text($data.top_domain['tld']);
                $("#dprice_int").html($data.top_domain['price_int'] + '.<span id="dprice_cent" class="cents"></span>');
                $("#dprice_cent").text($data.top_domain['price_cent']);

                // update slider maximum 20 for tld, 1 for dot
                var $tmp_max = 20 + $domainstring.length + 1;
                $("#max_chars").text($tmp_max);

                // add handler to select buttons
                $(".addSelect,.addTopToCart").click(function() {

                    // max in cart? stop adding more
                    var $current = $(".nrSelectedDomains").first().text();
                    if (parseInt($current) > 9) {
                        return;
                    }

                    $(this).hide();
                    //$(this).next() = $(this).next();
                    $(this).next().show();

                    var $domain = $(this).parent().parent().find(".domainName").text();
                    var $tld = $(this).parent().parent().find(".domainExtension").text();

                    if (!$domain) {
                        $domain = $(this).parent().parent().parent().find(".domainName").text();
                        $tld = $(this).parent().parent().parent().find(".domainExtension").text();
                    }
                    var $ldn = $(this).next();

                    $check_pending[$domain+$tld] = 1;

                    $.ajax({
                        url:    "https://my.freenom.com/includes/domains/fn-additional.php",
                        type:   'post',
                        data:   { domain: $domain, tld: $tld },
                        crossDomain: true,
                        beforeSend: function(xhr) {
                            xhr.withCredentials = true;
                        },
                        xhrFields: {
                            withCredentials: true
                        },
                        success: function($data, $status) {
                            delete $check_pending[$domain+$tld];
                            $check_pending = $check_pending - 1;
                            $ldn.hide();
                            if ($data.available) {
                                $ldn.next().show().next().show();
                                updateCartCount(1);
                            } else {
                                $ldn.next().next().next().show();
                            }
                        },
                    });
                });

                $(".removeSelected").click(function() {
                    $del_button = $(this);
                    var $domain = $(this).parent().parent().find(".domainName").text();
                    var $tld = $(this).parent().parent().find(".domainExtension").text();

                    $.ajax({
                        url:    "https://my.freenom.com/includes/domains/fn-remove.php",
                        type:   'post',
                        data:   { domain: $domain, tld: $tld },
                        crossDomain: true,
                        beforeSend: function(xhr) { xhr.withCredentials = true; },
                        xhrFields: { withCredentials: true },
                        success: function($data, $status) {
                            $($del_button).hide().next().hide().prev().prev().prev().show();
                            updateCartCount(-1);
                        },
                    });
                });

                // remove selected main domain
                $(".removeSmall").click(function() {
                    $del_button = $(this);
                    var $domain = $(this).parent().parent().parent().find(".domainName").text();
                    var $tld = $(this).parent().parent().parent().find(".domainExtension").text();

                    $.ajax({
                        url:    "https://my.freenom.com/includes/domains/fn-remove.php",
                        type:   'post',
                        data:   { domain: $domain, tld: $tld },
                        crossDomain: true,
                        beforeSend: function(xhr) { xhr.withCredentials = true; },
                        xhrFields: { withCredentials: true },
                        success: function($data, $status) {
                            $($del_button).hide().prev().hide().prev().prev().show();
                            updateCartCount(-1);
                        },
                    });
                });
            }
        });

        // show it for other TLD's - daar moeten we ook pricing voor hebben.
    });

    updateCartCount(0);
});

function updateCartCount($delta) {
    var $current = $(".nrSelectedDomains").first().text();
    var $next = parseInt($current) + parseInt($delta);

    if ($next < 1) {
        $(".nrSelectedDomains").text("0");
        $(".selectedDomains").hide();
        $(".selectedDomains").parent().find(".addToCart").hide();
        $(".fixedToCart.transparentBackground").find("div").hide();
        $(".fixedToCart.transparentBackground").hide();

    } else {
        $(".nrSelectedDomains").text($next);

        if ($next > 1) {
            $(".multipleSelectedDomains").show();
            $(".singleSelectedDomain").hide();
        } else {
            $(".multipleSelectedDomains").hide();
            $(".singleSelectedDomain").show();
        }

        $(".selectedDomains").show();
        $(".selectedDomains").parent().find(".addToCart").show();
        $(".fixedToCart.transparentBackground").find("div").show();
        $(".fixedToCart.transparentBackground").show();
    }

    if ($next > 9) {
        $(".maxCartReached").show();
        $maximum_in_cart = 1;
    } else {
        $(".maxCartReached").hide();
        $maximum_in_cart = 0;
    }
};
