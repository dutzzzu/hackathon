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
                    '/public/templates/' + templateName + '.html',
                    null,
                    successCb,
                    errorCb,
                    {dataType: 'html'}
                );
            }
        },

        makeGet: function (url, data, successCb, errorCb, options) {
            this._callAjax('GET', url, data, successCb, errorCb, options);
        },

        makePost: function (url, data, successCb, errorCb, options) {
            this._callAjax('POST', url, data, successCb, errorCb, options);
        },

        makePut: function (url, data, successCb, errorCb, options) {
            this._callAjax('PUT', url, data, successCb, errorCb, options);
        },

        makePatch: function (url, data, successCb, errorCb, options) {
            this._callAjax('PATCH', url, data, successCb, errorCb, options);
        },

        makeDelete: function (url, data, successCb, errorCb, options) {
            this._callAjax('DELETE', url, data, successCb, errorCb, options);
        },

        _callAjax: function (method, url, data, successCb, errorCb, options) {
            var headers = window.Application ? _.extend(this.headers, {}) : this.headers;

            options = options || {};

            $.ajax(_.extend({
                    'url': url,
                    'method': method,
                    'crossDomain': true,
                    'contentType': 'application/json',
                    'data': JSON.stringify(data),
                    'dataType': 'json',
                    'headers': headers
                }, options))
                .success(successCb || $.noop)
                .error(errorCb || $.noop);
        }
    }));
}());