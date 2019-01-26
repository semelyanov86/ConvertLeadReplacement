<div class="dashboardWidgetHeader">
    {include file="dashboards/WidgetHeader.tpl"|@vtemplate_path:$MODULE_NAME SETTING_EXIST=true}
    <div class="row-fluid filterContainer hide" style="position:absolute;z-index:100001">
        <div class="row-fluid">
			<span class="span5">
				<span class="pull-right">
					{vtranslate('LBL_HOT_LEAD_MINUTES', $MODULE_NAME)}
				</span>
			</span>
            <span class="span4">
				<input type="number" name="createdtime" class="input-large widgetFilter" value="{$CREATEDTIME}" />
			</span>
        </div>
        <div class="row-fluid">
        </div>
    </div>
</div>