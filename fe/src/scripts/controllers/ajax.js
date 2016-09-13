(function () {
    "use strict";

    _NAMESPACE(AppData, '_Controllers.Ajax', Backbone.Model.extend({
        _name: '_ajaxController_',

        headers: {
        },

        fetchTemplate: function (templateName, successCb, errorCb) {
            if (templateName) {
                this._callAjax(
                    'GET',
                    '/hackathon/fe/public/templates/' + templateName + '.html',
                    null,
                    successCb,
                    errorCb,
                    {dataType: 'html'}
                );
            }
        },

        makeGet: function (url, data, successCb, errorCb, options) {
            url = AppData._Constants.ServerURL + url;
            this._callAjax('GET', url, data, successCb, errorCb, options);
        },

        makePost: function (url, data, successCb, errorCb, options) {
            url = AppData._Constants.ServerURL + url;
            this._callAjax('POST', url, data, successCb, errorCb, options);
        },

        makePut: function (url, data, successCb, errorCb, options) {
            url = AppData._Constants.ServerURL + url;
            this._callAjax('PUT', url, data, successCb, errorCb, options);
        },

        makePatch: function (url, data, successCb, errorCb, options) {
            url = AppData._Constants.ServerURL + url;
            this._callAjax('PATCH', url, data, successCb, errorCb, options);
        },

        makeDelete: function (url, data, successCb, errorCb, options) {
            url = AppData._Constants.ServerURL + url;
            this._callAjax('DELETE', url, data, successCb, errorCb, options);
        },

        _callAjax: function (method, url, data, successCb, errorCb, options) {
            var userSessionId = window.Application ? Application.userModel.get('sessionId') : '',
                headers = window.Application ? _.extend(this.headers, {'session_id': userSessionId}) : this.headers;

            options = options || {};

            $.ajax(_.extend({
                    'url': url,
                    'method': method,
                    'cache': false,
                    'data': data,
                    'dataType': 'json',
                    'headers': headers
                }, options))
                .success(successCb || $.noop)
                .error(errorCb || $.noop);
        }
    }));
}());