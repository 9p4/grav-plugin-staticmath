<?php

namespace Grav\Plugin;
use Grav\Common\Grav;

class Staticmath
{
	/**
	 * Parse LaTeX code and convert it into KaTeX-rendered HTML
	 *
	 * @param string $tex The LaTeX code to be parsed
	 * @param string $mode The mode the server should respond in (inline or block)
	 * @return string The formatted HTML
	 */
	public function parseLatex(string $tex, string $mode) {
		$staticmath_server = Grav::instance()['config']->get('plugins.staticmath.server');
		$postfield = "mode=" . $mode . "&" . "data=" . urlencode($tex);
		$ch = curl_init($staticmath_server);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postfield);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Length: ' . strlen($postfield)
        ]);
		$result = curl_exec($ch);
		if (!$result) {
			return "<pre>" . $tex . "</pre>";
		}
		return $result;
	}

	/**
	 * Parse a raw page using Regex and black magic
	 * 
	 * @param string $content Page content
	 * @return string Page content with rendered math
	 */
	public function parsePage($content)
    {
		$delimiters = Grav::instance()['config']->get('plugins.staticmath.delimiters', []);
		// First, do the blocks
		foreach ($delimiters['block'] as $begin => $end) {
			$regex = '/' . preg_quote($begin) . '(.*?)' . preg_quote($end) . '/sm';
			// As long as we can still match a block, keep on going
			while (preg_match($regex, $content, $matches)) {
				// $matches is an array of matches, but the second element contains the "inner" portion
				// Parse that "inner" portion
				$parsed = $this->parseLatex($matches[1], "block");
				// Now replace that extracted bit with the parsed data
				$content = preg_replace($regex, $parsed, $content, 1);
			}
		}

		foreach ($delimiters['inline'] as $begin => $end) {
			$regex = '/' . preg_quote($begin) . '(.*?)' . preg_quote($end) . '/';
			// As long as we can still match an inline section, keep on going
			while(preg_match($regex, $content, $matches)) {
				// $matches is an array of matches, but the second element contains the "inner" portion
				// Parse that "inner" portion
				$parsed = $this->parseLatex($matches[1], "inline");
				// Now replace that extracted bit with the parsed data
				$content = preg_replace($regex, $parsed, $content, 1);
			}
		}

		return $content;

    }
}

?>
