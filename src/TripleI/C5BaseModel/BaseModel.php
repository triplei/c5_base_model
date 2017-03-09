<?php
/**
 * @author Dan Klassen <dan@triplei.ca>
 */

namespace TripleI\C5BaseModel;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\PrePersist;

class BaseModel
{

    /**
     * @Id
     * @Column(type="integer", nullable=false, options={"unsigned": true})
     * @GeneratedValue(strategy="AUTO")
     * @var integer
     */
    public $id;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * get an array of fields which should not be set with setData
     * @return array
     */
    protected function getSkipFields()
    {
        return [
            'ccm_token',
            'id'
        ];
    }

    /**
     * Set the data for this instance. If the setter methods exist for the model they will be used
     * otherwise the attribute will be directly set
     *
     * @param array $data an associative array of 'key' => 'value'
     */
    public function setData($data)
    {
        $skip_fields = $this->getSkipFields();
        foreach ($data as $key => $value) {
            if (in_array($key, $skip_fields)) {
                continue;
            }
            $method = "set" . $this->underscoreToCamelCase($key);
            if (method_exists($this, $method)) {
                $this->$method($value);
            } else {
                $this->$key = $value;
            }
        }
    }

    /**
     * get an instance of the EntityManager to work with
     * @return EntityManager
     */
    public static function getEntityManager()
    {
        $db = \Database::connection();

        return $db->getEntityManager();
    }

    /**
     * save the record to the database
     *
     * prior to inserting/updating the $item->beforeSave() hook is called which can stop the transaction
     * prior to inserting a new record $item->beforeCreate() is called which can also stop the transaction
     * after saving, $item->afterSave() is called which cannot rollback the transaction
     *
     * @return bool
     */
    public function save()
    {
        if ($this->beforeSave() === false) {
            return false;
        }
        $em = static::getEntityManager();

        if ($this->getId() > 0) {
            $em->merge($this);
        } else {
            if ($this->beforeCreate() === false) {
                return false;
            }
            $em->persist($this);
        }
        $em->flush();

        $this->afterSave();

        return true;
    }

    /**
     * remove the record from the database
     */
    public function destroy()
    {
        $em = static::getEntityManager();
        $em->remove($this);
        $em->flush();
    }

    /**
     * load a record by it's primary key
     * @param integer $id
     *
     * @return static
     */
    public static function getByID($id)
    {
        if ( ! intval($id)) {
            return false;
        }
        $em  = static::getEntityManager();
        $obj = $em->find(get_called_class(), intval($id));

        return $em->merge($obj);
    }

    /**
     * @param array $ids a flat array of the IDs of the records to load
     *
     * @return static[]
     */
    public static function loadByIDs($ids)
    {
        $em         = static::getEntityManager();
        $repository = $em->getRepository(get_called_class());

        return $repository->findBy(['id' => $ids]);
    }

    /**
     * this method is run before the object is committed to the database. If this method returns false
     * then the save will not proceed
     * @PrePersist()
     * @return boolean
     */
    public function beforeSave()
    {
        return true;
    }

    /**
     * this is run before a new record is persisted to the database. If the return value is false, the save will be aborted
     * @return bool
     */
    public function beforeCreate()
    {
        return true;
    }

    /**
     * this method runs after the object has been saved to the database
     */
    public function afterSave()
    {

    }

    /**
     * @return static
     */
    public static function factory()
    {
        $class = get_called_class();

        return new $class();
    }

    /**
     * get all of the records of this type
     * @param array $sort
     *
     * @return static[]
     */
    public static function getAll($sort = [])
    {
        $obj = static::factory();
        if (property_exists($obj, '_default_sort') && count($sort) == 0) {
            $sort = $obj->_default_sort;
        }
        if (is_string($sort)) {
            $sort = array($sort => 'ASC');
        }

        $em         = static::getEntityManager();
        $repository = $em->getRepository(get_called_class());

        return $repository->findBy(array(), $sort);
    }
    
    /**
     * get the newest (ordered by ID YRMV) record in the database
     *
     * return static
     */
    public static function getLast()
    {
        $em = static::getEntityManager();
        $repository = $em->getRepository(get_called_class());
        return $repository->findOneBy([], ['id' => 'DESC']);
    }

    /**
     * get the most recent entries in the DB (ordered by ID descending)
     * @param int $num number to get
     *
     * @return static[]
     */
    public static function getRecent($num = 20)
    {
        $em = static::getEntityManager();
        $repository = $em->getRepository(get_called_class());
        return $repository->findBy([], ['id' => 'DESC'], $num);
    }

    /**
     * convert a string from underscore_form to CamelCase form
     *
     * @param string $string
     * @param bool   $capitalizeFirstCharacter
     *
     * @return string
     */
    protected function underscoreToCamelCase($string, $capitalizeFirstCharacter = true)
    {
        $str = str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
        if ( ! $capitalizeFirstCharacter) {
            lcfirst($str);
        }

        return $str;
    }
}
