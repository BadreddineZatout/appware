<?php
/**
 * AppWare - a micro PHP framework
 *
 * @author      Ramzi HABIB <info@appwareframework.com>
 * @copyright   2016 Ramzi HABIB
 * @link        http://www.appwareframework.com
 * @license     http://www.appwareframework.com/license
 * @version     2.0.0
 * @package     AppWare
 *
 * MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

/**
 * AppWare
 * @package AppWare
 * @author  Ramzi HABIB
 * @since   2.0.0
 */

namespace AppWare\Core\Models;

use \AppWare\Core\Form\Validation as AppWareValidation;
use \Jenssegers\Mongodb\Eloquent\Model;
use \Watson\Rememberable\Rememberable;
use \stdClass;
use \Exception;
use \MongoData;
use \DateTime;

abstract class Document extends Model
{
    use Rememberable;

    /**
     * An object that is used to manage and execute data integrity rules on this
     * object.
     *
     * @var AppWareValidation
     */
    public $validation;

    /**
     * A set of validation rules per action to pass to the validation engine.
     *
     * @var AppWareValidation
     */
    public $validationRules;

    /**
     * A set of validation rules per action to pass to the validation engine.
     *
     * 	key
     * 	type
     * 	value
     * 	index
     * 	sort
     *
     * @var array
     */
    protected static $fields;//indexes!!! ensureIndex or something with caching

    /**
     * A set of validation rules per action to pass to the validation engine.
     *
     * @var boolean
     */
    protected static $_checkSchemaFields = true;

    /**
     * Dynamically fetch the linked database source fields schema if any available before saving a given record.
     *
     * @var array
     */
    protected static $_schemaFields = array();

    protected static $_schemaChecked = array();

    /**
     * Create a new Document model instance.
     *
     * @param  array  $attributes
     * @return Document
     */
    public function __construct($attributes = array()) {
        $this->validationRules = array();
        static::$_schemaFields[get_called_class()] = static::schemaFields();
        //Add ensureIndex operations/cache
        $this->schemaCheck();
        parent::__construct($attributes);
    }

    public static function checkSchemaFields($checkSchemaFields = null) {
        if ($checkSchemaFields !== null) {
            static::$_checkSchemaFields = (boolean) $checkSchemaFields;
        }
        return (boolean) static::$_checkSchemaFields;
    }

    private static function fetchSchemaFields() {
        return static::$fields;//Could be bypassed with an array to avoid a query
    }

    private static function schemaFields() {
        if (!static::checkSchemaFields()) return array();

        $fields = static::fetchSchemaFields();

        if (!$fields)
            $fields = array();

        $schema = array();

        foreach ($fields as $field) {
            $object = new stdClass();
            $object->key = $field['key'];
            $object->type = $field['type'];
            $object->value = $field['value'];
            $object->index = $field['index'];
            $object->sort = $field['sort'];
            $schema[$field['key']] = $object;
        }

        return $schema;
    }

    private static function getSchemaFields() {
        $className = get_called_class();
        if (isset(static::$_schemaFields[$className]) && is_array(static::$_schemaFields[$className])) return static::$_schemaFields[$className];
        else {
            static::$_schemaFields[$className] = static::schemaFields();
            return isset(static::$_schemaFields[$className]) && is_array(static::$_schemaFields[$className])?static::$_schemaFields[$className]:array();
        }
    }

    private static function getSchemaChecked() {
        $className = get_called_class();
        if (!isset(static::$_schemaChecked[$className])) static::$_schemaChecked[$className] = false;
        return (boolean)static::$_schemaChecked[$className];
    }

    private static function setSchemaChecked($schemaCheck = false) {
        return static::$_schemaChecked[get_called_class()] = (boolean) $schemaCheck;
    }

