(function($) {
    $.fn.showMore = function(options)  {

        //Настройки
        var settings = $.extend( {
            collapsed         : '',
            incollapsed	  : ''
        }, options);

        return this.each(function () {
            var heightNull = $(this).innerHeight();

            //Иницализация переменных
            var tags = $(this);
            var tagsHeight = $(this).innerHeight();
            var tagsBoxHeight = $(tags).children('.filter-tags__box').height();
            var tagsMoreLink = $(tags).next('.tags-more');
            var tagsMoreLinkText = $(tagsMoreLink).find('span');
            var tagsMoreLinkIcon = $(tagsMoreLink).find('i');

            if (tagsBoxHeight > tagsHeight) {
                $(tagsMoreLinkText).text(options.collapsed);
                $(tagsMoreLink).fadeIn('fast');
            } else {
                $(tags).removeClass('collapsed');
                $(tagsMoreLink).remove();
            }

            $(tagsMoreLink).click(function (e) {
                e.preventDefault();
                if ($(tagsMoreLink).hasClass('collapsed')) {
                    $(tags).css('height', tagsBoxHeight + 10);
                    $(tagsMoreLinkIcon).toggleClass('fa-caret-down fa-caret-up');
                    $(tagsMoreLinkText).text(options.incollapsed);
                    $(tagsMoreLink).toggleClass('collapsed incollapsed');
                } else {
                    $(tags).css('height', heightNull);
                    $(tagsMoreLinkIcon).toggleClass('fa-caret-down fa-caret-up');
                    $(tagsMoreLinkText).text(options.collapsed);
                    $(tagsMoreLink).toggleClass('collapsed incollapsed');
                }
            });

        });

    };
})(jQuery);