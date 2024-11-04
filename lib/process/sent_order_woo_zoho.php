<?php
/* after wocommerce payment received */

function action_woocommerce_thankyou( $order_get_id ) {

	global $wpdb;

	$order 		   = wc_get_order( $order_get_id );
	$order_status  = $order->get_status();

	$current_user_switched = current_user_switched();

	$order_data = $order->get_data(); // The Order data

	## BILLING INFORMATION:

	$billing_first_name = $order_data['billing']['first_name'];
	$billing_last_name  = $order_data['billing']['last_name'];
	$billing_company    = $order_data['billing']['company'];
	$billing_address_1  = $order_data['billing']['address_1'];
	$billing_address_2  = $order_data['billing']['address_2'];
	$billing_city 	    = $order_data['billing']['city'];
	$billing_state      = $order_data['billing']['state'];
	$billing_postcode   = $order_data['billing']['postcode'];
	$billing_country    = $order_data['billing']['country'];
	$billing_email      = $order_data['billing']['email'];
	$billing_phone      = $order_data['billing']['phone'];

	## SHIPPING INFORMATION:

	$shipping_first_name 	= $order_data['shipping']['first_name'];
	$shipping_last_name 	= $order_data['shipping']['last_name'];
	$shipping_company 		= $order_data['shipping']['company'];
	$shipping_address_1 	= $order_data['shipping']['address_1'];
	$shipping_address_2 	= $order_data['shipping']['address_2'];
	$shipping_city 			= $order_data['shipping']['city'];
	$shipping_state 		= $order_data['shipping']['state'];
	$shipping_postcode 		= $order_data['shipping']['postcode'];
	$shipping_country 		= $order_data['shipping']['country'];

	$order_session_id 	 = get_post_meta($order_get_id,'_fbf_order_data_session_id',true);
	$order_delivery_date = get_post_meta($order_get_id,'_gs_selected_date',true);

	$ZohoContactID = zoho_contact_recoard_id($order_data,$order_get_id);

	$OrderParam = $products = array();

	foreach ( $order->get_items() as $item_id => $item ) {

			 $product_id    = $item->get_product_id();
			 $_product 		= wc_get_product( $product_id );
			 $unit_price    = $_product->get_price();
			 $product_name  = $_product->get_name();
			 $sku    		= $_product->get_sku();
			 $quantity	    = $_product->get_stock_quantity();
			 $tax_status 	= $_product->get_tax_status();
			 $categories 	= get_the_terms( $product_id, 'product_cat');
			 $catname       = $categories[0]->name;

			 $weight 		= 	$_product->get_weight();
			 $length 		= 	$_product->get_length();
			 $width 		= 	$_product->get_width();
			 $height 		=	$_product->get_height();

			 $attributes    = $_product->get_attributes();

			$Productparam     = array('pname' => $product_name, 'unit_price' => $unit_price , 'stock' => $quantity , 'tax_status' => $tax_status, 'cname' => $catname , 'product_code' => $sku , 'weight' => $weight , 'length' => $length, 'width' => $width, 'height' => $height ,'attributes' => $attributes , 'product_id' => $product_id);


			 $pquantity = $item->get_quantity();

			 /* search product in zoho crm */

			$ZohoProductID	= zoho_product_update($Productparam);

			$product = array('Product_Code' => $sku , 'id' => $ZohoProductID );

			$products[] = array('product' => $product , 'quantity' => $pquantity);
		}

		$address = $billing_address_1;

		if(!empty($billing_address_2)){

			$address .= ', '.$billing_address_2;
		}

		$Saddress = $shipping_address_1;

		if(!empty($shipping_address_2)){

			$Saddress .= ', '.$shipping_address_2;
		}

		$countryName = WC()->countries->countries[ $billing_country  ];

		$countryNameShip = WC()->countries->countries[ $shipping_country  ];

		$shippinCost = $order->get_shipping_tax() + $order->get_shipping_total();

		$discount	 = $order->get_discount_tax() + $order->get_discount_total();

		$orderDate = $order->get_date_created();


		$OrderParam['Contact_Name']  		 = array('id' => $ZohoContactID);
		$OrderParam['Email']  		 		 = $billing_email;

		$OrderParam['Phone']  		 		 = $billing_phone;
		$OrderParam['Customer_IP_Address']   = get_post_meta($order_get_id,'_customer_ip_address',true);
		$OrderParam['Customer_User_Agent']   = get_post_meta($order_get_id,'_customer_user_agent',true);
		$OrderParam['Session_Data']   		 = home_url().'/visitor_session?id='.get_post_meta($order_get_id,'_fbf_order_data_session_id',true);

		$date_format = get_option( 'date_format' );

		$content_post = get_post($order_get_id);

		$dateOrder  = date('Y-m-d',strtotime($content_post->post_date));
		$dateOrder .= 'T';
		$dateOrder .= date('H:i:s',strtotime($content_post->post_date));
		$dateOrder .= '+01:00';

		$OrderParam['Payment_Method']  	     = $order->get_payment_method();

		$OrderParam['Billing_Street'] 	= $address;
		$OrderParam['Billing_State'] 	= $billing_state;
		$OrderParam['Billing_City'] 	= $billing_city;
		$OrderParam['Billing_Country'] 	= $countryName;
		$OrderParam['Billing_Code'] 	= $billing_postcode;

		$OrderParam['Shipping_Street'] 	= $Saddress;
		$OrderParam['Shipping_State'] 	= $shipping_state;
		$OrderParam['Shipping_City'] 	= $shipping_city;
		$OrderParam['Shipping_Country'] = $countryNameShip;
		$OrderParam['Shipping_Code'] 	= $shipping_postcode;

		$OrderParam['Shipping_Method'] 	= $order->get_shipping_method();
		$OrderParam['Shipping_Cost'] 	= floatval(number_format($shippinCost,2));

		$OrderParam['Discount'] 	= number_format($discount,2);


		$OrderParam['Discount_Offered'] 	= number_format($discount,2);

		$OrderParam['Adjustment'] 	= floatval(number_format($shippinCost,2));

		$OrderParam['Coupons_Used'] 	= $order->get_coupon_codes()[0];

		$OrderParam['Customer_Note'] 	= $order->get_customer_note();

		$OrderParam['Product_Details'] = $products;

	if(current_user_switched() && $order->get_payment_method() == 'cod'){

		/* Already check Quote created or not */

		$AlreadyQuote = get_post_meta($order_get_id,'_zoho_quote_id',true);

		if(empty($AlreadyQuote)){

			$ZohoContactID = zoho_contact_recoard_id($order_data,$order_get_id);

			$OrderParam['Subject']    			= "Quote #".$order_get_id;
			$OrderParam['Valid_Till']  			= get_post_meta($order_get_id,'_quote_expiry',true);
			$OrderParam['Quote_Stage'] 			= "Quote Open";
			$OrderParam['Quote_Source'] 		= "Agent via Website";
			$OrderParam['Quote_Reason'] 		= get_post_meta($order_get_id,'_quote_reason',true);
			$OrderParam['Taken_By'] 			= get_post_meta($order_get_id,'_taken_by',true);
			$OrderParam['Is_Quote'] 		    = get_post_meta($order_get_id,'_is_sales_quote',true);
			$OrderParam['Date_Quoted']          = $dateOrder;

			$CreateQuote = CreateQuoteZOHOCRM($OrderParam);

			if(!empty($CreateQuote)){

				$note = __('ZOHO Quote record ID <b>'.$CreateQuote.'</b>');
				$order->add_order_note( $note );
				update_post_meta($order_get_id,'_zoho_quote_id',$CreateQuote);
			}
		}

	}else{

		$AlreadySOCreated = get_post_meta($order_get_id,'_zoho_sale_order_id',true);

		if(empty($AlreadySOCreated)){


			$OrderParam['Subject'] 			     = "Web Order #".$order_get_id;
			$OrderParam['Status']  		 	     = $order_status;
			$OrderParam['Transaction_ID']  	     = $order->get_transaction_id();
			$OrderParam['Order_Completed_Date']  = $dateOrder;

			$OrderParam['Is_national_fitting']    = get_post_meta($order_get_id,'_is_national_fitting',true);
			$OrderParam['Vehicle_Registration']   = get_post_meta($order_get_id,'_national_fitting_reg_no',true);
			$OrderParam['Fitting_station_ID']     = get_post_meta($order_get_id,'_national_fitting_garage_id',true);
			$OrderParam['Fitting_Type']           = get_post_meta($order_get_id,'_national_fitting_type',true);
			$OrderParam['Confirmed_SO']           = get_post_meta($order_get_id,'_order_from_quote',true);
			$OrderParam['Taken_By']               = ucfirst(get_post_meta($order_get_id,'_taken_by',true));


			$Delivery_date = !empty(get_post_meta($order_get_id,'_gs_selected_date',true)) ? get_post_meta($order_get_id,'_gs_selected_date',true) : '';

			$is_national_fitting = get_post_meta($order_get_id,'_is_national_fitting',true);

			$Fitting_Date_Time_Slot = '';

			if(!empty($is_national_fitting) && $is_national_fitting == 1){

				$getfittingDate = get_post_meta($order_get_id,'_national_fitting_date_time',true);

				if(!empty($getfittingDate) && is_array($getfittingDate)){

					$Fitting_Date_Time_Slot = $getfittingDate['date'].'; '.strtoupper($getfittingDate['time']);
				}

			}

			$Fitment_advice = !empty(get_post_meta($order_get_id,'_fitting_advice',true)) ? get_post_meta($order_get_id,'_fitting_advice',true) : '';

			$OrderParam['Fitting_Date_Time_Slot']   = $Fitting_Date_Time_Slot;

			$OrderParam['Fitment_advice']           = $Fitment_advice;

			$OrderParam['Delivery_date']           = $Delivery_date;

			$Sales_Order_Source  = !empty(get_post_meta($order_get_id,'_converted_by',true)) ? get_post_meta($order_get_id,'_converted_by',true) : '';

			$OrderParam['Sales_Order_Source']           = !empty($Sales_Order_Source) ? ucfirst($Sales_Order_Source) : '';

			/* if coupon apply into quote then coupon data will be sent with sale order */

			$wpquoteID 			        = get_post_meta($order_get_id,'_order_from_quote',true);

			if(!empty($wpquoteID)){

				$orderQuote 		        = wc_get_order( $wpquoteID );

				$discountQuote	            = $orderQuote->get_discount_tax() + $orderQuote->get_discount_total();

				if($discountQuote > 0){

					$OrderParam['Discount'] 	= number_format($discountQuote,2);

					$OrderParam['Coupons_Used'] 	= $orderQuote->get_coupon_codes()[0];

					$OrderParam['Discount_Offered'] 	= number_format($discountQuote,2);

				}

			}


			//echo '<pre>'; print_r($OrderParam);


			$CreateSaleOrder = CreateSaleOrder($OrderParam);

			if(!empty($CreateSaleOrder)){

				$note = __('ZOHO sale order record ID <b>'.$CreateSaleOrder.'</b>');
				$order->add_order_note( $note );
				update_post_meta($order_get_id,'_zoho_sale_order_id',$CreateSaleOrder);


				$wpquoteID = get_post_meta($order_get_id,'_order_from_quote',true);

				$ZohoQuoteId = get_post_meta($wpquoteID,'_zoho_quote_id',true);

				$Converted_by_Customer = ucfirst(get_post_meta($order_get_id,'_converted_by',true));

				if(!empty($ZohoQuoteId)){

					$UpdateQuoteParam = array(
												'Converted_to_Order' 		=> (string)$order_get_id,
												'Date_Converted'     		=> $dateOrder,
												'Quote_Stage'        		=> "Closed as Sale Won",
												'Converted_by_Customer'     => $Converted_by_Customer
											 );

					$update_quote_zohocrm = update_quote_zohocrm($ZohoQuoteId,$UpdateQuoteParam);

				}

			}

		}

	}

}
add_action( 'woocommerce_thankyou', 'action_woocommerce_thankyou', 10, 1 );

