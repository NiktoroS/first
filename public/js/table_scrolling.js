(function($) {
    $.fn.fixMe = function() {
        return this.each(function() {
            var $this = $(this), $t_fixed;
            function init() {
                $this.wrap('<div class="container" />');
                $t_fixed = $this.clone();
                $t_fixed.find("tbody").remove().end().addClass("fixed").insertBefore($this);
                resizeFixed();
            }
            function resizeFixed() {
                $t_fixed.find("th").each(function(index) {
                    $(this).css("width", $this.find("th").eq(index).outerWidth(true) - 1.0 + "px");
//                    $(this).css("width", $(window).width() - 1.0 + "px");
                });
            }
            function scrollFixed() {
                var offset = $(this).scrollTop(),
                tableOffsetTop = $this.offset().top,
                tableOffsetBottom = tableOffsetTop + $this.height() - $this.find("thead").height();
                if (offset < tableOffsetTop || offset > tableOffsetBottom) {
                    $t_fixed.hide();
                } else if (offset >= tableOffsetTop && offset <= tableOffsetBottom && $t_fixed.is(":hidden")) {
                    $t_fixed.show();
                }
            }
            $(window).resize(resizeFixed);
            $(window).scroll(scrollFixed);
            init();
        });
    };
})
(jQuery);

$(document).ready(function() {
    $("table.scrolling").fixMe();
    $(".up").click(function() {
        $('html, body').animate({
            scrollTop: 0
        }, 2000);
    });
});