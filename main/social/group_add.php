<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.social
 * @author Julio Montoya <gugli100@gmail.com>
 */

$language_file= 'userInfo';
$cidReset = true;
//require_once '../inc/global.inc.php';

api_block_anonymous_users();
if (api_get_setting('social.allow_social_tool') != 'true') {
    api_not_allowed();
}

if (api_get_setting(
        'social.allow_students_to_create_groups_in_social'
    ) == 'false' && !api_is_allowed_to_edit()
) {
    api_not_allowed();
}

$table_message = Database::get_main_table(TABLE_MESSAGE);
$usergroup = new UserGroup();
$form = new FormValidator('add_group');
$usergroup->setGroupType($usergroup::SOCIAL_CLASS);
$usergroup->setForm($form, 'add', array());

if ($form->validate()) {
    $values = $form->exportValues();
    $values['group_type'] = UserGroup::SOCIAL_CLASS;
    $values['relation_type'] = GROUP_USER_PERMISSION_ADMIN;

    $groupId = $usergroup->save($values);
    Display::addFlash(Display::return_message(get_lang('GroupAdded')));
    header('Location: group_view.php?id='.$groupId);
    exit();
}

$nameTools = get_lang('AddGroup');
$this_section = SECTION_SOCIAL;

$interbreadcrumb[]= array ('url' =>'home.php','name' => get_lang('Social'));
$interbreadcrumb[]= array ('url' =>'groups.php','name' => get_lang('Groups'));
$interbreadcrumb[]= array ('url' =>'#','name' => $nameTools);

$social_avatar_block = SocialManager::show_social_avatar_block('group_add');
$social_menu_block = SocialManager::show_social_menu('group_add');
$social_right_content = $form->returnForm();

$tpl = \Chamilo\CoreBundle\Framework\Container::getTwig();
SocialManager::setSocialUserBlock($tpl, api_get_user_id(), null, null);
//$tpl->setHelp('Groups');
$tpl->addGlobal('social_menu_block', $social_menu_block);
$tpl->addGlobal('social_right_content', $social_right_content);

echo $tpl->render('@template_style/social/add_groups.html.twig');
