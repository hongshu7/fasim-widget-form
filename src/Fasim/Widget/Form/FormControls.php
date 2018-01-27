<?php
namespace Fasim\Widget\Form;

interface FormControl {
	function render();
}

class FormHtml implements FormControl {
	public $html = '';

	public function __construct($html='') {
		$this->html($html);
	}

	public function html($html='') {
		$this->html = $html;
		return $this;
	}

	public function render() {
		return $this->html;
	}
}

class FormScript extends FormHtml {
	public function render() {
		$html = '<script type="text/javascript">'."\n";
		$html .= $this->html."\n";
		$html .= '</script>'."\n";
		return $html;
	}
}

abstract class FormValue implements FormControl {
	public $label = '';
	public $key = '';
	public $value = '';
	public $readonly = false;
	

	public $rules = [];
	
	public $min = 0;
	public $max = 0;

	public $errorWord = '';
	public $errorType = '';

	public function __construct($key='') {
		$this->key($key);
	}

	public function label($label) {
		$this->label = $label;
		return $this;
	}

	public function key($key) {
		$this->key = $key;
		return $this;
	}

	public function value($value) {
		$this->value = $value;
		return $this;
	}

	public function readonly($readonly=true) {
		$this->readonly = $readonly;
		return $this;
	}

	public function notEmpty() {
		$this->rules[] = 'not_empty';
		return $this;
	}

	public function integerValue() {
		$this->rules[] = 'integer';
		return $this;
	}

	public function numbericValue() {
		$this->rules[] = 'numberic';
		return $this;
	}

	public function urlValue() {
		$this->rules[] = 'url';
		return $this;
	}

	public function emailValue() {
		$this->rules[] = 'email';
		return $this;
	}

	public function min($min) {
		$this->min = $min;
		return $this;
	}

	public function max($max) {
		$this->max = $max;
		return $this;
	}

	public function addRule($rule) {
		$this->rules[] = $rule;
		return $this;
	}

	public function getError() {

		if ($this->errorType == '') {
			return '';
		}
		if ($this->errorWord != '') {
			return $this->errorWord;
		}
		switch ($this->errorType) {
			case 'min':
				return '长度必须大于'.$this->min;
			case 'max':
				return '长度必须小于'.$this->max;
			case 'not_empty':
				return '不能为空';
			case 'integer':
				return '必须是整数';
			case 'numberic':
				return '必须是数字';
			case 'url':
				return '必须是网址';
			case 'email':
				return '必须是Email';
		}
		return '格式错误';
	}

	public function error($errorWord) {
		$this->errorWord = $errorWord;
	}

	public function addCustomError($errorWord) {
		$this->errorType = 'custom';
		$this->errorWord = $errorWord;
	}

	public function checkRules($value) {
		if (strlen($value.'') < $this->min) {
			$this->errorType = 'min';
			return false;
		}
		if ($this->max > 0 && strlen($value.'') > $this->max) {
			$this->errorType = 'max';
			return false;
		}
		foreach ($this->rules as $rule) {
			$result = $this->checkRule($rule, $value);
			if (!$result) {
				$this->errorType = $rule;
				return false;
			}
		}
		$this->errorType = '';
		return true;
	}

	public function checkRule($rule, $value) {
		if ($rule == 'not_empty') {
			if (empty($value)) {
				return false;
			}
		} else if (!empty($value)){
			$p = $rule;
			if ($rule == 'integer') {
				$p = '/^\d+$/s';
			} else if ($rule == 'numberic') {
				$p = '/^\d+\.?\d*$/s';
			} else if ($rule == 'email') {
				return filter_var($value, FILTER_VALIDATE_EMAIL);
			} else if ($rule == 'url') {
				return filter_var($value, FILTER_VALIDATE_URL);
			}
			if (!preg_match($p, $value)) {
				return false;
			}
		}
		return true;
	}
}


class FormButton implements FormControl {
	public $name;
	public $link = '';
	public $primary = false;
	public function __construct($name='') {
		$this->name = $name;
	}

	public function name($name) {
		$this->name = $name;
		return $this;
	}

	public function link($url) {
		$this->primary = false;
		$this->url = $url;
		return $this;
	}
	public function primary() {
		$this->primary = true;
		return $this;
	}

