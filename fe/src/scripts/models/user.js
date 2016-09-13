(function () {
    "use strict";

    _NAMESPACE(AppData, '_Models.User', Backbone.Model.extend({

        defaults: {
            name: 'BLa',
            age: 1,
            likes: "",
            birthday: "",
            country: "Romania",
            gender: "male"
        },

        initialize: function(){
            alert("Welcome to this world");
        },

        save: function() {

        },

        fetch: function() {

        }
    }));
}());