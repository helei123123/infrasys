<?php
        $this->Html->script('elements/form.js',false);        // Add special effect for form

        $this->Html->css('site/elements/breadcrumb.css',null,array('inline'=>false));

        $variable=$posConfig['PosConfig']['scfg_variable'];
        ?>

<style type="text/css">
<!--
        table.cell-table{
        border:1px solid;
        border-collapse:collapse;
        }
        .select-color{
        display:inline-block;
        width:16px;
        height:16px;
        border:1px solid #444;
        vertical-align:middle;
        }
        -->
</style>

<script type="text/javascript">

        $(document).ready(function(){

        //	Delete button event
        $('#DeleteButton').click(function(){
        if(isFormLoading())
        return false;    //	abort functions when loading content

        confirmDialog("<?php echo __('confirm_to_delete'); ?> ?","","","",function(){
        setFormQuit('deleting');
        location.href="<?php echo $this->Html->url('/admin'.$langPath.'pos/location_configs/delete/'.$posConfig['PosConfig']['scfg_id'].$backUrlParamsStr); ?>";
        return false;
        });
        return false;
        });
        });
</script>

<?php
        ///////////////////////////////////////////////////////////////////////////////
        // Show basic information
        echo'<div class="zone-body">';    //	Start zone-body

        echo $this->element('frame_basic_top',array(
        'title'=>__('view_detail').' - '.__d('pos','config_by_location').' : '.(empty($posConfig)?'':__d('pos',$variableKeyMapping[$variable])),
        'color'=>'grey',
        'minHeight'=>'580px',
        )
        );

        ///////////////////////////////////////////////////////////////////////////
        //	Create breadcrumb
        $breadCrumbLists=array(
        array('name'=>__d('pos','config_by_location'),'link'=>$this->Html->url('/admin'.$langPath.'pos/location_configs/listing'.$backUrlParamsStr)),
        array('name'=>__d('pos',$variableKeyMapping[$variable]),'link'=>$this->Html->url('/admin'.$langPath.'pos/location_configs/config/'.$variable.$backUrlParamsStr)),
        array('name'=>$posConfig['PosConfig']['scfg_record'],'link'=>'')
        );
        echo $this->element('breadcrumb',array('lists'=>$breadCrumbLists));

        ///////////////////////////////////////////////////////////////////////////
        //	Start the view area
        echo'<table class="sheet-basic sheet-border" width="99%">';

        //	Apply to
        $locationOpts=array(
        ''=>__d('pos','all_locations'),
        'shop'=>__d('outlet','shop'),
        'outlet'=>__d('outlet','outlet'),
        'station'=>__d('pos','station')
        );
        echo'<tr>'
        .'<th width="20%">'.__d('pos','apply_to').'</th>'
        .'<td style="font-size:14px; font-weight:bold; letter-spacing:1px;">'.(isset($locationOpts[$posConfig['PosConfig']['scfg_by']])?$locationOpts[$posConfig['PosConfig']['scfg_by']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;').'</td>'
        .'</tr>';

        //	Shop/Outlet/Station
        switch($posConfig['PosConfig']['scfg_by']){
        case'outlet':
        echo'<tr>'
        .'<th>'.__d('outlet','outlet').'</th>'
        .'<td style="font-size:14px; font-weight:bold; letter-spacing:1px;">'.$posConfig['PosConfig']['scfg_record'].'</td>'
        .'</tr>';
        break;
        case'shop':
        echo'<tr>'
        .'<th>'.__d('outlet','shop').'</th>'
        .'<td style="font-size:14px; font-weight:bold; letter-spacing:1px;">'.$posConfig['PosConfig']['scfg_record'].'</td>'
        .'</tr>';
        break;
        case'station':
        echo'<tr>'
        .'<th>'.__d('pos','station').'</th>'
        .'<td style="font-size:14px; font-weight:bold; letter-spacing:1px;">'.$posConfig['PosConfig']['scfg_record'].'</td>'
        .'</tr>';
        break;
default:
        break;
        }

        //	Value
        $value=$posConfig['PosConfig']['scfg_value'];
        switch($variable){
        case'fast_food_auto_takeout':
        case'Payment_amount_even_and_odd_indicator':
        case'fast_food_not_auto_waive_service_charge':
        case'ordering_panel_input_numpad':
        case'ordering_panel_show_price':
        case'not_check_stock':
        case'support_numeric_plu_only':
        case'calc_inclusive_tax_ref_by_check_total':
        case'ordering_panel_not_show_image':
        case'self_kiosk_set_menu_no_gudiance':
        case'not_allow_open_new_check':
        case'loyalty_member':
        case'reset_soldout_at_daily_close':
        case'skip_print_check_for_payment':
        case'void_guest_check_image':
        case'ask_table_section':
        case'allow_change_item_quantity_after_send':
        case'enlarge_ordering_basket':
        case'not_allow_to_order_when_zero_stock':
        case'split_table_with_keeping_cover':
        case'turn_off_testing_printer':
        case'show_table_size':
        case'show_floor_plan_after_switch_user':
        case'include_previous_same_level_discount':
        case'member_discount_not_validate_member_module':
        case'adjust_payments_reprint_receipt':
        case'adjust_tips_reprint_receipt':
        case'enable_autopayment_by_default_payment':
        case'show_page_up_and_down_button_for_list':
        case'update_master_table_status':
        case'support_mixed_revenue_non_revenue_payment':
        case'support_continuous_printing':
        case'hide_cashier_panel_numpad':
        case'display_admin_mode_only':
        case'hide_check_detail_bar':
        case'resequence_discount_list':
        case'ordering_basket_show_add_waive_tax_sc_info':
        case'hide_station_info_bar':
        case'stay_in_cashier_when_interface_payment_failed':
        case'enable_user_to_check_print_queue_status_if_alert_message':
        case'require_password_after_login_by_swipe_card':
        case'support_time_charge_item':
        $yesNoOpts=array('true'=>__('yes'),'false'=>__('no'));
        $value=isset($yesNoOpts[$value])?$yesNoOpts[$value]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;';
        break;
        case'new_check_auto_functions':
        case'pay_check_auto_functions':
        $supportOpts=array('n'=>__('no'),'y'=>__('yes'));
        if($variable=='new_check_auto_functions')
        $failHandlingOptions=array('q'=>__d('pos','force_to_quit_check'),'c'=>__d('pos','continue_operation'));
        else
        $failHandlingOptions=array('b'=>__d('pos','back_to_ordering_panel'),'c'=>__d('pos','continue_operation'));
        $mappings=json_decode($value,true);
        $value='<table class="cell-table" width="99%">';
        if($variable=='new_check_auto_functions')
        $value.='<tr><td>'.__d('pos','support_for_split_check').'</td><td colspan="2">'.(isset($mappings['support_for_split_check'])&&isset($supportOpts[$mappings['support_for_split_check']])?$supportOpts[$mappings['support_for_split_check']]:__('no')).'</td></tr>';
        $value.='<tr><th>'.__d('pos','function').'</th><th>'.__d('pos','failure_handling').'</th><th>'.__('seq').'</th></tr>';
        if(isset($mappings['function']))
        $mappings=$mappings['function'];
        for($i=0;$i<count($mappings);$i++){
        $value.='<tr>'
        .'<td>'.(isset($posFunctions[$mappings[$i]['function_key']])?$posFunctions[$mappings[$i]['function_key']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;').'</td>'
        .'<td>'.(isset($failHandlingOptions[$mappings[$i]['fail_handling']])?$failHandlingOptions[$mappings[$i]['fail_handling']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;').'</td>'
        .'<td>'.$mappings[$i]['seq'].'</td>'
        .'</tr>';
        }
        $value.='</table>';
        break;
        case'display_check_extra_info_in_ordering_basket':
        $supportOpts=array('n'=>__('no'),'y'=>__('yes'));
        $mappings=json_decode($value,true);
        $value='<table class="cell-table" width="99%">';
        $value.='<tr><td width="30%">'.__d('pos','always_reset_extra_info_window_size').'</td><td width="70%">'.(isset($mappings['always_reset_extra_info_window_size'])&&isset($supportOpts[$mappings['always_reset_extra_info_window_size']])?$supportOpts[$mappings['always_reset_extra_info_window_size']]:__('no')).'</td></tr>';
        $value.='<tr><th colspan="2">'.__d('pos','display_information').'</th>';
        if(isset($mappings['check_extra_info_list']))
        $mappings=$mappings['check_extra_info_list'];
        for($i=0;$i<count($mappings);$i++){
        $value.='<tr>'
        .'<td colspan="2">'.(isset($mappings[$i]['check_extra_info'])?__d('pos',$mappings[$i]['check_extra_info']):'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;').'</td>'
        .'</tr>';
        }
        $value.='</table>';
        break;
        case'gratuity_setting':
        $mappings=json_decode($value,true);
        $mappings=$mappings['cover_control'];
        $value='<table class="cell-table" width="99%">';
        $value.='<tr><th>'.__d('pos','gratuity').'</th><th>'.__d('pos','min_cover').'</th><th>'.__d('pos','max_cover').'</th></tr>';
        for($i=0;$i<count($mappings);$i++){
        $value.='<tr>'
        .'<td width="56%">'.(isset($posGratuities[$mappings[$i]['grat_id']])?$posGratuities[$mappings[$i]['grat_id']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;').'</td>'
        .'<td width="20%">'.$mappings[$i]['min_cover'].'</td>'
        .'<td width="20%">'.$mappings[$i]['max_cover'].'</td>'
        .'</tr>';
        }
        $value.='</table>';
        break;
        case'item_function_list':
        $mappings=json_decode($value,true);
        $value='<table class="cell-table" width="99%">';
        $value.='<tr><th>'.__d('pos','function').'</th></tr>';
        for($i=0;$i<count($mappings);$i++){
        $value.='<tr>'
        .'<td>'.(isset($posFunctions[$mappings[$i]['function_key']])?$posFunctions[$mappings[$i]['function_key']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;').'</td>'
        .'</tr>';
        }
        $value.='</table>';
        break;
        case'support_partial_payment':
        $values=json_decode($value,true);
        $yesNoOpts=array('y'=>__('yes'),''=>__('no'));
        $value='<table class="cell-table" width="99%">'
        .'<tr>'
        .'<td width="40%">'
        .__d('pos','support_partial_payment').' :'
        .'</td>'
        .'<td>'
        .(isset($yesNoOpts[$values['support_partial_payment']])?$yesNoOpts[$values['support_partial_payment']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>'
        .'<tr>'
        .'<td width="40%">'
        .__d('pos','continue_to_pay_after_settling_partial_payment').' :'
        .'</td>'
        .'<td>'
        .(isset($values['continue_to_pay_after_settling_partial_payment'])&&isset($yesNoOpts[$values['continue_to_pay_after_settling_partial_payment']])?$yesNoOpts[$values['continue_to_pay_after_settling_partial_payment']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>'
        .'<tr>'
        .'<td width="50%">'
        .__d('pos','print_receipt_only_when_finish_all_payment').' :'
        .'</td>'
        .'<td>'
        .(isset($values['print_receipt_only_when_finish_all_payment'])&&isset($yesNoOpts[$values['print_receipt_only_when_finish_all_payment']])?$yesNoOpts[$values['print_receipt_only_when_finish_all_payment']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>'
        .'<tr>'
        .'<td width="40%">'
        .__d('pos','void_all_payment_after_release_payment').' :'
        .'</td>'
        .'<td>'
        .(isset($values['void_all_payment_after_release_payment'])&&isset($yesNoOpts[$values['void_all_payment_after_release_payment']])?$yesNoOpts[$values['void_all_payment_after_release_payment']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>'
        .'</table>';
        break;
        case'business_hour_warn_level':
        case'open_table_screen_mode':
        case'apply_discount_restriction':
        case'double_check_discount_alert':
        case'audit_log_level':
        case'bday_custom_data1_type':
        case'auto_close_cashier_panel':
        case'ordering_timeout_option':
        case'reset_stock_quantity_at_daily_close':
        case'ask_table_with_advance_mode':
        case'item_stock_operation_input_mode':
        case'cashier_settlement_mode':
        case'ordering_basket_item_grouping_method':
        case'dutymeal_limit_reset_period':
        case'on_credit_limit_reset_period':
        case'fast_food_not_print_receipt':
        case'fine_dining_not_print_receipt':
        case'ordering_basket_toggle_consolidate_items_grouping_method':
        if($variable=='business_hour_warn_level')
        $selectOpts=array(0=>__d('pos','no_warning'),1=>__d('pos','pos_not_allowed'),2=>__d('pos','allowed_but_password_needed'),3=>__d('pos','allowed_but_password_needed_warning_at_login_only'));
        else if($variable=='open_table_screen_mode')
        $selectOpts=array(0=>__d('pos','use_floor_plan'),1=>__d('pos','input_table_no'),2=>__d('pos','table_mode'));
        else if($variable=='apply_discount_restriction')
        $selectOpts=array(0=>__d('pos','no_restriction'),1=>__d('pos','only_apply_one_discount'),2=>__d('pos','apply_item_discount_and_one_check_discount'),3=>__d('pos','apply_check_discount_to_items_with_no_item_discount'));
        else if($variable=='audit_log_level')
        $selectOpts=array(0=>__d('audit_log','disable_log'),1=>__d('audit_log','enable_log'));
        else if($variable=='bday_custom_data1_type')
        $selectOpts=array(''=>__('no_use'),'t'=>__d('pos','thailand_tax_invoice_number'));
        else if($variable=='auto_close_cashier_panel')
        $selectOpts=array(0=>__d('pos','none'),1=>__d('pos','fine_dining'),2=>__d('pos','fast_food_mode'),3=>__('all'));
        else if($variable=='double_check_discount_alert')
        $selectOpts=array(0=>__d('pos','no_warning'),1=>__d('pos','alert_is_prompted_if_apply_multiple_check_discount'),2=>__d('pos','alert_is_prompted_if_same_check_discount_is_applied'));
        else if($variable=='ordering_timeout_option')
        $selectOpts=array(0=>__d('pos','allow_continue_or_quit'),1=>__d('pos','quit_directly'));
        else if($variable=='reset_stock_quantity_at_daily_close')
        $selectOpts=array(0=>__d('pos','does_not_reset'),1=>__d('pos','reset_to_zero'),2=>__d('pos','delete_item_count_record'));
        else if($variable=='ask_table_with_advance_mode')
        $selectOpts=array(0=>__d('pos','digit_table_number'),1=>__d('pos','alphanumeric_table_number'));
        else if($variable=='item_stock_operation_input_mode')
        $selectOpts=array(0=>__d('pos','replace'),1=>__d('pos','add_on'));
        else if($variable=='cashier_settlement_mode')
        $selectOpts=array(''=>__d('user','user'),'o'=>__d('outlet','outlet'));
        else if($variable=='ordering_basket_item_grouping_method')
        $selectOpts=array('l'=>__d('pos','combine_last_item_in_ordering_basket'),'a'=>__d('pos','combine_items_in_ordering_basket'));
        else if($variable=='dutymeal_limit_reset_period'||$variable=='on_credit_limit_reset_period')
        $selectOpts=array('m'=>__('monthly'),'d'=>__('daily'));
        else if($variable=='fast_food_not_print_receipt'||$variable=='fine_dining_not_print_receipt')
        $selectOpts=array(''=>__('no'),'false'=>__('no'),'y'=>__('yes'),'true'=>__('yes'),'o'=>__d('pos','ask_option_to_print_receipt'),'e'=>__d('pos','send_email_receipt_only'));
        else if($variable=='ordering_basket_toggle_consolidate_items_grouping_method')
        $selectOpts=array('o'=>__d('pos','grouping_old_items_only'),'s'=>__d('pos','grouping_old_and_new_items_separately'),'a'=>__d('pos','grouping_both_old_and_new_items'));
        $value=isset($selectOpts[$value])?$selectOpts[$value]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;';
        break;
        case'call_number_input_setting':
        $temp=array(
        'method'=>$value,
        'image_file_for_scan_mode'=>'',
        );

        $values=is_array(json_decode($value,true))?json_decode($value,true):$temp;

        $methodOpts=array('m'=>__d('pos','manual_input'),'s'=>__d('pos','scan'));
        $value='<table class="cell-table" width="99%">'
        //	Method
        .'<tr>'
        .'<td width="40%">'
        .__d('pos','input_method') // new word: 'input_method'
        .'</td>'
        .'<td>'
        .(isset($values['method'])&&isset($methodOpts[$values['method']])?$methodOpts[$values['method']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>';

        //	File image
        if($values['method']=='s'){
        $value.='<tr>'
        .'<td>'
        .__d('pos','image_file_for_scan_mode') // new word: 'image_file_for_scan_mode'
        .'</td>'
        .'<td>'
        .($values['method']=='s'?$values['image_file_for_scan_mode']:'')
        //.(isset($values['image_file_for_scan_mode']) ? $values['image_file_for_scan_mode'] : '')
        .'</td>'
        .'</tr>';
        }

        $value.='</table>';
        break;
        case'payment_check_types':
        $mappings=json_decode($value,true);
        $mappings=$mappings['mapping'];
        $value='<table class="cell-table" width="99%">';
        $value.='<tr><th>'.__d('pos','payment_method').'</th><th>'.__d('pos','custom_type').'</th></tr>';
        for($i=0;$i<count($mappings);$i++){
        $value.='<tr>'
        .'<td>'.(isset($posPaymentMethods[$mappings[$i]['paym_id']])?$posPaymentMethods[$mappings[$i]['paym_id']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;').'</td>'
        .'<td>'.(isset($posCustomTypes[$mappings[$i]['ctyp_id']])?$posCustomTypes[$mappings[$i]['ctyp_id']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;').'</td>'
        .'</tr>';
        }
        $value.='</table>';
        break;
        case'auto_switch_from_pay_result_to_starting_page_time_control':
        case'ordering_timeout':
        $value.=' ('.__('seconds').')';
        break;
        case'time_control_to_open_next_check_by_member':
        $value.=' ('.__('minutes').')';
        break;
        case'menu_mode':
        $value.=' ('.__d('pos','default_table_number_for_menu_mode').')';
        break;
        case'copies_of_receipt':
        $value.=' ('.__d('pos','copies_of_receipt').')';
        break;
        case'check_calling_number':
        case'item_calling_number':
        case'payment_running_number':
        case'item_print_queue_running_number':
        $values=json_decode($value,true);
        $modeOpts=array(''=>__('all'),'t'=>__d('pos','take_away'),'d'=>__d('pos','fine_dining'),'f'=>__d('pos','fast_food'));
        $supportOpts=array(''=>__('no'),'y'=>__('yes'));
        $methodOpts=array('r'=>__d('pos','reset_everyday'),'c'=>__d('pos','carry_forward'));
        $value='<table class="cell-table" width="99%">'
        //	Support
        .'<tr>'
        .'<td width="25%">'
        .__d('pos','support')
        .'</td>'
        .'<td>'
        .(isset($values['support'])&&isset($supportOpts[$values['support']])?$supportOpts[$values['support']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>';
        if($variable=='check_calling_number'||$variable=='item_calling_number'||$variable=='payment_running_number')
        //	Pool Code
        $value.='<tr>'
        .'<td>'
        .__('code')
        .'</td>'
        .'<td>'
        .(isset($values['pool_code'])?$values['pool_code']:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>';
        //	Method
        $value.='<tr>'
        .'<td>'
        .__d('pos','running_method')
        .'</td>'
        .'<td>'
        .(isset($values['method'])&&isset($methodOpts[$values['method']])?$methodOpts[$values['method']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>';
        if($variable=='check_calling_number'||$variable=='item_calling_number')
        //	Mode
        $value.='<tr>'
        .'<td>'
        .__d('pos','running_mode')
        .'</td>'
        .'<td>'
        .(isset($values['mode'])&&isset($modeOpts[$values['mode']])?$modeOpts[$values['mode']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>';

        if($variable=='payment_running_number'){
        //	Single number for check
        $singleNoOpts=array('true'=>__('yes'),'false'=>__('no'));
        $value.='<tr>'
        .'<td>'
        .__d('pos','one_number_apply_on_one_check')
        .'</td>'
        .'<td>'
        .(isset($values['single_number_for_check'])&&isset($singleNoOpts[$values['single_number_for_check']])?$singleNoOpts[$values['single_number_for_check']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>';

        //	Payment methods
        $reusablePaymentIds=explode(",",isset($values['reusable_payment_ids'])?$values['reusable_payment_ids']:"");

        $value.='<tr>'
        .'<td>'
        .__d('pos','apply_to_payment_methods')
        .'</td>'
        .'<td>'
        .'<table width="100%" class="no-border">';
        foreach($posPaymentMethods as $id=>$name)
        $value.='<tr>'
        .'<td width="50%">'
        .__k($name,30)
        .'</td>'
        .'<td>'
        .(in_array($id,$reusablePaymentIds)?__d('pos','reuse'):__d('pos','not_reuse'))
        .'</td>'
        .'</tr>';

        $value.='</table>'
        .'</td>'
        .'</tr>';
        }
        if($variable=='item_print_queue_running_number'){
        //Apply to item print queue
        $value.='<tr>'
        .'<td>'
        .__d('pos','apply_to_item_print_queue').' :'
        .'</td>'
        .'<td>';

        $printQueues=isset($values['print_queues'])?$values['print_queues']:array();
        foreach($printQueues as $printQueue){
        if(isset($printQueueNames[$printQueue['id']]))
        $value.=__k($printQueueNames[$printQueue['id']],40).' ('.$printQueue['pool_code'].')'.'<br />';
        }
        $value.='</td>'
        .'</tr>';
        }
        $value.='</table>';
        break;
        case'open_check_setting':
        $values=json_decode($value,true);
        $supportOpts=array('n'=>__('no'),'y'=>__('yes'));
        $askTableNoOpts=array('y'=>__('yes'),'n'=>__('no'));
        $askGuestNoOpts=array('y'=>__('yes'),'n'=>__('no'));
        $value='<table class="cell-table" width="100%">'
        //	Support
        .'<tr>'
        .'<td width="25%">'
        .__d('pos','support')
        .'</td>'
        .'<td>'
        .(isset($values['support'])&&isset($supportOpts[$values['support']])?$supportOpts[$values['support']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>';

        // Period
        $periodIds=explode(",",isset($values['period_ids'])?$values['period_ids']:"");
        if($posConfig['PosConfig']['scfg_by']=="outlet"){
        $value.='<tr>'
        .'<td>'
        .__d('pos','period')
        .'</td>'
        .'<td>';

        if(!empty($periodIds[0])){
        foreach($periodIds as $periodId)
        if(isset($outletPeriods[$periodId]))
        $value.=__k($outletPeriods[$periodId],30).' '.'<br />';
        }else
        $value.='&lt;&lt;&lt; '.__('not_specified').' &gt;&gt;&gt;';
        $value.='</td>'
        .'</tr>';
        }

        $value.='<tr>'
        .'<td width="25%">'
        .__d('pos','ask_table_number')
        .'</td>'
        .'<td>'
        .(isset($values['ask_table_number'])&&isset($askTableNoOpts[$values['ask_table_number']])?$askTableNoOpts[$values['ask_table_number']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>';

        $value.='<tr>'
        .'<td width="25%">'
        .__d('pos','ask_guest_number')
        .'</td>'
        .'<td>'
        .(isset($values['ask_guest_number'])&&isset($askGuestNoOpts[$values['ask_guest_number']])?$askGuestNoOpts[$values['ask_guest_number']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>';

        $value.='</table>';
        break;
        case'employee_discount_limit':
        $values=json_decode($value,true);
        $modeOpts=array(
        'm'=>__d('pos','monthly_limit')
        );
        $value='<table class="cell-table" width="99%">'
        //	User
        .'<tr>'
        .'<td width="25%">'
        .__d('user','user')
        .'</td>'
        .'<td>'
        .(isset($userOpts[$values['userId']])?$userOpts[$values['userId']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>'
        //	Mode
        .'<tr>'
        .'<td>'
        .__d('pos','discount_mode')
        .'</td>'
        .'<td>'
        .$modeOpts[$values['mode']]
        .'</td>'
        .'</tr>'
        //	Limit
        .'<tr>'
        .'<td>'
        .__d('pos','discount_limit')
        .'</td>'
        .'<td>'
        .$values['limit']
        .'</td>'
        .'</tr>'
        .'</table>';
        break;
        case'payment_process_setting':
        $values=json_decode($value,true);
        $yesNoOpts=array('true'=>__('yes'),'false'=>__('no'));
        $value='<table class="cell-table" width="99%">'
        //	Display Loading Box During Payment
        .'<tr>'
        .'<td>'
        .__d('pos','display_loading_box_during_payment')
        .'</td>'
        .'<td>'
        .(isset($yesNoOpts[$values['display_loading_box_during_payment']])?$yesNoOpts[$values['display_loading_box_during_payment']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>'
        //	Payment Completion Message
        .'<tr>'
        .'<td>'
        .__d('pos','payment_completion_message')
        .'</td>'
        .'<td>'
        .(isset($values['payment_completion_message'])?$values['payment_completion_message']:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>'
        //	Payment Completion Image Name
        .'<tr>'
        .'<td>'
        .__d('pos','payment_completion_image_name')
        .'</td>'
        .'<td>'
        .(isset($values['payment_completion_image_name'])?$values['payment_completion_image_name']:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>'
        .'</table>';
        break;
        case'settlement_count_interval_to_print_guest_questionnaire':
        $values=json_decode($value,true);
        $value='<table class="cell-table" width="99%">'
        //	Interval Count
        .'<tr>'
        .'<td>'
        .__d('pos','settlement_count_interval_to_print_guest_questionnaire')
        .'</td>'
        .'<td>'
        .$values['interval_count']
        .'</td>'
        .'</tr>'
        .'</table>';
        break;
        case'generate_receipt_pdf':
        $values=json_decode($value,true);
        $supportOpts=array(''=>__('no'),'y'=>__('yes'));
        $value='<table class="cell-table" width="99%">'
        //	Support
        .'<tr>'
        .'<td width="25%">'
        .__d('pos','support')
        .'</td>'
        .'<td>'
        .(isset($values['support'])&&isset($supportOpts[$values['support']])?$supportOpts[$values['support']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>'
        //	Password
        .'<tr>'
        .'<td>'
        .__d('pos','password')
        .'</td>'
        .'<td>'
        .$values['password']
        .'</td>'
        .'</tr>'
        //	Export path
        .'<tr>'
        .'<td>'
        .__d('pos','export_path')
        .'</td>'
        .'<td>'
        .$values['path']
        .'</td>'
        .'</tr>'
        .'</table>';
        break;
        case'screen_saver_option':
        $values=json_decode($value,true);
        $value='<table class="no-border" width="99%">'
        //	Timeout
        .'<tr>'
        .'<td width="40%">'
        .__('timeout').' :'
        .'</td>'
        .'<td>'
        .(isset($values['timeout'])?$values['timeout']:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;').' ('.__('minutes').')'
        .'</td>'
        .'</tr>'
        //	Display Content (Color or Outlet Gallery)
        .'<tr>'
        .'<td>'
        .__d('pos','display_content').' :'
        .'</td>'
        .'<td>'
        .(isset($values['display_content'])&&$values['display_content']=="m"?__d('media','photo_gallery'):__d('pos','color'))
        .'</td>'
        .'</tr>'
        //	Color
        .'<tr>'
        .'<td>'
        .__d('pos','color').' :'
        .'</td>'
        .'<td>'
        .(isset($values['color'])?$values['color']:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>'
        //	Transparency
        .'<tr>'
        .'<td>'
        .__d('pos','transparency').' :'
        .'</td>'
        .'<td>'
        .(isset($values['transparency'])?$values['transparency']:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>';
        $value.='</table>';
        break;
        case'table_attribute_mandatory_key':
        $values=json_decode($value,true);
        $supportOpts=array(''=>__('no'),'y'=>__('yes'));
        $value='<table class="cell-table" width="99%">';
        for($i=1;$i<=10;$i++){
        //	key 1 - 10
        $value.='<tr>'
        .'<td width="40%">'
        .__('key').$i.' :'
        .'</td>'
        .'<td>'
        .(isset($supportOpts[$values['key'.$i]])?$supportOpts[$values['key'.$i]]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'.
        '</tr>';
        }
        $value.='</table>';
        break;
        case'export_e_journal':
        $values=json_decode($value,true);
        $supportOpts=array(''=>__('no'),'y'=>__('yes'));
        $value='<table class="cell-table" width="99%">'
        //	Support
        .'<tr>'
        .'<td width="25%">'
        .__d('pos','support')
        .'</td>'
        .'<td>'
        .(isset($values['support'])&&isset($supportOpts[$values['support']])?$supportOpts[$values['support']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>'
        //	Export path
        .'<tr>'
        .'<td>'
        .__d('pos','export_path')
        .'</td>'
        .'<td>'
        .$values['path']
        .'</td>'
        .'</tr>'
        .'</table>';
        break;
        case'force_daily_close':
        $values=json_decode($value,true);
        $supportOpts=array(''=>__('no'),'y'=>__('yes'));
        $value='<table style="border-collapse:collapse;" width="99%">'
        //	Support
        .'<tr>'
        .'<td width="25%">'
        .__d('pos','support')
        .'</td>'
        .'<td>'
        .(isset($values['support'])&&isset($supportOpts[$values['support']])?$supportOpts[$values['support']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>'
        //	User
        .'<tr>'
        .'<td width="25%">'
        .__d('user','user')
        .'</td>'
        .'<td>'
        .(isset($userOpts[$values['userId']])?$userOpts[$values['userId']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>'
        //	Payment Type
        .'<tr>'
        .'<td>'
        .__d('pos','payment_method')
        .'</td>'
        .'<td>'
        .(isset($posPaymentMethods[$values['paymentId']])?$posPaymentMethods[$values['paymentId']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>'
        //	Station
        .'<tr>'
        .'<td>'
        .__d('pos','station')
        .'</td>'
        .'<td>'
        .(isset($stationOpts[$values['stationId']])?$stationOpts[$values['stationId']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>'
        //	Carry forward open check to nex business day
        .'<tr>'
        .'<td width="25%">'
        .__d('pos','carry_forward')
        .'</td>'
        .'<td>'
        .(isset($values['carryForward'])&&isset($supportOpts[$values['carryForward']])?$supportOpts[$values['carryForward']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>'
        .'</table>';
        break;
        case'member_validation_setting':
        $values=json_decode($value,true);
        $supportOpts=array(''=>__('no'),'y'=>__('yes'));
        $typeOpts=array('normal'=>__d('pos','normal_member'),'employeeMember'=>__d('pos','employee_member'));
        $value='<table class="cell-table" width="99%">'
        //	Support
        .'<tr>'
        .'<td width="25%">'
        .__d('pos','no_validation_with_member_module')
        .'</td>'
        .'<td>'
        .(isset($values['no_member_validation_in_set_member'])&&isset($supportOpts[$values['no_member_validation_in_set_member']])?$supportOpts[$values['no_member_validation_in_set_member']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>'
        //	Export path
        .'<tr>'
        .'<td>'
        .__d('pos','msr_interface_code')
        .'</td>'
        .'<td>'
        .$values['interface_code']
        .'</td>'
        .'</tr>'
        //	Member Type
        .'<tr>'
        .'<td>'
        .__d('pos','member_type')
        .'</td>'
        .'<td>'
        .(isset($values['member_type'])&&isset($typeOpts[$values['member_type']])?$typeOpts[$values['member_type']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>'
        .'</table>';
        break;
        case'print_check_control':
        $values=json_decode($value,true);
        $supportOpts=array(''=>__('no'),'y'=>__('yes'));
        $value='<table class="cell-table" width="99%">'
        //	Support
        .'<tr>'
        .'<td width="25%">'
        .__d('pos','support')
        .'</td>'
        .'<td>'
        .(isset($values['support'])&&isset($supportOpts[$values['support']])?$supportOpts[$values['support']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>'
        //	Member Attachment
        .'<tr>'
        .'<td width="25%">'
        .__d('pos','need_member_attached')
        .'</td>'
        .'<td>'
        .(isset($values['need_member_attached'])&&isset($supportOpts[$values['need_member_attached']])?$supportOpts[$values['need_member_attached']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>'
        .'</table>';
        break;
        case'table_validation_setting':
        $values=json_decode($value,true);
        $supportOpts=array(''=>__('no'),'y'=>__('yes'));
        $value='<table class="cell-table" width="99%">'
        //	Support
        .'<tr>'
        .'<td width="25%">'
        .__d('pos','support')
        .'</td>'
        .'<td>'
        .(isset($values['support'])&&isset($supportOpts[$values['support']])?$supportOpts[$values['support']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>'
        //	MSR Interface Code
        .'<tr>'
        .'<td>'
        .__d('pos','msr_interface_code').' :'
        .'</td>'
        .'<td>'
        .$values['msr_interface_code']
        .'</td>'
        .'</tr>'
        //	Default Cover
        .'<tr>'
        .'<td>'
        .__d('pos','default_cover').' :'
        .'</td>'
        .'<td>'
        .$values['default_cover']
        .'</td>'
        .'</tr>'
        //	Minimum Check Total For All Tables
        .'<tr>'
        .'<td>'
        .__d('pos','minimum_check_total_for_all_tables').' :'
        .'</td>'
        .'<td>'
        .(isset($values['minimum_check_total_for_all_tables'])?$values['minimum_check_total_for_all_tables']:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>'
        //	Minimum Charge Item Code
        .'<tr>'
        .'<td>'
        .__d('pos','minimum_charge_item_code').' :'
        .'</td>'
        .'<td>'
        .(isset($values['minimum_charge_item_code'])?$values['minimum_charge_item_code']:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>'
        //	Maximum Check Total For All Tables
        .'<tr>'
        .'<td>'
        .__d('pos','maximum_check_total_for_all_tables').' :'
        .'</td>'
        .'<td>'
        .$values['maximum_check_total_for_all_tables']
        .'</td>'
        .'</tr>'
        // Ask for confirmation for check maximum
        .'<tr>'
        .'<td width="40%">'
        .__d('pos','ask_for_bypass_max_check_total').' :'
        .'</td>'
        .'<td>'
        .(isset($values['ask_for_bypass_max_check_total'])&&isset($supportOpts[$values['ask_for_bypass_max_check_total']])?$supportOpts[$values['ask_for_bypass_max_check_total']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>'
        //	Skip Ask Cover
        .'<tr>'
        .'<td width="25%">'
        .__d('pos','skip_ask_cover')
        .'</td>'
        .'<td>'
        .(isset($values['skip_ask_cover'])&&isset($supportOpts[$values['skip_ask_cover']])?$supportOpts[$values['skip_ask_cover']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>'
        .'</table>';
        break;
        case'set_order_ownership':
        $values=json_decode($value,true);
        $supportOpts=array(''=>__('no'),'y'=>__('yes'));
        $typeOpts=array('r'=>__d('pos','only_owner_allow_access_check'),'c'=>__d('pos','everyone_allow_access_check_except_print_check_and_pay_check'));
        $value='<table class="cell-table" width="99%">'
        //	Support
        .'<tr>'
        .'<td width="25%">'
        .__d('pos','support')
        .'</td>'
        .'<td>'
        .(isset($values['support'])&&isset($supportOpts[$values['support']])?$supportOpts[$values['support']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>'
        //	set ownership type
        .'<tr>'
        .'<td width="25%">'
        .__d('pos','ownership_type')
        .'</td>'
        .'<td>'
        .(isset($values['type'])&&isset($typeOpts[$values['type']])?$typeOpts[$values['type']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>'
        .'</table>';
        break;
        case'payment_checking':
        $values=json_decode($value,true);
        $supportOpts=array(''=>__('no'),'y'=>__('yes'));
        $value='<table class="cell-table" width="99%">'
        //	Support
        .'<tr>'
        .'<td width="25%">'
        .__d('pos','support')
        .'</td>'
        .'<td>'
        .(isset($values['support'])&&isset($supportOpts[$values['support']])?$supportOpts[$values['support']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>'
        //	Check Drawer Ownership
        .'<tr>'
        .'<td width="25%">'
        .__d('pos','check_drawer_ownership')
        .'</td>'
        .'<td>'
        .(isset($values['check_drawer_ownership'])&&isset($supportOpts[$values['check_drawer_ownership']])?$supportOpts[$values['check_drawer_ownership']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>'
        //	Clear Ownership In Daily Start
        .'<tr>'
        .'<td width="25%">'
        .__d('pos','clear_ownership_in_daily_start')
        .'</td>'
        .'<td>'
        .(isset($values['clear_ownership_in_daily_start'])&&isset($supportOpts[$values['clear_ownership_in_daily_start']])?$supportOpts[$values['clear_ownership_in_daily_start']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>'
        .'</table>';
        break;
        case'advance_order_setting':
        $values=json_decode($value,true);
        $supportOpts=array(''=>__('no'),'y'=>__('yes'));
        $value='<table style="border-collapse:collapse;" width="99%">'
        //	Support
        .'<tr>'
        .'<td width="25%">'
        .__d('pos','support')
        .'</td>'
        .'<td>'
        .(isset($values['support'])&&isset($supportOpts[$values['support']])?$supportOpts[$values['support']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>'
        //	Payment Method
        .'<tr>'
        .'<td>'
        .__d('pos','advance_order_payment_method')
        .'</td>'
        .'<td>'
        .(isset($posPaymentMethods[$values['paymentId']])?$posPaymentMethods[$values['paymentId']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>'
        .'</table>';
        break;
        case'table_floor_plan_setting':
        $values=json_decode($value,true);
        $supportOpts=array(''=>__('no'),'y'=>__('yes'));
        $value='<table class="cell-table" width="99%">'
        //	Support showing cooking overtime status
        .'<tr>'
        .'<td width="35%">'
        .__d('pos','support_cooking_overtime_status')
        .'</td>'
        .'<td>'
        .(isset($values['support_cooking_overtime'])&&isset($supportOpts[$values['support_cooking_overtime']])?$supportOpts[$values['support_cooking_overtime']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>'

        //	Support table status cleaning
        .'<tr>'
        .'<td>'
        .__d('pos','support_table_status_cleaning')
        .'</td>'
        .'<td>'
        .(isset($values['support_table_status_cleaning'])&&isset($supportOpts[$values['support_table_status_cleaning']])?$supportOpts[$values['support_table_status_cleaning']]:__('no'))
        .'</td>'
        .'</tr>'
        //	Automatically change cleaning to vacant interval
        .'<tr>'
        .'<td>'
        .__d('pos','automatically_change_cleaning_to_vacant_interval')
        .'</td>'
        .'<td>'
        .(isset($values['automatically_change_cleaning_to_vacant_interval'])?$values['automatically_change_cleaning_to_vacant_interval']:'0').' ('.__('seconds').')'
        .'</td>'
        .'</tr>'
        //	Cleaning Status function list
        .'<tr>'
        .'<td>'
        .__d('pos','support_cleaning_status_function_list')
        .'</td>';
        $value.='<td>'
        .'<table class="no-border" width="99%">';
        if(isset($values['cleaning_status_function_list'])){
        for($i=0;$i<count($values['cleaning_status_function_list']);$i++){
        $value.='<tr>'
        .'<td width="20%">'.(isset($posFunctions[$values['cleaning_status_function_list'][$i]['function_key']])?$posFunctions[$values['cleaning_status_function_list'][$i]['function_key']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;').'</td>'
        .'</tr>';
        }
        }
        $value.='</table>'
        .'</td>'
        .'</tr>'
        //	Table status color
        .'<tr>'
        .'<td>'
        .__d('pos','table_status_color')
        .'</td>'
        .'<td>';
        if(isset($values['table_status_color']))
        $tableStatusColorList=$values['table_status_color'];
        else{
        //	Handle old format
        $tableStatusColorList=$defaultTableStatusColor;
        $tableStatusColorList['cooking_over_time']=$values['cooking_overtime_status_color'];
        }

        $value.='<table class="no-border" width="99%">';
        foreach($tableStatusColorList as $tableStatus=>$tableStatusColor){
        $value.='<tr>'
        .'<td width="50%">'.__d('pos',$tableStatus).'</td>'
        .'<td width="25%" style="border-right:none;">'.$tableStatusColor.'</td>'
        .'<td style="border-left:none;">';
        if(!empty($tableStatusColor))
        $value.='<span class="select-color" style="background-color:#'.$tableStatusColor.';"></span>';
        $value.='</td>'
        .'</tr>';
        }
        $value.='</table>'
        .'</td>'
        .'</tr>'
        .'</table>';
        break;
        case'separate_inclusive_tax_on_display':
        $values=json_decode($value,true);
        $supportOpts=array(''=>__('no'),'y'=>__('yes'));
        $value='<table class="cell-table" width="99%">'
        //	Support Display inclusive tax in extend bar
        .'<tr>'
        .'<td width="25%">'
        .__d('pos','support_display_inclusive_tax_in_check')
        .'</td>'
        .'<td>'
        .(isset($values['support_display_inclusive_tax_in_check'])&&isset($supportOpts[$values['support_display_inclusive_tax_in_check']])?$supportOpts[$values['support_display_inclusive_tax_in_check']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>'
        .'</table>';
        break;
        case'auto_track_cover_based_on_item_ordering':
        $values=json_decode($value,true);
        $supportOpts=array('n'=>__('no'),'y'=>__('yes'));
        //	Support
        $value='<table class="cell-table" width="100%">'
        .'<tr>'
        .'<td width="25%">'
        .__d('pos','support')
        .'</td>'
        .'<td>'
        .(isset($values['support'])&&isset($supportOpts[$values['support']])?$supportOpts[$values['support']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>';

        //	Item group ids
        $itemGroupIds=explode(",",isset($values['item_group_ids'])?$values['item_group_ids']:"");
        $value.='<tr>'
        .'<td width="25%">'
        .__d('pos','item_group')
        .'</td>'
        .'<td>';
        if(!empty($itemGroupIds)){
        foreach($itemGroupIds as $mapping)
        $value.=(isset($itemGroupOpts[$mapping])&&$mapping!=0?$itemGroupOpts[$mapping]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;').'</br>';
        }
        else
        $value.='&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;';
        $value.='</td>'
        .'</tr>';
        // Period
        $periodIds=explode(",",isset($values['period_ids'])?$values['period_ids']:"");
        if($posConfig['PosConfig']['scfg_by']=="outlet"){
        $value.='<tr>'
        .'<td>'
        .__d('pos','period')
        .'</td>'
        .'<td>';

        foreach($periodIds as $periodId)
        if(isset($outletPeriods[$periodId]))
        $value.=__k($outletPeriods[$periodId],30).' '.'<br />';
        $value.='</td>'
        .'</tr>';
        }
        $value.='</table>';
        break;
        case'repeat_round_items_limitation':
        $values=json_decode($value,true);
        $supportOpts=array(''=>__('no'),'y'=>__('yes'));
        //	Support
        $value='<table class="cell-table" width="100%">'
        .'<tr>'
        .'<td width="25%">'
        .__d('pos','support')
        .'</td>'
        .'<td>'
        .(isset($values['support'])&&isset($supportOpts[$values['support']])?$supportOpts[$values['support']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>';

        //	Item department
        $itemDepts=isset($values['item_departments'])?$values['item_departments']:[];
        $value.='<tr>'
        .'<td width="25%">'
        .__d('pos','item_departments')
        .'</td>'
        .'<td>';
        if(!empty($itemDepts)){
        foreach($itemDepts as $mapping)
        $value.=(isset($itemDeptOpts[$mapping])&&$mapping!=0?$itemDeptOpts[$mapping]:"").'</br>';
        }
        else
        $value.='&lt;&lt;&lt; '.__('empty').' &gt;&gt;&gt;';
        $value.='</td>'
        .'</tr>';

        $value.='</table>';
        break;
        case'check_listing_total_calculation_method':
        $values=json_decode($value,true);
        $supportOpts=array(''=>__('no'),'y'=>__('yes'));
        $methodOpts=array('c'=>__d('pos','check_total'),'t'=>__d('pos','check_total_due'));
        $value='<table class="cell-table" width="99%">'
        //	Support
        .'<tr>'
        .'<td width="25%">'
        .__d('pos','support')
        .'</td>'
        .'<td>'
        .(isset($values['support'])&&isset($supportOpts[$values['support']])?$supportOpts[$values['support']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>'
        //	calculation method
        .'<tr>'
        .'<td width="25%">'
        .__d('pos','calculation_method')
        .'</td>'
        .'<td>'
        .(isset($values['method'])&&isset($methodOpts[$values['method']])?$methodOpts[$values['method']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>'
        .'</table>';
        break;
        case'idle_time_logout':
        $values=json_decode($value,true);
        $value='<table class="cell-table"  width="100%">'
        .'<tr>'
        .'<td width="40%">'
        .__('timeout').' :'
        .'</td>'
        .'<td>'
        .$values['timeout'].' '.__('seconds')
        .'</td>'
        .'</tr>';
        $value.='<tr>'
        .'<td width="10%">'
        .__d('user','user_group').' :'
        .'</td>'
        .'<td>';
        $value.='<table width="100%" class="no-border">';
        foreach($values['user_group_ids']as $userGroup){
        $value.='<tr><td width="50%">'
        .(isset($userGroupList[$userGroup['id']])?$userGroupList[$userGroup['id']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td><td>';
        $value.=isset($userGroup['timeout'])?$userGroup['timeout']:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;';
        $value.=' '.__('seconds').'</td></tr>';
        }
        $value.='</table>';
        $value.='</td></tr>';
        $value.='</table>';
        break;
        case'switch_check_info_setting':
        $values=json_decode($value,true);
        $supportOpts=array(''=>__('no'),'y'=>__('yes'));
        $selectOpts=array('open_time'=>__d('pos','open_check_time'),'cover_no'=>__d('pos','cover'),
        'check_total'=>__d('pos','check_total'),'member_number'=>__d('pos','member_number'),
        'member_name'=>__d('pos','member_name'),'owner_name'=>__d('pos','check_owner_name'),
        'table_size'=>__d('pos','table_size'),'check_info_one'=>__d('pos','check_info').' 1',
        'check_info_two'=>__d('pos','check_info').' 2','check_info_three'=>__d('pos','check_info').' 3',
        'check_info_four'=>__d('pos','check_info').' 4','check_info_five'=>__d('pos','check_info').' 5');
        $value='<table class="cell-table" width="99%">'
        //Default Display
        .'<tr>'
        .'<td>'.__d('pos','default_display').' :'.'</td>'
        .'<td>'
        .(isset($values['default_display'])&&isset($selectOpts[$values['default_display']])?$selectOpts[$values['default_display']]:__('unknown'))
        .'</td>'
        .'</tr>'
        .'<th>'.'</th>'
        .'<th><b><center>'.__d('pos','visible').'</center></b></th>'
        //	Open Time
        .'<tr>'
        .'<td width="25%">'
        .__d('pos','open_check_time')
        .'</td>'
        .'<td>'
        .(isset($values['open_time'])&&isset($supportOpts[$values['open_time']])?$supportOpts[$values['open_time']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>'
        //	Cover No
        .'<tr>'
        .'<td width="25%">'
        .__d('pos','cover')
        .'</td>'
        .'<td>'
        .(isset($values['cover_no'])&&isset($supportOpts[$values['cover_no']])?$supportOpts[$values['cover_no']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>'
        //	Check Total
        .'<tr>'
        .'<td width="25%">'
        .__d('pos','check_total')
        .'</td>'
        .'<td>'
        .(isset($values['check_total'])&&isset($supportOpts[$values['check_total']])?$supportOpts[$values['check_total']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>'
        //	Member Number
        .'<tr>'
        .'<td width="25%">'
        .__d('pos','member_number')
        .'</td>'
        .'<td>'
        .(isset($values['member_number'])&&isset($supportOpts[$values['member_number']])?$supportOpts[$values['member_number']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>'
        // Member Name
        .'<tr>'
        .'<td width="25%">'
        .__d('pos','member_name')
        .'</td>'
        .'<td>'
        .(isset($values['member_name'])&&isset($supportOpts[$values['member_name']])?$supportOpts[$values['member_name']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>'
        //	Owner Name
        .'<tr>'
        .'<td width="25%">'
        .__d('pos','check_owner_name')
        .'</td>'
        .'<td>'
        .(isset($values['owner_name'])&&isset($supportOpts[$values['owner_name']])?$supportOpts[$values['owner_name']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>'
        //	Check Info One
        .'<tr>'
        .'<td width="25%">'
        .__d('pos','check_info').' 1'.' :'
        .'</td>'
        .'<td>'
        .(isset($values['check_info_one'])&&isset($supportOpts[$values['check_info_one']])?$supportOpts[$values['check_info_one']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>'
        //	Check Info Two
        .'<tr>'
        .'<td width="25%">'
        .__d('pos','check_info').' 2'.' :'
        .'</td>'
        .'<td>'
        .(isset($values['check_info_two'])&&isset($supportOpts[$values['check_info_two']])?$supportOpts[$values['check_info_two']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>'
        //	Check Info Three
        .'<tr>'
        .'<td width="25%">'
        .__d('pos','check_info').' 3'.' :'
        .'</td>'
        .'<td>'
        .(isset($values['check_info_three'])&&isset($supportOpts[$values['check_info_three']])?$supportOpts[$values['check_info_three']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>'
        //	Check Info Four
        .'<tr>'
        .'<td width="25%">'
        .__d('pos','check_info').' 4'.' :'
        .'</td>'
        .'<td>'
        .(isset($values['check_info_four'])&&isset($supportOpts[$values['check_info_four']])?$supportOpts[$values['check_info_four']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>'
        //	Check Info Five
        .'<tr>'
        .'<td width="25%">'
        .__d('pos','check_info').' 5'.' :'
        .'</td>'
        .'<td>'
        .(isset($values['check_info_five'])&&isset($supportOpts[$values['check_info_five']])?$supportOpts[$values['check_info_five']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>'
        .'</table>';
        break;
        case'special_setup_for_inclusive_sc_tax':
        $values=json_decode($value,true);
        $yesNoOpts=array('y'=>__('yes'),''=>__('no'));
        $value='<table class="cell-table" width="99%">'
        .'<tr>'
        .'<td width="40%">'
        .__d('pos','breakdown_at_final_check_settlement').' :'
        .'</td>'
        .'<td>'
        .(isset($yesNoOpts[$values['breakdown_at_check_settle']])?$yesNoOpts[$values['breakdown_at_check_settle']]:__('unknown'))
        .'</td>'
        .'</tr>'
        .'</table>';
        break;
        case'payment_rounding_dummy_payment_mapping':
        $mappings=json_decode($value,true);
        $mappings=$mappings['mapping'];
        $value='<table class="cell-table" width="99%">';
        $value.='<tr><th>'.__d('pos','payment_method').'</th><th>'.__d('pos','dummy_payment_method').'</th></tr>';
        for($i=0;$i<count($mappings);$i++){
        $value.='<tr>'
        .'<td>'.(isset($posPaymentMethods[$mappings[$i]['paym_id']])?$posPaymentMethods[$mappings[$i]['paym_id']]:__('unknown')).'</td>'
        .'<td>'.(isset($posPaymentMethods[$mappings[$i]['dummy_paym_id']])?$posPaymentMethods[$mappings[$i]['dummy_paym_id']]:__('unknown')).'</td>'
        .'</tr>';
        }
        $value.='</table>';
        break;
        case'cover_limit':
        if(is_numeric($value)){
        $values['upper_bound']=$value;
        $values['warning']=0;
        }else
        $values=json_decode($value,true);
        $value='<table class="cell-table" width="99%">'
        //	Cover Upper Bound
        .'<tr>'
        .'<td>'
        .__d('pos','cover_upper_bound').' :'
        .'</td>'
        .'<td>'
        .($values['upper_bound']==0?__d('pos','no_limit'):$values['upper_bound'].' ('.__d('pos','cover').')')
        .'</td>'
        .'</tr>'
        //	Cover Warning
        .'<tr>'
        .'<td>'
        .__d('pos','cover_warning').' :'
        .'</td>'
        .'<td>'
        .($values['warning']==0?__d('pos','no_limit'):$values['warning'].' ('.__d('pos','cover').')')
        .'</td>'
        .'</tr>'
        .'</table>';
        break;
        case'ask_quantity_during_apply_discount':
        $values=json_decode($value,true);
        $yesNoOpts=array('y'=>__('yes'),''=>__('no'));
        $value='<table class="cell-table" width="99%">'
        .'<tr>'
        .'<td width="40%">'
        .__d('pos','check_discount').' :'
        .'</td>'
        .'<td>'
        .(isset($yesNoOpts[$values['check_discount']])?$yesNoOpts[$values['check_discount']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>'
        .'<tr>'
        .'<td width="40%">'
        .__d('pos','item_discount').' :'
        .'</td>'
        .'<td>'
        .(isset($values['item_discount'])&&isset($yesNoOpts[$values['item_discount']])?$yesNoOpts[$values['item_discount']]:'&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;')
        .'</td>'
        .'</tr>'
        .'</table>';
        break;
default:
        break;
        }

        echo'<tr>'
        .'<th>'.__('value').'</th>'
        .'<td>'.$value.'</td>'
        .'</tr>';

        //	Remark
        echo'<tr>'
        .'<th>'.__('remark').'</th>'
        .'<td>'.$posConfig['PosConfig']['scfg_remark'].'</td>'
        .'</tr>';

        echo'</table>';

        //	Draw buttons
        echo'<div class="buttons-bar">';
        echo $this->element('button',array('id'=>'EditButton','content'=>__("edit"),'link'=>$this->Html->url('/admin'.$langPath.'pos/location_configs/edit/'.$posConfig['PosConfig']['scfg_id'].$backUrlParamsStr)));
        echo" &nbsp; ".$this->element('button',array('id'=>'DeleteButton','content'=>__("delete")));
        echo" &nbsp; ".$this->element('button',array('id'=>'CancelButton','content'=>__("back"),'link'=>$this->Html->url('/admin'.$langPath.'pos/location_configs/config/'.$variable.$backUrlParamsStr)));
        echo'</div>';

        echo $this->element('frame_basic_bottom');

        echo'</div>';    // End zone-body

        ///////////////////////////////////////////////////////////////////////////////
        //	Other element
        echo $this->element('confirm_dialog');        // confirm dialog box element
        ?>