	public function render() {
		if ($this->primary) {
			return "<button class=\"btn btn-primary\"><i class=\"fa fa-save\"></i> {$this->name}</button> \n";
		} else if ($this->url != '') {
			$url = FormBuilder::getUrl($this->url);
			return "<a href=\"{$url}\" class=\"btn btn-default\">{$this->name}</a> \n";
		}
	}

}

class FormHidden extends FormValue {

	public function render() {
		return  "<input type=\"hidden\" name=\"n_{$this->key}\" value=\"{$this->value}\" /> \n";
	}

}

class FormGroup extends FormValue {
	
	public $groupClasses = [ 'form-group' ];
	public $remark = '';
	
	public function remark($remark) {
		$this->remark = $remark;
		return $this;
	}

	public function groupClass($groupClass) {
		$this->groupClasses[] = $groupClass;
		return $this;

	}


	public function render() {
		$error = $this->getError();
		if ($error != '') {
			$this->groupClasses[] = 'has-error';
		}
		$addClass = $error == '' ? '' : ' error';
		if ($this->groupClass != '') {
			$addClass .= ' ' . $this->groupClass;
		}
		$groupClass = implode(' ', $this->groupClasses);
		$html =  "<div class=\"{$groupClass}\"> \n";
		$html .=  "<label class=\"control-label\" for=\"i_{$this->key}\">{$this->label}</label> \n";
		$html .=  $this->renderInput();
		if ($error != '') {
			$html .=  "<div class=\"help-block\">{$error}</div> \n";
		}
		if ($this->remark) {
			$html .=  "<div class=\"help-block\">{$this->remark}</div> \n";
		}
		$html .=  "</div> \n";
		return $html;
	}

	public function renderInput() {
		return '';
	}
}

abstract class FormUpload extends FormGroup {
	public $maxCount = 10;
	public $allowFiles = '';
	public $width = 80;
	public $height = 80;
	public $title = '';
	public $mimeTypes = '';
	public $valueType = 'json';  //json or url
	public $uploadUrl = '/attachment/upload?dir=auto';

	public function maxCount($maxCount) {
		$this->maxCount = $maxCount;
		return $this;
	}

	public function title($title) {
		$this->title = $title;
		return $this;
	}

	public function allowFiles($allowFiles) {
		$this->allowFiles = $allowFiles;
		return $this;
	}

	public function mimeTypes($mimeTypes) {
		$this->mimeTypes = $mimeTypes;
		return $this;
	}
	
	public function valueType($valueType) {
		$this->valueType = $valueType;
		return $this;
	}

	public function uploadUrl($uploadUrl) {
		$this->uploadUrl = $uploadUrl;
		return $this;
	}

	public function width($width) {
		$this->width = $width;
		return $this;
	}

	public function height($height) {
		$this->height = $height;
		return $this;
	}

