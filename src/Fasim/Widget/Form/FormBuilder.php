<?php
namespace Fasim\Widget\Form;

use Fasim\Facades\Config;
use Fasim\Facades\Input;

class FormBuilder {
	
	private $action = '';
	private $method = 'post';
	private $controls = [];
	private $data = [];

	private $hasError = false;

	private $baseUrl = '';
	private $imageUrl = '';

	private static $instance = null;
	
	public function __construct() {
		$this->baseUrl = Config::baseUrl();
		$this->imageUrl = Config::get('url.cdn');
		if ($this->imageUrl == '' || substr($this->imageUrl, -1) != '/') {
			$this->imageUrl .= '/';
		}
		if (self::$instance == null) {
			self::$instance = $this;
		}
	}

	public function setBaseUrl($url) {
		$this->baseUrl = $url;
		return $this;
	}

	public function setImageUrl($url) {
		$this->imageUrl = $url;
		if ($this->imageUrl == '' || substr($this->imageUrl, -1) != '/') {
			$this->imageUrl .= '/';
		}
		return $this;
	}

	public function data($key = null, $value = null) {
		if ($key === null) {
			return $this->data;
		}
		if ($value === null ) {
			if (is_array($key)) {
				$this->data = $key;
			} else if (is_string($key)) {
				return isset($this->data[$key]) ? $this->data[$key] : null;
			} else {
				return null;
			}
		} else {
			$this->data[$key] = $value;
		}
		return $this;
	}

	public function action($url) {
		$this->action = FormBuilder::getUrl($url);
		return $this;
	}

	public function method($method) {
		$this->method = $method;
		return $this;
	}

	public function handle($callback=null) {
		$this->hasError = false;
		
		foreach ($this->controls as $control) {
			if ($control instanceof FormValue) {
				$pk = 'n_'.str_replace('.', '_-_', $control->key);
				$value = isset($_POST[$pk]) ? $_POST[$pk] : '';
				if (!$control->checkRules($value)) {
					$this->hasError = true;
				}
				$di = strpos($control->key, '.');
				if ($di !== false) {
					$keys = explode('.', $control->key);
					$values = &$this->data;
					for ($i = 0; $i < count($keys); $i++) {
						$key = $keys[$i];
						if ($i == count($keys) - 1) {
							$values[$key] = $value;
						} else {
							if (!isset($values[$key])) {
								$values[$key] = [];
							}
							$values = &$values[$key];
						}
					}
				} else {
					$this->data[$control->key] = $value;
				}
			}
		}
		if ($callback != null && is_callable($callback)) {
			$errors = $callback($this->hasError, $this->data);
			if (is_array($errors) && count($errors) > 0) {
				$this->hasError = true;
				foreach ($errors as $ek => $ev) {
					$this->addError($ek, $ev);
				}
			}
		}
		return !$this->hasError;
	}

	public function isSuccess() {
		return !$this->hasError;
	}

	public function addError($key, $errorWord) {
		foreach ($this->controls as $control) {
			if (isset($control->key) && $control->key == $key) {
				$control->addCustomError($errorWord);
			}
		}
	}

	protected function getValue($key, $values) {
		$di = strpos($key, '.');
		
		if ($di !== false) {
			$nkey = substr($key, $di + 1);
			$key = substr($key, 0, $di);
			
			if (isset($values[$key])) {
				return $this->getValue($nkey, $values[$key]);
			} 
		}
		if (isset($values[$key])) {
			return $values[$key];
		} 
		//not found
		return null;
	}

	public function build() {
		$html = "<form action=\"{$this->action}\" method=\"{$this->method}\"> \n";

		$controls = [];
		$hiddens = [];
		$buttons = [];

		$keys = [];
		foreach ($this->controls as $control) {
			if ($control instanceof FormValue) {
				$value = $this->getValue($control->key, $this->data);
				$control->value = $value === null ? $control->value : $value;
				$keys[] = $control->key;
			}
			if ($control instanceof FormHidden) {
				$hiddens[] = $control;
			} else if ($control instanceof FormButton) {
				$buttons[] = $control;
			} else  {
				$controls[] = $control;
			}
		}

		$hasReferer = false;
		foreach ($hiddens as $control) {
			$html .= $control->render();
			if ($control->key == 'referer') {
				$hasReferer = true;
			}
		}
		if (!$hasReferer) {
			$referer = Input::referer();
			$html .= "<input type=\"hidden\" name=\"referer\" value=\"{$referer}\" /> \n";
		}

		$html .= "<div class=\"well\"> \n";
		foreach ($controls as $control) {
			$html .= $control->render();
		}
		
		foreach ($buttons as $control) {
			$html .= $control->render();
		}
		$html .= "</div> \n";
		$html .= "</form> \n";

		foreach ($keys as $key) {
			if (strpos($key, '.') != false) {
				$nkey = str_replace('.', '_-_', $key);
				$html = str_replace('_'.$key.'"', '_'.$nkey.'"', $html);
			}
		}
		return $html;
	}

	public function add($control) {
		$this->controls[] = $control;
		return $this;
	}

	public function get($key) {
		foreach ($this->controls as $control) {
			if ($control->key == $key) {
				return $control;
			}
		}
		return null;
	}

	public static function newHidden($key='') {
		return new FormHidden($key);
	}

	public static function newText($key='') {
		return new FormText($key);
	}

	public static function newSelect($key='', $options=[]) {
		return new FormSelect($key, $options);
	}

	public static function newCheckbox($key='', $options=[]) {
		return new FormCheckbox($key, $options);
	}

	public static function newRadio($key='', $options=[]) {
		return new FormRadio($key, $options);
	}

	public static function newImage($key='') {
		return new FormImage($key);
	}

	public static function newImages($key='') {
		return new FormImages($key);
	}

	public static function newFile($key='') {
		return new FormFile($key);
	}

	public static function newDate($key='') {
		return new FormDate($key);
	}

	public static function newFiles($key='') {
		return new FormFiles($key);
	}

	public static function newTextarea($key='') {
		return new FormTextarea($key);
	}

	public static function newRichText($key='') {
		return new FormRichText($key);
	}

	public static function newButton($name='') {
		return new FormButton($name);
	}

	public static function newHtml($html='') {
		return new FormHtml($html);
	}

	public static function newScript($html='') {
		return new FormScript($html);
	}

	public static function getUrl($url) {
		if ($url{0} != '#' && (strlen($url) < 4 || substr($url, 0, 4) != 'http')) {
			if ($url{0} == '/') {
				$url = substr($url, 1);
			}
			$url = self::$instance->baseUrl.$url;
		}
		return $url;
	}

	public static function getImageUrl($url, $format='') {
		if (strlen($url) < 4 || substr($url, 0, 4) != 'http') {
			if ($url{0} == '/') {
				$url = substr($url, 1);
			}
			$url = self::$instance->imageUrl.$url;
		}
		if ($format != '') {
			$url .= '-'.$format.'.jpg';
		}
		return $url;
	}

}