/* If contact match then return zoho contact ID otherwise create an new contact into zoho crm  */

function zoho_contact_recoard_id($order_data,$orderID){

	global $woocommerce,$wpdb;

	$billing_first_name = $order_data['billing']['first_name'];
	$billing_last_name  = $order_data['billing']['last_name'];
	$billing_company    = $order_data['billing']['company'];
	$billing_address_1  = $order_data['billing']['address_1'];
	$billing_address_2  = $order_data['billing']['address_2'];
	$billing_city 	    = $order_data['billing']['city'];
	$billing_state      = $order_data['billing']['state'];
	$billing_postcode   = $order_data['billing']['postcode'];
	$billing_country    = $order_data['billing']['country'];
	$billing_email      = $order_data['billing']['email'];
	$billing_phone      = $order_data['billing']['phone'];

	$address = $billing_address_1;

	if(!empty($billing_address_2)){

		$address .= ', '.$billing_address_2;
	}

	$countryName = WC()->countries->countries[ $billing_country  ];

	$active_access_token = 1;
	if(strtotime(current_time('mysql')) > get_option('wc_zoho_access_token_expires_on'))
	{
		$active_access_token = get_refresh_token();
	}

	$zoho_contact_id = '';

	$zohoParam = array();

	$zohoParam['First_Name']        = $billing_first_name;
	$zohoParam['Last_Name']         = $billing_last_name;
	$zohoParam['Phone'] 	      	= $billing_phone;
	$zohoParam['Mailing_Street'] 	= $address;
	$zohoParam['Mailing_State'] 	= $billing_state;
	$zohoParam['Mailing_City'] 	    = $billing_city;
	$zohoParam['Mailing_Country'] 	= $countryName;
	$zohoParam['Mailing_Zip'] 	    = $billing_postcode;
	$zohoParam['Customer_User_Agent'] 	    = get_post_meta($orderID,'_customer_user_agent',true);
	$zohoParam['Customer_IP_Address'] 	    = get_post_meta($orderID,'_customer_ip_address',true);
	$zohoParam['Referrer'] 	    			= site_url();

	// Vehicle info
	if(!empty(get_post_meta($orderID,'_fbf_order_data_manufacturers',true))){
		$zohoParam['Vehicle_Make'] = get_post_meta($orderID,'_fbf_order_data_manufacturers',true);
	}
	if(!empty(get_post_meta($orderID,'_fbf_order_data_vehicles',true))){
		$zohoParam['Vehicle_Model'] = get_post_meta($orderID,'_fbf_order_data_vehicles',true);
	}

	// Tyre info
	if(!empty(get_post_meta($orderID,'_fbf_order_data_tyre_sizes',true))){
		$zohoParam['Current_Tyre_Size'] = get_post_meta($orderID,'_fbf_order_data_tyre_sizes',true);
	}
	if(!empty(get_post_meta($orderID,'_fbf_order_data_tyre_types',true))){
		$zohoParam['All_or_Mud_Terrain'] = get_post_meta($orderID,'_fbf_order_data_tyre_types',true);
	}
	if(!empty(get_post_meta($orderID,'_fbf_order_data_tyre_brands',true))){
		$zohoParam['Tyre_Brand'] = get_post_meta($orderID,'_fbf_order_data_tyre_brands',true);
	}
	if(!empty(get_post_meta($orderID,'_fbf_order_data_tyre_names',true))){
		$zohoParam['Tyre_Name'] = get_post_meta($orderID,'_fbf_order_data_tyre_names',true);
	}

	// Wheel info
	if(!empty(get_post_meta($orderID,'_fbf_order_data_wheel_sizes',true))){
		$zohoParam['Current_Wheel_Size'] = get_post_meta($orderID,'_fbf_order_data_wheel_sizes',true);
	}
	if(!empty(get_post_meta($orderID,'_fbf_order_data_wheel_types',true))){
		$zohoParam['Steel_or_Alloy'] = get_post_meta($orderID,'_fbf_order_data_wheel_types',true);
	}
	if(!empty(get_post_meta($orderID,'_fbf_order_data_wheel_brands',true))){
		$zohoParam['Wheel_Brand'] = get_post_meta($orderID,'_fbf_order_data_wheel_brands',true);
	}
	if(!empty(get_post_meta($orderID,'_fbf_order_data_wheel_names',true))){
		$zohoParam['Wheel_Name'] = get_post_meta($orderID,'_fbf_order_data_wheel_names',true);
	}

    // Marketing signup
    $zohoParam['Marketing_Signup'] = get_post_meta($orderID, '_marketing_signup', true)==='1';

	$ContactCreatedBy = "";

	if(is_user_logged_in()){

		if(current_user_switched()){

			$current_user_switched = current_user_switched();

			$ContactCreatedBy = $current_user_switched->ID;
		}
	}

	if($active_access_token === 1){

		$ContactExists = search_zoho_contact("Contacts",$billing_email,'GET');

		if($ContactExists['status'] == 1 && empty($ContactExists['body'])){

			/* Create new contact into zoho */

			$zohoParam['Email'] 	 		    = $billing_email;
			$zohoParam['Contact_Created_By']	= $ContactCreatedBy;
			$zohoParam['From_Website']			= true;
			$ZOHOContactdata         = array();
			$ZOHOContactdata[]       = $zohoParam;
			$paramsContact      	 = array('data'=>$ZOHOContactdata);
			$ZohoInsertContactData   = request_zoho_data('Contacts', $paramsContact,'POST');
			if($ZohoInsertContactData['body']['data'][0]['status'] == 'success'){
				$zoho_contact_id 	 =  $ZohoInsertContactData['body']['data'][0]['details']['id'];
			}

		}elseif($ContactExists['status'] == 1 && !empty($ContactExists['body'])){

			$zoho_contact_id 	= $ContactExists['body']['data'][0]['id'];

			/* Update details if changed */

			$ZOHOContactdata         = array();
			$ZOHOContactdata[]       = $zohoParam;
			$paramsContact      	 = array('data'=>$ZOHOContactdata);
			$ZohoUpdateContactData   = update_zoho_data('Contacts', $paramsContact, $zoho_contact_id , 'PUT');
		}

	}

	return $zoho_contact_id;
}

