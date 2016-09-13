/*global $, AppData, _NAMESPACE, Application, Ajax, Notifier, Backbone, _*/

(function () {
    "use strict";

    _NAMESPACE(AppData, '_Views.Wizard', AppData._Views.Base.extend({
        _name: '_wizardView_',
        _renderIn: '#main-content',
        _renderType: 'replace',
        _controllers: ['speech'],

        _speechController: null,

        _stepNumber: 1,

        _googlePlacesAutoComplete: null,

        events: {
            'keypress #destination': '_destinationAutoComplete',
            'change #destination': '_destinationAutoComplete',
            'click #speech': '_toggleSpeech',
            'click #next-step': '_goToNextStep',
            'click #previous-step': '_goToPreviousStep'
        },

        initialize: function (options) {
            this._googlePlacesAutoComplete = google.maps.places.Autocomplete;
            this.callSuper(this, 'initialize');
        },

        afterRender: function () {
            this._updateStepVisibility();
            this._initializeSelecter();
            this.callSuper(this, 'afterRender');
        },

        _setupListeners: function () {
            this.listenTo(this._speechController, 'transcript:success', this._gotSpeech.bind(this));

            this.callSuper(this, '_setupListeners');
        },

        _initializeSelecter: function () {
            this.$(".selecter_basic").selecter({callback: this._enableNextStep.bind(this)});
        },

        _updateStepVisibility: function () {
            _.each(this.$el.find('[id*=step-]'), function (stepElement) {
                var stepNumber = this.$(stepElement).attr('id').split('-')[1];

                if (parseInt(stepNumber, 10) !== this._stepNumber) {
                    this.$(stepElement).hide();
                } else {
                    this.$(stepElement).show();
                }
            }.bind(this));

            this.$('#previous-step').attr('disabled', true);
            this.$('#next-step').attr('disabled', true);

            if (this._stepNumber > 1) {
                this.$('#previous-step').attr('disabled', false);
            }
        },

        _destinationAutoComplete: function (event) {
            var destination = this.$(event.target).val();
            var autocomplete;

            if (destination.length < 3) {
                return;
            }

            autocomplete = new this._googlePlacesAutoComplete(this.$(event.target)[0]);
            autocomplete.addListener('place_changed', function() {
                this._enableNextStep();
            }.bind(this));
        },

        _enableNextStep: function () {
            this.$('#next-step').attr('disabled', false);
        },

        _gotSpeech: function (text) {
            this.$('#destination').val(text);
            this.$('#destination').trigger('change');
            setTimeout(function () {
                this.$('#destination').focus();
            }.bind(this), 500);
        },

        _goToNextStep: function () {
            this._stepNumber += 1;
            this._updateStepVisibility();
        },

        _goToPreviousStep: function () {
            this._stepNumber -= 1;
            this._updateStepVisibility();
        },

        _toggleSpeech: function () {
            if (this._speechController.isStarted) {
                this.$('#speech').text('Speak').removeClass('btn-primary').addClass('btn-default');
                this._speechController.stop();
                return
            }

            this.$('#speech').text('Stop').removeClass('btn-default').addClass('btn-primary');
            this._speechController.start();
        }
    }));
}());