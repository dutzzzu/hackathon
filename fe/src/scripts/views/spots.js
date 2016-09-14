(function () {
    "use strict";

    _NAMESPACE(AppData, '_Views.Spots', AppData._Views.Base.extend({
        _name: '_spotsView_',
        _renderIn: '#main-content',
        _renderType: 'replace',
        selectedCount: 0,

        events: {
            'click .swiper-slide': '_selectSlide'
        },

        initialize: function(options) {
            this.templateData.spots = options.attributes["spots"];
            this.selectedCount = 0;
            this.callSuper(this, 'initialize',[this.templateData]);
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
        },

        _selectSlide: function (event) {
            if ($(event.currentTarget).attr('data-selected') === 'true') {
                $(event.currentTarget).attr('data-selected', false);
                if (this.selectedCount > 0) this.selectedCount--;
            } else {
                $(event.currentTarget).attr('data-selected', true);
                this.selectedCount++;
            }

            this._displayRouteButton();
        },

        _displayRouteButton: function() {
            var button = $('#fixedbutton');
            if (this.selectedCount === 1) {
                button.removeClass("hidden");
                button.addClass("visible");
            }
            else if (this.selectedCount === 0) {
                button.removeClass("visible");
                button.addClass("hidden");
            }
        }
    }));
}());