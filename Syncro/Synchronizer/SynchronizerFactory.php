<?php
namespace Syncro\Synchronizer;

abstract class SynchronizerFactory {

	const MODE_SYNC = 'sync';
	const MODE_MIRROR = 'mirror';
	const MODE_RANDOM = 'random';
	
	public function getModes() {
		return [self::MODE_SYNC, self::MODE_MIRROR, self::MODE_RANDOM];
	}

	public function create($mode, $settings) {
		if(in_array($mode, $this->getModes())) {
			$synchronizer = $this->createSynchronizer($mode, $settings);
			
			if($settings->getSource()) {
				$source = $this->createSource($settings->getSource());
				$synchronizer->setSource($source);
			}
				
			if($settings->getTarget()) {
				$target = $this->createTarget($settings->getTarget());				
				$synchronizer->setTarget($target);
			}

			return $synchronizer;
			
		} else {
		
			return null;
			
		}
	}
	
	abstract protected function createSynchronizer($mode, $settings);
	
	abstract protected function createSource($settings);
	
	abstract protected function createTarget($settings);
	
}