(function () {
    "use strict";

    _NAMESPACE(AppData, '_Views.Results', AppData._Views.Base.extend({
        _name: '_resultsView_',
        _renderIn: '#main-content',
        _renderType: 'replace',

        initialize: function() {
            this.callSuper(this, 'initialize', [{days: [{
                day: 1,
                spots: ['spot1', 'spot2', 'spot3', 'spot4']
            }, {
                day: 2,
                spots: ['spot5', 'spot7', 'spot6', 'spot8']
            }]
            }]);
        },

        afterRender: function () {
            this.callSuper(this, 'afterRender');

            new google.maps.Map(document.getElementById('map'), {
                center: {lat: -34.397, lng: 150.644},
                zoom: 8
            });
        }
    }));
}());