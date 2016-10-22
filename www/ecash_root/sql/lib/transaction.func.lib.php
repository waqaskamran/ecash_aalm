<?php

/**
 *  Quick reference:
 *
 *  schedule_event($db <details_array>)
 *      Array Keys:
 *          'context'
 *          'event_type'
 *          'mechanism'
 *          'application_id'
 *          'date_scheduled_for'
 *          'notes'
 *          'parent_transaction_id'
 *          'event_amount_array'
 *              Array Keys:
 *                  'amount'
 *                  'event_amount_type'
 *  get_all_events($db <application_id>) TODO
 *  remove_scheduled_events($db <id> [by_application=FALSE] [error_on_zero_count=TRUE])
 *  lookup_context_id($db <name_short>)
 *  lookup_context_name($db <name_short>)
 *  lookup_context_name($db <id>)
 *  lookup_mechanism_id($db <name_short>)
 *  lookup_mechanism_name($db <name_short>)
 *  lookup_mechanism_name($db <id>)
 *  lookup_event_type_id($db <name_short>)
 *  lookup_event_type_name($db <name_short>)
 *  lookup_event_type_name($db <id>)
 *  lookup_event_amount_type_id($db <name_short>)
 *  lookup_event_amount_type_name($db <name_short>)
 *  lookup_event_amount_type_name($db <id>)
 *  lookup_transaction_status_id($db <mechanism_name_short> <name_short>)
 *  lookup_transaction_status_id($db <mechanism_id> <name_short>)
 *  lookup_transaction_status_name($db <id>)
 *  lookup_transaction_status_name($db <name_short> <mechanism_name_short>)
 *  lookup_transaction_status_name($db <name_short> <mechanism_id>)
 *  lookup_transactional_name($db <company_id> <array>)
 *      Array Keys:
 *          'mechanism_name_short'
 *          'context_name_short'
 *          'event_type_name_short'
 *          'event_amount_type_name_short'
 *          'mechanism_id'
 *          'context_id'
 *          'event_type_id'
 *          'event_amount_type_id'
 *          'event_sum_cardinality'
 */

