/*global AppData, Backbone, _NAMESPACE, Application*/

(function () {
    "use strict";

    _NAMESPACE(AppData, '_Routers.Application', Backbone.Router.extend({
        name: '_applicationRouter_',
        _activeView: null,

        routes: {
            '': 'index',
            'wizard': 'wizard',
            'wizard/:step': 'wizard',
            'login': 'login',
            'pick-spots': 'pickSpots',
            'results': 'results'
        },

        index: function () {
            Application.navigate('login');
        },

        wizard: function (step) {
            step = step || 1;
            this._renderView('Wizard', {step: step});
        },

        login: function() {
            this._renderView('Login');
        },

        pickSpots: function() {

            debugger;
            var spots = {
                historical: [ 'asdasd', 'dasdasd', 'asdasd', 'dasdasd','asdasd', 'dasdasd'],
                shopping: [ 'asdasda', 'dasdasd'],
                nightlife: ['dasdasd','dasdasd','asdasd', 'dasdasd'],
                sightseeing: ['sada','dasda', 'asdasd', 'dasdasd']
            };


            this._renderView('Spots', { spots: spots });

            //Ajax.makeGet("http://www.hackathon.dev/places?pagesizeparameter=1", null, function (response) {
            //    this._renderView('Spots', {
            //        spots: response.spots
            //    });
            //}, function (error) {
            //    console.log(error);
            //});
        },

        results: function () {
            this._renderView('Results');
        },

        _renderView: function (viewName, attributes) {
            viewName = viewName || 'Error404';
            if (this._activeView instanceof Backbone.View) {
                this._activeView.close();
            }

            this._activeView = AppData._Views[viewName] ? new AppData._Views[viewName]({attributes: attributes}) : new AppData._Views.Error404();
        }
    }));
}());