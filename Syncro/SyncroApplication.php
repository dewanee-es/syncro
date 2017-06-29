<?php
namespace Syncro;

use Symfony\Component\Console\Application;
//use Symfony\Component\Console\Output\OutputInferface;
use Symfony\Component\Console\Input\InputInterface;
use Syncro\Command\SyncCommand;

class SyncroApplication extends Application {

	//private $errorOutput;

	public function __construct() {
		parent::__construct('Syncro File Synchronizer', '0.3.4');	// 2015-01-30
	}

    /**
     * Gets the name of the command based on input.
     *
     * @param InputInterface $input The input interface
     *
     * @return string The command name
     */
    protected function getCommandName(InputInterface $input) {
        return 'syncro';
    }

    /**
     * Gets the default commands that should always be available.
     *
     * @return array An array of default Command instances
     */
    protected function getDefaultCommands() {
        // Keep the core default commands to have the HelpCommand
        // which is used when using the --help option
        $defaultCommands = parent::getDefaultCommands();

        $defaultCommands[] = new SyncCommand();

        return $defaultCommands;
    }

    /**
     * Overridden so that the application doesn't expect the command
     * name to be the first argument.
     */
    public function getDefinition() {
        $inputDefinition = parent::getDefinition();
        // clear out the normal first argument, which is the command name
        $inputDefinition->setArguments();

        return $inputDefinition;
    }
    
    /**
     * Configures the input and output instances based on the user arguments and options.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     */
    //protected function configureIO(InputInterface $input, OutputInferface $output) {
		//parent::configureIO($input, $output);

		/*if ($output instanceof ConsoleOutputInterface) {
			$this->errorOutput = $output->getErrorOutput();
		} else {
			$this->errorOutput = $output;
		}*/
	//}

	/**
	* Error handler, passes flow over the exception logger with new ErrorException.
	*/
	public function logError($num, $str, $file, $line, $context = null) {
		$this->logException(new ErrorException($str, 0, $num, $file, $line));
	}
	
	/**
	* Uncaught exception handler.
	*/
	public function logException(Exception $e) {
		if ( $debug == true ) {
			print "Exception Occured:";
			print "Type: " . get_class($e);
			print "Message: {$e->getMessage()}";
			print "File:    {$e->getFile()}";
			print "Line:    {$e->getLine()}";
		} else {
			print "Exception Occured:";
		}
	   
		$message = "Type: " . get_class($e) . "; Message: {$e->getMessage()}; File: {$e->getFile()}; Line: {$e->getLine()};";
		file_put_contents("error.log", $message . PHP_EOL, FILE_APPEND);

		exit();
	}
	
}
