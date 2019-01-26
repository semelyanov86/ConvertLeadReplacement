{*<!--
	PINstudio #Binizik
-->*}
{strip}
<link rel="stylesheet" href="libraries/jquery/posabsolute-jQuery-Validation-Engine/css/validationEngine.jquery.css" />
<script type="text/javascript" src="libraries/jquery/posabsolute-jQuery-Validation-Engine/js/jquery.validationEngine.js" ></script>
<script type="text/javascript" src="libraries/jquery/posabsolute-jQuery-Validation-Engine/js/languages/jquery.validationEngine-ru.js?v=6.4.0-201512"></script>
<style type="text/css">
	.table th, .table td {
		padding: 2px;
	}
    .ui-autocomplete {
        overflow-y: auto;
        max-height: 140px;
    }
	label {
		margin-left: 4px;
	}
	.fieldLabel label {
		cursor: default;
	}
	div.vtCall {
		z-index: 6000 !important
	}
	#getShops {
		display: inline-block;
		float: none;
	}
	.close{
		right: -50px;
		background: white;
		position: absolute;
		opacity: .7;
		border-radius: 50px;
		line-height: 21px;
		color: #999;
		border: 1px solid;
		padding: 10px 15px;
		outline: none;
		transition: all .2s ease;
	}

	.close:hover {
		color: tomato;
		opacity: 1;
	}

	.reminder {
		position: absolute;
		left: 100%;
		top: 50%;
		background: #ffffff linear-gradient(90deg,rgba(108, 137, 148, 0.45),transparent 20%);
		color: #f72626;
		border-radius: 0 10px 10px 0;
		transform: translateY(-50%);
		box-shadow: 4px 5px 25px #1e2c2f;
		font: normal 1rem/1.2rem sans-serif;
		min-width: 250px;
		z-index: -5;
		width: 60%;
		text-align: center;
	}

	.reminder h3 {
		line-height: 27px;
		font-size: 18px;
		color: tomato;
		padding: 5px 40px;
		margin: 20px 0px 5px;
		border-bottom: 1px solid;
	}

	.reminder h3 i {
		font-size: 2.2rem;
		font-family: serif;
		display: block;
	}

	.reminder ul {
		max-width: 260px;
		display: inline-block;
		text-align: left;
		margin: 30px 0px 65px 65px;
		list-style: none;
	}

	.reminder li {
		padding-bottom: 1em;
		position: relative;
        font: italic 1.4em/1.4em serif;
	}

	.reminder li:nth-of-type(1):before {
		background-image: url(layouts/vlayout/modules/Leads/resources/img/pack.png);
	}

	.reminder li:nth-of-type(2):before {
		background-image: url(layouts/vlayout/modules/Leads/resources/img/balloons.png);
	}

	.reminder li:nth-of-type(3):before {
		background-image: url(layouts/vlayout/modules/Leads/resources/img/card.png);
	}

	.reminder li:before {
		background-size: contain;
		background-repeat: no-repeat;
		content: "";
		display: block;
		width: 80px;
		height: 80px;
		position: absolute;
		left: -90px;
		top: 5px;
	}
	.reminder b {
		font-weight: normal;
		color: darkred;
        font-family: sans-serif;
	}
</style>
<div class="reminder">
	<h3><i>Обязательно</i> предложите клиенту:</h3>
	<ul>
		<li>оформить букет<br/> <b>лентой</b>, <b>упаковкой</b></li>
		<li>воздушные <b>шары</b>, <b>игрушку</b>, набор <b>конфет</b></li>
		<li><b>открытку</b> ручной работы</li>
	</ul>
