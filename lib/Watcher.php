<?php

namespace OCA\FilesAutomatedTagging;

use OCP\Files\File;
use OCP\Files\Node;

class Watcher {

	/** @var Operation */
	protected $operation;

	public function __construct(Operation $operation) {
		$this->operation = $operation;
	}


	public function fileCreated(Node $node) {
		if (!($node instanceof File)) {
			return;
		}

		if (!$this->isTaggingPath($node)) {
			return;
		}

		$this->operation->checkOperations($node->getStorage(), $node->getId(), $node->getInternalPath());
	}

	protected function isTaggingPath(File $file) {
		$path = $file->getPath();

		if (substr_count($path, '/') < 3) {
			return false;
		}

		// '', admin, 'files', 'path/to/file.txt'
		list(,, $folder,) = explode('/', $path, 4);

		return $folder === 'files';
	}
}
