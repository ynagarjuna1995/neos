/**
 * NodeSelection
 *
 * Contains the currently selected node proxy with parents.
 *
 * This model is the one most listened to, as when the node selection changes, the UI
 * is responding to that.
 */
define(
[
	'emberjs',
	'Library/jquery-with-dependencies',
	'Library/underscore',
	'vie/instance',
	'vie/entity'
], function(
	Ember,
	$,
	_,
	vie,
	EntityWrapper
) {
	return Ember.Object.extend({
		_nodes: [],

		/**
		 * if FALSE, no aloha tabs are currently shown. if ARRAY, contains
		 * the references to the "TabInSecondaryContainer" objects from Aloha,
		 * which should be displayed in the Neos inspector.
		 */
		currentlyShownSecondaryAlohaTabs: false,

		/**
		 * Identity map "RDFa Subject" -> "Entity" instances
		 */
		_entitiesBySubject: {},

		nodes: function() {
			if (this.get('currentlyShownSecondaryAlohaTabs')) {
				// we show secondary aloha tabs currently, so we *replace* the inspector contents.
				// we build up a custom-tailored "node type" which can be rendered using the normal
				// Inspector UI.
				var currentlyShownSecondaryAlohaTabs = this.get('currentlyShownSecondaryAlohaTabs'),
					nodeTypeGroups = {},
					nodeTypeGroupCount = 0,
					nodeTypeProperties = {},
					nodeTypeLabel = null;

				currentlyShownSecondaryAlohaTabs.forEach(function(tab) {
					// by convention, the last tab is the most specific one; so this is the one we show here.
					nodeTypeLabel = tab._settings.label;

					nodeTypeGroups['group' + nodeTypeGroupCount] = {
						position: nodeTypeGroupCount,
						label: tab._settings.label
					};
					$.each(tab._elemBySlot, function(componentKey) {
						var componentObject = tab._getSlottedComponents()[componentKey];
						if (!componentObject || !componentObject.isVisible()) {
							return;
						}

						// here, the actual mapping between aloha components and Neos editors happens.
						var editorClass = null;
						if (componentObject.buttonElement && componentObject._checked !== undefined) {
							editorClass = 'AlohaToggleButtonEditor';
						} else if (componentObject.buttonElement) {
							editorClass = 'AlohaButtonEditor';
						} else {
							editorClass = 'AlohaNonDefinedEditor';
						}

						nodeTypeProperties[componentKey] = {
							type: 'string', // Dummy, is ignored
							ui: {
								inspector: {
									group: 'group' + nodeTypeGroupCount,
									editor: 'TYPO3.Neos/Inspector/Editors/Aloha/' + editorClass,
									editorOptions: {
										alohaComponent: componentObject
									}
								}
							}
						};
					});
					nodeTypeGroupCount++;
				});

				var nodesWithVirtualNode = [];
				nodesWithVirtualNode.addObjects(this.get('_nodes'));

				nodesWithVirtualNode.addObject(Ember.Object.create({
					nodeType: 'ALOHA-CONTROL',
					$element: nodesWithVirtualNode.get('lastObject.$element'),
					_enableTransactionalInspector: false,
					attributes: Ember.Object.create({
					}),
					nodeTypeSchema: Ember.Object.create({
						properties: nodeTypeProperties,
						ui: {
							label: nodeTypeLabel,
							inspector: {
								groups: nodeTypeGroups
							}
						}
					})
				}));
				return nodesWithVirtualNode;
			} else {
				return this.get('_nodes');
			}
		}.property('_nodes.@each', 'currentlyShownSecondaryAlohaTabs.@each'),

		/**
		 *
		 */
		initialize: function() {
			vie.entities.reset();
			vie.load({element: $('body')}).from('rdfa').execute();
			this._entitiesBySubject = {};

			// Update the selection on initialize, such that the current Page is added to the breadcrumb
			this.updateSelection();
		},

		/**
		 * Update the selection from a selected content element.
		 * If we have a node activated, we add the CSS class "neos-contentelement-selected"
		 * to the body so that we can modify the appearance of the content element editing handles.
		 */
		updateSelection: function($element) {
			var activeClass = 'neos-contentelement-active';
			// Do not update the selection if the element is already selected
			if ($element && $element.hasClass(activeClass)) {
				return;
			}

			var nodes = [],
				that = this;

			// Remove active class from all previously active nodes (content elements and contentcollection)
			$('.' + activeClass).removeClass(activeClass);

			// TODO Check if we need that
			if (this._updating) {
				return;
			}
			this._updating = true;
			this.set('currentlyShownSecondaryAlohaTabs', false);

			if ($element !== undefined) {
				// Add active class to selected content element
				$element.addClass(activeClass);

				this._addNodeByElement(nodes, $element);
				$element.parents('.neos-contentelement[about], .neos-contentcollection[about]').each(function() {
					that._addNodeByElement(nodes, this);
				});

				// Add class to body that we have a content element selected
				$('body').addClass('neos-contentelement-selected');
			} else {
				$('body').removeClass('neos-contentelement-selected');
			}

			// add page node
			if (!$element || !$element.is('#neos-page-metainformation')) {
				this._addNodeByElement(nodes, $('#neos-page-metainformation'));
			}

			nodes = nodes.reverse();
			if (nodes.length > 0 && _.last(nodes) !== _.last(this.get('_nodes'))) {
				this.set('_nodes', nodes);
			}

			this._updating = false;
		},

		/**
		 *
		 * @param nodes
		 * @param $element
		 */
		_addNodeByElement: function(nodes, $element) {
			if ($element !== undefined) {
				var nodeProxy = this._createEntityWrapper($element);
				if (nodeProxy) {
					var entity = nodeProxy.get('_vieEntity'),
						properties = nodeProxy.get('nodeTypeSchema.properties'),
						propertyValidators = {};
					_.each(properties, function(propertyDefinition, propertyName) {
						if (typeof propertyDefinition.validation !== 'undefined') {
							var validators = [];
							_.each(propertyDefinition.validation, function(validatorOptions, validator) {
								var validatorClassName = validator;
								if (validatorClassName.indexOf('/') === -1) {
									validatorClassName = 'TYPO3.Neos/Validation/' + validator.charAt(0).toUpperCase() + validator.slice(1) + 'Validator';
								}

								require([validatorClassName], function(validatorClass) {
									Ember.run(function() {
										validators.push(validatorClass.create({options: validatorOptions}));
									});
								});
							});
							propertyValidators[propertyName] = validators;
						}
					});
					if (!_.isEmpty(propertyValidators)) {
						nodeProxy.set('validators', propertyValidators);
					}
					_.extend(entity, {
						validate: function(attrs, opts) {
							if (opts && opts.validate === false || typeof nodeProxy.get('validators') === 'undefined') {
								return;
							}

							var that = this,
								results = [];
							_.each(EntityWrapper.extractAttributesFromVieEntity(this, attrs), function(value, propertyName) {
								_.each(that.validateAttribute(propertyName, value), function(error) {
									results.push({
										property: propertyName,
										message: error
									});
								});
							});
							if (_.isEmpty(results)) {
								return;
							}
							return results;
						},
						validateAttribute: function(propertyName, value) {
							var results = [],
								propertyValidators = nodeProxy.get('validators.' + propertyName);
							_.each(propertyValidators, function(validator) {
								var result = validator.validate(value);
								if (result.length > 0) {
									results.push(result[0]);
								}
							});
							return results;
						}
					});
					nodes.push(nodeProxy);
				}
			}
		},

		_createEntityWrapper: function($element) {
			var subject = vie.service('rdfa').getElementSubject($element);

			if (!this._entitiesBySubject[subject]) {
				var entity = vie.entities.get(subject);
				if (entity === undefined) {
					return;
				}

				this._entitiesBySubject[subject] = EntityWrapper.create({
					_vieEntity: entity
				});
			}

			return this._entitiesBySubject[subject];
		},

		selectedNode: function() {
			var nodes = this.get('nodes');
			return nodes.length > 0 ? _.last(nodes) : null;
		}.property('nodes'),

		selectedNodeSchema: function() {
			var selectedNode = this.get('selectedNode');
			if (!selectedNode) {
				return null;
			}
			return selectedNode.get('nodeTypeSchema');
		}.property('selectedNode'),

		selectNode: function(node) {
			this.set('currentlyShownSecondaryAlohaTabs', false);
			this.updateSelection(node.get('$element'));
		}
	}).create();
});