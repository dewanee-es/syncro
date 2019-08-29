#!/usr/bin/php
<?php
/*
random_copy.py
refresh-tunes.sh
fichero con includes y excludes
copiar albumes
copiar canciones
OJO: ahora no copia directorios que tengan mp3 si tiene subdirectorios (por ejemplo con imágenes)
OJO: ahora parte los albumes CD1 CD2
symlinks
OJO: no sobrescribe. si existe directorio destino no lo copia
listar todos los archivos/directorios y coger aleatorios de ahí
POO
No copiar archivos que no sean mp3
integrar con syncro
test: movil 500 canciones
test: coche 10 gb
java
Skipping folder $source (< 5 songs)
*/
$doc = <<<DOC
Random Music for syncro

Usage:
    random.sh <src> <dst> <size> [--delete]
	
Arguments:
	<src>     Source folder
	<dst>     Destination folder
	<size>    Number of folders or size (with B, KB, MB, GB suffix)

Options:
    --delete  Remove and recreate destination folder before copying files.

DOC;


$min_songs_in_album = 5;


function regular_file($item) {
	return strpos($item, '.') !== false;
}


function get_folders($directory) {
	$folders = array();

    foreach(scandir($directory) as $filename) {
        if (in_array($filename, array('.', '..'))) {
				continue;
		}
        if(is_dir(os_path_join($directory, $filename))) {
			$folders[] = $filename;
		}
	}
	
	return $folders;
}


function remove_and_create_folder($directory) {
	rmtree($directory);
	if(!file_exists($directory)) {
        mkdir($directory, 0775, true);
    }
}


function rmtree($directory) {
	if (is_dir($directory))
	{
		foreach (scandir($directory) as $name)
		{
			if (in_array($name, array('.', '..')))
			{
				continue;
			}
			$subpath = $directory.DIRECTORY_SEPARATOR.$name;
			rmtree($subpath);
		}
		if(!@rmdir($directory)) {
			die("Couldn't remove directory $directory\n");
		}
	}
	else
	{
		unlink($directory);
	}
}


function get_size($directory) {
    $total_size = array(0, 0);
    if(is_dir($directory)) {
		foreach(scandir($directory) as $name) {
			if(in_array($name, array('.', '..'))) {
				continue;
			}
            $subpath = $directory.DIRECTORY_SEPARATOR.$name;
            $sub_size = get_size($subpath);
			$total_size[0] += $sub_size[0];
			$total_size[1]++;
        }
    } else {
		$total_size = array(filesize($directory), 1);
	}
	return $total_size;
}


function is_top_dir($directory) {
    return count(get_folders($directory)) == 0;
}


function get_extensions($directory) {
    $extension_count = array();

    foreach(scandir($directory) as $filename) {
        if(is_file(os_path_join($directory, $filename))) {
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if(isset($extension_count[$extension])) {
                $extension_count[$extension]++;
            } else {
                $extension_count[$extension] = 1;
            }
        }
    }

    return $extension_count;
}


function random_walk($source) {
	global $min_songs_in_album;
    if(is_top_dir($source)) {
        $extensions = get_extensions($source);
        if(@$extensions['mp3'] >= $min_songs_in_album) {
            return $source;
		} else {
			return null;
		}
    } else {
        return random_walk(os_path_join($source, random_choice(get_folders($source))));
	}
}


function os_path_join() {
	$args = func_get_args();
	$strip = function($arg) {
		return rtrim($arg, DIRECTORY_SEPARATOR);
	};
    return join(DIRECTORY_SEPARATOR, array_map($strip, $args));
}


function random_choice($array) {
	return $array[array_rand($array)];
}


function copy_folder($source, $destination) {
	echo "Copying $source...\n";
    if(!file_exists(os_path_join($destination, basename($source)))) {
        copytree($source, os_path_join($destination, basename($source)));
	}
}


