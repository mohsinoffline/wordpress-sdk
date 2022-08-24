<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
	 * @since       1.2.1.5
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	/**
	 * @var array $VARS
	 * @var Freemius $fs
	 */
	$fs   = freemius( $VARS['id'] );
	$slug = $fs->get_slug();

	$action = $fs->is_tracking_allowed() ?
		'stop_tracking' :
		'allow_tracking';

	$reconnect_url = $fs->get_activation_url( array(
		'nonce'     => wp_create_nonce( $fs->get_unique_affix() . '_reconnect' ),
		'fs_action' => ( $fs->get_unique_affix() . '_reconnect' ),
	) );

	$plugin_title                   = "<strong>{$fs->get_plugin()->title}</strong>";
	$opt_out_text                   = fs_text_x_inline( 'Opt Out', 'verb', 'opt-out', $slug );
	$opt_in_text                    = fs_text_x_inline( 'Opt In', 'verb', 'opt-in', $slug );

	if ( $fs->is_premium() ) {
		$opt_in_message_appreciation = fs_text_inline( 'Connectivity to the licensing engine was successfully re-established. Automatic security & feature updates are now available through the WP Admin Dashboard.', 'premium-opt-in-message-appreciation', $slug );

		$opt_out_message_subtitle       = sprintf( fs_text_inline( 'Warning: Opting out will block automatic updates', 'premium-opt-out-message-appreciation', $slug ), $fs->get_module_type() );
		$opt_out_message_usage_tracking = sprintf( fs_text_inline( 'Ongoing connectivity with the licensing engine is essential for receiving automatic security & feature updates of the paid product. To receive these updates, data like your license key, %1$s version, and WordPress version, is periodically sent to the server to check for updates. By opting out, you understand that your site won\'t receive automatic updates for %2$s from within the WP Admin Dashboard. This can put your site at risk, and we highly recommend to keep this connection active. If you do choose to opt-out, you\'ll need to check for %1$s updates and install them manually.', 'premium-opt-out-message-usage-tracking', $slug ), $fs->get_module_type(), $plugin_title );

		$primary_cta_label = fs_text_inline( 'I\'d like to keep automatic updates', 'premium-opt-out-cancel', $slug );
	} else {
		$opt_in_message_appreciation = sprintf( fs_text_inline( 'We appreciate your help in making the %s better by letting us track some usage data.', 'opt-in-message-appreciation', $slug ), $fs->get_module_type() );

		$opt_out_message_subtitle       = $opt_in_message_appreciation;
		$opt_out_message_usage_tracking = sprintf( fs_text_inline( "Usage tracking is done in the name of making %s better. Making a better user experience, prioritizing new features, and more good things. We'd really appreciate if you'll reconsider letting us continue with the tracking.", 'opt-out-message-usage-tracking', $slug ), $plugin_title );
		$primary_cta_label              = fs_text_inline( 'On second thought - I want to continue helping', 'opt-out-cancel', $slug );
	}

	$opt_out_message_clicking_opt_out = sprintf(
		fs_text_inline( 'By clicking "Opt Out", we will no longer be sending any data from %s to %s.', 'opt-out-message-clicking-opt-out', $slug ),
		$plugin_title,
		sprintf(
			'<a href="%s" target="_blank" rel="noopener">%s</a>',
			'https://freemius.com',
			'freemius.com'
		)
	);

	$admin_notice_params = array(
		'id'      => '',
		'slug'    => $fs->get_id(),
		'type'    => 'success',
		'sticky'  => false,
		'plugin'  => $fs->get_plugin()->title,
		'message' => $opt_in_message_appreciation
	);

	$admin_notice_html = fs_get_template( 'admin-notice.php', $admin_notice_params );

    $modal_content_html = "
		<h2" . ( $fs->is_premium() ? ' style="color: red"' : '' ) . ">{$opt_out_message_subtitle}</h2>
		<div class=\"notice notice-error inline opt-out-error-message\"><p></p></div>
		<p>{$opt_out_message_usage_tracking}</p>
		<p>{$opt_out_message_clicking_opt_out}</p>
		<label class=\"fs-permission-extensions\"><div class=\"fs-switch fs-small fs-round fs-" . ( $fs->is_extensions_tracking_allowed() ? 'on' : 'off' ) . "\"><div class=\"fs-toggle\"></div></div> " . fs_text_inline( 'Plugins & themes tracking' ) . " <span class=\"fs-switch-feedback success\"></span></label>";

	fs_enqueue_local_style( 'fs_dialog_boxes', '/admin/dialog-boxes.css' );
	fs_enqueue_local_style( 'fs_optout', '/admin/optout.css' );
	fs_enqueue_local_style( 'fs_common', '/admin/common.css' );

    $permission_manager = FS_Permission_Manager::instance( $fs );