function schedule_event($db, $details_array)
{
    /**
     * @param $db Resource object
     *
     * @param $details_array = array(
     *      'context'                       =>  Context name to look up
     *      'event_type'                    =>  What is the purpose of the transaction
     *      'mechanism'                     =>  By what means should the money move
     *      'application_id'                =>  Whose transaction this is, REQUIRED
     *      'date_scheduled_for'            =>  When is the event supposed to happen (MySQL DATE)
     *      'notes'                         =>  User-entered notes, if created by hand
     *      'parent_transaction_id'         =>  Optional parent id, use NULL if this transaction has no specific "cause"
     *      'event_amount_array' => array(
     *          index => array(
     *              'amount'                =>  Dollar value, plus or minus
     *              'event_amount_type'     =>  Event amount type name to look up
     *              ),
     *          ),
     *      );
     *
     */

    if(!is_array($details_array))
    {
        throw(new Exception("details_array is not an array!"));
    }

    foreach($details_array as $key => $value)
    {
        switch($key)
        {
            case 'date_scheduled_for':
                if(!is_string($value) || !strtotime($value))
                {
                    throw(new Exception("$key should be a valid date string"));
                }
                break;

            case 'notes':
                if(!is_string($value))
                {
                    throw(new Exception("$key should be a string"));
                }
                break;

            case 'application_id':
                if(!is_numeric($value) || 0 == $value)
                {
                    throw(new Exception("$key should be numeric"));
                }
                break;

            case 'parent_transaction_id':
                if(NULL !== $value && !intval($value))
                {
                    throw(new Exception("$key should be integer or null"));
                }
                break;

            case 'event_amount_array':
                if(!is_array($value))
                {
                    throw(new Exception("$key should be an array"));
                }
                if(!count($value))
                {
                    throw(new Exception("$key needs to have at least one row"));
                }
                foreach($value as $amount_row)
                {
                    if  (   !is_array(          $amount_row                     )
                        ||  !isset(             $amount_row['amount']           )
                        ||  !is_numeric(        $amount_row['amount']           )
                        ||  !floatval(          $amount_row['amount']           )
                        ||  !isset(             $amount_row['event_amount_type'])
                        ||  (   (   !is_string( $amount_row['event_amount_type'])
                                ||  "" ==       $amount_row['event_amount_type']
                                )
                            &&  (   !is_numeric($amount_row['event_amount_type'])
                                ||  0 ==        $amount_row['event_amount_type']
                                )
                            )
                        )
                    {
                        throw(new Exception("Invalid amount row"));
                    }
                }
                break;

            case 'context':
            case 'event_type':
            case 'mechanism':
                if  (   (   !is_string( $value )
                        ||  "" == $value
                        )
                    &&  (   !is_numeric( $value )
                        ||  0 == $value
                        )
                    )
                {
                    throw(new Exception("$key should either be the name_short, or the id"));
                }
                break;

            default:
                throw(new Exception("Unknown key: $key"));
                break;
        }
    }

    foreach( array( 'date_scheduled_for' , 'application_id' ,
                'event_amount_array' , 'context' , 'event_type' ,
                'mechanism' ) as $key )
    {
        if(!isset($details_array[$key]))
        {
            throw(new Exception("Missing $key"));
        }
    }

    if(!is_numeric($details_array['context']))
    {
        $details_array['context'] =
            lookup_context_id($db, $details_array['context']);
    }

    if(!is_numeric($details_array['event_type']))
    {
        $details_array['event_type'] =
            lookup_event_type_id($db, $details_array['event_type']);
    }

    if(!is_numeric($details_array['mechanism']))
    {
        $details_array['mechanism'] =
            lookup_mechanism_id($db, $details_array['mechanism']);
    }

    foreach($details_array['event_amount_array'] as $row_id => $amount_row)
    {
        if(!is_numeric($amount_row['event_amount_type']))
        {
            $details_array['event_amount_array'][$row_id]['event_amount_type'] =
                lookup_event_amount_type_id
                    ($db, $amount_row['event_amount_type']);
        }
    }

    $db->beginTransaction();

    $event = array();
    foreach( $details_array as $key => $value )
    {
        if( 'event_amount_array' != $key )
        {
            $event[] = "`$key` = ".$db->quote($value);
        }
    }
    $event = join(" , ",$event);

    $query = "
        INSERT INTO `event`
        SET         $event
        ";
    $rc = $db->query($query);
    if(1 != $rc)
    {
        $db->rollBack();
        throw(new Exception("Error inserting event record"));
    }

    $event_id = $db->lastInsertId();

    foreach( $details_array['event_amount_array'] as $amount_row )
    {
        $event_amount = array();
        foreach( $amount_row as $key => $value )
        {
            $event_amount[] = "`$key` = ".$db->quote($value);
        }
        $event_amount[] = "`event_id` = ".$db->quote($event_id);
        $event_amount = join(" , ",$event);

        $query = "
            INSERT INTO `event_amount`
            SET         $event_amount
            ";
        $rc = $db->exec($query);
        if(1 != $rc)
        {
            $db->rollBack();
            throw(new Exception("Error inserting event amount record"));
        }
    }

    $db->commit();

    return(NULL);
}

function remove_scheduled_events($db, $id, $by_application=FALSE, $error_on_zero_count=TRUE)
{
    /**
     * Given either an application id, or an event id (defaults to event),
     * will delete the event causing the trigger to clean up the
     * event_amount table as well.
     *
     * If the optional $error_on_zero_count argument is set, then an error
     * will be thrown if no records are deleted (meaning that they must
     * have no events or only events which are linked to transactions)
     */

    $id = $db->quote($id);
    $col = $by_application ? "application_id" : "event_id";

    $query = "
        DELETE FROM
            `event`
        USING
            `event`
            LEFT JOIN
                `event_amount` ON (
                        `event`.`event_id` = `event_amount`.`event_id`
                        AND `event_amount`.`transaction_id` IS NOT NULL
                        )
        WHERE
            `event`.`$col` = '$id'
            AND `event_amount`.`event_amount_id` IS NULL
        ";
    $rc = $db->exec($query);

    if(0 == $rc)
    {
        throw(new Exception("No rows deleted"));
    }

    return(NULL);
}

