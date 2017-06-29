<?php
namespace Syncro\Core;

use Syncro\Core\Settings;
use Syncro\Exception\SettingsException;
use Syncro\Exception\SyncroException;
use Syncro\Synchronizer\File\FatFileSynchronizerFactory;
use Syncro\Synchronizer\File\FileSynchronizerFactory;

class Syncro {
	
	public static function loadSettings($file, $output, $interaction) {
		$output->info("Loading settings file: $file");
		
		if(!file_exists($file)) {
			throw new SettingsException("Settings file $file doesn't exist");
		}
		
		$settings = file_get_contents($file);
		return new Settings($settings, $output, $interaction);
	}

	public static function run($project, $options, $interaction, $output) {
		$output->notice("Initializing project: $project");
		
		$settingsFile = $project . '.json';
		
		try {
			$settings = self::loadSettings($settingsFile, $output, $interaction);
			$settings->merge($options);	// Adds options to settings. Options overrides existing settings
			
			$name = $settings->getName();
			
			if($name) {
				$name = " [$name]";
			}
			
			if($settings->getFat()) {
				$factory = new FatFileSynchronizerFactory;
			} else {
				$factory = new FileSynchronizerFactory;
			}
			
			$mode = $settings->getMode();
			$synchronizer = $factory->create($mode, $settings);
			
			if(!$synchronizer) {
				throw new SettingsException("Unknown mode specified: $mode. Possible values: sync (default, two-way synchronization), mirror (one-way synchronization), random (copy random files)");
			}
			
			$output->message("Starting synchronization$name...");

			// Start the synchronization
			
			$time = $synchronizer->synchronize();
			
			$settings->setTime($time);
			
			self::saveSettings($settings, $settingsFile);

			// End of the synchronization

			$output->success("Synchronization successful!");

			return 0;
		} catch(SyncroException $ex) {
			$output->error("ERROR: " . $ex->getMessage());
			$output->error("The synchronization process has failed! The exit code is: " . $ex->getCode());

			return $ex->getCode();
		}
	}
	
	public static function saveSettings($settings, $file) {
		$output = $settings->getOutput();
		
		// We save the current time to the file. This is the time the last
		// synchronization was made.

		$output->info("Saving synchronization data to disk...");

		if(!$settings->getSimulate()) {
			$settings = $settings->getSettings();
			$bytes = file_put_contents($file, $settings);
				
			if($bytes === false) {
				$output->warn("WARNING: Could not save synchronization data, unable to open the file for writing.");
			}
		}
	}
	
}
