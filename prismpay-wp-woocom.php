<?php
/*
Plugin Name: PrismPay Payments For WooCommerce
Description: Extends WooCommerce to Process Payments with PrismPay gateway
Version: 1.0
Plugin URI: http://www.prismpaytech.com/
Author: M. Adeel Qureshi
Author URI: http://www.instantaccept.com
License: Under GPL
*/

add_action('plugins_loaded', 'woocommerce_prismpay_init', 0);

function woocommerce_prismpay_init() {

   if ( !class_exists( 'WC_Payment_Gateway' ) ) 
      return;

   /**
   * Localisation
   */
   load_plugin_textdomain('wc-tech-prismpay', false, dirname( plugin_basename( __FILE__ ) ) . '/languages');
   
   /**
   * PrismPay Payment Gateway class
   */
   class WC_Gateway_PrismPay extends WC_Payment_Gateway 
   {
      protected $msg = array();
      
      public function __construct(){
		
		//$this->id					= 		'authorizeaim';
		$this->id					= 		'prismpay';
		$this->method_title			= 		__('PrismPay', 'wc-tech-prismpay');
		$this->icon					= 		WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/images/logo.gif';
		$this->has_fields       	= 		true;
		
		$this->init_form_fields();
		$this->init_settings();
		
		$this->title				= 		$this->settings['title'];
		$this->description			= 		$this->settings['description'];
		$this->account_id			= 		$this->settings['account_id'];
		$this->sub_account_id		= 		$this->settings['sub_account_id'];
		$this->merchant_pin			= 		$this->settings['merchant_pin'];
		$this->des_encryption		= 		$this->settings['des_encryption'];
		$this->soap_reporting_key	= 		$this->settings['soap_reporting_key'];
		$this->mode					= 		$this->settings['working_mode'];
		$this->transaction_key		= 		$this->settings['transaction_key'];
		$this->success_message		= 		$this->settings['success_message'];
		$this->failed_message		= 		$this->settings['failed_message'];
		$this->liveurl				= 		'https://trans.myprismpay.com/MPWeb/services/TransactionService?wsdl';
		$this->msg['message']		= 		"";
		$this->msg['class']			= 		"";
        
         
         
         if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
             add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
          } else {
             add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
         }

         add_action('woocommerce_receipt_authorizeaim', array(&$this, 'receipt_page'));
         add_action('woocommerce_thankyou_authorizeaim',array(&$this, 'thankyou_page'));
      }

      function init_form_fields()
      {

         $this->form_fields = array(
            'enabled'      => array(
                  'title'        => __('Enable/Disable', 'wc-tech-prismpay'),
                  'type'         => 'checkbox',
                  'label'        => __('Enable PrismPay Payment Module.', 'wc-tech-prismpay'),
                  'default'      => 'no'),
            'title'        => array(
                  'title'        => __('Title:', 'wc-tech-prismpay'),
                  'type'         => 'text',
                  'description'  => __('This controls the title which the user sees during checkout.', 'wc-tech-prismpay'),
                  'default'      => __('PrismPay', 'wc-tech-prismpay')),
            'description'  => array(
                  'title'        => __('Description:', 'wc-tech-prismpay'),
                  'type'         => 'textarea',
                  'description'  => __('This controls the description which the user sees during checkout.', 'wc-tech-prismpay'),
                  'default'      => __('Pay securely by Credit / Debit Card or e-checks through PrismPay.', 'wc-tech-prismpay')),
            'account_id'     => array(
                  'title'        => __('Account ID', 'wc-tech-prismpay'),
                  'type'         => 'text',
                  'description'  => __('PrismPay Account ID')),
			'sub_account_id'     => array(
                  'title'        => __('Sub Account ID', 'wc-tech-prismpay'),
                  'type'         => 'text',
                  'description'  => __('PrismPay Sub-Account ID')),
			'merchant_pin'     => array(
                  'title'        => __('Merchant PIN', 'wc-tech-prismpay'),
                  'type'         => 'text',
                  'description'  => __('PrismPay Merchant PIN')),
			'des_encryption'     => array(
                  'title'        => __('Account Number 3DES Encryption', 'wc-tech-prismpay'),
                  'type'         => 'text',
                  'description'  => __('PrismPay Account Number 3DES Encryption')),
			'soap_reporting_key'     => array(
                  'title'        => __('SOAP Reporting Service Account Key', 'wc-tech-prismpay'),
                  'type'         => 'text',
                  'description'  => __('PrismPay SOAP Reporting Service Account Key')),
            'success_message' => array(
                  'title'        => __('Transaction Success Message', 'wc-tech-prismpay'),
                  'type'         => 'textarea',
                  'description'=>  __('Message to be displayed on successful transaction.', 'wc-tech-prismpay'),
                  'default'      => __('Your payment has been procssed successfully.', 'wc-tech-prismpay')),
            'failed_message'  => array(
                  'title'        => __('Transaction Failed Message', 'wc-tech-prismpay'),
                  'type'         => 'textarea',
                  'description'  =>  __('Message to be displayed on failed transaction.', 'wc-tech-prismpay'),
                  'default'      => __('Your transaction has been declined.', 'wc-tech-prismpay')),
            'working_mode'    => array(
                  'title'        => __('API Mode'),
                  'type'         => 'select',
            	  'options'      => array('false'=>'Live Mode', 'true'=>'Test/Sandbox Mode'),
                  'description'  => "Live/Test Mode" )
         );
      }
      
      /**
       * Admin Panel Options
       * 
      **/
      public function admin_options()
      {
         echo '<h3>'.__('PrismPay Payment Gateway', 'wc-tech-prismpay').'</h3>';
         echo '<p>'.__('PrismPay is most popular payment gateway for online payment processing').'</p>';
         echo '<table class="form-table">';
         $this->generate_settings_html();
         echo '</table>';

      }
      
      /**
      *  Fields for Authorize.net AIM
      **/
      function payment_fields()
      {
         if ( $this->description ) 
            echo wpautop(wptexturize($this->description));
			
			$years = "<option value=''>YY</option>";
			for($a = date('Y'); $a <= date('Y')+10; $a++)
			{
				$years .= "<option value='". $a ."'>". $a ."</option>";
			}
			
			$UserID 		= 	get_current_user_id();
			$profiles 		= 	get_option('_wp_prismpay_user_payment_profile_' . $UserID);
			?>
				<script type="text/javascript">
					function PP_Change_Payment_Method(val)
					{
						if(val == 1)
						{
							document.getElementById('CreditCardMethod_PP').style.display = 'block';
							document.getElementById('E_Check_Method_PP').style.display = 'none';
						} else
						{
							document.getElementById('CreditCardMethod_PP').style.display = 'none';
							document.getElementById('E_Check_Method_PP').style.display = 'block';
						}
					}
					
					function PP_Change_Profile_Payment(check)
					{
						if(check == "" || check.length <= 0)
						{
							document.getElementById('CreditCardMethod2_PP').style.display = 'block';
						} else
						{
							document.getElementById('CreditCardMethod2_PP').style.display = 'none';
						}
					}
				</script>
				<table border="0">
					<tr>
						<td width="150">Payment Method: </td>
						<td>
							<select id="pp_payment_method" name="pp_payment_method" onchange="javascript:PP_Change_Payment_Method(this.value);">
								<option value="1">Credit Card</option>
								<option value="2">E-Check</option>
							</select>
						</td>
					</tr>
				</table>
				
				
				<div id="CreditCardMethod_PP">
				
					<?php if(is_array($profiles) && sizeof($profiles) > 0){ ?>
					<table border="0">
						<tr>
							<td width="150">Saved Profiles: </td>
							<td>
								<select id="pp_payment_profiles" name="pp_payment_profiles" onchange="javascript:PP_Change_Profile_Payment(this.value);">
									<option value="">Select Profile</option>
									<?php foreach($profiles as $profile){ ?>
									<option value="<?php echo $profile['userprofileid']; ?>||<?php echo $profile['last4digits']; ?>"><?php echo $profile['last4digits']; ?> - <?php echo $profile['paytype']; ?></option>
									<?php } ?>
								</select>
							</td>
						</tr>
					</table>
					<?php } ?>
					
					<div id="CreditCardMethod2_PP">
					<table border="0">
						<tr>
							<td width="150">Credit Card: </td>
							<td><input type="text" name="pp_credircard" maxlength="16" /></td>
						</tr>
						<tr>
							<td>Expiry: </td>
							<td>
								<select name="pp_mm">
									<option value="">MM</option>
									<option value="01">Jan</option>
									<option value="02">Feb</option>
									<option value="03">Mar</option>
									<option value="04">Apr</option>
									<option value="05">May</option>
									<option value="06">Jun</option>
									<option value="07">Jul</option>
									<option value="08">Aug</option>
									<option value="09">Sep</option>
									<option value="10">Oct</option>
									<option value="11">Nov</option>
									<option value="12">Dec</option>
								</select> &nbsp;&nbsp;
								<select name="pp_yy">
									<?php echo $years; ?>
								</select><br />
							</td>
						</tr>
						<tr>
							<td>CVV: </td>
							<td><input type="text" name="pp_cvv"  maxlength="4" style="width:40px;" /></td>
						</tr>
						<?php if(isset($UserID) && $UserID > 0){ ?>
						<tr>
							<td>Save my card : </td>
							<td><input type="checkbox" id="pp_save_card" name="pp_save_card" value="1" /></td>
						</tr>
						<?php } ?>
					</table>
					</div>
					
				</div>
				
				<div id="E_Check_Method_PP" style="display:none">
					<table border="0">
						<tr>
							<td width="150">First/Last Name:</td>
							<td><input type='text' name='pp_f_name' id='f_name' value='' />/ &nbsp;<input type='text' name='pp_l_name' id='l_name' value='' /></td>
						</tr>
						<tr>
							<td>Account Type:</td>
							<td>
								<select name='pp_check_acc_type' id='pp_check_acc_type'>
									<option value='1'>Checking</option>
									<option value='2'>Savings</option>
								</select>
							</td>
						</tr>
						<tr>
							<td>Account Number:</td>
							<td><input type='text' name='pp_check_acc_number' id='pp_check_acc_number' value='' size='30' /></td>
						</tr>
						<tr>
							<td>Check Number:</td>
							<td><input type='text' name='pp_check_number' id='pp_check_number' value='' size='30' /></td>
						</tr>
						<tr>
							<td>Routing Number:</td>
							<td><input type='text' name='pp_check_routing_number' id='pp_check_routing_number' value='' size='30' /></td>
						</tr>
						<tr>
							<td valign="top">Example:</td>
							<td><img src='<?php echo WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) ?>/images/check.gif' border='0' /></td>
						</tr>
					</table>
				</div>
			
			<?php
      }
      
      /*
      * Basic Card validation
      */
      public function validate_fields()
      {
           global $woocommerce;
			
			if(isset($_POST['pp_payment_method']) && $_POST['pp_payment_method'] == 1)
			{
				if( $_POST['pp_payment_profiles'] == "" )
				{
					
					if (!$this->isCreditCardNumber($_POST['pp_credircard'])) 
					{
						$woocommerce->add_error(__('(Credit Card Number) is not valid.', 'wc-tech-prismpay')); 
					}
					if (!$this->isCorrectExpireMonth($_POST['pp_mm']))
					{
						$woocommerce->add_error(__('(Card Expiry Month) is not valid.', 'wc-tech-prismpay')); 
					}
					if (!$this->isCorrectExpireYear($_POST['pp_yy']))    
					{
						$woocommerce->add_error(__('(Card Expiry Year) is not valid.', 'wc-tech-prismpay')); 
					}
					if (!$this->isCCVNumber($_POST['pp_cvv']))
					{
						$woocommerce->add_error(__('(Card Verification Number) is not valid.', 'wc-tech-prismpay')); 
					}
					
				}
				
			} else
			{
				if(!isset($_POST['pp_f_name']) || empty($_POST['pp_f_name']))
				{
					$woocommerce->add_error(__('(First Name) required.', 'wc-tech-prismpay')); 
				}
				if(!isset($_POST['pp_l_name']) || empty($_POST['pp_l_name']))
				{
					$woocommerce->add_error(__('(Last Name) required.', 'wc-tech-prismpay')); 
				}
				if(!isset($_POST['pp_check_acc_number']) || empty($_POST['pp_check_acc_number']))
				{
					$woocommerce->add_error(__('(Account Number) required.', 'wc-tech-prismpay')); 
				}
				if(!isset($_POST['pp_check_number']) || empty($_POST['pp_check_number']))
				{
					$woocommerce->add_error(__('(Check Number) required.', 'wc-tech-prismpay')); 
				}
				if(!isset($_POST['pp_check_routing_number']) || empty($_POST['pp_check_routing_number']))
				{
					$woocommerce->add_error(__('(Routing Number) required.', 'wc-tech-prismpay')); 
				}
			}
      }
      
      /*
      * Check card 
      */
      private function isCreditCardNumber($toCheck) 
      {
         if (!is_numeric($toCheck))
            return false;
        
        $number = preg_replace('/[^0-9]+/', '', $toCheck);
        $strlen = strlen($number);
        $sum    = 0;

        if ($strlen < 13)
            return false; 
            
        for ($i=0; $i < $strlen; $i++)
        {
            $digit = substr($number, $strlen - $i - 1, 1);
            if($i % 2 == 1)
            {
                $sub_total = $digit * 2;
                if($sub_total > 9)
                {
                    $sub_total = 1 + ($sub_total - 10);
                }
            } 
            else 
            {
                $sub_total = $digit;
            }
            $sum += $sub_total;
        }
        
        if ($sum > 0 AND $sum % 10 == 0)
            return true; 

        return false;
      }
        
      private function isCCVNumber($toCheck) 
      {
         $length = strlen($toCheck);
         return is_numeric($toCheck) AND $length > 2 AND $length < 5;
      }
    
      /*
      * Check expiry date
      */
      private function isCorrectExpireMonth($mm) 
      {
          
         if ( is_numeric($mm) && !empty($mm) ){
            return true;
         }
         return false;
      }
	  private function isCorrectExpireYear($yy) 
      {
          
        if ( is_numeric($yy) && !empty($yy) ){
            return true;
         }
         return false;
      }
      
      public function thankyou_page($order_id) 
      {
      
       
      }
      
      /**
      * Receipt Page
      **/
      function receipt_page($order)
      {
         echo '<p>'.__('Thank you for your order.', 'wc-tech-prismpay').'</p>';
        
      }
      
      /**
       * Process the payment and return the result
      **/
      function process_payment($order_id)
      {
         global $woocommerce;
         $order = new WC_Order($order_id);

         $process_url = $this->liveurl;
         
		if(isset($_POST['pp_payment_method']) && $_POST['pp_payment_method'] == 1)
		{
			// Credit Card Processing ....
			$params = $this->generate_prismpay_params_cc($order);
			
			if( isset($_POST['pp_payment_profiles']) && !empty($_POST['pp_payment_profiles']) && $_POST['pp_payment_profiles'] != "" )
			{
				// Payments/Transactions with saved profiles ....
				$params 		= 	"";
				$params 		= 	$this->generate_prismpay_params_pf($order);
				$soapClient     = 	new SoapClient($process_url, array('trace' => 1));
				$response 		= 	$soapClient->__soapCall("processProfileSale", array('ProfileSale' => $params));
			} else
			if(isset($_POST['pp_save_card']) && $_POST['pp_save_card'] == 1)
			{
				// Save Card Profile / User Profile ....
				$soapClient     = 	new SoapClient($process_url, array('trace' => 1));
				$response 		= 	$soapClient->__soapCall("processCCProfileAdd", array('CreditCardInfo' => $params));
				$UserID 		= 	get_current_user_id();
				if($response->status == 'Approved' )
				{
					$option = "_wp_prismpay_user_payment_profile_" . $UserID;
					$optionValue = array(
										array(
											"userprofileid" => $response->userprofileid,
											"last4digits" => $response->last4digits,
											"paytype" => $response->paytype
										)
									);
									
					$UserID 		= 	get_current_user_id();
					$profiles 		= 	get_option('_wp_prismpay_user_payment_profile_' . $UserID);
					if(sizeof($profiles) > 0)
					{
						$optionVal = array_merge($profiles, $optionValue);
					} else
					{
						$optionVal = $optionValue;
					}
					update_option( $option, $optionVal );
					
				}
				
			} else
			{
				$soapClient     = 	new SoapClient($process_url, array('trace' => 1));
				$response 		= 	$soapClient->__soapCall("processCCSale", array('CreditCardInfo' => $params)); 
			}
			
		} else
		{
			// E-Check Processing ....
			$params = $this->generate_prismpay_params_ach($order);
			
			$soapClient     = 	new SoapClient($process_url, array('trace' => 1));
			$response 		= 	$soapClient->__soapCall("processACHSale", array('ACHInfo' => $params)); 
		}
		
		 
		//echo "<pre>";print_r($response);
		//exit();
      
         if ( count($response) >= 1 )
		 {
         
            if($response->status == 'Approved' )
			{

				$order->payment_complete();
				$woocommerce->cart->empty_cart();
				
				$order->add_order_note($this->success_message. ". " . $response->result . 'Transaction ID: '. $order->orderid );
				unset($_SESSION['order_awaiting_payment']);
				
                  return array(
				  			'result'   => 'success',
							'redirect' => $this->get_return_url( $order )
						);
            }
            else{
            
                $order->add_order_note($this->failed_message . ". " . $response->result );
                $woocommerce->add_error(__('(Transaction Error) '. $response->result, 'wc-tech-prismpay'));
            }
        }
        else {
            
            $order->add_order_note($this->failed_message);
            $order->update_status('failed');
            
            $woocommerce->add_error(__('(Transaction Error) Error processing payment.', 'wc-tech-prismpay')); 
        }
         
         
         
      }
      
    /* PrismPay Parameters for CreditCard Payment .... */
	public function generate_prismpay_params_cc($order)
	{
		if($this->mode == 'true'){
			$account_id = "py7l4";
		}
		else{
			$account_id = $this->account_id;
		}
		
		$prismpay_params = array(
					'acctid' 	=> 		$account_id,
					'amount' 	=> 		$order->order_total,
					'ccnum' 	=> 		$_POST['pp_credircard'],
					'expmon' 	=> 		$_POST['pp_mm'],
					'expyear' 	=> 		$_POST['pp_yy'],
					'ccname' 	=> 		$order->billing_first_name . " " . $order->billing_last_name,
					'cvv2' 		=>  	$_POST['pp_cvv'],
					'billaddress' => array(
										'addr1' => $order->billing_address_1,
										'addr2' => $order->billing_address_2,
										'addr3' => "",
										'city' => $order->billing_city,
										'state' => $order->billing_state,
										'zip' => $order->billing_postcode,
										'country' => $order->billing_country
									),
					'shipaddress' => array(
										'addr1' => $order->shipping_address_1,
										'addr2' => $order->shipping_address_2,
										'addr3' => "",
										'city' => $order->shipping_city,
										'state' => $order->shipping_state,
										'zip' => $order->shipping_postcode,
										'country' => $order->shipping_country
									),
					"customizedfields" => array(
										'ip' => $_SERVER['REMOTE_ADDR']
									),
					"encryptedreadertype" => "0",
					"cardpresent" => "0",
					"cardreaderpresent" => "0",
					"accttype" => "0",
					"profileactiontype" => "0",
					"manualrecurring" => "0",
					"avs_override" => "0",
					"cvv2_override" => "0",
					"loadbalance_override" => "0",
					"duplicate_override" => "0",
					"accountlookupflag" => "0",
					"accountlookupflag" => "0",
					"conveniencefeeflag" => "0",
					"contactlessflag" => "0"
				);
		 return $prismpay_params;
	  }
	  
	  /* PrismPay Parameters for Profile Sale .... */
	public function generate_prismpay_params_pf($order)
	{
		if($this->mode == 'true'){
			$account_id = "py7l4";
		}
		else{
			$account_id = $this->account_id;
		}
		
		$UserProfiles 	= 	$_POST['pp_payment_profiles'];
		$UserProfiles 	= 	explode("||",$UserProfiles);
		$ProfileID 		= 	$UserProfiles[0];
		$Last4	 		= 	$UserProfiles[1];		
		
		$prismpay_params = array(
					'acctid' 			=> 		$account_id,
					'amount' 			=> 		$order->order_total,
					'userprofileid' 	=> 		$ProfileID,
					'last4digits' 		=> 		$Last4,
					'billaddress' => array(
										'addr1' => $order->billing_address_1,
										'addr2' => $order->billing_address_2,
										'addr3' => "",
										'city' => $order->billing_city,
										'state' => $order->billing_state,
										'zip' => $order->billing_postcode,
										'country' => $order->billing_country
									),
					'shipaddress' => array(
										'addr1' => $order->shipping_address_1,
										'addr2' => $order->shipping_address_2,
										'addr3' => "",
										'city' => $order->shipping_city,
										'state' => $order->shipping_state,
										'zip' => $order->shipping_postcode,
										'country' => $order->shipping_country
									),
					"customizedfields" => array(
										'ip' => $_SERVER['REMOTE_ADDR']
									),
					"cvv2" => "0",
					"authonly" => "0",
					"encryptedreadertype" => "0",
					"cardpresent" => "0",
					"cardreaderpresent" => "0",
					"accttype" => "0",
					"profileactiontype" => "0",
					"manualrecurring" => "0",
					"avs_override" => "0",
					"cvv2_override" => "0",
					"loadbalance_override" => "0",
					"duplicate_override" => "0",
					"accountlookupflag" => "0",
					"accountlookupflag" => "0",
					"conveniencefeeflag" => "0"
				);
		 return $prismpay_params;
	  }
	  
	/* PrismPay Parameters for ACH Payments .... */
	public function generate_prismpay_params_ach($order)
	{
		if($this->mode == 'true'){
			$account_id = "py7l4";
		}
		else{
			$account_id = $this->account_id;
		}
		
		$prismpay_params = array(
						'acctid' 				=> 	$account_id,
						'subid' 				=> 	$this->sub_account_id,
						'accountkey' 			=> 	$this->soap_reporting_key,
						'amount' 				=> 	$order->order_total,
						'ckname' 				=> 	$_POST['pp_f_name'] ." ". $_POST['pp_l_name'],
						'ckaba' 				=> 	$_POST['pp_check_routing_number'],
						'ckacct' 				=> 	$_POST['pp_check_acc_number'],
						'ckno' 					=> 	$_POST['pp_check_number'],
						'cktype' 				=> 	"WEB",
						'merchantpin' 			=> 	$this->merchant_pin,
						'ckaccttype' 			=>  $_POST['pp_check_acc_type'],
						'billaddress' 			=> 	array(
														'addr1' => $order->billing_address_1,
														'addr2' => $order->billing_address_2,
														'addr3' => "",
														'city' => $order->billing_city,
														'state' => $order->billing_state,
														'zip' => $order->billing_postcode,
														'country' => $order->billing_country
													),
						'shipaddress' 			=> 	array(
														'addr1' => $order->shipping_address_1,
														'addr2' => $order->shipping_address_2,
														'addr3' => "",
														'city' => $order->shipping_city,
														'state' => $order->shipping_state,
														'zip' => $order->shipping_postcode,
														'country' => $order->shipping_country
													),
						"customizedfields" 		=> 	array(
														'ip' => $_SERVER['REMOTE_ADDR']
													),
						"encryptedreadertype" 	=> 	"0",
						"cardpresent" 			=> 	"0",
						"cardreaderpresent" 	=> 	"0",
						"cvv2" 					=> 	"0",
						"accttype" 				=> 	"0",
						"profileactiontype" 	=> 	"0",
						"manualrecurring" 		=> 	"0",
						"avs_override" 			=> 	"0",
						"cvv2_override" 		=> 	"0",
						"loadbalance_override" 	=> 	"0",
						"duplicate_override" 	=> 	"0",
						"accountlookupflag" 	=> 	"0",
						"accountlookupflag" 	=> 	"0",
						"conveniencefeeflag" 	=> 	"0"
					);
		 return $prismpay_params;
	  }
	  

      
   }

   /**
    * Add this Gateway to WooCommerce
   **/
   function woocommerce_prismpay_gateway($methods) 
   {
     // $methods[] = 'WC_PrismPay';
	  $methods[] = 'WC_Gateway_PrismPay';
      return $methods;
   }

   add_filter('woocommerce_payment_gateways', 'woocommerce_prismpay_gateway' );
}