//    $permissions        = $permission_manager->get_permissions( true );

    $form_id = "fs_opt_out_{$fs->get_id()}";
?>
<div id="<?php echo $form_id ?>" class="fs-modal fs-modal-opt-out" style="display: none">
    <div class="fs-modal-dialog">
        <div class="fs-modal-header">
            <h4><?php echo esc_html( $opt_out_text ) ?></h4>
        </div>
        <div class="fs-modal-body">
            <?php
                // @todo UPDATE DESCRIPTIONS TO TRANSLATABLE
                $optional_permissions = $permission_manager->get_license_optional_permissions( false, true );

                $permission_groups = array(
                    array(
                        'type'        => 'required',
                        'title'       => $fs->esc_html_inline( 'Required', 'required' ),
                        'desc'        => 'For delivery of security &amp; feature updates, and license management, PluginX needs to:',
                        'permissions' => $permission_manager->get_license_required_permissions(),
                    ),
                    array(
                        'type'        => 'optional',
                        'title'       => $fs->esc_html_inline( 'Optional', 'optional' ),
                        'desc'        => 'For ___ ______ short explanation of the values, you can optionally allow PluginX to view:',
                        'permissions' => $optional_permissions,
                    ),
                );
            ?>
            <div class="fs-permissions fs-open">
            <?php foreach ( $permission_groups as $i => $permission_group ) : ?>
                <div class="fs-permissions-section">
                    <div>
                        <div class="fs-permissions-section--header">
                            <a class="fs-permissions-section--header-optout" data-type="<?php echo $permission_group['type'] ?>" href="#"><?php echo esc_html( $opt_out_text ) ?></a>
                            <span class="fs-permissions-section--header-title"><?php echo $permission_group['title'] ?></span>
                        </div>
                        <p class="fs-permissions-section--desc"><?php echo $permission_group['desc'] ?></p></div>
                    <ul>
                        <?php
                            foreach ($permission_group['permissions'] as $permission) {
                                $permission_manager->render_permission( $permission );
                            }
                        ?>
                    </ul>
                </div>
                <?php if ( 0 === $i ) : ?><hr><?php endif ?>
            <?php endforeach ?>
            </div>
            <!--            <div class="fs-modal-panel active">' + modalContentHtml + '</div>-->
        </div>
        <div class="fs-modal-footer">
            <button class="button button-primary button-close" tabindex="1"><?php echo $fs->esc_html_inline( 'Done', 'done' ) ?></button>
        </div>
    </div>
</div>

