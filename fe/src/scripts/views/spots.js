(function () {
    "use strict";

    _NAMESPACE(AppData, '_Views.Spots', AppData._Views.Base.extend({
        _name: '_spotsView_',
        _renderIn: '#main-content',
        _renderType: 'replace',

        initialize: function() {
            this.callSuper(this, 'initialize');
        },

        afterRender: function () {
            $('#myCarousel').carousel();
            this.callSuper(this, 'afterRender');
        }
    }));
}());