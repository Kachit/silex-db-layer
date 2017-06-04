<?php
/**
 * Collection class
 *
 * @author Kachit
 * @package Kachit\Database
 */
namespace Kachit\Database;

use Kachit\Database\Exception\CollectionException;
use Traversable;

class Collection implements CollectionInterface, \JsonSerializable, \IteratorAggregate
{
    /**
     * @var EntityInterface[]
     */
    protected $data = [];

    /**
     * @param EntityInterface $entity
     * @return $this
     */
    public function add(EntityInterface $entity)
    {
        $this->checkEntity($entity);
        $this->data[$entity->getPk()] = $entity;
        return $this;
    }

    /**
     * @param EntityInterface[] $entities
     * @return CollectionInterface
     */
    public function fill(array $entities)
    {
        foreach($entities as $entity) {
            $this->add($entity);
        }
        return $this;
    }

    /**
     * @param mixed $index
     * @return EntityInterface
     * @throws \Exception
     */
    public function get($index)
    {
        if (!$this->has($index)) {
            throw new CollectionException(sprintf('Entity with index "%s" is not exists', $index));
        }
        return $this->data[$index];
    }

    /**
     * @param mixed $index
     * @return CollectionInterface
     * @throws \Exception
     */
    public function remove($index)
    {
        if (!$this->has($index)) {
            throw new CollectionException(sprintf('Entity with index "%s" is not exists', $index));
        }
        unset($this->data[$index]);
        return $this;
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->data);
    }
    /**
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->data);
    }

    /**
     * @param mixed $index
     * @return bool
     */
    public function has($index)
    {
        return isset($this->data[$index]);
    }

    /**
     * @return EntityInterface[]
     */
    public function toArray()
    {
        return $this->data;
    }

    /**
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * @param EntityInterface $entity
     * @throws \Exception
     */
    protected function checkEntity(EntityInterface $entity)
    {
        if ($entity->isNull()) {
            throw new CollectionException('Entity is null');
        }
        if (empty($entity->getPk())) {
            throw new CollectionException('Entity has no primary key');
        }
    }
}