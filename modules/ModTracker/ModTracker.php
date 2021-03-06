<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * ********************************************************************************** */

class ModTracker
{
	/**
	 * Constant variables which indicates the status of the changed record.
	 */
	public static $UPDATED = 0;
	public static $TRASH = 1;
	public static $CREATED = 2;
	public static $ACTIVE = 3;
	public static $LINK = 4;
	public static $UNLINK = 5;
	public static $CONVERTTOACCOUNT = 6;
	public static $DISPLAYED = 7;
	public static $ARCHIVED = 8;
	public static $DELETED = 9;

	/**
	 * Icon actions.
	 *
	 * @var array
	 */
	public static $iconActions = [
		0 => 'fas fa-edit',
		1 => 'fas fa-trash-alt',
		2 => 'fas fa-plus',
		3 => 'fas fa-undo-alt',
		4 => 'fas fa-link',
		5 => 'fas fa-unlink',
		6 => 'fas fa-exchange-alt',
		7 => 'fas fa-th-list',
		8 => 'fas fa-archive',
		9 => 'fas fa-eraser',
	];

	/**
	 * Colors actions.
	 *
	 * @var array
	 */
	public static $colorsActions = [
		0 => '#9c27b0',
		1 => '#ab0505',
		2 => '#607d8b',
		3 => '#009405',
		4 => '#009cb9',
		5 => '#de9100',
		6 => '#e2e3e5',
		7 => '#65a9ff',
		8 => '#0032a2',
		9 => '#000',
	];

	public static function getAllActionsTypes()
	{
		return [
			static::$UPDATED => 'LBL_AT_UPDATE',
			static::$TRASH => 'LBL_AT_TRASH',
			static::$CREATED => 'LBL_AT_CREATE',
			static::$ACTIVE => 'LBL_AT_ACTIVE',
			static::$LINK => 'LBL_AT_LINK',
			static::$UNLINK => 'LBL_AT_UNLINK',
			static::$CONVERTTOACCOUNT => 'LBL_AT_CONVERTTOACCOUNT',
			static::$DISPLAYED => 'LBL_AT_DISPLAY',
			static::$ARCHIVED => 'LBL_AT_ARCHIVED',
			static::$DELETED => 'LBL_AT_DELETE',
		];
	}

	/**
	 * Invoked when special actions are performed on the module.
	 *
	 * @param string Module name
	 * @param string Event Type
	 */
	public function moduleHandler($moduleName, $eventType)
	{
		if ($eventType === 'module.postinstall') {
			\App\Db::getInstance()->createCommand()->update('vtiger_tab', ['customized' => 0], ['name' => $moduleName])->execute();
			Settings_Vtiger_Module_Model::addSettingsField('LBL_OTHER_SETTINGS', [
				'name' => 'ModTracker',
				'iconpath' => 'adminIcon-modules-track-chanegs',
				'description' => 'LBL_MODTRACKER_DESCRIPTION',
				'linkto' => 'index.php?module=ModTracker&action=BasicSettings&parenttab=Settings&formodule=ModTracker',
			]);
		} elseif ($eventType === 'module.disabled') {
			\App\EventHandler::setInActive('ModTracker_ModTrackerHandler_Handler');
		} elseif ($eventType === 'module.enabled') {
			\App\EventHandler::setActive('ModTracker_ModTrackerHandler_Handler');
		}
	}

	/**
	 * function gives an array of module names for which modtracking is enabled.
	 */
	public function getModTrackerEnabledModules()
	{
		$rows = (new \App\Db\Query())->from('vtiger_modtracker_tabs')->all();
		foreach ($rows as &$row) {
			if ($row['visible'] === 1) {
				App\Cache::save('isTrackingEnabledForModule', $row['tabid'], true, App\Cache::LONG);
				$modules[] = \App\Module::getModuleName($row['tabid']);
			} else {
				App\Cache::save('isTrackingEnabledForModule', $row['tabid'], false, App\Cache::LONG);
			}
		}

		return $modules;
	}

	/**
	 * Invoked to disable tracking for the module.
	 *
	 * @param int $tabid
	 */
	public function disableTrackingForModule($tabid)
	{
		$db = \App\Db::getInstance();
		if (!static::isModulePresent($tabid)) {
			$db->createCommand()->insert('vtiger_modtracker_tabs', ['tabid' => $tabid, 'visible' => 0])->execute();
		} else {
			$db->createCommand()->update('vtiger_modtracker_tabs', ['visible' => 0], ['tabid' => $tabid])->execute();
		}
		if (static::isModtrackerLinkPresent($tabid)) {
			$moduleInstance = vtlib\Module::getInstance($tabid);
			$moduleInstance->deleteLink('DETAIL_VIEW_ADDITIONAL', 'View History');
		}
		$db->createCommand()
			->update('vtiger_field', ['presence' => 1], ['tabid' => $tabid, 'fieldname' => 'was_read'])
			->execute();
		App\Cache::save('isTrackingEnabledForModule', $tabid, false, App\Cache::LONG);
	}

