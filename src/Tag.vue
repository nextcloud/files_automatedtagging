<!--
  - @copyright Copyright (c) 2019 Julius Härtl <jus@bitgrid.net>
  -
  - @author Julius Härtl <jus@bitgrid.net>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<MultiselectTags :value="integerValue" :multiple="false" @input="emitInput" />
</template>

<script>
import MultiselectTags from '@nextcloud/vue/dist/Components/MultiselectTags.js'

export default {
	name: 'Tag',
	components: { MultiselectTags },
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
