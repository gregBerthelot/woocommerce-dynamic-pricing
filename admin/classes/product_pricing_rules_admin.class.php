<?php

class woocommerce_product_pricing_rules_admin {

	public function __construct() {
		add_action( 'woocommerce_product_write_panel_tabs', array(&$this, 'on_product_write_panel_tabs'), 99 );
		add_action( 'woocommerce_product_write_panels', array(&$this, 'product_data_panel'), 99 );
		add_action( 'woocommerce_process_product_meta', array(&$this, 'process_meta_box'), 1, 2 );
	}

	public function on_product_write_panel_tabs() {
		if ( WC_Dynamic_Pricing_Compatibility::is_wc_version_gte_2_3() ) :
			?>
			<li class="pricing_tab dynamic_pricing_options dynamic_pricing_options_23"><a href="#dynamic_pricing_data"><?php _e( 'Dynamic Pricing', 'wc_pricing' ); ?></a></li>

		<?php elseif ( WC_Dynamic_Pricing_Compatibility::is_wc_version_gte_2_1() ) : ?>	
			<li class="pricing_tab dynamic_pricing_options dynamic_pricing_options_21"><a href="#dynamic_pricing_data"><?php _e( 'Dynamic Pricing', 'wc_pricing' ); ?></a></li>
		<?php else : ?>
			<li class="pricing_tab dynamic_pricing_options"><a href="#dynamic_pricing_data"><?php _e( 'Dynamic Pricing', 'wc_pricing' ); ?></a></li>

		<?php
		endif;
	}

	public function product_data_panel() {
		global $post;
		$pricing_rule_sets = get_post_meta( $post->ID, '_pricing_rules', true );
		?>
		<div id="dynamic_pricing_data" class="panel woocommerce_options_panel">
			<div id="woocommerce-pricing-rules-wrap" data-setindex="<?php echo count( $pricing_rule_sets ); ?>">
				<?php $this->meta_box_javascript(); ?> 
				<?php if ( $pricing_rule_sets && is_array( $pricing_rule_sets ) && sizeof( $pricing_rule_sets ) > 0 ) : ?>

					<?php $this->create_rulesets( $pricing_rule_sets ); ?>

				<?php endif; ?>        
			</div>   

			<button title="<?php _e( 'Allows you to configure another Price Adjustment.  Useful if you have different sets of conditions and pricing adjustments which need to be applied to this product.', 'wc_pricing' ); ?>" id="woocommerce-pricing-add-ruleset" type="button" class="button button-primary"><?php _e( 'Add Pricing Group', 'wc_pricing' ); ?></button>
			<div class="clear"></div>
		</div>
		<?php
	}