    private function schemaCheck() {
        if (static::getSchemaChecked()) return true;

        $connection = $this->getConnection();
        if (!$connection || !is_object($connection)) return false;

        $schema = $connection->getSchemaBuilder();
        if (!$schema || !is_object($schema)) return false;

        $table = $this->getTable();

        $schemaFields = static::getSchemaFields();

        $compoundKeys = array();

        foreach ($schemaFields as $column => $field) {

            if (is_string($field->index)) {

                switch($field->index) {
                    case "basic":
                        try {
                            $schema->collection($table, function($collection) use($column)
                            {
                                $collection->index($column);
                            });
                        } catch(Exception $e) {}
                        break;
                    case "unique":
                        try {
                            $schema->collection($table, function($collection) use($column)
                            {
                                $collection->unique($column);
                            });
                        } catch(Exception $e) {}
                        break;
                    case "primary":
                        try {
                            $schema->collection($table, function($collection) use($column)
                            {
                                $collection->primary($column);
                            });
                        } catch(Exception $e) {}
                        break;
                }
            } elseif (is_array($field->index)) {//advanced and compound indexes
                foreach ($field->index as $key => $value) {
                    if (is_string($key) && in_array($key, array('basic', 'unique', 'primary')) && !empty($value)) {
                        $compoundKeys[$key][$value][] = $field->key;
                    } elseif (is_numeric($key) && is_array($value)) {
                        foreach($value as $valueKey) {
                            if (!in_array($valueKey, array('basic', 'unique', 'primary'))) continue;
                            $compoundKeys[$valueKey][$field->key] = $field->key;
                        }
                    }
                }
            }
        }

        if (count($compoundKeys) > 0) {
            foreach ($compoundKeys as $keyType => $keyNames) {
                foreach ($keyNames as $keyName => $column) {//$keyName ignored for now.
                    switch($keyType) {
                        case "basic":
                            try {
                                $schema->collection($table, function($collection) use($column)
                                {
                                    $collection->index($column);
                                });
                            } catch(Exception $e) {}
                            break;
                        case "unique":

                            try {
                                $schema->collection($table, function($collection) use($column)
                                {
                                    $collection->unique($column);
                                });
                            } catch(Exception $e) {}
                            break;
                        case "primary":
                            try {
                                $schema->collection($table, function($collection) use($column)
                                {
                                    $collection->primary($column);
                                });
                            } catch(Exception $e) {}
                            break;
                    }
                }
            }
        }

        return static::setSchemaChecked(true);
    }

    /**
     * @param AppWareValidation $validation
     * @return AppWareValidation
     */
    public function validation($validation = null) {
        if (is_object($validation) && (get_class($validation) === AppWareValidation::class)) {
            $this->validation = $validation;
        } else {
            if ($this->validation === null) {
                $this->validation = new AppWareValidation();
            }
        }
        return $this->validation;
    }

    /**
     * Returns the $this->validation()->validationResults() array.
     *
     * @return array
     * @todo add return type
     */
    public function validationResults() {
        return $this->validation()->errors();
    }


    /**
     * @param array $formPostValues
     * @param string $action
     * @return boolean
     * @todo add doc
     */
    public function validate($formPostValues, $action = 'default', $filter = array()) {
        if (isset($this->validationRules[$action])) {
            $this->validation()->reset();
            $this->validation()->rules($this->validationRules[$action]);
            $this->validation()->fields($formPostValues, $filter);
            return $this->validation()->validate($formPostValues);
        } else return true;
    }


    /**
     * @param array $fields
     * @param string|array $id
     * @return Document
     * @todo add doc
     */
    public static function createFields($fields = array(), $id = null) {
        if (is_array($id)) {//composite primary key
            return static::firstOrNew($id);
        } elseif($id) {
            return static::findOrNew($id);
        } else {//No composite primary key handling for now
            $instance = new static;

            $primaryKey = $instance->getKeyName();

            $primaryKeyValue = false;
            if(is_array($fields) && array_key_exists($primaryKey, $fields)) {
                $primaryKeyValue = $fields[$primaryKey];
            } elseif(is_object($fields) && property_exists($fields, $primaryKey)) {
                $primaryKeyValue = $fields->{$primaryKey};
            }

            if ($primaryKeyValue) {
                return static::findOrNew($primaryKeyValue);
            }
        }

        return new static;
    }

    /**
     * @param array $fields
     * @param string|boolean $validation
     * @return boolean
     * @todo add doc
     */
    public function saveFields($fields = array(), $validation = false) {

        $insert = $this->exists ? false : true;

        if ($insert === false) {
            $result = $this->updateFields($fields, $validation?$validation:'update');
        } else {
            $result = $this->insertFields($fields, $validation?$validation:'insert');
        }
        return $result;
    }

