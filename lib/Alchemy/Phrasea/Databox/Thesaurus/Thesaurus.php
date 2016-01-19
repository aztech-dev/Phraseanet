<?php

namespace Alchemy\Phrasea\Databox\Thesaurus;

use Alchemy\Phrasea\Databox\Util\XmlHelper;

class Thesaurus
{
    public static function createFromDomDocument(\DOMDocument $thesaurusDocument)
    {
        $thesaurusDocument
            ->documentElement
            ->setAttribute("modification_date", $now = date("YmdHis"));

        return new self($thesaurusDocument->saveXML());
    }

    /**
     * @var XmlHelper
     */
    private $xmlHelper;

    /**
     * @param string $rawThesaurus
     */
    public function __construct($rawThesaurus = '')
    {
        $this->xmlHelper = new XmlHelper($rawThesaurus);
    }

    /**
     * @return string
     */
    public function getRawThesaurus()
    {
        return $this->xmlHelper->getRawXml();
    }

    /**
     * @return false|\SimpleXMLElement
     */
    public function getSimpleXmlElement()
    {
        return $this->xmlHelper->getSimpleXmlElement();
    }

    /**
     * @return false|\DOMDocument
     */
    public function getDomDocument()
    {
        return $this->xmlHelper->getDomDocument();
    }

    /**
     * @return false|\DOMXPath
     */
    public function getDomXpath()
    {
        return $this->getDomXpath();
    }
}
