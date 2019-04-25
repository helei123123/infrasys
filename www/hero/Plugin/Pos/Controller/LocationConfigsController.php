<?php
/**
 * Location Configs Controller Class
 *
 * This file define the Location Configs Controller Class
 * @author	kwan tong
 * @copyright Copyright (c) 2014, Infrasys International Ltd.
 */

/**
 * Application controller class to manager the location configs
 */
class LocationConfigsController extends PosAppController {
	
	//	System configs
	//	Format {section1: {name1 : description1, name2 : description2, ...}, section2: {name3 : description3, name4 : description4, ...}, ...}
	private $systemConfigs = array(
								'system' => array(
									'fast_food_auto_takeout' => '',
									'fast_food_not_print_receipt' => '',
									'fast_food_not_auto_waive_service_charge' => '',
									'ordering_panel_input_numpad' => '',
									'ordering_panel_show_price' => '',
									'tender_amount' => '',
									'not_check_stock' => 'the_system_does_not_check_item_stock_during_operation_in_order_to_improve_the_performance',
									'business_hour_warn_level' => '',
									'auto_switch_from_pay_result_to_starting_page_time_control' => '',
									'ordering_timeout' => 'ordering_timeout_desc',
									'ordering_timeout_option' => 'define_quit_check_directly_or_select_to_continue_ordering',
									'open_table_screen_mode' => 'define_mode_to_select_table_for_operation_in_fine_dining_mode',
									'apply_discount_restriction' => '',
									'audit_log_level' => '',
									'bday_custom_data1_type' => '',
									'fine_dining_not_print_receipt' => '',
									'void_reason_for_payment_auto_discount' => 'void_reason_code_used_for_cancel_payment_with_auto_discount',
									'support_numeric_plu_only' => '',
									'barcode_ordering_format' => '',
									'calc_inclusive_tax_ref_by_check_total' => '',
									'check_info_self_define_description' => 'the_self_defined_info_descripions_for_function_add_edit_check_info',
									'auto_close_cashier_panel' => 'close_cashier_panel_automatically_in_selected_operation_mode',
									'double_check_discount_alert' => 'alert_is_prompted_if_apply_multiple_check_discount',
									'menu_mode' => 'default_table_number_for_menu_mode',
									'cover_limit' => '',
									'ordering_panel_not_show_image' => 'the_system_does_not_show_image_at_ordering_panel_and_menu_lookup_can_be_override_by_panel_not_show_image_setup',
									'reset_stock_quantity_at_daily_close' => 'reset_stock_quantity_with_different_modes_at_daily_close',
									'self_kiosk_set_menu_no_gudiance' => 'do_not_provide_set_menu_order_gudiance_in_self_kiosk_mode',
									'common_lookup_button_number' => 'common_lookup_modifier_discount_void_reason_button_setup_such_as_row_column_font_size',
									'menu_lookup_button_number' => 'menu_lookup_button_setup_such_as_row_column_font_size',
									'set_menu_button_number' => 'set_menu_button_setup_such_as_row_column_font_size',
									'skip_tips_payment_code' => 'define_payment_to_skip_tips',
									'not_allow_open_new_check' => 'the_system_does_not_allow_to_send_new_check',
									'ask_table_with_advance_mode' => 'choose_the_ask_table_no_input_panel',
									'loyalty_member' => 'allow_to_connect_to_loyalty_member_server',
									'reset_soldout_at_daily_close' => 'reset_soldout_item_at_daily_close',
									'item_stock_operation_input_mode' =>'define_input_value_by_replacing_or_adding_on',
									'skip_print_check_for_payment' => 'allow_skip_checking_of_print_guest_check_for_payment',
									'dutymeal_shop_limit' => 'dutymeal_shop_limit',
									'dutymeal_outlet_limit' => 'dutymeal_limit_for_outlet',
									'dutymeal_check_limit' => 'dutymeal_limit_for_single_check',
									'on_credit_shop_limit' => 'on_credit_shop_limit',
									'on_credit_outlet_limit' => 'on_credit_limit_for_outlet',
									'on_credit_check_limit' => 'on_credit_limit_for_single_check',
									'void_guest_check_image' => 'save_void_guest_check_image',
									'ask_table_section' => 'setup_table_section',
									'table_mode_row_and_column' => 'set_the_table_mode_table_display_arranage_with_row_and_column',
									'allow_change_item_quantity_after_send' => 'set_allowance_of_change_items_quantity_after_sending_check',
									'enlarge_ordering_basket' => 'enlarge_ordering_basket',
									'not_allow_to_order_when_zero_stock' => 'the_system_does_not_allow_to_order_item_when_zero_count',
									'split_table_with_keeping_cover' => 'keep_table_cover_after_splitting_table',
									'turn_off_testing_printer' => 'allow_to_turn_off_testing_printer_when_operating_daily_start',
									'show_table_size' => 'allow_to_show_table_size',
									'show_floor_plan_after_switch_user' => 'show_floor_plan_after_switch_user',
									'reprint_guest_check_times' => 'reprint_guest_check_times',
									'reprint_receipt_times' => 'reprint_receipt_times',
									'payment_check_types' => 'set_custom_type_for_payment_method',
									'include_previous_same_level_discount' => 'discount_calculation_include_previous_same_level_discount',
									'member_discount_not_validate_member_module' => 'member_discount_no_need_to_validate_member_module',
									'copies_of_receipt' => 'number_of_receipt_print_job',
									'adjust_payments_reprint_receipt' => 'allow_to_print_receipt_in_adjust_payments',
									'adjust_tips_reprint_receipt' => 'allow_to_print_receipt_in_adjust_tips',
									'new_check_auto_functions' => 'set_functions_to_be_executed_automatically_for_new_check',
									'enable_autopayment_by_default_payment' => 'enable_autopayment_by_default_payment',
									'generate_receipt_pdf' => 'generate_receipt_pdf',
									'screen_saver_option' => 'define_screen_saver_timeout_color_or_transparency',
									'show_page_up_and_down_button_for_list' => 'support_page_up_and_down_button',
									'export_e_journal' => 'allow_export_an_extra_receipt_with_txt_format_and_will_be_appended_to_target_file',
									'force_daily_close' => 'force_daily_close',
									'update_master_table_status' => 'update_master_table_status',
									'ask_quantity_during_apply_discount' => 'support_check_or_item_discount_and_non_open_discount',
									'member_validation_setting' => 'member_validation_setting',
									'print_check_control' => 'print_check_control',
									'table_validation_setting' => '',
									'set_order_ownership' => '',
									'number_of_drawer_owned_by_user' => 'number_of_drawer_owned_by_user',
									'payment_checking' => 'payment_checking',
									'table_attribute_mandatory_key' => 'table_attribute_mandatory_key',
									'advance_order_setting' => 'set_extra_setting_on_advance_order_function',
									'cashier_settlement_mode' => '',
									'support_mixed_revenue_non_revenue_payment' => 'support_mixed_revenue_non_revenue_payment',
									'support_continuous_printing' => 'support_continuous_printing_when_print_the_guest_check_and_settlement',
									'table_floor_plan_setting' => '',
									'settlement_count_interval_to_print_guest_questionnaire' => 'define_settlement_count_interval_that_can_print_guest_questionnaire_one_time',
									'call_number_input_setting' => 'call_number_input_setting',
									'pay_check_auto_functions' => 'pay_check_auto_functions',
									'gratuity_setting' => 'gratuity_setting',
									'item_function_list' => 'item_function_list',
									'remove_check_type_for_release_payment' => 'remove_check_type_for_release_payment',
									'dutymeal_limit_reset_period' => 'dutymeal_limit_reset_period',
									'on_credit_limit_reset_period' => 'on_credit_limit_reset_period',
									'separate_inclusive_tax_on_display' => 'separate_inclusive_tax_on_display',
									'resequence_discount_list' => 'hide_unavailable_discount_button_and_resequence_discount_list',
									'ordering_basket_show_add_waive_tax_sc_info' => 'show_added_or_waived_service_charge_and_tax_message_in_ordering_basket',
									'auto_track_cover_based_on_item_ordering' => 'auto_track_cover_based_on_item_ordering',
									'support_partial_payment' => 'support_partial_payment',
									'repeat_round_items_limitation' => 'repeat_round_items_limitation',
									'check_listing_total_calculation_method' => 'check_listing_total_calculation_method',
									'display_check_extra_info_in_ordering_basket' => 'display_check_extra_info_in_ordering_basket',
									'idle_time_logout' => 'system_logout_automatically_when_time_is_up_and_user_group_is_matched',
									'switch_check_info_setting' => 'setup_default_display_check_info_and_available_for_toggle_in_table_floor_plan_and_table_mode',
									'special_setup_for_inclusive_sc_tax' => 'special_handling_for_inclusive_sc_tax_details',
									'stay_in_cashier_when_interface_payment_failed' => 'stay_in_cashier_when_interface_payment_failed',
									'payment_rounding_dummy_payment_mapping' => 'payment_rounding_dummy_payment_mapping',
									'ordering_basket_toggle_consolidate_items_grouping_method' => 'ordering_basket_toggle_consolidate_items_grouping_method',
									'require_password_after_login_by_swipe_card' => '',
									'support_time_charge_item' => '',
									'Payment_amount_even_and_odd_indicator'=>''
								),
								'running_number' => array(
									'check_calling_number' => '',
									'item_calling_number' => '',
									'payment_running_number' => '',
									'item_print_queue_running_number' => ''
								),
								'user_limit' => array(
									'employee_discount_limit' => 'set_the_limit_amount_for_employee_discount'
								),
								'kiosk' => array(
									'payment_process_setting' => 'payment_process_setting',
									'ordering_basket_item_grouping_method' => 'ordering_basket_item_grouping_method',
									'hide_cashier_panel_numpad' => '',
									'display_admin_mode_only' => 'display_admin_button_only_in_station_information_bar',
									'hide_check_detail_bar' => 'hide_check_detail_bar_in_ordering_panel',
									'hide_station_info_bar' => 'hide_station_info_bar_in_new_order_page',
									'time_control_to_open_next_check_by_member' => 'time_control_to_allow_open_next_check_by_same_member',
									'enable_user_to_check_print_queue_status_if_alert_message' => 'enable_user_to_check_print_queue_status_if_alert_message'
								),
								'bar_tab' => array(
									'open_check_setting' => 'open_check_setting'
								)
							);
	private $availablePosFunctions = array(	//	For new check auto functions
		'set_member',
		'assign_check_type',
		'loyalty_svc_check_value',
		'loyalty_svc_suspend_card',
		'loyalty_svc_add_value',
		'loyalty_svc_issue_card',
		'loyalty_svc_transfer_card',
		'paid',
		'print_and_paid',
		'admin_mode',
		'switch_outlet',
		'assign_table_attributes',
		'change_language',
		'assign_ordering_type',
		'pms_enquiry'
	);
	
	private $availableCheckExtraInfoInOrderingBasket = array(	//	For check extra Info in Ordering Basket
		'account_name',
		'account_number',
		'card_no',
		'member_name',
		'member_number',
		'points_balance',
		'total_points_balance'
	);
	
	private $payCheckAutoFunctions = array(	//	For pay check auto functions
		'confirm_order_dialog',
		'set_call_number'
	);
	
	private $itemFunctionList = array(	//	For item function list
		'change_quantity_last',
		'delete_item',
		'repeat_item',
		'item_modifier',
		'item_discount',
		'rush_order',
		'insert_item',
		'item_detail',
		'takeout',
		'set_menu_replace_item'
	);
	
	private $cleaningStatusFunctionList = array(	//	For cleaning status
		'merge_table',
		'change_table'
	);
	
	private $defaultTableStatusColor = array( // default table status color settings
		'vacant' => 'FFFFFF',
		'seat_in' => '00A2E8',
		'occupied' => '0055B8',
		'printed' => '5B6F73',
		'cleaning' => 'A0B3B7',
		'cooking_over_time' => '031E3E'
	);

