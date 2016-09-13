/*global _NAMESPACE, window, Ajax, AppData, Backbone, DataStorage, Notifier*/

(function () {
    "use strict";
    
    _NAMESPACE(AppData, '_Controllers.Application', Backbone.Model.extend({
        _name: '_applicationController_',
        _applicationRouter: null,
        _userModel: null,

        getCurrentPage: function () {
            var fragment = Backbone.history.getFragment(),
                index1 = fragment.indexOf('/'),
                index2 = fragment.indexOf('?');

            return fragment.substring(0, Math.min(index1, index2)) || fragment;
        },

        loadRouter: function () {
            this._applicationRouter = this._applicationRouter || new AppData._Routers.Application();
            Backbone.history.start();
        },

        loadUser: function () {
            this.userModel = this.userModel || new AppData._Models.User();
            this.userModel.checkForExistingUser();
        },

        navigate: function (fragment) {
            this._applicationRouter.navigate(fragment, true);
        },

        purgeUser: function () {
            DataStorage.deleteObject('users', this.userModel.get('sessionId'));
            this.userModel.set(this.userModel.defaults);
            this.userModel.trigger('change');
        }
    }));
}());