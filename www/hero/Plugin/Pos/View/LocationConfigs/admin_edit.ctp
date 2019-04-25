<?php
        $this->Html->script('jquery/plugins/validation/jquery.validate.js',false);
        $this->Html->script('elements/form.js',false);        // Add special effect for form
        $this->Html->script('jquery/plugins/select2/select2.js',false);    //	Make selection easier with search function
        $this->Html->script('jquery/plugins/colorpicker/js/colorpicker.js',false);    //	Color picker for color selection

        $this->Html->css('site/elements/breadcrumb.css',null,array('inline'=>false));
        $this->Html->css('plugins/select2',null,array('inline'=>false));
        $this->Html->css('/js/jquery/plugins/colorpicker/css/colorpicker.css',null,array('inline'=>false));

        $bNoAllLocation=false;
        if(ENTERPRISE_CONFIG){        //	Not allow to select "all" for non-supreme config zone
        if(empty($bIsSupremeConfigZone))
        $bNoAllLocation=true;
        }

        if(empty($posConfig)){        // Add new object
        $vals=array(
        'scfg_id'=>0,
        'scfg_record_id'=>0,
        'scfg_record'=>'',
        'scfg_remark'=>''
        );

        if(empty($existIds['all'])&&empty($bNoAllLocation))
        $vals['scfg_by']='';
        else
        $vals['scfg_by']='shop';

        switch($variable){
        case'tender_amount':
        case'connection_setting':
        case'bday_custom_data1_type':
        case'void_reason_for_payment_auto_discount':
        case'barcode_ordering_format':
        case'check_info_self_define_description':
        case'menu_mode':
        case'common_lookup_button_number':
        case'menu_lookup_button_number':
        case'set_menu_button_number':
        case'skip_tips_payment_code':
        case'dutymeal_shop_limit':
        case'dutymeal_outlet_limit':
        case'dutymeal_check_limit':
        case'on_credit_shop_limit':
        case'on_credit_outlet_limit':
        case'on_credit_check_limit':
        case'cashier_settlement_mode':
        case'fast_food_not_print_receipt':
        case'fine_dining_not_print_receipt':
        $vals['scfg_value']='';
        break;
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
        case'support_time_charge_item':
        $vals['scfg_value']='false';
        break;
        case'new_check_auto_functions':
        case'pay_check_auto_functions':
        case'gratuity_setting':
        case'item_function_list':
        case'payment_rounding_dummy_payment_mapping':
        $vals['scfg_value']=array();
        break;
        case'allow_change_item_quantity_after_send':
        case'remove_check_type_for_release_payment':
        case'enable_user_to_check_print_queue_status_if_alert_message':
        case'require_password_after_login_by_swipe_card':
        $vals['scfg_value']='true';
        break;
        case'business_hour_warn_level':
        case'ordering_timeout':
        case'open_table_screen_mode':
        case'apply_discount_restriction':
        case'double_check_discount_alert':
        case'audit_log_level':
        case'auto_close_cashier_panel':
        case'ordering_timeout_option':
        case'reset_stock_quantity_at_daily_close':
        case'ask_table_with_advance_mode':
        case'item_stock_operation_input_mode':
        case'table_mode_row_and_column':
        case'reprint_guest_check_times':
        case'reprint_receipt_times':
        case'number_of_drawer_owned_by_user':
        case'copies_of_receipt':
        case'time_control_to_open_next_check_by_member':
        $vals['scfg_value']=0;
        break;
        case'new_check_auto_functions':
        $vals['scfg_value']['support_for_split_check']='n';
        break;
        case'payment_check_types':
        $vals['scfg_value']=array(
        'mapping'=>array()
        );
        break;
        case'auto_switch_from_pay_result_to_starting_page_time_control':
        $vals['scfg_value']=3;
        break;
        case'ordering_basket_item_grouping_method':
        $vals['scfg_value']='l';
        break;
        case'dutymeal_limit_reset_period':
        case'on_credit_limit_reset_period':
        $vals['scfg_value']='m';
        break;
        case'ordering_basket_toggle_consolidate_items_grouping_method':
        $vals['scfg_value']='o';
        break;
        case'call_number_input_setting':
        $vals['scfg_value']=array(
        'method'=>'m',
        'image_file_for_scan_mode'=>''
        );
        break;
        case'check_calling_number':
        case'item_calling_number':
        $vals['scfg_value']=array(
        'support'=>'y',
        'method'=>'r',
        'mode'=>'',
        'pool_code'=>''
        );
        break;
        case'payment_running_number':
        $vals['scfg_value']=array(
        'support'=>'y',
        'method'=>'c',
        'pool_code'=>'',
        'single_number_for_check'=>'false',
        'available_payment_ids'=>'',
        'reusable_payment_ids'=>''
        );
        break;
        case'item_print_queue_running_number':
        $vals['scfg_value']=array(
        'support'=>'y',
        'method'=>'r',
        'print_queues'=>array()
        );
        break;
        case'idle_time_logout':
        $vals['scfg_value']=array(
        'timeout'=>0,
        'user_group_ids'=>array()

        );

        break;
        case'open_check_setting':
        $vals['scfg_value']=array(
        'support'=>'n',
        'period_ids'=>'',
        'ask_table_number'=>'y',
        'ask_guest_number'=>'y'
        );
        break;
        case'employee_discount_limit':
        $vals['scfg_value']=array(
        'userId'=>0,
        'mode'=>'m',
        'limit'=>''
        );
        break;
        case'settlement_count_interval_to_print_guest_questionnaire':
        $vals['scfg_value']=array(
        'interval_count'=>0
        );
        break;
        case'payment_process_setting':
        $vals['scfg_value']=array(
        'display_loading_box_during_payment'=>'false',
        'payment_completion_message'=>'',
        'payment_completion_image_name'=>''
        );
        break;
        case'generate_receipt_pdf':
        $vals['scfg_value']=array(
        'support'=>'y',
        'password'=>'',
        'path'=>''
        );
        break;
        case'screen_saver_option':
        $vals['scfg_value']=array(
        'timeout'=>'0',
        'display_content'=>'c',
        'color'=>'000000',
        'transparency'=>'FF'
        );
        break;
        case'table_attribute_mandatory_key':
        $vals['scfg_value']=array(
        'key1'=>'',
        'key2'=>'',
        'key3'=>'',
        'key4'=>'',
        'key5'=>'',
        'key6'=>'',
        'key7'=>'',
        'key8'=>'',
        'key9'=>'',
        'key10'=>''
        );
        break;
        case'force_daily_close':
        $vals['scfg_value']=array(
        'support'=>'y',
        'userId'=>0,
        'paymentId'=>0,
        'stationId'=>0,
        'carry_forward'=>'y'
        );
        break;
        case'export_e_journal':
        $vals['scfg_value']=array(
        'support'=>'',
        'path'=>''
        );
        $vals['scfg_by']='station';
        $bNoAllLocation=true;
        break;
        case'member_validation_setting':
        $vals['scfg_value']=array(
        'no_member_validate'=>'',
        'interface_code'=>'',
        'member_type'=>''
        );
        break;
        case'print_check_control':
        $vals['scfg_value']=array(
        'support'=>'y',
        'need_member_attached'=>''
        );
        break;
        case'table_validation_setting':
        $vals['scfg_value']=array(
        'support'=>'y',
        'msr_interface_code'=>'',
        'default_cover'=>0,
        'minimum_check_total_for_all_tables'=>0,
        'minimum_charge_item_code'=>'',
        'maximum_check_total_for_all_tables'=>0,
        'ask_for_bypass_max_check_total'=>'',
        'skip_ask_cover'=>'y'
        );
        break;
        case'set_order_ownership':
        $vals['scfg_value']=array(
        'support'=>'y',
        'type'=>'r'
        );
        break;
        case'payment_checking':
        $vals['scfg_value']=array(
        'support'=>'y',
        'check_drawer_ownership'=>'',
        'clear_ownership_in_daily_start'=>''
        );
        break;
        case'advance_order_setting':
        $vals['scfg_value']=array(
        'support'=>'y',
        'paymentId'=>0
        );
        break;
        case'table_floor_plan_setting':
        $vals['scfg_value']=array(
        'support_cooking_overtime'=>'y',
        'support_table_status_cleaning'=>'y',
        'automatically_change_cleaning_to_vacant_interval'=>'0',
        'cleaning_status_function_list'=>array(),
        'table_status_color'=>$defaultTableStatusColor //	Defined in controller
        );
        break;
        case'separate_inclusive_tax_on_display':
        $vals['scfg_value']=array(
        'support_display_inclusive_tax_in_check'=>''
        );
        break;
        case'auto_track_cover_based_on_item_ordering':
        $vals['scfg_value']=array(
        'support'=>'y',
        'period_ids'=>'',
        'item_group_ids'=>''
        );
        break;
        case'repeat_round_items_limitation':
        $vals['scfg_value']=array(
        'support'=>'y',
        'item_departments'=>''
        );
        break;
        case'check_listing_total_calculation_method':
        $vals['scfg_value']=array(
        'support'=>'y',
        'method'=>'c'
        );
        break;
        case'support_partial_payment':
        $vals['scfg_value']=array(
        'support_partial_payment'=>'y',
        'continue_to_pay_after_settling_partial_payment'=>'',
        'print_receipt_only_when_finish_all_payment'=>'',
        'void_all_payment_after_release_payment'=>''
        );
        break;
        case'switch_check_info_setting':
        $vals['scfg_value']=array(
        'default_check_info'=>'open_time',
        'open_time'=>'y',
        'cover_no'=>'y',
        'check_total'=>'y',
        'member_no'=>'y',
        'member_name'=>'y',
        'owner_name'=>'y',
        'check_info_one'=>'',
        'check_info_two'=>'',
        'check_info_three'=>'',
        'check_info_four'=>'',
        'check_info_five'=>'',
        );
        break;
        case'special_setup_for_inclusive_sc_tax':
        $vals['scfg_value']=array(
        'breakdown_at_check_settle'=>''
        );
        break;
        case'cover_limit':
        $vals['scfg_value']=array(
        'upper_bound'=>0,
        'warning'=>0
        );
        break;
        case'ask_quantity_during_apply_discount':
        $vals['scfg_value']=array(
        'check_discount'=>'',
        'item_discount'=>''
        );
        break;
default:
        break;
        }
        }
        else{    // Edit existing object
        $vals=array(
        'scfg_id'=>$posConfig['PosConfig']['scfg_id'],
        'scfg_by'=>$posConfig['PosConfig']['scfg_by'],
        'scfg_record_id'=>$posConfig['PosConfig']['scfg_record_id'],
        'scfg_record'=>$posConfig['PosConfig']['scfg_record'],
        'scfg_value'=>$posConfig['PosConfig']['scfg_value'],
        'scfg_remark'=>$posConfig['PosConfig']['scfg_remark']
        );

        //	Special handling for some variables
        switch($variable){
        case'tender_amount':
        case'check_info_self_define_description':
        if(!empty($vals['scfg_value'])){
        $vals['scfg_value']=json_decode($vals['scfg_value'],true);
        $vals['scfg_value']=implode("\r\n",$vals['scfg_value']);
        }
        break;
        case'check_calling_number':
        case'item_calling_number':
        case'payment_running_number':
        case'item_print_queue_running_number':
        case'employee_discount_limit':
        case'settlement_count_interval_to_print_guest_questionnaire':
        case'payment_check_types':
        case'generate_receipt_pdf':
        case'new_check_auto_functions':
        case'gratuity_setting':
        case'screen_saver_option':
        case'export_e_journal':
        case'force_daily_close':
        case'member_validation_setting':
        case'print_check_control':
        case'table_validation_setting':
        case'set_order_ownership':
        case'payment_checking':
        case'table_attribute_mandatory_key':
        case'advance_order_setting':
        case'table_floor_plan_setting':
        case'payment_process_setting':
        case'pay_check_auto_functions':
        case'item_function_list':
        case'separate_inclusive_tax_on_display':
        case'open_check_setting':
        case'auto_track_cover_based_on_item_ordering':
        case'repeat_round_items_limitation':
        case'check_listing_total_calculation_method':
        case'display_check_extra_info_in_ordering_basket':
        case'idle_time_logout':
        case'support_partial_payment':
        case'switch_check_info_setting':
        case'special_setup_for_inclusive_sc_tax':
        case'payment_rounding_dummy_payment_mapping':
        case'cover_limit':
        case'ask_quantity_during_apply_discount':
        if(!empty($vals['scfg_value']))
        $vals['scfg_value']=json_decode($vals['scfg_value'],true);
        break;
        case'call_number_input_setting':
        if(!empty($vals['scfg_value'])){
        $temp=array(
        'method'=>$vals['scfg_value'],
        'image_file_for_scan_mode'=>'',
        );
        $vals['scfg_value']=is_array(json_decode($vals['scfg_value'],true))?json_decode($vals['scfg_value'],true):$temp;
        }
        break;
default:
        break;
        }
        }

        ////////////////////////////////////////////////////////////////////
        //	Generate HTML for mapping row
        function generateMappingRowContent($context,$type='',$paramList=array(),$rowVals=array()){        //	$paramList - columns of parameters, $rowVals - values of each parameter
        $selectionList=array(0=>'--- '.__('please_select').' ---');

        //	Delete icon
        $mappingRow=
        '<tr>'
        .'<td>'.$context->Html->link(
        $context->Html->image('icons/16x16/trash.png',array('alt'=>__('delete'),'title'=>__('delete'))),
        'javascript:;',
        array('class'=>'js-remove-mapping-row','escape'=>false)
        )
        .'</td>';
        if($type=='paymentCheckType'){
        $mappingRow.='<td>'        //	Payment Method
        .$context->Form->select("PaymentMethod",empty($paramList['posPaymentMethods'])?$selectionList:$selectionList+$paramList['posPaymentMethods'],array(
        'name'=>'data[PaymentCheckTypeMapping][PaymentMethod][]',
        'default'=>(empty($rowVals['paym_id'])?0:$rowVals['paym_id']),
        'style'=>'width:100%;',
        'empty'=>false
        )
        )
        .'</td>';

        $mappingRow.='<td>'        //	Custom Type
        .$context->Form->select("CustomType",empty($paramList['posCustomTypes'])?$selectionList:$selectionList+$paramList['posCustomTypes'],array(
        'name'=>'data[PaymentCheckTypeMapping][CustomType][]',
        'default'=>(empty($rowVals['ctyp_id'])?0:$rowVals['ctyp_id']),
        'style'=>'width:100%;',
        'empty'=>false
        )
        )
        .'</td>'
        .'</tr>';
        }
        else if($type=='newCheckAutoFunction'){
        $mappingRow.='<td>'        //	Function
        .$context->Form->select("Function",empty($paramList['posFunctions'])?$selectionList:$selectionList+$paramList['posFunctions'],array(
        'name'=>'data[NewCheckAutoFunctionMapping][Function][]',
        'default'=>(empty($rowVals['function_key'])?0:$rowVals['function_key']),
        'style'=>'width:100%;',
        'empty'=>false
        )
        )
        .'</td>';

        $failHandlingOptions=array('q'=>__d('pos','force_to_quit_check'),'c'=>__d('pos','continue_operation'));
        $mappingRow.='<td>'        //	Fail handling
        .$context->Form->select("FailHandling",empty($failHandlingOptions)?$selectionList:$selectionList+$failHandlingOptions,array(
        'name'=>'data[NewCheckAutoFunctionMapping][FailHandling][]',
        'default'=>(empty($rowVals['fail_handling'])?0:$rowVals['fail_handling']),
        'style'=>'width:100%;',
        'empty'=>false
        )
        )
        .'</td>';

        $mappingRow.='<td>'        //	Sequence
        .$context->Form->text('Seq',array(
        'name'=>'data[NewCheckAutoFunctionMapping][Seq][]',
        'class'=>'js-input-data',
        'value'=>(empty($rowVals['seq'])?1:$rowVals['seq']),
        'maxLength'=>255,
        'style'=>'width:calc(99% - 4px);'
        )
        )
        .'</td>'
        .'</tr>';
        }
        else if($type=='checkExtraInfoInOrderingBasket'){
        $mappingRow.='<td colspan="2" >'        //	Function
        .$context->Form->select("CheckExtraInfo",empty($paramList)?$selectionList:$selectionList+$paramList,array(
        'name'=>'data[CheckExtraInfoInOrderingBasketMapping][CheckExtraInfo][]',
        'default'=>(empty($rowVals['check_extra_info'])?0:$rowVals['check_extra_info']),
        'style'=>'width:100%;',
        'empty'=>false
        )
        )
        .'</td>'
        .'</tr>';
        }
        else if($type=='payCheckAutoFunction'){
        $mappingRow.='<td>'        //	Function
        .$context->Form->select("Function",empty($paramList['posFunctions'])?$selectionList:$selectionList+$paramList['posFunctions'],array(
        'name'=>'data[PayCheckAutoFunctionMapping][Function][]',
        'default'=>(empty($rowVals['function_key'])?0:$rowVals['function_key']),
        'style'=>'width:100%;',
        'empty'=>false
        )
        )
        .'</td>';

        $failHandlingOptions=array('b'=>__d('pos','back_to_ordering_panel'),'c'=>__d('pos','continue_operation'));
        $mappingRow.='<td>'        //	Fail handling
        .$context->Form->select("FailHandling",empty($failHandlingOptions)?$selectionList:$selectionList+$failHandlingOptions,array(
        'name'=>'data[PayCheckAutoFunctionMapping][FailHandling][]',
        'default'=>(empty($rowVals['fail_handling'])?0:$rowVals['fail_handling']),
        'style'=>'width:100%;',
        'empty'=>false
        )
        )
        .'</td>';

        $mappingRow.='<td>'        //	Sequence
        .$context->Form->text('Seq',array(
        'name'=>'data[PayCheckAutoFunctionMapping][Seq][]',
        'class'=>'js-input-data',
        'value'=>(empty($rowVals['seq'])?1:$rowVals['seq']),
        'maxLength'=>255,
        'style'=>'width:calc(99% - 4px);'
        )
        )
        .'</td>'
        .'</tr>';
        }
        else if($type=='gratuityCoverControl'){
        $mappingRow.='<td>'        //	Gratuity
        .$context->Form->select("Gratuity",empty($paramList['posGratuities'])?$selectionList:$selectionList+$paramList['posGratuities'],array(
        'name'=>'data[GratuityCoverControlMapping][Gratuity][]',
        'default'=>(empty($rowVals['grat_id'])?0:$rowVals['grat_id']),
        'style'=>'width:100%;',
        'empty'=>false
        )
        )
        .'</td>';

        $mappingRow.='<td>'        //	Min Cover
        .$context->Form->text('MinCover',array(
        'name'=>'data[GratuityCoverControlMapping][MinCover][]',
        'class'=>'js-input-data',
        'value'=>(empty($rowVals['min_cover'])?0:$rowVals['min_cover']),
        'maxLength'=>3,
        'style'=>'width:calc(99% - 4px);',
        'empty'=>false
        )
        )
        .'</td>';

        $mappingRow.='<td>'        //	Max Cover
        .$context->Form->text('MaxCover',array(
        'name'=>'data[GratuityCoverControlMapping][MaxCover][]',
        'class'=>'js-input-data',
        'value'=>(empty($rowVals['max_cover'])?0:$rowVals['max_cover']),
        'maxLength'=>3,
        'style'=>'width:calc(99% - 4px);',
        'empty'=>false
        )
        )
        .'</td>'
        .'</tr>';
        }
        else if($type=='itemFunctionList'){
        $mappingRow.='<td>'        //	Function
        .$context->Form->select("Function",empty($paramList['posFunctions'])?$selectionList:$selectionList+$paramList['posFunctions'],array(
        'name'=>'data[ItemFunctionListMapping][Function][]',
        'default'=>(empty($rowVals['function_key'])?0:$rowVals['function_key']),
        'style'=>'width:100%;',
        'empty'=>false
        )
        )
        .'</td>'
        .'</tr>';
        }
        else if($type=='itemGroupList'){
        $mappingRow.='<td>'        //	Item Group List
        .$context->Form->select("ItemGroup",empty($paramList['posMenuItemGroup'])?$selectionList:$selectionList+$paramList['posMenuItemGroup'],array(
        'name'=>'data[ItemGroupListMapping][ItemGroup][]',
        'default'=>(empty($rowVals)?0:$rowVals),
        'style'=>'width:100%;',
        'empty'=>false
        )
        )
        .'</td>'
        .'</tr>';
        }
        else if($type=='itemDepartmentList'){
        $mappingRow.='<td>'        //	Item Department List
        .$context->Form->select("ItemDept",empty($paramList['posMenuItemDept'])?$selectionList:$selectionList+$paramList['posMenuItemDept'],array(
        'name'=>'data[ItemDeptListMapping][itemDept][]',
        'default'=>(empty($rowVals)?0:$rowVals),
        'style'=>'width:100%;',
        'empty'=>false
        )
        )
        .'</td>'
        .'</tr>';
        }
        else if($type=='dummyPaymentMapping'){
        $mappingRow.='<td>'        //	Payment Method
        .$context->Form->select("PaymentMethod",empty($paramList['posPaymentMethods'])?$selectionList:$selectionList+$paramList['posPaymentMethods'],array(
        'name'=>'data[DummyPaymentMethodMapping][PaymentMethod][]',
        'default'=>(empty($rowVals['paym_id'])?0:$rowVals['paym_id']),
        'style'=>'width:100%;',
        'empty'=>false
        )
        )
        .'</td>';
        $mappingRow.='<td>'        //	Dummy Payment Method
        .$context->Form->select("DummyPaymentMethod",empty($paramList['dummyPaymentMethods'])?$selectionList:$selectionList+$paramList['dummyPaymentMethods'],array(
        'name'=>'data[DummyPaymentMethodMapping][DummyPaymentMethod][]',
        'default'=>(empty($rowVals['dummy_paym_id'])?0:$rowVals['dummy_paym_id']),
        'style'=>'width:100%;',
        'empty'=>false
        )
        )
        .'</td>'
        .'</tr>';
        }
        else if($type=='cleaningStatusFunctionList'){
        $mappingRow.='<td>'        //	Function
        .$context->Form->select("Function",empty($paramList['posFunctions'])?$selectionList:$selectionList+$paramList['posFunctions'],array(
        'name'=>'data[CleaningStatusFunctionListMapping][Function][]',
        'default'=>(empty($rowVals['function_key'])?0:$rowVals['function_key']),
        'style'=>'width:100%;',
        'empty'=>false
        )
        )
        .'</td>'
        .'</tr>';
        }
        $mappingRow=str_replace("\n","",$mappingRow);        // javascript not allow linefeed
        return $mappingRow;
        }
        ?>

