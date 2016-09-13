(function () {
    'use strict';
    var configuration,
        key;

    /**
     * @class AppData._Constants
     **/
    configuration = {
        'ServerAddress': '192.168.88.150',
        'ApplicationName': 'TravelApp'
    };

    window.AppData = {};
    window._NAMESPACE = function (ns, ns_string, value) {
        var parts = ns_string.split('.'),
            parent = ns,
            pl,
            i;

        if (parts[0] === "AppData") {
            parts = parts.slice(1);
        }

        pl = parts.length;
        for (i = 0; i < pl; i += 1) {
            if (parent[parts[i]] === undefined) {
                parent[parts[i]] = {};
            }

            if (i === (pl - 1)) {
                parent[parts[i]] = value;
            }
            parent = parent[parts[i]];
        }
        return parent;
    };

    function capitalizeString(string) {
        string = string || '';
        return string.charAt(0).toUpperCase() + string.slice(1);
    }

    for (key in configuration) {
        if (configuration.hasOwnProperty(key)) {
            window._NAMESPACE(AppData, '_Constants.' + capitalizeString(key), configuration[key]);
        }
    }


    (function () {
        'use strict';
        document.addEventListener("DOMContentLoaded", function () {
            window.DataStorage = new AppData._Helpers.DataStorage();
            window.Application = new AppData._Controllers.Application();

            window.Application.loadRouter();
            window.Application.trigger('started');
        });
    }());
}());