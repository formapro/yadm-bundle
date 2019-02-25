<?php
namespace Formapro\Yadm\Bundle\Doctrine\Fixture;

use Doctrine\Common\Persistence\ObjectManager;
use function Formapro\Yadm\get_object_id;

class ReferenceRepository extends \Doctrine\Common\DataFixtures\ReferenceRepository
{
    /**
     * @var object[]
     */
    private $references;

    /**
     * @var array[]
     */
    private $identities;

    /**
     * @var ObjectManager
     */
    private $manager;

    /**
     * @param ObjectManager $manager
     * @param array[] $identities
     */
    public function __construct(ObjectManager $manager, $identities = [])
    {
        $this->manager = $manager;
        $this->identities = $identities;
        $this->references = [];

        parent::__construct($manager);
    }

    /**
     * {@inheritdoc}
     */
    public function setReference($name, $reference)
    {
        $this->references[$name] = $reference;
        $this->identities[$name] = [get_class($reference), (string) get_object_id($reference)];
    }

    /**
     * {@inheritdoc}
     */
    public function setReferenceIdentity($name, $identity)
    {
        throw new \LogicException('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function addReference($name, $object)
    {
        if (isset($this->references[$name])) {
            throw new \BadMethodCallException("Reference to: ({$name}) already exists, use method setReference in order to override it");
        }

        $this->setReference($name, $object);
    }

    /**
     * {@inheritdoc}
     */
    public function getReference($name)
    {
        if (false == $this->hasReference($name)) {
            throw new \OutOfBoundsException("Reference to: ({$name}) does not exist");
        }

        return $this->references[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function hasReference($name)
    {
        return isset($this->references[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getReferenceNames($reference)
    {
        return array_keys($this->references, $reference, true);
    }

    /**
     * {@inheritdoc}
     */
    public function hasIdentity($name)
    {
        return array_key_exists($name, $this->identities);
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentities()
    {
        $identities = [];
        foreach ($this->references as $name => $reference) {
            $identities[$name] = [get_class($reference), (string) get_object_id($reference)];
        }

        return $identities;
    }

    /**
     * {@inheritdoc}
     */
    public function getReferences()
    {
        return $this->references;
    }

    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->manager;
    }
}
