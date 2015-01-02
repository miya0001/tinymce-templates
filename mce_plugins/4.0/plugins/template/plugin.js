/**
 * plugin.js
 *
 * Released under LGPL License.
 *
 * License: http://www.tinymce.com/license
 * Contributing: http://www.tinymce.com/contributing
 */

/*global tinymce:true */

tinymce.PluginManager.add('template', function(editor) {
	var each = tinymce.each;

	function createTemplateList(callback) {
		return function() {
			var templateList = editor.settings.templates;

			if (typeof(templateList) == "string") {
				tinymce.util.XHR.send({
					url: templateList,
					success: function(text) {
						callback(tinymce.util.JSON.parse(text));
					}
				});
			} else {
				callback(templateList);
			}
		};
	}

	function showDialog(templateList) {
		var win, values = [], templateHtml, id, is_shortcode, label;

		if (!templateList || templateList.length === 0) {
			editor.windowManager.alert('No templates defined');
			return;
		}

		tinymce.each(templateList, function(template) {
			values.push({
				selected: !values.length,
				text: template.title,
				value: {
					id: template.id,
					url: template.url,
					content: template.content,
					description: template.description,
					is_shortcode: template.is_shortcode
				}
			});
		});

		function onSelectTemplate(e) {
			var value = e.control.value();

			function insertIframeHtml(html) {
				if (html.indexOf('<html>') == -1) {
					var contentCssLinks = '';

					tinymce.each(editor.contentCSS, function(url) {
						contentCssLinks += '<link type="text/css" rel="stylesheet" href="' + editor.documentBaseURI.toAbsolute(url) + '">';
					});

					html = (
						'<!DOCTYPE html>' +
						'<html>' +
							'<head>' +
								contentCssLinks +
							'</head>' +
							'<body>' +
								html +
							'</body>' +
						'</html>'
					);
				}

				var doc = win.find('iframe')[0].getEl().contentWindow.document;
				doc.open();
				doc.write(html);
				doc.close();
			}

			if (value.url) {
				tinymce.util.XHR.send({
					url: value.url,
					success: function(html) {
						templateHtml = html;
						insertIframeHtml(templateHtml);
						id = value.id;
						is_shortcode = value.is_shortcode;
					}
				});
			} else {
				templateHtml = value.content;
				insertIframeHtml(templateHtml);
			}

			if (value.is_shortcode) {
				label = "Note: The template will be inserted as shortcode.";
			} else {
				label = '\u00a0';
			}

			win.find('#description')[0].text(e.control.value().description);
			win.find('#is_shortcode')[0].text(label);
		}

		win = editor.windowManager.open({
			title: 'Insert template',
			layout: 'flex',
			direction: 'column',
			align: 'stretch',
			padding: 15,
			spacing: 10,

			items: [
				{type: 'form', flex: 0, padding: 0, items: [
					{type: 'container', label: 'Templates', items: {
						type: 'listbox', label: 'Templates', name: 'template', values: values, onselect: onSelectTemplate
					}}
				]},
				{type: 'label', name: 'description', label: 'Description', text: '\u00a0'},
				{type: 'iframe', flex: 1, border: 1},
				{type: 'label', name: 'is_shortcode', label: '', text: '\u00a0'},
			],

			onsubmit: function() {
				insertTemplate(false, templateHtml, id, is_shortcode);
			},

			width: editor.getParam('template_popup_width', 600),
			height: editor.getParam('template_popup_height', 500)
		});

		win.find('listbox')[0].fire('select');
	}

	function insertTemplate(ui, html, id, is_shortcode) {
		if (is_shortcode) {
			editor.execCommand('mceInsertContent', false, '<p>[template id="'+id+'"]</p>');
			editor.addVisual();
			return;
		}

		var el, n, dom = editor.dom, sel = editor.selection.getContent();
		el = dom.create('div', null, html);

		editor.execCommand('mceInsertContent', false, el.innerHTML);
		editor.addVisual();
	}

	editor.addCommand('mceInsertTemplate', insertTemplate);

	editor.addButton('template', {
		title: 'Insert template',
		onclick: createTemplateList(showDialog)
	});

	editor.addMenuItem('template', {
		text: 'Insert template',
		onclick: createTemplateList(showDialog),
		context: 'insert'
	});
});
