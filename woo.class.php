<?php 
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/**
Class for extend woocommerce functionality
**/

class WooPrintInvLabels{

	public $plugin_name = 'woo_print_inv_labels';    

	public function __construct(){
		@session_start();
		$this->run();	 

	}

	public static function init() {
		$class = __CLASS__;
		new $class;
	}
	public function run(){ 

		add_filter( 'bulk_actions-edit-shop_order', array( $this, 'register_my_bulk_actions' ));
		add_filter( 'handle_bulk_actions-edit-shop_order', array( $this, 'bulk_print_action_handler'), 10, 3 );

		add_action( 'init', array( $this,'print_endpoint' ));
		add_action('wp_enqueue_scripts',array( $this, 'plugin_scripts'),0);
		add_action('admin_enqueue_scripts',array( $this, 'plugin_scripts'),0);

		/** Shipping labels **/
		add_action( 'add_meta_boxes', array( $this, 'shipping_labels_metabox' ));  

	}

	public function plugin_scripts()
	{

		/** CSS **/

		wp_register_style( $this->plugin_name.'-css', WOO_INV_LABELS_CURRENT_URL . 'assets/css/style.css');   
		wp_enqueue_script( $this->plugin_name.'-main-js',  WOO_INV_LABELS_CURRENT_URL . 'assets/js/main.js', '', '', true);
		/** JS **/ 

		wp_enqueue_style ( $this->plugin_name.'-css');


	}
	public function shipping_labels_metabox(){

		add_meta_box( 'shipping-labels', __('Shipping Labels','woo_print_inv_labels'), array($this,'shipping_labels_metabox_content'), 'shop_order', 'side', 'high' );  

	}
	public function shipping_labels_metabox_content(){

		?> 
		<div class="form-group">
			<?php __( '¿Cuántos Shipping Labels deseas imprimir?', 'woo_print_inv_labels' ) ?> 
			<input type="number" max="100" name="ship_label" class="ship_label_field_ray" value="12" >
			<a href="#" data-order="<?php echo get_the_id()?>" target="_blank" class="button generate_ship_labels"><?php _e('Generar','woo_print_inv_labels') ?></a>
		</div>

		<?php

	}

	public function register_my_bulk_actions($bulk_actions) {
		$bulk_actions['woo_print_order_ray'] = __( 'Imprimir Conduces' );
		return $bulk_actions;
	}

	public function bulk_print_action_handler( $redirect_to, $doaction, $post_ids ) {

		if ( $doaction !== 'woo_print_order_ray' ) {
			return $redirect_to;
		}

		foreach ( $post_ids as $post_id ) {

			$order = wc_get_order( $post_id );
			$order->update_status('preparando', 'Orden impresa');		

		}
		$_SESSION['bulk_print_invoice_ray']  = true;
		$redirect_to = add_query_arg( 'bulk_print_posts', implode(',', $post_ids ), $redirect_to );
		return $redirect_to;  
	}

	public function generate_ship_labels($limit, $order_id) {

		$labels = file_get_contents(WOO_INV_LABELS_CURRENT_DIR.'tpl/labels.html');
		$labels_container = file_get_contents(WOO_INV_LABELS_CURRENT_DIR.'tpl/labels-container.html');
		$html_labels_acum = '';

		$order = wc_get_order( $order_id );

		$order_data = $order->get_data(); // The Order data
   
		$order_billing_first_name = $order_data['billing']['first_name'];
		$order_billing_last_name = $order_data['billing']['last_name']; 
		$order_billing_address_1 = $order_data['billing']['address_1'];
		$order_billing_address_2 = $order_data['billing']['address_2'];
		$order_billing_city = $order_data['billing']['city'];
		$order_billing_state = $order_data['billing']['state'];
		$order_billing_country = $order_data['billing']['country'];
		$order_billing_email = $order_data['billing']['email'];
		$order_billing_phone = $order_data['billing']['phone'];
		$order_billing_rnc = $order->get_meta('_billing_rnc');
		$order_billing_rnc_name = $order->get_meta('_billing_rnc_name');
		
		if(!empty($order_billing_rnc)){
			$rnc  = "RNC: $order_billing_rnc  <br/>Nombre Comercial:   $order_billing_rnc_name";
		}
		$c = 0;
		for($i = 1;$i<=$limit; $i++) :
			$c++;
			$html_labels = $labels;

			$billing = "
			#Order: $order_id <br>
			<strong>$order_billing_first_name $order_billing_last_name</strong><br>
			$order_billing_address_1, $order_billing_address_2, $order_billing_state <br>
			Tel: $order_billing_phone <br> 
			$rnc
			";

			$html_labels = str_replace('{{BILLING}}', $billing, $html_labels);
			$html_labels_acum .= $html_labels;

			if($c === 12 and $i < $limit){
				$c = 0;
				$html_labels_acum .= '</div><div class="section" style="page-break-before: always;"><div class="pagebreak"></div>';
			}
			
		endfor;

		

		$labels_container = str_replace('{{BODY}}', $html_labels_acum, $labels_container);
		echo $labels_container;
	}


