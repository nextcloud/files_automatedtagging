<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcSelectTags v-model="integerValue" :multiple="false" @input="emitInput" />
</template>

<script>
import NcSelectTags from '@nextcloud/vue/dist/Components/NcSelectTags.js'

export default {
	name: 'Tag',
	components: { NcSelectTags },
	props: {
		modelValue: {
			type: String,
			default: '',
		},
	},
	emits: ['update:model-value'],
	computed: {
		integerValue() {
			const val = parseInt(this.modelValue)
			return isNaN(val) ? -1 : val
		},
	},
	methods: {
		emitInput(value) {
			this.$emit('update:model-value', '' + value)
		},
	},
}
</script>

<style scoped>
	.multiselect {
		width: 100%;
		max-width: 300px;
		margin: auto;
		text-align: center;
	}
</style>
