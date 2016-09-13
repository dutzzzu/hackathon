(function () {
    "use strict";

    _NAMESPACE(AppData, '_Models.User', Backbone.Model.extend({

        defaults: {
            id: "",
            name: "",
            age: "",
            likes: "",
            birthday: "",
            gender: "",
            hometown: ""
        },

        initialize: function(){
        },

        save: function() {
            DataStorage.setObject(DataStorage.storageKeys.users, "travel", this.toJSON());
        },

        fetch: function() {
            return DataStorage.getObject(DataStorage.storageKeys.users);
        }
    }));
}());