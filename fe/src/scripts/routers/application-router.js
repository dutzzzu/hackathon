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
            var spots = Application.shittyVariableToStoreDataIn;
            debugger;
            this._renderView('Spots', { spots: spots });
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