/* product insert and update in zoho */
function zoho_product_update($productDetails){


	global $wpdb;

	$zoho_product_id = '';

	$active_access_token = 1;
	if(strtotime(current_time('mysql')) > get_option('wc_zoho_access_token_expires_on'))
	{
		$active_access_token = get_refresh_token();
	}

	$zohoParam = array();

	$tax = array('Vat - 20.0 %');

	$zohoParam['Product_Name']        = $productDetails['pname'];
	$zohoParam['Product_Code']        = $productDetails['product_code'];
	$zohoParam['Product_Active'] 	  = true;
	$zohoParam['Product_Category'] 	  = $productDetails['cname'];

	if($productDetails['tax_status'] == 'taxable'){
		$zohoParam['Tax'] 				= $tax;
	}

	$zohoParam['Qty_in_Stock'] 	      = $productDetails['stock'];
	$zohoParam['Unit_Price'] 		  = number_format($productDetails['unit_price'],2);
	$zohoParam['Product_Weight_Kg']   = $productDetails['weight'];
	$zohoParam['Product_Length_cm']   = $productDetails['length'];


	foreach ( $productDetails['attributes'] as $attribute ) {

		$attributeDetails = wc_get_product_terms($productDetails['product_id'], $attribute['name']);

		$attribute_name = '';

		foreach ($attributeDetails as $attributecdcd) {

			$attribute_name .= $attributecdcd->name.', ';
		}

		$newAttrName = rtrim($attribute_name,', ');


		if($attribute['name'] == 'pa_tyre-load'){

			$zohoParam['Tyre_Load']   = $newAttrName;
		}

		if($attribute['name'] == 'pa_tyre-speed'){

			$zohoParam['Tyre_Speed']   = $newAttrName;
		}

		if($attribute['name'] == 'pa_load-speed-rating'){

			$zohoParam['Load_Speed_Rating']   = $newAttrName;
		}

		if($attribute['name'] == 'pa_brand-name'){

			$zohoParam['Product_Brand']   = $newAttrName;
		}

		if($attribute['name'] == 'pa_model-name'){

			$zohoParam['Model_Name']   = $newAttrName;
		}

		if($attribute['name'] == 'pa_tyre-type'){

			$zohoParam['Tyre_Type']   = $newAttrName;
		}

		if($attribute['name'] == 'pa_tyre-width'){

			$zohoParam['Tyre_Width']   = $newAttrName;
		}

		if($attribute['name'] == 'pa_tyre-size-label'){

			$zohoParam['Tyre_Size_Label']   = $newAttrName;
		}

		if($attribute['name'] == 'pa_tyre-size'){

			$zohoParam['Tyre_Size']   = $newAttrName;
		}

		if($attribute['name'] == 'pa_tyre-profile'){

			$zohoParam['Tyre_Profile']   = $newAttrName;
		}

		if($attribute['name'] == 'pa_list-on-ebay'){

			$zohoParam['List_on_eBay']   = $newAttrName;
		}

		if($attribute['name'] == 'pa_tyre-xl'){

			$zohoParam['Tyre_XL']   = $newAttrName;
		}

		if($attribute['name'] == 'pa_tyre-white-lettering'){

			$zohoParam['Tyre_White_Lettering']   = $newAttrName;
		}

		if($attribute['name'] == 'pa_tyre-runflat'){

			$zohoParam['Tyre_Runflat']   = $newAttrName;
		}

		if($attribute['name'] == 'pa_ec-label-fuel'){

			$zohoParam['EC_Label_Fuel']   = $newAttrName;
		}

		if($attribute['name'] == 'pa_ec-label-wet-grip'){

			$zohoParam['EC_Label_Wet_Grip']   = $newAttrName;
		}

		if($attribute['name'] == 'pa_tyre-label-noise'){

			$zohoParam['Tyre_Label_Noise']   = $newAttrName;
		}

		if($attribute['name'] == 'pa_wheel-width'){

			$zohoParam['Wheel_Width']   = $newAttrName;
		}

		if($attribute['name'] == 'pa_wheel-tuv'){

			$zohoParam['Wheel_TUV']   = $newAttrName;
		}

		if($attribute['name'] == 'pa_wheel-size'){

			$zohoParam['Wheel_Size']   = $newAttrName;
		}

		if($attribute['name'] == 'pa_wheel-pcd'){

			$zohoParam['Wheel_PCD']   = $newAttrName;
		}

		if($attribute['name'] == 'pa_wheel-offset'){

			$zohoParam['Wheel_Offset']   = $newAttrName;
		}

		if($attribute['name'] == 'pa_wheel-colour'){

			$zohoParam['Wheel_Colour']   = $newAttrName;
		}

		if($attribute['name'] == 'pa_wheel-load-rating'){

			$zohoParam['Wheel_Load_Rating']   = $newAttrName;
		}

		if($attribute['name'] == 'pa_centre-bore'){

			$zohoParam['Centre_Bore']   = $newAttrName;
		}

		if($attribute['name'] == 'pa_tyre-vehicle-specific'){

			$zohoParam['Tyre_Vehicle_Specific']   = $newAttrName;
		}

	}

	if($active_access_token === 1){

		$query = "((Product_Code:equals:".$productDetails['product_code']."))";

		$SearchProduct  = search_zoho_recoard('Products', $query, 'GET');

		if($SearchProduct['status'] == 1 && empty($SearchProduct['body'])){

			/* Insert new product */

			$ZOHOProductdata         = array();
			$ZOHOProductdata[]       = $zohoParam;
			$paramsProduct      	 = array('data'=>$ZOHOProductdata);
			$ZohoInsertProductData   = request_zoho_data('Products', $paramsProduct,'POST');
			if($ZohoInsertProductData['body']['data'][0]['status'] == 'success'){
				$zoho_product_id 	 =  $ZohoInsertProductData['body']['data'][0]['details']['id'];
			}

		}elseif($SearchProduct['status'] == 1 && !empty($SearchProduct['body'])){

			/* Update new product */

			$zoho_product_id 	= $SearchProduct['body']['data'][0]['id'];

			$ZOHOProductdata         = array();
			$ZOHOProductdata[]       = $zohoParam;
			$paramsProduct      	 = array('data'=>$ZOHOProductdata);
			$ZohoUpdateProductData   = update_zoho_data('Products', $paramsProduct, $zoho_product_id , 'PUT');
		}
	}

	return $zoho_product_id;
}

