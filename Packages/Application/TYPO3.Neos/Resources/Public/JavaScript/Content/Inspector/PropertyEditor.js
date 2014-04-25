/**
 * Property Editor
 */
define(
[
	'emberjs',
	'Library/jquery-with-dependencies',
	'Shared/Configuration'
], function(
	Ember,
	$,
	Configuration
) {
	return Ember.ContainerView.extend({
		propertyDefinition: null,
		value: null,
		isModified: false,
		hasValidationErrors: false,
		classNameBindings: ['isModified:neos-modified', 'hasValidationErrors:neos-error'],

		_valueDidChange: function() {
			if (this.get('inspector').isPropertyModified(this.get('propertyDefinition.key')) === true) {
				this.set('isModified', true);
			} else {
				this.set('isModified', false);
			}
		// we cannot just observe "value", but we also need to refresh when all properties changed (i.e. the user pressed apply).
		// Else, the change indication is not working correctly.
		}.observes('value', 'inspector.cleanProperties'),

		_validationErrorsDidChange: function() {
			if (this.get('isDestroyed') === true) {
				return;
			}
			var property = this.get('propertyDefinition.key'),
				validationErrors = this.get('inspector.validationErrors.' + property) || [];
			if (validationErrors.length > 0) {
				this.set('hasValidationErrors', true);
				this.$().tooltip('destroy').tooltip({
					animation: false,
					placement: 'bottom',
					title: validationErrors[0],
					trigger: 'manual'
				}).tooltip('show');
			} else {
				this.set('hasValidationErrors', false);
				this.$().tooltip('destroy');
			}
		},

		init: function() {
			this._super();
			this._loadView();
		},

		_loadView: function() {
			var that = this,
				propertyDefinition = this.get('propertyDefinition'),
				typeDefinition = Configuration.get('UserInterface.inspector.dataTypes.' + propertyDefinition.type),
				editorDefinition,
				globalEditorOptions,
				editor;

			Ember.bind(this, 'value', 'inspector.nodeProperties.' + propertyDefinition.key);
			Ember.assert('Type defaults for "' + propertyDefinition.type + '" not found!', !!typeDefinition);

			var editorOptions = $.extend(
				{
					elementId: propertyDefinition.elementId,
					inspectorBinding: this.inspectorBinding,
					valueBinding: 'inspector.nodeProperties.' + propertyDefinition.key
				},
				Configuration.get('UserInterface.inspector.editors.' + typeDefinition.editor + '.editorOptions') || {},
				typeDefinition.editorOptions || {},
				Ember.get(propertyDefinition, 'ui.inspector.editorOptions') || {}
			);

			editor = Ember.get(propertyDefinition, 'ui.inspector.editor');
			if (!editor) {
				editor = typeDefinition.editor;
			}

			if (editor.indexOf('Content/Inspector/Editors/') === 0) {
				// Rename old editor names for backwards compatibility
				editor = editor.replace('Content/Inspector/Editors/', 'TYPO3.Neos/Inspector/Editors/');
			}

			require([editor], function(editorClass) {
				Ember.run(function() {
					if (!that.isDestroyed) {
						// It might happen that the editor was deselected before the require() call completed; so we
						// need to check again whether the view has been destroyed in the meantime.
						var editor = editorClass.create(editorOptions);
						that.set('currentView', editor);
					}
				});
			});
		},

		didInsertElement: function() {
			this.get('inspector.validationErrors').addObserver(this.get('propertyDefinition.key'), this, '_validationErrorsDidChange');
		}
	});
});