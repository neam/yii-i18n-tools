<?php

trait PoContentsAttributeTrait
{

    public function translatePoJsonMessages($messages, $lang)
    {
        $return = $messages;
        foreach ($return as $key => &$entry) {
            // Skip headers entry
            if (empty($key)) {
                continue;
            }

            $items = explode("\x04", $key);
            $context = (count($items) > 1) ? $items[0] : null;
            $sourceMessage = (isset($items[1])) ? $items[1] : $items[0];
            $category = $this->getTranslationCategory('po_contents', $context);

            // The entry has plural forms if the first array element is not null
            if (!is_null($entry[0])) {
                // The source content first plural form (the singular form) is the $sourceMessage,
                // the second plural form is the first $entry element
                $sourceMessage = ChoiceFormatHelper::toString(array($sourceMessage, $entry[0]), $this->source_language);
                $message = Yii::t($category, $sourceMessage, array(), 'displayedMessages');
                // The translation should be sent as elements 1->end of array
                $entry = array($entry[0]);
                $translationChoiceFormatArray = ChoiceFormatHelper::toArray($message, $lang);
                foreach ($translationChoiceFormatArray as $translation) {
                    // Special fallback - in case the target language has more plural forms than the source language
                    // we'll supply the "true" plural form as fallback for the non-existing plural forms
                    if (is_null($translation)) {
                        $translation = $translationChoiceFormatArray["true"];
                    }
                    $entry[] = $translation;
                }
            } else {
                $message = Yii::t($category, $sourceMessage, array(), 'displayedMessages');
                // The translation should be sent as element 1
                $entry[1] = $message;
            }
        }
        return $return;
    }

    /**
     * Returns the translation category for the current model and attribute.
     *
     * @param string $attribute
     * @param string|null $context
     * @return string
     */
    public function getTranslationCategory($attribute, $context = null)
    {
        if ($context !== null) {
            return $this->tableName() . '-' . $this->id . '-' . $context . '-' . $attribute;
        } else {
            return $this->tableName() . 'i18n_catalog-' . $this->id . '-' . $attribute;
        }
    }

}