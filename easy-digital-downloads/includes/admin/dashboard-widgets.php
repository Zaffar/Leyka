<?php
/**
 * Dashboard Widgets
 *
 * @package     EDD
 * @subpackage  Admin/Dashboard
 * @copyright   Copyright (c) 2013, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Registers the dashboard widgets
 *
 * @author Sunny Ratilal
 * @since 1.2.2
 * @return void
 */
function edd_register_dashboard_widgets() {
	if ( current_user_can( apply_filters( 'edd_dashboard_stats_cap', 'edit_pages' ) ) ) {
		wp_add_dashboard_widget( 'edd_dashboard_sales', __('Easy Digital Downloads Sales Summary', 'edd'), 'edd_dashboard_sales_widget' );
	}
}
add_action('wp_dashboard_setup', 'edd_register_dashboard_widgets' );

/**
 * Sales Summary Dashboard Widget
 *
 * Builds and renders the Sales Summary dashboard widget. This widget displays
 * the current month's sales and earnings, total sales and earnings best selling
 * downloads as well as recent purchases made on your EDD Store.
 *
 * @author Sunny Ratilal
 * @since 1.2.2
 * @return void
 */
function edd_dashboard_sales_widget() {
	$top_selling_args = array(
		'post_type'              => 'download',
		'posts_per_page'         => 1,
		'post_status'            => 'publish',
		'meta_key'               => '_edd_download_sales',
		'meta_compare'           => '>',
		'meta_value'             => 0,
		'orderby'                => 'meta_value_num',
		'update_post_term_cache' => false,
		'order'                  => 'DESC'
	);

	$top_selling = get_posts( $top_selling_args );
	?>
	<div class="edd_dashboard_widget">
		<div class="table table_left table_current_month">
			<p class="sub"><?php _e( 'Current Month', 'edd' ) ?></p>
			<table>
				<tbody>
					<tr class="first">
						<td class="first b"><?php echo edd_currency_filter( edd_format_amount( edd_get_earnings_by_date( null, date( 'n' ), date( 'Y' ) ) ) ); ?></td>
						<td class="t monthly_earnings"><?php _e( 'Earnings', 'edd' ); ?></td>
					</tr>
					<tr>
						<?php $monthly_sales = edd_get_sales_by_date( null, date( 'n' ), date( 'Y' ) ); ?>
						<td class="first b"><?php echo $monthly_sales; ?></td>
						<td class="t monthly_sales"><?php echo _n( 'Sale', 'Sales', $monthly_sales, 'edd' ); ?></td>
					</tr>
				</tbody>
			</table>
			<p class="label_heading"><?php _e( 'Last Month', 'edd' ) ?></p>
			<?php
			$previous_month   = date( 'n' ) == 1 ? 12 : date( 'n' ) - 1;
			$previous_year    = $previous_month == 12 ? date( 'Y' ) - 1 : date( 'Y' );
			?>
			<div>
				<?php echo __( 'Earnings', 'edd' ) . ':&nbsp;<span class="edd_price_label">' . edd_currency_filter( edd_format_amount( edd_get_earnings_by_date( null, $previous_month, $previous_year ) ) ) . '</span>'; ?>
			</div>
			<div>
				<?php $last_month_sales = edd_get_sales_by_date( null, $previous_month, $previous_year ); ?>
				<?php echo _n( 'Sale', 'Sales', $last_month_sales, 'edd' ) . ':&nbsp;' . '<span class="edd_price_label">' . $last_month_sales . '</span>'; ?>
			</div>
		</div>
		<div class="table table_right table_totals">
			<p class="sub"><?php _e( 'Totals', 'edd' ) ?></p>
			<table>
				<tbody>
					<tr class="first">
						<td class="b b-earnings"><?php echo edd_currency_filter( edd_format_amount( edd_get_total_earnings() ) ); ?></td>
						<td class="last t earnings"><?php _e( 'Total Earnings', 'edd' ); ?></td>
					</tr>
					<tr>
						<td class="b b-sales"><?php echo edd_get_total_sales(); ?></td>
						<td class="last t sales"><?php _e( 'Total Sales', 'edd' ); ?></td>
					</tr>
				</tbody>
			</table>
			<?php if ( $top_selling ) {
				foreach( $top_selling as $list ) { ?>
					<p class="lifetime_best_selling label_heading"><?php _e('Lifetime Best Selling', 'edd') ?></p>
					<p><span class="lifetime_best_selling_label"><?php echo edd_get_download_sales_stats( $list->ID ); ?></span> <a href="<?php echo get_permalink( $list->ID ); ?>"><?php echo get_the_title( $list->ID ); ?></a></p>
			<?php } } ?>
		</div>
		<div style="clear: both"></div>
		<?php
		$payments = edd_get_payments( array(
			'number'   => 5,
			'mode'     => 'live',
			'orderby'  => 'post_date',
			'order'    => 'DESC',
			'user'     => null,
			'status'   => 'publish',
			'meta_key' => null,
			'fields'   => 'ids',
		) );

		if ( $payments ) { ?>
		<p class="edd_dashboard_widget_subheading"><?php _e( 'Recent Purchases', 'edd' ); ?></p>
		<div class="table recent_purchases">
			<table>
				<tbody>
					<?php
						foreach ( $payments as $payment ) {
							$payment_meta = edd_get_payment_meta( $payment );
					?>
					<tr>
						<td><?php echo get_the_title( $payment ) ?> - (<?php echo $payment_meta['email'] ?>) - <span class="edd_price_label"><?php echo edd_currency_filter( edd_format_amount( edd_get_payment_amount( $payment ) ) ); ?></span> - <a href="#TB_inline?width=640&amp;inlineId=purchased-files-<?php echo $payment; ?>" class="thickbox" title="<?php printf( __( 'Purchase Details for Payment #%s', 'edd' ), $payment ); ?> "><?php _e( 'View Order Details', 'edd' ); ?></a>
							<div id="purchased-files-<?php echo $payment; ?>" style="display:none;">
								<?php
									$cart_items = edd_get_payment_meta_cart_details( $payment );
									if ( empty( $cart_items ) || !$cart_items ) {
										$cart_items = maybe_unserialize( $payment_meta['downloads'] );
									}
								?>
								<h4><?php echo _n( __( 'Purchased File', 'edd' ), __( 'Purchased Files', 'edd' ), count( $cart_items ) ); ?></h4>
								<ul class="purchased-files-list">
								<?php
									if ( $cart_items ) {
										foreach ( $cart_items as $key => $cart_item ) {
											echo '<li>';
												$id = isset( $payment_meta['cart_details'] ) ? $cart_item['id'] : $cart_item;
												$price_override = isset( $payment_meta['cart_details'] ) ? $cart_item['price'] : null;
												$user_info = edd_get_payment_meta_user_info( $payment );
												$price = edd_get_download_final_price( $id, $user_info, $price_override );
												echo '<a href="' . admin_url( 'post.php?post=' . $id . '&action=edit' ) . '" target="_blank">' . get_the_title( $id ) . '</a>';
												echo  ' - ';
												if( isset( $cart_items[ $key ]['item_number'])) {
													$price_options = $cart_items[ $key ]['item_number']['options'];
													if( isset( $price_options['price_id'] ) ) {
														echo edd_get_price_option_name( $id, $price_options['price_id'], $payment);
														echo ' - ';
													}
												}
												echo edd_currency_filter( edd_format_amount( $price ) );
											echo '</li>';
										}
									}
								?>
								</ul>
								<?php $payment_date = strtotime( get_post_field( 'post_date', $payment ) ); ?>
								<p><?php echo __( 'Date and Time:', 'edd' ) . ' ' . date_i18n( get_option( 'date_format' ), $payment_date ) . ' ' . date_i18n( get_option( 'time_format' ), $payment_date ) ?>
								<p><?php echo __( 'Discount used:', 'edd' ) . ' '; if( isset( $user_info['discount'] ) && $user_info['discount'] != 'none' ) { echo $user_info['discount']; } else { _e( 'none', 'edd' ); } ?>
								<?php
								$fees = edd_get_payment_fees( $payment );
								if( ! empty( $fees ) ) : ?>
								<ul class="payment-fees">
									<?php foreach( $fees as $fee ) : ?>
									<li><?php echo $fee['label'] . ':&nbsp;' . edd_currency_filter( $fee['amount'] ); ?></li>
									<?php endforeach; ?>
								</ul>
								<?php endif; ?>
								<p><?php echo __( 'Total:', 'edd' ) . ' ' . edd_currency_filter( edd_format_amount( edd_get_payment_amount( $payment ) ) ); ?></p>

								<div class="purcase-personal-details">
									<h4><?php _e( 'Buyer\'s Personal Details:', 'edd' ); ?></h4>
									<ul>
										<li><?php echo __( 'Name:', 'edd' ) . ' ' . $user_info['first_name'] . ' ' . $user_info['last_name']; ?></li>
										<li><?php echo __( 'Email:', 'edd' ) . ' ' . $payment_meta['email']; ?></li>
										<?php do_action( 'edd_payment_personal_details_list', $payment_meta, $user_info ); ?>
									</ul>
								</div>
								<div class="payment-notes">
									<h4><?php _e( 'Payment Notes', 'edd' ); ?></h4>
									<?php
									$notes = edd_get_payment_notes( $payment );
									if ( ! empty( $notes ) ) :
										echo '<ul id="payment-notes">';
										foreach ( $notes as $note ):
											if ( ! empty( $note->user_id ) ) {
												$user = get_userdata( $note->user_id );
												$user = $user->display_name;
											} else {
												$user = __( 'EDD Bot', 'edd' );
											}
											echo '<div class="edd-payment-note"><strong>' . $user . '</strong>&nbsp;<em>' . $note->comment_date . '</em>&nbsp;&mdash;' . $note->comment_content . '</div>';
										endforeach;
										echo '</ul>';
									else :
										echo '<p>' . __( 'No payment notes', 'edd' ) . '</p>';
									endif;
									?>
								</div>
								<?php
								$gateway = edd_get_payment_gateway( $payment );
								if ( $gateway ) { ?>
								<div class="payment-method">
									<h4><?php _e( 'Payment Method:', 'edd' ); ?></h4>
									<span class="payment-method-name"><?php echo edd_get_gateway_admin_label( $gateway ); ?></span>
								</div>
								<?php } ?>
								<div class="purchase-key-wrap">
									<h4><?php _e('Purchase Key', 'edd'); ?></h4>
									<span class="purchase-key"><?php echo $payment_meta['key']; ?></span>
								</div>

								<?php do_action( 'edd_payment_view_details', $payment ); ?>

								<p><a id="edd-close-purchase-details" class="button-secondary" onclick="tb_remove();" title="<?php _e( 'Close', 'edd' ); ?>"><?php _e( 'Close', 'edd' ); ?></a></p>
							</div>
						</td>
					</tr>
					<?php } // End foreach ?>
				</tbody>
			</table>
		</div>
		<?php } // End if ?>
	</div>
	<?php
}