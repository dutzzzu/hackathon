(function () {
    "use strict";

    _NAMESPACE(AppData, '_Views.Spots', AppData._Views.Base.extend({
        _name: '_spotsView_',
        _renderIn: '#main-content',
        _renderType: 'replace',
        selectedCount: 0,
        
        _selections: [],

        events: {
            'click .swiper-slide': '_selectSlide',
            'click #fixedbutton': '_seeResult'
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
                centeredSlides: false,
                spaceBetween: 15
            });
        },

        _selectSlide: function (event) {
            if ($(event.currentTarget).attr('data-selected') === 'true') {
                $(event.currentTarget).attr('data-selected', false);
                if (this.selectedCount > 0) this.selectedCount--;
                this._selections.splice(this._selections.indexOf($(event.currentTarget).find('.spot-title').text().trim()), 1);
            } else {
                $(event.currentTarget).attr('data-selected', true);
                this.selectedCount++;
                this._selections.push($(event.currentTarget).find('.spot-title').text().trim());
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
        },

        _seeResult: function () {
            Application.userModel.set('yetAnotherShittyStupidVar', this._selections);
            Application.navigate('results');
        }
    }));
}());