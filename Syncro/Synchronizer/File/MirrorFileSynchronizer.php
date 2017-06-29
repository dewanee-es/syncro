<?php
namespace Syncro\Synchronizer\File;

use Syncro\Output\Output;
use Syncro\Synchronizer\File\Comparer\FilesComparerIterator;
use Syncro\Synchronizer\File\Comparer\OneWayFilesComparer;

class MirrorFileSynchronizer extends FileSynchronizer {

	public function getFilesComparerIterator($source, $target) {
		return new FilesComparerIterator(new OneWayFilesComparer($source, $target, $this->settings));
	}

	public function getMode() {
		return "mirror";
	}

	protected function syncPaths($source, $target, $root = false) {
		if($root) {
			if($this->output->getVerbosity() < Output::NOTICE) {
				$this->output->message("Mirroring " . $source->getName() . " '" . $source->getPath() . "' ----> " . $target->getName() . " '" . $target->getPath() . "'");
			} else {
				$this->output->notice("Mirroring " . $source->getName() . " ----> " . $target->getName());
			}
		} else {
			$this->output->debug("Mirroring " . $source->getRelativeName() . " ----> " . $target->getRelativeName());
		}
		
		// Calls the default path synchronization
		parent::syncPaths($source, $target);
	}
		
}
