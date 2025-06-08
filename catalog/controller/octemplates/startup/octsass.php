<?php
class ControllerOCTemplatesStartupOCTSass extends Controller {
	public function index() {
		$file = DIR_APPLICATION . 'view/theme/' . $this->config->get('config_theme') . '/stylesheet/bootstrap.css';

		if (!file_exists($file) && (is_file(DIR_APPLICATION . 'view/theme/' . $this->config->get('config_theme') . '/stylesheet/sass/_bootstrap.scss') && $this->config->get('developer_sass'))) {
			require_once(DIR_STORAGE . 'vendor/octemplates/ScssPhp/scss.inc.php');
			require_once(DIR_STORAGE . 'vendor/octemplates/php-autoprefixer/php-autoprefixer.inc.php');

			$scss = new ScssPhp\ScssPhp\Compiler();
			$scss->setImportPaths(DIR_APPLICATION . 'view/theme/' . $this->config->get('config_theme') . '/stylesheet/sass/');
			//$scss->setFormatter("ScssPhp\ScssPhp\Formatter\Crunched");

			$output = $scss->compile('@import "_bootstrap.scss"');

			$this->OCTaddFileCSS($file, $output);

			set_time_limit(600);
			ini_set('max_execution_time', 600);

			$unprefixed_css = file_get_contents($file);

			$autoprefixer = new Padaliyajay\PHPAutoprefixer\Autoprefixer($unprefixed_css);

			$output = $autoprefixer->compile();

			$this->OCTaddFileCSS($file, $output);
		}
	}

	private function OCTaddFileCSS($file, $output) {
		$to_replace = [
			"\r\n",
			"\r",
			"\n",
			"\t",
		];

		$output = str_replace($to_replace, "", $output);
		$output = str_replace('"`', '"\\', $output);

		file_put_contents($file, '');

		$handle = fopen($file, 'a+');

		flock($handle, LOCK_EX);

		fwrite($handle, $output);

		fflush($handle);

		flock($handle, LOCK_UN);

		fclose($handle);
	}
}
