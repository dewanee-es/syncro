<?php
namespace Syncro\Synchronizer\File\Comparer;

// FilenameMatcher
//
//  Features:
//  - Absolute and relative matching: (relative) *.php => file.php, /path/to/file.php     (absolute) /*.php => file.php
//  - Star for directory name: /*/file => /dir/file
//  - Double star for any path: /**/file => /dir/file, /dir/subdir/file
//  - Character group: *.jp[eg] => file.jpg, file.jpe     *.z[0-9] => file.z0, file.z1, file.z2, ...
//  - Character group negation: *.jp[!g] => file.jpa, file.jpb
//  - Choices: file.(bmp|png) => file.bmp, file.png
//  ~~- Negate choices: file.!(bmp|png) => file.jpg, file.pdf~~	NO IMPLEMENTED (A workaround is capture negate choices as groups and test after preg_match that no group has been captured)
//  - Case sensitive: =*.bmp => file.bmp (default)     ~*.bmp => file.bmp, file.BMP
//
class FilenameMatcher {

	private $patterns;

	public function __construct($patterns, $caseless = false) {
		if(!is_array($patterns)) {
			$patterns = [$patterns];
		}
		
		$this->patterns = $this->compile($patterns, $caseless);
	}
	
	private function compile($patterns, $caseless) {
		$compiled = array();
        $transforms = array(
			'\*\*'  => '.*',
            '\*'    => '[^/]*',
            '\?'    => '[^/]',
            '\[\!'  => '[^',
            '\['    => '[',
            '\]'    => ']',
            '\\\\'  => '\\',
            //'\!('   => '(',
			//'\('    => '(?:',	
			'\('    => '(',
			'\)'    => ')',
			'\-'    => '-',
			'\|'    => '|'
        );
		
		foreach($patterns as $pattern) {
			if($pattern{0} == '=') {
				$modifiers = null;
				$pattern = substr($pattern, 1);
			} else if($pattern{0} == '~') {
				$modifiers = 'i';
				$pattern = substr($pattern, 1);
			} else {
				$modifiers = ($caseless ? 'i' : '');
			}
			
			if($pattern{0} == '/') {
				$start = '^';
				$pattern = substr($pattern, 1);
			} else {
				$start = '^(?:[^/]*/)*';
			}
			
			$regex = '#'
			         . $start
					 . strtr(preg_quote($pattern, '#'), $transforms)	// This needs to be fixed: Iterate over the pattern string applying transforms and quoting characters as needed
					 . '$#'
					 . $modifiers;
			$compiled[] = $regex;
		}
       
        return $compiled;
	}
	
	public function match($filename) {
		$match = false;
	
		foreach($this->patterns as $pattern) {
			if(preg_match($pattern, $filename)) {
				$match = true;
				break;
			}
		}
		
		return $match;
	}
	
	public static function matchFilename($filename, $patterns) {
		$matcher = new self($patterns);
		return $matcher->match($filename);
	}

}
