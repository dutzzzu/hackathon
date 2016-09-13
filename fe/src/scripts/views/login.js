(function () {
    "use strict";

    _NAMESPACE(AppData, '_Views.Login', AppData._Views.Base.extend({
        _name: '_loginView_',
        _renderIn: '#main-content',
        _renderType: 'replace',

        initialize: function() {
            // Verify model
            var userModel = DataStorage.getObject("users",null);

            if (userModel && false) {
                Application.navigate("wizard");
            }
            else {
                // Check FB status
                this.callSuper(this, 'initialize');
            }
        },

        afterRender: function () {
            if (window.FB) {
                checkFBStatus();
            } else {
                setTimeout(checkFBStatus, 2000);
            }

            this.callSuper(this, 'afterRender');
        }
    }));
}());