<?php
namespace Syncro\Synchronizer;

/**
 * The SynchronizerTarget interface
 *
 */
interface SynchronizerTargetInterface
{
    /**
     * @param $settings
     */
    public function __construct($settings);
}
