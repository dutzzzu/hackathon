/*global $, AppData, _NAMESPACE, Application, Ajax, Notifier, Backbone, _*/

(function () {
    "use strict";

    _NAMESPACE(AppData, '_Views.Wizard', AppData._Views.Base.extend({
        _name: '_wizardView_',
        _renderIn: '#main-content',
        _renderType: 'replace',

        _stepNumber: 1
    }));
}());