/* create sale order */

function CreateSaleOrder($saleOrderParam){

	$active_access_token = 1;
	if(strtotime(current_time('mysql')) > get_option('wc_zoho_access_token_expires_on'))
	{
		$active_access_token = get_refresh_token();
	}

	$zohoParam = array();

	$zoho_saleOrder_id = '';

	if($active_access_token === 1){

		$ZOHOSaleOrderdata         = array();
		$ZOHOSaleOrderdata[]       = $saleOrderParam;
		$paramsSaleOrder      	  = array('data'=>$ZOHOSaleOrderdata);
		$ZohoCreateSaleOrder      = request_zoho_data('Sales_Orders', $paramsSaleOrder,'POST');

		if($ZohoCreateSaleOrder['status'] == 1){

			if($ZohoCreateSaleOrder['body']['data'][0]['status'] == 'success'){

				$zoho_saleOrder_id 	 =  $ZohoCreateSaleOrder['body']['data'][0]['details']['id'];
			}

		}
	}

	return $zoho_saleOrder_id;

}

/* Create Quote from wp to zoho crm */

function CreateQuoteZOHOCRM($QuoteParam){

	$active_access_token = 1;
	if(strtotime(current_time('mysql')) > get_option('wc_zoho_access_token_expires_on'))
	{
		$active_access_token = get_refresh_token();
	}

	$zohoParam = array();

	$zoho_Quote_id = '';

	if($active_access_token === 1){

		$ZOHOOrderdata         = array();
		$ZOHOOrderdata[]       = $QuoteParam;
		$paramsOrder       	   = array('data'=>$ZOHOOrderdata);
		$ZohoCreateQuotes      = request_zoho_data('Quotes', $paramsOrder,'POST');

		if($ZohoCreateQuotes['status'] == 1){

			if($ZohoCreateQuotes['body']['data'][0]['status'] == 'success'){

				$zoho_Quote_id 	 =  $ZohoCreateQuotes['body']['data'][0]['details']['id'];
			}

		}
	}

	return $zoho_Quote_id;

}

