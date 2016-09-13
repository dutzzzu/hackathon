(function () {
    "use strict";

    var _generatedControllers;

    function getControllerName(controllerName) {
        return controllerName.charAt(0).toUpperCase() + controllerName.slice(1);
    }

    _generatedControllers = {};

    _NAMESPACE(AppData, '_Nucleus', {
        
        setupControllers: function () {
            var controller,
                controllerName,
                generatedKey;

            this._controllers = this._controllers || [];

            if (this._controllers.length && typeof this._controllers === 'object') {
                for (controller in this._controllers) {
                    if (this._controllers.hasOwnProperty(controller)) {
                        controllerName = this._controllers[controller];
                        generatedKey = '_' + controllerName + 'Controller';

                        _generatedControllers[generatedKey] = _generatedControllers[generatedKey] || new AppData._Controllers[getControllerName(controllerName)]();
                        this[generatedKey] = _generatedControllers[generatedKey];
                    }
                }
            }
        }
    });
}());