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
use \Illuminate\Database\Eloquent\Model;
use \Illuminate\Database\Eloquent\Builder;
use \Watson\Rememberable\Rememberable;
use \stdClass;
use \Exception;

abstract class Base extends Model
{
    use Rememberable;

    /**
     * The composite primary key for the model.
     *
     * @var array
     */
    protected $compositePrimaryKey = array('id');

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
     * @var boolean
     */
    protected static $_checkSchemaFields = true;

    /**
     * Dynamically fetch the linked database source fields schema if any available before saving a given record.
     *
     * @var array
     */
    protected static $_schemaFields = array();

    /**
     * Create a new Base model instance.
     *
     * @param  array  $attributes
     * @return Base
     */
    public function __construct($attributes = array()) {
        $this->validationRules = array();
        parent::__construct($attributes);
    }

    public static function checkSchemaFields($checkSchemaFields = null) {
        if ($checkSchemaFields !== null) {
            static::$_checkSchemaFields = (boolean) $checkSchemaFields;
        }
        return (boolean) static::$_checkSchemaFields;
    }

    public function getColumnsNames() {
        return array_unique($this->getConnection()->getSchemaBuilder()->getColumnListing($this->table));
    }

    private function fetchSchemaFields() {
        return $this->getColumnsNames();
    }

    private function schemaFields() {
        if (!static::checkSchemaFields()) return array();

        $fields = $this->fetchSchemaFields();

        if (!$fields)
            $fields = array();

        $schema = array();

        foreach ($fields as $field) {

            /*$type = $field->type;
            $unsigned = stripos($type, 'unsigned') !== false;
            $length = '';
            $precision = '';
            $parentheses = strpos($type, '(');
            $enum = '';
   
            if ($parentheses !== false) {
               $lengthParts = explode(',', substr($type, $parentheses + 1, -1));
               $type = substr($type, 0, $parentheses);
   
               if (strcasecmp($type, 'enum') == 0) {
                  $enum = array();
                  foreach($lengthParts as $value)
                     $enum[] = trim($value, "'");
               } else {
                  $length = trim($lengthParts[0]);
                  if(count($lengthParts) > 1)
                     $precision = trim($lengthParts[1]);
               }
            }*/


            $object = new stdClass();
            //$object->name = $field->field;
            $object->name = $field;
            /*$object->primaryKey = ($field->key == 'PRI' ? true : false);
            $object->type = $type;
            //$object->type2 = $field->type;
            $object->unsigned = $unsigned;
            $object->allowNull = ($field->null == 'YES');
            $object->default = $field->default;
            $object->length = $length;
            $object->precision = $precision;
            $object->enum = $enum;
            $object->keyType = null; // give placeholder so it can be defined again.
            $object->autoIncrement = strpos($field->extra, 'auto_increment') === false ? false : true;*/
            //$schema[$field->field] = $object;
            $schema[$field] = $object;
        }

        return $schema;
    }