<style type="text/css">
<!--
        .js-running-number-pool:hover{
        background-color: #d9d9d9;
        cursor:pointer;
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

        var existRunningNoCodes=eval("("+'<?php echo json_encode(empty($existRunningNoCodes) ? '' : $existRunningNoCodes); ?>'+")");
        var printQueueNames=eval("("+'<?php echo json_encode(empty($printQueueNames) ? '' : $printQueueNames); ?>'+")");
        var activeLocations=eval("("+'<?php echo json_encode(empty($activeLocations) ? '' : $activeLocations); ?>'+")");
        var activeLocationMsg='<span id="ActiveLocationMsg" style="color:#b22;"><br />'
        +'<?php echo __d('pos', 'active_config_is_found_under_this_location'); ?>'
        +'<input type="hidden" name="data[PosConfig][Support]" value="" id="PosConfigSupport">'
        +'</span>';

        //	Form Validation
        $.validator.methods.regular=function(value,element,param){
        if(!param.test(value)){
        return false;
        }
        return true;
        };

        //	Check value
        $.validator.addMethod('checkValue',function(value,element){

        var variable="<?php echo $variable; ?>";
        var value=$('#PosConfigValue').val();
        if(variable=="connection_setting"||variable=="tender_amount"||
        variable=="auto_switch_from_pay_result_to_starting_page_time_control"||variable=="ordering_timeout"||variable=="check_info_self_define_description"||
        variable=="common_lookup_button_number"||variable=="menu_lookup_button_number"||variable=="set_menu_button_number"||variable=="skip_tips_payment_code"||
        variable=="dutymeal_shop_limit"||variable=="dutymeal_outlet_limit"||variable=="dutymeal_check_limit"||variable=="on_credit_shop_limit"||variable=="on_credit_check_limit"||variable=="on_credit_outlet_limit"||
        variable=="table_mode_row_and_column"||variable=="reprint_guest_check_times"||variable=="reprint_receipt_times"||variable=="number_of_drawer_owned_by_user"||variable=="time_control_to_open_next_check_by_member"){
        if($.trim(value)=="")
        return false;
        if(variable=="auto_switch_from_pay_result_to_starting_page_time_control"||variable=="ordering_timeout"||variable=="dutymeal_outlet_limit"
        ||variable=="dutymeal_check_limit"||variable=="dutymeal_shop_limit"||variable=="on_credit_check_limit"||variable=="on_credit_shop_limit"||variable=="on_credit_outlet_limit"||variable=="reprint_guest_check_times"
        ||variable=="reprint_receipt_times"||variable=="number_of_drawer_owned_by_user"||variable=="time_control_to_open_next_check_by_member"){
        if(!(/^[0-9]+$/.test(value)))
        return false;
        }
        if(variable=="table_mode_row_and_column"){
        if(!(/^[0-9:]+$/.test(value)))
        return false;
        }
        if(variable=="common_lookup_button_number"||variable=="menu_lookup_button_number"||variable=="set_menu_button_number"){
        if((/^[0-9]+$/.test(value)))
        return false;
        try{
        if(!JSON.parse(value))
        return false;
        }
        catch(e){
        return false;
        }
        }
        }

        return true;
        },"");

        //	Check if the code is already existed for running numbers
        $.validator.addMethod('checkCodeExist',function(value,element){
        value=$.trim(value.toLowerCase());
        if($.inArray(value,existRunningNoCodes)==-1)
        return false;
        return true;
        },"");

        //	Check the location
        $.validator.addMethod('checkLocation',function(value,element){

        var location=$('input[name="data[PosConfig][ScfgBy]"]:checked').val();

        if(location=='')
        return true;

        else if(location=='outlet'){
        if($('#PosConfigOutletId').val()==0)
        return false;
        }
        else if(location=='shop'){
        if($('#PosConfigShopId').val()==0)
        return false;
        }
        else if(location=='station'){
        if($('#PosConfigStationId').val()==0)
        return false;
        }

        return true;
        },"");

        //	Check validation
        $("#PosConfigForm").validate({
        rules:{
        'data[PosConfig][ScfgBy]':"checkLocation",
        'data[PosConfig][Value]':"checkValue",
        'data[PosConfig][PoolCode]':{
        "required":true,
        "checkCodeExist":true
        },
        'data[PosConfig][UserId]':{
        "required":true,
        "min":1
        },
        'data[PosConfig][Limit]':{
        "required":true,
        "number":true,
        "min":1
        },
        'data[PosConfig][DefaultCover]':{
        "number":true,
        "min":0
        },
        'data[PosConfig][CoverUpperBound]':{
        "number":true,
        "min":0
        },
        'data[PosConfig][CoverWarning]':{
        "number":true,
        "min":0
        },
        'data[PosConfig][MinimumCheckTotalForAllTables]':{
        "required":true,
        "number":true,
        "min":0
        },
        'data[PosConfig][MinimumChargeItemCode]':{
        "required":function(element){
        return $('input[name="data[PosConfig][MinimumCheckTotalForAllTables]"]').val()>0;
        }
        },
        'data[PosConfig][MaximumCheckTotalForAllTables]':{
        "required":true,
        "number":true,
        "min":function(element){
        if($('input[name="data[PosConfig][MaximumCheckTotalForAllTables]"]').val()==0)
        return 0;
        else
        return $('input[name="data[PosConfig][MinimumCheckTotalForAllTables]"]').val();
        }
        },
        'data[PosConfig][PaymentId]':{
        "required":true,
        "min":1
        }
        },
        messages:{
        'data[PosConfig][ScfgBy]':"<?php echo __d('pos', 'please_select_the_location'); ?>",
        'data[PosConfig][Value]':"<?php echo __('invalid_format'); ?>",
        'data[PosConfig][PoolCode]':{
        "required":"<?php echo '<br />'. __('this_field_is_required'); ?>",
        "checkCodeExist":"<?php echo '<br />'.__d('pos', 'the_code_does_not_exist'); ?>"
        },
        'data[PosConfig][UserId]':{
        "required":"<?php echo __('this_field_is_required'); ?>",
        "min":"<?php echo __('this_field_is_required'); ?>"
        },
        'data[PosConfig][Limit]':{
        "required":"<?php echo __('this_field_is_required'); ?>",
        "number":"<?php echo __('this_field_should_be_a_number'); ?>",
        "min":"<?php echo __('this_field_should_be_a_positive_number'); ?>"
        },
        'data[PosConfig][DefaultCover]':{
        "number":"<?php echo __('this_field_should_be_a_number'); ?>",
        "min":"<?php echo __('this_field_should_be_a_positive_number'); ?>"
        },
        'data[PosConfig][MinimumCheckTotalForAllTables]':{
        "required":"<?php echo __('this_field_is_required'); ?>",
        "number":"<?php echo __('this_field_should_be_a_number'); ?>",
        "min":"<?php echo __('this_field_should_be_a_positive_number'); ?>"
        },
        'data[PosConfig][MinimumChargeItemCode]':{
        "required":"<?php echo __('this_field_is_required'); ?>"
        },
        'data[PosConfig][MaximumCheckTotalForAllTables]':{
        "number":"<?php echo __('this_field_should_be_a_number'); ?>",
        "min":"<?php echo __d('pos', 'this_field_should_be_larger_than_minimum_check_total_for_all_tables'); ?>"
        },
        'data[PosConfig][PaymentId]':{
        "required":"<?php echo __('this_field_is_required'); ?>",
        "min":"<?php echo __('this_field_is_required'); ?>"
        }
        },
        errorPlacement:function(error,element){
        error.appendTo(element.parent("td"));
        },
        errorClass:"warning",
        onkeyup:false
        });

        $('div.UserGroupSelectionDiv :checkbox').change(function(){
        if($(this).prop("checked")){
        $('#Timeout'+$(this).attr('id')).prop('disabled',false);
        $('#Timeout'+$(this).attr('id')).prop('value',$('#Timeout').val());
        }else{
        $('#Timeout'+$(this).attr('id')).prop('disabled',true);
        $('#Timeout'+$(this).attr('id')).prop('value','0');
        }
        });

        //	Show color picker
        $('.js-input-color').ColorPicker({
        onSubmit:function(hsb,hex,rgb,el){
        $(el).val(hex);
        $(el).ColorPickerHide();
        $(el).trigger('change');
        },
        onBeforeShow:function(){
        $(this).ColorPickerSetColor(this.value);
        }
        })
        .bind('click',function(){
        $(this).ColorPickerSetColor(this.value);
        $(this).trigger('change');
        });

        //	Update Color
        $('.js-input-color').change(function(){
        var color=$.trim($(this).val());
        if(color==''){
        color=$(this).attr('ogn_value');
        $(this).val(color);
        }

        // The Tag name of the span : $(this).parent().children(':nth-child(2)').get(0).tagName;
        var boxRegion=$(this).parent().children(':nth-child(2)');
        $(boxRegion).css({
        'background-color':'#'+color
        });
        });

        //	Click Save button
        $('#SaveButton').click(function(){
        if(isFormLoading())
        return false;    //	abort functions when loading content
        if(!$("#PosConfigForm").valid())
        return false;
        if($('input[name="data[PosConfig][ScfgBy]"]:checked').val()=="outlet"&&$('.outletPeriod').length>0&&$('.outletPeriod:checked').length==0){
        $('div#PeriodValidationMessageDiv').html("<?php echo __d('pos','please_select_at_least_one_period'); ?>");
        return false;
        }

        if($('div.UserGroupSelectionDiv :checkbox:checked').length==0&&$('div#UserGroupSelectionMessageDiv').length!=0){
        $('div#UserGroupSelectionMessageDiv').html("<?php echo __d('pos','please_select_at_least_one_user_group'); ?>");
        return false;
        }else{
        $('div#UserGroupSelectionMessageDiv').html("");
        }

        if(!checkPrintQueueRunningNumberValid())
        return false;

        if(!checkMappingValid())
        return false;
        setFormQuit('saving');
        $("#PosConfigForm").submit();
        return false;
        });

        //	Click Reset button
        $('#ResetButton').click(function(){
        if(isFormLoading())
        return false;    //	abort functions when loading content
        $("#PosConfigForm")[0].reset();
        $('input[name="data[PosConfig][ScfgBy]"]:checked').trigger('change');
        $('div#PeriodValidationMessageDiv').html("");
        $('div.UserGroupSelectionDiv :checkbox').prop('checked',false);
        $('#Timeout').prop('value','1');
        $('.UserGroupTimeout').prop('disabled',true);
        $('.UserGroupTimeout').prop('value','0');
        showPeriodList();
        $('.js-payment-method').trigger('change');
        $('.js-print-queue').trigger('change');
        $('.js-screen-saver').trigger('change');
        resetColor();
        return false;
        });

        //	Delete button event
        $('#DeleteButton').click(function(){
        if(isFormLoading())
        return false;    //	abort functions when loading content

        confirmDialog("<?php echo __('confirm_to_delete'); ?> ?","","","",function(){
        setFormQuit('deleting');
        location.href="<?php echo $this->Html->url('/admin'.$langPath.'pos/location_configs/delete/'.$vals['scfg_id'].$backUrlParamsStr); ?>";
        return false;
        });
        return false;
        });

        var updateShopId=0;
        var updateOutletId=0;
        //	Change location
        $('input[name="data[PosConfig][ScfgBy]"]').change(function(){
        //	Show or hide display
        var location=$(this).val();
        $('.js-location').hide();
        $('.js-location[location="'+location+'"]').show();
        if(location=="outlet"){
        $('#periodRow').show();
        }
        else{
        $('#periodRow').hide();
        $('.outletPeriod').prop('checked',false);
        }

        //	Check active config for location
        checkActiveLocation();
        });

        $('select[name="data[PosConfig][ShopId]"]').change(function(){
        //	Show or hide display
        showPeriodList();

        updateShopId=$(this).val();
        });

        $('select[name="data[PosConfig][OutletId]"]').change(function(){
        //	Show or hide display

        showPeriodList();
        $('div#PeriodValidationMessageDiv').html("");
        updateOutletId=$(this).val();
        if(count!=0)
        $('.outletPeriod').prop('checked',false);
        count++;

        });

        function showPeriodList(){
        updateOutletId=$('select[name="data[PosConfig][OutletId]"]').val();
        if(updateOutletId==""){
        return false;
        }

        if(isFormLoading())
        return false;

        //	Call Ajax
        $.ajax({
        type:"POST",
        url:"<?php echo $this->html->url('/admin'.$langPath.'pos/location_configs/load_periods/'); ?>"+updateOutletId+"<?php echo (empty($vals['scfg_value']['period_ids']) ? '' : '/'.$vals['scfg_value']['period_ids'])?>",
        success:function(htmlData){
        //	Error found
        if(htmlData.error!=undefined&&htmlData.error!=''){
        alertDialog('<?php echo __('invalid_data'); ?>','','');
        return false;
        }
        //	Show settings
        $('div#PeriodsDiv').html(htmlData);
        }
        });    //	End Ajax
        }
        //	Click available running number pools link
        $('.js-show-running-number-pools').click(function(){
        var relatedPoolCodeTextId=$(this).attr('relatedPoolCodeTextId');
        if(relatedPoolCodeTextId==undefined)
        relatedPoolCodeTextId='';
        var modalOptions={
        position:["10%"],
        onShow:function(dialog){
        //	Move the X close button to correct position
        var closeLeft=dialog.container.outerWidth()-24+'px';
        dialog.container.find('a.modalCloseX').css({"left":closeLeft});

        //	Click records
        $('.js-running-number-pool').click(function(e){
        var code=$(this).attr('code');
        $('#PosConfigPoolCode'+relatedPoolCodeTextId).val(code);
        $.modal.close();
        });

        //	Cancel event handler for close button
        $('#CloseRunningNumPoolsDialogButton').click(function(e){
        $.modal.close();
        });
        },
        containerCss:{
        'height':'665px',
        'width':'800px'
        }
        };

        var title="<?php echo __d('pos', 'available_running_number_pools'); ?>";
        commonDialog(title,runningNumPoolsDialogObj.html,modalOptions);
        });

        //	Check active location when changing shop/outlet/station id
        $('#PosConfigShopId').on('change',checkActiveLocation);
        $('#PosConfigOutletId').on('change',checkActiveLocation);
        $('#PosConfigStationId').on('change',checkActiveLocation);

        //	Available payment method checkbox is changed
        $('.js-payment-method').change(function(){
        if($(this).is(':checked'))
        $(this).closest('tr').find('input[type="radio"]').removeAttr('disabled').removeClass('field-disabled');
        else
        $(this).closest('tr').find('input[type="radio"]')
        .attr('disabled','disabled')
        .addClass('field-disabled');
        });

        //	Available print queue checkbox is changed
        $('.js-print-queue').change(function(){
        var printQueueId=$(this).attr('printQueueId');
        if($(this).is(':checked'))
        $('#PosConfigPoolCode'+printQueueId).removeAttr('disabled').removeClass('field-disabled');
        else{
        $('#PosConfigPoolCode'+printQueueId).val('');
        $('#PosConfigPoolCode'+printQueueId).attr('disabled','disabled').addClass('field-disabled');
        }
        });

        //	Available screen saver checkbox is changed
        $('.js-screen-saver').change(function(){
        if($(this).val()=='m')
        $('.js-screen-saver-text').attr('disabled','disabled').addClass('field-disabled');
        else
        $('.js-screen-saver-text').removeAttr('disabled').removeClass('field-disabled');
        });

        //	Change support cleaning table status
        $('input[name="data[PosConfig][SupportTableStatusCleaning]"]').change(function(){
        if($('#PosConfigSupportTableStatusCleaning').is(':checked')){
        $('#PosConfigChangeCleaningToVacantInterval')
        .val('0')
        .attr('disabled','disabled')
        .addClass('field-disabled');
        }
        else{
        $('#PosConfigChangeCleaningToVacantInterval').removeAttr('disabled').removeClass('field-disabled');
        }
        });

        //	Call number input method is change
        $('select[name="data[PosConfig][Method]"]').change(function(){
        if($('#PosConfigMethod').val()=='m'){
        $('.js-imagefile').hide();
        //$('#PosConfigImageFileForScanMode').attr('disabled', 'disabled').addClass('field-disabled');
        }
        else{
        $('.js-imagefile').show();
        //$('#PosConfigImageFileForScanMode').removeAttr('disabled').removeClass('field-disabled');
        }
        });

        ////////////////////////////////////////////////////////////////////////
        //	For Mappings

        //	Handle add row for new mapping event
        $('.js-add-mapping-row').click(function(){
        generateNewMappingRowContent();
        return false;
        });

        //	Generate a new row of mapping
        function generateNewMappingRowContent(){
<?php
        if($variable=='payment_check_types'){
        echo"rowContent = '".generateMappingRowContent($this,'paymentCheckType',array('posPaymentMethods'=>$posPaymentMethods,'posCustomTypes'=>$posCustomTypes),array())."';";
        echo'$("#PaymentCheckTypeMapping").append(rowContent);';
        }
        else if($variable=='new_check_auto_functions'){
        echo"rowContent = '".generateMappingRowContent($this,'newCheckAutoFunction',array('posFunctions'=>$posFunctions),array())."';";
        echo'$("#NewCheckAutoFunctionMapping").append(rowContent);';
        }
        else if($variable=='pay_check_auto_functions'){
        echo"rowContent = '".generateMappingRowContent($this,'payCheckAutoFunction',array('posFunctions'=>$posFunctions),array())."';";
        echo'$("#PayCheckAutoFunctionMapping").append(rowContent);';
        }
        else if($variable=='display_check_extra_info_in_ordering_basket'){
        echo"rowContent = '".generateMappingRowContent($this,'checkExtraInfoInOrderingBasket',$availabeCheckExtraInfo,array())."';";
        echo'$("#CheckExtraInfoInOrderingBasketMapping").append(rowContent);';
        }
        else if($variable=='gratuity_setting'){
        echo"rowContent = '".generateMappingRowContent($this,'gratuityCoverControl',array('posGratuities'=>$posGratuities),array())."';";
        echo'$("#GratuityCoverControlMapping").append(rowContent);';
        }
        else if($variable=='item_function_list'){
        echo"rowContent = '".generateMappingRowContent($this,'itemFunctionList',array('posFunctions'=>$posFunctions),array())."';";
        echo'$("#ItemFunctionListMapping").append(rowContent);';
        }
        else if($variable=='auto_track_cover_based_on_item_ordering'){
        echo"rowContent = '".generateMappingRowContent($this,'itemGroupList',array('posMenuItemGroup'=>$itemGroupOpts),array())."';";
        echo'$("#ItemGroupListMapping").append(rowContent);';
        }
        else if($variable=='repeat_round_items_limitation'){
        echo"rowContent = '".generateMappingRowContent($this,'itemDepartmentList',array('posMenuItemDept'=>$itemDeptOpts),array())."';";
        echo'$("#ItemDeptListMapping").append(rowContent);';
        }
        else if($variable=='payment_rounding_dummy_payment_mapping'){
        echo"rowContent = '".generateMappingRowContent($this,'dummyPaymentMapping',array('posPaymentMethods'=>$posPaymentMethods,'dummyPaymentMethods'=>$posPaymentMethods),array())."';";
        echo'$("#DummyPaymentMethodMapping").append(rowContent);';
        }
        else if($variable=='table_floor_plan_setting'){
        echo"rowContent = '".generateMappingRowContent($this,'cleaningStatusFunctionList',array('posFunctions'=>$posFunctions),array())."';";
        echo'$("#CleaningStatusFunctionListMapping").append(rowContent);';
        }
        ?>
        bindMappingRowEvent();

        }

        //	Bind event for mapping rows
        function bindMappingRowEvent(){
        //	Handle remove row for existing cover discount mapping event
        $('.js-remove-mapping-row').unbind('click')            //	Must unbind the click remove row event first
        .bind('click',clickRemoveMappingRowEvent);
        $('.js-input-data').unbind('click')                    //	Rebind focus event
        .bind('click',function(){$(this).select();});
        }

        //	Click Remove Row event
        function clickRemoveMappingRowEvent(event){
        if(isFormLoading())
        return false;    //	abort functions when loading content

        //	Clear last confirmDialog flag (if any)
        $('.js-remove-mapping-row').attr('confirmDialog','');

        //	Mark this row
        $(this).attr('confirmDialog','1');

        confirmDialog("<?php echo __d('pos', 'are_you_sure_to_remove_this_mapping_after_submission'); ?> ?","","","",function(){
        var row=$('.js-remove-mapping-row[confirmDialog="1"]').closest('tr');
        row.remove();

        return false;
        });

        return false;
        }

        //	Check if print queue running numbers are valid
        function checkPrintQueueRunningNumberValid(){
        var isValid=true;
        var variable='<?php echo $variable; ?>';
        if(variable!='item_print_queue_running_number')
        return true;

        for(var printQueueId in printQueueNames){
        var checkboxValue=$('#PosConfigPrintQueueName'+printQueueId)[0].checked;
        var msg='';
        if(checkboxValue){
        var textboxValue=$('#PosConfigPoolCode'+printQueueId).val();
        if(textboxValue=='')
        msg="<?php echo '<br />'. __('this_field_is_required'); ?>"
        else{
        textboxValue=$.trim(textboxValue.toLowerCase());
        if($.inArray(textboxValue,existRunningNoCodes)==-1)
        msg="<?php echo '<br />'.__d('pos', 'the_code_does_not_exist'); ?>"
        }

        if(msg!=''){
        $('#PosConfigPoolCode'+printQueueId).parent().append('<label class="warning">'+msg+'</label>');
        isValid=false;
        }
        }
        }
        return isValid;
        }

        //	Check if mappings are valid
        function checkMappingValid(){
        var variable='<?php echo $variable; ?>';
        if(variable!='payment_check_types'&&variable!='new_check_auto_functions'&&variable!='pay_check_auto_functions'&&
        variable!='display_check_extra_info_in_ordering_basket'&&variable!='gratuity_setting'&&variable!='item_function_list'
        &&variable!='payment_rounding_dummy_payment_mapping'&&variable!='table_floor_plan_setting')
        return true;

        $('.input-mappings').parent('td').find('label.warning').remove();
        if(variable=='payment_check_types'){
        //	Check fields
        var msg='';
        var selectedVals=[];
        $('select[name="data[PaymentCheckTypeMapping][PaymentMethod][]"]').each(function(idx,element){
        var paymentMethod=$(element).val();
        var customType=$(element).parent('td').parent('tr').find('select[name="data[PaymentCheckTypeMapping][CustomType][]"]').val();
        if(paymentMethod==0||customType==0)
        msg='<?php echo __('all_fields_are_required'); ?>';

        //	Check if payment method is duplicated
        if($.inArray(paymentMethod,selectedVals)!=-1)
        msg='<?php echo __d('pos', 'only_one_custom_type_is_allowed_for_one_payment_method'); ?>';
        selectedVals.push(paymentMethod);

        if(msg!='')
        return false;
        });
        if(msg!=''){
        $('#PaymentCheckTypeMapping').parent().append('<label class="warning">'+msg+'</label>');
        return false;
        }
        }
        else if(variable=='new_check_auto_functions'){
        //	Check fields
        var msg='';
        var selectedCombination=[];    //	For checking whehter the same sequence contains same function
        $('select[name="data[NewCheckAutoFunctionMapping][Function][]"]').each(function(idx,element){
        var posFunction=$(element).val();
        var failHandling=$(element).parent('td').parent('tr').find('select[name="data[NewCheckAutoFunctionMapping][FailHandling][]"]').val();
        var seq=$(element).parent('td').parent('tr').find('input[name="data[NewCheckAutoFunctionMapping][Seq][]"]').val();
        if(posFunction==0||failHandling==0||seq=='')        //	Empty fields
        msg='<?php echo __('all_fields_are_required'); ?>';
        else if(seq==0||!/^[0-9]*$/.exec(seq))        //	Invalid seq
        msg='<?php echo __('invalid_format_for_seq'); ?>';

        //	Check if the same function is set on the same seq
        var functionSeqString=posFunction+seq;
        if($.inArray(functionSeqString,selectedCombination)!=-1)
        msg='<?php echo __d('pos', 'functions_cannot_be_duplicated_in_the_same_sequence'); ?>';
        selectedCombination.push(functionSeqString);

        if(msg!='')
        return false;
        });
        if(msg!=''){
        $('#NewCheckAutoFunctionMapping').parent().append('<label class="warning">'+msg+'</label>');
        return false;
        }
        }
        else if(variable=='pay_check_auto_functions'){
        //	Check fields
        var msg='';
        var selectedCombination=[];    //	For checking whehter the same sequence contains same function
        $('select[name="data[PayCheckAutoFunctionMapping][Function][]"]').each(function(idx,element){
        var posFunction=$(element).val();
        var failHandling=$(element).parent('td').parent('tr').find('select[name="data[PayCheckAutoFunctionMapping][FailHandling][]"]').val();
        var seq=$(element).parent('td').parent('tr').find('input[name="data[PayCheckAutoFunctionMapping][Seq][]"]').val();
        if(posFunction==0||failHandling==0||seq=='')        //	Empty fields
        msg='<?php echo __('all_fields_are_required'); ?>';
        else if(seq==0||!/^[0-9]*$/.exec(seq))        //	Invalid seq
        msg='<?php echo __('invalid_format_for_seq'); ?>';

        //	Check if the same function is set on the same seq
        var functionSeqString=posFunction+seq;
        if($.inArray(functionSeqString,selectedCombination)!=-1)
        msg='<?php echo __d('pos', 'functions_cannot_be_duplicated_in_the_same_sequence'); ?>';
        selectedCombination.push(functionSeqString);

        if(msg!='')
        return false;
        });
        if(msg!=''){
        $('#PayCheckAutoFunctionMapping').parent().append('<label class="warning">'+msg+'</label>');
        return false;
        }
        }
        else if(variable=='display_check_extra_info_in_ordering_basket'){
        //	Check fields
        var msg='';
        var selectedCombination=[];    //	For checking whehter the same sequence contains same function
        $('select[name="data[CheckExtraInfoInOrderingBasketMapping][CheckExtraInfo][]"]').each(function(idx,element){
        var availableCheckExtraInfo=$(element).val();
        if(availableCheckExtraInfo==0)        //	Empty fields
        msg='<?php echo __('all_fields_are_required'); ?>';

        //	Check if the same function is set on the same seq
        var checkExtraInfoString=availableCheckExtraInfo;
        if($.inArray(checkExtraInfoString,selectedCombination)!=-1)
        msg='<?php echo __d('pos', 'extra_information_cannot_be_duplicated'); ?>';
        selectedCombination.push(checkExtraInfoString);

        if(msg!='')
        return false;
        });
        if(msg!=''){
        $('#CheckExtraInfoInOrderingBasketMapping').parent().append('<label class="warning">'+msg+'</label>');
        return false;
        }
        }
        else if(variable=='gratuity_setting'){
        //	Check fields
        var msg='';
        var selectedVals=[];
        $('select[name="data[GratuityCoverControlMapping][Gratuity][]"]').each(function(idx,element){
        var gratuityId=$(element).val();
        var minCover=$(element).parent('td').parent('tr').find('input[name="data[GratuityCoverControlMapping][MinCover][]"]').val();
        var maxCover=$(element).parent('td').parent('tr').find('input[name="data[GratuityCoverControlMapping][MaxCover][]"]').val();

        if(gratuityId==0||minCover==''||maxCover=='')        //	Empty fields
        msg='<?php echo __('all_fields_are_required'); ?>';
        else if(!/^[0-9]*$/.exec(minCover)||!/^[0-9]*$/.exec(maxCover))        //	Invalid cover
        msg='<?php echo __('invalid_format_for_cover'); ?>';
        else if(minCover==0&&maxCover==0)
        msg='<?php echo __d('pos', 'min_cover_and_max_cover_cannot_be_both_zero'); ?>';
        else if(minCover!=0&&maxCover!=0&&(parseInt(minCover)>parseInt(maxCover)))
        msg='<?php echo __d('pos', 'max_cover_should_be_larger_than_min_cover'); ?>';
        selectedVals.push(gratuityId);

        if(msg!='')
        return false;
        });
        if(msg!=''){
        $('#GratuityCoverControlMapping').parent().append('<label class="warning">'+msg+'</label>');
        return false;
        }
        }
        else if(variable=='item_function_list'){
        //	Check fields
        var msg='';
        var selectedFunctions=[];    //	For checking whehter the same sequence contains same function
        $('select[name="data[ItemFunctionListMapping][Function][]"]').each(function(idx,element){
        var posFunction=$(element).val();
        if(posFunction==0)        //	Empty fields
        msg='<?php echo __('all_fields_are_required'); ?>';

        //	Check if the same function is set
        var functionString=posFunction;
        if($.inArray(functionString,selectedFunctions)!=-1)
        msg='<?php echo __d('pos', 'functions_cannot_be_duplicated'); ?>';
        selectedFunctions.push(functionString);

        if(msg!='')
        return false;
        });
        if(msg!=''){
        $('#ItemFunctionListMapping').parent().append('<label class="warning">'+msg+'</label>');
        return false;
        }
        }
        else if(variable=='payment_rounding_dummy_payment_mapping'){
        //	Check fields
        var msg='';
        var selectedVals=[];
        $('select[name="data[DummyPaymentMethodMapping][PaymentMethod][]"]').each(function(idx,element){
        var paymentMethod=$(element).val();
        var dummyPaymentMethod=$(element).parent('td').parent('tr').find('select[name="data[DummyPaymentMethodMapping][DummyPaymentMethod][]"]').val();
        if(paymentMethod==0||dummyPaymentMethod==0)
        msg='<?php echo __('all_fields_are_required'); ?>';

        //	Check if payment method is duplicated
        if($.inArray(paymentMethod,selectedVals)!=-1)
        msg='<?php echo __d('pos', 'only_one_dummy_payment_method_is_allowed_for_one_payment_method'); ?>';
        selectedVals.push(paymentMethod);

        if(msg!='')
        return false;
        });
        if(msg!=''){
        $('#DummyPaymentMethodMapping').parent().append('<label class="warning">'+msg+'</label>');
        return false;
        }
        }
        else if(variable=='table_floor_plan_setting'){
        //	Check fields
        var msg='';
        var selectedFunctions=[];    //	For checking whehter the same sequence contains same function
        $('select[name="data[CleaningStatusFunctionListMapping][Function][]"]').each(function(idx,element){
        var posFunction=$(element).val();
        if(posFunction==0)        //	Empty fields
        msg='<?php echo __('all_fields_are_required'); ?>';

        //	Check if the same function is set
        var functionString=posFunction;
        if($.inArray(functionString,selectedFunctions)!=-1)
        msg='<?php echo __d('pos', 'functions_cannot_be_duplicated'); ?>';
        selectedFunctions.push(functionString);

        if(msg!='')
        return false;
        });
        if(msg!=''){
        $('#CleaningStatusFunctionListMapping').parent().append('<label class="warning">'+msg+'</label>');
        return false;
        }
        }
        return true;
        }

        ////////////////////////////////////////////////////////////////////////
        //	Initialize functions

        $('.js-payment-method').trigger('change');
        $('.js-print-queue').trigger('change');
        $('.js-screen-saver:checked').trigger('change');
        $('select[name="data[PosConfig][Method]"]').trigger('change');
        bindMappingRowEvent();

        //	Enable search function in selection boxes
        if($.isFunction($.fn.select2))
        $('#PosConfigStationId').select2();

        $('input[name="data[PosConfig][ScfgBy]"]:checked').trigger('change');

        var count=0;
        $('select[name="data[PosConfig][OutletId]"]').trigger('change');
        showPeriodList();

        var runningNumPoolsDialogObj={};    //	Declare the object of available running number pools dialog
        runningNumPoolsDialogObj.html=$('#RunningNumPoolsDialog').html();
        $('#RunningNumPoolsDialog').html('');    // reset to empty to avoid duplicate id in form

        ////////////////////////////////////////////////////////////////////////
        //	Internal functions

        //	Disable support input if another config under the location is active
        function checkActiveLocation(){
        var location=$('input[name="data[PosConfig][ScfgBy]"]:checked').val();
        var id=$('#PosConfig'+location.charAt(0).toUpperCase()+location.slice(1)+'Id option:selected').val();
        id=(location==''?0:id);

        $('input[name="data[PosConfig][Support]"]').removeAttr('disabled');
        $('#ActiveLocationMsg').remove();
        //	Check active config
        for(var i=0;i<activeLocations.length;i++){
        if(location==activeLocations[i].scfg_by&&id==activeLocations[i].scfg_record_id){
        //	Location that another config is active
        $('input:radio[name="data[PosConfig][Support]"]').filter('[value=""]').prop('checked',true);
        $('input[name="data[PosConfig][Support]"]').attr('disabled','disabled');
        $('#ActiveLocationMsg').remove();
        $('#PosConfigSupport').closest('td').append(activeLocationMsg);
        bAnotherActive=true;
        break;
        }
        }
        }

        ////////////////////////////////////////////////////////////////////////
        //	Internal functions

        //	Reset color
        function resetColor(){
        $('.js-input-color').filter(function(index){
        $(this).siblings('.select-color').css('background-color','#'+$(this).val());
        });
        }

        });
