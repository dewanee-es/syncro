<?php
namespace Syncro\Synchronizer\File;

use Syncro\Output\Output;
use Syncro\Synchronizer\File\Comparer\FilesComparerIterator;
use Syncro\Synchronizer\File\Comparer\TwoWayFilesComparer;

class SyncFileSynchronizer extends FileSynchronizer {

	public function getFilesComparerIterator($source, $target) {
		return new FilesComparerIterator(new TwoWayFilesComparer($source, $target, $this->settings));
	}

	public function getMode() {
		return "synchronization";
	}

	protected function syncPaths($source, $target, $root = false) {
		if($root) {
			if($this->output->getVerbosity() < Output::NOTICE) {
				$this->output->message("Synchronizing " . $source->getName() . " '" . $source->getPath() . "' <---> " . $target->getName() . " '" . $target->getPath() . "'");
			} else {
				$this->output->notice("Synchronizing " . $source->getName() . " <---> " . $target->getName());
			}
		} else {
			$this->output->debug("Synchronizing " . $source->getRelativeName() . " <---> " . $target->getRelativeName());
		}
		
		// Calls the default path synchronization
		parent::syncPaths($source, $target);
	}
	
}
