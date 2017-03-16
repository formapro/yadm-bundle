<?php
namespace Makasim\Yadm\Bundle\Doctrine;

use Doctrine\Common\Persistence\ObjectManager;
use function Makasim\Values\get_values;
use function Makasim\Values\set_values;
use function Makasim\Yadm\get_object_id;
use Makasim\Yadm\Registry;

class YadmManager implements ObjectManager
{
    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var object[]
     */
    private $peristed = [];

    /**
     * @param Registry $registry
     */
    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function find($className, $id)
    {
        return $this->registry->getStorage($className)->findOne(['_id' => $id]);
    }

    /**
     * {@inheritdoc}
     */
    public function persist($object)
    {
        $this->peristed[] = $object;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($object)
    {
        $this->registry->getStorage(get_class($object))->delete($object);
    }

    /**
     * {@inheritdoc}
     */
    public function merge($object)
    {
        // do nothing
    }

    /**
     * {@inheritdoc}
     */
    public function clear($objectName = null)
    {
        $this->peristed = [];
    }

    /**
     * {@inheritdoc}
     */
    public function detach($object)
    {
        // do nothing
    }

    /**
     * {@inheritdoc}
     */
    public function refresh($object)
    {
        $id = get_object_id($object);

        $refreshedObject = $this->registry->getStorage(get_class($object))->findOne(['_id' => $id]);
        $refreshedValues = get_values($refreshedObject);

        set_values($object, $refreshedValues);
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        $persisted = $this->peristed;
        $this->peristed = [];

        foreach ($persisted as $object) {
            $storage = $this->registry->getStorage(get_class($object));

            if ($id = get_object_id($object)) {
                $storage->update($object);
            } else {
                $storage->insert($object);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getRepository($className)
    {
        throw new \LogicException('Not implmeneted');
    }

    /**
     * {@inheritdoc}
     */
    public function getClassMetadata($className)
    {
        throw new \LogicException('Not implmeneted');
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadataFactory()
    {
        throw new \LogicException('Not implmeneted');
    }

    /**
     * {@inheritdoc}
     */
    public function initializeObject($obj)
    {
        throw new \LogicException('Not implmeneted');
    }

    /**
     * {@inheritdoc}
     */
    public function contains($object)
    {
        return true;
    }
}