</script>

<?php
        echo $this->Form->create('PosConfig',array(
        'id'=>'PosConfigForm',
        'name'=>'PosConfigForm',
        'url'=>'/admin'.$langPath.'pos/location_configs/save'.(empty($posConfig)?'':'/'.$vals['scfg_id']).$backUrlParamsStr,
        )
        );

        echo $this->Form->hidden('Section',array('value'=>$section));
        echo $this->Form->hidden('Variable',array('value'=>$variable));

        ///////////////////////////////////////////////////////////////////////////////
        // Show basic information
        echo'<div class="zone-body">';    //	Start zone-body

        echo $this->element('frame_basic_top',array(
        'title'=>(empty($posConfig)?__('add_new'):__('edit')).' - '.__d('pos','config_by_location').' : '.__d('pos',$variableKeyMapping[$variable]).(empty($posConfig)?'':' ('.$vals['scfg_record'].')'),
        'color'=>'grey',
        'minHeight'=>'580px',
        )
        );

        ///////////////////////////////////////////////////////////////////////////
        //	Create breadcrumb
        $breadCrumbLists=array(
        array('name'=>__d('pos','config_by_location'),'link'=>$this->Html->url('/admin'.$langPath.'pos/location_configs/listing'.$backUrlParamsStr)),
        array('name'=>__d('pos',$variableKeyMapping[$variable]),'link'=>$this->Html->url('/admin'.$langPath.'pos/location_configs/config/'.$variable.$backUrlParamsStr)),
        );
        if(empty($posConfig))
        $breadCrumbLists[]=array('name'=>__('add_new'),'link'=>'');
        else
        $breadCrumbLists[]=array('name'=>$vals['scfg_record'],'link'=>'');

        echo $this->element('breadcrumb',array('lists'=>$breadCrumbLists));

        ///////////////////////////////////////////////////////////////////////////
        //	Start the edit area
        echo'<table class="sheet-basic sheet-border" width="99%">';

        //	Apply to
        $location=array(
        'shop'=>__d('outlet','shop'),
        'outlet'=>__d('outlet','outlet'),
        'station'=>__d('pos','station')
        );
        if(empty($existIds['all'])&&empty($bNoAllLocation))
        $location=array(''=>__d('pos','all_locations'))+$location;
        echo'<tr>'
        .'<th width="20%">'.__d('pos','apply_to').' <em>*</em></th>'
        .'<td>'
        .$this->Form->radio("ScfgBy",$location,array('value'=>$vals['scfg_by'],'legend'=>false,'empty'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;')).'<br>'
        .'</td>'
        .'</tr>';

        //	Shop
        $shopOpts=array(0=>'--- '.__('please_select').' ---')+$shopOpts;
        $selectShopId=($vals['scfg_by']=='shop'?$vals['scfg_record_id']:0);
        echo'<tr class="js-location" location="shop">'
        .'<th>'.__d('outlet','shop').' <em>*</em></th>'
        .'<td>'
        .$this->Form->select('ShopId',$shopOpts,array('style'=>'width:300px;','default'=>$selectShopId,'empty'=>false))
        .'</td>'
        .'</tr>';

        //	Outlet
        $outletOpts=array(0=>'--- '.__('please_select').' ---')+$outletOpts;
        $selectOutletId=($vals['scfg_by']=='outlet'?$vals['scfg_record_id']:0);
        echo'<tr class="js-location" location="outlet">'
        .'<th>'.__d('outlet','outlet').' <em>*</em></th>'
        .'<td>'
        .$this->Form->select('OutletId',$outletOpts,array('style'=>'width:300px;','default'=>$selectOutletId,'empty'=>false))
        .'</td>'
        .'</tr>';

        //	Station
        $stationOpts=array(0=>'--- '.__('please_select').' ---')+$stationOpts;
        $selectStationId=($vals['scfg_by']=='station'?$vals['scfg_record_id']:0);
        echo'<tr class="js-location" location="station">'
        .'<th>'.__d('pos','station').' <em>*</em></th>'
        .'<td>'
        .$this->Form->select('StationId',$stationOpts,array('style'=>'width:300px;','default'=>$selectStationId,'empty'=>false))
        .'</td>'
        .'</tr>';

        //	Value
        switch($variable){
        case'connection_setting':
        echo'<tr>'
        .'<th>'.__('value').' <em>*</em></th>'
        .'<td>'
        .$this->Form->text('Value',array('class'=>'js-input-data','value'=>$vals['scfg_value'],'maxLength'=>255,'style'=>'width:400px;'))
        .'<br>'.__('format').' : [ '.__('ip_address').':'.__('port_no').' ]'
        .'<br></td>'
        .'</tr>';
        break;
        case'auto_switch_from_pay_result_to_starting_page_time_control':
        case'ordering_timeout':
        echo'<tr>'
        .'<th>'.__('value').' <em>*</em></th>'
        .'<td>'
        .$this->Form->text('Value',array('class'=>'js-input-data','value'=>$vals['scfg_value'],'maxLength'=>255,'style'=>'width:50px;'))
        .'&nbsp;'.__('seconds')
        .($variable=='ordering_timeout'?' (0 - '.__d('pos','no_timeout').')':'')
        .'<br></td>'
        .'</tr>';
        break;
        case'time_control_to_open_next_check_by_member':
        echo'<tr>'
        .'<th>'.__('value').' <em>*</em></th>'
        .'<td>'
        .$this->Form->text('Value',array('class'=>'js-input-data','value'=>$vals['scfg_value'],'maxLength'=>255,'style'=>'width:50px;'))
        .'&nbsp;'.__('minutes')
        .($variable=='time_control_to_open_next_check_by_member'?' (0 - '.__d('pos','no_timeout').')':'')
        .'<br></td>'
        .'</tr>';
        break;
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
        case'remove_check_type_for_release_payment':
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
        echo'<tr>'
        .'<th>'.__('value').'</th>'
        .'<td>'
        .$this->Form->radio('Value',$yesNoOpts,array('value'=>$vals['scfg_value'],'legend'=>false,'empty'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'<br></td>'
        .'</tr>';
        break;
        case'cashier_settlement_mode':
        $modeOpts=array(''=>__d('user','user'),'o'=>__d('outlet','outlet'));
        echo'<tr>'
        .'<th>'.__('value').'</th>'
        .'<td>'
        .$this->Form->radio('Value',$modeOpts,array('value'=>$vals['scfg_value'],'legend'=>false,'empty'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'<br></td>'
        .'</tr>';
        break;
        case'new_check_auto_functions':
        $supportOpts=array('y'=>__('yes'),'n'=>__('no'));
        echo'<tr>'
        .'<th>'.__('value').'</th>'
        .'<td>';

        //	Mappings
        echo'<table id="NewCheckAutoFunctionMapping" class="list-basic list-border header-align-center input-mappings" style="margin:0px auto 10px;" width="99%">';
        echo'<tr>'
        .'<tr>'
        .'<td colspan="2">'.__d('pos','support_for_split_check').'</td>'
        .'<td colspan="2">'
        .$this->Form->radio('SupportForSplitCheck',$supportOpts,array('value'=>isset($vals['scfg_value']['support_for_split_check'])?$vals['scfg_value']['support_for_split_check']:'n','empty'=>false,'legend'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        .'</tr>'
        .'</tr>';
        echo'<tr>'
        .'<th></th>'    //	Delete button
        .'<th width="43%">'.__d('pos','function').'</th>'
        .'<th width="43%">'.__d('pos','failure_handling').'</th>'
        .'<th width="10%">'.__('seq').'</th>'
        .'</tr>';

        if(!empty($vals['scfg_value'])){
        $mappings=$vals['scfg_value'];
        if(isset($mappings['function']))
        $mappings=$mappings['function'];
        foreach($mappings as $mapping)
        echo generateMappingRowContent($this,'newCheckAutoFunction',array('posFunctions'=>$posFunctions),$mapping);
        }
        echo'</table>';

        //	Add row button
        echo'<div style="float:right; padding-right:10px;">'
        .$this->element('button',array('id'=>'AddRowButton','content'=>__d('pos','add_row'),'class'=>'button-basic-2','appendClass'=>'js-add-mapping-row'))
        .'</div>';

        echo'</td>'
        .'</tr>';
        break;
        case'display_check_extra_info_in_ordering_basket':
        $supportOpts=array('y'=>__('yes'),'n'=>__('no'));
        echo'<tr>'
        .'<th>'.__('value').'</th>'
        .'<td>';
        //	Mappings
        echo'<table id="CheckExtraInfoInOrderingBasketMapping" class="list-basic list-border header-align-center input-mappings" style="margin:0px auto 10px;" width="99%">';
        echo'<tr>'
        .'<tr>'
        .'<td width="3%">'
        .'<td width="30%">'.__d('pos','always_reset_extra_info_window_size').'</td>'
        .'<td width="63%">'
        .$this->Form->radio('AlwaysResetExtraInfoWindowSize',$supportOpts,array('value'=>isset($vals['scfg_value']['always_reset_extra_info_window_size'])?$vals['scfg_value']['always_reset_extra_info_window_size']:'n','empty'=>false,'legend'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        .'</tr>'
        .'<th></th>'    //	Delete button
        .'<th colspan="2" width="96%">'.__d('pos','display_information').'</th>'
        .'</tr>';
        if(isset($vals['scfg_value']['check_extra_info_list'])){
        foreach($vals['scfg_value']['check_extra_info_list']as $mapping){
        if(!empty($mapping))
        echo generateMappingRowContent($this,'checkExtraInfoInOrderingBasket',$availabeCheckExtraInfo,$mapping);
        }
        }
        echo'</table>';

        //	Add row button
        echo'<div style="float:right; padding-right:10px;">'
        .$this->element('button',array('id'=>'AddRowButton','content'=>__d('pos','add_row'),'class'=>'button-basic-2','appendClass'=>'js-add-mapping-row'))
        .'</div>';

        echo'</td>'
        .'</tr>';
        break;
        case'gratuity_setting':
        echo'<tr>'
        .'<th>'.__('value').'</th>'
        .'<td>';

        //	Mappings
        echo'<table id="GratuityCoverControlMapping" class="list-basic list-border header-align-center input-mappings" style="margin:0px auto 10px;" width="99%">';
        echo'<tr>'
        .'<th></th>'    //	Delete button
        .'<th width="56%">'.__d('pos','gratuity').'</th>'
        .'<th width="20%">'.__d('pos','min_cover').'</th>'
        .'<th width="20%">'.__d('pos','max_cover').'</th>'
        .'</tr>';

        if(!empty($vals['scfg_value']['cover_control'])){
        foreach($vals['scfg_value']['cover_control']as $mapping)
        echo generateMappingRowContent($this,'gratuityCoverControl',array('posGratuities'=>$posGratuities),$mapping);
        }
        echo'</table>';

        //	Add row button
        echo'<div style="float:right; padding-right:10px;">'
        .$this->element('button',array('id'=>'AddRowButton','content'=>__d('pos','add_row'),'class'=>'button-basic-2','appendClass'=>'js-add-mapping-row'))
        .'</div>';

        echo'</td>'
        .'</tr>';
        break;
        case'support_partial_payment':
        $yesNoOpts=array('y'=>__('yes'),''=>__('no'));
        echo'<tr>'
        .'<th>'.__('value').' <em>*</em></th>'
        .'<td>'
        .'<table style="border-collapse:collapse;" width="99%">'
        .'<tr>'
        .'<td width="40%">'
        .__d('pos','support_partial_payment').' :'
        .'</td>'
        .'<td>'
        .$this->Form->radio('Support',$yesNoOpts,array('value'=>isset($vals['scfg_value']['support_partial_payment'])?$vals['scfg_value']['support_partial_payment']:'y','empty'=>false,'legend'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        .'</tr>'
        .'<tr>'
        .'<td width="40%">'
        .__d('pos','continue_to_pay_after_settling_partial_payment').' :'
        .'</td>'
        .'<td>'
        .$this->Form->radio('ContinueToPay',$yesNoOpts,array('value'=>isset($vals['scfg_value']['continue_to_pay_after_settling_partial_payment'])?$vals['scfg_value']['continue_to_pay_after_settling_partial_payment']:'','empty'=>false,'legend'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        .'</tr>'
        .'<tr>'
        .'<td width="40%">'
        .__d('pos','print_receipt_only_when_finish_all_payment').' :'
        .'</td>'
        .'<td>'
        .$this->Form->radio('PrintReceiptOnlyWhenFullPay',$yesNoOpts,array('value'=>isset($vals['scfg_value']['print_receipt_only_when_finish_all_payment'])?$vals['scfg_value']['print_receipt_only_when_finish_all_payment']:'','empty'=>false,'legend'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        .'</tr>'
        .'<tr>'
        .'<td width="40%">'
        .__d('pos','void_all_payment_after_release_payment').' :'
        .'</td>'
        .'<td>'
        .$this->Form->radio('VoidAllPaymentAfterReleasePayment',$yesNoOpts,array('value'=>isset($vals['scfg_value']['void_all_payment_after_release_payment'])?$vals['scfg_value']['void_all_payment_after_release_payment']:'','empty'=>false,'legend'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        .'</tr>'
        .'</table>'
        .'</td>'
        .'</tr>';
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
        case'ordering_basket_item_grouping_method':
        case'dutymeal_limit_reset_period':
        case'on_credit_limit_reset_period':
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
        else if($variable=='ordering_basket_item_grouping_method')
        $selectOpts=array('l'=>__d('pos','combine_last_item_in_ordering_basket'),'a'=>__d('pos','combine_items_in_ordering_basket'));
        else if($variable=='dutymeal_limit_reset_period'||$variable=='on_credit_limit_reset_period')
        $selectOpts=array('m'=>__('monthly'),'d'=>__('daily'));
        echo'<tr>'
        .'<th>'.__('value').'</th>'
        .'<td>'
        .$this->Form->select('Value',$selectOpts,array('style'=>'width:200px;','default'=>$vals['scfg_value'],'empty'=>false))
        .'<br></td>'
        .'</tr>';
        break;
        case'fast_food_not_print_receipt':
        case'fine_dining_not_print_receipt':
        case'ordering_basket_toggle_consolidate_items_grouping_method':
        if($variable=='fast_food_not_print_receipt'||$variable=='fine_dining_not_print_receipt')
        $selectOpts=array(''=>__('no'),'y'=>__('yes'),'o'=>__d('pos','ask_option_to_print_receipt'),'e'=>__d('pos','send_email_receipt_only'));
        else if($variable=='ordering_basket_toggle_consolidate_items_grouping_method')
        $selectOpts=array('o'=>__d('pos','grouping_old_items_only'),'s'=>__d('pos','grouping_old_and_new_items_separately'),'a'=>__d('pos','grouping_both_old_and_new_items'));
        echo'<tr>'
        .'<th>'.__('value').'</th>'
        .'<td>'
        .$this->Form->select('Value',$selectOpts,array('style'=>'width:300px;','default'=>$vals['scfg_value'],'empty'=>false))
        .'<br></td>'
        .'</tr>';
        break;
        case'call_number_input_setting':
        $methodOpts=array('m'=>__d('pos','manual_input'),'s'=>__d('pos','scan'));
        echo'<tr>'
        .'<th>'.__('value').' <em>*</em></th>'
        .'<td>'
        .'<table style="border-collapse:collapse;" width="99%">'
        //	Method
        .'<tr>'
        .'<td width="40%">'
        .__d('pos','input_method')    // new word: 'input_method'
        .'</td>'
        .'<td>'
        .$this->Form->select('Method',$methodOpts,array('style'=>'width:200px;','default'=>isset($vals['scfg_value']['method'])?$vals['scfg_value']['method']:'s','empty'=>false))
        .'</td>'
        .'</tr>'
        //	File image
        .'<tr class="js-imagefile">'
        .'<td>'
        .__d('pos','image_file_for_scan_mode')    // new word: 'image_file_for_scan_mode'
        .'</td>'
        .'<td>'
        .$this->Form->text('ImageFileForScanMode',array('class'=>'js-input-data','value'=>isset($vals['scfg_value']['method'])?$vals['scfg_value']['image_file_for_scan_mode']:'','maxLength'=>128,'style'=>'width:193px;margin-right:10px;'))
        .'</td>'
        .'</tr>';
        echo'</table>'
        .'</td>'
        .'</tr>';
        break;
        case'tender_amount':
        echo'<tr>'
        .'<th>'.__('value').' <em>*</em><br>('.__('separated_by_linefeed').')</th>'
        .'<td>'
        .$this->Form->textarea('Value',array('class'=>'js-input-data','value'=>$vals['scfg_value'],'style'=>'width:250px;height:80px;'))
        .'<br></td>'
        .'</tr>';
        break;
        case'check_info_self_define_description':
        echo'<tr>'
        .'<th>'.__('value').' <em>*</em><br>('.__('separated_by_linefeed').')</th>'
        .'<td>'
        .$this->Form->textarea('Value',array('class'=>'js-input-data','value'=>$vals['scfg_value'],'style'=>'width:250px;height:80px;'))
        .'<br>'.__d('pos','example').'1 :'
        .'<br>'.__d('pos','self_defined_value').'1'
        .'<br>'.__d('pos','self_defined_value').'2'
        .'<br>'.__d('pos','self_defined_value').'3'
        .'<br>'.__d('pos','self_defined_value').'4'
        .'<br>'.__d('pos','self_defined_value').'5'
        .'<br>'
        .'<br> ***'.__d('pos','if_no_value_for_specific_info_please_provide_linefeed')
        .'<br></td>'
        .'</tr>';
        break;
        case'void_reason_for_payment_auto_discount':
        echo'<tr>'
        .'<th>'.__('value').' <em>*</em></th>'
        .'<td>'
        .$this->Form->text('Value',array('class'=>'js-input-data','value'=>$vals['scfg_value'],'maxLength'=>255,'style'=>'width:100px;'))
        .'&nbsp;('.__d('pos','void_discount_reason_code').')'
        .'<br></td>'
        .'</tr>';
        break;
        case'barcode_ordering_format':
        echo'<tr>'
        .'<th>'.__('value').' <em>*</em></th>'
        .'<td>'
        .$this->Form->text('Value',array('class'=>'js-input-data','value'=>$vals['scfg_value'],'maxLength'=>255,'style'=>'width:400px;'))
        .'<br>'.__('format').' :'
        .'<br>'.' I : '.__d('pos','item_code')
        .'<br>'.' S : '.__d('pos','sku')
        .'<br>'.' A : '.__d('pos','item_total_whole_number_part')
        .'<br>'.' D : '.__d('pos','item_total_decimal_part')
        .'<br>'
        .'<br>'.__d('pos','example').' :'
        .'<br> IIIIAAAADD'
        .'<br></td>'
        .'</tr>';
        break;
        case'menu_mode':
        echo'<tr>'
        .'<th>'.__('value').' <em>*</em></th>'
        .'<td>'
        .$this->Form->text('Value',array('class'=>'js-input-data','value'=>$vals['scfg_value'],'maxLength'=>255,'style'=>'width:50px;'))
        .'&nbsp;'.__d('pos','default_table_number_for_menu_mode')
        .'<br></td>'
        .'</tr>';
        break;
        case'cover_limit':
        echo'<tr>'
        .'<th>'.__('value').' <em>*</em></th>'
        .'<td>'
        .'<table style="border-collapse:collapse;" width="99%">'
        //	Cover Upper Bound
        .'<tr>'
        .'<td>'.__d('pos','cover_upper_bound').'</td>'
        .'<td>'
        .$this->Form->text('CoverUpperBound',array('class'=>'js-input-data','value'=>(is_numeric($vals['scfg_value']['upper_bound'])?$vals['scfg_value']['upper_bound']:$vals['scfg_value']),'maxLength'=>128,'style'=>'width:128px;margin-right:10px;'))
        .__d('pos','cover').' '
        .'</td>'
        .'</tr>'
        //	Cover Warning
        .'<tr>'
        .'<td width="40%">'.__d('pos','cover_warning').'</td>'
        .'<td>'
        .$this->Form->text('CoverWarning',array('class'=>'js-input-data','value'=>(is_numeric($vals['scfg_value']['warning'])?$vals['scfg_value']['warning']:0),'maxLength'=>128,'style'=>'width:128px;margin-right:10px;'))
        .__d('pos','cover').' '
        .'</td>'
        .'</tr>'
        .'</table>'
        .'</td>'
        .'</tr>';
        break;
        case'menu_lookup_button_number':
        echo'<tr>'
        .'<th>'.__('value').' <em>*</em><br></th>'
        .'<td>'
        .$this->Form->textarea('Value',array('class'=>'js-input-data','value'=>$vals['scfg_value'],'style'=>'width:250px;height:100px;'))
        .'<br>'.__d('pos','example').':'
        .'<br><i>'.'{ "tablet" : { "row": 5, "column": 4, "row_with_image": 2, "column_with_image": 2, "font_size": 20 }, "mobile" : { "row": 3, "column": 3, "row_with_image": 2, "column_with_image": 2, "font_size": 14 } }'.'</i>'
        .'<br>'
        .'<br>'.__d('pos','fields').':'
        .'<br><i>'.'tablet{...}'.'</i>: '.__d('pos','setup_for_tablet')
        .'<br><i>'.'mobile{...}'.'</i>: '.__d('pos','setup_for_mobile')
        .'<br><i>'.'row'.'</i>: '.__d('pos','no_of_rows').', '.__d('pos','eg').': 4'
        .'<br><i>'.'column'.'</i>: '.__d('pos','no_of_columns').', '.__d('pos','eg').': 6'
        .'<br><i>'.'row_with_image'.'</i>: '.__d('pos','no_of_rows_for_item_with_image').', '.__d('pos','eg').': 2'
        .'<br><i>'.'column_with_image'.'</i>: '.__d('pos','no_of_columns_for_item_with_image').', '.__d('pos','eg').': 2'
        .'<br><i>'.'font_size'.'</i>: '.__d('pos','font_size_of_button_name').', '.__d('pos','eg').': 20'
        .'<br></td>'
        .'</tr>';
        break;
        case'common_lookup_button_number':
        echo'<tr>'
        .'<th>'.__('value').' <em>*</em><br></th>'
        .'<td>'
        .$this->Form->textarea('Value',array('class'=>'js-input-data','value'=>$vals['scfg_value'],'style'=>'width:250px;height:100px;'))
        .'<br>'.__d('pos','example').':'
        .'<br><i>'.'{ "tablet" : { "row": 4, "column": 6, "font_size": 20}, "mobile" : { "row": 3, "column": 3, "font_size": 16 } }'.'</i>'
        .'<br>'
        .'<br>'.__d('pos','fields').':'
        .'<br><i>'.'tablet{...}'.'</i>: '.__d('pos','setup_for_tablet')
        .'<br><i>'.'mobile{...}'.'</i>: '.__d('pos','setup_for_mobile')
        .'<br><i>'.'row'.'</i>: '.__d('pos','no_of_rows').', '.__d('pos','eg').': 4'
        .'<br><i>'.'column'.'</i>: '.__d('pos','no_of_columns').', '.__d('pos','eg').': 6'
        .'<br><i>'.'font_size'.'</i>: '.__d('pos','font_size_of_button_name').', '.__d('pos','eg').': 20'
        .'<br></td>'
        .'</tr>';
        break;
        case'set_menu_button_number':
        echo'<tr>'
        .'<th>'.__('value').' <em>*</em><br></th>'
        .'<td>'
        .$this->Form->textarea('Value',array('class'=>'js-input-data','value'=>$vals['scfg_value'],'style'=>'width:250px;height:100px;'))
        .'<br>'.__d('pos','example').':'
        .'<br><i>'.'{ "tablet" : { "row": 3, "column": 4, "row_with_image": 2, "column_with_image": 6, "font_size": 20}, "mobile" : { "row": 3, "column": 3, "row_with_image": 3, "column_with_image": 3, "font_size": 20 } }'.'</i>'
        .'<br>'
        .'<br>'.__d('pos','fields').':'
        .'<br><i>'.'tablet{...}'.'</i>: '.__d('pos','setup_for_tablet')
        .'<br><i>'.'mobile{...}'.'</i>: '.__d('pos','setup_for_mobile')
        .'<br><i>'.'row'.'</i>: '.__d('pos','no_of_rows').', '.__d('pos','eg').': 3'
        .'<br><i>'.'column'.'</i>: '.__d('pos','no_of_columns').', '.__d('pos','eg').': 4'
        .'<br><i>'.'row_with_image'.'</i>: '.__d('pos','no_of_rows').', '.__d('pos','eg').': 2'
        .'<br><i>'.'column_with_image'.'</i>: '.__d('pos','no_of_rows').', '.__d('pos','eg').': 6'
        .'<br><i>'.'font_size'.'</i>: '.__d('pos','font_size_of_button_name').', '.__d('pos','eg').': 20'
        .'<br></td>'
        .'</tr>';
        break;
        case'skip_tips_payment_code':
        echo'<tr>'
        .'<th>'.__('value').' <em>*</em></th>'
        .'<td>'
        .$this->Form->text('Value',array('class'=>'js-input-data','value'=>$vals['scfg_value'],'maxLength'=>255,'style'=>'width:400px;'))
        .'<br>'.__('format').' :'
        .'<br>'.' <'.__d('pos','payment_code').'>,<'.__d('pos','payment_code').'>,...'
        .'<br>'
        .'<br>'.__d('pos','example').' :'
        .'<br> 0001,0002'
        .'<br></td>'
        .'</tr>';
        break;
        case'check_calling_number':
        case'item_calling_number':
        case'payment_running_number':
        case'item_print_queue_running_number':
        $modeOpts=array(''=>__('all'),'t'=>__d('pos','take_away'),'d'=>__d('pos','fine_dining'),'f'=>__d('pos','fast_food'));
        $supportOpts=array('y'=>__('yes'),''=>__('no'));
        $methodOpts=array('r'=>__d('pos','reset_everyday'),'c'=>__d('pos','carry_forward'));

        echo'<tr>'
        .'<th>'.__('value').' <em>*</em></th>'
        .'<td>'
        .'<table style="border-collapse:collapse;" width="99%">'
        //	Support
        .'<tr>'
        .'<td width="25%">'
        .__d('pos','support')
        .'</td>'
        .'<td>'
        .$this->Form->radio('Support',$supportOpts,array('value'=>isset($vals['scfg_value']['support'])?$vals['scfg_value']['support']:'y','empty'=>false,'legend'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        .'</tr>';
        if($variable=='check_calling_number'||$variable=='item_calling_number'||$variable=='payment_running_number')
        //	Running Number Pool Code
        echo'<tr>'
        .'<td>'
        .__('code')
        .'</td>'
        .'<td>'
        .$this->Form->text('PoolCode',array('class'=>'js-input-data','value'=>isset($vals['scfg_value']['pool_code'])?$vals['scfg_value']['pool_code']:'','maxLength'=>128,'style'=>'width:193px;margin-right:10px;'))
        .$this->Html->link(__d('pos','available_running_number_pools'),'javascript:;',array('class'=>'js-show-running-number-pools','escape'=>false))
        .'</td>'
        .'</tr>';
        //	Method
        echo'<tr>'
        .'<td>'
        .__d('pos','running_method')
        .'</td>'
        .'<td>'
        .$this->Form->select('Method',$methodOpts,array('style'=>'width:200px;','default'=>isset($vals['scfg_value']['method'])?$vals['scfg_value']['method']:'c','empty'=>false))
        .'</td>'
        .'</tr>';
        //	Running mode
        if($variable=='check_calling_number'||$variable=='item_calling_number'){
        echo'<tr>'
        .'<td>'
        .__d('pos','running_mode')
        .'</td>'
        .'<td>'
        .$this->Form->select('Mode',$modeOpts,array('style'=>'width:200px;','default'=>isset($vals['scfg_value']['mode'])?$vals['scfg_value']['mode']:'','empty'=>false))
        .'</td>'
        .'</tr>';
        }
        //	Payment running number
        if($variable=='payment_running_number'){
        //	Single number for check
        $singleNoOpts=array('true'=>__('yes'),'false'=>__('no'));
        echo'<tr>'
        .'<td>'
        .__d('pos','one_number_apply_on_one_check')
        .'</td>'
        .'<td>'
        .$this->Form->radio('SingleNo',$singleNoOpts,array('value'=>isset($vals['scfg_value']['single_number_for_check'])?$vals['scfg_value']['single_number_for_check']:'false','empty'=>false,'legend'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        .'</tr>';

        //	Payment methods
        $availablePaymentIds=explode(",",isset($vals['scfg_value']['available_payment_ids'])?$vals['scfg_value']['available_payment_ids']:"");
        $reusablePaymentIds=explode(",",isset($vals['scfg_value']['reusable_payment_ids'])?$vals['scfg_value']['reusable_payment_ids']:"");

        $paymentReuseOpts=array(
        'r'=>__d('pos','reuse'),
        ''=>__d('pos','not_reuse')
        );
        echo'<tr>'
        .'<td>'
        .__d('pos','apply_to_payment_methods')
        .'</td>'
        .'<td>';
        if(!empty($posPaymentMethods)){
        echo'<table class="no-border">';
        foreach($posPaymentMethods as $id=>$name)
        echo'<tr>'
        .'<td width="60%">'
        .$this->Form->checkbox('PaymentMethod'.$id,array('class'=>'js-payment-method','paymentId'=>$id,'checked'=>(in_array($id,$availablePaymentIds)?true:false)))
        .'<label for="PosConfigPaymentMethod'.$id.'">'.__k($name,30).'</label>'
        .'</td>'
        .'<td>'
        .$this->Form->radio('PaymentReuse'.$id,$paymentReuseOpts,array('paymentId'=>$id,'value'=>(in_array($id,$reusablePaymentIds)?'r':''),'legend'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        .'</tr>';

        echo'</table>';
        }

        echo'</td>'
        .'</tr>';
        }
        //Item Print Queue running number
        if($variable=='item_print_queue_running_number'){
        //Apply to item print queue
        echo'<tr>'
        .'<td>'
        .__d('pos','apply_to_item_print_queue')
        .'</td>'
        .'<td>';

        if(!empty($printQueueNames)){
        $withCodePrintQueues=array();
        foreach($vals['scfg_value']['print_queues']as $printQueue)
        $withCodePrintQueues[$printQueue['id']]=$printQueue['pool_code'];

        echo'<table class="no-border">';
        foreach($printQueueNames as $id=>$name){
        echo'<tr>'
        .'<td width="40%">'
        .$this->Form->checkbox('PrintQueueName'.$id,array('class'=>'js-print-queue','printQueueId'=>$id,'checked'=>(in_array($id,array_keys($withCodePrintQueues))?true:false)))
        .'<label for="PosConfigPrintQueueName'.$id.'">'.__k($name,40).'</label>'
        .'</td>'
        .'<td>'
        .$this->Form->text('PoolCode'.$id,array('class'=>'js-input-data','value'=>isset($withCodePrintQueues[$id])?$withCodePrintQueues[$id]:'','maxLength'=>128,'style'=>'width:100px;margin-right:10px;'))
        .$this->Html->link(__d('pos','available_running_number_pools'),'javascript:;',array('class'=>'js-show-running-number-pools','escape'=>false,'relatedPoolCodeTextId'=>$id))
        .'</td>'
        .'</tr>';
        }
        echo'</table>';
        }

        echo'</td>'
        .'</tr>';
        }

        echo'</table>'
        .'</td>'
        .'</tr>';
        break;

        case'idle_time_logout':
        $userGroupRecordList=$vals['scfg_value']['user_group_ids'];
        $selectedUserGroup=array();
        $detailedUserGroupTimeout=array();
        foreach($userGroupRecordList as $id=>$userGroup){
        $selectedUserGroup[$id]=$userGroup['id'];
        $detailedUserGroupTimeout[$userGroup['id']]=$userGroup['timeout'];
        }

        echo'<tr>'
        .'<th>'.__('value').' <em>*</em></th>'
        .'<td>'.'<table style = "border-collapse:collapse;" width = "100%">';
        echo'<tr>'
        .'<td>'.__('timeout').' <em>*</em></td>'
        .'<td>'
        .$this->Form->text('Timeout',array('class'=>'js-input-data','value'=>$vals['scfg_value']['timeout'],'type'=>'number','required'=>true,'min'=>1,'step'=>1,'maxLength'=>255,'style'=>'width:70px;','id'=>'Timeout'))
        .' '.__('seconds')
        .'<br>'
        .'</td>'
        .'</tr>';
        echo'<tr>'
        .'<td>'
        .__d('user','user_group').' <em>*</em></td>'
        .'<td width="60%"><div class = "UserGroupSelectionDiv">';
        echo'<table width="100%" class="no-border">';
        foreach($userGroupList as $userGroupId=>$userGroupName){
        echo'<tr><td>';
        echo $this->Form->checkbox('UserGroup'.$userGroupId,array('userGroupId'=>$userGroupId,'id'=>$userGroupId,'checked'=>(in_array($userGroupId,$selectedUserGroup)?true:false)))
        .__k($userGroupName,30)
        .'<br>';
        echo'</td><td>';
        echo $this->Form->text('Timeout'.$userGroupId,array('class'=>'js-input-data','id'=>'Timeout'.$userGroupId,'class'=>'UserGroupTimeout','value'=>isset($detailedUserGroupTimeout[$userGroupId])?$detailedUserGroupTimeout[$userGroupId]:'0','min'=>1,'type'=>'number','required'=>true,'disabled'=>(in_array($userGroupId,$selectedUserGroup)?false:true),'step'=>1,'maxLength'=>255,'style'=>'width:35px;')).' '.__('seconds');
        echo'</td></tr>';
        }
        echo'</table>';
        echo'<div id = "UserGroupSelectionMessageDiv" style= "color:#B22; text-decoration:blink;"></div>';
        echo'</div></td></tr>';

        echo'</table>.</td>';
        break;
        case'open_check_setting':
        $supportOpts=array('y'=>__('yes'),'n'=>__('no'));
        $askTableNoOpts=array('y'=>__('yes'),'n'=>__('no'));
        $askGuestNoOpts=array('y'=>__('yes'),'n'=>__('no'));
        echo'<tr>'
        .'<th>'.__('value').' <em>*</em></th>'
        .'<td>'
        .'<table style="border-collapse:collapse;" width="100%">'
        //	Support
        .'<tr>'
        .'<td width="25%">'
        .__d('pos','support')
        .'</td>'
        .'<td>'
        .$this->Form->radio('Support',$supportOpts,array('value'=>isset($vals['scfg_value']['support'])?$vals['scfg_value']['support']:'y','empty'=>false,'legend'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        .'</tr>';
        echo'<tr id="periodRow">'
        .'<td>'
        .__d('pos','period')
        .'</td>'
        .'<td>';
        echo'<div id ="PeriodsDiv"></div>';
        echo'<div id ="PeriodValidationMessageDiv" style= "color:#B22; text-decoration:blink;"></div>';
        echo'<tr>'
        .'<td width="25%">'
        .__d('pos','ask_table_number')
        .'</td>'
        .'<td>'
        .$this->Form->radio('ask_table_number',$askTableNoOpts,array('value'=>isset($vals['scfg_value']['ask_table_number'])?$vals['scfg_value']['ask_table_number']:'y','empty'=>false,'legend'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        .'</tr>';
        echo'<tr>'
        .'<td width="25%">'
        .__d('pos','ask_guest_number')
        .'</td>'
        .'<td>'
        .$this->Form->radio('ask_guest_number',$askGuestNoOpts,array('value'=>isset($vals['scfg_value']['ask_guest_number'])?$vals['scfg_value']['ask_guest_number']:'y','empty'=>false,'legend'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        .'</tr>'
        .'</table>';

        break;
        case'dutymeal_shop_limit':
        case'dutymeal_outlet_limit':
        case'dutymeal_check_limit':
        case'on_credit_shop_limit':
        case'on_credit_outlet_limit':
        case'on_credit_check_limit':
        case'reprint_guest_check_times':
        case'reprint_receipt_times':
        case'number_of_drawer_owned_by_user':
        case'copies_of_receipt':
        echo'<tr>'
        .'<th>'.__('value').' <em>*</em></th>'
        .'<td>'
        .$this->Form->text('Value',array('class'=>'js-input-data','value'=>$vals['scfg_value'],'maxLength'=>255,'style'=>'width:50px;'))
        .'<br></td>'
        .'</tr>';
        break;
        case'table_mode_row_and_column':
        echo'<tr>'
        .'<th>'.__('value').' <em>*</em></th>'
        .'<td>'
        .$this->Form->text('Value',array('class'=>'js-input-data','value'=>$vals['scfg_value'],'maxLength'=>255,'style'=>'width:50px;'))
        .'<br>'.__('format').' - R:C'
        .'<br>'.' R -'.__('number_of_row').'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.__('range').' '.'(1 - 10)'
        .'<br>'.' C -'.__('number_of_column').'&nbsp;&nbsp;&nbsp;'.__('range').' '.'(1 - 20)'
        .'<br>'
        .'<br>'.__d('pos','example').' - 3:10 '
        .'<br></td>'
        .'</tr>';
        break;
        case'payment_check_types':
        echo'<tr>'
        .'<th>'.__('value').'</th>'
        .'<td>';

        //	Mappings
        echo'<table id="PaymentCheckTypeMapping" class="list-basic list-border header-align-center input-mappings" style="margin:0px auto 10px;" width="99%">';
        echo'<tr>'
        .'<th></th>'    //	Delete button
        .'<th width="48%">'.__d('pos','payment_method').'</th>'
        .'<th width="48%">'.__d('pos','custom_type').'</th>'
        .'</tr>';

        if(!empty($vals['scfg_value']['mapping'])){
        foreach($vals['scfg_value']['mapping']as $mapping)
        echo generateMappingRowContent($this,'paymentCheckType',array('posPaymentMethods'=>$posPaymentMethods,'posCustomTypes'=>$posCustomTypes),$mapping);
        }
        echo'</table>';

        //	Add row button
        echo'<div style="float:right; padding-right:10px;">'
        .$this->element('button',array('id'=>'AddRowButton','content'=>__d('pos','add_row'),'class'=>'button-basic-2','appendClass'=>'js-add-mapping-row'))
        .'</div>';

        echo'</td>'
        .'</tr>';
        break;
        case'employee_discount_limit':
        $modeOpts=array(
        'm'=>__d('pos','monthly_limit')
        );
        echo'<tr>'
        .'<th>'.__('value').' <em>*</em></th>'
        .'<td>'
        .'<table style="border-collapse:collapse;" width="99%">'
        //	User
        .'<tr>'
        .'<td width="25%">'.__d('user','user').'</td>'
        .'<td>'
        .$this->Form->select('UserId',
        $userOpts,
        array(
        'style'=>'width:200px;margin-right:10px;',
        'default'=>isset($userOpts[$vals['scfg_value']['userId']])?$vals['scfg_value']['userId']:0,
        'empty'=>false
        )
        )
        .'</td>'
        .'</tr>'
        //	Mode
        .'<tr>'
        .'<td>'.__d('pos','discount_mode').'</td>'
        .'<td>'
        .$this->Form->select('Mode',$modeOpts,array('style'=>'width:200px;','default'=>$vals['scfg_value']['mode'],'empty'=>false))
        .'</td>'
        .'</tr>'
        //	Limit
        .'<tr>'
        .'<td>'.__d('pos','discount_limit').'</td>'
        .'<td>'
        .$this->Form->text('Limit',array('class'=>'js-input-data','value'=>$vals['scfg_value']['limit'],'maxLength'=>128,'style'=>'width:193px;margin-right:10px;'))
        .'</td>'
        .'</tr>'
        .'</table>'
        .'</td>'
        .'</tr>';
        break;
        case'settlement_count_interval_to_print_guest_questionnaire':
        echo'<tr>'
        .'<th>'.__('value').' <em>*</em></th>'
        .'<td>'
        .'<table style="border-collapse:collapse;" width="99%">'
        //	Interval Count
        .'<tr>'
        .'<td>'.__d('pos','settlement_count_interval_to_print_guest_questionnaire').'</td>'
        .'<td>'
        .$this->Form->text('IntervalCount',array('class'=>'js-input-data','value'=>$vals['scfg_value']['interval_count'],'maxLength'=>128,'style'=>'width:193px;margin-right:10px;'))
        .'</td>'
        .'</tr>'
        .'</table>'
        .'</td>'
        .'</tr>';
        break;
        case'payment_process_setting':
        $yesNoOpts=array('true'=>__('yes'),'false'=>__('no'));
        echo'<tr>'
        .'<th>'.__('value').' <em>*</em></th>'
        .'<td>'
        .'<table style="border-collapse:collapse;" width="99%">'
        //	Display Loading Box During Payment
        .'<tr>'
        .'<td>'.__d('pos','display_loading_box_during_payment').'</td>'
        .'<td>'
        .$this->Form->radio('LoadingBox',$yesNoOpts,array('value'=>isset($vals['scfg_value']['display_loading_box_during_payment'])?$vals['scfg_value']['display_loading_box_during_payment']:'y','legend'=>false,'empty'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        .'</tr>'
        //	Payment Completion Message
        .'<tr>'
        .'<td>'.__d('pos','payment_completion_message').'</td>'
        .'<td>'
        .$this->Form->textarea('Completion',array('class'=>'js-input-data','value'=>$vals['scfg_value']['payment_completion_message'],'style'=>'width:320px;height:50px;'))
        .'</td>'
        .'</tr>'
        //	Payment Completion Image Name
        .'<tr>'
        .'<td>'.__d('pos','payment_completion_image_name').'</td>'
        .'<td>'
        .$this->Form->text('CompletionImage',array('class'=>'js-input-data','value'=>$vals['scfg_value']['payment_completion_image_name'],'maxLength'=>128,'style'=>'width:193px;margin-right:10px;'))
        .'</td>'
        .'</tr>'
        .'</table>'
        .'</td>'
        .'</tr>';
        break;
        case'generate_receipt_pdf':
        $supportOpts=array('y'=>__('yes'),''=>__('no'));
        echo'<tr>'
        .'<th>'.__('value').' <em>*</em></th>'
        .'<td>'
        .'<table style="border-collapse:collapse;" width="99%">'
        //	Support
        .'<td width="25%">'
        .__d('pos','support')
        .'</td>'
        .'<td>'
        .$this->Form->radio('Support',$supportOpts,array('value'=>isset($vals['scfg_value']['support'])?$vals['scfg_value']['support']:'y','empty'=>false,'legend'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        //	Password
        .'<tr>'
        .'<td>'.__d('pos','password').'</td>'
        .'<td>'
        .$this->Form->text('Password',array('class'=>'js-input-data','value'=>$vals['scfg_value']['password'],'maxLength'=>128,'style'=>'width:193px;margin-right:10px;'))
        .'</td>'
        .'</tr>'
        //	Export path
        .'<tr>'
        .'<td>'.__d('pos','export_path').'</td>'
        .'<td>'
        .$this->Form->text('Path',array('class'=>'js-input-data','value'=>$vals['scfg_value']['path'],'maxLength'=>128,'style'=>'width:193px;margin-right:10px;'))
        .'</td>'
        .'</tr>'
        .'</table>'
        .'</td>'
        .'</tr>';
        break;
        case'screen_saver_option':
        $displayContent=array('c'=>__d('pos','color'),'m'=>__d('media','photo_gallery'));
        echo'<tr>'
        .'<th>'.__('value').' <em>*</em></th>'
        .'<td>'
        .'<table style="border-collapse:collapse;" width="99%">'
        //	Timeout
        .'<tr>'
        .'<td width="25%">'
        .__('timeout')." (".__('minutes').")"
        .'</td>'
        .'<td>'
        .$this->Form->text('Timeout',array('class'=>'js-input-data','value'=>isset($vals['scfg_value']['timeout'])?$vals['scfg_value']['timeout']:'','maxLength'=>128,'style'=>'width:193px;margin-right:10px;'))
        .'<br>'.__d('pos','example').' : '.' 0 - '.__d('pos','no_timeout')
        .'</td>'
        .'</tr>'
        //	Display Content
        .'<tr>'
        .'<td>'
        .__d('pos','display_content')
        .'</td>'
        .'<td>'
        .$this->Form->radio('DisplayContent',$displayContent,array('value'=>isset($vals['scfg_value']['display_content'])?$vals['scfg_value']['display_content']:'c','class'=>'js-screen-saver','empty'=>false,'legend'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        .'</tr>'
        //	Color
        .'<tr>'
        .'<td>'
        .__d('pos','color')
        .'</td>'
        .'<td>'
        .$this->Form->text('Color',array('class'=>'js-input-data','value'=>isset($vals['scfg_value']['color'])?$vals['scfg_value']['color']:'','class'=>'js-screen-saver-text','maxLength'=>128,'style'=>'width:193px;margin-right:10px;'))
        .'<br>'.__d('pos','example').' : '.' 000000 - '.__d('pos','black').' ,FFFFFF - '.__d('pos','white')
        .'</td>'
        .'</tr>'
        //	Transparency
        .'<tr>'
        .'<td>'
        .__d('pos','transparency')
        .'</td>'
        .'<td>'
        .$this->Form->text('Transparency',array('class'=>'js-input-data','value'=>isset($vals['scfg_value']['transparency'])?$vals['scfg_value']['transparency']:'','class'=>'js-screen-saver-text','maxLength'=>128,'style'=>'width:193px;margin-right:10px;'))
        .'<br>'.__d('pos','example').' : '.' 00 - 0%, 80 - 50%, FF - 100%'
        .'</td>'
        .'</tr>';
        echo'</table>'
        .'</td>'
        .'</tr>';
        break;
        case'table_attribute_mandatory_key':
        $supportOpts=array('y'=>__('yes'),''=>__('no'));
        echo'<tr>'
        .'<th>'.__('value').' <em>*</em></th>'
        .'<td>'
        .'<table style="border-collapse:collapse;" width="99%">';
        for($i=1;$i<=10;$i++){
        //	key 1 - 10
        echo'<tr>'
        .'<td width="25%">'.__d('pos','key').$i.'</td>'
        .'<td>'
        .$this->Form->radio('Key'.$i,$supportOpts,array('value'=>isset($vals['scfg_value']['key'.$i])?$vals['scfg_value']['key'.$i]:'y','empty'=>false,'legend'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        .'</tr>';
        }
        echo'</table>'
        .'</td>'
        .'</tr>';
        break;
        case'export_e_journal':
        $supportOpts=array('y'=>__('yes'),''=>__('no'));
        echo'<tr>'
        .'<th>'.__('value').' <em>*</em></th>'
        .'<td>'
        .'<table style="border-collapse:collapse;" width="99%">'
        //	Support
        .'<td width="25%">'
        .__d('pos','support')
        .'</td>'
        .'<td>'
        .$this->Form->radio('Support',$supportOpts,array('value'=>isset($vals['scfg_value']['support'])?$vals['scfg_value']['support']:'y','empty'=>false,'legend'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        //	Export path
        .'<tr>'
        .'<td>'.__d('pos','export_path').'</td>'
        .'<td>'
        .$this->Form->text('Path',array('class'=>'js-input-data','value'=>$vals['scfg_value']['path'],'maxLength'=>128,'style'=>'width:193px;margin-right:10px;'))
        .'</td>'
        .'</tr>'
        .'</table>'
        .'</td>'
        .'</tr>';
        $location=array('station'=>__d('pos','station'));
        break;
        case'force_daily_close':
        $supportOpts=array('y'=>__('yes'),''=>__('no'));
        echo'<tr>'
        .'<th>'.__('value').' <em>*</em></th>'
        .'<td>'
        .'<table style="border-collapse:collapse;" width="99%">'
        //	Support
        .'<td width="25%">'
        .__d('pos','support')
        .'</td>'
        .'<td>'
        .$this->Form->radio('Support',$supportOpts,array('value'=>isset($vals['scfg_value']['support'])?$vals['scfg_value']['support']:'y','empty'=>false,'legend'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        //	User
        .'<tr>'
        .'<td width="25%">'.__d('user','user').'</td>'
        .'<td>'
        .$this->Form->select('UserId',
        $userOpts,
        array(
        'style'=>'width:200px;margin-right:10px;',
        'default'=>isset($userOpts[$vals['scfg_value']['userId']])?$vals['scfg_value']['userId']:0,
        'empty'=>false
        )
        )
        .'</td>'
        .'</tr>'
        //	Payment
        .'<tr>'
        .'<td width="25%">'.__d('pos','payment_method').'</td>'
        .'<td>'
        .$this->Form->select('PaymentId',
        $posPaymentMethods,
        array(
        'style'=>'width:100%;',
        'default'=>isset($posPaymentMethods[$vals['scfg_value']['paymentId']])?$vals['scfg_value']['paymentId']:0,
        'empty'=>false
        )
        )
        .'</td>'
        .'</tr>'
        //	Station
        .'<tr>'
        .'<td width="25%">'.__d('pos','station').'</td>'
        .'<td>'
        .$this->Form->select('PayByStationId',
        $stationOpts,
        array(
        'style'=>'width:100%;',
        'default'=>isset($vals['scfg_value']['stationId'])?$vals['scfg_value']['stationId']:0,
        'empty'=>false
        )
        )
        .'</td>'
        .'</tr>'
        // Carry forward open check to next business day
        .'<tr>'
        .'<td width="25%">'.__d('pos','carry_forward').'</td>'
        .'<td>'
        .$this->Form->radio('CarryForward',$supportOpts,array('value'=>isset($vals['scfg_value']['carryForward'])?$vals['scfg_value']['carryForward']:'y','empty'=>false,'legend'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        .'</tr>'
        .'</table>'
        .'</td>'
        .'</tr>';
        break;
        case'member_validation_setting':
        $supportOpts=array('y'=>__('yes'),''=>__('no'));
        $typeOpts=array('normal'=>__d('pos','normal_member'),'employeeMember'=>__d('pos','employee_member'));
        echo'<tr>'
        .'<th>'.__('value').' <em>*</em></th>'
        .'<td>'
        .'<table style="border-collapse:collapse;" width="99%">'
        //	No member validation
        .'<td width="25%">'
        .__d('pos','no_validation_with_member_module')
        .'</td>'
        .'<td>'
        .$this->Form->radio('NoMemberValidate',$supportOpts,array('value'=>isset($vals['scfg_value']['no_member_validation_in_set_member'])?$vals['scfg_value']['no_member_validation_in_set_member']:'y','empty'=>false,'legend'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        //	MSR interface code
        .'<tr>'
        .'<td>'.__d('pos','msr_interface_code').'</td>'
        .'<td>'
        .$this->Form->text('InterfaceCode',array('class'=>'js-input-data','value'=>$vals['scfg_value']['interface_code'],'maxLength'=>128,'style'=>'width:193px;margin-right:10px;'))
        .'</td>'
        .'</tr>'
        //	Member Type
        .'<tr>'
        .'<td>'.__d('pos','member_type').'</td>'
        .'<td>'
        .$this->Form->select('MemberType',$typeOpts,array('style'=>'width:200px;','default'=>isset($vals['scfg_value']['member_type'])?$vals['scfg_value']['member_type']:'n','empty'=>false))
        .'</td>'
        .'</tr>'
        .'</table>'
        .'</td>'
        .'</tr>';
        break;
        case'print_check_control':
        $supportOpts=array('y'=>__('yes'),''=>__('no'));
        echo'<tr>'
        .'<th>'.__('value').' <em>*</em></th>'
        .'<td>'
        .'<table style="border-collapse:collapse;" width="99%">'
        //	Support
        .'<td width="25%">'
        .__d('pos','support')
        .'</td>'
        .'<td>'
        .$this->Form->radio('Support',$supportOpts,array('value'=>isset($vals['scfg_value']['support'])?$vals['scfg_value']['support']:'y','empty'=>false,'legend'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        //	Member Attachment
        .'<tr>'
        .'<td>'.__d('pos','need_member_attached').'</td>'
        .'<td>'
        .$this->Form->radio('MemberAttachment',$supportOpts,array('value'=>isset($vals['scfg_value']['need_member_attached'])?$vals['scfg_value']['need_member_attached']:'y','empty'=>false,'legend'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        .'</tr>'
        .'</table>'
        .'</td>'
        .'</tr>';
        break;
        case'table_validation_setting':
        $supportOpts=array('y'=>__('yes'),''=>__('no'));
        echo'<tr>'
        .'<th>'.__('value').' <em>*</em></th>'
        .'<td>'
        .'<table style="border-collapse:collapse;" width="99%">'
        //	Support
        .'<td width="25%">'
        .__d('pos','support')
        .'</td>'
        .'<td>'
        .$this->Form->radio('Support',$supportOpts,array('value'=>isset($vals['scfg_value']['support'])?$vals['scfg_value']['support']:'y','empty'=>false,'legend'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        //	MSR Interface Code
        .'<tr>'
        .'<td>'.__d('pos','msr_interface_code').'</td>'
        .'<td>'
        .$this->Form->text('MsrInterfaceCode',array('class'=>'js-input-data','value'=>$vals['scfg_value']['msr_interface_code'],'maxLength'=>128,'style'=>'width:193px;margin-right:10px;'))
        .'</td>'
        .'</tr>'
        //	Default Cover
        .'<tr>'
        .'<td>'.__d('pos','default_cover').'</td>'
        .'<td>'
        .$this->Form->text('DefaultCover',array('class'=>'js-input-data','value'=>$vals['scfg_value']['default_cover'],'maxLength'=>128,'style'=>'width:193px;margin-right:10px;'))
        .'</td>'
        .'</tr>'
        //	Minimum Check Total For All Tables
        .'<tr>'
        .'<td>'.__d('pos','minimum_check_total_for_all_tables').'</td>'
        .'<td>'
        .$this->Form->text('MinimumCheckTotalForAllTables',array('class'=>'js-input-data','value'=>isset($vals['scfg_value']['minimum_check_total_for_all_tables'])?$vals['scfg_value']['minimum_check_total_for_all_tables']:0,'maxLength'=>128,'style'=>'width:193px;margin-right:10px;'))
        .'</td>'
        .'</tr>'
        //	Minimum Charge Item Code
        .'<tr>'
        .'<td>'.__d('pos','minimum_charge_item_code').'</td>'
        .'<td>'
        .$this->Form->text('MinimumChargeItemCode',array('class'=>'js-input-data','value'=>isset($vals['scfg_value']['minimum_charge_item_code'])?$vals['scfg_value']['minimum_charge_item_code']:'','maxLength'=>128,'style'=>'width:193px;margin-right:10px;'))
        .'</td>'
        .'</tr>'
        //	Maximum Check Total For All Tables
        .'<tr>'
        .'<td>'.__d('pos','maximum_check_total_for_all_tables').'</td>'
        .'<td>'
        .$this->Form->text('MaximumCheckTotalForAllTables',array('class'=>'js-input-data','value'=>$vals['scfg_value']['maximum_check_total_for_all_tables'],'maxLength'=>128,'style'=>'width:193px;margin-right:10px;'))
        .'</td>'
        .'</tr>'
        // Ask for confirmation for check maximum
        .'<tr>'
        .'<td width="40%">'
        .__d('pos','ask_for_bypass_max_check_total').' :'
        .'</td>'
        .'<td>'
        .$this->Form->radio('AskForBypassMaxCheckTotal',$supportOpts,array('value'=>isset($vals['scfg_value']['ask_for_bypass_max_check_total'])?$vals['scfg_value']['ask_for_bypass_max_check_total']:'y','empty'=>false,'legend'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        .'</tr>'
        //	Skip Ask Cover
        .'<td width="25%">'
        .__d('pos','skip_ask_cover')
        .'</td>'
        .'<td>'
        .$this->Form->radio('SkipAskCover',$supportOpts,array('value'=>isset($vals['scfg_value']['skip_ask_cover'])?$vals['scfg_value']['skip_ask_cover']:'y','empty'=>false,'legend'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        .'</table>'
        .'</td>'
        .'</tr>';
        $location=array('station'=>__d('pos','station'));
        break;
        case'set_order_ownership':
        $supportOpts=array('y'=>__('yes'),''=>__('no'));
        $typeOpts=array('r'=>__d('pos','only_owner_allow_access_check'),'c'=>__d('pos','everyone_allow_access_check_except_print_check_and_pay_check'));
        echo'<tr>'
        .'<th>'.__('value').' <em>*</em></th>'
        .'<td>'
        .'<table style="border-collapse:collapse;" width="99%">'
        //	Support
        .'<td width="25%">'
        .__d('pos','support')
        .'</td>'
        .'<td>'
        .$this->Form->radio('Support',$supportOpts,array('value'=>isset($vals['scfg_value']['support'])?$vals['scfg_value']['support']:'y','empty'=>false,'legend'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        //set ownership type
        .'<tr>'
        .'<td>'
        .__d('pos','ownership_type')
        .'</td>'
        .'<td>'
        .$this->Form->select('Type',$typeOpts,array('style'=>'width:100%;','default'=>isset($vals['scfg_value']['type'])?$vals['scfg_value']['type']:'','empty'=>false))
        .'</td>'
        .'</tr>'
        .'</table>'
        .'</td>'
        .'</tr>';
        break;
        case'payment_checking':
        $supportOpts=array('y'=>__('yes'),''=>__('no'));
        echo'<tr>'
        .'<th>'.__('value').' <em>*</em></th>'
        .'<td>'
        .'<table style="border-collapse:collapse;" width="99%">'
        //	Support
        .'<td width="25%">'
        .__d('pos','support')
        .'</td>'
        .'<td>'
        .$this->Form->radio('Support',$supportOpts,array('value'=>isset($vals['scfg_value']['support'])?$vals['scfg_value']['support']:'y','empty'=>false,'legend'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        //	Check Drawer Ownership
        .'<tr>'
        .'<td>'.__d('pos','check_drawer_ownership').'</td>'
        .'<td>'
        .$this->Form->radio('CheckDrawerOwnership',$supportOpts,array('value'=>isset($vals['scfg_value']['check_drawer_ownership'])?$vals['scfg_value']['check_drawer_ownership']:'y','empty'=>false,'legend'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        .'</tr>'
        //	Clear Ownership In Daily Start
        .'<tr>'
        .'<td>'.__d('pos','clear_ownership_in_daily_start').'</td>'
        .'<td>'
        .$this->Form->radio('ClearOwnershipInDailyStart',$supportOpts,array('value'=>isset($vals['scfg_value']['clear_ownership_in_daily_start'])?$vals['scfg_value']['clear_ownership_in_daily_start']:'y','empty'=>false,'legend'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        .'</tr>'
        .'</table>'
        .'</td>'
        .'</tr>';
        break;
        case'advance_order_setting':
        $supportOpts=array('y'=>__('yes'),''=>__('no'));
        echo'<tr>'
        .'<th>'.__('value').' <em>*</em></th>'
        .'<td>'
        .'<table style="border-collapse:collapse;" width="99%">'
        //	Support
        .'<td width="25%">'
        .__d('pos','support')
        .'</td>'
        .'<td>'
        .$this->Form->radio('Support',$supportOpts,array('value'=>isset($vals['scfg_value']['support'])?$vals['scfg_value']['support']:'y','empty'=>false,'legend'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        //	Payment Method
        .'<tr>'
        .'<td width="25%">'.__d('pos','advance_order_payment_method').'</td>'
        .'<td>'
        .$this->Form->select('PaymentId',
        $posPaymentMethods,
        array(
        'style'=>'width:100%;',
        'default'=>isset($posPaymentMethods[$vals['scfg_value']['paymentId']])?$vals['scfg_value']['paymentId']:0,
        'empty'=>false
        )
        )
        .'</td>'
        .'</tr>'
        .'</table>'
        .'</td>'
        .'</tr>';
        break;
        case'table_floor_plan_setting':

        if(isset($vals['scfg_value']['cleaning_status_function_list']))
        $mapping=$vals['scfg_value']['cleaning_status_function_list'];
        $supportOpts=array('y'=>__('yes'),''=>__('no'));
        $intervalTextbox=array('class'=>'js-input-data','value'=>isset($vals['scfg_value']['automatically_change_cleaning_to_vacant_interval'])?$vals['scfg_value']['automatically_change_cleaning_to_vacant_interval']:'0','maxLength'=>128,'style'=>'width:193px;margin-right:10px;');
        if(!isset($vals['scfg_value']['support_table_status_cleaning'])||$vals['scfg_value']['support_table_status_cleaning']!='y')
        $intervalTextbox['disabled']='disabled';
        echo'<tr>'
        .'<th>'.__('value').' <em>*</em></th>'
        .'<td>'
        .'<table style="border-collapse:collapse;" width="99%">'
        //	Support showing cooking overtime status
        .'<tr>'
        .'<td width="35%">'
        .__d('pos','support_cooking_overtime_status')
        .'</td>'
        .'<td>'
        .$this->Form->radio('SupportCookingOvertime',$supportOpts,array('value'=>isset($vals['scfg_value']['support_cooking_overtime'])?$vals['scfg_value']['support_cooking_overtime']:'y','empty'=>false,'legend'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        .'</tr>'
        //	Support table status cleaning
        .'<td>'
        .__d('pos','support_table_status_cleaning')
        .'</td>'
        .'<td>'
        .$this->Form->radio('SupportTableStatusCleaning',$supportOpts,array('value'=>isset($vals['scfg_value']['support_table_status_cleaning'])?$vals['scfg_value']['support_table_status_cleaning']:'','empty'=>false,'legend'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        //	Automatically change cleaning to vacant interval
        .'<tr>'
        .'<td>'.__d('pos','automatically_change_cleaning_to_vacant_interval').' ('.__('seconds').')'.'</td>'
        .'<td>'
        .$this->Form->text('ChangeCleaningToVacantInterval',$intervalTextbox)
        .'<br>'.__d('pos','example').' : '.' 0 - '.__d('pos','no_timeout')
        .'<br>'
        .'<br>'.__d('pos','final_interval_may_differ_by_5s')
        .'</td>'
        .'</tr>'
        //	Cleaning Status Function List
        .'<tr>'
        .'<td>'.__d('pos','support_cleaning_status_function_list').'</td>'
        .'<td>'
        .'<table id="CleaningStatusFunctionListMapping" class="list-basic list-border header-align-center input-mappings" style="margin:0px auto 10px;" width="99%">'
        .'<tr>'
        .'<th></th>'    //	Delete button
        .'<th width="100%">'.__d('pos','function').'</th>'
        .'</tr>';
        if(isset($mapping))
        for($i=0;$i<count($mapping);$i++)
        echo generateMappingRowContent($this,'cleaningStatusFunctionList',array('posFunctions'=>$posFunctions),$mapping[$i]);
        echo'</table>';

        //	Add row button
        echo'<div style="float:right; padding-right:10px;">'
        .$this->element('button',array('id'=>'AddRowButton','content'=>__d('pos','add_row'),'class'=>'button-basic-2','appendClass'=>'js-add-mapping-row'))
        .'</div>';
        echo'</td>'
        .'</tr>'
        //	Table status color
        .'<tr>'
        .'<td>'.__d('pos','table_status_color').'</td>'
        .'<td>'
        .'<table class="list-basic header-align-center input-columns" width="99%">';
        if(!isset($vals['scfg_value']['table_status_color'])){
        //	handle old format
        $vals['scfg_value']['table_status_color']=$defaultTableStatusColor;
        $vals['scfg_value']['table_status_color']['cooking_over_time']=$vals['scfg_value']['cooking_overtime_status_color'];
        }
        foreach($vals['scfg_value']['table_status_color']as $tableStatus=>$tableStatusColor){
        echo'<tr><td>'.__d('pos',$tableStatus).'</td>'
        .'<td>'.$this->Form->text($tableStatus,array(
        'name'=>'data[PosConfig][TableStatusColor]['.$tableStatus.']',
        'id'=>'PosConfig'.'TableStatusColor'.Inflector::camelize($tableStatus),
        'class'=>'js-input-color',
        'value'=>$tableStatusColor,
        'ogn_value'=>$tableStatusColor,
        'maxLength'=>20,
        'style'=>'width: 100px;',
        )
        );
        echo' <span class="select-color" style="background-color:#'.$tableStatusColor.';"></span></td></tr>';
        }
        echo'</table>'
        .'</td>'
        .'</tr>'
        .'</table>'
        .'</td>'
        .'</tr>';
        $location=array('station'=>__d('pos','station'));
        break;
        case'pay_check_auto_functions':
        echo'<tr>'
        .'<th>'.__('value').'</th>'
        .'<td>';

        //	Mappings
        echo'<table id="PayCheckAutoFunctionMapping" class="list-basic list-border header-align-center input-mappings" style="margin:0px auto 10px;" width="99%">';
        echo'<tr>'
        .'<th></th>'    //	Delete button
        .'<th width="43%">'.__d('pos','function').'</th>'
        .'<th width="43%">'.__d('pos','failure_handling').'</th>'
        .'<th width="10%">'.__('seq').'</th>'
        .'</tr>';
        if(!empty($vals['scfg_value'])){
        foreach($vals['scfg_value']as $mapping)
        echo generateMappingRowContent($this,'payCheckAutoFunction',array('posFunctions'=>$posFunctions),$mapping);
        }
        echo'</table>';

        //	Add row button
        echo'<div style="float:right; padding-right:10px;">'
        .$this->element('button',array('id'=>'AddRowButton','content'=>__d('pos','add_row'),'class'=>'button-basic-2','appendClass'=>'js-add-mapping-row'))
        .'</div>';

        echo'</td>'
        .'</tr>';
        break;
        case'item_function_list':
        echo'<tr>'
        .'<th>'.__('value').'</th>'
        .'<td>';

        //	Mappings
        echo'<table id="ItemFunctionListMapping" class="list-basic list-border header-align-center input-mappings" style="margin:0px auto 10px;" width="99%">';
        echo'<tr>'
        .'<th></th>'    //	Delete button
        .'<th width="100%">'.__d('pos','function').'</th>'
        .'</tr>';
        if(!empty($vals['scfg_value'])){
        foreach($vals['scfg_value']as $mapping)
        echo generateMappingRowContent($this,'itemFunctionList',array('posFunctions'=>$posFunctions),$mapping);
        }
        echo'</table>';

        //	Add row button
        echo'<div style="float:right; padding-right:10px;">'
        .$this->element('button',array('id'=>'AddRowButton','content'=>__d('pos','add_row'),'class'=>'button-basic-2','appendClass'=>'js-add-mapping-row'))
        .'</div>';

        echo'</td>'
        .'</tr>';
        break;
        case'separate_inclusive_tax_on_display':
        $supportOpts=array('y'=>__('yes'),''=>__('no'));
        echo'<tr>'
        .'<th>'.__('value').' <em>*</em></th>'
        .'<td>'
        .'<table style="border-collapse:collapse;" width="99%">'
        //	Support Display inclusive tax in extend bar
        .'<td width="25%">'
        .__d('pos','support_display_inclusive_tax_in_check')
        .'</td>'
        .'<td>'
        .$this->Form->radio('SupportDisplayInclusiveTaxInCheck',$supportOpts,array('value'=>isset($vals['scfg_value']['support_display_inclusive_tax_in_check'])?$vals['scfg_value']['support_display_inclusive_tax_in_check']:'y','empty'=>false,'legend'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        .'</table>'
        .'</td>'
        .'</tr>';
        break;
        case'auto_track_cover_based_on_item_ordering':
        $supportOpts=array('y'=>__('yes'),'n'=>__('no'));
        echo'<tr>'
        .'<th>'.__('value').' <em>*</em></th>'
        .'<td>'
        .'<table style="border-collapse:collapse;" width="100%">'
        //	Support
        .'<tr>'
        .'<td width="25%">'
        .__d('pos','support')
        .'</td>'
        .'<td>'
        .$this->Form->radio('Support',$supportOpts,array('value'=>isset($vals['scfg_value']['support'])?$vals['scfg_value']['support']:'y','empty'=>false,'legend'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        .'</tr>';
        //	Payment Method
        echo'<tr>'
        .'<td width="25%">'.__d('pos','item_group').'</td>'
        .'<td>'

        .'<table id="ItemGroupListMapping" class="list-basic list-border header-align-center input-mappings" style="margin:0px auto 10px;" width="99%">'
        .'<tr>'
        .'<th></th>'    //	Delete button
        .'<th width="90%">'.__d('pos','item_group').'</th>'
        .'</tr>';
        $itemGroupIds=explode(",",isset($vals['scfg_value']['item_group_ids'])?$vals['scfg_value']['item_group_ids']:"");
        if(!empty($itemGroupIds)){
        foreach($itemGroupIds as $mapping)
        echo generateMappingRowContent($this,'itemGroupList',array('posMenuItemGroup'=>$itemGroupOpts),$mapping);
        }
        echo'</table>';

        //	Add row button
        echo'<div style="float:right; padding-right:10px;">'
        .$this->element('button',array('id'=>'AddRowButton','content'=>__d('pos','add_row'),'class'=>'button-basic-2','appendClass'=>'js-add-mapping-row'))
        .'</div>'
        .'</td>'
        .'</tr>';

        $periodIds1=explode(",",isset($vals['scfg_value']['period_ids'])?$vals['scfg_value']['period_ids']:"");
        $checkedPeriodIds=$vals['scfg_value']['period_ids'];
        echo'<tr id="periodRow">'
        .'<td>'
        .__d('pos','period')
        .'</td>'
        .'<td>'
        .'<div id ="PeriodsDiv"></div>'
        .'<div id ="PeriodValidationMessageDiv" style= "color:#B22; text-decoration:blink;"></div>'
        .'</table>';
        break;
        case'repeat_round_items_limitation':
        $supportOpts=array('y'=>__('yes'),''=>__('no'));
        echo'<tr>'
        .'<th>'.__('value').' <em>*</em></th>'
        .'<td>'
        .'<table style="border-collapse:collapse;" width="100%">'
        //	Support
        .'<tr>'
        .'<td width="25%">'
        .__d('pos','support')
        .'</td>'
        .'<td>'
        .$this->Form->radio('Support',$supportOpts,array('value'=>isset($vals['scfg_value']['support'])?$vals['scfg_value']['support']:'y','empty'=>false,'legend'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        .'</tr>';
        //	Item department
        echo'<tr>'
        .'<td width="25%">'.__d('pos','item_departments')
        .'</td>'
        .'<td>'
        .'<table id="ItemDeptListMapping" class="list-basic list-border header-align-center input-mappings" style="margin:0px auto 10px;" width="99%">'
        .'<tr>'
        .'<th></th>'    //	Delete button
        .'<th width="90%">'.__d('pos','item_departments').'</th>'
        .'</tr>';
        $itemDeptGroups=isset($vals['scfg_value']['item_departments'])?$vals['scfg_value']['item_departments']:[];
        if(!empty($itemDeptGroups)){
        foreach($itemDeptGroups as $mapping)
        echo generateMappingRowContent($this,'itemDepartmentList',array('posMenuItemDept'=>$itemDeptOpts),$mapping);
        }
        echo'</table>';

        //	Add row button
        echo'<div style="float:right; padding-right:10px;">'
        .$this->element('button',array('id'=>'AddRowButton','content'=>__d('pos','add_row'),'class'=>'button-basic-2','appendClass'=>'js-add-mapping-row'))
        .'</div>'
        .'</td>'
        .'</tr>';
        echo'</table>';
        break;
        case'check_listing_total_calculation_method':
        $supportOpts=array('y'=>__('yes'),''=>__('no'));
        $methodOpts=array('c'=>__d('pos','check_total'),'t'=>__d('pos','check_total_due'));
        echo'<tr>'
        .'<th>'.__('value').' <em>*</em></th>'
        .'<td>'
        .'<table style="border-collapse:collapse;" width="99%">'
        //	Support
        .'<td width="25%">'
        .__d('pos','support')
        .'</td>'
        .'<td>'
        .$this->Form->radio('Support',$supportOpts,array('value'=>isset($vals['scfg_value']['support'])?$vals['scfg_value']['support']:'y','empty'=>false,'legend'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        // Calculation Method
        .'<tr>'
        .'<td>'
        .__d('pos','calculation_method')
        .'</td>'
        .'<td>'
        .$this->Form->select('Method',$methodOpts,array('style'=>'width:100%;','default'=>isset($vals['scfg_value']['method'])?$vals['scfg_value']['method']:'','empty'=>false))
        .'</td>'
        .'</tr>'
        .'</table>'
        .'</td>'
        .'</tr>';
        break;
        case'switch_check_info_setting':
        $supportOpts=array('y'=>__('yes'),''=>__('no'));
        $selectOpts=array('open_time'=>__d('pos','open_check_time'),'cover_no'=>__d('pos','cover'),
        'check_total'=>__d('pos','check_total'),'member_number'=>__d('pos','member_number'),
        'member_name'=>__d('pos','member_name'),'owner_name'=>__d('pos','check_owner_name'),
        'table_size'=>__d('pos','table_size'),'check_info_one'=>__d('pos','check_info').' 1',
        'check_info_two'=>__d('pos','check_info').' 2','check_info_three'=>__d('pos','check_info').' 3',
        'check_info_four'=>__d('pos','check_info').' 4','check_info_five'=>__d('pos','check_info').' 5');
        echo'<tr>'
        .'<th>'.__('value').' <em>*</em></th>'
        .'<td>'
        .'<table style="border-collapse:collapse;" width="99%">'
        //Default Display
        .'<tr>'
        .'<td>'.__d('pos','default_display').' :'.'</td>'
        .'<td>'
        .$this->Form->select('DefaultDisplay',$selectOpts,array('style'=>'width:300px;','default'=>isset($vals['scfg_value']['default_display'])?$vals['scfg_value']['default_display']:'time','empty'=>false))
        .'</td>'
        .'</tr>'
        .'<th>'.'</th>'
        .'<th><b><center>'.__d('pos','visible').'</center></b></th>'
        //	Open Time
        .'<tr>'
        .'<td>'.__d('pos','open_check_time').' :'.'</td>'
        .'<td>'
        .$this->Form->radio('OpenTime',$supportOpts,array('value'=>isset($vals['scfg_value']['open_time'])?$vals['scfg_value']['open_time']:'y','empty'=>false,'legend'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        .'</tr>'
        //	Cover No
        .'<tr>'
        .'<td>'.__d('pos','cover').' :'.'</td>'
        .'<td>'
        .$this->Form->radio('CoverNo',$supportOpts,array('value'=>isset($vals['scfg_value']['cover_no'])?$vals['scfg_value']['cover_no']:'y','empty'=>false,'legend'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        .'</tr>'
        //	Check Total
        .'<tr>'
        .'<td>'.__d('pos','check_total').' :'.'</td>'
        .'<td>'
        .$this->Form->radio('CheckTotal',$supportOpts,array('value'=>isset($vals['scfg_value']['check_total'])?$vals['scfg_value']['check_total']:'y','empty'=>false,'legend'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        .'</tr>'
        //	Member No
        .'<tr>'
        .'<td>'.__d('pos','member_number').' :'.'</td>'
        .'<td>'
        .$this->Form->radio('MemberNo',$supportOpts,array('value'=>isset($vals['scfg_value']['member_number'])?$vals['scfg_value']['member_number']:'y','empty'=>false,'legend'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        .'</tr>'
        // Member Name
        .'<tr>'
        .'<td width="40%">'
        .__d('pos','member_name').' :'
        .'</td>'
        .'<td>'
        .$this->Form->radio('MemberName',$supportOpts,array('value'=>isset($vals['scfg_value']['member_name'])?$vals['scfg_value']['member_name']:'y','empty'=>false,'legend'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        .'</tr>'
        // Owner Name
        .'<tr>'
        .'<td width="40%">'
        .__d('pos','check_owner_name').' :'
        .'</td>'
        .'<td>'
        .$this->Form->radio('OwnerName',$supportOpts,array('value'=>isset($vals['scfg_value']['owner_name'])?$vals['scfg_value']['owner_name']:'y','empty'=>false,'legend'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        .'</tr>'
        // Check Info One
        .'<tr>'
        .'<td width="40%">'
        .__d('pos','check_info').' 1'.' :'
        .'</td>'
        .'<td>'
        .$this->Form->radio('CheckInfoOne',$supportOpts,array('value'=>isset($vals['scfg_value']['check_info_one'])?$vals['scfg_value']['check_info_one']:'y','empty'=>false,'legend'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        .'</tr>'
        // Check Info Two
        .'<tr>'
        .'<td width="40%">'
        .__d('pos','check_info').' 2'.' :'
        .'</td>'
        .'<td>'
        .$this->Form->radio('CheckInfoTwo',$supportOpts,array('value'=>isset($vals['scfg_value']['check_info_two'])?$vals['scfg_value']['check_info_two']:'y','empty'=>false,'legend'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        .'</tr>'
        // Check Info Three
        .'<tr>'
        .'<td width="40%">'
        .__d('pos','check_info').' 3'.' :'
        .'</td>'
        .'<td>'
        .$this->Form->radio('CheckInfoThree',$supportOpts,array('value'=>isset($vals['scfg_value']['check_info_three'])?$vals['scfg_value']['check_info_three']:'y','empty'=>false,'legend'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        .'</tr>'
        // Check Info Four
        .'<tr>'
        .'<td width="40%">'
        .__d('pos','check_info').' 4'.' :'
        .'</td>'
        .'<td>'
        .$this->Form->radio('CheckInfoFour',$supportOpts,array('value'=>isset($vals['scfg_value']['check_info_four'])?$vals['scfg_value']['check_info_four']:'y','empty'=>false,'legend'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        .'</tr>'
        //	Check Info Five
        .'<td width="25%">'
        .__d('pos','check_info').' 5'.' :'
        .'</td>'
        .'<td>'
        .$this->Form->radio('CheckInfoFive',$supportOpts,array('value'=>isset($vals['scfg_value']['check_info_five'])?$vals['scfg_value']['check_info_five']:'y','empty'=>false,'legend'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        .'</table>'
        .'</td>'
        .'</tr>';
        $location=array('station'=>__d('pos','station'));
        break;
        case'special_setup_for_inclusive_sc_tax':
        $yesNoOpts=array('y'=>__('yes'),''=>__('no'));
        echo'<tr>'
        .'<th>'.__('value').' <em>*</em></th>'
        .'<td>'
        .'<table style="border-collapse:collapse;" width="99%">'
        .'<tr>'
        .'<td width="40%">'
        .__d('pos','breakdown_at_final_check_settlement').' :'
        .'</td>'
        .'<td>'
        .$this->Form->radio('BreakdownAtFinalSettle',$yesNoOpts,array('value'=>isset($vals['scfg_value']['breakdown_at_check_settle'])?$vals['scfg_value']['breakdown_at_check_settle']:'y','empty'=>false,'legend'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        .'</tr>'
        .'</table>'
        .'</td>'
        .'</tr>';
        break;
        case'payment_rounding_dummy_payment_mapping':
        echo'<tr>'
        .'<th>'.__('value').'</th>'
        .'<td>';

        //	Mappings
        echo'<table id="DummyPaymentMethodMapping" class="list-basic list-border header-align-center input-mappings" style="margin:0px auto 10px;" width="99%">';
        echo'<tr>'
        .'<th></th>'    //	Delete button
        .'<th width="48%">'.__d('pos','payment_method').'</th>'
        .'<th width="48%">'.__d('pos','dummy_payment_method').'</th>'
        .'</tr>';

        if(!empty($vals['scfg_value']['mapping'])){
        foreach($vals['scfg_value']['mapping']as $mapping)
        echo generateMappingRowContent($this,'dummyPaymentMapping',array('posPaymentMethods'=>$posPaymentMethods,'dummyPaymentMethods'=>$posPaymentMethods),$mapping);
        }
        echo'</table>';

        //	Add row button
        echo'<div style="float:right; padding-right:10px;">'
        .$this->element('button',array('id'=>'AddRowButton','content'=>__d('pos','add_row'),'class'=>'button-basic-2','appendClass'=>'js-add-mapping-row'))
        .'</div>';

        echo'</td>'
        .'</tr>';
        break;
        case'ask_quantity_during_apply_discount':
        $yesNoOpts=array('y'=>__('yes'),''=>__('no'));
        echo'<tr>'
        .'<th>'.__('value').' <em>*</em></th>'
        .'<td>'
        .'<table style="border-collapse:collapse;" width="99%">'
        .'<tr>'
        .'<td width="40%">'
        .__d('pos','check_discount').' :'
        .'</td>'
        .'<td>'
        .$this->Form->radio('CheckDiscount',$yesNoOpts,array('value'=>isset($vals['scfg_value']['check_discount'])?$vals['scfg_value']['check_discount']:'','empty'=>false,'legend'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        .'</tr>'
        .'<tr>'
        .'<td width="40%">'
        .__d('pos','item_discount').' :'
        .'</td>'
        .'<td>'
        .$this->Form->radio('ItemDiscount',$yesNoOpts,array('value'=>isset($vals['scfg_value']['item_discount'])?$vals['scfg_value']['item_discount']:'','empty'=>false,'legend'=>false,'separator'=>'&nbsp;&nbsp;&nbsp;'))
        .'</td>'
        .'</tr>'
        .'</table>'
        .'</td>'
        .'</tr>';
        break;
default:
        break;
        }

        //	Remark
        echo'<tr>'
        .'<th>'.__('remark').'</th>'
        .'<td>'
        .$this->Form->textarea('Remark',array('class'=>'js-input-data','value'=>$vals['scfg_remark'],'style'=>'width:400px;height:50px;'))
        .'</td>'
        .'</tr>';

        echo'</table>';

        //	Available running number pools dialog
        echo'<div id="RunningNumPoolsDialog" style="display:none;">';
        echo'<div id="RunningNumPoolsInfo" style="padding:5px;height:565px;overflow:auto">';

        echo'<table class="sheet-basic sheet-border" width="99%" style="background-color:#fff;">';

        //	Header line
        echo'<tr>';
        echo'<th width="40%">'.__('name').'</th>'
        .'<th width="10%">'.__('code').'</th>'
        .'<th width="10%">'.__d('pos','prefix').'</th>'
        .'<th width="40%">'.__d('pos','number_range').'</th>';
        echo'</tr>';

        //	List of available running number pools
        if(!empty($existRunningNoPools)){
        foreach($existRunningNoPools as $existRunningNoPool){
        //	Print table
        echo'<tr class="js-running-number-pool" code="'.$existRunningNoPool['Code'].'">'
        .'<td>'
        .$existRunningNoPool['Name']
        .'</td>'
        .'<td>'
        .$existRunningNoPool['Code']
        .'</td>'
        .'<td>'
        .$existRunningNoPool['Prefix']
        .'</td>'
        .'<td>'
        .$existRunningNoPool['Range']
        .'</td>'
        .'</tr>';
        }
        }
        else    //	No record
        echo'<tr><td colspan="4" style="height:100px;text-align:center;vertical-align:middle;">'
        .__('no_record_found')
        .'</td></tr>';

        echo'</table>';

        echo'</div>';    //	End RunningNumPoolsInfo

        //	Close button
        echo'<div style="margin-top:10px;text-align:center;">';
        echo $this->element('button',array('id'=>'CloseRunningNumPoolsDialogButton','content'=>__('close')));
        echo'</div>';
        echo'</div>';    //	End RunningNumPoolsDialog

        //	Draw buttons
        echo'<div class="buttons-bar">';
        echo $this->element('button',array('id'=>'SaveButton','content'=>__('save')));
        echo" &nbsp; ".$this->element('button',array('id'=>'ResetButton','content'=>__('reset')));
        if(!empty($posConfig))
        echo" &nbsp; ".$this->element('button',array('id'=>'DeleteButton','content'=>__("delete")));
        echo" &nbsp; ".$this->element('button',array('id'=>'CancelButton','content'=>__("cancel"),'link'=>$this->Html->url('/admin'.$langPath.'pos/location_configs/config/'.$variable.$backUrlParamsStr)));
        echo'</div>';

        echo $this->element('frame_basic_bottom');

        echo'</div>';    // End zone-body

        echo $this->Form->end();

        ///////////////////////////////////////////////////////////////////////////////
        //	Other elements
        echo $this->element('confirm_dialog');        // confirm dialog box element
        echo $this->element('common_dialog');        // common dialog box element
        ?>