function lookup_simple($db, $get_column, $table_name, $where_array)
{
    /**
     * This is an "internal" function used by other methods in this file,
     * but may be used directly.  It takes a column to be returned from a
     * table name with a given where clause, and expects to get a SINGLE
     * row out of the database.  Any more or less will produce an error.
     */

    $where_join = array();
    foreach($where_array as $column => $value)
    {
        $new = "`{$column}` ";

        if(NULL === $value)
        {
            $new .= "IS NULL";
        }
        else
        {
            $new .= "= ".$db->quote($value);
        }

        $where_join[] = $new;
    }
    $where_join = join(" AND ", $where_join);

    $query = "
        SELECT  `{$get_column}`  AS `value`
        FROM    `{$table_name}`
        WHERE   $where_join
        ";
    $result = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

    if  (   !is_array($result)
        ||  1 != count($result)
        ||  !isset($result[0])
        ||  1 != count($result[0])
        ||  !isset($result[0]['value'])
        )
    {
        throw(new Exception("Unexpected return"));
    }

    return($result[0]['value']);
}

function lookup_context_id($db, $name_short)
{
    $column_name = 'context_id';
    $table_name = 'context';

    $lookup_array = array();
    $lookup_array['name_short'] = $name_short;

    $result = lookup_simple($db, $column_name, $table_name, $lookup_array);

    return($result);
}

function lookup_context_name($db, $reference)
{
    $column_name = 'name';

    $table_name = 'context';

    $lookup_array = array();
    if(is_numeric($reference))
    {
        $lookup_array['context_id'] = $reference;
    }
    else
    {
        $lookup_array['name_short'] = $reference;
    }

    $result = lookup_simple($db, $column_name, $table_name, $lookup_array);

    return($result);
}

function lookup_mechanism_id($db, $name_short)
{
    $column_name = 'mechanism_id';
    $table_name = 'mechanism';

    $lookup_array = array();
    $lookup_array['name_short'] = $name_short;

    $result = lookup_simple($db, $column_name, $table_name, $lookup_array);

    return($result);
}

function lookup_mechanism_name($db, $reference)
{
    $column_name = 'name';

    $table_name = 'mechanism';

    $lookup_array = array();
    if(is_numeric($reference))
    {
        $lookup_array['mechanism_id'] = $reference;
    }
    else
    {
        $lookup_array['name_short'] = $reference;
    }

    $result = lookup_simple($db, $column_name, $table_name, $lookup_array);

    return($result);
}

function lookup_event_type_id($db, $name_short)
{
    $column_name = 'event_type_id';
    $table_name = 'event_type';

    $lookup_array = array();
    $lookup_array['name_short'] = $name_short;

    $result = lookup_simple($db, $column_name, $table_name, $lookup_array);

    return($result);
}

function lookup_event_type_name($db, $reference)
{
    $column_name = 'name';

    $table_name = 'event_type';

    $lookup_array = array();
    if(is_numeric($reference))
    {
        $lookup_array['event_type_id'] = $reference;
    }
    else
    {
        $lookup_array['name_short'] = $reference;
    }

    $result = lookup_simple($db, $column_name, $table_name, $lookup_array);

    return($result);
}

function lookup_event_amount_type_id($db, $name_short)
{
    $column_name = 'event_amount_type_id';
    $table_name = 'event_amount_type';

    $lookup_array = array();
    $lookup_array['name_short'] = $name_short;

    $result = lookup_simple($db, $column_name, $table_name, $lookup_array);

    return($result);
}