	public function renderInput() {
		$fileId = 'i_'.$this->key;
		$value = htmlspecialchars($this->value);
		$html = "<input type=\"hidden\" id=\"{$fileId}\" name=\"n_{$this->key}\" value=\"{$value}\" /> \n";
		$fileListId = 'fileList_'.$this->key;
		$filePickerId = 'filePicker_'.$this->key;
		$files = [];
		if (!empty($this->value)) {
			if ($this->valueType == 'url') {
				if ($this->maxCount == 1) {
					$files = [ ['url' => $this->value] ];
				} else {
					$_files = explode(';', $this->value);
					foreach ($_files as $f) {
						$files[] = ['url' => $f];
					}
				}
			} else {
				$files = json_decode($this->value, true);
				if (count($files) > 0 && $this->maxCount == 1) {
					$files = [ $files ];
				}
			}
		}

		$html .= '<div class="webuploader clearfix">'."\n";
		$html .= '<div id="'.$fileListId.'" class="uploader-list">'."\n";
		$width = $this->width + 2;
		$height = $this->height + 2;
		$doubleWidth = $this->width * 2;
		$doubleHeight = $this->height * 2;
		$style = " style=\"width:{$width}px;height:{$height}px;\"";
		$isImage = strlen($this->mimeTypes) > 5 && substr($this->mimeTypes, 0, 5) == 'image';
		foreach ($files as $file) {
			$ext = substr(strrchr($file['url'], '.'), 1); 
			if ($isImage) {
				$html .= '<div class="file-item"'.$style.'><img src="'.$file['url'].'" /><i class="fa fa-close"></i></div>'."\n";
			} else {
				$html .= '<div class="file-item"'.$style.'><div class="file-icon"><span class="file-icon file-icon-'.$ext.'"></span></div><div class="file-name">'.$file['name'].'</div><i class="fa fa-close"></i></div>'."\n";
			}
		}
		$html .= '</div>'."\n";
		$html .= '<div id="'.$filePickerId.'" class="file-upload-btn"'.$style.'><i class="fa fa-plus fa-3x"></i><span>'.$this->title.'</span></div>'."\n";
		$html .= '</div>'."\n";
		$html .= <<<EOT
<script type="text/javascript">
$('body').ready(function(){
	var maxCount = {$this->maxCount};
	var valueType = '{$this->valueType}';
	var width = {$width};
	var height = {$height};
	var isImage = '{$this->mimeTypes}'.length > 5 && '{$this->mimeTypes}'.substr(0, 5) == 'image';
	var uploader = WebUploader.create({
		auto: true,
		duplicate: true,
		server: '{$this->uploadUrl}',
		pick: '#{$filePickerId}',
		accept: {
			title: '{$this->title}',
			extensions: '{$this->allowFiles}',
			mimeTypes: '{$this->mimeTypes}'
		}
	});
	function checkCount() {
		var items = $('#{$fileListId} .file-item');
		$('#{$filePickerId}').toggle(items.length < maxCount);
	}
	checkCount();
	function removeItem(btn) {
		var item = $(btn).closest('div');
		if (maxCount == 1) {
			$('#{$fileId}').val('');
		} else {
			var value = '';
			var index = $('#{$fileListId} .file-item').index(item);
			if (valueType == 'url') {
				var items = $('#{$fileId}').val().split(';');
				items.splice(index, 1);
				value = items.join(';');
			} else {
				var items = JSON.parse($('#{$fileId}').val());
				items.splice(index, 1);
				value = JSON.stringify(items);
			}
			$('#{$fileId}').val(value);
		}
		item.remove();
		checkCount();
	}
	$('#{$fileListId} .file-item i').click(function(){
		removeItem(this);
	});
	uploader.on('fileQueued', function( file ) {
		var content = '';
		if (isImage) {
			content = '<img>';
		} else {
			content = '<div class="file-icon">' +
			'<span class="file-icon file-icon-' + file.ext + '"></span>' +
			'</div>' +
			'<div class="file-name">' + file.name + '</div>';
		}
		var li = $(
			'<div id="' + file.id + '" class="file-item" style="width:'+width+'px;height:'+height+'px;">' +
				content +
				'<i class="fa fa-close"></i>' +
			'</div>'
		);
		$('#{$fileListId}').append(li);
		checkCount();
		li.find('i').click(function(){
			uploader.cancelFile( file );
		});
		if (isImage) {
			var img = li.find('img');
			uploader.makeThumb(file, function( error, src ) {
				if ( error ) {
					img.replaceWith('<span>不能预览</span>');
					return;
				}
				img.attr( 'src', src );
			}, {$doubleWidth}, {$doubleHeight} );
		}
	});
	uploader.on('fileDequeued', function( file ) {
		var obj = $('#' + file.id + ' i');
		removeItem(obj);
	});
	uploader.on('uploadProgress', function( file, percentage ) {
		var li = $( '#'+file.id ), percentDiv = li.find('.progress span');
		if ( !percentDiv.length ) {
			percentDiv = $('<p class="progress"><span></span></p>').appendTo( li ).find('span');
		}
		percentDiv.css('width', percentage * 100 + '%' );
	});
	function showError(fileId, msg) {
		console.log(fileId);
		var li = $('#'+fileId), errorDiv = li.find('p.error');
		if ( !errorDiv.length ) {
			errorDiv = $('<p class="error"></p>').appendTo( li );
		}
		errorDiv.text(msg);
	}
	uploader.on('uploadSuccess', function( file, response ) {
		//$( '#'+file.id ).addClass('upload-state-done');
		if (typeof response == 'object') {
			if (response.error == 0) {
				var item = {
					url: response.url,
					name: response.name
				};
				if (isImage) {
					item.width = parseInt(response.width);
					item.height = parseInt(response.height);
				}
				var value = '';
				if (maxCount == 1) {
					if (valueType == 'url') {
						value = response.url;
					} else {
						value = JSON.stringify(item);
					}
				} else {
					var val = $('#{$fileId}').val();
					if (valueType == 'url') {
						var items = val == '' ? [] : val.split(';');
						items.push(response.url);
						value = items.join(';');
					} else {
						var items = val == '' ? [] : JSON.parse(val);
						items.push(item);
						value = JSON.stringify(items);
					}
				}
				$('#{$fileId}').val(value);
			} else {
				showError(file.id, response.message);
			}
		} else {
			showError(file.id, '上传失败');
		}
	});
	uploader.on('uploadError', function( file ) {
		showError(file.id, '上传失败');
	});
	uploader.on('uploadComplete', function( file ) {
		$( '#'+file.id ).find('.progress').remove();
	});
	uploader.on('error', function( type ) {
		if (type == 'Q_EXCEED_NUM_LIMIT') {
			alert('所选的文件数量超过限制');
		} else if (type == 'Q_EXCEED_SIZE_LIMIT') {
			alert('所选的文件大小超过限制');
		} else if (type == 'Q_TYPE_DENIED') {
			alert('所选的文件类型不允许上传');
		} else if (type == 'F_DUPLICATE') {
			alert('重复文件');
		} else {
			alert('未知错误:' + type);
		}
	});
});
</script>
EOT;
		return $html;
	}
}