	//	Mapping of POS variables and translation keys
	private $variableKeyMapping = array(
		//	system variable
		'fast_food_auto_takeout' => 'set_auto_takeout_if_fast_food',
		'fast_food_not_print_receipt' => 'do_not_print_receipt_if_fast_food',
		'fast_food_not_auto_waive_service_charge' => 'do_not_auto_waive_service_charge_if_fast_food',
		'ordering_panel_input_numpad' => 'show_input_numpad_in_ordering_panel',
		'ordering_panel_show_price' => 'show_price_in_ordering_panel',
		'tender_amount' => 'tender_amount',
		'not_check_stock' => 'no_stock_checking',
		'business_hour_warn_level' => 'business_hour_warning_level',
		'auto_switch_from_pay_result_to_starting_page_time_control' => 'auto_switch_from_pay_result_to_starting_page_time_control',
		'ordering_timeout' => 'ordering_timeout',
		'ordering_timeout_option' => 'ordering_timeout_option',
		'open_table_screen_mode' => 'open_table_screen_mode',
		'apply_discount_restriction' => 'apply_discount_restriction',
		'audit_log_level' => 'audit_log_level',
		'bday_custom_data1_type' => 'business_day_custom_data1_type',
		'fine_dining_not_print_receipt' => 'do_not_print_receipt_if_fine_dining',
		'void_reason_for_payment_auto_discount' => 'void_reason_for_payment_auto_discount',
		'support_numeric_plu_only' => 'support_numeric_item_code_only',
		'barcode_ordering_format' => 'order_item_by_barcode_barcode_format',
		'calc_inclusive_tax_ref_by_check_total' => 'calculate_inclusive_tax_reference_by_check_total',
		'check_info_self_define_description' => 'check_info_self_define_description',
		'auto_close_cashier_panel' => 'auto_close_cashier_panel_after_finish_payment',
		'double_check_discount_alert' => 'double_apply_check_discount_alert',
		'menu_mode' => 'menu_mode',
		'cover_limit' => 'cover_limitation',
		'ordering_panel_not_show_image' => 'ordering_panel_not_show_image',
		'reset_stock_quantity_at_daily_close' => 'reset_stock_quantity_at_daily_close',
		'self_kiosk_set_menu_no_gudiance' => 'self_kiosk_set_menu_no_ordering_gudiance',
		'common_lookup_button_number' => 'common_lookup_button_number',
		'menu_lookup_button_number' => 'menu_panel_button_number',
		'set_menu_button_number' => 'set_menu_button_number',
		'skip_tips_payment_code' => 'skip_tips_payment_code',
		'not_allow_open_new_check' => 'do_not_allow_to_send_new_check',
		'ask_table_with_advance_mode' => 'table_no_input_panel_style',
		'loyalty_member' => 'loyalty_member',
		'reset_soldout_at_daily_close' => 'reset_soldout_at_daily_close',
		'item_stock_operation_input_mode' =>'item_stock_operation_input_mode',
		'skip_print_check_for_payment' => 'skip_print_check_for_payment',
		'dutymeal_shop_limit' => 'dutymeal_shop_limit',
		'dutymeal_outlet_limit' => 'duty_meal_outlet_limit',
		'dutymeal_check_limit' => 'duty_meal_check_limit',
		'on_credit_shop_limit' => 'on_credit_shop_limit',
		'on_credit_outlet_limit' => 'on_credit_outlet_limit',
		'on_credit_check_limit' => 'on_credit_check_limit',
		'void_guest_check_image' => 'void_guest_check_image',
		'ask_table_section' => 'ask_table_section',
		'table_mode_row_and_column' => 'table_mode_row_and_column',
		'allow_change_item_quantity_after_send' => 'allow_change_item_quantity_after_send',
		'enlarge_ordering_basket' => 'enlarge_order_basket',
		'not_allow_to_order_when_zero_stock' => 'not_allow_to_order_when_zero_stock',
		'split_table_with_keeping_cover' => 'split_table_with_keeping_cover',
		'turn_off_testing_printer' => 'turn_off_testing_printer',
		'show_table_size' => 'show_table_size',
		'show_floor_plan_after_switch_user' => 'show_floor_plan_after_switch_user',
		'reprint_guest_check_times' => 'reprint_guest_check_times',
		'reprint_receipt_times' => 'reprint_receipt_times',
		'payment_check_types' => 'payment_check_types',
		'include_previous_same_level_discount' => 'include_previous_same_level_discount',
		'member_discount_not_validate_member_module' => 'member_discount_not_validate_member_module',
		'copies_of_receipt' => 'copies_of_receipt',
		'adjust_payments_reprint_receipt' => 'adjust_payments_reprint_receipt',
		'adjust_tips_reprint_receipt' => 'adjust_tips_reprint_receipt',
		'new_check_auto_functions' => 'execute_functions_automatically_for_new_check',
		'enable_autopayment_by_default_payment' => 'enable_auto_payment_by_default_payment',
		'generate_receipt_pdf' => 'generate_extra_pdf_format_receipt',
		'screen_saver_option' => 'screen_saver_option',
		'show_page_up_and_down_button_for_list' => 'show_page_up_and_down_button_for_list',
		'export_e_journal' => 'export_e_journal',
		'force_daily_close' => 'force_daily_close',
		'update_master_table_status' => 'update_master_table_status',
		'ask_quantity_during_apply_discount' => 'ask_quantity_during_apply_discount',
		'member_validation_setting' => 'member_validation_setting',
		'print_check_control' => 'print_check_control',
		'table_validation_setting' => 'table_validation_setting',
		'set_order_ownership' => 'set_order_ownership',
		'number_of_drawer_owned_by_user' => 'number_of_drawer_owned_by_user',
		'payment_checking' => 'payment_checking',
		'table_attribute_mandatory_key' => 'table_attribute_mandatory_key',
		'advance_order_setting' => 'advance_order_setting',
		'cashier_settlement_mode' => 'cashier_settlement_mode',
		'support_mixed_revenue_non_revenue_payment' => 'support_mixed_revenue_non_revenue_payment',
		'support_continuous_printing' => 'support_continuous_printing',
		'table_floor_plan_setting' => 'table_floor_plan_setting',
		'settlement_count_interval_to_print_guest_questionnaire' => 'settlement_count_interval_to_print_guest_questionnaire',
		'call_number_input_setting' => 'call_number_input_setting',
		'pay_check_auto_functions' => 'pay_check_auto_functions',
		'gratuity_setting' => 'gratuity_setting',
		'item_function_list' => 'item_function_list',
		'remove_check_type_for_release_payment' => 'remove_check_type_for_release_payment',
		'dutymeal_limit_reset_period' => 'dutymeal_limit_reset_period',
		'on_credit_limit_reset_period' => 'on_credit_limit_reset_period',
		'separate_inclusive_tax_on_display' => 'separate_inclusive_tax_on_display',
		'resequence_discount_list' => 'resequence_discount_list',
		'ordering_basket_show_add_waive_tax_sc_info' => 'ordering_basket_show_add_waive_tax_sc_info',
		'auto_track_cover_based_on_item_ordering' => 'auto_track_cover_based_on_item_ordering',
		'support_partial_payment' => 'support_partial_payment',
		'repeat_round_items_limitation' => 'repeat_round_items_limitation',
		'check_listing_total_calculation_method' => 'check_listing_total_calculation_method',
		'display_check_extra_info_in_ordering_basket' => 'display_check_extra_info_in_ordering_basket',
		'switch_check_info_setting' => 'switch_check_info_setting',
		'special_setup_for_inclusive_sc_tax' => 'special_handling_for_inclusive_sc_tax',
		'stay_in_cashier_when_interface_payment_failed' => 'stay_in_cashier_when_interface_payment_failed',
		'payment_rounding_dummy_payment_mapping' => 'payment_rounding_dummy_payment_mapping',
		'ordering_basket_toggle_consolidate_items_grouping_method' => 'ordering_basket_toggle_consolidate_items_grouping_method',
		'require_password_after_login_by_swipe_card' => 'require_password_after_login_by_swipe_card',
		'support_time_charge_item' => 'support_time_charge_item',
		
		//	running number variable
		'check_calling_number' => 'check_calling_number',
		'item_calling_number' => 'item_calling_number',
		'payment_running_number' => 'payment_running_number',
		'item_print_queue_running_number' => 'item_print_queue',
		
		//	user limit variable
		'employee_discount_limit' => 'employee_discount_limit',
		
		'payment_process_setting' => 'payment_process_setting',
		'ordering_basket_item_grouping_method' => 'ordering_basket_item_grouping_method',
		'hide_cashier_panel_numpad' => 'hide_numpad_in_cashier_panel',
		'display_admin_mode_only' => 'display_admin_button_only',
		'hide_check_detail_bar' => 'hide_check_detail_bar',
		'open_check_setting' => 'open_check_setting',
		'hide_station_info_bar' => 'hide_station_info_bar',
		'idle_time_logout' => 'logout_when_idle_timeout',
		'time_control_to_open_next_check_by_member' => 'time_control_to_open_next_check_by_member',
		'enable_user_to_check_print_queue_status_if_alert_message' => 'enable_user_to_check_print_queue_status_if_alert_message',
		'Payment_amount_even_and_odd_indicator' => 'Payment_amount_even_and_odd_indicator'
	);
	
	public $uses = array('Pos.PosConfig', 'Pos.PosRunningNumberPool');
	public $components = array('User.UserAuth', 'Pos.PosAccessControl', 'Outlet.OutletApiGeneral', 'User.UserApiGeneral', 'AuditLog');


	/**
	 * common functions for all actions before filter
	 */
	public function beforeFilter() {
		parent::beforeFilter();
		
		//	Unset audit log level setting if audit log module doesn't exist
		if (!array_key_exists('audit_log', $this->plugins))
			unset($this->systemConfigs['system']['audit_log_level']);
	}

	/**
	 * Location configs listing
	 */
	public function admin_listing() {
		$this->layout = 'default_admin';
		$this->pageInfo['navKey'] = 'pos_location_configs';
		$this->pageInfo['reloadUrl'] = 'pos/location_configs/listing';
		$errorTitleStr = __('error').' : '.__d('pos', 'config_by_location');
		
		//	Check access right
		$bAllowAccess = $this->PosAccessControl->checkAccessible('config_by_location', 'read');
		if (!$bAllowAccess) {
			$this->setAction('admin_error', array('key'=>'no_permission_to_access_data', 'title'=>$errorTitleStr, 'url'=>'home'));
			return;
		}
		
		//	Get filter if any
		$selectSection = $this->Common->getUrlParam($this, 'section');
		
		$this->pageInfo['reloadUrl'] = 'pos/location_configs/listing'.(empty($selectSection) ? '' : '/section:'.$selectSection);
		
		//	Get sections
		foreach($this->systemConfigs as $curSection => $variables) 
			$sections[$curSection] = __d('pos', $curSection);
		
		//	Get system configs
		$systemConfigs = $this->systemConfigs;
		if (isset($selectSection) && isset($this->systemConfigs[$selectSection]))
			$systemConfigs = array($selectSection => $this->systemConfigs[$selectSection]);
		
		//	Get POS variables and translation keys mapping
		$variableKeyMapping = $this->variableKeyMapping;
		
		$this->set(compact('sections', 'selectSection', 'systemConfigs', 'variableKeyMapping'));
	}
	
