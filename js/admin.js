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
	 * @class OCA.FilesAutomatedTagging.OperationsView
	 *
	 * this creates the view for configured operations
	 */
	OCA.FilesAutomatedTagging.OperationsView =
		OCA.WorkflowEngine.OperationsView.extend({
			initialize: function() {
				this._initialize('OCA\\FilesAutomatedTagging\\Operation');
			}
		});
})();


$(document).ready(function() {
	new OCA.FilesAutomatedTagging.OperationsView({
		el: '#files_automatedtagging .rules',
		collection: new OCA.FilesAutomatedTagging.OperationsCollection()
	});
});
