/*global AppData, Backbone, _NAMESPACE, Application*/

(function () {
    "use strict";

    _NAMESPACE(AppData, '_Routers.Application', Backbone.Router.extend({
        name: '_applicationRouter_',
        _activeView: null,

        routes: {
            '': 'index'
        },

        index: function () {
            this._renderView('Index');
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