(function () {
    "use strict";

    _NAMESPACE(AppData, '_Views.Results', AppData._Views.Base.extend({
        _name: '_resultsView_',
        _renderIn: '#main-content',
        _renderType: 'replace',

        initialize: function() {
            this.callSuper(this, 'initialize');
            this.callSuper(this, 'initialize', [{days: {
                day: 1
            }}]);
        },

        afterRender: function () {
            this.callSuper(this, 'afterRender');
        }
    }));
}());