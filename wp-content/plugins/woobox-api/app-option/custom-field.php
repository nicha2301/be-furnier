<?php
/**
 * Redux Framework is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Redux Framework is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Redux Framework. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package     ReduxFramework
 * @author      Dovy Paukstys
 * @version     3.1.5
 */
// Exit if accessed directly
if (!defined('ABSPATH')) exit;
// Don't duplicate me!
if (!class_exists('ReduxFramework_custom_field'))
{
    /**
     * Main ReduxFramework_custom_field class
     *
     * @since       1.0.0
     */
    class ReduxFramework_custom_field
    {

        /**
         * Field Constructor.
         *
         * Required - must call the parent constructor, then assign field and value to vars, and obviously call the render field function
         *
         * @since       1.0.0
         * @access      public
         * @return      void
         */
        function __construct($field = array() , $value = '', $parent)
        {

            $this->parent = $parent;
            $this->field = $field;
            $this->value = $value;
            if (empty($this->extension_dir))
            {
                $this->extension_dir = trailingslashit(str_replace('\\', '/', dirname(__FILE__)));
                $this->extension_url = site_url(str_replace(trailingslashit(str_replace('\\', '/', ABSPATH)) , '', $this->extension_dir));
            }
            // Set default args for this field to avoid bad indexes. Change this to anything you use.
            $defaults = array(
                'options' => array() ,
                'stylesheet' => '',
                'output' => true,
                'enqueue' => true,
                'enqueue_frontend' => true
            );
            $this->field = wp_parse_args($this->field, $defaults);

        }
        /**
         * Field Render Function.
         *
         * Takes the vars and outputs the HTML for the field in the settings
         *
         * @since       1.0.0
         * @access      public
         * @return      void
         */
        public function render()
        {

            // HTML output goes here
            
?>
            <input type="text" value="<?php echo get_home_url(); ?>" id="woobox_site_url">
            <div class="container">
                <div class="row">
                <?php
            $client_key = get_option('client_key');
            $client_secret = get_option('client_secret');
?>
                    <div class="col-lg-8">
                    <div class="form-group">
                            <h6>Consumer Key</h6>
                            <input type="text" id="client_key_new" class="form-control" value="<?php echo esc_attr($client_key); ?>">
                        </div>
                        <div class="form-group">
                            <h6>Consumer Secret</h6>
                            <input type="text" id="client_secret_new" class="form-control" value="<?php echo esc_attr($client_secret); ?>">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-8">
                        <div id="temp_token_error">
                        </div>
                        <div style="display:none;">
                        <div class="form-group">
                            <h6>Temporary Token</h6>
                            <input type="text" id="temp_token" class="form-control" readonly>
                        </div>
                        <div class="form-group">
                            <h6>Temporary Token Secret</h6>
                            <input type="text" id="temp_token_secret" class="form-control" readonly>
                        </div>
                        </div>
                        <div id="authorize_url">
                        </div>

                        <div class="form-group">
                            <a class="btn btn-primary text-white" id="check-button">Click For Verification Token</a>
                        </div>

                    </div>
                </div>
                <?php
            $auth_token = get_option('auth_token');
            $auth_token_secret = get_option('auth_token_secret');
?>
                <div class="row">
                    <div class="col-lg-8">
                        <div id="token_error">
                        </div>
                        <div class="form-group">
                            <h6>Verification token</h6>
                            <input type="text" id="oauth_verifier" class="form-control">
                        </div>

                        <div class="form-group">
                            <h6>Access token</h6>
                            <input type="text" class="form-control" id="access_token" value="<?php echo esc_attr($auth_token); ?>" readonly>
                        </div>

                        <div class="form-group">
                            <h6>Access token secret</h6>
                            <input type="text" class="form-control" id="access_token_secret" value="<?php echo esc_attr($auth_token_secret); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <a class="btn btn-primary text-white" id="final-button">Get Access Token</a>
                        </div>
                    </div>
                </div>
            </div>
                
                

                          
                
                
                
                
            <?php

        }

        /**
         * Enqueue Function.
         *
         * If this field requires any scripts, or css define this function and register/enqueue the scripts/css
         *
         * @since       1.0.0
         * @access      public
         * @return      void
         */
        public function enqueue()
        {
            // wp_enqueue_script(
            //     'redux-field-icon-select-js',
            //     $this->extension_url . 'assest/js/sample.js',
            //     array( 'jquery' ),
            //     time(),
            //     true
            // );
            // wp_enqueue_style(
            //     'redux-field-icon-select-css',
            //     $this->extension_url . 'field_custom_field.css',
            //     time(),
            //     true
            // );
            
        }

        /**
         * Output Function.
         *
         * Used to enqueue to the front-end
         *
         * @since       1.0.0
         * @access      public
         * @return      void
         */
        public function output()
        {
            if ($this->field['enqueue_frontend'])
            {
            }

        }

    }
}