    private function getSchemaFields() {
        $className = get_called_class();
        if (isset(static::$_schemaFields[$className]) && is_array(static::$_schemaFields[$className])) return static::$_schemaFields[$className];
        else {
            static::$_schemaFields[$className] = $this->schemaFields();
            return isset(static::$_schemaFields[$className]) && is_array(static::$_schemaFields[$className])?static::$_schemaFields[$className]:array();
        }
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
     * Returns the $this->validation->validationResults() array.
     *
     * @return array
     */
    public function validationResults() {
        return $this->validation()->errors();
    }


    /**
     * @param array $formPostValues
     * @param string $action
     * @param array $filter
     * @return boolean
     * @todo add doc
     */
    public function validate($formPostValues, $action = 'default', $filter = array()) {
        if (isset($this->validationRules[$action])) {
            $this->validation()->reset();
            $this->validation()->rules($this->validationRules[$action]);
            $this->validation()->fields($formPostValues, $filter);
            return $this->validation()->validate();
        } else return true;
    }

    /**
     * @param array $fields
     * @param string|array $id
     * @return Base
     * @todo add doc
     */
    public static function createFields($fields = array(), $id = null) {
        if (is_array($id)) {//composite primary key
            try {
                return static::firstOrNew($id);
            } catch (Exception $e) {}//No error & validation handling for now?
        } elseif($id) {
            try {
                return static::findOrNew($id);
            } catch (Exception $e) {}//No error & validation handling for now?
        } else {

            $primaryKey = false;
            $compositePrimaryKey = array();

            try {
                $instance = new static;
                $compositePrimaryKey = $instance->getCompositeKeyName();
            } catch (Exception $e) {}//No error & validation handling for now?

            if (is_array($compositePrimaryKey) && count($compositePrimaryKey) > 1) {//Composite primary key handling ...

                $compositePrimaryKeyValue = array();
                foreach($compositePrimaryKey as $primaryKey) {
                    if(is_array($fields) && array_key_exists($primaryKey, $fields)) {
                        $primaryKeyValue = $fields[$primaryKey];
                    } elseif(is_object($fields) && property_exists($fields, $primaryKey)) {
                        $primaryKeyValue = $fields->{$primaryKey};
                    } else {
                        $primaryKeyValue = false;
                    }

                    if ($primaryKeyValue) {
                        $compositePrimaryKeyValue[$primaryKey] = $primaryKeyValue;
                    }
                }

                if (count($compositePrimaryKey) == count($compositePrimaryKeyValue)) {
                    try {
                        return static::firstOrNew($compositePrimaryKeyValue);
                    } catch (Exception $e) {}//No error & validation handling for now?
                }
            } else {
                try {
                    $primaryKey = $instance->getKeyName();
                } catch (Exception $e) {}//No error & validation handling for now?

                $primaryKeyValue = false;
                if(is_array($fields) && array_key_exists($primaryKey, $fields)) {
                    $primaryKeyValue = $fields[$primaryKey];
                } elseif(is_object($fields) && property_exists($fields, $primaryKey)) {
                    $primaryKeyValue = $fields->{$primaryKey};
                }

                if ($primaryKeyValue) {
                    try {
                        return static::findOrNew($primaryKeyValue);
                    } catch (Exception $e) {}//No error & validation handling for now?
                }
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
            $fields = static::checkSchemaFields() ? array_intersect_key($fields, $this->getSchemaFields()) : $fields;
            //unset($fields[static::$_primaryKey]);

            try {
                foreach($fields as $key => $value) {
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
            $fields = static::checkSchemaFields() ? array_intersect_key($fields, $this->getSchemaFields()) : array();
            //unset($fields[static::$_primaryKey]);

            try {
                foreach($fields as $key => $value) {
                    $this->{$key} = $value;
                }

                $compositeKey = $this->getCompositeKeyName();

                if (is_array($compositeKey) && count($compositeKey) > 1) {
                    $result = $this->performCompositeUpdate($this->newQuery());
                } else {
                    $result = $this->save();
                }
            } catch (Exception $e) {
                $this->validation()->error('*', $e->getCode() . ' : ' . $e->getMessage());
                $result = false;
            }
        }
        return $result;
    }

    /**
     * Perform a model update operation.
     *
     * @param  Builder  $query
     * @return bool
     */
    protected function performCompositeUpdate(Builder $query)
    {
        $dirty = $this->getDirty();

        if (count($dirty) > 0)
        {
            // If the updating event returns false, we will cancel the update operation so
            // developers can hook Validation systems into their models and cancel this
            // operation if the model does not pass validation. Otherwise, we update.
            if ($this->fireModelEvent('updating') === false)
            {
                return false;
            }

            // First we need to create a fresh query instance and touch the creation and
            // update timestamp on the model which are maintained by us for developer
            // convenience. Then we will just continue saving the model instances.
            if ($this->timestamps)
            {
                $this->updateTimestamps();
            }

            // Once we have run the update operation, we will fire the "updated" event for
            // this model instance. This will allow developers to hook into these after
            // models are updated, giving them a chance to do any special processing.
            $dirty = $this->getDirty();

            $this->setCompositeKeysForSaveQuery($query)->update($dirty);

            $this->fireModelEvent('updated', false);
        }

        return true;
    }

    /**
     * Set the keys for a save update query.
     *
     * @param  Builder  $query
     * @return Builder
     */
    protected function setCompositeKeysForSaveQuery(Builder $query)
    {
        $compositeKey = $this->getCompositeKeyForSaveQuery();
        foreach($compositeKey as $compositeKeyName => $compositeKeyValue) {
            $query->where($compositeKeyName, '=', $compositeKeyValue);
        }

        return $query;
    }

    /**
     * Get the primary key value for a save query.
     *
     * @return mixed
     */
    protected function getCompositeKeyForSaveQuery()
    {
        $compositeKey = array();

        $compositeKeyNames = $this->getCompositeKeyName();

        foreach($compositeKeyNames as $compositeKeyName) {
            if (isset($this->original[$compositeKeyName]))
            {
                $compositeKey[$compositeKeyName] = $this->original[$compositeKeyName];
            }
            else
            {
                $compositeKey[$compositeKeyName] = $this->getAttribute($compositeKeyName);
            }
        }

        return $compositeKey;
    }

    /**
     * Get the primary key for the model.
     *
     * @return array
     */
    public function getCompositeKeyName()
    {
        return $this->compositePrimaryKey ?: array();
    }

}
