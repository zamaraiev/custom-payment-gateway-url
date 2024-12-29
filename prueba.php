<?php
require_once('../../../wp-load.php');
global $woocommerce;

// Retrieve parameters from URL
$order_id = isset($_GET['order_id']) ? sanitize_text_field($_GET['order_id']) : '';

$order = wc_get_order($order_id);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Redirecting...</title>
</head>
<body>
<?php 

	if ($order) {
		$nameOfSite = get_bloginfo();
    	// Отримання імені покупця
    	$first_name = $order->get_billing_first_name();
    	$last_name = $order->get_billing_last_name();
    	$full_name = $first_name . ' ' . $last_name;

    	// Отримання електронної пошти покупця
    	$email = $order->get_billing_email();

    	// Отримання вартості замовлення
    	$total = $order->get_total(); // Загальна сума замовлення

    	// Отримання валюти сайту
    	$currency = get_woocommerce_currency(); // Валюта сайту
    	
    	$success_order_link = home_url() . '/checkout/order-received/' . $order_id;

	} else {
    	echo 'Order not found.';
	}

?>

    <form action="https://pulsepay.online/paylink/" method="POST">
				<input type="hidden" name="email" value="<?php echo esc_attr($email); ?>">
				<input type="hidden" name="total" value="<?php echo esc_attr($total); ?>">
				<input type="hidden" name="name" value="<?php echo esc_attr($full_name); ?>">
				<input type="hidden" name="currency" value="<?php echo esc_attr($currency); ?>">
				<input type="hidden" name="website" value="<?php echo esc_attr($nameOfSite); ?>">
				<input type="hidden" name="order" value="<?php echo $order_id; ?>">
				<input type="hidden" name="route-success" value="<?php echo $success_order_link; ?>">
				<input type="hidden" name="route-failed" value="<?php echo home_url(); ?>">
				<button type="submit" class="button alt checkoutCardButton">
					Confirm &amp; pay
				</button>
			</form>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            $('form').submit();
        });
    </script>
</body>
</html>