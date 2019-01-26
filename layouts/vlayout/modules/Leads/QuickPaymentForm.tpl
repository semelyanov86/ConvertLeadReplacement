{literal}
<style>
.paymentModal * {box-sizing: border-box;}
.paymentModal {padding: 10px 20px; min-width: 400px;}
.data td { min-width: 150px; text-align: right; padding: 15px 0px 10px 10px;}
.data td:nth-of-type(2n) {min-width: 350px; text-align: inherit;}
.ctrls {display: flex; justify-content: flex-end; align-items: center;}
.ctrls .cancelLink {padding: 6px 25px;}
.ctrls .cancelLink:hover {outline: 1px solid tomato;}
.paymentModal .data .select2 {width:100%}
</style>
{/literal}
<div class="paymentModal">
    <h3>Обращение: Создать Платеж</h3>
    <hr/>
    <form id="quickPay">
    <table class="data">
    <tr>
        <td>
            <label>Тип платежа</label>
        </td>
        <td>
            <select name="paytype" class="chzn-select chzn-done select2" data-validation-engine="validate[required]">
            {foreach from=$PAYTYPE item=name key=id}
                <option value="{$name}">{$name}</option>
            {/foreach}
        </select>
        </td>
    </tr>
    <tr>
        <td>
            <label>Сумма</label>
        </td>
        <td>
            {*TODO inputmask*}
            <input type="text" name="payamount" class="select2-search" data-validation-engine="validate[required]" placeholder="0"/>
        </td>
    </tr>
    </table>
    </form>
    <hr/>
    <div class="ctrls">
        <button id="createPayment" class="btn btn-success" type="button" data-mode="add"><strong>Создать Платеж</strong></button>
        <a class="cancelLink cancelLinkContainer" type="reset" data-dismiss="modal">Отменить</a>
    </div>
</div>