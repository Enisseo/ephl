<?php
/**
 * Several easy to use HTML-related functions.
 * 
 * @author Enisseo
 */

/**
 * Formats a string for HTML display.
 *
 * @param string $str
 * @param mixed... $params additional HTML elements added after formatting by using [[innerText]]
 * @return string The string safe for HTML display.
 */
function html()
{
	$str = func_get_arg(0);
	$html = htmlspecialchars($str);
	if (func_num_args() > 1)
	{
		$params = func_get_args();
		array_shift($params);
		foreach ($params as $param)
		{
			if (is_array($param))
			{
				$html = preg_replace('/\[\[([^\]]*?)\]\]/', $param[0] . '\1' . $param[1], $html, 1);
			}
			else
			{
				$html = preg_replace('/\[\[([^\]]*?)\]\]/', $param, $html, 1);
			}
		}
	}
	return $html;
}

/**
 * Returns an array used for adding an element into a html output.
 *
 * @param string $element the HTML tag.
 * @param array|string $attributes the attributes added to the HTML tag.
 * @return array
 */
function htmlelement($element, $attributes = '')
{
	$attrs = '';
	$idPos = strpos($element, '#');
	if ($idPos !== false)
	{
		$idName = substr($element, $idPos + 1);
		$attrs .= ' id="' . str_replace('"', '\\"', html($idName)) . '"';
		$element = substr($element, 0, $idPos);
	}
	$classNamePos = strpos($element, '.');
	if ($classNamePos !== false)
	{
		$className = substr($element, $classNamePos + 1);
		$attrs .= ' class="' . str_replace('"', '\\"', html($className)) . '"';
		$element = substr($element, 0, $classNamePos);
	}
	if (!empty($attributes))
	{
		if (is_array($attributes))
		{
			foreach ($attributes as $attr => $value)
			{
				$attrs .= ' ' . html($attr) . '="' . str_replace('"', '\\"', html($value)) . '"';
			}
		}
		else
		{
			$attrs = ' ' . $attributes;
		}
	}
	return in_array($element, array('br', 'img', 'hr', 'meta', ))? array('<' . $element . $attrs . ' />', ''): array('<' . $element . $attrs . '>', '</' . $element . '>');
}

/**
 * Returns an array used for adding a link into a html output.
 *
 * @param string $url the url of the HTML link.
 * @param string $title the title attribute of the link.
 * @return array
 */
function htmllink($url, $title = '', $attributes = array())
{
	return htmlelement('a', array_merge($attributes, array('href' => $url, 'title' => $title)));
}

/**
 * Formats a string for Javascript display.
 *
 * @param string $str
 * @return string The string safe for Javascript display.
 */
function javascript($str)
{
	return '\'' . preg_replace('/[\r\n]+/', '\' + \'', addslashes($str)) . '\'';
}


/**
 * Returns the style="" inline attribute with each value concatenated.
 *
 * <p>This function is mainly used for inline styling in email.</p>
 *
 * @param string... $args the style attributes.
 * @return string
 */
function style()
{
	$args = func_get_args();
	return ' style="' . str_replace(';;', ';', join(';', $args)) . '" ';
}


/**
 * Formats a message into nice HTML.
 *
 * @param string $str
 * @return string the HTML version of the message, with some formats.
 */
function text2html($str)
{
	$html = html($str);
	$html = preg_replace('#(((https?://)|(www\.))[^\<\s\./]+(?:[\./][^\<\s\./]*)+)(/|\b)#ie', 'str_replace(\'-\', \'--\', str_replace(\'_\', \'__\', \'\1\5\'))', $html);
	$html = preg_replace('#(\S+@\S+(\.\S+)+)\b#ie', 'str_replace(\'-\', \'--\', str_replace(\'_\', \'__\', \'\1\'))', $html);
	$html = preg_replace('/(?<=\s|^)\*([^\*\r\n]*)(?<=\S)\*/', '<strong>\1</strong>', $html);
	$html = preg_replace('/<strong><\/strong>/', '*', $html);
	$html = preg_replace('/(?<=\s|^)\-([^\-\r\n]*)(?<=\S)\-/', '<strike>\1</strike>', $html);
	$html = preg_replace('/<strike><\/strike>/', '-', $html);
	$html = preg_replace('/(?<=\s|^)_([^_\r\n]*)(?<=\S)_/', '<em>\1</em>', $html);
	$html = preg_replace('/<em><\/em>/', '_', $html);
	$html = preg_replace('#(((https?://)|(www\.))[^\<\s\./\:]+(?:[\./][^\<\s\./\:]*)+)(/|\b)#ie', '\'<a rel="nofollow" onclick="window.open(this.href);return false;" href="\' . (\'\3\'? \'\': \'http://\') . str_replace(\'--\', \'-\', str_replace(\'__\', \'_\', \'\1\5\')) .\'">\' . str_replace(\'--\', \'-\', str_replace(\'__\', \'_\', \'\1\5\')) .\'</a>\'', $html);
	$html = preg_replace('#(\S+@\S+(\.\S+)+)\b#ie', '\'<a onclick="this.href=\\\'mailto:\\\'+this.innerHTML.replace(/<span.+?\/span>/img, \\\'\\\');window.open(this.href);return false;" href="">\' . preg_replace(\'/@/\', \'<span style="display:none">---</span>@<span style="display:none">---</span>\', str_replace(\'--\', \'-\', str_replace(\'__\', \'_\', \'\1\'))) . \'</a>\'', $html);
	return nl2br($html);
}


/**
 * Transforms a HTML string to a nice text string (for email, mainly).
 * 
 * @param string $html
 * @param string the cleaned version of the HTML.
 */
function html2text($html)
{
	return
		preg_replace('#(\s*[\r\n]\s*){3,}#ms', "\n\n",
		preg_replace('# {2,}#', ' ',
		strip_tags(
		preg_replace('#<img[^>]+alt="([^"]*)"[^>]*(\s*/)?>#im', '\1', 
		preg_replace('#<a[^>]+href="([^"]*)"[^>]*>(.*?)</a>#im', '\2 [\1]', 
		preg_replace('#</(h1|h2|h3|h4|h5|h6|p)>#i', "\0\n", 
		preg_replace('#<br(\s*/)?>#i', "\n", 
		preg_replace('#^.*?<body[^>]*>#ims', '',
		preg_replace('#</body>.*$#ims', '', $html
		)))))))));
}
