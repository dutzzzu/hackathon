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
            hometown: "",
            wizard: {
                step1: '',
                step2: '',
                step3: '',
                step4: '',
                step5: '',
                step6: ''
            }
        },

        initialize: function(){
            this.set(this.fetch());
        },

        save: function() {
            DataStorage.setObject(DataStorage.storageKeys.users, "travel", this.toJSON());
        },

        fetch: function() {
            return DataStorage.getObject(DataStorage.storageKeys.users, "travel");
        }
    }));
}());