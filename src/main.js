/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import wrap from '@vue/web-component-wrapper'
import Vue from 'vue'

import Tag from './Tag.vue'

const AutoTaggerComponent = wrap(Vue, Tag)
const customElementId = 'oca-files_automatedtagging-operation-tag'

window.customElements.define(customElementId, AutoTaggerComponent)

// In Vue 2, wrap doesn't support disabling shadow :(
// Disable with a hack
Object.defineProperty(AutoTaggerComponent.prototype, 'attachShadow', { value() { return this } })
Object.defineProperty(AutoTaggerComponent.prototype, 'shadowRoot', { get() { return this } })

window.OCA.WorkflowEngine.registerOperator({
	id: 'OCA\\FilesAutomatedTagging\\Operation',
	name: t('files_automatedtagging', 'Tag a file'),
	description: t('files_automatedtagging'),
	color: 'var(--color-success)',
	operation: '',
	element: customElementId,
})
