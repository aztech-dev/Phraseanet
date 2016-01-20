<?php

namespace Alchemy\Tests\Phrasea\Databox;

use Alchemy\Phrasea\Databox\Util\XmlHelper;

class XmlHelperTest extends \PHPUnit_Framework_TestCase
{

    public function testGetDomDocumentReturnsFalseWithInvalidXml()
    {
        $helper = new XmlHelper('<right></wrong>');

        $this->assertFalse($helper->getDomDocument());
    }
}
