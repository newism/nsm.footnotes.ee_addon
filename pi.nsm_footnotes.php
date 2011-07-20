<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require PATH_THIRD.'nsm_footnotes/config.php';

/**
 * NSM Footnotes Plugin
 * 
 * Generally a module is better to use than a plugin if if it has not CP backend
 *
 * @package			NsmFootnotes
 * @version			0.1.0
 * @author			Leevi Graham <http://leevigraham.com>
 * @copyright 		Copyright (c) 2007-2010 Newism <http://newism.com.au>
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
			'fn_class' => $EE->TMPL->fetch_param('fn_class', 'footnote'),
			'fn_list_class' => $EE->TMPL->fetch_param('fn_list_class'),
		);

		// match {fn id="1"}Footnote content{/fn}
		// match {fn id="1" /}
		preg_match_all("#{ref(.*?)(?:/}|}(.*?)({/ref}))#", $tagdata, $matches);

		$refs = array();
		foreach(array_keys($matches[0]) as $count) {
			$refs[$count] = array(
				'tagdata' => $matches[0][$count],
				'tagparams' => $EE->functions->assign_parameters($matches[1][$count]),
				'content' => $matches[2][$count],
				'tagpair' => empty($matches[2][$count])
			);
		}
		// print_r($refs);

		$footnotes = array();
		foreach ($refs as $count => $ref) {
			// named reference
			if(isset($ref['tagparams']['name'])) {
				// that has content
				if(!empty($ref['content'])) {
					$footnotes[$ref['tagparams']['name']]['content'] = $ref['content'];
				}
				// add this reference
				$footnotes[$ref['tagparams']['name']]['refs'][] = $count;
			} else {
				$footnote = array(
					'content' => $ref['content'],
					'refs' => array($count)
				);
				$footnotes[] = $footnote;
			}
		}

		// print_r($footnotes);
		foreach ($refs as $count => $ref) {
			$html = "<a 
						href='#{$options['fn_prefix']}{$count}'
						id='{$options['ref_prefix']}{$count}'
						class='{$options['ref_class']}'
					>{$count}</a>";
			$tagdata = str_replace($ref['tagdata'], $html, $tagdata);
		}

		print_r($EE->TMPL->var_single);
		// Single variable
		if(in_array('footnotes', $EE->TMPL->var_single)) {
			$html = "<ul class=".$options['fn_list_class'].">";
			foreach ($footnotes as $count => $footnote) {
				$html .= "<li>";
				foreach ($footnote['refs'] as $ref) {
					$html .= "<a 
								href='#{$options['ref_prefix']}{$ref}' 
								id='{$options['fn_prefix']}{$ref}'
								class='{$options['fn_class']}'
							>{$ref}</a></sup> ";
				}
				$html .="{$footnote['content']}</li>";
			}
			$html .= "</ul>";
			$tagdata = str_replace(LD."footnotes".RD, $html, $tagdata);
		} else {
			$data = array(
				'footnote_refs_total_results' => count($refs),
				'footnote_total_results' => count($footnotes)
			);

			foreach ($footnotes as $count => $footnote) {
				$fn = array(
					'footnote_count' => $count,
					'footnote_content' => $footnote['content'],
					'footnote_refs_total_results' => count($footnote['refs'])
				);
				foreach ($footnote['refs'] as $ref_count => $ref_id) {
					$fn['footnote_refs'][] = array(
						'footnote_ref_count' => $ref_count,
						'footnote_ref_id' => $ref_id
					);
				}
				$data['footnotes'][] = $fn;
			}

			$tagdata = $EE->TMPL->parse_variables_row($tagdata, $data);
		}


		$this->return_data = $tagdata;
	}

}