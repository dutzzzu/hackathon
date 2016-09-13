/*global AppData, Backbone, _NAMESPACE, localStorage*/

(function () {
    "use strict";

    _NAMESPACE(AppData, '_Helpers.DataStorage', Backbone.Model.extend({
        _name: '_dataStorageHelper_',

        storageKeys: {
            'users': AppData._Constants.ApplicationName + '.users.key'
        },

        setItem: function (key, value) {
            localStorage.setItem(key, value);
        },

        getItem: function (key) {
            return localStorage.getItem(key);
        },

        setObject: function (storageKey, key, object) {
            var storageObject = JSON.parse(this.getItem(this.storageKeys[storageKey]) || '{}');

            storageObject[key] = object;
            this.setItem(this.storageKeys[storageKey], JSON.stringify(storageObject));
        },

        getObject: function (storageKey, key) {
            var storageObject = JSON.parse(this.getItem(this.storageKeys[storageKey]) || '{}');
            return key ? storageObject[key] : storageObject;
        },
        
        deleteObject: function (storageKey, key) {
            var storageObject = JSON.parse(this.getItem(this.storageKeys[storageKey]) || '{}');
            delete storageObject[key];
            this.setItem(this.storageKeys[storageKey], JSON.stringify(storageObject));
        },

        searchObjectWhere: function (storageKey, key, value) {
            var storageObject = JSON.parse(this.getItem(this.storageKeys[storageKey]) || '{}'),
                attribute,
                response = {};

            for (attribute in storageObject) {
                if (storageObject.hasOwnProperty(attribute)) {
                    if (storageObject[attribute][key] === value) {
                        response =  storageObject[attribute];
                        break;
                    }
                }
            }

            return response;
        }
    }));
}());