function lookup_event_amount_type_name($db, $reference)
{
    $column_name = 'name';

    $table_name = 'event_amount_type';

    $lookup_array = array();
    if(is_numeric($reference))
    {
        $lookup_array['event_amount_type_id'] = $reference;
    }
    else
    {
        $lookup_array['name_short'] = $reference;
    }

    $result = lookup_simple($db, $column_name, $table_name, $lookup_array);

    return($result);
}

function lookup_transaction_status_id($db, $mechanism, $name_short)
{
    $column_name = 'transaction_status_id';
    $table_name = 'transaction_status';

    $lookup_array = array();
    if(is_numeric($mechanism))
    {
        $lookup_array['mechanism_id'] = $mechanism;
    }
    else
    {
        $lookup_array['mechanism_id'] = lookup_mechanism_id($db, $mechanism);
    }
    $lookup_array['name_short'] = $name_short;

    $result = lookup_simple($db, $column_name, $table_name, $lookup_array);

    return($result);
}

function lookup_transaction_status_name($db, $status_reference, $mechanism_reference = NULL)
{
    $column_name = 'name';

    $table_name = 'transaction_status';

    $lookup_array = array();

    if(NULL !== $mechanism_reference)
    {
        if(is_numeric($mechanism_reference))
        {
            $lookup_array['mechanism_id'] = $mechanism_reference;
        }
        else
        {
            $lookup_array['mechanism_id'] = lookup_mechanism_id($db, $mechanism_reference);
        }
    }

    if(is_numeric($status_reference))
    {
        $lookup_array['transaction_status_id'] = $status_reference;
    }
    elseif(isset($lookup_array['mechanism_id']))
    {
        $lookup_array['name_short'] = $status_reference;
    }
    else
    {
        throw(new Exception("If you use a name_short for the transaction_status, you must provide an indicator of which mechanism to look up"));
    }

    $result = lookup_simple($db, $column_name, $table_name, $lookup_array);

    return($result);
}

function lookup_transactional_name($db, $company_id, $lookup_array)
{
    if(!is_array($lookup_array))
    {
        throw(new Exception("lookup_array provided is not an array"));
    }

    foreach($lookup_array as $key => $value)
    {
        switch($key)
        {
            case 'mechanism_name_short':
                $lookup_array['mechanism_id'] =
                    lookup_mechanism_id($db, $value);
                unset($lookup_array['mechanism_name_short']);
                break;

            case 'context_name_short':
                $lookup_array['context_id'] =
                    lookup_context_id($db, $value);
                unset($lookup_array['context_name_short']);
                break;

            case 'event_type_name_short':
                $lookup_array['event_type_id'] =
                    lookup_event_type_id($db, $value);
                unset($lookup_array['event_type_name_short']);
                break;

            case 'event_amount_type_name_short':
                $lookup_array['event_amount_type_id'] =
                    lookup_event_amount_type_id($db, $value);
                unset($lookup_array['event_amount_type_name_short']);
                break;

            case 'mechanism_id':
            case 'context_id':
            case 'event_type_id':
            case 'event_amount_type_id':
            case 'event_sum_cardinality':
                break;

            default:
                throw(new Exception("Invalid key in lookup_array"));
                break;
        }
    }

    $lookup_array['company_id'] = $company_id;

    $where_join = array();
    foreach($lookup_array as $column => $value)
    {
        $new = "`{$column}` ";

        if(NULL === $value)
        {
            $new .= "IS NULL";
        }
        else
        {
            $new .= "= ".$db->quote($value);
        }

        $where_join[] = $new;
    }
    $where_join = join(" AND ", $where_join);

    $query = "
        SELECT  `name`  AS `name`
        FROM    `transactional_names`
        WHERE   $where_join
        ";
    $result = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

    if  (   !is_array($result)
        ||  1 != count($result)
        ||  !isset($result[0])
        ||  1 != count($result[0])
        ||  !isset($result[0]['name'])
        )
    {
        throw(new Exception("Unexpected return"));
    }

    return($result[0]['name']);
}

?>
