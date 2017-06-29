<?php
namespace Syncro\Synchronizer\File;

class FatFileSynchronizerFactory extends FileSynchronizerFactory {

	protected function createSource($source) {	
		return new FatFileSynchronizerPath($source);
	}
	
	protected function createTarget($target) {
		return new FatFileSynchronizerPath($target);
	}

}