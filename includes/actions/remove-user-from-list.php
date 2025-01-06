<?php
/**
 * Remove User From List
 *
 * @package     AutomatorWP\Integrations\MailPoet\Actions\Remove_User_From_List
 * @author      AutomatorWP <contact@automatorwp.com>, Ruben Garcia <rubengcdev@gmail.com>
 * @since       1.0.0
 */
// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

class AutomatorWP_MailPoet_Remove_User_From_List extends AutomatorWP_Integration_Action {

    public $integration = 'mailpoet';
    public $action = 'mailpoet_remove_user_from_list';

    /**
     * Register the trigger
     *
     * @since 1.0.0
     */
    public function register() {

        automatorwp_register_action( $this->action, array(
            'integration'       => $this->integration,
            'label'             => __( 'Remove user from a list', 'automatorwp-mailpoet' ),
            'select_option'     => __( 'Remove user from a <strong>list</strong>', 'automatorwp-mailpoet' ),
            /* translators: %1$s: List. */
            'edit_label'        => sprintf( __( 'Remove user from %1$s', 'automatorwp-mailpoet' ), '{list}' ),
            /* translators: %1$s: List. */
            'log_label'         => sprintf( __( 'Remove user from %1$s', 'automatorwp-mailpoet' ), '{list}' ),
            'options'           => array(
                'list' => array(
                    'from' => 'list',
                    'fields' => array(
                        'list' => array(
                            'name' => __( 'List:', 'automatorwp-mailpoet' ),
                            'type' => 'select',
                            'options_cb' => 'automatorwp_mailpoet_lists_options_cb',
                            'option_none' => true,
                            'option_none_value' => 'all',
                            'option_none_label' => __( 'all lists', 'automatorwp-mailpoet' ),
                            'default' => 'all'
                        ),
                    )
                )
            ),
        ) );

    }

    /**
     * Action execution function
     *
     * @since 1.0.0
     *
     * @param stdClass  $action             The action object
     * @param int       $user_id            The user ID
     * @param array     $action_options     The action's stored options (with tags already passed)
     * @param stdClass  $automation         The action's automation object
     */
    public function execute( $action, $user_id, $action_options, $automation ) {

        if ( ! class_exists( '\MailPoet\API\API' ) ) {
            return;
        }

        // Shorthand
        $list_id = $action_options['list'];

        // Get the MailPoet API
        $mailpoet = \MailPoet\API\API::MP( 'v1' );

        // Get the user to find its subscriber
        $user = get_userdata( $user_id );

        try {

            // Get the subscriber
            $subscriber = $mailpoet->getSubscriber( $user->user_email );

            $lists_ids = array();

            if( $list_id === 'all' ) {
                // Unsubscribe from all lists

                // Get the subscriber subscriptions
                $subscriptions = $subscriber['subscriptions'];

                if ( ! empty( $subscriptions ) ) {

                    // Setup an array with all MailPoet lists
                    $lists = $mailpoet->getLists();
                    $all_lists_ids = array();

                    foreach ( $lists as $list ) {
                        $all_lists_ids[] = $list['id'];
                    }

                    foreach ( $subscriptions as $subscription ) {

                        // Add only the lists that user can be removed from
                        if ( in_array( $subscription['segment_id'], $all_lists_ids ) ) {
                            $lists_ids[] = $subscription['segment_id'];
                        }

                    }
                }

            } else {
                // Unsubscribe from a specific list
                $lists_ids = array( $list_id );
            }

            // Bail if no lists to unsubscribe
            if( empty( $lists_ids ) ) {
                return;
            }

            // Unsubscribe user from lists
            $mailpoet->unsubscribeFromLists( $subscriber['id'], $lists_ids );

        } catch ( \MailPoet\API\MP\v1\APIException $e ) {

        }

    }

}

new AutomatorWP_MailPoet_Remove_User_From_List();