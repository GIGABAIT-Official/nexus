<?php
/*
 *	Made by Samerton
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.0.0-pr13
 *
 *  License: MIT
 *
 *  DefaultRevamp template settings
 */

$NexusLanguage = new Language(ROOT_PATH . '/custom/' . '/templates/' . '/Nexus/' . '/template_settings/' . '/language', LANGUAGE);

$GLOBALS['NexusLanguage'] = $NexusLanguage;

require_once(ROOT_PATH . '/custom/' . '/templates/' . '/Nexus/' . '/template_settings/' . 'settings.php');

$module = new Nexus($language, $pages, $INFO_MODULE);

class Nexus extends Module
{

	private $_language, $NexusLanguage;

	public function __construct($language, $pages, $INFO_MODULE)
	{
		$this->_language = $language;

		$this->NexusLanguage = $GLOBALS['NexusLanguage'];

		$this->module_name = $INFO_MODULE['name'];
		$author = $INFO_MODULE['author'];
		$module_version = $INFO_MODULE['module_ver'];
		$nameless_version = $INFO_MODULE['nml_ver'];
		parent::__construct($this, $this->module_name, $author, $module_version, $nameless_version);

		// StaffCP
		$pages->add($this->module_name, '/panel/nexus', 'pages/panel/settings.php');
	}

	public function onInstall()
	{

		try {
			$engine = Config::get('mysql/engine');
			$charset = Config::get('mysql/charset');
		} catch (Exception $e) {
			$engine = 'InnoDB';
			$charset = 'utf8mb4';
		}
		if (!$engine || is_array($engine))
			$engine = 'InnoDB';

		if (!$charset || is_array($charset))
			$charset = 'latin1';

		// Queries
		$queries = new Queries();

		try {
			$queries->createTable("nexus_settings", "`id` int(11) NOT NULL AUTO_INCREMENT, `name` varchar(255) NOT NULL, `value` varchar(5000) NOT NULL, PRIMARY KEY (`id`)", "ENGINE=$engine DEFAULT CHARSET=$charset");
		} catch (Exception $e) {
			var_dump($e);
		}
	}

	public function onUninstall()
	{
	}

	public function onEnable()
	{

		$queries = new Queries();

		try {

			$group = $queries->getWhere('groups', array('id', '=', 2));
			$group = $group[0];

			$group_permissions = json_decode($group->permissions, TRUE);
			$group_permissions['admincp.nexus'] = 1;

			$group_permissions = json_encode($group_permissions);
			$queries->update('groups', 2, array('permissions' => $group_permissions));
		} catch (Exception $e) {
			// Error
		}
	}

	public function onDisable()
	{
	}