	/**
	 * Config system variable by location
	 * @param type $variable
	 * @return type 
	 */
	public function admin_config($variable = null) {
		$this->layout = 'default_admin';
		$this->pageInfo['navKey'] = 'pos_location_configs';
		$errorTitleStr = __('error').' : '.__d('pos', 'config_by_location');
		
		if (empty($variable)) {
			$this->setAction('admin_error', array('key'=>'missing_information', 'title'=>$errorTitleStr, 'url'=>'pos/location_configs/listing'));
			return;
		}
		
		$section = '';
		foreach($this->systemConfigs as $curSection => $variables) {
			if (in_array($variable, array_keys($variables))) {
				$section = $curSection;
				break;
			}
		}
		
		if (empty($section)) {
			$this->setAction('admin_error', array('key'=>'invalid_data', 'title'=>$errorTitleStr, 'url'=>'pos/location_configs/listing'));
			return;
		}
		
		//	Check access right
		$bAllowAccess = $this->PosAccessControl->checkAccessible('config_by_location', 'read');
		if (!$bAllowAccess) {
			$this->setAction('admin_error', array('key'=>'no_permission_to_access_data', 'title'=>$errorTitleStr, 'url'=>'home'));
			return;
		}
		
		//	Get system configs
		$conditions = array(
							'scfg_section' => $section,
							'scfg_variable' => $variable
							);
		
		if (ENTERPRISE_CONFIG) {
			$this->importModel('Outlet.OutShop');
			$this->importModel('Outlet.OutOutlet');
			$this->importModel('Pos.PosStation');

			//	Get filtered shop list
			$conds = array('shop_status <>' => 'd');
			$this->ConfigZoneTools->buildSearchConditionsByModel($conds, $this->OutShop, false, false);
			$outShops = $this->OutShop->find('all', array(
										'conditions' => $conds,
										'recursive' => -1
										)
									);
			$outShopIds = array();
			foreach($outShops as $outSingleShop)
				$outShopIds[] = $outSingleShop['OutShop']['shop_id'];

			//	Get filtered Outlet list
			$conds = array('olet_status <>' => 'd');
			$this->ConfigZoneTools->buildSearchConditionsByModel($conds, $this->OutOutlet, false, false);
			$outOutlets = $this->OutOutlet->find('all', array(
										'conditions' => $conds,
										'recursive' => -1
										)
									);
			$outOutletIds = array();
			foreach($outOutlets as $outSingleOutlet)
				$outOutletIds[] = $outSingleOutlet['OutOutlet']['olet_id'];

			//	Get filtered station list
			$posStations = $this->PosStation->find('all', array(
										'conditions' => array('stat_status <>' => 'd', 'stat_shop_id' => $outShopIds),
										'recursive' => -1
										)
									);
			$posStationIds = array();
			foreach($posStations as $posStation)
				$posStationIds[] = $posStation['PosStation']['stat_id'];

			$conditions['OR'] = array(
										array('scfg_by' => ''),
										array('scfg_by' => 'shop', 'scfg_record_id' => array_merge($outShopIds, array(0))),
										array('scfg_by' => 'outlet', 'scfg_record_id' => array_merge($outOutletIds, array(0))),
										array('scfg_by' => 'station', 'scfg_record_id' => array_merge($posStationIds, array(0)))
									);
		}
		
		$systemConfigs = $this->PosConfig->find('all', array(
							'conditions' => $conditions,
							'order' => 'scfg_by ASC, scfg_record_id ASC', 
							'recursive' => -1
						)
					);
		
		$systemConfigsByLocations = array('station' => array(), 'outlet' => array(), 'shop' => array(), '' => array());
		$recordIds = array('station' => array(), 'outlet' => array(), 'shop'=> array());
 		foreach($systemConfigs as $systemConfig) {
			$location = $systemConfig['PosConfig']['scfg_by'];
			$systemConfigsByLocations[$location][] = $systemConfig['PosConfig'];
			$recordIds[$location][] = $systemConfig['PosConfig']['scfg_record_id'];
		}
		
		//	Get all shops
		$records = array('station' => array(), 'outlet' => array(), 'shop' => array());
		if (!empty($recordIds['shop']) && (!empty($this->OutShop) || $this->importModel('Outlet.OutShop'))) {
			$records['shop'] = $this->OutShop->find('list', array(
									'fields' => array('shop_code', 'shop_name_l'.$this->langKey, 'shop_id'),
									'conditions' => array(
											'shop_id' => $recordIds['shop'],
											'shop_status <>' => 'd'
										),
									'recursive' => -1
								)
							);
		}
		
		//	Get all outlets
		if (!empty($recordIds['outlet']) && (!empty($this->OutOutlet) || $this->importModel('Outlet.OutOutlet'))) {
			$records['outlet'] = $this->OutOutlet->find('list', array(
									'fields' => array('olet_code', 'olet_name_l'.$this->langKey, 'olet_id'),
									'conditions' => array(
											'olet_id' => $recordIds['outlet'],
											'olet_status <>' => 'd'
										),
									'recursive' => -1
								)
							);
		}
		
		//	Get all stations
		if (!empty($recordIds['station']) && (!empty($this->PosStation) || $this->importModel('Pos.PosStation'))) {	
			$records['station'] = $this->PosStation->find('list', array(
									'fields' => array('stat_id', 'stat_name_l'.$this->langKey),
									'conditions' => array(
											'stat_id' => $recordIds['station'],
											'stat_status <>' => 'd'
										),
									'recursive' => -1
								)
							);
		}
		
		//	Match outlet/shop/station to system configs
		foreach($systemConfigsByLocations as $location => $systemConfigs) {
			foreach($systemConfigs as $id => $systemConfig) {
				$location = $systemConfig['scfg_by'];
				$recordId = $systemConfig['scfg_record_id'];
				
				if ($location == '')								//	All location
					$systemConfigsByLocations[$location][$id]['scfg_record'] = array();
				else if (isset($records[$location][$recordId]))		//	Specific shop/outlet/station
					$systemConfigsByLocations[$location][$id]['scfg_record'] = $records[$location][$recordId];
				else												//	Shop/Outet/Station not found
					$systemConfigsByLocations[$location][$id]['scfg_record'] = array();
			}
		}
		
		foreach($systemConfigsByLocations as $location => $systemConfigs) {
			if (empty($systemConfigs))
				unset($systemConfigsByLocations[$location]);
		}
		
		//	Get payment methods
		if ($variable == 'payment_running_number' || $variable == 'payment_check_types' || $variable == 'force_daily_close' || 
				$variable == 'advance_order_setting' || $variable == 'payment_rounding_dummy_payment_mapping') {
			if (!empty($this->PosPaymentMethod) || $this->importModel('Pos.PosPaymentMethod')) {
				$conditions = array('paym_status <>' => 'd');
				if (ENTERPRISE_CONFIG)
					$this->ConfigZoneTools->buildSearchConditionsByModel($conditions, $this->PosPaymentMethod, true, true);
				
				$posPaymentMethodRecs = $this->PosPaymentMethod->find('all', array(
						'fields' => array('paym_id', 'paym_name_l'.$this->langKey, 'paym_status'),
						'conditions' => $conditions,
						'order' => 'paym_seq ASC',
						'recursive' => -1
				));
				if (ENTERPRISE_CONFIG) {
					$this->ConfigZoneTools->loadCurrentLangForRecords($this->PosPaymentMethod, $posPaymentMethodRecs, array('name'));
					$this->ConfigZoneTools->loadOverrideByConfigZone($this->PosPaymentMethod, $posPaymentMethodRecs, array('name_l'.$this->langKey, 'status'));
				}
				
				$posPaymentMethods = array();
				
				//dummy selection
				if($variable == 'force_daily_close' || $variable == 'advance_order_setting' || $variable =='payment_rounding_dummy_payment_mapping')
					$posPaymentMethods = array(0 => '--- '.__('please_select').' ---');
				
				foreach($posPaymentMethodRecs as $posPaymentMethod) {
					if ($posPaymentMethod['PosPaymentMethod']['paym_status'] == '')
						$posPaymentMethods[$posPaymentMethod['PosPaymentMethod']['paym_id']] = $posPaymentMethod['PosPaymentMethod']['paym_name_l'.$this->langKey];
				}
				$this->set(compact('posPaymentMethods'));
			}
		}
		
		$this->__getUserGroupList($variable);
		
		//	Get Period	
		if ($variable == 'open_check_setting' || $variable == 'auto_track_cover_based_on_item_ordering') {
			$outletPeriodList = array();
			$params['outletIds'] = array();
			$reply = array();
			foreach($systemConfigsByLocations as $location => $systemConfigs) {
				if(!empty($systemConfigs) && $location == "outlet"){
					foreach($systemConfigs as $systemConfig)
						array_push($params['outletIds'], $systemConfig['scfg_record_id']);
					
					$errorKey = $this->OutletApiGeneral->getOutletPeriodListByOutlet($params, $reply);
					if(empty($errorKey) && $reply['periods'] != null)
						$outletPeriodList = $reply['periods'];
				}
			}
			
			$outletPeriods = array();
			foreach($outletPeriodList as $outletPeriod) {
					$outletPeriods[$outletPeriod['OutPeriod']['perd_id']] = $outletPeriod['OutPeriod']['perd_name_l'.$this->langKey];
			}
			
			$this->set(compact('outletPeriods'));
		}
		
		//	Get stations
		if ($variable == 'force_daily_close') {
			if (!empty($this->PosStation) || $this->importModel('Pos.PosStation')) {
				$stationOpts = $this->PosStation->find('list', array(
								'fields' => array('stat_id', 'stat_name_l'.$this->langKey),
								'conditions' => array(
										'stat_status' => ''
								),
						'recursive' => -1
				));
				$this->set(compact('stationOpts'));
			}
		}
		
		//	Get custom types
		if ($variable == 'payment_check_types') {
			if (!empty($this->PosCustomType) || $this->importModel('Pos.PosCustomType')) {
				$posCustomTypes = $this->PosCustomType->find('list', array(
						'fields' => array('ctyp_id', 'ctyp_name_l'.$this->langKey),
						'conditions' => array('ctyp_status' => ''),
						'recursive' => -1
				));
				$this->set(compact('posCustomTypes'));
			}
		}
		
		//	Get pos gratuities
		if ($variable == 'gratuity_setting') {
			if (!empty($this->PosGratuity) || $this->importModel('Pos.PosGratuity')) {
				$posGratuities = $this->PosGratuity->find('list', array(
						'fields' => array('grat_id', 'grat_name_l'.$this->langKey),
						'conditions' => array(
							'grat_status' => '',
							'OR' => array(
								'grat_rate >' => 0,
								'grat_fix_amount >' => 0
							)
						),
						'recursive' => -1
				));
				$this->set(compact('posGratuities'));
			}
		}
		
		//	Get pos functions
		if ($variable == 'new_check_auto_functions' || $variable == 'pay_check_auto_functions' || $variable == 'item_function_list' || $variable == 'table_floor_plan_setting') {
			$funckeys = "";
			if($variable == 'new_check_auto_functions')
				$funckeys = $this->availablePosFunctions;
			else if($variable == 'item_function_list')
				$funckeys = $this->itemFunctionList;
			else if($variable == 'table_floor_plan_setting')
				$funckeys = $this->cleaningStatusFunctionList;
			else
				$funckeys = $this->payCheckAutoFunctions;
			
			if (!empty($this->PosFunction) || $this->importModel('Pos.PosFunction')) {
				$posFunctions = $this->PosFunction->find('list', array(
						'fields' => array('func_key', 'func_name_l'.$this->langKey),
						'conditions' => array(
							'func_status' => '',
							'func_key' => $funckeys
						),
						'recursive' => -1
				));
				$this->set(compact('posFunctions'));
			}
		}
		
		//	Get Available Check Extra Info in Ordering Basket
		if ($variable == 'display_check_extra_info_in_ordering_basket') {
			foreach ($this->availableCheckExtraInfoInOrderingBasket as $availableCheckExtraInfo)
				$availabeCheckExtraInfo[$availableCheckExtraInfo] = __d('pos', $availableCheckExtraInfo);
			$this->set(compact('availabeCheckExtraInfo'));
		}
		
		//	Get user list
		if ($variable == 'employee_discount_limit' || $variable == 'force_daily_close') {
			if (!empty($this->UserUser) || $this->importModel('User.UserUser')) {
				$conditions = array(
					'user_status <>' => 'd',
					'user_role <>' => 's'
					);
				if (ENTERPRISE_CONFIG)
					$this->ConfigZoneTools->buildSearchConditionsByModel($conditions, $this->UserUser, false, false);
				$userUsers = $this->UserUser->find('all', array(
						'conditions' => $conditions,
						'order' => 'user_last_name_l'.$this->langKey.' ASC, user_first_name_l'.$this->langKey.' ASC',
						'recursive' => -1
				));
				$userOpts = array(0=>'--- '.__('please_select').' ---');
				foreach ($userUsers as $user) {
					$userName = $user['UserUser']['user_last_name_l'.$this->langKey].(empty($user['UserUser']['user_last_name_l'.$this->langKey]) ? '' : ', ').$user['UserUser']['user_first_name_l'.$this->langKey];
					if (!empty($user['UserUser']['user_number']))
						$userName .= ' ('.$user['UserUser']['user_number'].')';
					$userOpts[$user['UserUser']['user_id']] = $userName;
				}
				$this->set(compact('userOpts'));
			}
		}
		
		//	Get menu print queue names
		if ($variable == 'item_print_queue_running_number')
			$this->__getMenuPrintQueueNames();

		//	Get item group list
		if ($variable == 'auto_track_cover_based_on_item_ordering') 
			$this->__setItemGroupOpts();
		
		// Get item departments list
		if ($variable == 'repeat_round_items_limitation')
			$this->__setItemDeptOpts();
		
		// Default Table Status Color
		if ($variable == 'table_floor_plan_setting')
			$this->set('defaultTableStatusColor', $this->defaultTableStatusColor);
			
		//	Get POS variables and translation keys mapping
		$variableKeyMapping = $this->variableKeyMapping;
		
		$this->pageInfo['reloadUrl'] = 'pos/location_configs/config/'.$variable;
		
		$this->set('backUrlParamsStr', $this->Common->getUrlParamsStr($this));
		
		$this->set(compact('systemConfigsByLocations', 'variable', 'variableKeyMapping'));
	}
	
