<?php

namespace Alchemy\Phrasea\Databox\Structure;

use Symfony\Component\Translation\TranslatorInterface;
use Traversable;

class StructureErrorCollection implements \IteratorAggregate
{
    /**
     * @var StructureError[]
     */
    private $errors = [];

    /**
     * @param string $message
     * @param string[] $context
     */
    public function addErrorMessage($message, array $context = [])
    {
        $this->addError(new StructureError($message, $context));
    }

    /**
     * @param StructureError $error
     */
    public function addError(StructureError $error)
    {
        $this->errors[] = $error;
    }

    /**
     * @param TranslatorInterface $translator
     * @return string[]
     */
    public function getErrors(TranslatorInterface $translator)
    {
        $errors = [];

        foreach ($this->errors as $error) {
            $errors[] = $translator->trans($error->getMessage(), $error->getContext());
        }

        return $errors;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     */
    public function getIterator()
    {
        return $this->errors;
    }
}
