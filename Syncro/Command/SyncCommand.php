<?php
namespace Syncro\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Syncro\Core\Settings;
use Syncro\Core\Syncro;
use Syncro\Interaction\InputInteraction;
use Syncro\Output\ConsoleOutputAdapter;

class SyncCommand extends Command {
	
	/*
	 <info>OPTIONS</info>

	  <comment>--ansi</comment>     Force turning on ANSI output coloring
	  <comment>-h</comment>
	  <comment>--help</comment>     Displays this help message
	  <comment>--no-ansi</comment>  Turn off ANSI output coloring
	  <comment>-n</comment>
	  <comment>--no-interaction</comment>  Do not ask any interactive question
	  <comment>-q</comment>
	  <comment>--quiet</comment>    Supress output
	  <comment>-s</comment>
	  <comment>--simulate</comment> Enable simulation mode. Don't make any change to files.
				 Overrides value of project file.
	  <comment>-v</comment>
	  <comment>--verbose</comment>  Displays more verbose messages.
				 Can optionally take a value between 1 (default) and 3 to output
				 even more verbose messages:			
				 <comment>-v</comment>   or <comment>--verbose</comment>   Notice messages
				 <comment>-vv</comment>  or <comment>--verbose=2</comment> Info messages
				 <comment>-vvv</comment> or <comment>--verbose=3</comment> Debug messages
	  <comment>-V</comment>
	  <comment>--version</comment>  Displays application name and version
	*/
	
    protected function configure() {
        $this
            ->setName('syncro')
            ->setDescription('Synchronize a project')
            ->addArgument(
                'project',
                InputArgument::REQUIRED,
                'Project name'
            )
			->addOption(
				'simulate',
				's',
				InputOption::VALUE_NONE,
				'Enable simulation mode. Don\'t make any change to files (Overrides value of project file).'
			)
			->addOption(
				'auto',
				'a',
				InputOption::VALUE_NONE,
				'Enable auto mode. When a conflict is encountered the newer file is copied to the other path without asking anything. WARNING: Be careful with this option, you may loose files. To run a synchronization and skip conflicts without asking use the -n option.'
			)
			->setHelp(<<<EOF
The <info>Syncro</info> tool makes it easy to synchronize files between two
paths. It supports three types of synchronization:

  * <info>sync</info>: (default) Conciliate changes applying changes in both paths.
  * <info>mirror</info>:         Apply changes only to target path to create a replica
                    of source filesystem.
  * <info>random</info>:         Copy some random files or folders from source path to
                    target path

Each synchronization is defined by a project file which contains the settings.
The project file has <comment>.json</comment> extension.

To run a synchronization project use the project filename without extension:

  <info>syncro project</info>
  
<comment>Project file:</comment>

 The project file has the following JSON format:

   <info>{</info>
     <info>"name":     string,</info>       (optional)
                 Name of the project
     <info>"mode":     string,</info>       (optional, defaults: sync)
                 Synchronization mode. Possible values are:
                 * <info>mirror</info>: Apply changes from source to target
                 * <info>sync</info>:   Apply changes in both directions
                 * <info>random</info>: Copy random files from source to target
     <info>"source":   string/object,</info> (required)
                 Source path to be synchronized:
                 * <info>string</info>: The source path
                 * <info>object</info>:
                   <info>{</info>
                     <info>"name": string,</info> (optional)
                             The path name
                     <info>"path": string</info>  (required)
                             The source path
                   <info>}</info>
                 A path must contain some special characters:
                 * <info>~</info>: It's replaced by user home path
                 * <info>?</info>: Matches one character (except directory separator)
                 * <info>*</info>: Matches any number of characters (except directory
                      separator)
                 The wildcard characters <info>?</info> and <info>*</info> are evaluated. If there is
                 more than one path matching path value an error will be shown.
     <info>"target":   string/object,</info> (required)
                 Target path to be synchronized (same format as source)
     <info>"simulate": boolean,</info>      (optional, defaults: false)
                 This option will cause the program not to take any action
     <info>"preserve": boolean,</info>      (optional, defaults: false)
                 If set to true doesn't remove deleted files
     <info>"checksum": boolean,</info>      (optional, defaults: false)
                 Set this setting to true to make the script compare files
                 using a checksum
     <info>"fat":      boolean,</info>      (optional, defaults: false)
                 When source or target (or both) is a FAT filesystem enable
                 this option to handle file modification time better
     <info>"hidden":   boolean,</info>      (optional, defaults: true)
                 If set to false it will cause the script to skip all hidden
                 files and folders
     <info>"excludes": string array,</info>  (optional, defaults: empty)
                 Paths and filenames excluded from the synchronization.
                 The path could be absolute if it starts with a / character or
                 relative if not:
                   <info>*.txt</info>:  Exclude text files in any directory
                   <info>/*.txt</info>: Exclude text files in root directory
                 Some special characters can be used:
                 * <info>?</info>: Matches one character (except directory separator)
                 * <info>*</info>: Matches any number of characters (except directory
                      separator)
                 * <info>**</info>: Matches any number of characters (including
                      directory separator)
                 * <info>[abc]</info>: Matches a character of: a, b or c
                      A range may be specified: <info>[a-z]</info>, <info>[0-9]</info>
                      Multiple ranges: <info>[a-Z0-9]</info>
                 * <info>[!abc]</info>: Matches any character except: a, b or c
                      A range may be specified to exclude those characters
                 * <info>(a|b)</info>: Matches either a or b
                 * <info>\ </info>: Escapes the next character
                 To force a case (in)sensitive match precede the path with:
                 * <info>=</info>: Case sensitive
                 * <info>~</info>: Case insensitive
     <info>"debug":    boolean,</info>      (optional, defaults: false)
                 Enable debug mode. The program prints every action it takes to
                 stdout
     <info>"time":     int,</info>            (optional, defaults: 0)
                 Last synchronization time in unix time format. This value is
                 automatically updated by the synchronizer so setting manually
                 this value is discouraged.
                 
                       <comment>--Random mode settings--</comment>
     <info>"size":     int/string</info>      (optional, defaults: 100 files)
                 Size of files to copy: <info>number</info> <info>suffix</info> <info>type</info>
                 * <info>suffix</info> (optional, defaults: none): <info>B</info> for bytes,
                   <info>KB</info> for kilobytes, <info>MB</info> for megabytes and <info>GB</info> for gigabytes.
                   If none, number is the number of files/folders to copy
                 * <info>type</info> (optional, defaults: files): <info>files</info> or <info>folders</info>
                 Examples:
                 * <info>100</info>: Copy 100 files (same as <info>100 files</info>)
                 * <info>100 folders</info>: Copy 100 folders
                 * <info>100 MB</info>: Copy up to 100 MB of files (same as <info>100 MB files</info>)
                 * <info>100 MB folders</info>: Copy up to 100 MB of folders
   <info>}</info>
EOF
			)
        ;
    }
	
    protected function execute(InputInterface $input, OutputInterface $output) {
        $project = $input->getArgument('project');
        $helper = $this->getHelper('question');
        $console = new ConsoleOutputAdapter($output);
        $interaction = new InputInteraction($helper, $input, $output);
        $settings = new Settings;
        $simulate = $input->getOption('simulate');
        $auto = $input->getOption('auto');
		
        if($simulate) {
          $settings->setSimulate(true);
        }
		
        if($auto) {
          $interaction->setAuto(true);
        }
        
        return Syncro::run($project, $settings, $interaction, $console);
    }
	
}