<script type="text/javascript">
	(function( $ ) {
		$( document ).ready(function() {
			var modalContentHtml = <?php echo json_encode( $modal_content_html ) ?>,
			    modalHtml =
				    '<div class="fs-modal fs-modal-opt-out">'
				    + '	<div class="fs-modal-dialog">'
				    + '		<div class="fs-modal-header">'
				    + '		    <h4><?php echo esc_js( $opt_out_text ) ?></h4>'
				    + '		</div>'
				    + '		<div class="fs-modal-body">'
				    + '			<div class="fs-modal-panel active">' + modalContentHtml + '</div>'
				    + '		</div>'
				    + '		<div class="fs-modal-footer">'
				    + '			<button class="button <?php echo $fs->is_premium() ? 'button-primary warn' : 'button-secondary' ?> button-opt-out" tabindex="1"><?php echo esc_js( $opt_out_text ) ?></button>'
				    + '			<button class="button <?php echo $fs->is_premium() ? 'button-secondary' : 'button-primary' ?> button-close" tabindex="2"><?php echo esc_js( $primary_cta_label ) ?></button>'
				    + '		</div>'
				    + '	</div>'
				    + '</div>',
                $modal              = $('#<?php echo $form_id ?>'),
                $adminNotice        = $( <?php echo json_encode( $admin_notice_html ) ?> ),
                action              = '<?php echo $action ?>',
                actionLinkSelector  = 'span.opt-in-or-opt-out.<?php echo $slug ?> a',
                $optOutButton       = $modal.find( '.button-opt-out' ),
                $optOutErrorMessage = $modal.find( '.opt-out-error-message' ),
                $body               = $( 'body' ),
                moduleID            = '<?php echo $fs->get_id() ?>';

			$modal.data( 'action', action );
			//$modal.appendTo( $body );

			function registerActionLinkClick() {
                $body.on( 'click', actionLinkSelector, function( evt ) {
					evt.preventDefault();

					if ( 'stop_tracking' == $modal.data( 'action' ) ) {
						showModal();
					} else {
						optIn();
					}

					return false;
				});
			}

			function registerEventHandlers() {
				registerActionLinkClick();

				$modal.on( 'click', '.button-opt-out', function( evt ) {
					evt.preventDefault();

					if ( $( this ).hasClass( 'disabled' ) ) {
						return;
					}

					disableOptOutButton();
					optOut();
				});

                var isOptingOut = false;
                $modal.on( 'click', '.fs-permissions-section--header-optout', function ( evt ) {
                    if (isOptingOut) {
                        return;
                    }

                    var $optOutButton = $( this );

                    if ( 'optional' === $optOutButton.attr( 'data-type' ) ) {
                        isOptingOut = true;

                        // Remove previously added feedback element.
                        $modal.find( '.fs-switch-feedback' )
                              .remove();

                        var $switches = $optOutButton.parents( '.fs-permissions-section' )
                                                     .find( '.fs-permission .fs-switch' );

                        var switchStates = [];
                        for (var i = 0; i < $switches.length; i++) {
                            switchStates.push($($switches[i]).hasClass( 'fs-on' ));
                        }

                        $switches
                            .removeClass( 'fs-on' )
                            .addClass( 'fs-off' );

                        $switches.parents( '.fs-permission' )
                                 .addClass( 'fs-disabled' );

                        var $switchFeedback = $( '<span class="fs-switch-feedback"><i class="fs-ajax-spinner"></i></span>' );

                        $optOutButton.after( $switchFeedback )

                        updatePermissions(
                            '<?php echo $optional_permissions[0]['id'] ?>,<?php echo $optional_permissions[1]['id'] ?>',
                            false,
                            function () {
                                $switchFeedback.addClass( 'success' );
                                $switchFeedback.html( '<i class="dashicons dashicons-yes"></i> <?php echo esc_js( fs_text_inline( 'Saved', 'saved', $fs->get_slug() ) ) ?>' );
                            },
                            function () {
                                // Revert switches to their previous state.
                                for (var i = 0; i < switchStates.length; i++) {
                                    if (switchStates[i]) {
                                        $($switches[i]).addClass( 'fs-on' )
                                                       .removeClass( 'fs-disabled' )
                                                       .removeClass( 'fs-off' );
                                    }
                                }
                            },
                            function () {
                                isOptingOut = false;
                            }
                        )
                    } else {

                    }
                });

				// If the user has clicked outside the window, close the modal.
				$modal.on( 'click', '.fs-close, .button-close', function() {
					closeModal();
					return false;
				});
			}

			<?php if ( $fs->is_registered() ) : ?>
			registerEventHandlers();
			<?php endif ?>

			function showModal() {
				resetModal();

				// Display the dialog box.
                $modal.show();
				$modal.addClass( 'active' );
				$body.addClass( 'has-fs-modal' );
			}

			function closeModal() {
				$modal.removeClass( 'active' );
				$body.removeClass( 'has-fs-modal' );
                $modal.hide();
			}

			function resetOptOutButton() {
				enableOptOutButton();
				$optOutButton.text( <?php echo json_encode( $opt_out_text ) ?> );
			}

			function resetModal() {
				hideError();
				resetOptOutButton();
			}

			function optIn() {
				sendRequest();
			}

			function optOut() {
				sendRequest();
			}

			function sendRequest() {
			    var $actionLink = $( actionLinkSelector );

				$.ajax({
					url: ajaxurl,
					method: 'POST',
					data: {
						action   : ( 'stop_tracking' == action ?
								'<?php echo $fs->get_ajax_action( 'stop_tracking' ) ?>' :
								'<?php echo $fs->get_ajax_action( 'allow_tracking' ) ?>'
						),
						security : ( 'stop_tracking' == action ?
								'<?php echo $fs->get_ajax_security( 'stop_tracking' ) ?>' :
								'<?php echo $fs->get_ajax_security( 'allow_tracking' ) ?>'
						),
						module_id: moduleID,
                        _wp_http_referer: '<?php echo $fs->current_page_url() ?>'
					},
					beforeSend: function() {
						if ( 'allow_tracking' == action ) {
							$actionLink.text( '<?php fs_esc_js_echo_inline( 'Opting in', 'opting-in', $slug ) ?>...' );
						} else {
							$optOutButton.text( '<?php fs_esc_js_echo_inline( 'Opting out', 'opting-out', $slug ) ?>...' );
						}
					},
					success: function( resultObj ) {
						if ( resultObj.success ) {
							if ( 'allow_tracking' == action ) {
								action = 'stop_tracking';
								$actionLink.text( '<?php echo esc_js( $opt_out_text ) ?>' );
								showOptInAppreciationMessageAndScrollToTop();
							} else {
								action = 'allow_tracking';
								$actionLink.text( '<?php echo esc_js( $opt_in_text ) ?>' );
								closeModal();

								if ( $adminNotice.length > 0 ) {
									$adminNotice.remove();
								}
							}

							$modal.data( 'action', action );
						} else {
							showError( resultObj.error );
							resetOptOutButton();
						}
					}
				});
			}

			function enableOptOutButton() {
				$optOutButton.removeClass( 'disabled' );
			}

			function disableOptOutButton() {
				$optOutButton.addClass( 'disabled' );
			}

			function hideError() {
				$optOutErrorMessage.hide();
			}

			function showOptInAppreciationMessageAndScrollToTop() {
				$adminNotice.insertAfter( $( '#wpbody-content' ).find( ' > .wrap > h1' ) );
				window.scrollTo(0, 0);
			}

			function showError( msg ) {
				$optOutErrorMessage.find( ' > p' ).html( msg );
				$optOutErrorMessage.show();
			}

			<?php if ( $fs->is_theme() ) : ?>
			/**
			 * Add opt-in/out button to the active theme's buttons collection
			 * in the theme's extended details overlay.
			 *
			 * @author Vova Feldman (@svovaf)
			 * @since 1.2.2.7
			 */
			$('.theme-overlay').contentChange(function () {
				if (0 === $('.theme-overlay.active').length) {
					// Add opt-in/out button only to the currently active theme.
					return;
				}

				if ($('#fs_theme_opt_in_out').length > 0){
					// Button already there.
					return;
				}

				var label = (('stop_tracking' == action) ?
					    '<?php echo esc_js( $opt_out_text ) ?>' :
				        '<?php echo esc_js( $opt_in_text ) ?>'),
                    href = (('stop_tracking' != action) ?
                        '<?php echo ( $fs->is_registered() ? '' : esc_js( $reconnect_url ) ) ?>' :
                        '');

				var $actionLink = $('<a id="fs_theme_opt_in_out" href="' + encodeURI(href) + '" class="button">' + label + '</a>');

				actionLinkSelector = '#fs_theme_opt_in_out';

				$modal.data( 'action', action );

				$('.theme-wrap .theme-actions .active-theme').append($actionLink);

				if ('' === href) {
					registerActionLinkClick();
				}
			});
			<?php endif ?>

            <?php $permission_manager->require_permissions_js( true ) ?>
		});
	})( jQuery );
</script>