	/**
	 * View system config detail
	 * @param type $configId
	 * @return type 
	 */
	public function admin_view($configId = null) {
		$this->layout = 'default_admin';
		$this->pageInfo['navKey'] = 'pos_location_configs';
		$errorTitleStr = __('error').' : '.__d('pos', 'config_by_location');
		
		if (empty($configId)) {
			$this->setAction('admin_error', array('key'=>'missing_information', 'title'=>$errorTitleStr, 'url'=>'pos/location_configs/listing'));
			return;
		}

		//	Get system config basic information
		$posConfig = $this->PosConfig->findActiveById($configId);
		if (empty($posConfig)) {
			$this->setAction('admin_error', array('key'=>'invalid_data', 'title'=>$errorTitleStr, 'url'=>'pos/location_configs/listing'));
			return;
		}
		
		//	Check access right
		$bAllowAccess = $this->PosAccessControl->checkAccessible('config_by_location', 'read');
		if (!$bAllowAccess) {
			$this->setAction('admin_error', array('key'=>'no_permission_to_access_data', 'title'=>$errorTitleStr, 'url'=>'pos/location_configs/listing'));
			return;
		}
		
		//	Check config zone access right
		if (ENTERPRISE_CONFIG) {
			if (!$this->__checkConfigZoneAccessRight($posConfig, 'r')) {
				$this->setAction('admin_error', array('key'=>'no_permission_to_access_data', 'title'=>$errorTitleStr, 'url'=>'pos/location_configs/listing'));
				return;
			}
		}		
		
		//	Get shop/outlet/station
		$record = '&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;';
		if (empty($posConfig['PosConfig']['scfg_by'])) {
			$record = __d('pos', 'all_locations');
		}
		if ($posConfig['PosConfig']['scfg_by'] == 'outlet'  && (!empty($this->OutOutlet) || $this->importModel('Outlet.OutOutlet'))) {
			$outOutlet = $this->OutOutlet->findNotDeletedById($posConfig['PosConfig']['scfg_record_id']);
			if (!empty($outOutlet)) 
				$record = $outOutlet['OutOutlet']['olet_name_l'.$this->langKey] 
						.(empty($outOutlet['OutOutlet']['olet_code']) ? '' : ' ('.$outOutlet['OutOutlet']['olet_code'].')');
		}
		else if ($posConfig['PosConfig']['scfg_by'] == 'shop' && (!empty($this->OutShop) || $this->importModel('Outlet.OutShop'))) {
			$outShop = $this->OutShop->findNotDeletedById($posConfig['PosConfig']['scfg_record_id']);
			if (!empty($outShop)) 
				$record = $outShop['OutShop']['shop_name_l'.$this->langKey] 
						.(empty($outShop['OutShop']['shop_code']) ? '' : ' ('.$outShop['OutShop']['shop_code'].')');
			
		}
		else if ($posConfig['PosConfig']['scfg_by'] == 'station'  && (!empty($this->PosStation) || $this->importModel('Pos.PosStation'))) {			
			$posStation = $this->PosStation->findNotDeletedById($posConfig['PosConfig']['scfg_record_id']);
			if (!empty($posStation)) 
				$record = $posStation['PosStation']['stat_name_l'.$this->langKey];
		}
		
		//	Get payment methods for payment_running_number
		if ($posConfig['PosConfig']['scfg_variable'] == 'payment_running_number' && $posConfig['PosConfig']['scfg_section'] == 'running_number') {
			$posPaymentMethods = array();
			
			$configValue = json_decode($posConfig['PosConfig']['scfg_value'], true);
			if (isset($configValue['available_payment_ids']) && !empty($configValue['available_payment_ids'])) {
				$paymentMethodIds = explode(",", $configValue['available_payment_ids']);
				
				if (!empty($this->PosPaymentMethod) || $this->importModel('Pos.PosPaymentMethod'))
					$posPaymentMethods = $this->PosPaymentMethod->find('list', array(
							'fields' => array('paym_id', 'paym_name_l'.$this->langKey),
							'conditions' => array(
									'paym_id' => $paymentMethodIds,
									'paym_status' => ''
							),
							'recursive' => -1
					));
			}
			$this->set(compact('posPaymentMethods'));
		}
		
		//	Get Period	
		if (($posConfig['PosConfig']['scfg_variable'] == 'open_check_setting' && $posConfig['PosConfig']['scfg_section'] == 'bar_tab')
			|| ($posConfig['PosConfig']['scfg_variable'] == 'auto_track_cover_based_on_item_ordering')){
			$outletPeriodList = array();
			if($posConfig['PosConfig']['scfg_by'] == 'outlet'){
				$params['outletIds'] = $outOutlet['OutOutlet']['olet_id'];
				$reply = array();
				$errorKey = $this->OutletApiGeneral->getOutletPeriodListByOutlet($params, $reply);
				if(empty($errorKey) && $reply['periods'] != null)
					$outletPeriodList = $reply['periods'];
			}
			
			$outletPeriods = array();
			foreach($outletPeriodList as $outletPeriod) {
					$outletPeriods[$outletPeriod['OutPeriod']['perd_id']] = $outletPeriod['OutPeriod']['perd_name_l'.$this->langKey];
			}
			
			$this->set(compact('outletPeriods'));
		}
		
		//	Get payment methods and custom types for payment check types (separate since save format very different from payment_running_number) 
		if ($posConfig['PosConfig']['scfg_variable'] == 'payment_check_types' || $posConfig['PosConfig']['scfg_variable'] == 'force_daily_close' || 
				$posConfig['PosConfig']['scfg_variable'] == 'advance_order_setting' || $posConfig['PosConfig']['scfg_variable' ] == 'payment_rounding_dummy_payment_mapping') {
			$posPaymentMethods = $posPaymentMethodIds = $posCustomTypes = $posCustomTypeIds = array();
			
			$configValue = json_decode($posConfig['PosConfig']['scfg_value'], true);
			if($posConfig['PosConfig']['scfg_variable'] == 'payment_check_types') {
				if (!empty($configValue['mapping'])) {
					for($i=0; $i<count($configValue['mapping']); $i++) {
						$posPaymentMethodIds[] = $configValue['mapping'][$i]['paym_id'];
						$posCustomTypeIds[] = $configValue['mapping'][$i]['ctyp_id'];
					}
				}
			}
			else if($posConfig['PosConfig']['scfg_variable'] == 'force_daily_close' || $posConfig['PosConfig']['scfg_variable'] == 'advance_order_setting')
				$posPaymentMethodIds[] = $configValue['paymentId'];
			else if($posConfig['PosConfig']['scfg_variable'] == 'payment_rounding_dummy_payment_mapping'){
				if (!empty($configValue['mapping'])) {
					for($i=0; $i<count($configValue['mapping']); $i++) {
						$posPaymentMethodIds[] = $configValue['mapping'][$i]['paym_id'];
						$posPaymentMethodIds[] = $configValue['mapping'][$i]['dummy_paym_id'];
						//$dummyPaymentMethodIds[] = $configValue['mapping'][$i]['dummy_paym_id'];
					}
				}
			}
			
			if (!empty($this->PosPaymentMethod) || $this->importModel('Pos.PosPaymentMethod')) {
				$conditions = array(
					'paym_status <>' => 'd',
					'paym_id' => $posPaymentMethodIds
					);
				if (ENTERPRISE_CONFIG)
					$this->ConfigZoneTools->buildSearchConditionsByModel($conditions, $this->PosPaymentMethod, true, true);
				
				$posPaymentMethodRecs = $this->PosPaymentMethod->find('all', array(
						'fields' => array('paym_id', 'paym_name_l'.$this->langKey, 'paym_status'),
						'conditions' => $conditions,
						'order' => 'paym_seq ASC',
						'recursive' => -1
				));
				if (ENTERPRISE_CONFIG) {
					$this->ConfigZoneTools->loadCurrentLangForRecords($this->PosPaymentMethod, $posPaymentMethodRecs, array('name'));
					$this->ConfigZoneTools->loadOverrideByConfigZone($this->PosPaymentMethod, $posPaymentMethodRecs, array('name_l'.$this->langKey, 'status'));
				}
				
				$posPaymentMethods = array();
				//Add dummy selection
				if($posConfig['PosConfig']['scfg_variable'] == 'force_daily_close' || $posConfig['PosConfig']['scfg_variable'] == 'advance_order_setting')
					$posPaymentMethods = array(0 => '--- '.__('please_select').' ---');
				
				foreach($posPaymentMethodRecs as $posPaymentMethod) {
					if ($posPaymentMethod['PosPaymentMethod']['paym_status'] == '')
						$posPaymentMethods[$posPaymentMethod['PosPaymentMethod']['paym_id']] = $posPaymentMethod['PosPaymentMethod']['paym_name_l'.$this->langKey];
				}
			}
			if (!empty($this->PosCustomType) || $this->importModel('Pos.PosCustomType'))
				$posCustomTypes = $this->PosCustomType->find('list', array(
						'fields' => array('ctyp_id', 'ctyp_name_l'.$this->langKey),
						'conditions' => array(
								'ctyp_id' => $posCustomTypeIds,
								'ctyp_status' => ''
						),
						'recursive' => -1
				));
			$this->set(compact('posPaymentMethods', 'posCustomTypes'));
		}
		
		//	Get functions for new check auto functions and  pay check auto functions
		if ($posConfig['PosConfig']['scfg_variable'] == 'new_check_auto_functions' || $posConfig['PosConfig']['scfg_variable'] == 'pay_check_auto_functions' || $posConfig['PosConfig']['scfg_variable'] == 'item_function_list' || $posConfig['PosConfig']['scfg_variable'] == 'table_floor_plan_setting') {
			$posFunctions = $posFunctionKeys = array();
			$configValue = json_decode($posConfig['PosConfig']['scfg_value'], true);
			if($posConfig['PosConfig']['scfg_variable'] == 'table_floor_plan_setting' && isset($configValue['cleaning_status_function_list']))
				$configValue = $configValue['cleaning_status_function_list'];
			
			if (!empty($configValue)) {
				if(isset($configValue['function']))
					$configValue = $configValue['function'];
				for($i=0; $i<count($configValue); $i++){
					if(isset($configValue[$i]['function_key']))
						$posFunctionKeys[] = $configValue[$i]['function_key'];
				}
			}
			if (!empty($this->PosFunction) || $this->importModel('Pos.PosFunction'))
				$posFunctions = $this->PosFunction->find('list', array(
						'fields' => array('func_key', 'func_name_l'.$this->langKey),
						'conditions' => array(
								'func_key' => $posFunctionKeys,
								'func_status' => ''
						),
						'recursive' => -1
				));
			$this->set(compact('posFunctions'));
		}
		
		if ($posConfig['PosConfig']['scfg_variable'] == 'display_check_extra_info_in_ordering_basket') {
			$checkExtraInfoList = array();
			
			$configValue = json_decode($posConfig['PosConfig']['scfg_value'], true);
			if (!empty($configValue)) {
				if(isset($configValue['check_extra_info_list']))
					$configValue = $configValue['check_extra_info_list'];
				for($i=0; $i<count($configValue); $i++) {
					if(isset($configValue[$i]['check_extra_info']))
						$checkExtraInfoList[] = $configValue[$i]['check_extra_info'];
				}
			}
			
			$this->set(compact('checkExtraInfoList'));
		}
		
		//	Get functions for gratuitiy setting
		if ($posConfig['PosConfig']['scfg_variable'] == 'gratuity_setting') {
			$posGratuities = $posGratuitiesIds = array();
			
			$configValue = json_decode($posConfig['PosConfig']['scfg_value'], true);
			if (!empty($configValue['cover_control'])) {
				for($i=0; $i<count($configValue['cover_control']); $i++)
					$posGratuitiesIds[] = $configValue['cover_control'][$i]['grat_id'];
			}
			
			if (!empty($this->PosGratuity) || $this->importModel('Pos.PosGratuity'))
				$posGratuities = $this->PosGratuity->find('list', array(
						'fields' => array('grat_id', 'grat_name_l'.$this->langKey),
						'conditions' => array(
								'grat_id' => $posGratuitiesIds,
								'grat_status' => ''
						),
						'recursive' => -1
				));
			$this->set(compact('posGratuities'));
		}
		
		//	Get user lists
		if ($posConfig['PosConfig']['scfg_variable'] == 'employee_discount_limit' || $posConfig['PosConfig']['scfg_variable'] == 'force_daily_close') {
			if (!empty($this->UserUser) || $this->importModel('User.UserUser')) {
				$userUsers = $this->UserUser->find('all', array(
						'conditions' => array(
								'user_status <>' => 'd',
								'user_role <>' => 's'
						),
						'recursive' => -1
				));
				$userOpts = array(0=>'--- '.__('please_select').' ---');
				foreach ($userUsers as $user) {
					$userName = $user['UserUser']['user_last_name_l'.$this->langKey].(empty($user['UserUser']['user_last_name_l'.$this->langKey]) ? '' : ', ').$user['UserUser']['user_first_name_l'.$this->langKey];
					if (!empty($user['UserUser']['user_number']))
						$userName .= ' ('.$user['UserUser']['user_number'].')';
					$userOpts[$user['UserUser']['user_id']] = $userName;
				}
				$this->set(compact('userOpts'));
			}
		}

		//Get User Group list
		if(isset($posConfig['PosConfig']['scfg_value']))
			$this->__getUserGroupList($posConfig['PosConfig']['scfg_variable'],$posConfig['PosConfig']['scfg_value']);
		else
			$this->__getUserGroupList($posConfig['PosConfig']['scfg_variable']);
		
		//Get stations
		if ($posConfig['PosConfig']['scfg_variable'] == 'force_daily_close') {
			if (!empty($this->PosStation) || $this->importModel('Pos.PosStation')) {
				$stationOpts = $this->PosStation->find('list', array(
								'fields' => array('stat_id', 'stat_name_l'.$this->langKey),
								'conditions' => array(
										'stat_status' => ''
								),
						'recursive' => -1
				));
				$this->set(compact('stationOpts'));
			}
		}
		
		//	Get menu print queue names
		if ($posConfig['PosConfig']['scfg_variable'] == 'item_print_queue_running_number')
			$this->__getMenuPrintQueueNames();

		//Get item groups
		if ($posConfig['PosConfig']['scfg_variable'] == 'auto_track_cover_based_on_item_ordering')
			$this->__setItemGroupOpts();
		
		if ($posConfig['PosConfig']['scfg_variable'] == 'repeat_round_items_limitation')
			$this->__setItemDeptOpts();
		
		//	Default Table Status Color
		if ($posConfig['PosConfig']['scfg_variable'] == 'table_floor_plan_setting')
			$this->set('defaultTableStatusColor', $this->defaultTableStatusColor);

		$posConfig['PosConfig']['scfg_record'] = $record;
		
		//	Get POS variables and translation keys mapping
		$variableKeyMapping = $this->variableKeyMapping;
		
		$this->pageInfo['reloadUrl'] = 'pos/location_configs/view/'.$configId;
		
		$this->set('backUrlParamsStr', $this->Common->getUrlParamsStr($this));

		$this->set(compact('posConfig', 'variableKeyMapping'));
	}
	
	/**
	 * Add new system config
	 * @param type $variable
	 * @return type 
	 */
	public function admin_add($variable=null) {
		$this->layout = 'default_admin';
		$this->pageInfo['navKey'] = 'pos_location_configs';
		$errorTitleStr = __('error').' : '.__d('pos', 'config_by_location');
		
		if (empty($variable)) {
			$this->setAction('admin_error', array('key'=>'missing_information', 'title'=>$errorTitleStr, 'url'=>'pos/location_configs/listing'));
			return;
		}

		$this->pageInfo['reloadUrl'] = 'pos/location_configs/add/'.$variable;

		//	Check access right
		$bAllowAccess = $this->PosAccessControl->checkAccessible('config_by_location', 'create');
		if (!$bAllowAccess) {
			$this->setAction('admin_error', array('key'=>'no_permission_to_run_operation', 'title'=>$errorTitleStr, 'url'=>'pos/location_configs/config/'.$variable));
			return;
		}
		
		//	Get extra info
		$result = $this->__getExtraInfo($variable);
		if (!empty($result['error_msg'])) {
			$this->setAction('admin_error', array('key'=>$result['error_msg'], 'title'=>$errorTitleStr, 'url'=>'pos/location_configs/config/'.$variable));
			return;
		}
		
		//	Get POS variables and translation keys mapping
		$variableKeyMapping = $this->variableKeyMapping;
		
		$this->set('backUrlParamsStr', $this->Common->getUrlParamsStr($this));
		
		$this->set(compact('variable', 'variableKeyMapping'));
		
		$this->render('admin_edit');
	}
	
	/**
	 * Edit system config
	 * @param type $configId
	 * @return type 
	 */
	public function admin_edit($configId = null) {
		$this->layout = 'default_admin';
		$this->pageInfo['navKey'] = 'pos_location_configs';
		$errorTitleStr = __('error').' : '.__d('pos', 'config_by_location');
		
		if (empty($configId)) {
			$this->setAction('admin_error', array('key'=>'missing_information', 'title'=>$errorTitleStr, 'url'=>'pos/location_configs/listing'));
			return;
		}

		//	Get system config basic information
		$posConfig = $this->PosConfig->findActiveById($configId);
		if (empty($posConfig)) {
			$this->setAction('admin_error', array('key'=>'invalid_data', 'title'=>$errorTitleStr, 'url'=>'pos/location_configs/listing'));
			return;
		}
		$variable = $posConfig['PosConfig']['scfg_variable'];

		//	Check access right
		$bAllowAccess = $this->PosAccessControl->checkAccessible('config_by_location', 'update');
		if (!$bAllowAccess) {
			$this->setAction('admin_error', array('key'=>'no_permission_to_run_operation', 'title'=>$errorTitleStr, 'url'=>'pos/location_configs/config/'.$variable));
			return;
		}
		
		//	Check config zone access right
		if (ENTERPRISE_CONFIG) {
			if (!$this->__checkConfigZoneAccessRight($posConfig, 'w')) {
				$this->setAction('admin_error', array('key'=>'no_permission_to_run_operation', 'title'=>$errorTitleStr, 'url'=>'pos/location_configs/config/'.$variable));
				return;
			}
		}		
		
		//	Get extra info
		$result = $this->__getExtraInfo($variable, $posConfig);
		if (!empty($result['error_msg'])) {
			$this->setAction('admin_error', array('key'=>$result['error_msg'], 'title'=>$errorTitleStr, 'url'=>'pos/location_configs/config/'.$variable));
			return;
		}
		
		//	Get POS variables and translation keys mapping
		$variableKeyMapping = $this->variableKeyMapping;
		
		$this->pageInfo['reloadUrl'] = 'pos/location_configs/edit/'.$configId;
		
		$this->set('backUrlParamsStr', $this->Common->getUrlParamsStr($this));
		
		$this->set(compact('posConfig', 'variable', 'variableKeyMapping'));
	}
	