/* Update quote param after convert quote into sale order */

function update_quote_zohocrm($quoteID,$QuoteParam){


	$active_access_token = 1;
	if(strtotime(current_time('mysql')) > get_option('wc_zoho_access_token_expires_on'))
	{
		$active_access_token = get_refresh_token();
	}

	$zohoParam = array();

	$zoho_Quote_id = '';

	if($active_access_token === 1){

		$ZOHOOrderdata         = array();
		$ZOHOOrderdata[]       = $QuoteParam;
		$paramsOrder       	   = array('data'=>$ZOHOOrderdata);
		$ZohoUpdateQuotes      = update_zoho_data('Quotes', $paramsOrder, $quoteID , 'PUT');


	}

}

if (!function_exists('get_post_id_by_meta_key_and_value')) {

 function get_post_id_by_meta_key_and_value($key, $value) {
   global $wpdb;
   $meta = $wpdb->get_results("SELECT * FROM `".$wpdb->postmeta."` WHERE meta_key='".$wpdb->escape($key)."' AND meta_value='".$wpdb->escape($value)."'");
   if (is_array($meta) && !empty($meta) && isset($meta[0])) {
      $meta = $meta[0];
      }
   if (is_object($meta)) {
      return $meta->post_id;
      }
   else {
      return false;
      }
   }
}

/*ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);*/
?>
