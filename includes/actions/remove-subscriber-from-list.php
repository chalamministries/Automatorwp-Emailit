<?php
/**
 * Remove Subscriber From List
 *
 * @package     AutomatorWP\Integrations\MailPoet\Actions\Remove_Subscriber_From_List
 * @author      AutomatorWP <contact@automatorwp.com>, Ruben Garcia <rubengcdev@gmail.com>
 * @since       1.0.0
 */
// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

class AutomatorWP_MailPoet_Remove_Subscriber_From_List extends AutomatorWP_Integration_Action {

    public $integration = 'mailpoet';
    public $action = 'mailpoet_remove_subscriber_from_list';

    /**
     * Register the trigger
     *
     * @since 1.0.0
     */
    public function register() {

        automatorwp_register_action( $this->action, array(
            'integration'       => $this->integration,
            'label'             => __( 'Remove subscriber from a list', 'automatorwp-mailpoet' ),
            'select_option'     => __( 'Remove subscriber from a <strong>list</strong>', 'automatorwp-mailpoet' ),
            /* translators: %1$s: Subscriber. %2$s: List. */
            'edit_label'        => sprintf( __( 'Remove %1$s from %2$s', 'automatorwp-mailpoet' ), '{subscriber}', '{list}' ),
            /* translators: %1$s: Subscriber. %2$s: List. */
            'log_label'         => sprintf( __( 'Remove %1$s from %2$s', 'automatorwp-mailpoet' ), '{subscriber}', '{list}' ),
            'options'           => array(
                'subscriber' => array(
                    'from' => 'email',
                    'default' => __( 'subscriber', 'automatorwp-mailpoet' ),
                    'fields' => array(
                        'email' => array(
                            'name' => __( 'Email:', 'automatorwp-mailpoet' ),
                            'type' => 'text',
                        ),
                    )
                ),
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
        $email = $action_options['email'];
        $list_id = $action_options['list'];

        $this->result = '';

        // Bail if no email
        if( empty( $email ) ) {
            $this->result = __( 'Email subscriber field is empty', 'automatorwp-mailpoet' );
            return;
        }

        // Get the MailPoet API
        $mailpoet = \MailPoet\API\API::MP( 'v1' );

        try {

            // Get the subscriber
            $subscriber = $mailpoet->getSubscriber( $email );

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

    /**
     * Register required hooks
     *
     * @since 1.0.0
     */
    public function hooks() {

        // Log meta data
        add_filter( 'automatorwp_user_completed_action_log_meta', array( $this, 'log_meta' ), 10, 5 );

        // Log fields
        add_filter( 'automatorwp_log_fields', array( $this, 'log_fields' ), 10, 5 );

        parent::hooks();

    }

    /**
     * Action custom log meta
     *
     * @since 1.0.0
     *
     * @param array     $log_meta           Log meta data
     * @param stdClass  $action             The action object
     * @param int       $user_id            The user ID
     * @param array     $action_options     The action's stored options (with tags already passed)
     * @param stdClass  $automation         The action's automation object
     *
     * @return array
     */
    public function log_meta( $log_meta, $action, $user_id, $action_options, $automation ) {

        // Bail if action type don't match this action
        if( $action->type !== $this->action ) {
            return $log_meta;
        }

        // Store the action's result
        $log_meta['result'] = $this->result;

        return $log_meta;
    }

    /**
     * Action custom log fields
     *
     * @since 1.0.0
     *
     * @param array     $log_fields The log fields
     * @param stdClass  $log        The log object
     * @param stdClass  $object     The trigger/action/automation object attached to the log
     *
     * @return array
     */
    public function log_fields( $log_fields, $log, $object ) {

        // Bail if log is not assigned to an action
        if( $log->type !== 'action' ) {
            return $log_fields;
        }

        // Bail if action type don't match this action
        if( $object->type !== $this->action ) {
            return $log_fields;
        }

        $log_fields['result'] = array(
            'name' => __( 'Result:', 'automatorwp-activecampaign' ),
            'type' => 'text',
        );

        return $log_fields;
    }

}

new AutomatorWP_MailPoet_Remove_Subscriber_From_List();