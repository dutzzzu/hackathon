(function () {
    "use strict";

    _NAMESPACE(AppData, '_Views.Base', Backbone.View.extend({
        _name: '',
        _controllers: '',
        _renderIn: '',
        _renderType: 'replace',

        el: '',
        template: '',
        templateData: {},

        initialize: function (templateData) {
            debugger;
            this.templateData = templateData || this.templateData;
            this.on('fetchTemplate:success', this.beforeRender);
            Ajax.fetchTemplate(this._getTemplateName(), this._fetchTemplateSuccess.bind(this));
            this._setupControllers();
        },

        beforeRender: function () {
            this._ensureElement();
            this.render();
        },

        render: function (templateData) {
            this.templateData = templateData || this.templateData;
            this.$el.html(this.template(this.templateData));
            this.afterRender();
        },

        afterRender: function () {
            if (this._renderIn) {
                if (this._renderType === 'append') {
                    $(this._renderIn).append(this.$el);
                } else if (this._renderType === 'replace') {
                    $(this._renderIn).html(this.$el);
                }
            }

            this.undelegateEvents();
            //noinspection JSUnresolvedVariable
            this.delegateEvents(this.events);

            this.$el.addClass('backbone-view');
            this.trigger('render:success');
        },

        callSuper: function (context, fn, args) {
            AppData._Views.Base.prototype[fn].apply(context, args);
        },

        close: function () {
            this.$el.empty();
            this.remove();
            this.unbind();
        },

        _fetchTemplateSuccess: function (templateData) {
            this.template = _.template($(templateData).html());
            this.trigger('fetchTemplate:success');
            this._setupListeners();
        },

        _getTemplateName: function () {
            return this._name.replace(/_/gi, '').replace(/([A-Z])/g, '-$1').toLowerCase();
        },

        _setupListeners: function () {
            $.noop();
        },

        _setupControllers: function () {
            AppData._Nucleus.setupControllers.call(this, arguments);
        }
    }));
}());/**
 * Created by alex on 9/13/16.
 */