	/**
	 * Do save add/edit system config
	 * @param type $configId 
	 */
	public function admin_save($configId = null) {
		$this->layout = 'default_admin';
		$this->pageInfo['navKey'] = 'pos_location_configs';
		$errorTitleStr = __('error').' : '.__d('pos', 'config_by_location');
		
		if (!empty($configId)) {
			$posConfig = $this->PosConfig->findActiveById($configId);
			if (empty($posConfig)) {
				$this->setAction('admin_error', array('key'=>'invalid_data', 'title'=>$errorTitleStr, 'url'=>'pos/location_configs/listing'));
				return;
			}
		}
		else 
			$posConfig = null;
		
		//	Check access right
		$bAllowAccess = $this->PosAccessControl->checkAccessible('config_by_location', (empty($posConfig) ? 'create' : 'update'));
		if (!$bAllowAccess) {
			$this->setAction('admin_error', array('key'=>'no_permission_to_run_operation', 'title'=>$errorTitleStr, 'url'=>'pos/location_configs/listing'));
			return;
		}
		
		//	Check config zone access right
		if (ENTERPRISE_CONFIG) {
			if (!empty($posConfig) && !$this->__checkConfigZoneAccessRight($posConfig, 'w')) {
				$this->setAction('admin_error', array('key'=>'no_permission_to_run_operation', 'title'=>$errorTitleStr, 'url'=>'pos/location_configs/listing'));
				return;
			}
		}		

		if (empty($this->data['PosConfig'])) {
			$this->setAction('admin_error', array('key'=>'missing_information', 'title'=>$errorTitleStr, 'url'=>'pos/location_configs/listing'));
			return;
		}
		
		$postData = $this->data['PosConfig'];
		$checkErrors = array(
			array('name' => 'Section', 'required' => true, 'not_empty' => true),
			array('name' => 'Variable', 'required' => true, 'not_empty' => true),
			array('name' => 'ScfgBy', 'required' => true),
			array('name' => 'Value', 'required' => true),
			array('name' => 'Remark', 'required' => true)
		);
		
		// Special handling for generate receipt PDF
		if($postData['Variable'] == 'generate_receipt_pdf'){
			$checkErrors[] = array('name' => 'Support', 'required' => true);
			$checkErrors[] = array('name' => 'Password', 'required' => true);
			$checkErrors[] = array('name' => 'Path', 'required' => true, 'not_empty' => true);
			$postData['Value'] = array(
						'support' => $postData['Support'],
						'password' => $postData['Password'],
						'path' => $postData['Path']
			);
			$postData['Value'] = json_encode($postData['Value']);
		}
		
		// Special handling for generate receipt E journal
		if($postData['Variable'] == 'export_e_journal'){
			$checkErrors[] = array('name' => 'Support', 'required' => true);
			$checkErrors[] = array('name' => 'Path', 'required' => true, 'not_empty' => true);
			$postData['Value'] = array(
						'support' => $postData['Support'],
						'path' => $postData['Path']
			);
			$postData['Value'] = json_encode($postData['Value']);
		}
		
		//	Special handling for screen saver option
		if ($postData['Variable'] == 'screen_saver_option') {
			$checkErrors[] = array('name' => 'Timeout', 'required' => true, 'is_positive_int' => true);
			$checkErrors[] = array('name' => 'Color', 'required' => true, 'not_empty' => true);
			$checkErrors[] = array('name' => 'Transparency', 'required' => true, 'not_empty' => true);
			$checkErrors[] = array('name' => 'DisplayContent', 'required' => true, 'not_empty' => true);
			if ($postData['DisplayContent'] == "m") {
				$postData['Color'] = "000000";
				$postData['Transparency'] = "FF";
			}
			$postData['Value'] = array(
						'timeout' => $postData['Timeout'],
						'display_content' => $postData['DisplayContent'],
						'color' => $postData['Color'],
						'transparency' => $postData['Transparency']
			);
			
			$postData['Value'] = json_encode($postData['Value']);
		}
		
		//	Special handling for table_attribute_mandatory_key
		if ($postData['Variable'] == 'table_attribute_mandatory_key') {
			$postData['Value'] = array();
			for ($i=1; $i<=10; $i++){
				$postData['Value']['key'.$i] = $postData['Key'.$i];
			}
			$postData['Value'] = json_encode($postData['Value']);
		}
		
		//	Special handling for employee discount limit
		if ($postData['Variable'] == 'employee_discount_limit') {
			$checkErrors[] = array('name' => 'UserId', 'required' => true, 'not_empty' => true);
			$checkErrors[] = array('name' => 'Mode', 'required' => true);
			$checkErrors[] = array('name' => 'Limit', 'required' => true, 'not_empty' => true, 'is_numeric' => true);
			$postData['Value'] = array(
						'userId' => $postData['UserId'],
						'mode' => $postData['Mode'],
						'limit' => $postData['Limit']
			);
			$postData['Value'] = json_encode($postData['Value']);
		}
		
		//	Special handling for employee discount limit
		if ($postData['Variable'] == 'settlement_count_interval_to_print_guest_questionnaire') {
			$checkErrors[] = array('name' => 'IntervalCount', 'required' => true, 'is_positive_int' => true);
			$postData['Value'] = array(
						'interval_count' => $postData['IntervalCount']
			);
			$postData['Value'] = json_encode($postData['Value']);
		}
		
		//	Special handling for running number configs
		if ($postData['Section'] == 'running_number') {
			$checkErrors[] = array('name' => 'Support', 'required' => true);
			$checkErrors[] = array('name' => 'Method', 'required' => true);
			$postData['Value'] = array(
						'support' => $postData['Support'],
						'method' => $postData['Method'],
			);

			if ($postData['Variable'] == 'check_calling_number' || $postData['Variable'] == 'item_calling_number' || $postData['Variable'] == 'payment_running_number'){
				$checkErrors[] = array('name' => 'PoolCode', 'required' => true, 'not_empty' => true);
				$postData['Value'] = array_merge($postData['Value'], array('pool_code' => trim($postData['PoolCode'])));
			}

			if ($postData['Variable'] == 'check_calling_number' || $postData['Variable'] == 'item_calling_number'){
				$checkErrors[] = array('name' => 'Mode', 'required' => true);
				$postData['Value'] = array_merge($postData['Value'], array('mode' => $postData['Mode']));
			}
			
			//	Special handling for payment running number configs
			if ($postData['Variable'] == 'payment_running_number') {
				$checkErrors[] = array('name' => 'SingleNo', 'required' => true);
				$postData['Value'] = array_merge($postData['Value'], array('single_number_for_check' => $postData['SingleNo']));
				$availablePaymentIds = '';
				$reusablePaymentIds = '';
				
				//	Get pos payment method ids
				if (!empty($this->PosPaymentMethod) || $this->importModel('Pos.PosPaymentMethod'))
					$posPaymentMethodIds = $this->PosPaymentMethod->find('list', array(
							'fields' => array('paym_id', 'paym_id'),
							'conditions' => array('paym_status' => ''),
							'recursive' => -1
					));
				
				//	Get available payment ids and reusable payment ids
				foreach ($posPaymentMethodIds as $posPaymentMethodId) {
					if (isset($postData['PaymentMethod'.$posPaymentMethodId]) && intval($postData['PaymentMethod'.$posPaymentMethodId]) == 1) {
						$availablePaymentIds .= $posPaymentMethodId.',';
					
						if (isset($postData['PaymentReuse'.$posPaymentMethodId]) && $postData['PaymentReuse'.$posPaymentMethodId] == 'r')
							$reusablePaymentIds .= $posPaymentMethodId.',';
					}
				}
				
				//	Remove the last comma (,)
				$availablePaymentIds = empty($availablePaymentIds) ? '' : substr($availablePaymentIds, 0, -1);
				$reusablePaymentIds = empty($reusablePaymentIds) ? '' : substr($reusablePaymentIds, 0, -1);
				
				$postData['Value'] = array_merge($postData['Value'], array('available_payment_ids' => $availablePaymentIds, 'reusable_payment_ids' => $reusablePaymentIds));
			}
			
			//	Special handling for item print queue running number configs
			if ($postData['Variable'] == 'item_print_queue_running_number') {
				$printQueueNames = $this->__getMenuPrintQueueNames();
				$withCodePrintQueues = array();
				foreach (array_keys($printQueueNames) as $printQueueid){
					if (isset($postData['PrintQueueName'.$printQueueid]) && intval($postData['PrintQueueName'.$printQueueid]) == 1) {
						$checkErrors[] = array('name' => 'PoolCode'.$printQueueid, 'required' => true, 'not_empty' => true);
						$withCodePrintQueue = array('id' => $printQueueid, 'pool_code' => trim($postData['PoolCode'.$printQueueid]));
						array_push($withCodePrintQueues, $withCodePrintQueue);
					}
				}
				$postData['Value'] = array_merge($postData['Value'], array('print_queues' => $withCodePrintQueues));
			}

			$postData['Value'] = json_encode($postData['Value']);
		}
		
		//	Special handling for payment check type configs
		if ($postData['Variable'] == 'payment_check_types') {
			if (empty($this->data['PaymentCheckTypeMapping'])) {
				$this->setAction('admin_error', array('key'=>'invalid_data', 'title'=>$errorTitleStr, 'url'=>'pos/location_configs/listing'));
				return;
			}
			$mappings = $this->data['PaymentCheckTypeMapping'];
			$saveData = array(
				'mapping' => array()
			);
			
			//	Get mapping data
			for($i=0; $i<count($mappings['PaymentMethod']); $i++) {
				$paymentMethodId = $mappings['PaymentMethod'][$i];
				$customTypeId = $mappings['CustomType'][$i];
				
				//	Check for valid data
				if (empty($paymentMethodId) || empty($customTypeId)) {
					$this->setAction('admin_error', array('key'=>'invalid_data', 'title'=>$errorTitleStr, 'url'=>'pos/location_configs/listing'));
					return;
				}
				
				$row = array(
					'paym_id' => $paymentMethodId,
					'ctyp_id' => $customTypeId
				);
				$saveData['mapping'][] = $row;
			}
			$postData['Value'] = json_encode($saveData);
		}
		
		// Special handling for Force Daily Close
		if($postData['Variable'] == 'force_daily_close'){
			$checkErrors[] = array('name' => 'UserId', 'required' => true, 'not_empty' => true);
			$checkErrors[] = array('name' => 'PaymentId', 'required' => true, 'not_empty' => true);
			$checkErrors[] = array('name' => 'PayByStationId', 'required' => true, 'not_empty' => true);
			$postData['Value'] = array(
						'support' => $postData['Support'],
						'userId' => $postData['UserId'],
						'paymentId' => $postData['PaymentId'],
						'stationId' => $postData['PayByStationId'],
						'carryForward' => $postData['CarryForward']
			);
			$postData['Value'] = json_encode($postData['Value']);
		}
		
		if ($postData['Variable'] == 'idle_time_logout') {
			$checkErrors[] = array('name' => 'Timeout', 'required' => true, 'not_empty' => true,'is_positive_int' => true);
			$userGroups = $this->__getUserGroupList($postData['Variable']);
			$userGroupList = array();
			$userGroupIndex = 0;
			foreach ($userGroups as $userGroupId => $userGroupName) {
				if ($postData['UserGroup'.$userGroupId] == 1) {
					$checkErrors[] = array('name' => 'Timeout'.$userGroupId, 'required' => true, 'is_positive_int' => true);
					$temUserGroupArray = array('id' => $userGroupId, 'timeout' => $postData['Timeout'.$userGroupId]);
					$userGroupList[$userGroupIndex++] = $temUserGroupArray;
				}
			}
			$postData['Value'] = array(
				'timeout' => $postData['Timeout'],
				'user_group_ids' => $userGroupList
			);
			$postData['Value'] = json_encode($postData['Value']);
		}
		
		if($postData['Variable'] == 'open_check_setting'){
			$postData['Value'] =  array(
				'support' => $postData['Support'],
				'ask_table_number' => $postData['ask_table_number'],
				'ask_guest_number' => $postData['ask_guest_number']
			);
			
			if ($postData['ScfgBy'] == 'outlet')
				$postData['Value'] = array_merge($postData['Value'], array('period_ids' => $this->__getOutletPeriodIds($postData['OutletId'])));
			$postData['Value'] = json_encode($postData['Value']);
		}
		
		// Special handling for Table Validaiton Setting
		if($postData['Variable'] == 'table_validation_setting'){
			$checkErrors[] = array('name' => 'Support', 'required' => true);
			$checkErrors[] = array('name' => 'MsrInterfaceCode', 'required' => true);
			$checkErrors[] = array('name' => 'DefaultCover', 'required' => true, 'is_positive_int' => true);
			$checkErrors[] = array('name' => 'MinimumCheckTotalForAllTables', 'required' => true, 'is_positive_int' => true);
			$checkErrors[] = array('name' => 'MinimumChargeItemCode', 'required' => true);
			$checkErrors[] = array('name' => 'MaximumCheckTotalForAllTables', 'required' => true, 'is_positive_int' => true);
			$checkErrors[] = array('name' => 'AskForBypassMaxCheckTotal', 'required' => true);
			$checkErrors[] = array('name' => 'SkipAskCover', 'required' => true);
			$postData['Value'] = array(
						'support' => $postData['Support'],
						'msr_interface_code' => $postData['MsrInterfaceCode'],
						'default_cover' => empty($postData['DefaultCover'])? 0 : $postData['DefaultCover'],
						'minimum_check_total_for_all_tables' => empty($postData['MinimumCheckTotalForAllTables'])? 0 : $postData['MinimumCheckTotalForAllTables'],
						'minimum_charge_item_code' => $postData['MinimumChargeItemCode'],
						'maximum_check_total_for_all_tables' => empty($postData['MaximumCheckTotalForAllTables'])? 0 : $postData['MaximumCheckTotalForAllTables'],
						'ask_for_bypass_max_check_total' => $postData['AskForBypassMaxCheckTotal'],
						'skip_ask_cover' => $postData['SkipAskCover']
			);
			$postData['Value'] = json_encode($postData['Value']);
		}
		
		// Special handling for Switch Check Info Setting
		if($postData['Variable'] == 'switch_check_info_setting'){
			$checkErrors[] = array('name' => 'DefaultDisplay', 'required' => true);
			$checkErrors[] = array('name' => 'OpenTime', 'required' => true);
			$checkErrors[] = array('name' => 'CoverNo', 'required' => true);
			$checkErrors[] = array('name' => 'CheckTotal', 'required' => true);
			$checkErrors[] = array('name' => 'MemberNo', 'required' => true);
			$checkErrors[] = array('name' => 'MemberName', 'required' => true);
			$checkErrors[] = array('name' => 'OwnerName', 'required' => true);
			$checkErrors[] = array('name' => 'CheckInfoOne', 'required' => true);
			$checkErrors[] = array('name' => 'CheckInfoTwo', 'required' => true);
			$checkErrors[] = array('name' => 'CheckInfoThree', 'required' => true);
			$checkErrors[] = array('name' => 'CheckInfoFour', 'required' => true);
			$checkErrors[] = array('name' => 'CheckInfoFive', 'required' => true);
			$postData['Value'] = array(
				'default_display' => $postData['DefaultDisplay'],
				'open_time' => $postData['OpenTime'],
				'cover_no' => $postData['CoverNo'],
				'check_total' => $postData['CheckTotal'],
				'member_number' => $postData['MemberNo'],
				'member_name' => $postData['MemberName'],
				'owner_name' => $postData['OwnerName'],
				'check_info_one' => $postData['CheckInfoOne'],
				'check_info_two' => $postData['CheckInfoTwo'],
				'check_info_three' => $postData['CheckInfoThree'],
				'check_info_four' => $postData['CheckInfoFour'],
				'check_info_five' => $postData['CheckInfoFive'],
			);
			$postData['Value'] = json_encode($postData['Value']);
		}
		
		// Special handling for set call number input
		if($postData['Variable'] == 'call_number_input_setting'){
			$checkErrors[] = array('name' => 'Method', 'required' => true);
			$checkErrors[] = array('name' => 'ImageFileForScanMode', 'required' => false, 'not_empty' => false);
			$postData['Value'] = array(
						'method' => $postData['Method'],
						'image_file_for_scan_mode' => ($postData['Method'] == 's') ? $postData['ImageFileForScanMode'] : ''
			);
			$postData['Value'] = json_encode($postData['Value']);
		}
		
		//	Special handling for new check auto function configs
		if ($postData['Variable'] == 'new_check_auto_functions' || $postData['Variable'] == 'pay_check_auto_functions') {
			$autoFunctionMapping = "";
			
			$saveData = array();
			
			if($postData['Variable'] == 'new_check_auto_functions'){
				$autoFunctionMapping = 'NewCheckAutoFunctionMapping';
				$checkErrors[] = array('name' => 'SupportForSplitCheck', 'required' => true);
				$saveData['support_for_split_check'] = $postData['SupportForSplitCheck'];
			}
			else
				$autoFunctionMapping = 'PayCheckAutoFunctionMapping';
			
			if (empty($this->data[$autoFunctionMapping])) {
				$this->setAction('admin_error', array('key'=>'invalid_data', 'title'=>$errorTitleStr, 'url'=>'pos/location_configs/listing'));
				return;
			}
			$mappings = $this->data[$autoFunctionMapping];
			
			//	Get mapping data
			for($i=0; $i<count($mappings['Function']); $i++) {
				$functionKey = $mappings['Function'][$i];
				$failHandling = $mappings['FailHandling'][$i];
				$seq = $mappings['Seq'][$i];
				
				//	Check for valid data
				if (empty($functionKey) || empty($failHandling) || empty($seq)) {
					$this->setAction('admin_error', array('key'=>'invalid_data', 'title'=>$errorTitleStr, 'url'=>'pos/location_configs/listing'));
					return;
				}
				
				$row = array(
					'function_key' => $functionKey,
					'fail_handling' => $failHandling,
					'seq' => $seq
				);
				
				if($postData['Variable'] == 'new_check_auto_functions')
					$saveData['function'][] = $row;
				else
					$saveData[] = $row;
			}
			$postData['Value'] = json_encode($saveData);
		}
		
		//	Special handling for Extra Information in Ordering Basket
		if ($postData['Variable'] == 'display_check_extra_info_in_ordering_basket') {
			$saveData = array();
			
			$checkErrors[] = array('name' => 'AlwaysResetExtraInfoWindowSize', 'required' => true);
			$saveData['always_reset_extra_info_window_size'] = $postData['AlwaysResetExtraInfoWindowSize'];
			
			if (empty($this->data['CheckExtraInfoInOrderingBasketMapping'])) {
				$this->setAction('admin_error', array('key'=>'invalid_data', 'title'=>$errorTitleStr, 'url'=>'pos/location_configs/listing'));
				return;
			}
			$mappings = $this->data['CheckExtraInfoInOrderingBasketMapping'];
			
			//	Get mapping data
			for($i=0; $i<count($mappings['CheckExtraInfo']); $i++) {
				
				$checkExtraInfo = $mappings['CheckExtraInfo'][$i];
				
				//	Check for valid data
				if (empty($checkExtraInfo)) {
					$this->setAction('admin_error', array('key'=>'invalid_data', 'title'=>$errorTitleStr, 'url'=>'pos/location_configs/listing'));
					return;
				}
				
				$row = array(
					'check_extra_info' => $checkExtraInfo,
					'seq' => $i
				);
				
				$saveData['check_extra_info_list'][] = $row;

			}
			$postData['Value'] = json_encode($saveData);
		}
		
		//	Special handling for gratuity setting
		if ($postData['Variable'] == 'gratuity_setting') {
			if (empty($this->data['GratuityCoverControlMapping'])) {
				$this->setAction('admin_error', array('key'=>'invalid_data', 'title'=>$errorTitleStr, 'url'=>'pos/location_configs/listing'));
				return;
			}
			$mappings = $this->data['GratuityCoverControlMapping'];
			$saveData = array(
				'cover_control' => array()
			);
			
			//	Get mapping data
			for($i=0; $i<count($mappings['Gratuity']); $i++) {
				$gratuityId = $mappings['Gratuity'][$i];
				$minCover = $mappings['MinCover'][$i];
				$maxCover = $mappings['MaxCover'][$i];
				//	Check for valid data
				if (empty($gratuityId) || !is_numeric($minCover) || $minCover < 0 || !is_numeric($maxCover) || $maxCover < 0) {
					$this->setAction('admin_error', array('key'=>'invalid_data', 'title'=>$errorTitleStr, 'url'=>'pos/location_configs/listing'));
					return;
				}
				
				$row = array(
					'grat_id' => $gratuityId,
					'min_cover' => $minCover,
					'max_cover' => $maxCover
				);
				$saveData['cover_control'][] = $row;
			}
			$postData['Value'] = json_encode($saveData);
		}
		
		//	Special handling for payment process setting
		if ($postData['Variable'] == 'payment_process_setting') {
			$checkErrors[] = array('name' => 'LoadingBox', 'required' => true);
			$checkErrors[] = array('name' => 'Completion', 'required' => true);
			$postData['Value'] = array(
						'display_loading_box_during_payment' => $postData['LoadingBox'],
						'payment_completion_message' => $postData['Completion'],
						'payment_completion_image_name' => $postData['CompletionImage']
			);
			$postData['Value'] = json_encode($postData['Value']);
		}
		
		//	Special handling for item function list configs
		if ($postData['Variable'] == 'item_function_list'){
			if (empty($this->data['ItemFunctionListMapping'])) {
				$this->setAction('admin_error', array('key'=>'invalid_data', 'title'=>$errorTitleStr, 'url'=>'pos/location_configs/listing'));
				return;
			}
			$mappings = $this->data['ItemFunctionListMapping'];
			$saveData = array();
			
			//	Get mapping data
			for($i=0; $i<count($mappings['Function']); $i++) {
				$function = $mappings['Function'][$i];
				
				//	Check for valid data
				if (empty($function)) {
					$this->setAction('admin_error', array('key'=>'invalid_data', 'title'=>$errorTitleStr, 'url'=>'pos/location_configs/listing'));
					return;
				}
				
				$row = array(
					'function_key' => $function
				);
				$saveData[] = $row;
			}
			$postData['Value'] = json_encode($saveData);
		}
		
		// Special handling for set member validation
		if($postData['Variable'] == 'member_validation_setting'){
			$checkErrors[] = array('name' => 'NoMemberValidate', 'required' => true);
			$checkErrors[] = array('name' => 'InterfaceCode', 'required' => false, 'not_empty' => false);
			$checkErrors[] = array('name' => 'MemberType', 'required' => false, 'not_empty' => false);
			$postData['Value'] = array(
						'no_member_validation_in_set_member' => $postData['NoMemberValidate'],
						'interface_code' => $postData['InterfaceCode'],
						'member_type' => $postData['MemberType']
			);
			$postData['Value'] = json_encode($postData['Value']);
		}
		
		// Special handling for print check control
		if($postData['Variable'] == 'print_check_control'){
			$checkErrors[] = array('name' => 'Support', 'required' => true);
			$checkErrors[] = array('name' => 'MemberAttachment', 'required' => true);
			$postData['Value'] = array(
						'support' => $postData['Support'],
						'need_member_attached' => $postData['MemberAttachment']
			);
			$postData['Value'] = json_encode($postData['Value']);
		}
		
		// Special handling for set order ownership
		if($postData['Variable'] == 'set_order_ownership'){
			$checkErrors[] = array('name' => 'Support', 'required' => true);
			$checkErrors[] = array('name' => 'Type', 'required' => true);
			$postData['Value'] = array(
						'support' => $postData['Support'],
						'type' => $postData['Type']
			);
			$postData['Value'] = json_encode($postData['Value']);
		}
		
		// Special handling for payment checking control
		if($postData['Variable'] == 'payment_checking'){
			$checkErrors[] = array('name' => 'Support', 'required' => true);
			$checkErrors[] = array('name' => 'CheckDrawerOwnership', 'required' => true);
			$checkErrors[] = array('name' => 'ClearOwnershipInDailyStart', 'required' => true);
			$postData['Value'] = array(
						'support' => $postData['Support'],
						'check_drawer_ownership' => $postData['CheckDrawerOwnership'],
						'clear_ownership_in_daily_start' => $postData['ClearOwnershipInDailyStart']
			);
			$postData['Value'] = json_encode($postData['Value']);
		}
		
		// Special handling for default payment code for advance order
		if($postData['Variable'] == 'advance_order_setting'){
			$checkErrors[] = array('name' => 'Support', 'required' => true);
			$checkErrors[] = array('name' => 'PaymentId', 'required' => true, 'not_empty' => true);
			$postData['Value'] = array(
						'support' => $postData['Support'],
						'paymentId' => $postData['PaymentId'],
			);
			$postData['Value'] = json_encode($postData['Value']);
		}
		
		// Special handling for table floor plan setting
		if($postData['Variable'] == 'table_floor_plan_setting'){
			$checkErrors[] = array('name' => 'SupportCookingOvertime', 'required' => true);
			if ($postData['SupportTableStatusCleaning'] == 'y')
				$checkErrors[] = array('name' => 'ChangeCleaningToVacantInterval', 'required' => true, 'is_positive_int' => true);
			$mapping = array();
			$saveData = array();
			if(!empty($this->data['CleaningStatusFunctionListMapping'])){
				$mapping = $this->data['CleaningStatusFunctionListMapping'];
				// Get mapping data
				for($i = 0; $i < count($mapping['Function']); $i++){
					$function = $mapping['Function'][$i];
					
					//	Check for valid data
					if (empty($function)) {
						$this->setAction('admin_error', array('key'=>'invalid_data', 'title'=>$errorTitleStr, 'url'=>'pos/location_configs/listing'));
						return;
					}
					
					$row = array(
					'function_key' => $function
					);
					$saveData[] = $row;
				}
			}
			
			$postData['Value'] = array(
						'support_cooking_overtime' => $postData['SupportCookingOvertime'],
						'support_table_status_cleaning' => $postData['SupportTableStatusCleaning'],
						'automatically_change_cleaning_to_vacant_interval' => ($postData['SupportTableStatusCleaning'] == 'y') ? $postData['ChangeCleaningToVacantInterval'] : '0',
						'cleaning_status_function_list' => $saveData,
						'table_status_color' => $postData['TableStatusColor']
			);
			$postData['Value'] = json_encode($postData['Value']);
		}
		
		// Special handling for separate_inclusive_tax_on_display
		if($postData['Variable'] == 'separate_inclusive_tax_on_display'){
			$checkErrors[] = array('name' => 'SupportDisplayInclusiveTaxInCheck', 'required' => true);
			$postData['Value'] = array(
						'support_display_inclusive_tax_in_check' => $postData['SupportDisplayInclusiveTaxInCheck'],
			);
			$postData['Value'] = json_encode($postData['Value']);
		}
		
		// Special handling for auto track cover based on item ordering
		if($postData['Variable'] == 'auto_track_cover_based_on_item_ordering'){
			$checkErrors[] = array('name' => 'Support', 'required' => true);
			
			//	Check for valid data
			if (empty($this->data['ItemGroupListMapping'])) {
				$this->setAction('admin_error', array('key'=>'invalid_data', 'title'=>$errorTitleStr, 'url'=>'pos/location_configs/listing'));
				return;
			}
			$mappings = $this->data['ItemGroupListMapping'];
			$saveData = array();
			
			//	Get mapping data
			$itemGroups = '';
			for($i=0; $i<count($mappings['ItemGroup']); $i++) {
				$itemGroup = $mappings['ItemGroup'][$i];
				
				if($i == 0)
				    $itemGroups = $itemGroup;
				else
				    $itemGroups .= ','.$itemGroup;
			}
			
			$postData['Value'] = array(
						'support' => $postData['Support'],
						'item_group_ids' => $itemGroups,
			);
			
			$outletOutPeriodRecs = array();
			
			if ($postData['ScfgBy'] == 'outlet')
				$postData['Value'] = array_merge($postData['Value'], array('period_ids' => $this->__getOutletPeriodIds($postData['OutletId'])));
			$postData['Value'] = json_encode($postData['Value']);
		}
		
		
		// Special handling for repeat round items limitation
		if ($postData['Variable'] == 'repeat_round_items_limitation') {
			$checkErrors[] = array('name' => 'Support', 'required' => true);
			
			if (empty($this->data['ItemDeptListMapping'])) {
				$this->setAction('admin_error', array('key'=>'missing_information', 'title'=>$errorTitleStr, 'url'=>'pos/location_configs/listing'));
				return;
			}
			else
				$mappings = $this->data['ItemDeptListMapping'];
			
			$saveData = array();
			//	Get mapping data
			$itemDepts = '';
			$itemDept = [];
			for ($i=0; $i<count($mappings['itemDept']); $i++) {
				if (!empty($mappings['itemDept'][$i]) && !in_array($mappings['itemDept'][$i],$itemDept ))
					$itemDept[] = intval($mappings['itemDept'][$i]);
			}
			
			$postData['Value'] = array(
						'support' => $postData['Support'],
						'item_departments' => $itemDept,
			);
			
			$postData['Value'] = json_encode($postData['Value']);
		}
		
		// Special handling for check listing total calculation method
		if($postData['Variable'] == 'check_listing_total_calculation_method'){
			$checkErrors[] = array('name' => 'Support', 'required' => true);
			$checkErrors[] = array('name' => 'Method', 'required' => true);
			$postData['Value'] = array(
						'support' => $postData['Support'],
						'method' => $postData['Method']
			);
			$postData['Value'] = json_encode($postData['Value']);
		}
		
		//	Special handling for support partial payment
		if ($postData['Variable'] == 'support_partial_payment') {
			$checkErrors[] = array('name' => 'Support', 'required' => true);
			$checkErrors[] = array('name' => 'ContinueToPay', 'required' => true);
			$checkErrors[] = array('name' => 'PrintReceiptOnlyWhenFullPay', 'required' => true);
			$checkErrors[] = array('name' => 'VoidAllPaymentAfterReleasePayment', 'required' => true);
			$postData['Value'] = array(
						'support_partial_payment' => $postData['Support'],
						'continue_to_pay_after_settling_partial_payment' => $postData['ContinueToPay'],
						'print_receipt_only_when_finish_all_payment' => $postData['PrintReceiptOnlyWhenFullPay'],
						'void_all_payment_after_release_payment' => $postData['VoidAllPaymentAfterReleasePayment']
			);
			$postData['Value'] = json_encode($postData['Value']);
		}
		
		//	Special handling for inclusive sc / tax
		if ($postData['Variable'] == 'special_setup_for_inclusive_sc_tax') {
			$checkErrors[] = array('name' => 'BreakdownAtFinalSettle', 'required' => true);
			$postData['Value'] = array(
						'breakdown_at_check_settle' => $postData['BreakdownAtFinalSettle']
			);
			$postData['Value'] = json_encode($postData['Value']);
		}
		
		//	Handle the value if the variable is tender amount or check info descriptions
		if ($postData['Variable'] == 'tender_amount' || $postData['Variable'] == "check_info_self_define_description") {
			if (empty($postData['Value']))
				$postData['Value'] = NULL;
			else {
				$postData['Value'] = explode("\r\n", $postData['Value']);
				if ($postData['Value'] === false)
					$postData['Value'] = NULL;
				else {
					$tmpValue = array();
					foreach($postData['Value'] as $id => $value) {
						//	For tender amount
						if ($postData['Variable'] == 'tender_amount' && !empty($value))	//	Remove empty value
							$tmpValue[] = intval($value);
						
						//	For check info description
						if ($postData['Variable'] == "check_info_self_define_description")
							$tmpValue[] = $value;
					}
					
					if ($tmpValue === false || empty($tmpValue))
						$postData['Value'] = NULL;
					else 
						$postData['Value'] = json_encode($tmpValue);
				}
			}
		}
		
		//	Special handling for dummy payment mapping configs
		if ($postData['Variable'] == 'payment_rounding_dummy_payment_mapping') {
			if (empty($this->data['DummyPaymentMethodMapping'])) {
				$this->setAction('admin_error', array('key'=>'invalid_data', 'title'=>$errorTitleStr, 'url'=>'pos/location_configs/listing'));
				return;
			}
			$mappings = $this->data['DummyPaymentMethodMapping'];
			$saveData = array(
				'mapping' => array()
			);
			
			//	Get mapping data
			for($i=0; $i<count($mappings['PaymentMethod']); $i++) {
				$paymentMethodId = $mappings['PaymentMethod'][$i];
				$dummyPaymentMethodId = $mappings['DummyPaymentMethod'][$i];
				
				//	Check for valid data
				if (empty($paymentMethodId) || empty($dummyPaymentMethodId)) {
					$this->setAction('admin_error', array('key'=>'invalid_data', 'title'=>$errorTitleStr, 'url'=>'pos/location_configs/listing'));
					return;
				}
				
				$row = array(
					'paym_id' => $paymentMethodId,
					'dummy_paym_id' => $dummyPaymentMethodId
				);
				$saveData['mapping'][] = $row;
			}
			$postData['Value'] = json_encode($saveData);
		}
		
		if($postData['Variable'] == 'cover_limit'){
			$checkErrors[] = array('name' => 'CoverUpperBound', 'required' => true, 'is_positive_int' => true);
			$checkErrors[] = array('name' => 'CoverWarning', 'required' => true, 'is_positive_int' => true);
			$postData['Value'] = array(
						'upper_bound' => empty($postData['CoverUpperBound'])? 0 : $postData['CoverUpperBound'],
						'warning' => empty($postData['CoverWarning'])? 0 : $postData['CoverWarning']
			);
			$postData['Value'] = json_encode($postData['Value']);
		}
		
		//	ask quantity during apply discount
		if ($postData['Variable'] == 'ask_quantity_during_apply_discount') {
			$checkErrors[] = array('name' => 'CheckDiscount', 'required' => true);
			$checkErrors[] = array('name' => 'ItemDiscount', 'required' => true);
			$postData['Value'] = array(
						'check_discount' => $postData['CheckDiscount'],
						'item_discount' => $postData['ItemDiscount']
			);
			$postData['Value'] = json_encode($postData['Value']);
		}
		
		$errors = $this->Common->checkElements($postData, $checkErrors);
		if (!empty($errors)) {
			$this->setAction('admin_error', array('key'=>'invalid_data', 'title'=>$errorTitleStr, 'url'=>'pos/location_configs/listing'));
			return;
		}
		
		//	Get record id
		$recordId = 0;
		if ($postData['ScfgBy'] == 'outlet')
			$recordId = $postData['OutletId'];
		else if ($postData['ScfgBy'] == 'shop')
			$recordId = $postData['ShopId'];
		else if ($postData['ScfgBy'] == 'station')
			$recordId = $postData['StationId'];
		
		$saveData = array(
				'scfg_by' => $postData['ScfgBy'],
				'scfg_record_id' => $recordId,
				'scfg_section' => $postData['Section'],
				'scfg_variable' => $postData['Variable'],
				'scfg_index' => 0,
				'scfg_value' => $postData['Value'],
				'scfg_remark' => !empty($postData['Remark']) ? $postData['Remark'] : NULL
			);
		
		if (empty($posConfig)) 
			$this->PosConfig->create();	// create a new row
		else
			$this->PosConfig->id = $configId;	//	Edit the row

		if (!$this->PosConfig->save($saveData)) {
			$this->setAction('admin_error', array('key'=>'system_error', 'title'=>$errorTitleStr, 'url'=>'pos/location_configs/config/'.$postData['Variable']));
			return;		// should not happen
		}
		
		if (empty($posConfig))
			$configId = $this->PosConfig->id;
		
		//	Write audit log
		$this->__writeAuditLog($configId, (isset($posConfig['PosConfig']) ? $posConfig['PosConfig'] : $posConfig), $saveData);
		
		$this->redirect('/admin'.$this->langPath.'pos/location_configs/config/'.$postData['Variable'].$this->Common->getUrlParamsStr($this));
	}
	