	public function create_rulesets( $pricing_rule_sets ) {

		foreach ( $pricing_rule_sets as $name => $pricing_rule_set ) {
			$pricing_rules = isset( $pricing_rule_set['rules'] ) ? $pricing_rule_set['rules'] : null;
			$block_pricing_rules = isset( $pricing_rule_set['blockrules'] ) ? $pricing_rule_set['blockrules'] : null;

			$pricing_conditions = isset( $pricing_rule_set['conditions'] ) ? $pricing_rule_set['conditions'] : null;
			$collector = isset( $pricing_rule_set['collector'] ) ? $pricing_rule_set['collector'] : null;
			$variation_rules = isset( $pricing_rule_set['variation_rules'] ) ? $pricing_rule_set['variation_rules'] : null;

			$mode = isset( $pricing_rule_set['mode'] ) ? $pricing_rule_set['mode'] : 'continuous';
			$date_from = isset( $pricing_rule_set['date_from'] ) ? $pricing_rule_set['date_from'] : '';
			$date_to = isset( $pricing_rule_set['date_to'] ) ? $pricing_rule_set['date_to'] : '';
			?>
			<div id="woocommerce-pricing-ruleset-<?php echo $name; ?>" class="woocommerce_pricing_ruleset">
				<div id="woocommerce-pricing-conditions-<?php echo $name; ?>" class="section    ">
					<h4 class="first">Pricing Group<a href="#" data-name="<?php echo $name; ?>" class="delete_pricing_ruleset" ><img  src="<?php echo WC_Dynamic_Pricing::plugin_url(); ?>/assets/images/delete.png" title="<?php _e( 'Delete this Price Adjustment', 'wc_pricing' ); ?>" alt="<?php _e( 'Delete this Price Adjustment', 'wc_pricing' ); ?>" style="cursor:pointer; margin:0 3px;float:right;" /></a></h4>    
					<?php
					$condition_index = 0;
					if ( is_array( $pricing_conditions ) && sizeof( $pricing_conditions ) > 0 ):
						?>
						<input type="hidden" name="pricing_rules[<?php echo $name; ?>][conditions_type]" value="all" />
						<?php
						foreach ( $pricing_conditions as $condition ) :
							$condition_index++;
							$this->create_condition( $condition, $name, $condition_index );
						endforeach;
					else :
						?>
						<input type="hidden" name="pricing_rules[<?php echo $name; ?>][conditions_type]" value="all" />
						<?php
						$this->create_condition( array('type' => 'apply_to', 'args' => array('applies_to' => 'everyone', 'roles' => array())), $name, 1 );
					endif;
					?>
				</div>

				<div id="woocommerce-pricing-collector-<?php echo $name; ?>" class="section">
					<?php
					if ( is_array( $collector ) && count( $collector ) > 0 ) {
						$this->create_collector( $collector, $name );
					} else {
						$product_cats = array();
						$this->create_collector( array('type' => 'product', 'args' => array('cats' => $product_cats)), $name );
					}
					?>
				</div>

				<div id="woocommerce-pricing-variations-<?php echo $name; ?>" class="section">
					<?php
					$variation_index = 0;
					if ( is_array( $variation_rules ) && count( $variation_rules ) > 0 ) {
						$this->create_variation_selector( $variation_rules, $name );
					} else {
						$product_cats = array();
						$this->create_variation_selector( null, $name );
					}
					?>
				</div>

				<div id="woocommerce-pricing-mode-<?php echo $name; ?>" class="section">
					<label for="pricing_ruleset_mode_value_<?php echo $name . '_0'; ?>"><?php _e( 'Rule Processing Mode', 'wc_pricing' ); ?></label>
					<select id="pricing_ruleset_mode_value_<?php echo $name . '_0'; ?>" name="pricing_rules[<?php echo $name; ?>][mode]" class="pricing_rule_mode">
						<option <?php selected( 'continuous', $mode ); ?> value="continuous"><?php _e( 'Bulk', 'wc_pricing' ); ?></option>
						<option <?php selected( 'block', $mode ); ?> value="block"><?php _e( 'Special Offer', 'wc_pricing' ); ?></option>
					</select>
				</div>

				<div id="woocommerce-pricing-dates-<?php echo $name; ?>" class="section pricing-rule-date-fields">
					<label for="pricing_ruleset_dates_value_<?php echo $name . '_date_from'; ?>"><?php _e( 'Dates', 'wc_pricing' ); ?></label>
					<input value="<?php echo $date_from; ?>" type="text" class="short date_from" title="<?php _e( 'Leave both fields blank to not restrict this pricing group to a date range', 'wc_pricing' ); ?>" name="pricing_rules[<?php echo $name; ?>][date_from]" id="pricing_ruleset_dates_value_<?php echo $name . '_date_from'; ?>" value="" placeholder="<?php echo _x( 'From&hellip;', 'placeholder', 'woocommerce' ) ?> YYYY-MM-DD" maxlength="10" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])">
					<input value="<?php echo $date_to; ?>" type="text" class="short date_to" title="<?php _e( 'Leave both fields blank to not restrict this pricing group to a date range', 'wc_pricing' ); ?>" name="pricing_rules[<?php echo $name; ?>][date_to]" id="pricing_ruleset_dates_value_<?php echo $name . '_date_to'; ?>" value="" placeholder="<?php echo _x( 'To&hellip;', 'placeholder', 'woocommerce' ); ?> YYYY-MM-DD" maxlength="10" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])">
					<div class="clear"></div>
				</div>

				<script type="text/javascript">
					jQuery(document).ready(function ($) {
						// DATE PICKER FIELDS
						$(".pricing-rule-date-fields input:not(.hasDatepicker)").datepicker({
							defaultDate: "",
							dateFormat: "yy-mm-dd",
							numberOfMonths: 1,
							showButtonPanel: true,
							showOn: "button",
							buttonImage: woocommerce_pricing_admin.calendar_image,
							buttonImageOnly: true,
							onSelect: function (selectedDate) {
								var option = $(this).is('.date_from') ? "minDate" : "maxDate";

								var instance = $(this).data("datepicker"),
									date = $.datepicker.parseDate(
										instance.settings.dateFormat ||
										$.datepicker._defaults.dateFormat,
										selectedDate, instance.settings);

								var dates = $(this).parents('.pricing-rule-date-fields').find('input');
								dates.not(this).datepicker("option", option, date);
							}
						});
					})
				</script>			

				<div class="clear"></div>
				<div class="section" style="display:<?php echo ($mode == 'continuous' ? 'block' : 'none;'); ?>">
					<table  id="woocommerce-pricing-rules-table-<?php echo $name; ?>"  class="continuous" data-lastindex="<?php echo (is_array( $pricing_rules ) && sizeof( $pricing_rules ) > 0) ? count( $pricing_rules ) : '1'; ?>">
						<thead>
						<th>
							<?php _e( 'Minimum Quantity', 'wc_pricing' ); ?>
						</th>
						<th>
							<?php _e( 'Max Quantity', 'wc_pricing' ); ?>
						</th>
						<th>
							<?php _e( 'Type', 'wc_pricing' ); ?>
						</th>
						<th>
							<?php _e( 'Amount', 'wc_pricing' ); ?>
						</th>
						<th>&nbsp;</th>
						</thead>
						<tbody>
							<?php
							$index = 0;
							if ( is_array( $pricing_rules ) && sizeof( $pricing_rules ) > 0 ) {
								foreach ( $pricing_rules as $rule ) {
									$index++;
									$this->get_continuous_row( $rule, $name, $index );
								}
							} else {
								$this->get_continuous_row( array('to' => '', 'from' => '', 'amount' => '', 'type' => ''), $name, 1 );
							}
							?>
						</tbody>
						<tfoot>
						</tfoot>
					</table>
				</div>

				<div class="section" style="display:<?php echo ($mode == 'block' ? 'block' : 'none;'); ?>">
					<table  id="woocommerce-pricing-blockrules-table-<?php echo $name; ?>" class="block" data-lastindex="<?php echo (is_array( $pricing_rules ) && sizeof( $pricing_rules ) > 0) ? count( $pricing_rules ) : '1'; ?>">
						<thead>
						<th>
							<?php _e( 'Purchase', 'wc_pricing' ); ?>
						</th>
						<th>
							<?php _e( 'Receive', 'wc_pricing' ); ?>
						</th>
						<th>
							<?php _e( 'Type', 'wc_pricing' ); ?>
						</th>
						<th>
							<?php _e( 'Amount', 'wc_pricing' ); ?>
						</th>
						<th>
							<?php _e( 'Repeating', 'wc_pricing' ); ?>
						</th>
						<th>&nbsp;</th>
						</thead>
						<tbody>
							<?php
							$index = 0;
							if ( is_array( $block_pricing_rules ) && sizeof( $block_pricing_rules ) > 0 ) {
								foreach ( $block_pricing_rules as $rule ) {
									$index++;
									$this->get_block_row( $rule, $name, $index, count( $block_pricing_rules ) );
								}
							} else {
								$this->get_block_row( array('adjust' => '', 'from' => '', 'amount' => '', 'type' => '', 'repeating' => 'no'), $name, 1, 1 );
							}
							?>
						</tbody>
						<tfoot>
						</tfoot>
					</table>
				</div>

			</div>
			<?php
		}
	}

	public function create_empty_ruleset( $set_index ) {
		$pricing_rule_sets = array();
		$pricing_rule_sets[$set_index] = array();
		$pricing_rule_sets[$set_index]['title'] = 'Rule Set ' . $set_index;
		$pricing_rule_sets[$set_index]['rules'] = array();
		$this->create_rulesets( $pricing_rule_sets );
	}

	private function create_condition( $condition, $name, $condition_index ) {
		global $wp_roles;
		switch ( $condition['type'] ) {
			case 'apply_to':
				$this->create_condition_apply_to( $condition, $name, $condition_index );
				break;
			default:
				break;
		}
	}

	private function create_condition_apply_to( $condition, $name, $condition_index ) {
		if ( !isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}
		$all_roles = $wp_roles->roles;
		$div_style = ($condition['args']['applies_to'] != 'roles') ? 'display:none;' : '';
		?>

		<div>
			<label for="pricing_rule_apply_to_<?php echo $name . '_' . $condition_index; ?>"><?php _e( 'Applies To:', 'wc_pricing' ); ?></label>
			<input type="hidden" name="pricing_rules[<?php echo $name; ?>][conditions][<?php echo $condition_index; ?>][type]" value="apply_to" />

			<select title="<?php _e( 'Choose if this rule should apply to everyone, or to specific roles.  Useful if you only give discounts to existing customers, or if you have tiered pricing based on the users role.', 'wc_pricing' ); ?>" class="pricing_rule_apply_to" id="pricing_rule_apply_to_<?php echo $name . '_' . $condition_index; ?>" name="pricing_rules[<?php echo $name; ?>][conditions][<?php echo $condition_index; ?>][args][applies_to]">
				<option <?php selected( 'everyone', $condition['args']['applies_to'] ); ?> value="everyone"><?php _e( 'Everyone', 'wc_pricing' ); ?></option>
				<option <?php selected( 'unauthenticated', $condition['args']['applies_to'] ); ?> value="unauthenticated"><?php _e( 'Guests', 'wc_pricing' ); ?></option>
				<option <?php selected( 'roles', $condition['args']['applies_to'] ); ?> value="roles"><?php _e( 'Specific Roles', 'wc_pricing' ); ?></option>
				<?php do_action( 'woocommerce_dynamic_pricing_applies_to_options', 'advanced_product', $condition, $name, $condition_index ); ?>
			</select>

			<div class="roles" style="<?php echo $div_style; ?>">
				<?php $chunks = array_chunk( $all_roles, ceil( count( $all_roles ) / 3 ), true ); ?>

				<?php foreach ( $chunks as $chunk ) : ?>
					<ul class="list-column">        
						<?php foreach ( $chunk as $role_id => $role ) : ?>
							<?php $role_checked = (isset( $condition['args']['roles'] ) && is_array( $condition['args']['roles'] ) && in_array( $role_id, $condition['args']['roles'] )) ? 'checked="checked"' : ''; ?>
							<li>
								<label for="role_<?php echo $role_id; ?>" class="selectit">
									<input <?php echo $role_checked; ?> type="checkbox" id="role_<?php echo $role_id; ?>" name="pricing_rules[<?php echo $name; ?>][conditions][<?php echo $condition_index; ?>][args][roles][]" value="<?php echo $role_id; ?>" /><?php echo $role['name']; ?>
								</label>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endforeach; ?>

			</div>

			<?php do_action( 'woocommerce_dynamic_pricing_applies_to_selectors', 'advanced_product', $condition, $name, $condition_index ); ?>


			<div class="clear"></div>
		</div>
		<?php
	}

	private function create_variation_selector( $condition, $name ) {
		global $post;

		$post_id = isset( $_POST['post'] ) ? intval( $_POST['post'] ) : $post->ID;
		if ( !$post_id ) {
			return;
		}

		$product = null;
		if ( function_exists( 'get_product' ) ) {
			$product = get_product( $post_id );
		} else {
			$product = new WC_Product( $post_id );
		}

		if ( !$product->has_child() ) {
			return;
		}

		$all_variations = $product->get_children();
		$div_style = ($condition['args']['type'] != 'variations') ? 'display:none;' : '';
		?>

		<div>
			<label for="pricing_rule_variations_<?php echo $name; ?>"><?php _e( 'Product / Variations:', 'wc_pricing' ); ?></label>
			<select title="<?php _e( 'Choose what you would like to apply this pricing rule set to', 'wc_pricing' ); ?>" class="pricing_rule_variations" id="pricing_rule_variations_<?php echo $name; ?>" name="pricing_rules[<?php echo $name; ?>][variation_rules][args][type]">
				<option <?php selected( 'product', $condition['args']['type'] ); ?> value="product"><?php _e( 'All Variations', 'wc_pricing' ); ?></option>
				<option <?php selected( 'variations', $condition['args']['type'] ); ?> value="variations"><?php _e( 'Specific Variations', 'wc_pricing' ); ?></option>
			</select>

			<div class="variations" style="<?php echo $div_style; ?>">
				<?php  sort($all_variations); ?>
				<?php $chunks = array_chunk( $all_variations, ceil( count( $all_variations ) / 3 ), true ); ?>

				<?php foreach ( $chunks as $chunk ) : ?>

					<ul class="list-column">        
						<?php foreach ( $chunk as $variation_id ) : ?>
							<?php $variation_object = new WC_Product_Variation( $variation_id ); ?>
							<?php $variation_checked = (isset( $condition['args']['variations'] ) && is_array( $condition['args']['variations'] ) && in_array( $variation_id, $condition['args']['variations'] )) ? 'checked="checked"' : ''; ?>
							<li>
								<label for="variation_<?php echo $variation_id; ?>_<?php echo $name; ?>" class="selectit">
									<input <?php echo $variation_checked; ?> type="checkbox" id="variation_<?php echo $variation_id; ?>_<?php echo $name; ?>" name="pricing_rules[<?php echo $name; ?>][variation_rules][args][variations][]" value="<?php echo $variation_id; ?>" /><?php _e('Variation ID:', 'wc_pricing'); ?> <?php echo  $variation_id; ?>
								</label>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endforeach; ?>

			</div>
			<div class="clear"></div>
		</div>
		<?php
	}

	private function create_collector( $collector, $name ) {
		$terms = (array) get_terms( 'product_cat', array('get' => 'all') );
		$div_style = ($collector['type'] != 'cat') ? 'display:none;' : '';
		?>
		<label for="pricing_rule_when_<?php echo $name; ?>"><?php _e( 'Quantities based on:', 'wc_pricing' ); ?></label>
		<select title="<?php _e( 'Choose how to calculate the quantity.  This tallied amount is used in determining the min and max quantities used below in the Quantity Pricing section.', 'wc_pricing' ); ?>" class="pricing_rule_when" id="pricing_rule_when_<?php echo $name; ?>" name="pricing_rules[<?php echo $name; ?>][collector][type]">
			<option title="<?php _e( 'Calculate quantity based on the Product ID', 'wc_pricing' ); ?>" <?php selected( 'product', $collector['type'] ); ?> value="product"><?php _e( 'Product Quantity', 'wc_pricing' ); ?></option>
			<option title="<?php _e( 'Calculate quantity based on the Variation ID', 'wc_pricing' ); ?>" <?php selected( 'variation', $collector['type'] ); ?> value="variation"><?php _e( 'Variation Quantity', 'wc_pricing' ); ?></option>
			<option title="<?php _e( 'Calculate quantity based on the Cart Line Item', 'wc_pricing' ); ?>" <?php selected( 'cart_item', $collector['type'] ); ?> value="cart_item"><?php _e( 'Cart Line Item Quantity', 'wc_pricing' ); ?></option>
			<option title="<?php _e( 'Calculate quantity based on total amount of a category in the cart', 'wc_pricing' ); ?>" <?php selected( 'cat', $collector['type'] ); ?> value="cat"><?php _e( 'Quantity of Category', 'wc_pricing' ); ?></option>
		</select>
		<br />
		<div class="cats" style="<?php echo $div_style; ?>">
			<?php $chunks = array_chunk( $terms, ceil( count( $terms ) / 3 ) ); ?>
			<?php foreach ( $chunks as $chunk ) : ?>
				<ul class="list-column">        
					<?php foreach ( $chunk as $term ) : ?>
						<?php $term_checked = (isset( $collector['args']['cats'] ) && is_array( $collector['args']['cats'] ) && in_array( $term->term_id, $collector['args']['cats'] )) ? 'checked="checked"' : ''; ?> 
						<li>
							<label for="<?php echo $name; ?>_term_<?php echo $term->term_id; ?>" class="selectit">
								<input <?php echo $term_checked; ?> type="checkbox" id="<?php echo $name; ?>_term_<?php echo $term->term_id; ?>" name="pricing_rules[<?php echo $name; ?>][collector][args][cats][]" value="<?php echo $term->term_id; ?>" /><?php echo $term->name; ?>
							</label>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endforeach; ?>
			<div class="clear"></div>
		</div>

		<?php
	}

	private function get_continuous_row( $rule, $name, $index ) {
		?>
		<tr id="pricing_rule_row_<?php echo $name . '_' . $index; ?>">
			<td>
				<input title="<?php _e( 'Apply this adjustment when the quantity in the cart starts at this value.  Use * for any.', 'wc_pricing' ); ?>" class="int_pricing_rule" id="pricing_rule_from_input_<?php echo $name . '_' . $index; ?>" type="text" name="pricing_rules[<?php echo $name; ?>][rules][<?php echo $index ?>][from]" value="<?php echo $rule['from']; ?>" />
			</td>
			<td>
				<input title="<?php _e( 'Apply this adjustment when the quantity in the cart is less than this value.  Use * for any.', 'wc_pricing' ); ?>" class="int_pricing_rule" id="pricing_rule_to_input_<?php echo $name . '_' . $index; ?>" type="text" name="pricing_rules[<?php echo $name; ?>][rules][<?php echo $index ?>][to]" value="<?php echo $rule['to']; ?>" />
			</td>
			<td>
				<select title="<?php _e( 'The type of adjustment to apply', 'wc_pricing' ); ?>" id="pricing_rule_type_value_<?php echo $name . '_' . $index; ?>" name="pricing_rules[<?php echo $name; ?>][rules][<?php echo $index; ?>][type]">
					<option <?php selected( 'price_discount', $rule['type'] ); ?> value="price_discount"><?php _e( 'Price Discount', 'wc_pricing' ); ?></option>
					<option <?php selected( 'percentage_discount', $rule['type'] ); ?> value="percentage_discount"><?php _e( 'Percentage Discount', 'wc_pricing' ); ?></option>
					<option <?php selected( 'fixed_price', $rule['type'] ); ?> value="fixed_price"><?php _e( 'Fixed Price', 'wc_pricing' ); ?></option>
				</select>
			</td>
			<td>
				<input title="<?php _e( 'The value of the adjustment. Currency and percentage symbols are not required', 'wc_pricing' ); ?>" class="float_rule_number" id="pricing_rule_amount_input_<?php echo $name . '_' . $index; ?>" type="text" name="pricing_rules[<?php echo $name; ?>][rules][<?php echo $index; ?>][amount]" value="<?php echo $rule['amount']; ?>" /> 
			</td>
			<td width="48"><a class="add_pricing_rule" data-index="<?php echo $index; ?>" data-name="<?php echo $name; ?>"><img 
						src="<?php echo WC_Dynamic_Pricing::plugin_url() . '/assets/images/add.png'; ?>" 
						title="<?php _e( 'add another rule', 'wc_pricing' ); ?>" alt="<?php _e( 'add another rule', 'wc_pricing' ); ?>" 
						style="cursor:pointer; margin:0 3px;" /></a><a <?php echo ($index > 1) ? '' : 'style="display:none;"'; ?> class="delete_pricing_rule" data-index="<?php echo $index; ?>" data-name="<?php echo $name; ?>"><img 
						src="<?php echo WC_Dynamic_Pricing::plugin_url() . '/assets/images/remove.png'; ?>" 
						title="<?php _e( 'add another rule', 'wc_pricing' ); ?>" alt="<?php _e( 'add another rule', 'wc_pricing' ); ?>" 
						style="cursor:pointer; margin:0 3px;" /></a>
			</td>
		</tr>
		<?php
	}

	private function get_block_row( $rule, $name, $index, $row_count ) {
		?>
		<tr id="pricing_blockrule_row_<?php echo $name . '_' . $index; ?>">
			<td>
				<input title="<?php _e( 'Apply this adjustment when the quantity in the cart starts at this value.  Use * for any.', 'wc_pricing' ); ?>" class="int_pricing_rule" id="pricing_rule_from_input_<?php echo $name . '_' . $index; ?>" type="text" name="pricing_rules[<?php echo $name; ?>][blockrules][<?php echo $index ?>][from]" value="<?php echo $rule['from']; ?>" />
			</td>
			<td>
				<input title="<?php _e( 'Apply the discount to this many items', 'wc_pricing' ); ?>" class="int_pricing_rule" id="pricing_blockrule_to_input_<?php echo $name . '_' . $index; ?>" type="text" name="pricing_rules[<?php echo $name; ?>][blockrules][<?php echo $index ?>][adjust]" value="<?php echo $rule['adjust']; ?>" />
			</td>

			<td>
				<select title="<?php _e( 'The type of adjustment to apply', 'wc_pricing' ); ?>" name="pricing_rules[<?php echo $name; ?>][blockrules][<?php echo $index; ?>][type]">
					<option <?php selected( 'fixed_adjustment', $rule['type'] ); ?> value="fixed_adjustment"><?php _e( 'Price Discount', 'wc_pricing' ); ?></option>
					<option <?php selected( 'percent_adjustment', $rule['type'] ); ?> value="percent_adjustment"><?php _e( 'Percentage Discount', 'wc_pricing' ); ?></option>
					<option <?php selected( 'fixed_price', $rule['type'] ); ?> value="fixed_price"><?php _e( 'Fixed Price', 'wc_pricing' ); ?></option>
				</select>
			</td>

			<td>
				<input title="<?php _e( 'The value of the adjustment. Currency and percentage symbols are not required', 'wc_pricing' ); ?>" class="float_rule_number" id="pricing_blockrule_amount_input_<?php echo $name . '_' . $index; ?>" type="text" 
				       name="pricing_rules[<?php echo $name; ?>][blockrules][<?php echo $index; ?>][amount]" value="<?php echo $rule['amount']; ?>" /> 
			</td>

			<td>
				<select title="<?php _e( 'If the rule is repeating', 'wc_pricing' ); ?>" id="pricing_blockrule_type_value_<?php echo $name . '_' . $index; ?>" name="pricing_rules[<?php echo $name; ?>][blockrules][<?php echo $index; ?>][repeating]">
					<option <?php selected( 'no', $rule['repeating'] ); ?> value="no"><?php _e( 'No', 'wc_pricing' ); ?></option>
					<option <?php selected( 'yes', $rule['repeating'] ); ?> value="yes"><?php _e( 'Yes', 'wc_pricing' ); ?></option>
				</select>
			</td>

			<td width="48"><a class="add_pricing_blockrule" data-index="<?php echo $index; ?>" data-name="<?php echo $name; ?>"><img 
						src="<?php echo WC_Dynamic_Pricing::plugin_url() . '/assets/images/add.png'; ?>" 
						title="<?php _e( 'add another rule', 'wc_pricing' ); ?>" alt="<?php _e( 'add another rule', 'wc_pricing' ); ?>" 
						style="cursor:pointer; margin:0 3px;" /></a><a <?php echo ($row_count > 1) ? '' : 'style="display:none;"'; ?> class="delete_pricing_blockrule" data-index="<?php echo $index; ?>" data-name="<?php echo $name; ?>"><img 
						src="<?php echo WC_Dynamic_Pricing::plugin_url() . '/assets/images/remove.png'; ?>" 
						title="<?php _e( 'add another rule', 'wc_pricing' ); ?>" alt="<?php _e( 'add another rule', 'wc_pricing' ); ?>" 
						style="cursor:pointer; margin:0 3px;" /></a>
			</td>
		</tr>
		<?php
	}

	private function meta_box_javascript() {
		?>
		<script type="text/javascript">

			jQuery(document).ready(function ($) {
		<?php do_action( 'woocommerce_dynamic_pricing_metabox_js', 'advanced_product' ); ?>
				var set_index = 0;
				var rule_indexes = new Array();

				$('.woocommerce_pricing_ruleset').each(function () {
					var length = $('table tbody tr', $(this)).length;
					if (length == 1) {
						$('.delete_pricing_rule', $(this)).hide();
					}
				});


				$("#woocommerce-pricing-add-ruleset").click(function (event) {
					event.preventDefault();

					var set_index = $("#woocommerce-pricing-rules-wrap").data('setindex') + 1;
					$("#woocommerce-pricing-rules-wrap").data('setindex', set_index);

					var data = {
						set_index: set_index,
						post:<?php echo isset( $_GET['post'] ) ? $_GET['post'] : 0; ?>,
						action: 'create_empty_ruleset'
					}

					$.post(ajaxurl, data, function (response) {
						$('#woocommerce-pricing-rules-wrap').append(response);

					});
				});

				$('#woocommerce-pricing-rules-wrap').delegate('.pricing_rule_apply_to', 'change', function (event) {
					var value = $(this).val();
					if (value != 'roles' && $('.roles', $(this).parent()).is(':visible')) {
						$('.roles', $(this).parent()).fadeOut();
						$('.roles input[type=checkbox]', $(this).closest('div')).removeAttr('checked');
					}

					if (value == 'roles') {
						$('.roles', $(this).parent()).fadeIn();
					}
				});

				$('#woocommerce-pricing-rules-wrap').delegate('.pricing_rule_variations', 'change', function (event) {
					var value = $(this).val();
					if (value != 'variations') {
						$('.variations', $(this).parent()).fadeOut();
						$('.variations input[type=checkbox]', $(this).closest('div')).removeAttr('checked');
					} else {
						$('.variations', $(this).parent()).fadeIn();
					}
				});


				$('#woocommerce-pricing-rules-wrap').delegate('.pricing_rule_when', 'change', function (event) {
					var value = $(this).val();
					if (value != 'cat') {
						$('.cats', $(this).closest('div')).fadeOut();
						$('.cats input[type=checkbox]', $(this).closest('div')).removeAttr('checked');

					} else {
						$('.cats', $(this).closest('div')).fadeIn();
					}
				});

				$('#woocommerce-pricing-rules-wrap').delegate('.pricing_rule_mode', 'change', function (event) {
					var value = $(this).val();
					if (value != 'block') {
						$('table.block', $(this).closest('div.woocommerce_pricing_ruleset')).parent().fadeOut('fast', function () {
							$('table.continuous', $(this).closest('div.woocommerce_pricing_ruleset')).parent().fadeIn();
						});
					} else {

						$('table.continuous', $(this).closest('div.woocommerce_pricing_ruleset')).parent().fadeOut('fast', function () {
							$('table.block', $(this).closest('div.woocommerce_pricing_ruleset')).parent().fadeIn();
						});
					}
				});

				//Remove Pricing Set
				$('#woocommerce-pricing-rules-wrap').delegate('.delete_pricing_ruleset', 'click', function (event) {
					event.preventDefault();
					DeleteRuleSet($(this).data('name'));
				});

				//Add Button
				$('#woocommerce-pricing-rules-wrap').delegate('.add_pricing_rule', 'click', function (event) {
					event.preventDefault();
					InsertContinuousRule($(this).data('index'), $(this).data('name'));
				});

				$('#woocommerce-pricing-rules-wrap').delegate('.add_pricing_blockrule', 'click', function (event) {
					event.preventDefault();
					InsertBlockRule($(this).data('index'), $(this).data('name'));
				});


				//Remove Button                
				$('#woocommerce-pricing-rules-wrap').delegate('.delete_pricing_rule', 'click', function (event) {
					event.preventDefault();
					DeleteRule($(this).data('index'), $(this).data('name'));
				});

				//Remove Button                
				$('#woocommerce-pricing-rules-wrap').delegate('.delete_pricing_blockrule', 'click', function (event) {
					event.preventDefault();
					DeleteBlockRule($(this).closest('tr'), $(this).closest('table'));
				});


				

				$("#woocommerce-pricing-rules-wrap").sortable(
					{
						handle: 'h4.first',
						containment: 'parent',
						axis: 'y'
					});

				function InsertContinuousRule(previousRowIndex, name) {


					var $index = $("#woocommerce-pricing-rules-table-" + name).data('lastindex') + 1;
					$("#woocommerce-pricing-rules-table-" + name).data('lastindex', $index);

					var html = '';
					html += '<tr id="pricing_rule_row_' + name + '_' + $index + '">';
					html += '<td>';
					html += '<input class="int_pricing_rule" id="pricing_rule_from_input_' + name + '_' + $index + '" type="text" name="pricing_rules[' + name + '][rules][' + $index + '][from]" value="" /> ';
					html += '</td>';
					html += '<td>';
					html += '<input class="int_pricing_rule" id="pricing_rule_to_input_' + name + '_' + $index + '" type="text" name="pricing_rules[' + name + '][rules][' + $index + '][to]" value="" /> ';
					html += '</td>';
					html += '<td>';
					html += '<select id="pricing_rule_type_value_' + name + '_' + $index + '" name="pricing_rules[' + name + '][rules][' + $index + '][type]">';
					html += '<option value="price_discount">Price Discount</option>';
					html += '<option value="percentage_discount">Percentage Discount</option>';
					html += '<option value="fixed_price">Fixed Price</option>';
					html += '</select>';
					html += '</td>';
					html += '<td>';
					html += '<input class="float_pricing_rule" id="pricing_rule_amount_input_' + $index + '" type="text" name="pricing_rules[' + name + '][rules][' + $index + '][amount]" value="" /> ';
					html += '</td>';
					html += '<td width="48">';
					html += '<a data-index="' + $index + '" data-name="' + name + '" class="add_pricing_rule"><img  src="<?php echo WC_Dynamic_Pricing::plugin_url() . '/assets/images/add.png'; ?>" title="add another rule" alt="add another rule" style="cursor:pointer; margin:0 3px;" /></a>';
					html += '<a data-index="' + $index + '" data-name="' + name + '" class="delete_pricing_rule"><img data-index="' + $index + '" src="<?php echo WC_Dynamic_Pricing::plugin_url() . '/assets/images/remove.png'; ?>" title="remove rule" alt="remove rule" style="cursor:pointer; margin:0 3px;" /></a>';
					html += '</td>';
					html += '</tr>';

					$('#pricing_rule_row_' + name + '_' + previousRowIndex).after(html);
					$('.delete_pricing_rule', "#woocommerce-pricing-rules-table-" + name).show();

				}

				function InsertBlockRule(previousRowIndex, name) {
					var $index = $("#woocommerce-pricing-blockrules-table-" + name).data('lastindex') + 1;
					$("#woocommerce-pricing-blockrules-table-" + name).data('lastindex', $index);

					var html = '';
					html += '<tr id="pricing_blockrule_row_' + name + '_' + $index + '">';
					html += '<td>';
					html += '<input class="int_pricing_blockrule" type="text" name="pricing_rules[' + name + '][blockrules][' + $index + '][from]" value="" /> ';
					html += '</td>';
					html += '<td>';
					html += '<input class="int_pricing_blockrule" type="text" name="pricing_rules[' + name + '][blockrules][' + $index + '][adjust]" value="" /> ';
					html += '</td>';
					html += '<td>';
					html += '<select name="pricing_rules[' + name + '][blockrules][' + $index + '][type]">';
					html += '<option value="price_discount">Price Discount</option>';
					html += '<option value="percentage_discount">Percentage Discount</option>';
					html += '<option value="fixed_price">Fixed Price</option>';
					html += '</select>';
					html += '</td>';
					html += '<td>';
					html += '<input class="float_pricing_rule" id="pricing_rule_amount_input_' + $index + '" type="text" name="pricing_rules[' + name + '][blockrules][' + $index + '][amount]" value="" /> ';
					html += '</td>';
					html += '<td>';
					html += '<select name="pricing_rules[' + name + '][blockrules][' + $index + '][repeating]">';
					html += '<option value="no">No</option>';
					html += '<option value="yes">Yes</option>';
					html += '</select>';
					html += '</td>';
					html += '<td width="48">';
					html += '<a data-index="' + $index + '" data-name="' + name + '" class="add_pricing_blockrule"><img  src="<?php echo WC_Dynamic_Pricing::plugin_url() . '/assets/images/add.png'; ?>" title="add another rule" alt="add another rule" style="cursor:pointer; margin:0 3px;" /></a>';
					html += '<a data-index="' + $index + '" data-name="' + name + '" class="delete_pricing_blockrule"><img data-index="' + $index + '" src="<?php echo WC_Dynamic_Pricing::plugin_url() . '/assets/images/remove.png'; ?>" title="remove rule" alt="remove rule" style="cursor:pointer; margin:0 3px;" /></a>';
					html += '</td>';
					html += '</tr>';

					$('#pricing_blockrule_row_' + name + '_' + previousRowIndex).after(html);
					$('.delete_pricing_blockrule', "#woocommerce-pricing-blockrules-table-" + name).show();
				}

				function DeleteRule(index, name) {
					if (confirm("Are you sure you would like to remove this price adjustment?")) {
						$('#pricing_rule_row_' + name + '_' + index).remove();

						var $index = $('tbody tr', "#woocommerce-pricing-rules-table-" + name).length;
						if ($index > 1) {
							$('.delete_pricing_rule', "#woocommerce-pricing-rules-table-" + name).show();
						} else {
							$('.delete_pricing_rule', "#woocommerce-pricing-rules-table-" + name).hide();
						}
					}
				}

				function DeleteBlockRule($tr, $table) {
					if (confirm("Are you sure you would like to remove this price adjustment?")) {
						$tr.remove();

						var count = $('tr', $table).length;
						if (count > 1) {
							$('.delete_pricing_blockrule', $table).show();
						} else {
							$('.delete_pricing_blockrule', $table).hide();
						}
					}
				}

				function DeleteRuleSet(name) {
					if (confirm('Are you sure you would like to remove this dynamic price set?')) {
						$('#woocommerce-pricing-ruleset-' + name).slideUp().remove();
					}
				}

			});

		</script>
		<?php
	}

	public function process_meta_box( $post_id, $post ) {
		if ( isset( $_POST['pricing_rules'] ) ) {
			update_post_meta( $post_id, '_pricing_rules', $_POST['pricing_rules'] );
		} else {
			delete_post_meta( $post_id, '_pricing_rules' );
		}
	}

}
