(function () {
    "use strict";

    _NAMESPACE(AppData, '_Views.Results', AppData._Views.Base.extend({
        _name: '_resultsView_',
        _renderIn: '#main-content',
        _renderType: 'replace',

        initialize: function(options) {
            var results = options.attributes.results;

            var a = moment(Application.userModel.attributes.wizard.step3);
            var b = moment(Application.userModel.attributes.wizard.step4);
            var days = Math.abs(a.diff(b, 'days')) + 1;

            var otherResults = [];

            for (var i = 0; i < days; i += 1) {
                otherResults.push({
                    day: (i + 1),
                    places: results.slice((i * Math.ceil(results.length / days)), Math.ceil(results.length / days) * (i + 1))
                });
            }
            this.callSuper(this, 'initialize', [{results: otherResults}]);
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