class FormImages extends FormUpload {

	public function __construct($key='') {
		$this->key($key);
		$this->title = '上传图片';
		$this->mimeTypes = 'image/*';
		$this->allowFiles = 'jpg,jpeg,png,gif';
	}
}

class FormImage extends FormImages {
	public function renderInput() {
		$this->maxCount(1);
		return parent::renderInput();
	}
}

class FormFiles extends FormUpload {
	public function __construct($key='') {
		$this->key($key);
		$this->title = '上传文件';
		$this->allowFiles = 'doc,docx,xls,xlsx,ppt,pptx,txt,rar,zip,7z,html,htm,mp3,mov,mp4,avi';
		$this->width(120);
	}

	public function width($width) {
		$this->width = $width;
		$this->height = $width + 24;
		return $this;
	}

	public function height($height) {
		return $this;
	}
}

class FormFile extends FormFiles {
	public function renderInput() {
		$this->maxCount(1);
		return parent::renderInput();
	}
}

class FormAttrs extends FormGroup {
	public $inputClasses = ['form-control'];
	public $styles = [];

	public function miniStyle() {
		return $this;
	}

	public function smallStyle() {
		return $this;
	}

	public function mediumStyle() {
		return $this;
	}

	public function largeStyle() {
		return $this;
	}

	public function xLargeStyle() {
		return $this;
	}

	public function xxLargeStyle() {
		return $this;
	}

	public function inputClass($clazz) {
		$this->inputClasses[] = $clazz;
		return $this;
	}

	public function getStyle() {
		$style = '';
		if (!empty($this->styles)) {
			$style = ' style="';
			foreach ($this->styles as $k => $v) {
				$style .= "$k:$v;";
			}
			$style .= '"';
		}
		return $style;
	}

	public function width($width) {
		$this->styles['width'] = $width + 'px';
		return $this;
	}

	public function height($height) {
		$this->styles['height'] = $height + 'px';
		return $this;
	}

	public function style($name, $value) {
		$this->styles[$name] = $value;
		return $this;
	}
}

class FormText extends FormAttrs {
	public $placeholder = '';

	public function placeholder($placeholder) {
		$this->placeholder = $placeholder;
		return $this;
	}

	public function renderInput() {
		$style = $this->getStyle();
		$readonly = $this->readonly ? ' readonly="readonly"' : '';
		$classes = implode(' ', $this->inputClasses);
		return "<input id=\"i_{$this->key}\" type=\"text\" name=\"n_{$this->key}\" placeholder=\"{$this->placeholder}\" value=\"{$this->value}\" class=\"{$classes}\"{$style}{$readonly} /> \n";
	}

}

class FormDate extends FormAttrs {
	public $dateFormat = 'yyyy-mm-dd';
	public $dateStyle = 'date';

	public $options = [
		'format' => 'yyyy-mm-dd',
		'autoclose' => 'true'
	];
	public function __construct($key='') {
		$this->key($key);
		//$this->groupClass('date');
	}

	public function dateStyle($format='') {
		$this->options['format'] = $format;
		return $this;
	}

	public function dateOptions($options) {
		$this->options = array_merge($this->options, $options);
		return $this;
	}