	public function onPageLoad($user, $pages, $cache, $smarty, $navs, $widgets, $template)
	{

		PermissionHandler::registerPermissions($this->module_name, array(
			'admincp.nexus' => $this->NexusLanguage->get('general', 'group_permision')
		));




		// Widgets
		require_once(ROOT_PATH . '/modules/Nexus/widgets/donate.php');
		$module_pages = $widgets->getPages('Donate');
		$NexusLanguage = $this->NexusLanguage;
		$Donate = new Donate($module_pages, $smarty, $user, $NexusLanguage);
		$widgets->add($Donate);

		require_once(ROOT_PATH . '/modules/Nexus/widgets/message.php');
		$module_pages = $widgets->getPages('Message');
		$NexusLanguage = $this->NexusLanguage;
		$Message = new Message($module_pages, $smarty, $user, $NexusLanguage);
		$widgets->add($Message);




		// $jsonIn = file_get_contents('https://discordapp.com/api/servers/760945720470667294/widget.json');
		// $JSON = json_decode($jsonIn, true);
		// $dsOnlineUsers = $JSON['presence_count'];
		// $dsName = $JSON['name'];


		// echo '<pre>DS NAME = ' . $dsName . '<br>';
		// echo 'DS ONLINE USERS = ' . $dsOnlineUsers . '</pre>';


		$icon = '<i class="fas fa-palette"></i>';
		$order = 19;

		if (defined('FRONT_END')) {

			if ($user->isLoggedIn()) {
				$smarty->assign(array(
					'USER_LOGIN' => 1
				));
			}

			$queries = new Queries();
			$settings_data = $queries->getWhere('nexus_settings', array('id', '<>', 0));
			if (count($settings_data)) {
				foreach ($settings_data as $value) {
					$settings_data_array[$value->name] = array(
						'id' => Output::getClean($value->id),
						'value' => Output::getClean($value->value)
					);

					if ($value->name == 'discord_id') {

						$discord_server = array();
						if ($value->value !== '') {

							if ($cache->isCached('ds_status_ping')) {
								$discord_server = $cache->retrieve('ds_status_ping');
							} else {
								$ch = curl_init();
								curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
								curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
								curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
								curl_setopt($ch, CURLOPT_TIMEOUT, 5);
								curl_setopt($ch, CURLOPT_URL, 'https://discordapp.com/api/servers/' . $value->value . '/widget.json');
								$result = curl_exec($ch);
								$result = json_decode($result);
								curl_close($ch);

								$discord_server = array(
									'name' => $result->name,
									'members' => $result->presence_count,
									'link' => $result->instant_invite,
								);

								$cache->store('ds_status_ping', $discord_server, 60);
							}

							$smarty->assign(array(
								'DISCORD_SERVER' => $discord_server,
							));
						}
					}

					$smarty->assign(array(
						strtoupper($value->name) => htmlspecialchars_decode($settings_data_array[$value->name]['value'])
					));
				}
			}
		}

		if (defined('BACK_END')) {

			$title = $this->NexusLanguage->get('general', 'title');


			if ($user->hasPermission('admincp.nexus')) {

				$navs[2]->add('nexus_divider', mb_strtoupper($title, 'UTF-8'), 'divider', 'top', null, $order, '');

				$navs[2]->add('nexus_items', $title, URL::build('/panel/nexus'), 'top', null, $order + 0.1, $icon);
			}
		}
	}
}


if(Input::exists()){
	if(Token::check()){
		$cache->setCache('template_settings');

		if(isset($_POST['darkMode'])){
			$cache->store('darkMode', $_POST['darkMode']);
		}

		if(isset($_POST['navbarColour'])){
			$cache->store('navbarColour', $_POST['navbarColour']);
		}

		Session::flash('admin_templates', $language->get('admin', 'successfully_updated'));

	} else
		$errors = array($language->get('general', 'invalid_token'));
}


$NexusLanguage = $GLOBALS['NexusLanguage'];
$page_title = $NexusLanguage->get('general', 'title');

if ($user->isLoggedIn()) {
	if ($user->canViewStaffCP) {

		Redirect::to(URL::build('/'));
		die();
	}
	if (!$user->isAdmLoggedIn()) {

		Redirect::to(URL::build('/panel/auth'));
		die();
	} else {
		if (!$user->hasPermission('admincp.nexus')) {
			require_once(ROOT_PATH . '/403.php');
			die();
		}
	}
} else {
	// Not logged in
	Redirect::to(URL::build('/login'));
	die();
}

define('PAGE', 'panel');
define('PARENT_PAGE', 'nexus_items');
define('PANEL_PAGE', 'nexus_items');

require_once(ROOT_PATH . '/core/templates/backend_init.php');


$settings_data = $queries->getWhere('nexus_settings', array('id', '<>', 0));
if (count($settings_data)) {
	foreach ($settings_data as $key => $value) {
		$settings_data_array[$value->name] = array(
			'id' => Output::getClean($value->id),
			'value' => Output::getClean($value->value)
		);
		$smarty->assign(array(
			strtoupper($value->name) => $settings_data_array[$value->name]['value']
		));
	}
}



