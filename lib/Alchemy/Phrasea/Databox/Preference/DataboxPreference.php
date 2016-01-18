<?php

namespace Alchemy\Phrasea\Databox\Preference;

class DataboxPreference
{
    /**
     * @var null|int
     */
    private $id;

    /**
     * @var string
     */
    private $property;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var mixed
     */
    private $value;

    /**
     * @var \DateTime
     */
    private $updatedOn;

    /**
     * @var \DateTime
     */
    private $createdOn;

    public function __construct($id = null, $locale, $property, $value = '', \DateTime $createdOn = null, \DateTime $updatedOn = null)
    {
        $this->id = $id;
        $this->createdOn = $createdOn ?: new \DateTime();
        $this->updatedOn = $updatedOn ?: new \DateTime();
        $this->locale = $locale;
        $this->property = $property;

        $this->value = $value;
    }

    /**
     * @return null|int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @return string
     */
    public function getProperty()
    {
        return $this->property;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     * @param bool $updateTimestamp
     */
    public function setValue($value, $updateTimestamp = true)
    {
        $this->value = $value;

        if ((bool) $updateTimestamp) {
            $this->updatedOn = new \DateTime();
        }
    }

    /**
     * @return \DateTime
     */
    public function getCreatedOn()
    {
        return $this->createdOn;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedOn()
    {
        return $this->updatedOn;
    }
}
