<?php
namespace Syncro\Synchronizer;

use Syncro\Exception\SettingsException;

abstract class AbstractSynchronizer implements SynchronizerInterface {
	
	protected $settings;
	
	protected $source;
	protected $target;
	
	public function __construct($settings) {
		$this->settings = $settings;
	}	
	
    /**
     * @param \Syncro\Synchronizer\SynchronizerSourceInterface $source
     */
    public function setSource(SynchronizerSourceInterface $source) {
		$this->source = $source;
	}

    /**
     * @param \Syncro\Synchronizer\SynchronizerTargetInterface $target
     */
    public function setTarget(SynchronizerTargetInterface $target) {
		$this->target = $target;
	}
	
}
