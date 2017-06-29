<?php
namespace Syncro\Synchronizer;

/**
 * The SynchronizerSource interface
 *
 */
interface SynchronizerSourceInterface
{
    /**
     * @param $settings
     */
    public function __construct($settings);
}
