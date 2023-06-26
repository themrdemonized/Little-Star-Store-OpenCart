<?php
$_['page_title']    			= "Order Manager";
$_['heading_title']    			= '<a href="https://opencart3x.ru" target="_blank" title="Разработчик Opencart3x.ru" style="color:#233746"><i class="fa fa-circle-o"></i></a> '. $_['page_title'];
$_['heading_version']   		= "3.0";

$_['entry_buttons']   			= "Buttons";
$_['help_buttons']   			= "Select buttons, you want to be displayed.";

$_['button_history']       		= "Add History";
$_['button_invoice']       		= "Print Invoices";
$_['button_shipping']       	= "Shipping Lists";
$_['button_delete']       		= "Delete Orders";
$_['button_create']       		= "Create Order";
$_['button_minimize']       	= "Toggle List";
$_['button_toggle']       		= "Toggle All";
$_['button_filter']       		= "Filter Orders";
$_['button_clear']       		= "Clear Filters";
$_['button_edit_customer']      = "Edit Customer";
$_['button_view_order']       	= "View Order";
$_['button_edit_order']       	= "Edit Order";

$_['entry_columns']   			= "Columns";
$_['help_columns']   			= "Select columns, you want to be displayed in orders list.";

$_['column_select']       		= "Select";
$_['column_order_id']       	= "ID";
$_['column_order_status_id']	= "Status";
$_['column_customer'] 			= "Customer";
$_['column_recipient'] 			= "Recipient";
$_['column_company']  			= "Company";
$_['column_products']       	= "Products";
$_['column_date_added']    		= "Added";
$_['column_date_modified'] 		= "Modified";
$_['column_payment'] 			= "Payment";
$_['column_shipping']			= "Shipping";
$_['column_subtotal']    		= "Subtotal";
$_['column_total']    			= "Total";
$_['column_actions']        	= "Actions";

$_['entry_statuses']   			= "Order Statuses";
$_['help_statuses']   			= "Orders with selected statuses and set their colors.";

$_['entry_payments']   			= "Payment Methods";
$_['help_payments']   			= "Set colors for payment methods.";

$_['entry_shippings']   		= "Shipping Methods";
$_['help_shippings']   			= "Set colors for shipping methods.";

$_['entry_mode']				= "Display Mode";
$_['help_mode']					= "Full - all orders, Custom - orders with selected statuses only.";

$_['text_mode_full']        	= "Full";
$_['text_mode_custom']    		= "Custom";

$_['entry_notice']				= "Short Notice";
$_['help_notice']				= "Will be displayed in module header on dashboard (HTML allowed).";

$_['entry_hide_dashboard']		= "Hide Dashboard";
$_['help_hide_dashboard']		= "Only Order Manager module will be displayed on dashboard.";

$_['entry_filters']				= "Show Filters";
$_['help_filters']				= "Display column filters in orders list.";

$_['entry_notify']				= "Notify Customer";
$_['help_notify']				= "Send notification to customer, when order status is changed.";

$_['entry_default_limit']		= "Default Limit";
$_['help_default_limit']		= "Default number of orders per page.";

$_['entry_default_links']		= "Default Links";
$_['help_default_links']		= "Default number of links (pages) in pagination.";

$_['entry_default_sort']		= "Default Sort";
$_['help_default_sort']			= "Default sorting column.";

$_['text_sort_order_id']        = "Order ID";
$_['text_sort_customer']    	= "Customer";
$_['text_sort_company']     	= "Company";
$_['text_sort_order_status_id'] = "Status";
$_['text_sort_date_added']  	= "Date Added";
$_['text_sort_date_modified']	= "Date Modified";
$_['text_sort_payment']     	= "Payment";
$_['text_sort_shipping']    	= "Shipping";
$_['text_sort_subtotal']       	= "Subtotal";
$_['text_sort_total']       	= "Total";

$_['entry_default_order']		= "Default Order";
$_['help_default_order']		= "Default ordering direction.";

$_['text_order_desc']			= "Descending";
$_['text_order_asc']        	= "Ascending";

$_['entry_name_format']			= "Name Format";
$_['help_name_format']			= "Select name format.";

$_['text_firstname']    		= "First name, Last name";
$_['text_lastname']       		= "Last name, First name";

