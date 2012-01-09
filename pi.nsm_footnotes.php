<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require PATH_THIRD.'nsm_footnotes/config.php';

/**
 * NSM Footnotes Plugin
 * 
 * Generally a module is better to use than a plugin if if it has not CP backend
 *
 * @package			NsmFootnotes
 * @version			0.3.0
 * @author			Leevi Graham <http://leevigraham.com>
 * @copyright 		Copyright (c) 2007-2011 Newism <http://newism.com.au>
 * @license 		Commercial - please see LICENSE file included with this distribution
 * @link			http://expressionengine-addons.com/nsm-footnotes
 * @see 			http://expressionengine.com/public_beta/docs/development/plugins.html
 */

/**
 * Plugin Info
 *
 * @var array
 */
$plugin_info = array(
	'pi_name' => NSM_FOOTNOTES_NAME,
	'pi_version' => NSM_FOOTNOTES_VERSION,
	'pi_author' => 'Leevi Graham',
	'pi_author_url' => 'http://leevigraham.com/',
	'pi_description' => 'Parses content for references which are turned into footnotes',
	'pi_usage' => "http://ee-garage.com/nsm-footnotes"
);

class Nsm_footnotes{

	/**
	 * The return string
	 *
	 * @var string
	 */
	var $return_data = "";

	/**
	 * Parses the tag content for references and outputs them as footnotes
	 */
	function Nsm_footnotes() {

		$EE =& get_instance();
		$tagdata = $EE->TMPL->tagdata;

		$options = array(
			'ref_prefix' => $EE->TMPL->fetch_param('ref_prefix', 'ref-'),
			'ref_class' =>  $EE->TMPL->fetch_param('ref_class', 'ref'),
			'fn_prefix' => $EE->TMPL->fetch_param('fn_prefix', 'fn-'),
			'left_delimiter' => $EE->TMPL->fetch_param('left_delimiter', '\[\['),
			'right_delimiter' => $EE->TMPL->fetch_param('right_delimiter', '\]\]'),

			'fn_list_class' => $EE->TMPL->fetch_param('fn_list_class'),
			'fn_class' => $EE->TMPL->fetch_param('fn_class', 'footnote'),
			'fn_count_class' => $EE->TMPL->fetch_param('fn_count_class', 'footnote-count'),
			'fn_ref_class' => $EE->TMPL->fetch_param('fn_ref_class', 'footnote-reference'),
			'fn_caret_class' =>  $EE->TMPL->fetch_param('fn_caret_class', 'footnote-caret'),
		);

		preg_match_all("#".$options['left_delimiter']."\s*(\#[^\s]+)?(.*?)".$options['right_delimiter']."#", $tagdata, $matches, PREG_SET_ORDER);

		$footnotes = array();
		foreach ($matches as $count => $match) {
			$content = trim($match[2]);
			if(empty($match[1]) == false) {
				if(empty($content) == false) {
					$footnotes[$match[1]]['content'] = $content;
				}
				$footnotes[$match[1]]['refs'][] = $match;
			} else {
				$footnotes[] = array(
					'content' => $content,
					'refs' => array($match)
				);
			}
		}

		$data = array(
			'footnotes_total_results' => count($footnotes),
			'footnotes_refs_total_results' => 0
		);

		// building up a list item incase their is a single {footnotes} tag
		$footnotes_html = " <ul class=".$options['fn_list_class']."> ";

		$footnote_count = 0;
		foreach ($footnotes as $footnote) {

			$footnote_count++;

			$fn = array(
				'footnote_count' => $footnote_count,
				'footnote_content' => $footnote['content'],
				'footnote_refs_total_results' => count($footnote['refs'])
			);

			$ref_count = 0;

			// building up a list item incase their is a single {footnotes} tag
			$footnotes_html .= " <li class='".$options['fn_class']."'> ";
			$footnotes_html .= " <span class='".$options['fn_count_class']."'>".$footnote_count."</span> ";

			if(count($footnote['refs']) > 1) {
				$footnotes_html .= " <span class='".$options['fn_caret_class']."'>^</span> ";
			}

			foreach ($footnote['refs'] as $ref) {
				$ref_count++;

				$ref_id = $this->num_to_letter($ref_count);

				if(count($footnote['refs']) > 1){
					$ref_content = $ref_id;
					$ref_class = $options['fn_ref_class'];
				} else {
					$ref_content = " ^ ";
					$ref_class = $options['fn_caret_class'] . " " . $options['fn_ref_class'];
				}

				$data['footnotes_refs_total_results']++;

				$fn['footnote_refs'][] = array(
					'footnote_ref_count' => $ref_count,
					'footnote_ref_id' => $ref_id,
					'footnote_ref_content' => $ref_content
				);

				$tagdata = str_replace($ref['0'], 
								" <a 
									href='#{$options['fn_prefix']}{$footnote_count}-{$ref_id}'
									id='{$options['ref_prefix']}{$footnote_count}-{$ref_id}'
									class='{$options['ref_class']}'
								>{$footnote_count}</a> "
							, $tagdata);

				// building up a list item incase their is a single {footnotes} tag
				$footnotes_html .= " <a 
							href='#{$options['ref_prefix']}{$footnote_count}-{$ref_id}' 
							id='{$options['fn_prefix']}{$footnote_count}-{$ref_id}'
							class='{$ref_class}'
						>{$ref_content}</a> ";
			}

			// building up a list item incase their is a single {footnotes} tag
			$footnotes_html .= $fn['footnote_content'] . " </li>";

			$data['footnotes'][] = $fn;
		}
		$footnotes_html .= " </ul> ";

		// Tagpair or single tag
		if(in_array('footnotes', $EE->TMPL->var_single) == false) {
			$tagdata = $EE->TMPL->parse_variables_row($tagdata, $data);
		}
		else {
			$tagdata = $EE->functions->prep_conditionals($tagdata, $data);
			$tagdata = str_replace(LD."footnotes".RD, $footnotes_html, $tagdata);
		}

		$this->return_data = $tagdata;
	}

	/**
	 * Takes a number and converts it to a-z,aa-zz,aaa-zzz, etc with uppercase option
	 *
	 * @access	public
	 * @param	int	number to convert
	 * @param	bool	upper case the letter on return?
	 * @return	string	letters from number input
	 */
	function num_to_letter($num, $uppercase = FALSE)
	{
		$letter = 	chr((($num - 1) % 26) + 97);
		$letter .= 	(floor($num/26) > 0) ? str_repeat($letter, floor($num/26)) : '';
		return 	($uppercase ? strtoupper($letter) : $letter); 
	}

}