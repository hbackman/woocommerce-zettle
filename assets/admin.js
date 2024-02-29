jQuery(function ($) {
  $("#wc_zettle_install_webhooks").on("click", function (e) {
    e.preventDefault();

    $.post(ajaxurl, {
      action: "wc_zettle_install_webhooks",
    }, function () {
      window.location.reload();
    });
  });
});