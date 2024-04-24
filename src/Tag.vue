<!--
  - @copyright Copyright (c) 2019 Julius Härtl <jus@bitgrid.net>
  - @author Julius Härtl <jus@bitgrid.net>
  - @license GNU AGPL version 3 or any later version
  -
  - SPDX-FileCopyrightText: 2019 Julius Härtl <jus@bitgrid.net>
  - SPDX-License-Identifier: AGPL-3.0-or-later
  -->

<template>
	<NcSelectTags :value="integerValue" :multiple="false" @input="emitInput" />
</template>

<script>
import NcSelectTags from '@nextcloud/vue/dist/Components/NcSelectTags.js'

export default {
	name: 'Tag',
	components: { NcSelectTags },
	props: {
		value: {
			type: String,
			default: '',
		},
	},
	computed: {
		integerValue() {
			return parseInt(this.value)
		},
	},
	methods: {
		emitInput(value) {
			this.$emit('input', '' + value)
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
