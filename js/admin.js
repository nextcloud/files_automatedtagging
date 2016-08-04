/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

(function() {
	OCA.FilesAutomatedTagging = OCA.FilesAutomatedTagging || {};

	/**
	 * @class OCA.FilesAutomatedTagging.Operation
	 */
	OCA.FilesAutomatedTagging.Operation =
		OCA.WorkflowEngine.Operation.extend({
			defaults: {
				'class': 'OCA\\FilesAutomatedTagging\\Operation',
				'name': '',
				'checks': [],
				'operation': ''
			}
		});

	/**
	 * @class OCA.FilesAutomatedTagging.OperationsCollection
	 *
	 * collection for all configurated operations
	 */
	OCA.FilesAutomatedTagging.OperationsCollection =
		OCA.WorkflowEngine.OperationsCollection.extend({
			model: OCA.FilesAutomatedTagging.Operation
		});

	/**
	 * @class OCA.FilesAutomatedTagging.OperationView
	 *
	 * this creates the view for a single operation
	 */
	OCA.FilesAutomatedTagging.OperationView =
		OCA.WorkflowEngine.OperationView.extend({
			model: OCA.FilesAutomatedTagging.Operation,
			render: function() {
				var $el = OCA.WorkflowEngine.OperationView.prototype.render.apply(this);

				$el.find('input.operation-operation')
					.css('width', '400px')
					.select2({
					allowClear: true,
					multiple: true,
					placeholder: t('files_automatedtagging', 'Tags to assign…'),
					query: _.debounce(function(query) {
						query.callback({
							results: OC.SystemTags.collection.filterByName(query.term)
						});
					}, 100, true),
					initSelection: function(element, callback) {
						var val = $(element).val().trim();
						if (val) {
							var tagIds = val.split(',').sort(),
								tags = [];

							_.each(tagIds, function (tagId) {
								var tag = OC.SystemTags.collection.get(tagId);
								if (!_.isUndefined(tag)) {
									tags.push(tag.toJSON());
								}
							});

							callback(tags);

						}
					},
					formatResult: function (tag) {
						return OC.SystemTags.getDescriptiveTag(tag);
					},
					formatSelection: function (tagId) {
						var tag = OC.SystemTags.collection.get(tagId);
						return OC.SystemTags.getDescriptiveTag(tag)[0].outerHTML;
					},
					escapeMarkup: function(m) {
						return m;
					}
				});
			}
		});

	/**
	 * @class OCA.FilesAutomatedTagging.OperationsView
	 *
	 * this creates the view for configured operations
	 */
	OCA.FilesAutomatedTagging.OperationsView =
		OCA.WorkflowEngine.OperationsView.extend({
			initialize: function() {
				OCA.WorkflowEngine.OperationsView.prototype.initialize.apply(this, [
					'OCA\\FilesAutomatedTagging\\Operation'
				]);
			},
			renderOperation: function(operation) {
				var subView = new OCA.FilesAutomatedTagging.OperationView({
						model: operation
					});

				OCA.WorkflowEngine.OperationsView.prototype.renderOperation.apply(this, [
					subView
				]);
			}
		});
})();


$(document).ready(function() {
	OC.SystemTags.collection.fetch({
		success: function() {
			new OCA.FilesAutomatedTagging.OperationsView({
				el: '#files_automatedtagging .rules',
				collection: new OCA.FilesAutomatedTagging.OperationsCollection()
			});
		}
	});
});
