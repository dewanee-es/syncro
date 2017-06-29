<?php
namespace Syncro\Synchronizer\File;

use Syncro\Synchronizer\SynchronizerFactory;

class FileSynchronizerFactory extends SynchronizerFactory {

	protected function createSynchronizer($mode, $settings) {
		switch($mode) {
		case 'mirror':
			$synchronizer = new MirrorFileSynchronizer($settings);
			break;
		case 'sync':
			$synchronizer = new SyncFileSynchronizer($settings);
			break;
		case 'random':
			$synchronizer = new RandomFileSynchronizer($settings);
			break;
		default:
			$synchronizer = null;
		}
		
		return $synchronizer;
	}
	
	protected function createSource($source) {	
		return new FileSynchronizerPath($source);
	}
	
	protected function createTarget($target) {
		return new FileSynchronizerPath($target);
	}
	
}