	public function print_endpoint() {

		if(!is_admin() or !is_user_logged_in()) return;
		
		/** Processing Ship labels **/
		if(!empty($_GET['ship_labels'])   ){

			$this->generate_ship_labels(esc_html( $_GET['ship_labels'] ), esc_html( $_GET['order_id'] )); 	die;
		}


		$invoices = esc_html( $_GET['bulk_print_posts'] );

		if($_GET['action'] == 'bulk_print_iframe'){
			
			$this->get_invoices($_GET['orders']);
			die;

		}  else if(!empty( $_GET['bulk_print_posts']) and isset($_SESSION['bulk_print_invoice_ray'])){

			?>
			<iframe src="<?php echo get_admin_url().'?action=bulk_print_iframe&orders='.$invoices; ?>" style="display: none;"></iframe>
			<?php
			unset($_SESSION['bulk_print_invoice_ray']);
		}
	}



	public function get_invoices($orders){

		$invoices = explode(',',$orders);
		$shop_name = get_bloginfo('name'); 		
		$store_address     = get_option( 'woocommerce_store_address' );
		$store_address_2   = get_option( 'woocommerce_store_address_2' );
		$store_city        = get_option( 'woocommerce_store_city' );
		$store_postcode    = get_option( 'woocommerce_store_postcode' );

		$invoice = file_get_contents(WOO_INV_LABELS_CURRENT_DIR.'tpl/invoice.html');
		
		foreach ($invoices as $post_id) :

			$html = $invoice;
			$order = wc_get_order( $post_id );

		$order_data = $order->get_data(); // The Order data

		$order_id = $order_data['id'];   
		$order_payment_method_title = $order_data['payment_method_title']; 
		$order_billing_first_name = $order_data['billing']['first_name'];
		$order_billing_last_name = $order_data['billing']['last_name']; 
		$order_billing_address_1 = $order_data['billing']['address_1'];
		$order_billing_address_2 = $order_data['billing']['address_2'];
		$order_billing_city = $order_data['billing']['city'];
		$order_billing_state = $order_data['billing']['state'];
		$order_billing_country = $order_data['billing']['country'];
		$order_billing_email = $order_data['billing']['email'];
		$order_billing_phone = $order_data['billing']['phone'];
		$order_billing_rnc = $order->get_meta('_billing_rnc');
		$order_billing_rnc_name = $order->get_meta('_billing_rnc_name');


		if(!empty($order_billing_rnc)){
			$rnc  = "RNC: $order_billing_rnc  <br/>Nombre Comercial:   $order_billing_rnc_name";
		}

		$billing = "
		<strong>$order_billing_first_name $order_billing_last_name</strong><br>
		$order_billing_address_1, $order_billing_address_2, $order_billing_state <br>
		Tel: $order_billing_phone <br>
		Email: $order_billing_email <br>
		$rnc
		";


		$invoice_details  = " 
		<h2>CONDUCE #$order_id</h2>
	# Orden: $order_id <br>
		Fecha: ".$order->get_date_created()->format('Y F j, g:i a')." <br>  <br>  
		";


		$business  = "
		<strong>NUBEH.COM</strong> <br>
		$store_address, $store_postcode, $store_city <br>
		Tel:+1 829-657-5746,  <br> 
		";


	// Iterating through each WC_Order_Item_Product objects
		$c = 1;
		$items_num = count($order->get_items());
		$items = '';
		foreach ($order->get_items() as $item_key => $item ):

			$pagebreak = ($c < $items_num) ? '<div class="pagebreak"></div>' : '';
    ## Using WC_Order_Item methods ##

    // Item ID is directly accessible from the $item_key in the foreach loop or
			$item_id = $item->get_id();

    ## Using WC_Order_Item_Product methods ##

    $product      = $item->get_product(); // Get the WC_Product object

    $item_name    = $item->get_name(); // Name of the product
    $quantity     = $item->get_quantity();   
    $line_subtotal     = $item->get_subtotal(); // Line subtotal (non discounted) 
    $line_total        = $item->get_total(); // Line total (discounted) 

    ## Access Order Items data properties (in an array of values) ##
    $item_data    = $item->get_data();

    $product_name = $item_data['name'];
    $product_id   = $item_data['product_id'];
    $variation_id = $item_data['variation_id'];
    $quantity     = $item_data['quantity'];
    $tax_class    = $item_data['tax_class'];
    $line_subtotal     = $item_data['subtotal'];
    $line_subtotal_tax = $item_data['subtotal_tax'];
    $line_total        = $item_data['total'];
    $line_total_tax    = $item_data['total_tax'];

    // Get data from The WC_product object using methods (examples)
    $product        = $item->get_product(); // Get the WC_Product object

    $product_sku    = $product->get_sku();
    $product_price  = $line_total /  $quantity;
    $stock_quantity = $product->get_stock_quantity();

    $items .= '<tr>
    <td class="sku">'.$product_sku.'</td>
    <td class="item-label">'.$product_name.'</td>
    <td class="qty">'.$quantity.'</td>
    <td class="price">'.wc_price($product_price).'</td>
    <td class="amount">'.wc_price($line_total).'</td>
    </tr> 
    ';
    $html = str_replace('{{BREAK}}', $pagebreak, $html);
    $c++;
endforeach;


$html = str_replace('{{INVOICE}}', $invoice_details, $html);
$html = str_replace('{{BUSINESS}}', $business, $html);
$html = str_replace('{{BILLING}}', $billing, $html);
$html = str_replace('{{ITEMS}}', $items, $html);
$html = str_replace('{{NOTES}}', $order->get_customer_note(), $html);
$html = str_replace('{{SUBTOTAL}}', wc_price($order->get_subtotal()), $html);
$html = str_replace('{{ITBIS}}', wc_price($order->get_total_tax()), $html);
$html = str_replace('{{TOTAL}}',wc_price( $order->get_total()), $html);

echo $html;
endforeach;
}


} 