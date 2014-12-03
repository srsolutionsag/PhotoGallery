<?php

require_once('./Services/Repository/classes/class.ilRepositoryObjectPlugin.php');

/**
 * PhotoGallery repository object plugin
 *
 * @author  Martin Studer <ms@studer-raimann.ch>
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 *
 * @version $Id$
 *
 */
class ilPhotoGalleryPlugin extends ilRepositoryObjectPlugin {

	const PLUGIN_NAME = 'PhotoGallery';
	/**
	 * @var ilPhotoGalleryPlugin
	 */
	protected static $instance;


	/**
	 * @return ilPhotoGalleryPlugin
	 */
	public static function getInstance() {
		if (!isset(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * @return string
	 */
	public function getPluginName() {
		return self::PLUGIN_NAME;
	}


	public static function loadAR() {
		$ILIAS_AR = './Services/ActiveRecord/class.ActiveRecord.php';
		$CUSTOM_AR = './Customizing/global/plugins/Libraries/ActiveRecord/class.ActiveRecord.php';

		if (class_exists('ActiveRecord')) {
			return true;
		}

		if (class_exists('ActiveRecordList')) {
			return true;
		}

		if (is_file($ILIAS_AR)) {
			require_once($ILIAS_AR);
		} elseif (is_file($CUSTOM_AR)) {
			require_once($CUSTOM_AR);
		} else {
			throw new Exception('Please install ILIAS ActiveRecord or use ILIAS 5');
		}
	}


	public function updateLanguageFiles() {
		ini_set('auto_detect_line_endings', true);
		$path = substr(__FILE__, 0, strpos(__FILE__, 'classes')) . 'lang/';
		if (file_exists($path . 'lang_custom.csv')) {
			$file = $path . 'lang_custom.csv';
		} else {
			$file = $path . 'lang.csv';
		}
		$keys = array();
		$new_lines = array();

		foreach (file($file) as $n => $row) {
			//			$row = utf8_encode($row);
			if ($n == 0) {
				$keys = str_getcsv($row, ";");
				continue;
			}
			$data = str_getcsv($row, ";");;
			foreach ($keys as $i => $k) {
				if ($k != 'var' AND $k != 'part') {
					if ($data[1]) {
						$new_lines[$k][] = $data[0] . '_' . $data[1] . '#:#' . $data[$i];
					} else {
						$new_lines[$k][] = $data[0] . '#:#' . $data[$i];
					}
				}
			}
		}
		$start = '<!-- language file start -->' . PHP_EOL;
		$status = true;

		foreach ($new_lines as $lng_key => $lang) {
			$status = file_put_contents($path . 'ilias_' . $lng_key . '.lang', $start . implode(PHP_EOL, $lang));
		}

		if (!$status) {
			ilUtil::sendFailure('Language-Files could not be written');
		}
		$this->updateLanguages();
	}
}

?>
