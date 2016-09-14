(function () {
    "use strict";

    _NAMESPACE(AppData, '_Views.Spots', AppData._Views.Base.extend({
        _name: '_spotsView_',
        _renderIn: '#main-content',
        _renderType: 'replace',

        events: {
            'click .swiper-slide': '_selectSlide'
        },

        afterRender: function () {
            this.callSuper(this, 'afterRender');

            var slidesPerView = $(window).outerWidth() > 400 ? 2 : 1;
            slidesPerView = $(window).outerWidth() > 600 ? 3 : slidesPerView;

            var swiper = new Swiper('.swiper-container', {
                nextButton: '.swiper-button-next',
                prevButton: '.swiper-button-prev',
                slidesPerView: slidesPerView,
                centeredSlides: true,
                spaceBetween: 15
            });

            swiper.appendSlide([
                '<div class="swiper-slide">Slide ' + (11) + '</div>',
                '<div class="swiper-slide">Slide ' + (12) + '</div>'
            ]);

        },

        _selectSlide: function (event) {
            if ($(event.currentTarget).attr('data-selected') === 'true') {
                $(event.currentTarget).attr('data-selected', false);
            } else {
                $(event.currentTarget).attr('data-selected', true);
            }
        }
    }));
}());