	public function autoclose($autoclose) {
		$this->autoclose = $autoclose ? 'true' : 'false';
		return $this;
	}

	public function renderInput() {
		$this->inputClasses[] = 'datepicker';
		$style = $this->getStyle();
		$readonly = $this->readonly ? ' readonly="readonly"' : '';
		$classes = implode(' ', $this->inputClasses);
		$html = '<div class="input-group date">'."\n";
		$html .= "<input id=\"i_{$this->key}\" type=\"text\" name=\"n_{$this->key}\" placeholder=\"{$this->placeholder}\" value=\"{$this->value}\" class=\"{$classes}\"{$style}{$readonly} ";
		foreach ($this->options as $ok => $ov) {
			$html .= " data-date-{$ok}=\"{$ov}\"";
		}
		$html .= "/> \n";
		$html .= '<span class="input-group-addon"><i class="fa fa-calendar"></i></span>'."\n";
		$html .= "</div>\n";

		return $html;
	}
}

class FormTextarea extends FormText {
	public function renderInput() {
		$style = $this->getStyle();
		$classes = implode(' ', $this->inputClasses);
		$readonly = $this->readonly ? ' readonly="readonly"' : '';
		return "<textarea id=\"i_{$this->key}\" type=\"text\" name=\"n_{$this->key}\" placeholder=\"{$this->placeholder}\"  class=\"{$classes}\"{$style}{$readonly}>{$this->value}</textarea> \n";
	}
}

class FormRichText extends FormTextarea {
	public $uploadUrl = '';
	public $fileManagerUrl = '';
	function __construct($key) {
		$this->key($key);
		$this->uploadUrl('attachment/upload?ke=1');
		$this->fileManagerUrl('attachment/file_manager');
	}

	public function uploadUrl($uploadUrl='') {
		$this->uploadUrl = FormBuilder::getUrl($uploadUrl);
		return $this;
	}

	public function fileManagerUrl($fileManagerUrl='') {
		$this->fileManagerUrl = FormBuilder::getUrl($fileManagerUrl);
		return $this;
	}

	public function renderInput() {
		if (!isset($this->styles['width'])) {
			$this->style('width', '100%');
		}
		if (!isset($this->styles['height'])) {
			$this->style('height', '500px');
		}
		$baseurl = FormBuilder::getUrl('');
		$html = parent::renderInput();
		$html .= <<<EOT
<script type="text/javascript">
var editor_{$this->key};
$('body').ready(function() {
	KindEditor.ready(function(K) {
		editor_{$this->key} = K.create('#i_{$this->key}', {
			cssPath: [
				'{$baseurl}static/admin/lib/kindeditor/plugins/code/prettify.css',
				'{$baseurl}static/admin/css/content.css'
			],
			bodyClass: 'ke-content content',
			allowFileManager : true,
			uploadJson: '{$this->uploadUrl}',
			fileManagerJson: '{$this->fileManagerUrl}',
		});
	});
});
</script>
EOT;
		return $html;
	}
}

class FormSelect extends FormAttrs {
	public $options = [];
	public function __construct($key='', $options=[]) {
		$this->key($key);
		$this->options($options);
	}

	public function options($options) {
		if (is_array($options)) {
			foreach ($options as $ok => $ov) {
				if (is_array($ov) && isset($ov['value'])) {
					//fixed
					if (isset($ov['key'])) {
						$ov['name'] = $ov['key'];
					}
					if (isset($ov['name'])) {
						$this->options[] = [
							'name' => $ov['name'],
							'value' => $ov['value']
						];
					}
				} else if (is_string($ov)) {
					$this->options[] = [
						'name' => $ov,
						'value' => $ok.''
					];
				}
			}
		}
		return $this;
	}

	public function renderInput() {
		$style = $this->getStyle();
		$readonly = $this->readonly ? ' readonly="readonly"' : '';
		$classes = implode(' ', $this->inputClasses);
		$html = "<select id=\"i_{$this->key}\" name=\"n_{$this->key}\" class=\"{$classes}\"{$style}{$readonly}> \n";
		foreach ($this->options as $option) {
			$selected = $this->value == $option['value'] ? ' selected="selected"' : '';
			$html .= "<option value=\"{$option['value']}\"{$selected}>{$option['name']}</option>\n";
		}
		$html .= "</select> \n";
		return $html;
	}
}