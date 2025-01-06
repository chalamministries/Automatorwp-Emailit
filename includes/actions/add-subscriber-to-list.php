<?php
/**
 * Add Subscriber To List
 *
 * @package     AutomatorWP\Integrations\MailPoet\Actions\Add_Subscriber_To_List
 * @author      AutomatorWP <contact@automatorwp.com>, Ruben Garcia <rubengcdev@gmail.com>
 * @since       1.0.0
 */
// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

class AutomatorWP_MailPoet_Add_Subscriber_To_List extends AutomatorWP_Integration_Action {

    public $integration = 'mailpoet';
    public $action = 'mailpoet_add_subscriber_to_list';

    /**
     * Register the trigger
     *
     * @since 1.0.0
     */
    public function register() {

        automatorwp_register_action( $this->action, array(
            'integration'       => $this->integration,
            'label'             => __( 'Add subscriber to a list', 'automatorwp-mailpoet' ),
            'select_option'     => __( 'Add <strong>subscriber</strong> to a <strong>list</strong>', 'automatorwp-mailpoet' ),
            /* translators: %1$s: Subscriber. %2$s: List. */
            'edit_label'        => sprintf( __( 'Add %1$s to %2$s', 'automatorwp-mailpoet' ), '{subscriber}', '{list}' ),
            /* translators: %1$s: Subscriber. %2$s: List. */
            'log_label'         => sprintf( __( 'Add %1$s to %2$s', 'automatorwp-mailpoet' ), '{subscriber}', '{list}' ),
            'options'           => array(
                'subscriber' => array(
                    'from' => 'email',
                    'default' => __( 'subscriber', 'automatorwp-mailpoet' ),
                    'fields' => array(
                        'email' => array(
                            'name' => __( 'Email:', 'automatorwp-mailpoet' ),
                            'type' => 'text',
                        ),
                        'first_name' => array(
                            'name' => __( 'First name:', 'automatorwp-mailpoet' ),
                            'type' => 'text',
                        ),
                        'last_name' => array(
                            'name' => __( 'Last name:', 'automatorwp-mailpoet' ),
                            'type' => 'text',
                        ),
                        'status' => array(
                            'name' => __( 'Status:', 'automatorwp-mailpoet' ),
                            'type' => 'select',
                            'options' => array(
                                'subscribed'   => __( 'Subscribed', 'automatorwp-mailpoet' ),
                                'unsubscribed' => __( 'Unsubscribed', 'automatorwp-mailpoet' ),
                                'unconfirmed'  => __( 'Unconfirmed', 'automatorwp-mailpoet' ),
                                'inactive'     => __( 'Inactive', 'automatorwp-mailpoet' ),
                                'bounced'      => __( 'Bounced', 'automatorwp-mailpoet' ),
                            ),
                            'default' => ''
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
                            'default' => ''
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
        $first_name = $action_options['first_name'];
        $last_name = $action_options['last_name'];
        $status = $action_options['status'];
        $list_id = $action_options['list'];

        $this->result = '';

        // Bail if no email
        if( empty( $email ) ) {
            $this->result = __( 'Email subscriber field is empty', 'automatorwp-mailpoet' );
            return;
        }

        // Bail if not list selected
        if( $list_id === '' ) {
            return;
        }

        // Get the MailPoet API
        $mailpoet = \MailPoet\API\API::MP( 'v1' );

        // Setup the subscriber data
        $subscriber = array(
            'email' => $email,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'status' => $status,
        );

        try {
            // Check if email is already a subscriber
            $existing_subscriber = $mailpoet->getSubscriber( $subscriber['email'] );
            
            if( $existing_subscriber ) {
                // Add existing subscriber to the list
                $mailpoet->subscribeToList( $existing_subscriber['id'], $list_id, array( 'send_confirmation_email' => true ) );
            } 

        } catch ( \MailPoet\API\MP\v1\APIException $e ) {
            // Register the new subscriber
            $mailpoet->addSubscriber( $subscriber, array( $list_id ), array( 'send_confirmation_email' => true ) );
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

new AutomatorWP_MailPoet_Add_Subscriber_To_List();