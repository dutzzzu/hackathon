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
        },

        saveToApi: function () {
            var data = {
                fb_id: this.get('id'),
                name: this.get('name'),
                age: (this.get('age').min || 0) + '-' + (this.get('age').max || 99),
                // likes: this.get('likes'),
                // birthday: this.get('birthday'),
                gender: this.get('gender'),
                // hometown: this.get('hometown'),
                destination_lat: this.get('wizard').step1.api.lat,
                destination_lng: this.get('wizard').step1.api.lng,
                start_date: this.get('wizard').step3,
                end_date: this.get('wizard').step4,
                accomodation_lat: this.get('wizard').step5.api.lat,
                accomodation_lng: this.get('wizard').step5.api.lng,
                // interests: this.get('wizard').step6
            };

            Ajax.makePost('http://hackathon.dev/user', data, this._saveSuccess.bind(this), this._saveError.bind(this));
        },

        _saveSuccess: function () {

        },

        _saveError: function () {

        }
    }));
}());