    /**
     * @param array $fields
     * @param string|boolean $validation
     * @return mixed
     * @todo add doc
     */
    public function insertFields($fields = array(), $validation = 'insert') {

        $result = false;
        if ($this->validate($fields, $validation)) {
            // Strip out fields that aren't in the schema.
            // This is done after validation to allow custom validations to work.
            if (static::checkSchemaFields()) {
                $schemaFields = static::getSchemaFields();
                //$fields = array_intersect_key($fields, $schemaFields);
                $validFields = array();
                foreach($schemaFields as $field => $schemaField) {
                    $validFields = array_merge($validFields, static::_preg_grep_keys('#^' . str_replace(array('\*'), array('([a-zA-Z0-9]*)'), preg_quote($field, '#')) . '$#', $fields), $validFields);
                }
                $fields = array_intersect_key($fields, $validFields);
            }

            foreach($fields as $name => $field) {
                if (strpos($name, '.') !== false) {
                    $fieldParts = explode('.', $name);
                    $newName = array_shift($fieldParts);
                    $newField = implode('.', $fieldParts);
                    if (!isset($fields[$newName])) {
                        $fields[$newName] = array();
                    }
                    array_set($fields[$newName], $newField, $field);
                    unset($fields[$name]);
                }
            }

            try {
                foreach($fields as $key => $value) {

                    if (is_array($value) || $value instanceof \ArrayAccess) {
                        array_walk_recursive($value, function (&$itemValue, $itemKey) {
                            if ($itemValue instanceof MongoDate)
                            {
                                $itemValue = $itemValue;
                            }
                            elseif ($itemValue instanceof DateTime)
                            {
                                $itemValue = new MongoDate($itemValue->getTimestamp());
                            }
                        });
                    }
                    elseif ($value instanceof MongoDate)
                    {
                        $value = $value;
                    }
                    elseif ($value instanceof DateTime)
                    {
                        $value = new MongoDate($value->getTimestamp());
                    }

                    $this->{$key} = $value;
                }

                $result = $this->save();
            } catch (Exception $e) {
                $this->validation()->error('*', $e->getCode() . ' : ' . $e->getMessage());
                $result = false;
            }
        }

        return $result;
    }


    /**
     * @param array $fields
     * @param string|boolean $validation
     * @return mixed
     * @todo add doc
     */
    public function updateFields($fields = array(), $validation = 'update') {

        $result = false;
        if ($this->validate($fields, $validation)) {
            // Strip out fields that aren't in the schema.
            // This is done after validation to allow custom validations to work.
            if (static::checkSchemaFields()) {
                $schemaFields = static::getSchemaFields();
                //$fields = array_intersect_key($fields, $schemaFields);
                $validFields = array();
                foreach($schemaFields as $field => $schemaField) {
                    $validFields = array_merge($validFields, static::_preg_grep_keys('#^' . str_replace(array('\*'), array('([a-zA-Z0-9]*)'), preg_quote($field, '#')) . '$#', $fields), $validFields);
                }
                $fields = array_intersect_key($fields, $validFields);
            }

            foreach($fields as $name => $field) {
                if (strpos($name, '.') !== false) {
                    $fieldParts = explode('.', $name);
                    $newName = array_shift($fieldParts);
                    $newField = implode('.', $fieldParts);
                    if (!isset($fields[$newName])) {
                        $fields[$newName] = array();
                    }
                    array_set($fields[$newName], $newField, $field);
                    unset($fields[$name]);
                }
            }

            try {

                foreach($fields as $key => $value) {

                    if (is_array($value) || $value instanceof \ArrayAccess) {
                        array_walk_recursive($value, function (&$itemValue, $itemKey) {
                            if ($itemValue instanceof MongoDate)
                            {
                                $itemValue = $itemValue;
                            }
                            elseif ($itemValue instanceof DateTime)
                            {
                                $itemValue = new MongoDate($itemValue->getTimestamp());
                            }
                        });
                    }

                    try {
                        $oldValue = $this->getOriginal($key, $value);

                        if (is_array($oldValue) && is_array($value))
                        {
                            $this->{$key} = array_replace_recursive($oldValue, $value);
                        }
                        elseif ($value instanceof MongoDate)
                        {
                            $this->{$key} = $value;
                        }
                        elseif ($value instanceof DateTime)
                        {
                            $this->{$key} = new MongoDate($value->getTimestamp());
                        }
                        elseif (is_array($oldValue) && is_object($value))
                        {
                            $this->{$key} = array_replace_recursive($oldValue, (array) $value);
                        }
                        elseif (is_object($oldValue) && is_array($value))
                        {
                            $this->{$key} = (object) array_replace_recursive((array) $oldValue, $value);
                        }
                        elseif (is_object($oldValue) && is_object($value))
                        {
                            $this->{$key} = (object) array_replace_recursive((array) $oldValue, (array) $value);
                        }
                        else
                        {
                            throw new Exception("Merging Array or object data types only.");
                        }
                    } catch(Exception $e) {
                        $this->{$key} = $value;
                    }
                }
                $result = $this->save();
            } catch (Exception $e) {
                $this->validation()->error('*', $e->getCode() . ' : ' . $e->getMessage());
                $result = false;
            }
        }
        return $result;
    }

    protected static function _preg_grep_keys($pattern, $input, $flags = 0) {
        return array_intersect_key($input, array_flip(preg_grep($pattern, array_keys($input), $flags)));
    }
}
