<?php
/**
*
* @package postlink
* @copyright (c) 2015 phpBB Group
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace jenkler\postlink\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
	protected $config;

	public function __construct(\phpbb\config\config $config)
	{
		$this->config = $config;
	}

	static public function getSubscribedEvents()
	{
		return array(
			'core.acp_board_config_edit_add' => 'add_postlink_config',
			'core.page_footer' => 'rewrite_postlink'
		);
	}

	public function add_postlink_config($event)
	{
		if ($event['mode'] == 'settings' && isset($event['display_vars']['vars']['board_timezone']))
		{
			$display_vars = $event['display_vars'];
			$postlink_vars = array(
				'postlink_prefix_url' => array(
					'lang' => 'Postlink prefix',
					'type' => 'text:40:40',
					'explain' => true,
				),
			);
			$insert_after = array('after' => 'board_timezone');
			$display_vars['vars'] = phpbb_insert_config_array($display_vars['vars'], $postlink_vars, $insert_after);
			$event['display_vars'] = $display_vars;
		}
	}

	private function add_prefix($matches)
	{
		$host = parse_url($this->config['postlink_prefix_url'], PHP_URL_HOST);
		$http = $matches[2] ? $matches[2] : 'http://';
		$url = trim($http.$matches[3], '"');
		if(strpos($url, substr($host , strpos($host, '.') + 1)) !== false)
		{
			return $matches[1].$url.'"';
		}
		return $matches[1].$this->config['postlink_prefix_url'].urlencode($url).'"';
	}

	public function rewrite_postlink($event)
	{
		if(isset($this->config['postlink_prefix_url']) && $this->config['postlink_prefix_url'] == '') return;

		global $request;
		global $phpbb_container;

		$context = $phpbb_container->get('template_context');
		$tpldata = &$context->get_data_ref();

		if(isset($tpldata['postrow']))
		{
			foreach ($tpldata['postrow'] as &$postrow)
			{
				if(!empty($postrow['MESSAGE']))
				{
					$postrow['MESSAGE'] = preg_replace_callback('|(href=[\'"]?)(https?://)?([^\'"\s]+[\'"]?)|i',
					'self::add_prefix', $postrow['MESSAGE']);
				}
			}
		}
	}
}
