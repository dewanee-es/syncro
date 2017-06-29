<?php
namespace Syncro\Synchronizer\File;

class FileStatus {

	const UNKNOWN = 0;
	const UNCHANGED = 1;
	const OLDER = 2;
	const UPDATED = 3;
	const NEWER = 4;
	const CHANGED = 5;
	const ADDED = 6;
	const DELETED = 7;
	const NONE = 8;
	const UNKNOWN_UNCHANGED = 11;
	const UNKNOWN_ADDED = 16;
	const UNKNOWN_DELETED = 17;
	
	public static function text($status) {
		switch($status) {
		case self::UNCHANGED:
			return 'unchanged';
		case self::OLDER:
			return 'older';
		case self::UPDATED:
			return 'updated';
		case self::NEWER:
			return 'newer';
		case self::CHANGED:
			return 'changed';
		case self::ADDED:
			return 'new';
		case self::DELETED:
			return 'deleted';
		case self::NONE:
			return 'none';
		case self::UNKNOWN_UNCHANGED:
			return 'unchanged?';
		case self::UNKNOWN_ADDED:
			return 'new?';
		case self::UNKNOWN_DELETED:
			return 'deleted?';
		case self::UNKNOWN:
		default:
			return 'unknown';
		}
	}
	
}
