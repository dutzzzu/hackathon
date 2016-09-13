(function () {
    "use strict";

    _NAMESPACE(AppData, '_Controllers.Speech', Backbone.Model.extend({
        _name: '_speechController_',
        _speechRecognition: new webkitSpeechRecognition(),

        isStarted: false,

        initialize: function () {
            this._speechRecognition.continuous = true;
            this._speechRecognition.interimResults = true;

            this._speechRecognition.onstart = this._onStart.bind(this);
            this._speechRecognition.onresult = this._onResult.bind(this);
            this._speechRecognition.onerror = this._onError.bind(this);
            this._speechRecognition.onend = this._onEnd.bind(this);
        },

        start: function () {
            this._speechRecognition.start();
        },

        stop: function () {
            this._speechRecognition.stop();
        },

        _onStart: function () {
            this.isStarted = true;
        },

        _onResult: function (event) {
            var interim_transcript = '';
            var final_transcript = '';

            for (var i = event.resultIndex; i < event.results.length; i += 1) {
                if (event.results[i].isFinal) {
                    final_transcript += event.results[i][0].transcript;
                } else {
                    interim_transcript += event.results[i][0].transcript;
                }
            }

            if (final_transcript) {
                this.trigger('transcript:success', final_transcript);
            }
        },

        _onError: function () {
        },

        _onEnd: function () {
            this.isStarted = false;
        }
    }));
}());