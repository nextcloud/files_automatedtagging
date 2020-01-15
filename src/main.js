import Tag from './Tag'

window.OCA.WorkflowEngine.registerOperator({
	id: 'OCA\\FilesAutomatedTagging\\Operation',
	name: t('files_automatedtagging', 'Tag a file'),
	description: t('files_automatedtagging'),
	color: 'var(--color-success)',
	operation: '',
	options: Tag,
})