$_['entry_address_format']		= "Address Format";
$_['help_address_format']		= "Available placeholders: {name}, {company}, {telephone}, {email}, {address}, {country}, {city}, {zone}, {postcode}.";

$_['entry_address_default']		= "<b>{name}</b>\nStore: {store}\n{company}\n<a href='skype:+{telephone}?call'>{telephone}</a>\n<a href='mailto:{email}'>{email}</a>\n{address}\n{country}, {city}, {zone}\n{postcode}";

$_['entry_date_format']			= "Date Format";
$_['help_date_format']			= "Define order date format, following PHP date() function rules (e.g.: d.m.Y).";

$_['entry_addips']				= "Add IPs";
$_['help_addips']				= "Automatically add new IP addresses to API.";

$_['text_color']       			= "Color";
$_['text_missing']       		= "Missing";

/* Orders list */
			
$_['text_title']				= "Order Manager";

$_['button_comment']			= "Add comment";
$_['button_change']				= "Change status";
			
$_['text_sort']					= "Sort by this column";
$_['text_products']				= "Total products: %s";
$_['text_toggle_address']		= "Toggle Address";
$_['text_toggle_products']		= "Toggle Products";
$_['text_any']					= "Any";
$_['text_yes']					= "Yes";
$_['text_no']					= "No";
$_['text_unspecified']			= "Not specified.";
$_['text_total_orders']			= "Total orders: %s";
$_['text_delete_confirm']		= "Do you really wish to delete selected orders? There's no undone!";
$_['text_comment']				= "Comment text";
$_['text_tracking']				= "Tracking number";
$_['text_empty_list']			= "No orders added or match given criteria.";
$_['text_add_info']				= "Add information";

$_['button_close']				= "Close";

$_['message_delete_success']	= "Selected orders were successfully deleted.";
$_['message_history_success']	= "History was successfully added to selected orders.";

$_['error_selected']			= "Error: No orders were selected, operation aborted.";
$_['error_api_id']				= "Error: API not found. Check your API settings.";
$_['error_api_login']			= "Error: Failed to login to API. Check your API settings.";
	
/* Generic language strings */

$_['heading_latest']   		= "You have the latest version: %s";
$_['heading_future']   		= "Wow! You have version %s and it's from THE FUTURE!";
$_['heading_update']   		= "A new version available: %s. Click <a href='http://thekrotek.com/profile/my-orders' title='Download new version' target='_blank'>here</a> to download.";

$_['entry_customer_groups'] = "Customer groups";
$_['help_customer_groups']  = "Extension will work for selected groups only.";

$_['entry_geo_zone']   		= "Geo Zone";
$_['help_geo_zone']   		= "Extension will work for selected geo zone only.";

$_['entry_tax_class']  		= "Tax Class";
$_['help_tax_class']   		= "Tax class, which will be applied for this extension";

$_['entry_status']     		= "Status";
$_['help_status']   		= "Enable or disable this extension";

$_['entry_sort_order'] 		= "Sort Order";
$_['help_sort_order']   	= "Position in the list of extensions of the same type.";

$_['text_edit_title']       = "Edit %s";

$_['text_total']    		= "Total";
$_['text_module']    		= "Modules";
$_['text_shipping']    		= "Shipping";
$_['text_payment']    		= "Payment";

$_['button_apply']      	= "Apply";

$_['text_content_top']    	= "Content Top";
$_['text_content_bottom'] 	= "Content Bottom";
$_['text_column_left']    	= "Column Left";
$_['text_column_right']   	= "Column Right";

$_['entry_module_layout']   = "Layout:";
$_['entry_module_position'] = "Position:";
$_['entry_module_status']   = "Status:";
$_['entry_module_sort']    	= "Sort Order:";

$_['message_success']     	= "Success: You have modified %s!";

$_['error_permission'] 		= "Warning: You do not have permission to modify %s!";
$_['error_version'] 		= "Impossible to get version information: no connection to server.";
$_['error_fopen'] 			= "Impossible to get version information: allow_url_fopen option is disabled.";
$_['error_numerical'] 		= "Error: %s value should be numerical.";
$_['error_percent'] 		= "Error: %s value should be numerical or in percent.";
$_['error_positive'] 		= "Error: %s value should be zero or more.";
$_['error_curl']      		= "cURL error: (%s) %s. Fix it (if necessary) and try to reinstall.";

?>