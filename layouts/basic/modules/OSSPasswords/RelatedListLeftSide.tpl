{*<!-- {[The file is published on the basis of YetiForce Public License 3.0 that can be found in the following directory: licenses/LicenseEN.txt or yetiforce.com]} -->*}
{strip}
	{if $IS_FAVORITES}
		{assign var=RECORD_IS_FAVORITE value=(int)in_array($RELATED_RECORD->getId(),$FAVORITES)}
		<div>
			<a class="favorites btn btn-light btn-sm" data-state="{$RECORD_IS_FAVORITE}">
				<span title="{\App\Language::translate('LBL_REMOVE_FROM_FAVORITES', $MODULE)}" class="fas fa-star {if !$RECORD_IS_FAVORITE}d-none{/if}"></span>
				<span title="{\App\Language::translate('LBL_ADD_TO_FAVORITES', $MODULE)}" class="far fa-star {if $RECORD_IS_FAVORITE}d-none{/if}"></span>
			</a>
		</div>
	{/if}
	<div>
		<a href="#" id="copybtn_{$PASS_ID}" data-id="{$PASS_ID}" class="copy_pass d-none btn btn-light btn-sm popoverTooltip" data-content="{\App\Language::translate('LBL_CopyToClipboardTitle', $RELATED_MODULE_NAME)}"><span class="fas fa-download"></span></a>&nbsp;
	</div>
	<a href="#" class="show_pass btn btn-sm btn-light popoverTooltip" id="btn_{$PASS_ID}" data-content="{\App\Language::translate('LBL_ShowPassword', $RELATED_MODULE_NAME)}" data-title-show="{\App\Language::translate('LBL_ShowPassword', $RELATED_MODULE_NAME)}" data-title-hide="{\App\Language::translate('LBL_HidePassword', $RELATED_MODULE_NAME)}"><span class="adminIcon-passwords-encryption"></span></a>
		{assign var=LINKS value=$RELATED_RECORD->getRecordRelatedListViewLinksLeftSide($VIEW_MODEL)}
		{if count($LINKS) > 0}
			{assign var=ONLY_ONE value=count($LINKS) eq 1}
		<div class="actions">
			<div class=" {if $ONLY_ONE}float-right{else}d-none actionImages{/if}">
				{foreach from=$LINKS item=LINK}
					{include file=\App\Layout::getTemplatePath('ButtonLink.tpl', $MODULE) BUTTON_VIEW='listViewBasic'}
				{/foreach}
			</div>
			{if !$ONLY_ONE}
				<button type="button" class="btn btn-sm btn-light toolsAction">
					<span class="fas fa-wrench"></span>
				</button>
			{/if}
		</div>
	{/if}
	{if AppConfig::module('ModTracker', 'UNREVIEWED_COUNT') && $RELATED_MODULE->isPermitted('ReviewingUpdates') && $RELATED_MODULE->isTrackingEnabled() && $RELATED_RECORD->isViewable()}
		<div>
			<a href="{$RELATED_RECORD->getUpdatesUrl()}" class="unreviewed alignMiddle">
				<span class="badge bgDanger all" title="{\App\Language::translate('LBL_NUMBER_UNREAD_CHANGES', 'ModTracker')}"></span>
				<span class="badge bgBlue mail noLeftRadius noRightRadius" title="{\App\Language::translate('LBL_NUMBER_UNREAD_MAILS', 'ModTracker')}"></span>
			</a>
		</div>
	{/if}
{/strip}
