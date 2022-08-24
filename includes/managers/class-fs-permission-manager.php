<?php
    /**
     * @package     Freemius
     * @copyright   Copyright (c) 2022, Freemius, Inc.
     * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
     * @since       2.5.1
     */

    if ( ! defined( 'ABSPATH' ) ) {
        exit;
    }

    /**
     * This class is responsible for managing the user permissions.
     *
     * @author Vova Feldman (@svovaf)
     * @since 2.5.1
     */
    class FS_Permission_Manager {
        /**
         * @var Freemius
         */
        private $_fs;

        /**
         * @var array<number,self>
         */
        private static $_instances = array();

        /**
         * @param Freemius $fs
         *
         * @return self
         */
        static function instance( Freemius $fs ) {
            $id = $fs->get_id();

            if ( ! isset( self::$_instances[ $id ] ) ) {
                self::$_instances[ $id ] = new self( $fs );
            }

            return self::$_instances[ $id ];
        }

        /**
         * @param Freemius $fs
         */
        protected function __construct( Freemius $fs ) {
            $this->_fs = $fs;
        }

        /**
         * @param bool    $is_license_activation
         * @param array[] $extra_permissions
         *
         * @return array[]
         */
        function get_permissions( $is_license_activation, array $extra_permissions = array() ) {
            return $is_license_activation ?
                $this->get_license_activation_permissions( $extra_permissions ) :
                $this->get_opt_in_permissions( $extra_permissions );
        }

        /**
         * @param array[] $extra_permissions
         *
         * @return array[]
         */
        function get_opt_in_permissions( array $extra_permissions = array() ) {
            // Alias.
            $fs = $this->_fs;

            $permissions = $extra_permissions;

            $permissions[] = $this->get_permission(
                'profile',
                'admin-users',
                $fs->get_text_inline( 'View Basic Profile Info', 'permissions-profile' ),
                $fs->get_text_inline( 'Your WordPress user\'s: first & last name, and email address', 'permissions-profile_desc' ),
                $fs->get_text_inline( 'Never miss important updates, get security warnings before they become public knowledge, and receive notifications about special offers and awesome new features.', 'permissions-profile_tooltip' ),
                5
            );

            $permissions[] = $this->get_permission(
                'site',
                'admin-links',
                $fs->get_text_inline( 'View Basic Website Info', 'permissions-site' ),
                $fs->get_text_inline( 'Homepage URL & title, WP & PHP versions, and site language', 'permissions-site_desc' ),
                sprintf(
                /* translators: %s: 'Plugin' or 'Theme' */
                    $fs->get_text_inline( 'To provide additional functionality that\'s relevant to your website, avoid WordPress or PHP version incompatibilities that can break your website, and recognize which languages & regions the %s should be translated and tailored to.', 'permissions-site_tooltip' ),
                    $fs->get_module_label( true )
                ),
                10
            );

            $permissions[] = $this->get_permission(
                'events',
                'admin-' . ( $fs->is_plugin() ? 'plugins' : 'appearance' ),
                sprintf( $fs->get_text_inline( 'View Basic %s Info', 'permissions-events' ), $fs->get_module_label() ),
                sprintf(
                    /* translators: %s: 'Plugin' or 'Theme' */
                    $fs->get_text_inline( 'Current %s & SDK versions, and if active or uninstalled', 'permissions-events_desc' ),
                    $fs->get_module_label( true )
                ),
                '',
                20
            );

            $permissions[] = $this->get_extensions_permission( false );

            return $this->get_sorted_permissions_by_priority( $permissions );
        }

        #--------------------------------------------------------------------------------
        #region License Activation Permissions
        #--------------------------------------------------------------------------------

        /**
         * @param array[] $extra_permissions
         *
         * @return array[]
         */
        function get_license_activation_permissions(
            array $extra_permissions = array(),
            $include_optional_label = true
        ) {
            $permissions = array_merge(
                $this->get_license_required_permissions(),
                $this->get_license_optional_permissions( $include_optional_label ),
                $extra_permissions
            );

            return $this->get_sorted_permissions_by_priority( $permissions );
        }

        /**
         * @return array[]
         */
        function get_license_required_permissions() {
            // Alias.
            $fs = $this->_fs;

            $permissions = array();

            $permissions[] = $this->get_permission(
                'essentials',
                'admin-links',
                $fs->get_text_inline( 'View License Essentials', 'permissions-essentials' ),
                $fs->get_text_inline(
                    sprintf(
                    /* translators: %s: 'Plugin' or 'Theme' */
                        'Homepage URL, %s version, SDK version',
                        $fs->get_module_label()
                    ),
                    'permissions-essentials_desc'
                ),
                sprintf(
                /* translators: %s: 'Plugin' or 'Theme' */
                    $fs->get_text_inline( 'To let you manage & control where the license is activated and ensure %s security & feature updates are only delivered to websites you authorize.', 'permissions-essentials_tooltip' ),
                    $fs->get_module_label( true )
                ),
                10
            );

            $permissions[] = $this->get_permission(
                'events',
                'admin-' . ( $fs->is_plugin() ? 'plugins' : 'appearance' ),
                sprintf( $fs->get_text_inline( 'View %s State', 'permissions-events' ), $fs->get_module_label() ),
                sprintf(
                /* translators: %s: 'Plugin' or 'Theme' */
                    $fs->get_text_inline( 'Is active, deactivated, or uninstalled', 'permissions-events_desc-paid' ),
                    $fs->get_module_label( true )
                ),
                sprintf( $fs->get_text_inline( 'So you can reuse the license when the %s is no longer active.', 'permissions-events_tooltip' ), $fs->get_module_label( true ) ),
                20
            );

            return $permissions;
        }

        /**
         * @return array[]
         */
        function get_license_optional_permissions(
            $include_optional_label = false,
            $load_default_from_storage = false
        ) {
            return array(
                $this->get_diagnostic_permission( $include_optional_label, $load_default_from_storage ),
                $this->get_extensions_permission( true, $include_optional_label, $load_default_from_storage ),
            );
        }

        /**
         * @param bool $include_optional_label
         * @param bool $load_default_from_storage
         *
         * @return array
         */
        function get_diagnostic_permission(
            $include_optional_label = false,
            $load_default_from_storage = false
        ) {
            $is_on_by_default = true;

            return $this->get_permission(
                'diagnostic',
                'wordpress-alt',
                $this->_fs->get_text_inline( 'View Diagnostic Info', 'permissions-diagnostic' ) . ( $include_optional_label ? ' (' . $this->_fs->get_text_inline( 'optional' ) . ')' : '' ),
                $this->_fs->get_text_inline( 'WordPress & PHP versions, site language & title', 'permissions-diagnostic_desc' ),
                sprintf(
                /* translators: %s: 'Plugin' or 'Theme' */
                    $this->_fs->get_text_inline( 'To avoid breaking your website due to WordPress or PHP version incompatibilities, and recognize which languages & regions the %s should be translated and tailored to.', 'permissions-diagnostic_tooltip' ),
                    $this->_fs->get_module_label( true )
                ),
                25,
                true,
                $load_default_from_storage ?
                    $this->_fs->is_diagnostic_tracking_allowed( $is_on_by_default ) :
                    $is_on_by_default
            );
        }

        #endregion

        #--------------------------------------------------------------------------------
        #region Common Permissions
        #--------------------------------------------------------------------------------

        /**
         * @param bool $is_license_activation
         * @param bool $include_optional_label
         * @param bool $load_default_from_storage
         *
         * @return array
         */
        function get_extensions_permission(
            $is_license_activation,
            $include_optional_label = false,
            $load_default_from_storage = false
        ) {
            $is_on_by_default = ! $is_license_activation;

            return $this->get_permission(
                'extensions',
                'block-default',
                $this->_fs->get_text_inline( 'View Plugins & Themes List', 'permissions-extensions' ) . ( $is_license_activation ? ( $include_optional_label ? ' (' . $this->_fs->get_text_inline( 'optional' ) . ')' : '' ) : '' ),
                $this->_fs->get_text_inline( 'Names, slugs, versions, and if active or not', 'permissions-extensions_desc' ),
                $this->_fs->get_text_inline( 'To ensure compatibility and avoid conflicts with your installed plugins and themes.', 'permissions-events_tooltip' ),
                25,
                true,
                $load_default_from_storage ?
                    $this->_fs->is_extensions_tracking_allowed( $is_on_by_default ) :
                    $is_on_by_default
            );
        }

        #endregion

        #--------------------------------------------------------------------------------
        #region Optional Permissions
        #--------------------------------------------------------------------------------

        /**
         * @return array[]
         */
        function get_newsletter_permission() {
            return $this->get_permission(
                'newsletter',
                'email-alt',
                $this->_fs->get_text_inline( 'Newsletter', 'permissions-newsletter' ),
                $this->_fs->get_text_inline( 'Updates, announcements, marketing, no spam', 'permissions-newsletter_desc' ),
                '',
                15
            );
        }

        #endregion

        #--------------------------------------------------------------------------------
        #region Rendering
        #--------------------------------------------------------------------------------

        /**
         * @param array $permission
         */
        function render_permission( array $permission ) {
            fs_require_template( 'connect/permission.php', $permission );
        }

        function require_permissions_js( $interactive = false, $inline = true ) {
            $params = array(
                'fs'          => $this->_fs,
                'inline'      => $inline,
                'interactive' => $interactive,
            );

            fs_require_template( 'js/permissions.php', $params );
        }

        #endregion

        #--------------------------------------------------------------------------------
        #region Helper Methods
        #--------------------------------------------------------------------------------

        /**
         * @param string $id
         * @param string $dashicon
         * @param string $label
         * @param string $desc
         * @param string $tooltip
         * @param string $priority
         * @param bool   $is_optional
         * @param bool   $is_on_by_default
         *
         * @return array
         */
        private function get_permission(
            $id,
            $dashicon,
            $label,
            $desc,
            $tooltip = '',
            $priority = 10,
            $is_optional = false,
            $is_on_by_default = true
        ) {
            return array(
                'id'         => $id,
                'icon-class' => $this->_fs->apply_filters( "permission_{$id}_icon", "dashicons dashicons-{$dashicon}" ),
                'label'      => $this->_fs->apply_filters( "permission_{$id}_label", $label ),
                'tooltip'    => $this->_fs->apply_filters( "permission_{$id}_tooltip", $tooltip ),
                'desc'       => $this->_fs->apply_filters( "permission_{$id}_desc", $desc ),
                'priority'   => $this->_fs->apply_filters( "permission_{$id}_priority", $priority ),
                'optional'   => $is_optional,
                'default'    => $this->_fs->apply_filters( "permission_{$id}_default", $is_on_by_default ),
            );
        }

        /**
         * @param array $permissions
         *
         * @return array[]
         */
        private function get_sorted_permissions_by_priority(array $permissions ) {
            // Allow filtering of the permissions list.
            $permissions = $this->_fs->apply_filters( 'permission_list', $permissions );

            // Sort by priority.
            uasort( $permissions, 'fs_sort_by_priority' );

            return $permissions;
        }

        #endregion
    }