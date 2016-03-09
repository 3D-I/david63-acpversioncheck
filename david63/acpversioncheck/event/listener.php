<?php
/**
*
* @package ACP Version Check
* @copyright (c) 2016 david63
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace david63\acpversioncheck\event;

/**
* @ignore
*/
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
* Event listener
*/
class listener implements EventSubscriberInterface
{
	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\template\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var string phpBB root path */
	protected $root_path;

	/**
	* Constructor for listener
	*
	* @param \phpbb\config\config				$config		Config object
	* @param \phpbb\template\template\template	$template	Template object
	* @param \phpbb\user                		$user		User object
	* @param string 							$root_path
	*
	* @access public
	*/
	public function __construct(\phpbb\config\config $config, \phpbb\template\template $template, \phpbb\user $user, $root_path)
	{
		$this->config		= $config;
		$this->template		= $template;
		$this->user			= $user;
		$this->root_path	= $root_path;
	}

	/**
	* Assign functions defined in this class to event listeners in the core
	*
	* @return array
	* @static
	* @access public
	*/
	static public function getSubscribedEvents()
	{
		return array(
			'core.acp_main_notice' => 'check_versions',
		);
	}

	/**
	* @param object $event The event object
	* @return null
	* @access public
	*/
	public function check_versions($event)
	{
		$this->user->add_lang_ext('david63/acpversioncheck', 'acpversioncheck');

		$version_check = true;

		$const_version	= PHPBB_VERSION;
		$db_version		= $this->config['version'];

		// Check const & config versions
		$version_check = phpbb_version_compare($db_version, $const_version, '=');

		// Get style names & versions
		$style_versions	= array();
		$style			= new \DirectoryIterator($this->root_path . 'styles');

		foreach ($style as $style_info)
		{
    		if ($style_info->isDir() && !$style_info->isDot() && $style_info->getFilename() != 'all')
			{
				$style_file = fopen($this->root_path . 'styles/' . $style_info->getFilename() . '/style.cfg', 'r');

				while($line = fgets($style_file))
				{
    				if (strpos(strtolower($line), 'name') === 0)
					{
						$style_name = $this->strip_data_from_line($line);
        				continue;
    				}
					if (strpos(strtolower($line), 'phpbb_version') === 0)
					{
						$style_version = $this->strip_data_from_line($line);
						// Let's check the version here rather than looping through later
						$check_version = phpbb_version_compare($db_version, $style_version, '=');
						$version_check = ($check_version == false ? false : $version_check);
        				continue;
    				}
				}
				fclose($style_file);

				$style_versions[] = array(
					'name' 		=> $style_name,
					'version'	=> $style_version,
				);
    		}
		}

		foreach ($style_versions as $key => $row)
		{
			$this->template->assign_block_vars('style_versions', array(
				'STYLE_NAME'	=> $row['name'],
				'STYLE_VERSION'	=> $row['version'],
		   	));
		}

		// Output template data
		$this->template->assign_vars(array(
			'CONSTANT_VERSION' 		=> $const_version,
			'DB_VERSION' 			=> $db_version,

			'S_ACP_VERSIONCHECK'	=> $version_check,
		));
	}

	private function strip_data_from_line($line)
	{
		$pos 	= strpos($line , '=');
		$data	= trim(substr($line, $pos + 1));
		return $data;
	}
}
