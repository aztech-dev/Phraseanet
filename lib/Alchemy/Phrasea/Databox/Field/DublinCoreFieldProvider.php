<?php

namespace Alchemy\Phrasea\Databox\Field;

class DublinCoreFieldProvider
{
    /**
     * @return \databox_Field_DCESAbstract[]
     */
    public function getAvailableDublinCoreFields()
    {
        static $fields = null;

        if ($fields === null) {
            $fields = [
                \databox_Field_DCESAbstract::Contributor => new \databox_Field_DCES_Contributor(),
                \databox_Field_DCESAbstract::Coverage    => new \databox_Field_DCES_Coverage(),
                \databox_Field_DCESAbstract::Creator     => new \databox_Field_DCES_Creator(),
                \databox_Field_DCESAbstract::Date        => new \databox_Field_DCES_Date(),
                \databox_Field_DCESAbstract::Description => new \databox_Field_DCES_Description(),
                \databox_Field_DCESAbstract::Format      => new \databox_Field_DCES_Format(),
                \databox_Field_DCESAbstract::Identifier  => new \databox_Field_DCES_Identifier(),
                \databox_Field_DCESAbstract::Language    => new \databox_Field_DCES_Language(),
                \databox_Field_DCESAbstract::Publisher   => new \databox_Field_DCES_Publisher(),
                \databox_Field_DCESAbstract::Relation    => new \databox_Field_DCES_Relation(),
                \databox_Field_DCESAbstract::Rights      => new \databox_Field_DCES_Rights(),
                \databox_Field_DCESAbstract::Source      => new \databox_Field_DCES_Source(),
                \databox_Field_DCESAbstract::Subject     => new \databox_Field_DCES_Subject(),
                \databox_Field_DCESAbstract::Title       => new \databox_Field_DCES_Title(),
                \databox_Field_DCESAbstract::Type        => new \databox_Field_DCES_Type()
            ];
        }

        return $fields;
    }
}