if (!isset($_POST['sel_btn'])) {
	if (Input::exists()) {
		$errors = array();


		if (Token::check(Input::get('token'))) {

			Session::flash('select_btn', $_POST['sel_btn']);

			if (count($_FILES)) {

				require(ROOT_PATH . '/core/includes/bulletproof/bulletproof.php');

				$module_img_dir = join(DIRECTORY_SEPARATOR, array(ROOT_PATH, 'uploads', 'Nexus'));

				if (!file_exists($module_img_dir)) {
					mkdir($module_img_dir);
				}

				foreach ($_FILES as $key => $value) {


					$image = new Bulletproof\Image($_FILES);

					$image_extensions = array('jpg', 'png', 'gif', 'jpeg');
					$image->setSize(1000, 10 * 1048576);
					$image->setDimension(2000, 2000);
					$image->setMime($image_extensions);
					$image->setName($key);

					$image->setLocation(join(DIRECTORY_SEPARATOR, array(ROOT_PATH, 'uploads', 'Nexus')));

					if ($image[$key]) {
						$upload = $image->upload();
						if ($upload) {

							$img_url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . '/uploads/Nexus/' . $upload->getName() . '.' . $upload->getMime();

							try {
								$queries->update('nexus_settings', $settings_data_array[$key]['id'], array(
									'value' => $img_url
								));
							} catch (Exception $e) {
								$queries->create('nexus_settings',  array(
									'name' => $key,
									'value' => $img_url
								));
							}

							Session::flash('staff', $NexusLanguage->get('general', 'save_successfully'));
							Redirect::to(URL::build('/panel/nexus/'));
							die();
						}
					} else {
						$errors = $NexusLanguage->get('general', 'img_empty_and_size') . ini_get('upload_max_filesize');
					}
				}
			} else {

				foreach ($_POST as $key => $value) {

					if ($key == 'token') {
						continue;
					}


					$validate = new Validate();
					$validation = $validate->check($_POST, array(
						'token' => array(
							'required' => true
						)
					));

					if ($validation->passed()) {
						try {
							try {
								$queries->update('nexus_settings', $settings_data_array[$key]['id'], array(
									'value' => Input::get($key)
								));
							} catch (Exception $e) {
								$queries->create('nexus_settings',  array(
									'name' => $key,
									'value' => Input::get($key)
								));
							}
						} catch (Exception $e) {
							$errors[] = $e->getMessage();
						}
					} else {
						$errors[] = $NexusLanguage->get('general', 'save_errors');
					}
				}
				Session::flash('select_btn', $_POST['sel_btn_session']);
				if (!count($errors)) {
					Session::flash('staff', $NexusLanguage->get('general', 'save_successfully'));
					Redirect::to(URL::build('/panel/nexus/'));
					die();
				}
			}
		} else {
			$errors[] = $language->get('general', 'invalid_token');
		}
	}
}





// Smarty variables for links to tpl files admin panel
// $tpl_panel_files = scandir(join(DIRECTORY_SEPARATOR, array(ROOT_PATH, 'custom', 'panel_templates', getTemplateName('panel_'), 'nexus')));
// foreach ($tpl_panel_files as $key => $file) {
// 	$file = getExtension($file);
// 	if ($file['1'] != 'tpl') {
// 		continue;
// 	}

// 	$smarty->assign(array(
// 		strtoupper($file['0']) . '_URL' => URL::build('/panel/nexus/' . $file['0'])

// 	));
// }


// Route == name tpl file
// $template_file = 'nexus/' . getEndRoute() . '.tpl';
$template_file = 'nexus/nexus.tpl';
// $smarty->assign(array(
// 	'TPL_NAME' => $_POST['sel_btn']
// ));



// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, array($navigation, $cc_nav, $mod_nav), $widgets, $template);
$page_load = microtime(true) - $start;
define('PAGE_LOAD_TIME', str_replace('{x}', round($page_load, 3), $language->get('general', 'page_loaded_in')));
$template->onPageLoad();
if (Session::exists('select_btn')) {
	$smarty->assign(array(
		'TPL_NAME_SESSION' =>  Session::flash('select_btn')
	));
}

if (Session::exists('staff'))
	$success = Session::flash('staff');

if (isset($success))
	$smarty->assign(array(
		'SUCCESS' => $success,
		'SUCCESS_TITLE' => $language->get('general', 'success')
	));

if (isset($errors) && count($errors))
	$smarty->assign(array(
		'ERRORS' => $errors,
		'ERRORS_TITLE' => $language->get('general', 'error')
	));

$smarty->assign(array(
	'TOKEN' => Token::get(),
));

require(ROOT_PATH . '/core/templates/panel_navbar.php');

$template->displayTemplate($template_file, $smarty);