	function admin_load_periods($outletId, $selectPeriodIds = "") {
		Configure::write('debug',0);
		$this->layout = 'ajax';
		
		if (!empty($outletId) && $outletId != 0) {
			$outletPeriods = array();
			$params['outletIds'] = $outletId;
			$reply = array();
			$errorKey = $this->OutletApiGeneral->getOutletPeriodListByOutlet($params, $reply);
			if(empty($errorKey))
				$outletPeriods = $reply['periods'];
		}
		
		$this->set(compact('outletPeriods', 'selectPeriodIds'));
	}	
	/**
	 * Delete system config
	 * @param integer $configId
	 */
	function admin_delete($configId = null) {
		$this->layout = 'default_admin';
		$this->pageInfo['navKey'] = 'pos_location_configs';
		$errorTitleStr = __('error').' : '.__d('pos', 'config_by_location');
		
		if (empty($configId)) {
			$this->setAction('admin_error', array('key'=>'missing_information', 'title'=>$errorTitleStr, 'url'=>'pos/location_configs/listing'));
			return;
		}
		
		$posConfig = $this->PosConfig->findActiveById($configId);
		if (empty($posConfig)) {
			$this->setAction('admin_error', array('key'=>'invalid_data', 'title'=>$errorTitleStr, 'url'=>'pos/location_configs/listing'));
			return;
		}
		
		//	Check access right
		$bAllowAccess = $this->PosAccessControl->checkAccessible('config_by_location', 'delete');
		if (!$bAllowAccess) {
			$this->setAction('admin_error', array('key'=>'no_permission_to_run_operation', 'title'=>$errorTitleStr, 'url'=>'pos/location_configs/config/'.$posConfig['PosConfig']['scfg_variable']));
			return;
		}
		
		//	Check config zone access right
		if (ENTERPRISE_CONFIG) {
			if (!$this->__checkConfigZoneAccessRight($posConfig, 'w')) {
				$this->setAction('admin_error', array('key'=>'no_permission_to_run_operation', 'title'=>$errorTitleStr, 'url'=>'pos/location_configs/config/'.$posConfig['PosConfig']['scfg_variable']));
				return;
			}
		}		
		
		//  Delete the record
		if (!$this->PosConfig->delete($configId)) {
			$this->setAction('admin_error', array('key'=>'system_error', 'title'=>$errorTitleStr, 'url'=>'pos/location_configs/config/'.$posConfig['PosConfig']['scfg_variable']));
			return;		// should not happen
		}
		
		//	Write audit log
		$this->__writeAuditLog($configId, $posConfig['PosConfig']);
		
		$this->redirect('/admin'.$this->langPath.'pos/location_configs/config/'.$posConfig['PosConfig']['scfg_variable'].$this->Common->getUrlParamsStr($this));		
	}
	
	
	////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////
	//	Internal Functions
	////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////
	