	/**
	 * Invoked to enable tracking for the module.
	 *
	 * @param int $tabid
	 */
	public function enableTrackingForModule($tabid)
	{
		if (!static::isModulePresent($tabid)) {
			\App\Db::getInstance()->createCommand()->insert('vtiger_modtracker_tabs', ['tabid' => $tabid, 'visible' => 1])->execute();
		} else {
			\App\Db::getInstance()->createCommand()->update('vtiger_modtracker_tabs', ['visible' => 1], ['tabid' => $tabid])->execute();
		}
		\App\Db::getInstance()->createCommand()->update('vtiger_field', ['presence' => 2], ['tabid' => $tabid, 'fieldname' => 'was_read'])->execute();
		if (static::isModtrackerLinkPresent($tabid)) {
			$moduleInstance = vtlib\Module::getInstance($tabid);
			$moduleInstance->addLink('DETAIL_VIEW_ADDITIONAL', 'View History', "javascript:ModTrackerCommon.showhistory('\$RECORD\$')", '', '', ['path' => 'modules/ModTracker/ModTracker.php', 'class' => 'ModTracker', 'method' => 'isViewPermitted']);
		}
		App\Cache::save('isTrackingEnabledForModule', $tabid, true, App\Cache::LONG);
	}

	/**
	 * Invoked to check if tracking is enabled or disabled for the module.
	 *
	 * @param string $moduleName
	 */
	public static function isTrackingEnabledForModule($moduleName)
	{
		$tabId = \App\Module::getModuleId($moduleName);
		if (App\Cache::has('isTrackingEnabledForModule', $tabId)) {
			return App\Cache::get('isTrackingEnabledForModule', $tabId);
		}
		$isExists = (new \App\Db\Query())->from('vtiger_modtracker_tabs')
			->where(['vtiger_modtracker_tabs.visible' => 1, 'vtiger_modtracker_tabs.tabid' => $tabId])
			->exists();
		App\Cache::save('isTrackingEnabledForModule', $tabId, $isExists, App\Cache::LONG);

		return $isExists;
	}

	/**
	 * Invoked to check if the module is present in the table or not.
	 *
	 * @param int $tabId
	 */
	public static function isModulePresent($tabId)
	{
		if (!App\Cache::has('isTrackingEnabledForModule', $tabId)) {
			$row = (new \App\Db\Query())->from('vtiger_modtracker_tabs')->where(['tabid' => $tabId])->one();
			if ($row) {
				App\Cache::save('isTrackingEnabledForModule', $tabId, (bool) $row['visible'], App\Cache::LONG);

				return true;
			} else {
				return false;
			}
		}

		return true;
	}

	/**
	 * Invoked to check if ModTracker links are enabled for the module.
	 *
	 * @param int $tabid
	 */
	public static function isModtrackerLinkPresent($tabid)
	{
		return (new \App\Db\Query())->from('vtiger_links')
			->where(['linktype' => 'DETAIL_VIEW_ADDITIONAL', 'linklabel' => 'View History', 'tabid' => $tabid])
			->exists();
	}

	/**
	 * This function checks access to the view.
	 *
	 * @param \vtlib\LinkData $linkData
	 *
	 * @return bool
	 */
	public static function isViewPermitted(\vtlib\LinkData $linkData)
	{
		$moduleName = $linkData->getModule();
		$recordId = $linkData->getInputParameter('record');
		if (\App\Privilege::isPermitted($moduleName, 'DetailView', $recordId)) {
			return true;
		}

		return false;
	}

	public static function trackRelation($sourceModule, $sourceId, $targetModule, $targetId, $type)
	{
		$db = App\Db::getInstance();
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$currentTime = date('Y-m-d H:i:s');
		$db->createCommand()->insert('vtiger_modtracker_basic', [
			'crmid' => $sourceId,
			'module' => $sourceModule,
			'whodid' => $currentUser->getRealId(),
			'changedon' => $currentTime,
			'status' => $type,
			'last_reviewed_users' => '#' . $currentUser->getRealId() . '#',
		])->execute();
		$id = $db->getLastInsertID('vtiger_modtracker_basic_id_seq');
		ModTracker_Record_Model::unsetReviewed($sourceId, $currentUser->getRealId(), $id);
		$db->createCommand()->insert('vtiger_modtracker_relations', [
			'id' => $id,
			'targetmodule' => $targetModule,
			'targetid' => $targetId,
			'changedon' => $currentTime,
		])->execute();
		$isMyRecord = (new App\Db\Query())->from('vtiger_crmentity')
			->where(['<>', 'smownerid', $currentUser->getRealId()])
			->andWhere(['crmid' => $sourceId])
			->exists();
		if ($isMyRecord) {
			$db->createCommand()
				->update('vtiger_crmentity', ['was_read' => 0], ['crmid' => $sourceId])
				->execute();
		}
	}

	/**
	 * Function is executed when adding related record.
	 *
	 * @param string $sourceModule
	 * @param int    $sourceId
	 * @param string $targetModule
	 * @param int    $targetId
	 */
	public static function linkRelation($sourceModule, $sourceId, $targetModule, $targetId)
	{
		self::trackRelation($sourceModule, $sourceId, $targetModule, $targetId, self::$LINK);
		if (in_array($sourceModule, AppConfig::module('ModTracker', 'SHOW_TIMELINE_IN_LISTVIEW')) && \App\Privilege::isPermitted($sourceModule, 'TimeLineList')) {
			ModTracker_Record_Model::setLastRelation($sourceId, $sourceModule);
		}
	}

	/**
	 * Function is executed when removing related record.
	 *
	 * @param string $sourceModule
	 * @param int    $sourceId
	 * @param string $targetModule
	 * @param int    $targetId
	 */
	public static function unLinkRelation($sourceModule, $sourceId, $targetModule, $targetId)
	{
		self::trackRelation($sourceModule, $sourceId, $targetModule, $targetId, self::$UNLINK);
		if (in_array($sourceModule, AppConfig::module('ModTracker', 'SHOW_TIMELINE_IN_LISTVIEW')) && \App\Privilege::isPermitted($sourceModule, 'TimeLineList')) {
			ModTracker_Record_Model::setLastRelation($sourceId, $sourceModule);
		}
	}
}
