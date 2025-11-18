/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineCustomElement } from 'vue'

import Tag from './Tag.vue'

const AutoTaggerComponent = defineCustomElement(Tag, { shadowRoot: false })
const customElementId = 'oca-files_automatedtagging-operation-tag'
window.customElements.define(customElementId, AutoTaggerComponent)

window.OCA.WorkflowEngine.registerOperator({
	id: 'OCA\\FilesAutomatedTagging\\Operation',
	name: t('files_automatedtagging', 'Tag a file'),
	description: t('files_automatedtagging'),
	color: 'var(--color-success)',
	operation: '',
	element: customElementId,
})
