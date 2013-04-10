<?php
/**
 * Provides PHP & JavaScript code to help manage responsive design.
 * 
 * <p>This aims to load only what's necessary for each platform.</p>
 * 
 * @author Enisseo
 */

class Responsive
{
	protected $config = array();
	
	public function __construct($config = null)
	{
		if (is_null($config))
		{
			$config = array(
				'mobile' => 480,
				'tablet' => 768,
				'grid' => 960,
				'desktop' => -1,
				);
		}
		$this->config = $config;
	}
	
	public function getJQueryInitCode()
	{
		return 'jQuery(function($) {
	var state = false;
	var loadedCss = [];
	var loadedJS = [];
	var states = ' . json_encode($this->config) . ';
	$(window).resize(function() {
		var winWidth = $(window).width();
		for (var newState in states) {
			var stateWidth = states[newState];
			if (stateWidth < 0 || winWidth <= stateWidth) {
				if (newState != state) {
					state = newState;
					var data = {
						r_state: state,
						r_width: winWidth,
						r_height: $(window).height(),
						r_pxRatio: window.devicePixelRatio
					};
					$(\'.Responsive\').each(function() {
						var rData = $.extend({}, data, {r_id: $(this).attr(\'id\')});
						$(this)
							.addClass(\'ResponsiveLoading\')
							.load(window.location.href, rData, function() { $(this).removeClass(\'ResponsiveLoading\'); });
					});
					var cssData = $.extend({}, data, {r_id: \'__css__\'});
					$.ajax({url: window.location.href, type: \'POST\', dataType: \'json\', data: cssData, success: function(cssFiles) {
						for (var c = 0; c < loadedCss.length; c++) {
							$(loadedCss[c]).remove();
						}
						loadedCss = [];
						for (var f = 0; f < cssFiles.length; f++) {
							var cssLink = document.createElement("link");
							cssLink.setAttribute("rel", "stylesheet");
							cssLink.setAttribute("type", "text/css");
							cssLink.setAttribute("href", cssFiles[f]);
							document.getElementsByTagName("head")[0].appendChild(cssLink)
							loadedCss.push(cssLink);
						}
					}});
					var jsData = $.extend({}, data, {r_id: \'__js__\'});
					$.ajax({url: window.location.href, type: \'POST\', dataType: \'json\', data: jsData, success: function(jsFiles) {
						for (var j = 0; j < loadedJS.length; j++) {
							$(loadedJS[j]).remove();
						}
						loadedJS = [];
						for (var f = 0; f < jsFiles.length; f++) {
							var jsScript = document.createElement("script");
							jsScript.setAttribute("type","text/javascript");
							jsScript.setAttribute("src", jsFiles[f]);
							document.getElementsByTagName("head")[0].appendChild(jsScript)
							loadedJS.push(jsScript);
						}
					}});
				}
				break;
			}
		}
	}).resize();
});';
	}
	
	public function get($name, $default)
	{
		return isset($_POST['r_' . $name])? $_POST['r_' . $name]: $default;
	}
	
}

function responsive_init($widths = array(480, 768, 960))
{
	global $_responsive;
	if (empty($_responsive))
	{
		$_responsive = new Responsive();
	}
	?><script><?php echo $_responsive->getJQueryInitCode();?></script><?php
}

function responsive($name, $default = null)
{
	global $_responsive;
	if (empty($_responsive))
	{
		$_responsive = new Responsive();
	}
	return $_responsive->get($name, $default);
}