	/**
	 * Get extra info
	 * @param type $variable
	 * @param type $curPosConfig
	 */
	private function __getExtraInfo($variable, &$curPosConfig=null) {
		
		///////////////////////////////////////////////////////////////////
		//	Get section
		$section = '';
		foreach($this->systemConfigs as $curSection => $variables) {
			if (in_array($variable, array_keys($variables))) {
				$section = $curSection;
				break;
			}
		}
		
		if (empty($section))
			return(array('error_msg'=>'invalid_data'));
		
		///////////////////////////////////////////////////////////////////
		//	Get existed shop/outlet/station Ids
		$conditions = array(
							'scfg_section' => 'system',
							'scfg_variable' => $variable
						);
		if (!empty($curPosConfig))
			$conditions[] = array('scfg_id <>' => $curPosConfig['PosConfig']['scfg_id']);
		
		$posConfigs = $this->PosConfig->find('all', array(
						'fields' => array('scfg_id', 'scfg_by', 'scfg_record_id'),
						'conditions' => $conditions,
					)
				);
		
		$existIds = array('all' => array(), 'outlet' => array(), 'shop' => array(), 'station' => array());
		foreach($posConfigs as $posConfig) {
			$location = $posConfig['PosConfig']['scfg_by'];
			
			if (empty($location))	//	all location
				$existIds['all'][] = $posConfig['PosConfig']['scfg_id'];
			
			if ($location == 'outlet' || $location == 'shop' || $location == 'station')		//	Outlet/Shop/Station
				$existIds[$location][] = $posConfig['PosConfig']['scfg_record_id'];
		}
		
		/////////////////////////////////////////////////////////////////////////
		//	Get list of shops and outlets
		$outShops = array();
		$outShopIds = array();
		if (!empty($this->OutShop) || $this->importModel('Outlet.OutShop')) {
			$conditions = array('shop_status <>' => 'd');
			if (ENTERPRISE_CONFIG)
				$this->ConfigZoneTools->buildSearchConditionsByModel($conditions, $this->OutShop, false, false);
			$this->OutShop->unbindAll(array('hasMany' => array('OutOutlet')));
			$outShops = $this->OutShop->find('all', array(
											'conditions' => $conditions,
											'recursive' => 1,
											'order' => 'OutShop.shop_seq ASC',
											)
										);
			foreach($outShops as $outSingleShop)
				$outShopIds[] = $outSingleShop['OutShop']['shop_id'];
		}							
		
		///////////////////////////////////////////////////////////////////
		//	Get list of stations
		$posStations = array();
		if (!empty($this->PosStation) || $this->importModel('Pos.PosStation')) {
			$conditions = array('stat_status <>' => 'd');
			if (ENTERPRISE_CONFIG)
				$conditions['stat_shop_id'] = $outShopIds;
			$posStations = $this->PosStation->find('all', array(
									'conditions' => $conditions,
									'order' => 'stat_shop_id ASC, stat_name_l'.$this->langKey,
									'recursive' => -1
								)
							);
		}			
		
		///////////////////////////////////////////////////////////////////
		//	Set shop / outlet / station options
		$outlets = $stations = array();
		$optGroups = $shopOpts = $outletOpts = $stationOpts = array();
		$idx = 1;
		foreach($outShops as $outShop) {
			//	Set shop options
			$shopId = $outShop['OutShop']['shop_id'];
			$shopName = $outShop['OutShop']['shop_name_l'.$this->langKey].(empty($outShop['OutShop']['shop_code']) ? '' : ' ('.$outShop['OutShop']['shop_code'].')');
			
			$optGroups[$shopId] = $idx.'. '.$shopName;
			$idx ++;
			
			//	Set outlet options
			foreach($outShop['OutOutlet'] as $outOutlet) {
				$outletId = $outOutlet['olet_id'];
				$outletName = $outOutlet['olet_name_l'.$this->langKey].(empty($outOutlet['olet_code']) ? '' : ' ('.$outOutlet['olet_code'].')');
				
				if (in_array($outletId, $existIds['outlet']))
					continue;
				
				$outletOpts[$optGroups[$shopId]][$outletId] = $outletName;
				$outlets[$outletId] = $outletName;
			}
			
			//	Set station options
			foreach($posStations as $posStationKey => $posStation) {
				$stationId = $posStation['PosStation']['stat_id'];
				$stationName = $posStation['PosStation']['stat_name_l'.$this->langKey];
				
				if (in_array($stationId, $existIds['station']))
					continue;

				if ($posStation['PosStation']['stat_shop_id'] != $shopId)
					continue;
				
				$stationOpts[$optGroups[$shopId]][$stationId] = $stationName;
				$stations[$stationId] = $stationName;
				unset($posStations[$posStationKey]);
			}
			
			if (in_array($shopId, $existIds['shop']))
				continue;
			
			$shopOpts[$shopId] = $shopName;
		}
		
		///////////////////////////////////////////////////////////////////
		//	Get shop / outlet / station name for current record
		if (!empty($curPosConfig)) {						
			$scfgRecord = '&lt;&lt;&lt; '.__('unknown').' &gt;&gt;&gt;';
			$scfgRecordId = $curPosConfig['PosConfig']['scfg_record_id'];
			switch ($curPosConfig['PosConfig']['scfg_by']) {
				case '':
					$scfgRecord = __d('pos', 'all_locations');
					break;
				case 'shop':
					if (array_key_exists($scfgRecordId, $shopOpts))
						$scfgRecord = $shopOpts[$scfgRecordId];
					break;
				case 'outlet':
					if (array_key_exists($scfgRecordId, $outlets))
						$scfgRecord = $outlets[$scfgRecordId];
					break;
				case 'station':
					if (array_key_exists($scfgRecordId, $stations))
						$scfgRecord = $stations[$scfgRecordId];
					break;
				default:
					break;
			}
			$curPosConfig['PosConfig']['scfg_record'] = $scfgRecord;
		}
		
		///////////////////////////////////////////////////////////////////
		//	For running number section
		if ($variable == 'check_calling_number' || $variable == 'item_calling_number' || $variable == 'payment_running_number' || $variable == 'item_print_queue_running_number'){
			//	Get existing running number pool
			$existRunningNoCodes = array();
			$existRunningNoPools = array();
			$results = $this->PosRunningNumberPool->find('all', array(
							'conditions' => array(
								'PosRunningNumberPool.runp_status <>' => 'd'
								),
							'order' => 'PosRunningNumberPool.runp_seq ASC', 
							'recursive' => -1
					)
			);
			if (!empty($results)) {
				foreach($results as $result){
					$existRunningNoCodes[] = strtolower($result['PosRunningNumberPool']['runp_code']);
					$existRunningNoPools[] = array(
						'Code' => $result['PosRunningNumberPool']['runp_code'],
						'Name' => $result['PosRunningNumberPool']['runp_name_l'.$this->langKey],
						'Prefix' => $result['PosRunningNumberPool']['runp_prefix'],
						'Range' => $result['PosRunningNumberPool']['runp_start_num'].' - '.$result['PosRunningNumberPool']['runp_end_num']
					);
				}
			}
			
			//	Get shop/outlet/station for which confg is already active
			$activeLocations = array();
			$results = $this->PosConfig->find('all', array(
							'conditions' => array(
								'PosConfig.scfg_id <>' => $curPosConfig['PosConfig']['scfg_id'],
								'PosConfig.scfg_section' => 'running_number',
								'PosConfig.scfg_variable' => $variable,
								'PosConfig.scfg_value <>' => ''
							),
							'recursive' => -1
					)
			);
			if (!empty($results)){
				foreach($results as $result){
					$value = json_decode($result['PosConfig']['scfg_value'], true);
					if ($value['support'] == 'y')	//	Active config
						$activeLocations[] = array(
							'scfg_by' => $result['PosConfig']['scfg_by'],
							'scfg_record_id' => $result['PosConfig']['scfg_record_id']
						);
				}
			}
			
			$this->set(compact('existRunningNoCodes', 'existRunningNoPools', 'activeLocations'));
		}
		
		///////////////////////////////////////////////////////////////////
		//	For payment check types
		
		//	Get payment methods
		if ($variable == 'payment_running_number' || $variable == 'payment_check_types' || $variable == 'force_daily_close' || 
				$variable == 'advance_order_setting' || $variable == 'payment_rounding_dummy_payment_mapping') {
			if (!empty($this->PosPaymentMethod) || $this->importModel('Pos.PosPaymentMethod')) {
				$conditions = array('paym_status <>' => 'd');
				if (ENTERPRISE_CONFIG)
					$this->ConfigZoneTools->buildSearchConditionsByModel($conditions, $this->PosPaymentMethod, true, true);
				
				$posPaymentMethodRecs = $this->PosPaymentMethod->find('all', array(
						'fields' => array('paym_id', 'paym_name_l'.$this->langKey, 'paym_status'),
						'conditions' => $conditions,
						'order' => 'paym_seq ASC',
						'recursive' => -1
				));
				if (ENTERPRISE_CONFIG) {
					$this->ConfigZoneTools->loadCurrentLangForRecords($this->PosPaymentMethod, $posPaymentMethodRecs, array('name'));
					$this->ConfigZoneTools->loadOverrideByConfigZone($this->PosPaymentMethod, $posPaymentMethodRecs, array('name_l'.$this->langKey, 'status'));
				}
				
				$posPaymentMethods = array();
				//add dummy selection
				if($variable == 'force_daily_close' || $variable == 'advance_order_setting')
					$posPaymentMethods = array(0=>'--- '.__('please_select').' ---');
				foreach($posPaymentMethodRecs as $posPaymentMethod) {
					if ($posPaymentMethod['PosPaymentMethod']['paym_status'] == '')
						$posPaymentMethods[$posPaymentMethod['PosPaymentMethod']['paym_id']] = $posPaymentMethod['PosPaymentMethod']['paym_name_l'.$this->langKey];
				}
				$this->set(compact('posPaymentMethods'));
			}
		}
		
		//	Get custom check types
		if ($variable == 'payment_check_types') {
			if (!empty($this->PosCustomType) || $this->importModel('Pos.PosCustomType')) {
				$posCustomTypes = $this->PosCustomType->find('list', array(
						'fields' => array('ctyp_id', 'ctyp_name_l'.$this->langKey),
						'conditions' => array(
							'ctyp_status' => '',
							'ctyp_type' => 'check'
						),
						'recursive' => -1
				));
				$this->set(compact('posCustomTypes'));
			}
		}
		
		///////////////////////////////////////////////////////////////////
		//	For new check auto functions
		//	Get functions
		if ($variable == 'new_check_auto_functions' || $variable == 'pay_check_auto_functions' || $variable == 'item_function_list' || $variable == 'table_floor_plan_setting') {
			$funkeys = "";
			if($variable == 'new_check_auto_functions')
				$funkeys = $this->availablePosFunctions;
			else if($variable == 'item_function_list')
				$funkeys = $this->itemFunctionList;
			else if($variable == 'table_floor_plan_setting')
				$funkeys = $this->cleaningStatusFunctionList;
			else
				$funkeys = $this->payCheckAutoFunctions;
			
			if (!empty($this->PosFunction) || $this->importModel('Pos.PosFunction')) {
				$posFunctions = $this->PosFunction->find('list', array(
						'fields' => array('func_key', 'func_name_l'.$this->langKey),
						'conditions' => array(
							'func_status' => '',
							'func_key' => $funkeys
						),
						'recursive' => -1
				));
				$this->set(compact('posFunctions'));
			}
		}
		
		//	Get Available Check Extra Info in Ordering Basket
		if ($variable == 'display_check_extra_info_in_ordering_basket') {
			foreach ($this->availableCheckExtraInfoInOrderingBasket as $availableCheckExtraInfo)
				$availabeCheckExtraInfo[$availableCheckExtraInfo] = __d('pos', $availableCheckExtraInfo);
			$this->set(compact('availabeCheckExtraInfo'));
		}
		
		//	Get gratuities
		if ($variable == 'gratuity_setting') {
			if (!empty($this->PosGratuity) || $this->importModel('Pos.PosGratuity')) {
				$posGratuities = $this->PosGratuity->find('list', array(
						'fields' => array('grat_id', 'grat_name_l'.$this->langKey),
						'conditions' => array(
							'grat_status' => '',
							'OR' => array(
								'grat_rate >' => 0,
								'grat_fix_amount >' => 0
							)
						),
						'recursive' => -1
				));
				$this->set(compact('posGratuities'));
			}
		}
		
		///////////////////////////////////////////////////////////////////
		//	For user limit section
		if ($variable == 'employee_discount_limit' || $variable == 'force_daily_close') {
			//	Get user list
			if (!empty($this->UserUser) || $this->importModel('User.UserUser')) {
				$conditions = array(
					'user_status <>' => 'd',
					'user_role <>' => 's'
					);
				if (ENTERPRISE_CONFIG)
					$this->ConfigZoneTools->buildSearchConditionsByModel($conditions, $this->UserUser, false, false);
				$userUsers = $this->UserUser->find('all', array(
						'conditions' => $conditions,
						'order' => 'user_last_name_l'.$this->langKey.' ASC, user_first_name_l'.$this->langKey.' ASC',
						'recursive' => -1
				));
				$userOpts = array(0=>'--- '.__('please_select').' ---');
				foreach ($userUsers as $user) {
					$userName = $user['UserUser']['user_last_name_l'.$this->langKey].(empty($user['UserUser']['user_last_name_l'.$this->langKey]) ? '' : ', ').$user['UserUser']['user_first_name_l'.$this->langKey];
					if (!empty($user['UserUser']['user_number']))
						$userName .= ' ('.$user['UserUser']['user_number'].')';
					$userOpts[$user['UserUser']['user_id']] = $userName;
				}
				$this->set(compact('userOpts'));
			}
		}
		
		$this->__getUserGroupList($variable);
		
		//	Get menu print queue names
		if ($variable == 'item_print_queue_running_number')
			$this->__getMenuPrintQueueNames();

		///////////////////////////////////////////////////////////////////
		//	Get item group list
		if ($variable == 'auto_track_cover_based_on_item_ordering')
			$this->__setItemGroupOpts();
		
		
		///////////////////////////////////////////////////////////////////
		//	Get item department list
		if ($variable == 'repeat_round_items_limitation')
			$this->__setItemDeptOpts();
		
		///////////////////////////////////////////////////////////////////
		// Get default Table Status Color
		if ($variable == 'table_floor_plan_setting')
			$this->set('defaultTableStatusColor', $this->defaultTableStatusColor);
		
		$this->set(compact('existIds', 'shopOpts', 'outletOpts', 'stationOpts', 'section'));	
	}
	
