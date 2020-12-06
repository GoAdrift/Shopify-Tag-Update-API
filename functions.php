<?php
function targetCompany_deactivate() {
    wp_clear_scheduled_hook( 'targetCompany_cron' );
}
 
add_action('init', function() {
    add_action( 'targetCompany_cron', 'targetCompany_hourly_event' );
    register_deactivation_hook( __FILE__, 'targetCompany_deactivate' );
 	$args = array( $args_1, $args_2 );
    if (! wp_next_scheduled ( 'targetCompany_hourly_event', $args )) {
        wp_schedule_event( time(), 'every-2-hours', 'targetCompany_hourly_event', $args );  
    }
});

	add_filter( 'cron_schedules', function ( $schedules ) {
		$schedules['every-2-hours'] = array(
			'interval' => 120 * MINUTE_IN_SECONDS,
			'display'  => __( 'Every 2 hours' )
		);
		return $schedules;
	} );

add_action( 'targetCompany_hourly_event', 'targetCompany_shopify_timer', 10, 2 );

function targetCompany_shopify_timer(){

	$sent = 0;
	$output = targetCompany_get_shopify();
	$products = json_decode( $output, true )['products'];

	if( is_array($products) ){
		$all_sizes = array( XSMALL, SMALL, MEDIUM, LARGE, XLARGE, XXLARGE, XXXLARGE );
		
		foreach( $products as $product ){
			if( is_array( $product ) ){
				
				$tags = '';
				$product_sizes = array();
				$old_tags = $product[tags];
				$tags_array = explode( ', ', $old_tags );
				$qty_count = 0;
				foreach( $product[variants] as $variant ){

					$option1 = $variant[option1];
					if( in_array( $option1, $all_sizes, $strict = TRUE ) ){
						$qty = $variant[inventory_quantity];
						if( $qty ){
							$qty_count++;
							if( ! in_array( $option1, $tags_array, $strict = TRUE ) ){
								array_push( $tags_array, $option1 );
							}
							if( ! in_array( $option1, $product_sizes, $strict = TRUE ) ){
								array_push( $product_sizes, $option1 );
							}
						}
					}
				}
				if( $qty_count == 0 ){
					continue;
				}
				if( is_array( $all_sizes )){
					foreach( $all_sizes as $size ){
						if( ! in_array( $size, $product_sizes, $strict = TRUE ) ){
							$is = in_array( $size, $tags_array, $strict = TRUE );
							if( $is ){
								$key = array_search( $size, $tags_array, $strict = TRUE );
								if( $key ){
									unset( $tags_array[$key] );
								}
							}
						}
					}
				}

				if( is_array( $tags_array )){
					foreach( $tags_array as $t ){
						if( !$tags ){
							$tags = $t;
						} else if( $tags ){
							$tags .= ', ' . $t;
						}
					}
					$product[tags] = $tags;
					$product_id = $product[id];
					$put =array(
						'product'	=>	array(
							'id'	=>	$product_id,
							'tags' 	=>	$tags
						)
					);

					if( $product_id ){
						$results = targetCompany_post_shopify( $product_id, json_encode( $put ) );
						$sent++;
					}
				}
			}
		}
	}
}


function targetCompany_get_shopify(){
	$user = '########API#USER########';
	$pass = '########API#PASSWORD########';
	$init = "https://".$user.":".$pass."@targetCompany.myshopify.com/admin/api/2020-10/products.json?limit=250";
	$ch = curl_init( );
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "GET");
	curl_setopt( $ch, CURLOPT_URL, $init );

	curl_setopt($ch, CURLOPT_USERAGENT, 'GoAdrift Web Solution v.1');
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1000);
	curl_setopt($ch, CURLOPT_TIMEOUT, 1000);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
	curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	$output = curl_exec ($ch);
	$test = curl_close ($ch);	
	return $output;
}

function targetCompany_post_shopify($product_id, $put){
	if( $put ){
		$user = '########API#USER########';
		$pass = '########API#PASSWORD########';
		$init = "https://".$user.":".$pass."@targetCompany.myshopify.com/admin/api/2020-10/products/".$product_id.".json";
		$ch = curl_init( );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Content-Type: application/json','Content-Length: ' . strlen($put) ) );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "PUT");
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $put );
		curl_setopt( $ch, CURLOPT_URL, $init );
		
		curl_setopt($ch, CURLOPT_USERAGENT, 'GoAdrift Web Solution v.1');
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		$output = curl_exec ($ch);
		$test = curl_close ($ch);
		return $output;
	}
}
