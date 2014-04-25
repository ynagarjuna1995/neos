/**
 * Inspector
 */
define(
[
	'emberjs',
	'Library/jquery-with-dependencies',
	'./InspectorController',
	'./Breadcrumb',
	'./Section',
	'./UnappliedChangesDialog',
	'../Components/Button',
	'text!./InspectorView.html'
], function(
	Ember,
	$,
	InspectorController,
	Breadcrumb,
	Section,
	UnappliedChangesDialog,
	Button,
	template
) {
	/**
	 * The Inspector is displayed on the right side of the page.
	 *
	 * Furthermore, it contains *Editors*
	 */
	return Ember.View.extend({
		elementId: 'neos-inspector',

		template: Ember.Handlebars.compile(template),
		Button: Button,
		Section: Section,
		Breadcrumb: Breadcrumb,

		controller: InspectorController,

		/**
		 * When we are in edit mode, the click protection layer is intercepting
		 * every click outside the Inspector.
		 */
		$clickProtectionLayer: null,

		onInspectorModeChanged: function() {
			if (this.get('controller.inspectorMode') === true) {
				$('body').addClass('neos-inspector-panel-open');
			} else {
				$('body').removeClass('neos-inspector-panel-open');
			}
		}.observes('controller.inspectorMode').on('init'),

		/**
		 * When pressing Enter inside a property, we apply and leave the edit mode
		 */
		keyDown: function(event) {
			if (event.keyCode === 13) {
				this.get('controller').apply();
				return false;
			}
		},

		/**
		 * When the editors have been modified, we add / remove the click
		 * protection layer.
		 */
		_modifiedDidChange: function() {
			var zIndex,
				that = this;
			if (this.get('controller.modified')) {
				zIndex = this.$().css('z-index') - 1;
				this.$clickProtectionLayer = $('<div id="neos-inspector-clickprotection" />').css({'z-index': zIndex});
				this.$clickProtectionLayer.click(function(e) {
					e.stopPropagation();
					that._showUnappliedDialog();
				});
				$('#neos-application').append(this.$clickProtectionLayer);
			} else {
				this.$clickProtectionLayer.remove();
			}
		}.observes('controller.modified'),

		/**
		 * When clicking the click protection, we show a dialog
		 */
		_showUnappliedDialog: function() {
			UnappliedChangesDialog.create().appendTo('#neos-application');
		}
	});
});