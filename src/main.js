/**
 * SPDX-FileCopyrightText: 2019-2022 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Tag from './Tag.vue'

window.OCA.WorkflowEngine.registerOperator({
	id: 'OCA\\FilesAutomatedTagging\\Operation',
	name: t('files_automatedtagging', 'Tag a file'),
	description: t('files_automatedtagging'),
	color: 'var(--color-success)',
	operation: '',
	options: Tag,
})
