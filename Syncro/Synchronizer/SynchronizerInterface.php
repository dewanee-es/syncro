<?php
namespace Syncro\Synchronizer;

/**
 * The Synchronizer interface
 *
 */
interface SynchronizerInterface {

    public function synchronize();

    /**
     * @param \Syncro\Synchronizer\SynchronizerSourceInterface $source
     */
    public function setSource(SynchronizerSourceInterface $source);

    /**
     * @param \Syncro\Synchronizer\SynchronizerTargetInterface $target
     */
    public function setTarget(SynchronizerTargetInterface $target);

}
