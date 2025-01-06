<?php
/**
 * Functions
 *
 * @package     AutomatorWP\MailPoet\Functions
 * @since       1.0.0
 */
// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

/**
 * Options callback for lists
 *
 * @since 1.0.0
 *
 * @param stdClass $field
 *
 * @return array
 */
function automatorwp_mailpoet_lists_options_cb( $field ) {

    $none_value = '';
    $none_label = __( 'a list', 'automatorwp-mailpoet' );
    $options = automatorwp_options_cb_none_option( $field, $none_value, $none_label );

    if ( ! class_exists( '\MailPoet\API\API' ) ) {
        return $options;
    }

    $mailpoet  = \MailPoet\API\API::MP( 'v1' );
    $lists = $mailpoet->getLists();

    if( is_array( $lists ) ) {
        foreach( $lists as $list ) {
            $options[$list['id']] = $list['name'];
        }
    }

    return $options;

}

/**
 * Options callback for forms
 *
 * @since 1.0.0
 * 
 * @param stdClass $field
 *
 * @return array
 */
function automatorwp_mailpoet_options_cb_form( $field ) {

    // Setup vars
    $value = $field->escaped_value;
    $none_value = 'any';
    $none_label = __( 'any form', 'automatorwp-mailpoet' );
    $options = automatorwp_options_cb_none_option( $field, $none_value, $none_label );
    
    if( ! empty( $value ) ) {
        if( ! is_array( $value ) ) {
            $value = array( $value );
        }

        foreach( $value as $form_id ) {

            // Skip option none
            if( $form_id === $none_value ) {
                continue;
            }
            
            $options[$form_id] = automatorwp_mailpoet_get_form_name( $form_id );
        }
    }

    return $options;

}

/**
 * Get MailPoet forms
 *
 * @since 1.0.0
 *
 * @param stdClass $field
 *
 * @return array
 */
function automatorwp_mailpoet_get_forms( ) {

    global $wpdb;

    $forms = array( );

    $results = $wpdb->get_results( $wpdb->prepare( "SELECT id, name FROM {$wpdb->prefix}mailpoet_forms WHERE deleted_at IS NULL AND status = %s",
     'enabled'
     ) );

    foreach ( $results as $form ){   

        $forms[] = array(
            'id'    => $form->id,
            'name'  => $form->name,
        );         

    }

    return $forms;

}

/**
 * Get Mailpoet form name
 *
 * @since 1.0.0
 *
 * @param int    $form_id         ID form
 * 
 */
function automatorwp_mailpoet_get_form_name( $form_id ) {

    // Empty title if no ID provided
    if( absint( $form_id ) === 0 ) {
        return '';
    }

    global $wpdb;

    return $wpdb->get_var( $wpdb->prepare(
        "SELECT name FROM {$wpdb->prefix}mailpoet_forms WHERE id = %s",
        $form_id
    ) );

}

/**
 * Get form fields values
 *
 * @since 1.0.0
 *
 * @param array $data
 *
 * @return array
 */
function automatorwp_mailpoet_get_form_fields_values( $data ) {

    $form_fields = array();

    // Loop all fields
    foreach ( $data as $key => $value ) {

        $field_name = $key;
        $field_value = $value;

        $form_fields[$field_name] = $field_value;
    }

    // Check for AutomatorWP 1.4.4
    if( function_exists( 'automatorwp_utilities_pull_array_values' ) ) {
        $form_fields = automatorwp_utilities_pull_array_values( $form_fields );
    }

    return $form_fields;

}

/**
 * Custom tags replacements
 *
 * @since 1.0.0
 *
 * @param string    $parsed_content     Content parsed
 * @param array     $replacements       Automation replacements
 * @param int       $automation_id      The automation ID
 * @param int       $user_id            The user ID
 * @param string    $content            The content to parse
 *
 * @return string
 */
function automatorwp_mailpoet_parse_automation_tags( $parsed_content, $replacements, $automation_id, $user_id, $content ) {

    $new_replacements = array();

    // Get automation triggers to pass their tags
    $triggers = automatorwp_get_automation_triggers( $automation_id );

    foreach( $triggers as $trigger ) {

        $trigger_args = automatorwp_get_trigger( $trigger->type );

        // Skip if trigger is not from this integration
        if( $trigger_args['integration'] !== 'mailpoet' ) {
            continue;
        }

        // Get the last trigger log (where data for tags replacement will be get
        $log = automatorwp_get_user_last_completion( $trigger->id, $user_id, 'trigger' );

        if( ! $log ) {
            continue;
        }

        ct_setup_table( 'automatorwp_logs' );
        $form_fields = ct_get_object_meta( $log->id, 'form_fields', true );
        ct_reset_setup_table();

        // Skip if not form fields
        if( ! is_array( $form_fields ) ) {
            continue;
        }

        // Look for form field tags
        preg_match_all( "/\{" . $trigger->id . ":form_field:\s*(.*?)\s*\}/", $parsed_content, $matches );

        if( is_array( $matches ) && isset( $matches[1] ) ) {

            foreach( $matches[1] as $field_name ) {
                // Replace {ID:form_field:NAME} by the field value
                if( isset( $form_fields[$field_name] ) ) {
                    $new_replacements['{' . $trigger->id . ':form_field:' . $field_name . '}'] = $form_fields[$field_name];
                }
            }

        }

    }

    if( count( $new_replacements ) ) {

        $tags = array_keys( $new_replacements );

        // Replace all tags by their replacements
        $parsed_content = str_replace( $tags, $new_replacements, $parsed_content );

    }

    return $parsed_content;

}
add_filter( 'automatorwp_parse_automation_tags', 'automatorwp_mailpoet_parse_automation_tags', 10, 5 );