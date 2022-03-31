<?php

namespace PhpArsenal\SoapClient\Result;

use Doctrine\Common\Collections\ArrayCollection;
use PhpArsenal\SoapClient\Result\DescribeSObjectResult\Field;

class DescribeSObjectResult
{
    protected $activateable;
    protected $childRelationships;
    protected $createable;
    protected $custom;
    protected $customSetting;
    protected $deletable;
    protected $deprecatedAndHidden;
    protected $feedEnabled;
    protected $fields;
    protected $keyPrefix;
    protected $label;
    protected $labelPlural;
    protected $layoutable;
    protected $mergeable;
    protected $name;
    protected $queryable;
    protected $replicateable;
    protected $retrieveable;
    protected $searchable;
    protected $triggerable;
    protected $undeletable;
    protected $updateable;

    /**
     * @return bool
     */
    public function isActivateable()
    {
        return $this->activateable;
    }

    /**
     * @return ArrayCollection
     */
    public function getChildRelationships()
    {
        if (! $this->childRelationships instanceof ArrayCollection) {
            $this->childRelationships = new ArrayCollection($this->childRelationships);
        }

        return $this->childRelationships;
    }

    /**
     * Get child relationship by name.
     *
     * @param  string  $name  Relationship name
     * @return ChildRelationship
     */
    public function getChildRelationship($name)
    {
        return $this->getChildRelationships()->filter(function ($input) use ($name) {
            return $name === $input->getRelationshipName();
        })->first();
    }

    /**
     * @return bool
     */
    public function isCreateable()
    {
        return $this->createable;
    }

    /**
     * @return bool
     */
    public function isCustom()
    {
        return $this->custom;
    }

    /**
     * @return bool
     */
    public function isCustomSetting()
    {
        return $this->customSetting;
    }

    /**
     * @return bool
     */
    public function isDeletable()
    {
        return $this->deletable;
    }

    /**
     * @return bool
     */
    public function isDeprecatedAndHidden()
    {
        return $this->deprecatedAndHidden;
    }

    /**
     * @return bool
     */
    public function isFeedEnabled()
    {
        return $this->feedEnabled;
    }

    /**
     * @return ArrayCollection|Field[]
     */
    public function getFields()
    {
        if (! $this->fields instanceof ArrayCollection) {
            $this->fields = new ArrayCollection($this->fields);
        }

        return $this->fields;
    }

    /**
     * Get field description by field name.
     *
     * @param  string  $field  Field name
     * @return Field
     */
    public function getField($field)
    {
        return $this->getFields()->filter(function ($input) use ($field) {
            return $field === $input->getName();
        })->first();
    }

    /**
     * @return string
     */
    public function getKeyPrefix()
    {
        return $this->keyPrefix;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @return string
     */
    public function getLabelPlural()
    {
        return $this->labelPlural;
    }

    /**
     * @return bool
     */
    public function isLayoutable()
    {
        return $this->layoutable;
    }

    /**
     * @return bool
     */
    public function isMergeable()
    {
        return $this->mergeable;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function isQueryable()
    {
        return $this->queryable;
    }

    /**
     * @return bool
     */
    public function isReplicateable()
    {
        return $this->replicateable;
    }

    /**
     * @return bool
     */
    public function isRetrieveable()
    {
        return $this->retrieveable;
    }

    /**
     * @return bool
     */
    public function isSearchable()
    {
        return $this->searchable;
    }

    /**
     * @return bool
     */
    public function isTriggerable()
    {
        return $this->triggerable;
    }

    /**
     * @return bool
     */
    public function isUndeletable()
    {
        return $this->undeletable;
    }

    /**
     * @return bool
     */
    public function isUpdateable()
    {
        return $this->updateable;
    }

    /**
     * Get all fields that constitute relationships to other objects.
     *
     * @return ArrayCollection
     */
    public function getRelationshipFields()
    {
        return $this->getFields()->filter(function ($field) {
            return null !== $field->getRelationshipName();
        });
    }

    /**
     * Get a relationship field.
     *
     * @param  string  $name
     * @return Field
     */
    public function getRelationshipField($name)
    {
        return $this->getRelationshipFields()->filter(function ($field) use ($name) {
            return $name === $field->getName();
        })->first();
    }
}
