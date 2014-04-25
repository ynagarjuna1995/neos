define(
[
	'Library/jquery-with-dependencies',
	'Shared/Configuration',
	'Content/Model/NodeSelection'
],
function(
	$,
	Configuration,
	NodeSelection
) {
	if (!window.T3.isContentModule) {
		return;
	}

	if (Configuration.get('Schema') === undefined) {
		// schema not yet loaded, we need to initialize aloha
		Configuration.addObserver('Schema', function() {
			initAloha();
		});
	} else {
		initAloha();
	}

	function initAloha() {

		var nodeTypes = Configuration.get('Schema');

		var nodeSettings = {};
		$.each(nodeTypes, function(nodeTypeName, nodeType) {
			if (nodeType.properties && typeof nodeType.properties == 'object') {
				$.each(nodeType.properties, function(propertyName, property) {
					$.each(['table', 'link', 'list', 'alignment', 'format'], function(i, mode) {
						if (property.ui && property.ui.aloha && property.ui.aloha[mode]) {
							var selector = '[typeof="typo3:' + nodeTypeName + '"]' +
								 ' [property="typo3:' + propertyName + '"]';
							nodeSettings[mode] = nodeSettings[mode] ? nodeSettings[mode] : {};
							nodeSettings[mode][selector] = property.ui.aloha[mode];
						}
					});
				});
			}
		});

		var Aloha = window.Aloha = window.Aloha || {__shouldInit: true};

		Aloha.settings = {
			logLevels: {'error': true, 'warn': true, 'info': false, 'debug': false},
			errorhandling : false,
			sidebar: {
				disabled: true
			},
			plugins: {
				load: [
					'common/ui',
					'common/link',
					'common/table',
					'common/format',
					'common/list',
					//'image/image-plugin',
					//'highlighteditables/highlighteditables-plugin',
					'common/dom-to-xhtml',
					'common/contenthandler',
					//'common/characterpicker',
					'common/commands',
					'common/block',
					'common/align',
					//'common/abbr',
					//'common/horizontalruler',
					'common/paste',
					// some extra plugins
					//'toc/toc-plugin',
					//'extra/cite',
					//'flag-icons/flag-icons-plugin',
					//'numerated-headers/numerated-headers-plugin',
					'extra/formatlesspaste',
					//'linkbrowser/linkbrowser-plugin',
					//'imagebrowser/imagebrowser-plugin',
					//'extra/ribbon',
					//'extra/wai-lang',
					//'extra/headerids',
					//'metaview/metaview-plugin',
					//'extra/listenforcer'

					// 'neosAloha/neosintegration',
					// 'neosAloha/neos-links'
					'neosAloha/ajax-repository'
				].join(','),
				block: {
					sidebarAttributeEditor: false
				},
				/**
				 * FIXME:
				 * Currently the filtering of options for alignment and format don't work correctly,
				 * meaning that all options are still shown. This seems to be an Aloha bug and needs
				 * more research.
				 */
				table: { config: [], editables: nodeSettings['table'] },
				link: { config: [], editables: nodeSettings['link'] },
				list: { config: [], editables: nodeSettings['list'] },
				alignment: { config: [], editables: nodeSettings['alignment'] },
				format: { config: ['b', 'i', 'u', 'sub', 'sup', 'p', 'h1', 'h2', 'h3', 'pre', 'removeFormat'], editables: nodeSettings['format'] }
			},
			toolbar: {
				tabs: [
					{
						label: 'tab.format.label',
						// The "format" tab is shown in the top-menu, the remaining tabs are shown
						// in the inspector.
						components: [
							[ 'formatBlock', 'bold', 'italic', 'underline', 'subscript', 'superscript', 'formatLink', 'editLink', 'createTable', 'formatAbbr', 'formatNumeratedHeaders', 'toggleDragDrop', 'toggleMetaView', 'wailang', 'toggleFormatlessPaste', 'alignLeft', 'alignCenter', 'alignRight', 'alignJustify', 'orderedList', 'unorderedList', 'indentList', 'outdentList', 'colorPicker']
						]
					},
					// we completely disable the "insert" tab, as the needed features should reside in the "format" tab.
					{
						label: "tab.insert.label",
						showOn: function() {
							return false;
						}
					},
					// we completely disable the "link" tab, as this should all be in the "format" tab.
					{
						label: 'tab.link.label',
						showOn: function() {
							return false;
						}
					}

				]
			},

			// Fine-tune some Aloha-SmartContentChange settings, making the whole system feel more responsive.
			smartContentChange: {
				idle: 500,
				delay: 150
			},
			bundles: {
				// Path for custom bundle relative from require.js path
				//neosAloha: '/_Resources/Static/Packages/TYPO3.Neos/JavaScript/alohaplugins/'
			},

			baseUrl: alohaBaseUrl,

			// Pass on our jQuery instance to Aloha to prevent double loading of jQuery
			jQuery: $,
			predefinedModules: {
				'jqueryui': $.ui,
				NeosNodeSelection: NodeSelection
			},
			requireConfig: {
				map: {
					'../../JavaScript/LibraryExtensions/UiAlohaPlugin/button': {
						'originalButton': 'ui/button'
					},
					'*': {
						'ui/ui-plugin': '../../JavaScript/LibraryExtensions/UiAlohaPlugin/ui-plugin',
						'ui/multiSplit': '../../JavaScript/LibraryExtensions/UiAlohaPlugin/multiSplit',
						'ui/button': '../../JavaScript/LibraryExtensions/UiAlohaPlugin/button'
					}
				},
				paths: {
					'ajax-repository': '../../JavaScript/Content/LibraryExtensions/AlohaLinkPlugin/lib'
				}
			},
			// Basic sanitation of content
			contentHandler: {
				insertHtml: ['word', 'generic', 'oembed', 'sanitize'],
				initEditable: ['sanitize'],
				getContents: ['blockelement', 'sanitize', 'basic'],
				sanitize: 'relaxed', // relaxed, restricted, basic
				allows: {
					elements: [
						'a', 'abbr', 'b', 'blockquote', 'br', 'caption', 'cite', 'code', 'col',
						'colgroup', 'dd', 'del', 'dl', 'dt', 'em', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
						'i', 'img', 'li', 'ol', 'p', 'pre', 'q', 'small', 'strike', 'strong',
						'sub', 'sup', 'table', 'tbody', 'td', 'tfoot', 'th', 'thead', 'tr', 'u',
						'ul', 'span', 'hr', 'object', 'div'
					],
					attributes: {
						'a': ['href', 'data-gentics-aloha-repository', 'data-gentics-aloha-object-id', 'data-ajax-repository-temporary-data'],
						'blockquote': ['cite'],
						'q': ['cite'],
						'div': ['id', 'class', 'style'],
						'h1': ['style'],
						'h2': ['style'],
						'h3': ['style'],
						'h4': ['style'],
						'h5': ['style'],
						'h6': ['style'],
						'p': ['class', 'style', 'id'],
						'td': ['abbr', 'axis', 'colSpan', 'rowSpan', 'colspan', 'rowspan', 'style'],
						'th': ['abbr', 'axis', 'colSpan', 'rowSpan', 'colspan', 'rowspan', 'scope']
					},

					protocols: {
						'a': {'href': ['ftp', 'http', 'https', 'mailto', '__relative__', 'node']},
						'blockquote': {'cite': ['http', 'https', '__relative__']},
						'q': {'cite': ['http', 'https', '__relative__']}
					}
				}
			}
		};

		require(
			{
				context: 'aloha',
				baseUrl: alohaBaseUrl
			},
			['aloha']
		)
	}
});