</div>
<div class="modelContainer" style="overflow-x: hidden; overflow-y: scroll; height: 90vh; width: 792px; ">
<button class="close" aria-hidden="true" data-dismiss="modal" type="button" title="Закрыть">x</button>
<form id="convertView" class="form-horizontal recordEditView" name="ConvertLeadToSalesOrder" method="post" action="index.php">
	<input type="hidden" name="module" value="Leads">
	<input type="hidden" name="action" value="ConvertLeadToSalesOrder">
	<input type="hidden" name="sourceid" value="{$SRCID}">
	<input type="hidden" name="contactAction" value="Create">
	<input type="hidden" name="utmmetka" value="{$fieldValues['cf_853']}">
	<input type="hidden" name="istok" value="{$fieldValues['cf_854']}">
	<input type="hidden" name="jsonSelectedProducts" value="{}">
	<div class="quickCreateContent">
		<div class="modal-body">
			<h4><img src="layouts/vlayout/modules/Leads/resources/img/Company.png" style="position: relative; top: 16px; margin-top: -30px;"> Информация по заказу</h4><br>
			<table class="massEditTable table table-bordered">
				<tr>
					<td class="fieldLabel"><label>Наименование заказа</label></td>
					<td class="fieldValue" colspan="3"><input class="input-xxlarge" name="subject" type="text" style="width: 584px;"></td>
				</tr>
				<tr>
					<td class="fieldLabel"><label><span class="redColor">*</span>Имя</label></td>
					<td class="fieldValue"><input class="input-large firstnamelead" name="firstname" type="text" value="{$fieldValues['firstname']}" data-validation-engine="validate[required]"></td>
					<td class="fieldLabel"><label>Фамилия</label></td>
					<td class="fieldValue"><input class="input-large" name="lastname" type="text" value="{$fieldValues['lastname']}{if empty($fieldValues['lastname'])}-{/if}"></td>	
				</tr>
				<tr>
					<td class="fieldLabel"><label><span class="redColor">*</span>Мобильный тел.</label></td>
					<td class="fieldValue"><input class="input-large" name="mobile" type="text" value="{$fieldValues['mobile']}" data-validation-engine="validate[required]"></td>
					<td class="fieldLabel"><label>Домашний тел.</label></td>
					<td class="fieldValue"><input class="input-large" name="homephone" type="text" value="{$fieldValues['phone']}"></td>
				</tr>
				<tr>
					<td class="fieldLabel"><label>Адрес Email</label></td>
					<td class="fieldValue"><input class="input-large" name="email" data-validation-engine="validate[custom[email]]" type="email" value="{$fieldValues['email']}"></td>
					<td class="fieldLabel"><label>Ответственный</label></td>
					<td class="fieldValue">
						<select class="chzn-select chzn-done select2" style="width: 220px" name="assigned_user_id" value="{$current_user}">
							<optgroup label="{vtranslate('LBL_USERS')}">
								{foreach key=OWNER_ID item=OWNER_NAME from=$USERLIST}
                                    <option value="{$OWNER_ID}" data-picklistvalue="{$OWNER_NAME}" {if $current_user eq $OWNER_ID} selected {/if}>
                                    {$OWNER_NAME}
                                    </option>
								{/foreach}
							</optgroup>
                        </select>
					</td>
				</tr>
				<tr>
					<td class="fieldLabel"><label>Самовывоз</label></td>
					<td class="fieldValue"><input class="input-large" name="samov" type="checkbox" {if $fieldValues['cf_840'] == 1}checked="checked"{/if}></td>
					<td class="fieldLabel"><label>Дата доставки</label></td>
					<td class="fieldValue"><input class="input-large dateRange" style="width: 210px" data-inputmask="'mask': '99-99-9999'" name="cf_650" type="text" value="{if empty($fieldValues['cf_841'])}{date('d-m-Y')}{else}{date('d-m-Y', strtotime($fieldValues['cf_841']))}{/if}"></td>				
				</tr>
				<tr {if $fieldValues['cf_840'] == 0}style="display: none;"{/if} class="jsSamovTime">
					<td class="fieldLabel"><label>Время самовывоза</label></td>
					<td class="fieldValue" colspan="3">
						<span>С </span>
						<select class="chzn-select chzn-done checkTimeJs" name="timedostsam" style="width: 50px">
							<option value="07">07</option>
							<option value="08">08</option>
							<option value="09">09</option>
							<option value="10">10</option>
							<option value="11">11</option>
							<option value="12">12</option>
							<option value="13">13</option>
							<option value="14">14</option>
							<option value="15">15</option>
							<option value="16">16</option>
							<option value="17">17</option>
							<option value="18">18</option>
							<option value="19">19</option>
							<option value="20">20</option>
							<option value="21">21</option>
							<option value="22">22</option>
							<option value="23">23</option>
							<option value="00">00</option>
							<option value="01">01</option>
							<option value="02">02</option>
							<option value="03">03</option>
							<option value="04">04</option>
							<option value="05">05</option>
							<option value="06">06</option>
						</select>
						<span>&nbsp;:&nbsp;</span>
						<select class="chzn-select chzn-done checkTimeJs" name="timedostminutesam" style="width: 50px">
							<option value="00">00</option>
							<option value="15">15</option>
							<option value="30">30</option>
							<option value="45">45</option>
						</select>
						<span>&nbsp;&nbsp;&nbsp;&nbsp;До </span>
						<select class="chzn-select chzn-done checkTimeJs" name="timedostfromsam" style="width: 50px">
							<option value="07">07</option>
							<option value="08" selected>08</option>
							<option value="09">09</option>
							<option value="10">10</option>
							<option value="11">11</option>
							<option value="12">12</option>
							<option value="13">13</option>
							<option value="14">14</option>
							<option value="15">15</option>
							<option value="16">16</option>
							<option value="17">17</option>
							<option value="18">18</option>
							<option value="19">19</option>
							<option value="20">20</option>
							<option value="21">21</option>
							<option value="22">22</option>
							<option value="23">23</option>
							<option value="00">00</option>
							<option value="01">01</option>
							<option value="02">02</option>
							<option value="03">03</option>
							<option value="04">04</option>
							<option value="05">05</option>
							<option value="06">06</option>
						</select>
						<span>&nbsp;:&nbsp;</span>
						<select class="chzn-select chzn-done checkTimeJs" name="timedostminutefromsam" style="width: 50px">
							<option value="00">00</option>
							<option value="15">15</option>
							<option value="30">30</option>
							<option value="45">45</option>
						</select>
					</td>
				</tr>
			</table>
			<span class="samovJs" {if $fieldValues['cf_840'] == 1}style="display: none" js="hide"{/if}>
				<h4><img src="layouts/vlayout/modules/Leads/resources/img/Vendors.png" style="position: relative; top: 10px;"> Доставка</h4><br>
				<table class="massEditTable table table-bordered">
					<tr>
						<td class="fieldLabel"><label>Время доставки</label></td>
						<td class="fieldValue" colspan="3">
							<span>С </span>
							<select class="chzn-select chzn-done checkTimeJs" name="timedost" style="width: 50px">
								<option value="07">07</option>
								<option value="08">08</option>
								<option value="09">09</option>
								<option value="10">10</option>
								<option value="11">11</option>
								<option value="12">12</option>
								<option value="13">13</option>
								<option value="14">14</option>
								<option value="15">15</option>
								<option value="16">16</option>
								<option value="17">17</option>
								<option value="18">18</option>
								<option value="19">19</option>
								<option value="20">20</option>
								<option value="21">21</option>
								<option value="22">22</option>
								<option value="23">23</option>
								<option value="00">00</option>
								<option value="01">01</option>
								<option value="02">02</option>
								<option value="03">03</option>
								<option value="04">04</option>
								<option value="05">05</option>
								<option value="06">06</option>
							</select>
							<span>&nbsp;:&nbsp;</span>
							<select class="chzn-select chzn-done checkTimeJs" name="timedostminute" style="width: 50px">
								<option value="00">00</option>
								<option value="15">15</option>
								<option value="30">30</option>
								<option value="45">45</option>
							</select>
							<span>&nbsp;&nbsp;&nbsp;&nbsp;До </span>
							<select class="chzn-select chzn-done timeselectJs checkTimeJs" name="timedostfrom" style="width: 50px" value="08">
								<option value="07">07</option>
								<option value="08" selected>08</option>
								<option value="09">09</option>
								<option value="10">10</option>
								<option value="11">11</option>
								<option value="12">12</option>
								<option value="13">13</option>
								<option value="14">14</option>
								<option value="15">15</option>
								<option value="16">16</option>
								<option value="17">17</option>
								<option value="18">18</option>
								<option value="19">19</option>
								<option value="20">20</option>
								<option value="21">21</option>
								<option value="22">22</option>
								<option value="23">23</option>
								<option value="00">00</option>
								<option value="01">01</option>
								<option value="02">02</option>
								<option value="03">03</option>
								<option value="04">04</option>
								<option value="05">05</option>
								<option value="06">06</option>
							</select>
							<span>&nbsp;:&nbsp;</span>
							<select class="chzn-select chzn-done timeselectJs checkTimeJs" name="timedostminutefrom" style="width: 50px">
								<option value="00">00</option>
								<option value="15">15</option>
								<option value="30">30</option>
								<option value="45">45</option>
							</select>
						</td>
					</tr>
					<tr>
						<td class="fieldLabel"><label>Дедлайн</label></td>
						<td class="fieldValue" colspan="3">
							<span>&nbsp;&nbsp;&nbsp;</span>
							<select class="chzn-select chzn-done timeselectJs checkTimeJs" name="timededline" style="width: 50px">
								<option value="07">07</option>
								<option value="08" selected>08</option>
								<option value="09">09</option>
								<option value="10">10</option>
								<option value="11">11</option>
								<option value="12">12</option>
								<option value="13">13</option>
								<option value="14">14</option>
								<option value="15">15</option>
								<option value="16">16</option>
								<option value="17">17</option>
								<option value="18">18</option>
								<option value="19">19</option>
								<option value="20">20</option>
								<option value="21">21</option>
								<option value="22">22</option>
								<option value="23">23</option>
								<option value="00">00</option>
								<option value="01">01</option>
								<option value="02">02</option>
								<option value="03">03</option>
								<option value="04">04</option>
								<option value="05">05</option>
								<option value="06">06</option>
							</select>
							<span>&nbsp;:&nbsp;</span>
							<select class="chzn-select chzn-done timeselectJs checkTimeJs" name="timededlineminute" style="width: 50px">
								<option value="00">00</option>
								<option value="15">15</option>
								<option value="30">30</option>
								<option value="45">45</option>
							</select>
						</td>
					</tr>
					<tr>
						<td class="fieldLabel"><label>К точному времени</label></td>
						<td class="fieldValue"><input class="input-large" name="timeselect" type="checkbox"></td>
						<td class="fieldLabel"><label>Себе</label></td>
						<td class="fieldValue"><input class="input-large" name="setmefunct" type="checkbox" {if empty($fieldValues['cf_836'])}checked="checked"{/if}></td>				
					</tr>
					<tr>
						<td class="fieldLabel"><label>Имя получателя</label></td>
						<td class="fieldValue"><input class="input-large" name="namepolychatel" type="text" {if empty($fieldValues['cf_836'])}readonly{/if} value="{$fieldValues['cf_836']}"></td>
						<td class="fieldLabel"><label>Телефон получателя</label></td>
						<td class="fieldValue"><input class="input-large" name="phone" type="text" {if empty($fieldValues['cf_836'])}readonly{/if} value="{$fieldValues['cf_837']}"></td>
					</tr>
					<tr>
						<td class="fieldLabel"><label>Адрес</label></td>
						<td class="fieldValue" colspan="3"><input class="input-xxlarge" style="width: 586px;" name="lane" type="text" value="{$fieldValues['lane']}"></td>
					</tr>
					<tr>
						<td class="fieldLabel"><label>Примечание к адресу</label></td>
						<td class="fieldValue" colspan="3"><input class="input-xxlarge" style="width: 586px;" name="aboutme" type="text"></td>
					</tr>
				</table>
			</span>
			<h4><img src="layouts/vlayout/modules/Leads/resources/img/Potentials.png" style="position: relative; top: 10px;"> Оплата</h4><br>
			<table class="massEditTable table table-bordered">
				<tr>
					<td class="fieldLabel"><label><span class="redColor">*</span>Магазин доставки</label></td>
					<td class="fieldValue">
						<input type="hidden" style="width: 220px;" name="magazin" value="{$fieldValues['cf_847']}"/>
						{* PINstudio begin @Denis #red-269 *}
						<div class="row-fluid input-append">
							<select class="chzn-select chzn-done select2" style="width: 220px" name="shopid" value="{$fieldValues['shopid']}" data-validation-engine="validate[required]">
								<option value="-">-</option>
								{foreach $shopList as $shop}
									<option {if $shop['name'] == $fieldValues['cf_847']}selected="selected"{/if} value="{$shop['id']}">{$shop['name']}</option>
								{/foreach}
							</select>
							<span id="getShops" class="add-on popupBtn">
								<i class="icon-search" title="Выбрать"></i>
							</span>
						</div>
						{* PINstudio end *}
					</td>
					<td class="fieldLabel"><label><span style="display: none" id="cityLabel" class="redColor">*</span>Город</label></td>
					<td class="fieldValue"><input class="input-large" name="city" type="text" value="{$fieldValues['city']}{if empty($fieldValues['city'])}Санкт-Петербург{/if}"></td>
				</tr>
				<tr>
					<td class="fieldLabel"><label><span class="redColor">*</span>Бренд</label></td>
					<td class="fieldValue">
						<select class="chzn-select chzn-done select2" style="width: 220px" name="brend" value="{$fieldValues['cf_838']}" data-validation-engine="validate[required]">
							{foreach from=$brands item=name key=id}
								<option {if $name == $fieldValues['cf_838']}selected="selected"{/if} value="{$name}">{$name}</option>
							{/foreach}
						</select>
					</td>
					<td class="fieldLabel"><label>Где принят заказ</label></td>
					<td class="fieldValue">
						<select class="chzn-select chzn-done select2" style="width: 220px" name="maptack" {if !empty($fieldValues['cf_845'])}value="Сайт"{else}value="Call-center"{/if}>
							{foreach from=$whereadds item=name key=id}
								<option {if (!empty($fieldValues['cf_845']))}{if $name == 'Сайт'}selected="selected"{/if}{else}{if $name == 'Call-center'}selected="selected"{/if}{/if} value="{$name}">{$name}</option>
							{/foreach}
						</select>
					</td>
				</tr>
				<tr>
					<td class="fieldLabel"><label><span class="redColor">*</span>Тип оплаты</label></td>
					<td class="fieldValue">
						<select class="chzn-select chzn-done select2" style="width: 220px" name="typepay" value="{$fieldValues['cf_839']}" data-validation-engine="validate[required]">
							{foreach from=$payments item=name key=id}
								<option {if mb_strtoupper($name) == mb_strtoupper($fieldValues['cf_839'])}selected="selected"{/if} value="{$name}">{$name}</option>
							{/foreach}
						</select>
					</td>
					<td class="fieldLabel"><label>№ заказа</label></td>
					<td class="fieldValue"><input class="input-large" name="nomerzakaz" type="text" data-inputmask="'mask': '9{literal}{*}{/literal}'" value="{$fieldValues['cf_845']}"></td>
				</tr>
				<tr>
					<td class="fieldLabel"><label>Примечание к оплате</label></td>
					<td class="fieldValue" colspan="3"><input class="input-xxlarge" style="width: 590px;" name="proplata" type="text"></td>
				</tr>
				<tr>
					<td class="fieldLabel"><label>Для флориста</label></td>
					<td class="fieldValue" colspan="3"><textarea class="input-xxlarge" name="description" rows="4" style="resize: none; width: 600px;">{$fieldValues['description']}</textarea></td>
				</tr>
			</table>
			<h4><img src="layouts/vlayout/modules/Leads/resources/img/Products.png" style="position: relative; top: 10px;"> Товар</h4><br>
			<table class="massEditTable table table-bordered" id="products">
				<thead>
					<td class="fieldLabel" style="width: 4%">{* <span class="icon-remove"></span> *}</td>
					<td class="fieldLabel"><label>Наименование</label></td>
					<td class="fieldLabel"><label>Описание</label></td>
					<td class="fieldLabel" style="width: 42px;"><label>Кол-во</label></td>
					<td class="fieldLabel" style="width: 42px;"><label>Цена</label></td>
					<td class="fieldLabel" style="width: 48px;"><label>Скидка %</label></td>
				</thead>
				<tbody>
                    {**}
				</tbody>
			</table>
			<br>
			<button type="button" class="btn addButton" id="addDynaProduct"><img src="layouts/vlayout/skins/images/Products.png"></img></button>
			<button type="button" class="btn addButton" id="addDynaService"><img src="layouts/vlayout/skins/images/Services.png"></img></button>
			<button type="button" class="btn addButton" id="addProduct"><i class="icon-plus"></i><strong>{vtranslate('LBL_ADD_PRODUCT')}</strong></button>
			<button type="button" class="btn addButton" id="addService"><i class="icon-plus"></i><strong>{vtranslate('LBL_ADD_SERVICE')}</strong></button>
			{* PINstudio @DK*}
			<table class="massEditTable table" id="delivery_cost" {if $fieldValues['cf_840'] != 0}style="display: none;"{/if}>
				<tbody>
					<tr>
						<td style="width: 75%;"><img src="layouts/vlayout/modules/Leads/resources/img/Delivery.png" style="float:right"/></td>
						<td class="fieldLabel">
							<label>Стоимость доставки</label>
						</td>
						<td class="fieldValue">
							<input class="input-mini" style="" name="s_h_amount" data-inputmask="'mask': '9{literal}{*}{/literal}'" type="text" value="0">
						</td>
					</tr>
				</tbody>
			</table>
			{* PINstudio end *}
			<h2 id="products-summ" align="right">Итого: <span>0</span> руб.</h2>
			<table class="massEditTable table table-bordered">
				<tr>
					<td class="fieldLabel"><label>Оплачено</label> <small>(Создайте платеж)</small></td>
					<td class="fieldValue">
                        <input id="paystatus" type="text" name="paystatus" readonly class="input-large currencyField" value="{$PAID}">
                    </td>
					<td class="fieldLabel"><label>Доплата</label></td>
					<td class="fieldValue"><input class="input-large" readonly name="doplata" type="text"></td>
				</tr>
			</table>
		</div>
	</div>
	<div class="modal-footer quickCreateActions">
		<a class="cancelLink cancelLinkContainer pull-right" type="reset" data-dismiss="modal">Отмена</a>
		<button class="btn btn-success" id="convert-btn"><strong>Сохранить</strong></button>
	</div>
</form>
</div>
<script>
var shp = {$LOOKUP};
var timeValues = {$TIMES};
</script>
<script type="text/javascript" src="layouts/vlayout/modules/Leads/resources/ConvertLeadToSalesOrder.js"></script>
{/strip}