$smarty->assign(array(
	// NamelessMC 
		'SUBMIT' => $language->get('general', 'submit'),
		'YES' => $language->get('general', 'yes'),
		'NO' => $language->get('general', 'no'),
		'BACK' => $language->get('general', 'back'),
		'BACK_LINK' => URL::build('/panel/nexus'),
		'ARE_YOU_SURE' => $language->get('general', 'are_you_sure'),
		'CONFIRM_DELETE' => $language->get('general', 'confirm_delete'),
		'NAME' => $language->get('admin', 'name'),
		'DESCRIPTION' => $language->get('admin', 'description'),

	// Nexus About
		'TITLE' => $NexusLanguage->get('general', 'title'),

	// Navigation
		'NAVIGATION' => $NexusLanguage->get('navigation', 'navigation'),
		'OPTIONS_PAGE' => $NexusLanguage->get('navigation', 'options_page'),
		'COLORS_PAGE' => $NexusLanguage->get('navigation', 'colors_page'),
		'NAVBAR_PAGE' => $NexusLanguage->get('navigation', 'navbar_page'),
		'CONNECTIONS_PAGE' => $NexusLanguage->get('navigation', 'connections_page'),
		'ADVANCED_PAGE' => $NexusLanguage->get('navigation', 'advanced_page'),
		'ARC_PAGE' => $NexusLanguage->get('navigation', 'arc_page'),
		'WIDGETS_PAGE' => $NexusLanguage->get('navigation', 'widgets_page'),
		'EMBED_PAGE' => $NexusLanguage->get('navigation', 'embed_page'),
		'UPDATES_PAGE' => $NexusLanguage->get('navigation', 'updates_page'),
		'SUPPORT_PAGE' => $NexusLanguage->get('navigation', 'support_page'),

	// Options
		'FAVICON_LABEL' => $NexusLanguage->get('options', 'favicon_label'),
		'ABOUT_LABEL' => $NexusLanguage->get('options', 'about_label'),
		'ABOUT_PLACEHOLDER_LABEL' => $NexusLanguage->get('options', 'about_placeholder_label'),

	// Colors
		'DARKMODE_LABEL' => $NexusLanguage->get('colors', 'darkmode_label'),
		'TEMPLATE_COLOR_LABEL' => $NexusLanguage->get('colors', 'template_color_label'),
		'NAVBAR_COLOR_LABEL' => $NexusLanguage->get('colors', 'navbar_color_label'),
		'FOOTER_COLOR_LABEL' => $NexusLanguage->get('colors', 'footer_color_label'),
		'BORDER_COLOR_LABEL' => $NexusLanguage->get('colors', 'border_color_label'),
		'COLORS_INFO_LABEL' => $NexusLanguage->get('colors', 'colors_info_label'),

	// Navbar
		'LOGO_LABEL' => $NexusLanguage->get('navbar', 'logo_label'),
		'NAVIGATION_MENU_LABEL' => $NexusLanguage->get('navbar', 'navigation_menu_label'),
		'NAVIGATION_STYLE_LABEL' => $NexusLanguage->get('navbar', 'navigation_style_label'),
		'NAV_TRUE_LABEL' => $NexusLanguage->get('navbar', 'nav_true_label'),
		'NAV_FALSE_LABEL' => $NexusLanguage->get('navbar', 'nav_false_label'),
		'DYNAMIC_LABEL' => $NexusLanguage->get('navbar', 'dynamic_label'),
		'ELEGANT_LABEL' => $NexusLanguage->get('navbar', 'elegant_label'),

	// Connections
		'SERVER_DOMAIN_LABEL' => $NexusLanguage->get('connections', 'server_domain_label'),
		'IP_ADDRESS_LABEL' => $NexusLanguage->get('connections', 'ip_address_label'),
		'SERVER_PORT_LABEL' => $NexusLanguage->get('connections', 'server_port_label'),
		'STYLE_LABEL' => $NexusLanguage->get('connections', 'style_label'),
		'SIMPLE_LABEL' => $NexusLanguage->get('connections', 'simple_label'),
		'ADVANCED_LABEL' => $NexusLanguage->get('connections', 'advanced_label'),
		'DISCORD_LABEL' => $NexusLanguage->get('connections', 'discord_label'),
		'DISCORD_ID_LABEL' => $NexusLanguage->get('connections', 'discord_id_label'),
		'ENABLE_DISCORD_LABEL' => $NexusLanguage->get('connections', 'enable_discord_label'),
		'ENABLE_MINECRAFT_LABEL' => $NexusLanguage->get('connections', 'enable_minecraft_label'),

	// Advanced
		'ADV_WARNING' => $NexusLanguage->get('advanced', 'adv_warning'),
		'ADV_NAV_LABEL' => $NexusLanguage->get('advanced', 'adv_nav_label'),
		'ADV_CONTAINER_LABEL' => $NexusLanguage->get('advanced', 'adv_container_label'),
		'ADV_MARGIN_TOP_LABEL' => $NexusLanguage->get('advanced', 'adv_margin_top_label'),
		'ADV_MARGIN_BOTTOM_LABEL' => $NexusLanguage->get('advanced', 'adv_margin_bottom_label'),
		'ADV_NAV_SIZE_LABEL' => $NexusLanguage->get('advanced', 'adv_nav_size_label'),
		'MINI_LABEL' => $NexusLanguage->get('advanced', 'mini_label'),
		'TINY_LABEL' => $NexusLanguage->get('advanced', 'tiny_label'),
		'SMALL_LABEL' => $NexusLanguage->get('advanced', 'small_label'),
		'LARGE_LABEL' => $NexusLanguage->get('advanced', 'large_label'),
		'HUGE_LABEL' => $NexusLanguage->get('advanced', 'huge_label'),
		'MASSIVE_LABEL' => $NexusLanguage->get('advanced', 'massive_label'),

	// Arc
		'ARC_LABEL' => $NexusLanguage->get('arc', 'arc_label'),
		'ARC_URL_LABEL' => $NexusLanguage->get('arc', 'arc_url_label'),
		'ARC_INFO_1' => $NexusLanguage->get('arc', 'arc_info_1'),
		'ARC_INFO_2' => $NexusLanguage->get('arc', 'arc_info_2'),
		'ARC_INFO_3' => $NexusLanguage->get('arc', 'arc_info_3'),
		'ARC_INFO_4' => $NexusLanguage->get('arc', 'arc_info_4'),
		'ARC_INFO_5' => $NexusLanguage->get('arc', 'arc_info_5'),

	// Widgets
		// Donation Widget
			'DONATE_WIDGET_LABEL' => $NexusLanguage->get('widgets', 'donate_widget_label'),
			'DONATE_EMAIL_LABEL' => $NexusLanguage->get('widgets', 'donate_email_label'),
			'FIRST_AMOUNT_LABEL' => $NexusLanguage->get('widgets', 'first_amount_label'),
			'SECOND_AMOUNT_LABEL' => $NexusLanguage->get('widgets', 'second_amount_label'),
			'THIRD_AMOUNT_LABEL' => $NexusLanguage->get('widgets', 'third_amount_label'),

		// Message Widget
			'MESSAGE_WIDGET_LABEL' => $NexusLanguage->get('widgets', 'message_widget_label'),
			'MESSAGE_TITLE_LABEL' => $NexusLanguage->get('widgets', 'message_title_label'),
			'MESSAGE_TEXT_LABEL' => $NexusLanguage->get('widgets', 'message_text_label'),
			'MESSAGE_ICON_LABEL' => $NexusLanguage->get('widgets', 'message_icon_label'),

	// Embed
		'EMBED_HEAD_LABEL' => $NexusLanguage->get('embed', 'embed_head_label'),
		'EMBED_DESC_LABEL' => $NexusLanguage->get('embed', 'embed_desc_label'),
		'EMBED_COLOR_LABEL' => $NexusLanguage->get('embed', 'embed_color_label'),
		'EMBED_IMAGE_LABEL' => $NexusLanguage->get('embed', 'embed_image_label'),
		'EMBED_KEYWORDS_LABEL' => $NexusLanguage->get('embed', 'embed_keywords_label'),
		'EMBED_IMAGE_INFO_LABEL' => $NexusLanguage->get('embed', 'embed_image_info_label'),
		'EMBED_KEYWORDS_INFO_LABEL' => $NexusLanguage->get('embed', 'embed_keywords_info_label'),
		'EMBED_PREVIEW_LABEL' => $NexusLanguage->get('embed', 'embed_preview_label'),

	// Other
		'TRUE_LABEL' => $NexusLanguage->get('general', 'true_label'),
		'FALSE_LABEL' => $NexusLanguage->get('general', 'false_label'),
		'INFO_BOX_LABEL' => $NexusLanguage->get('general', 'info_box_label'),
));