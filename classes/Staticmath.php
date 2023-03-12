<?php

namespace Grav\Plugin;
use Grav\Common\Grav;
use RocketTheme\Toolbox\Event\Event;

class Staticmath
{
	protected $markdown;
	protected $enabled = true;

	public function enabled($enable = null)
    {
        if (is_bool($enable)) {
            $this->enabled = (bool) $enable;
        }

        return $this->enabled;
	}

	public function render($content, $options = [], $page = null)
    {
        return $this->markdown->text($content);
	}

	public function parseLatex(string $tex) {
		$staticmath_server = Grav::instance()['config']->get('plugins.staticmath.server');
		$postfield = "data=" . $tex;
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

	public function setupMarkdown($markdown)
    {
        /**
         * Markdown blocks
         */

        $delimiters = Grav::instance()['config']->get('plugins.staticmath.delimiters', []);

        // Add Latex block environment to Markdown parser
        $this->markdown = $markdown;
        foreach ($delimiters['block'] as $begin => $end) {
            $markdown->addBlockType($begin[0], 'Latex', true, true, 0);
        }

        $markdown->blockLatex = function($line, $block = null) use ($delimiters)
        {
            if (!$this->enabled()) {
                return;
            }

            foreach ($delimiters['block'] as $begin => $end) {
                if (preg_match('/^(' . preg_quote($begin) . ')[ ]*$/', $line['text'], $matches)) {
                    $block = [
                        'begin' => $begin,
                        'end' => $end,
                        'element' => [
                            'name' => 'p',
                            'attributes' => [
                                'class' => 'mathjax mathjax--block'
                            ],
                            'text' => [],
                        ]
                    ];

                    return $block;
                }
            }
        };

        $markdown->blockLatexContinue = function($line, $block)
        {
            if (isset($block['complete'])) {
                return;
            }

            if (preg_match('/^'. preg_quote($block['end']) . '[ ]*$/', $line['text'])) {
                $block['complete'] = true;
                return $block;
            }

            $block['element']['text'][] = $line['body'];
            return $block;
        };

        $markdown->blockLatexComplete = function($block)
        {
            $text = $this->parseLatex(implode("\n", $block['element']['text']));

            $block['element']['text'] = $text;
			$block['markup'] = $block['element']['text'];
            return $block;
        };

        /**
         * Markdown inline
         */

        // Add Latex inline environment to Markdown parser
        foreach ($delimiters['inline'] as $begin => $end) {
            $markdown->addInlineType($begin[0], 'Latex', 0);
        }

        $markdown->inlineLatex = function($excerpt) use ($delimiters)
        {
            if (!$this->enabled()) {
                return;
            }

            foreach ($delimiters['inline'] as $begin => $end) {
                $begin = preg_quote($begin);
                $end = preg_quote($end);

                if (preg_match('/^(' . $begin . ')[ ]*(.+?)[ ]*(' . $end . ')/s', $excerpt['text'], $matches))
                {
                    $text = preg_replace("/[\pZ\pC]+/u", ' ', '\\(' . $matches[2] . '\\)');
                    $block = [
                        'extent' => strlen($matches[0]),
                        'begin' => $matches[1],
                        'end' => $matches[3],
                        'element' => [
                            'name' => 'span',
                            'attributes' => [
                                'class' => 'mathjax mathjax--inline'
                            ],
                            'text' => $text
                        ]
                    ];

                    $block['element']['text'] = $text;
					$block['markup'] = "hey :0";
                    return $block;
                }
            }
        };
    }
}

?>