function copytree($src, $dst) { 
    $dir = opendir($src); 
    @mkdir($dst); 
    while(false !== ( $file = readdir($dir)) ) { 
        if (( $file != '.' ) && ( $file != '..' )) { 
            if ( is_dir($src . '/' . $file) ) { 
                copytree($src . '/' . $file,$dst . '/' . $file); 
            } 
            else { 
                copy($src . '/' . $file,$dst . '/' . $file); 
            } 
        } 
    } 
    closedir($dir); 
} 


function space_left($directory, $max_size) {
	if($max_size[0]) {
		$current_size = get_size($directory);
		return array($max_size[0], $max_size[1] - $current_size[0]);
	} else {
		return array($max_size[0], $max_size[1] - 1);
	}
}


function process_size($text) {
	if(!preg_match('/^([0-9]+)([KMG])?(B)?$/i', $text, $matches)) {
		die("Invalid size\n");
	}
	
	$unit = empty($matches[2])
			? (empty($matches[3])
				? 'N'
				: 'B')
			: $matches[2];
	$size = (int) $matches[1];
	
	switch(strtoupper($unit)) {
	case 'G':
		$size = $size * 1024;
	case 'M':
		$size = $size * 1024;
	case 'K':
		$size = $size * 1024;
	}
	
	return array($unit != 'N', $size);	// array($is_in_bytes, $size) $is_in_bytes: true, false ($size is number of items)
}


function docopt($doc, $version) {
	global $argv;
	
	if(@$argv[1] == '--version') {
		echo $version . "\n";
		exit;
	}	
	
	$lines = explode("\n", $doc);
	$is_usage = false;
	$usage = false;
	foreach($lines as $line) {
		if(strpos($line, 'Usage:') === 0) {
			$is_usage = true;
		} else if(empty(trim($line))) {
			$is_usage = false;
		} else if($is_usage) {
			$ok = true;
			$arguments = array();
			$usage = trim($line);
			$args = explode(' ', $usage);
			$iargs = 0;
			$iargv = 0;
			
			while($ok && $iargs < count($args)) {
				if($iargs == 0) {
					$iargv++;
				} else {
					$arg = $args[$iargs];
					if($arg{0} == '[') {
						$optional = true;
						$arg = substr($arg, 1, -1);
					} else {
						$optional = false;
					}
					if($arg{0} == '-') {
						if(isset($argv[$iargv]) && $argv[$iargv] == $arg) {
							$arguments[$arg] = true;
							$iargv++;
						} else if($optional) {
							$arguments[$arg] = false;
						} else {
							$ok = false;
						}
					} else {
						if(isset($argv[$iargv]) && $argv[$iargv]{0} != '-') {
							$arguments[$arg] = $argv[$iargv];
							$iargv++;
						} else if($optional) {
							$arguments[$arg] = null;
						} else {
							$ok = false;
						}
					}
				}
				$iargs++;
			}
			
			if($ok && $iargv < count($argv)) {
				$ok = false;
			}
			
			if($ok) {
				return $arguments;
			}
		}
	}
	
	echo $doc;
	die(1);
}


function main() {
	global $doc;
    $arguments = docopt($doc, 'Random Music 0.2.1 (build: 20170613)');

    if($arguments["--delete"]) {
		echo "Deleting " . $arguments["<dst>"] . "...\n";
        remove_and_create_folder($arguments["<dst>"]);
	}
	
	$size = process_size($arguments["<size>"]);
	$end = $size[1] <= 0;

    while(!$end) {
        $source = random_walk($arguments["<src>"]);
        
        if(!empty($source)) {
			$size = space_left($source, $size);
			if($size[1] >= 0) {
				copy_folder($source, $arguments["<dst>"]);
			}
			$end = $size[1] <= 0;
		}
	}
    
	list($bytes, $items) = get_size($arguments["<dst>"]);
	
    echo "\nDone. Destination size: " . round($bytes / 1024 / 1024, 2) . " MiB ($items folders)\n";
}

    
main();
