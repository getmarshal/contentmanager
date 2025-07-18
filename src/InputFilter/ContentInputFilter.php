<?php

declare(strict_types=1);

namespace Marshal\ContentManager\InputFilter;

use Laminas\InputFilter\Input;
use Laminas\InputFilter\InputFilter;
use Marshal\ContentManager\Content;

class ContentInputFilter extends InputFilter
{
    public function __construct(private Content $content)
    {
        foreach ($content->getType()->getProperties() as $property) {
            if ($property->isAutoIncrement()) {
                continue;
            }

            // dynamically create an input for the property
            $input = new Input($property->getIdentifier());

            // add property filters and validators
            foreach ($property->getFilters() as $filter => $options) {
                $input->getFilterChain()->attachByName($filter, $options);
            }

            foreach ($property->getValidators() as $validator => $options) {
                $input->getValidatorChain()->attachByName($validator, $options);
            }

            // set input options
            if ($property->hasRelation()) {
                $input->setAllowEmpty(FALSE)->setRequired(TRUE);
            } else {
                $input->setAllowEmpty($property->getNotNull())->setRequired($property->getNotNull());
            }

            // append the input to theinput filter
            $this->add($input);
        }
    }

    public function setData($data): static
    {
        foreach ($this->content->getType()->getProperties() as $property)
        {
            if (! isset($data[$property->getIdentifier()])) {
                $propertyValue = $property->getValue();
                $data[$property->getIdentifier()] = $propertyValue instanceof Content ? $propertyValue->toArray() : $propertyValue;
            }
        }

        parent::setData($data);

        return $this;
    }
}