	/**
	 * Set item group list to itemGroupOpts
	 */
	private function __setItemGroupOpts() {
		$meneItemGroups = array();
		$params = array();
		App::import('Component', 'Menu.MenuApiGeneral');
		$MenuApiGeneral = new MenuApiGeneralComponent(new ComponentCollection());
		$errorKey = $MenuApiGeneral->getAllMenuItemGroup($params, $reply);

		if(empty($errorKey))
			$meneItemGroups = $reply['menuItemGroups'];

		$itemGroupOpts = array(0 => '--- '.__('please_select').' ---');
		foreach ($meneItemGroups as $menuItemGroup)
			$itemGroupOpts[$menuItemGroup['MenuItemGroup']['igrp_id']] = $menuItemGroup['MenuItemGroup']['igrp_name_l'.$this->langKey].'('.$menuItemGroup['MenuItemGroup']['igrp_code'].')';

		$this->set(compact('itemGroupOpts'));
	}
	
	/**
	 * Set item department list to itemDeptOpts
	 */
	private function __setItemDeptOpts() {
		$menuItemDepts = array();
		$params = array();
		App::import('Component', 'Menu.MenuApiGeneral');
		$MenuApiGeneral = new MenuApiGeneralComponent(new ComponentCollection());
		$errorKey = $MenuApiGeneral->getAllMenuItemDepts($params, $reply);

		if(empty($errorKey))
			$menuItemDepts = $reply['item_depts'];

		$itemDeptOpts = array(0 => '--- '.__('please_select').' ---');
		foreach ($menuItemDepts as $menuItemDept) {
			$menuItemDeptName = $menuItemDept['MenuItemDept']['idep_name_l'.$this->langKey];
			$itemDeptOpts[$menuItemDept['MenuItemDept']['idep_id']] = $menuItemDeptName;
		}
		
		$this->set(compact('itemDeptOpts'));
	}
	
	
	/**
	 * Get the outlet period id
	 * @param type $outletId
	 */
	private function __getOutletPeriodIds($outletId) {
	    $outletPeriodList = array();
	    $params['outletIds'] = $outletId;
	    $reply = array();
	    $errorKey = $this->OutletApiGeneral->getOutletPeriodListByOutlet($params, $reply);
	    if(empty($errorKey) && $reply['periods'] != null)
		    $outletPeriodList = $reply['periods'];

	    $selectededPeriodIds = '';
	    foreach ($outletPeriodList as $period)
		    if (isset($this->data['Period'.$period['OutPeriod']['perd_id']]) && intval($this->data['Period'.$period['OutPeriod']['perd_id']]) == 1)
			    $selectededPeriodIds .= $period['OutPeriod']['perd_id'].',';

	    //	Remove the last comma (,)
	    $selectededPeriodIds = empty($selectededPeriodIds) ? '' : substr($selectededPeriodIds, 0, -1);
	    
	    return $selectededPeriodIds;
	}
	
	/**
	 * Check config zone access right for action
	 * @param type $posConfig
	 * @param type $action
	 */
	private function __checkConfigZoneAccessRight(&$posConfig, $action) {
		$location = $posConfig['PosConfig']['scfg_by'];
		$recordId = $posConfig['PosConfig']['scfg_record_id'];
		
		switch($location) {
		case '':
			if ($this->ConfigZoneTools->bIsSupremeConfigZone || $action == 'r')
				return true;
			break;
		case 'shop':
			if (empty($this->OutShop))
				$this->importModel('Outlet.OutShop');
			$conditions = array('shop_id' => $recordId, 'shop_status <>' => 'd');
			$this->ConfigZoneTools->buildSearchConditionsByModel($conditions, $this->OutShop, false, ($action == 'r' ? true : false));
			$outShop = $this->OutShop->find('first', array(
									'conditions' => $conditions,
									'recursive' => -1
									)
								);
			if (!empty($outShop))
				return true;
			break;
		case 'outlet':
			if (empty($this->OutOutlet))
				$this->importModel('Outlet.OutOutlet');
			$conditions = array('olet_id' => $recordId, 'olet_status <>' => 'd');
			$this->ConfigZoneTools->buildSearchConditionsByModel($conditions, $this->OutOutlet, false, ($action == 'r' ? true : false));
			$outOutlet = $this->OutOutlet->find('first', array(
									'conditions' => $conditions,
									'recursive' => -1
									)
								);
			if (!empty($outOutlet))
				return true;
			break;
		case 'station':
			//	First try to locate the station record
			if (empty($this->PosStation))
				$this->importModel('Pos.PosStation');
			$conditions = array('stat_id' => $recordId, 'stat_status <>' => 'd');
			$posStation = $this->PosStation->find('first', array(
										'conditions' => $conditions,
										'recursive' => -1
										)
									);
			
			//	Then try to check the shop of the station has the permission
			if (!empty($posStation)) {
				if (empty($this->OutShop))
					$this->importModel('Outlet.OutShop');
				$conditions = array('shop_id' => $posStation['PosStation']['stat_shop_id'], 'shop_status <>' => 'd');
				$this->ConfigZoneTools->buildSearchConditionsByModel($conditions, $this->OutShop, false, ($action == 'r' ? true : false));
				$outShop = $this->OutShop->find('first', array(
										'conditions' => $conditions,
										'recursive' => -1
										)
									);
				if (!empty($outShop))
					return true;
			}
			break;
		}
		
		return false;
	}
	
	/**
	 * Write Audit Log for Config by Location
	 * @param type $configId
	 * @param type $oldData
	 * @param type $newData
	 */
	private function __writeAuditLog($configId, $oldData = null, $newData = null) {
		$shopId = 0;
		$outletId = 0;
		$action = 'u';	// Action - 'n': add new, 'u': update, 'd': delete
		$location = '';
		$recordId = 0;
		if(empty($newData)) {
			$action = 'd';
			$location = $oldData['scfg_by'];
			$recordId = $oldData['scfg_record_id'];
		} else {
			if(empty($oldData))
				$action = 'n';
			$location = $newData['scfg_by'];
			$recordId = $newData['scfg_record_id'];
		}
		
		// Find the corresponding shop id and outlet id
		switch ($location) {
		case 'shop':
			$shopId = $recordId;
			break;
		case 'outlet':
			$outletId = $recordId;
			if (empty($this->OutOutlet))
				$this->importModel('Outlet.OutOutlet');
			$outOutlet = $this->OutOutlet->findNotDeletedById($recordId);
			if (!empty($outOutlet))
				$shopId = $outOutlet['OutOutlet']['olet_shop_id'];
			break;
		case 'station':
			if (empty($this->PosStation))
				$this->importModel('Pos.PosStation');
			$posStation = $this->PosStation->findNotDeletedById($recordId);
			if (!empty($posStation)) {
				$shopId = $posStation['PosStation']['stat_shop_id'];
				$outletId = $posStation['PosStation']['stat_olet_id'];
			}
			break;
		}
		
		//	Write audit log
		$options = array(
					'typeKey' => ( $action == 'n' ? 'new' : ( $action == 'u' ? 'update' : 'delete' ) ).'_config_by_location',
					'recordId' => $configId,
					'desc' => ( $action == 'n' ? 'New' : ( $action == 'u' ? 'Update' : 'Delete' ) ).' Config by Location ( ID:'.$configId.' )',
					'shopId' => $shopId,
					'oletId' => $outletId,
					);
		if (ENTERPRISE_CONFIG)
			$options['cfgzIds'] = $this->ConfigZoneTools->myCfgzIds;
		$this->AuditLog->writeLog('pos', $oldData, $newData, $options);
	}
	
	public function __getUserGroupList($variable, $posConfigValue = null) {
		if ($variable == 'idle_time_logout') {
			$reply = array();
			$params = array('langKey' => $this->langKey);
			if ($posConfigValue != null) {
				$value = json_decode($posConfigValue, true);
				$params['ids'] = array();
				foreach ($value['user_group_ids'] as $id => $content) {
					$params['ids'][$id] = $value['id'];
				}
			}
			$errorKey = $this->UserApiGeneral->getAllActiveUserGroups($params, $reply);
			if (empty($errorKey))
				$userGroups = $reply['userGroups'];
			$userGroupList = array();
			foreach ($userGroups as $userGroup) {
				$userGroupList[$userGroup['UserUserGroup']['ugrp_id']] = $userGroup['UserUserGroup']['ugrp_name_l'.$this->langKey];
			}
			$this->set(compact('userGroupList'));
			return $userGroupList;
		}
	}

	/**
	 * Set Menu Print Queue Names
	 */
	private function __getMenuPrintQueueNames() {
		if (!empty($this->MenuItemPrintQueue) || $this->importModel('Menu.MenuItemPrintQueue')) {
			$conditions = array(
				'itpq_status' => ''
				);
			if (ENTERPRISE_CONFIG)
				$this->ConfigZoneTools->buildSearchConditionsByModel($conditions, $this->MenuItemPrintQueue, false, true);

			$printQueueNames = $this->MenuItemPrintQueue->find('list', array(
					'fields' => array('itpq_id', 'itpq_name_l'.$this->langKey),
					'conditions' => $conditions,
					'recursive' => -1
			));
			$this->set(compact('printQueueNames'));
			return $printQueueNames;
		}
	}
}
?>