<?php

trait TranslatableItemTrait
{

    /**
     * Enumerates all translatable and recursively translatable attributes
     * @return array
     */
    public function getTranslatableAttributes()
    {

        $translatableAttributes = array();

        $behaviors = $this->behaviors();

        if (isset($behaviors['i18n-attribute-messages'])) {
            foreach ($behaviors['i18n-attribute-messages']['translationAttributes'] as $translationAttribute) {
                $sourceLanguageContentAttribute = "_" . $translationAttribute;
                $translatableAttributes[$translationAttribute] = $sourceLanguageContentAttribute;
            }
        }

        if (isset($behaviors['i18n-columns'])) {
            foreach ($behaviors['i18n-columns']['translationAttributes'] as $translationAttribute) {
                $sourceLanguageContentAttribute = $translationAttribute . "_" . $this->source_language;
                $translatableAttributes[$translationAttribute] = $sourceLanguageContentAttribute;
            }
        }

        $recursivelyTranslatableAttributes = $this->getRecursivelyTranslatableAttributes();
        foreach ($recursivelyTranslatableAttributes as $translationAttribute => $validatorMethod) {
            $sourceLanguageContentAttribute = $translationAttribute;
            $translatableAttributes[$translationAttribute] = $sourceLanguageContentAttribute;
        }

        return $translatableAttributes;

    }

    public function getMultilingualRelations()
    {

        $multilingualRelations = array();

        $behaviors = $this->behaviors();

        if (isset($behaviors['i18n-columns'])) {
            foreach ($behaviors['i18n-columns']['multilingualRelations'] as $multilingualRelation => $multilingualRelationAttribute) {
                foreach (LanguageHelper::getLanguageList() as $code => $label) {
                    if (!isset($multilingualRelations[$multilingualRelation])) {
                        $multilingualRelations[$multilingualRelation] = [];
                    }
                    $multilingualRelations[$multilingualRelation][$code] = lcfirst(PhInflector::id2camel($multilingualRelationAttribute . "_" . $code, "_"));
                }
            }
        }

        return $multilingualRelations;

    }

    /**
     * Enumerates all recursively translatable attributes
     * @return array
     */
    public function getRecursivelyTranslatableAttributes()
    {

        $recursivelyTranslatableAttributes = array();

        // The following fields are not itself translated, but contains translated contents, they need some special attention
        $i18nRecursivelyValidatedMap = DataModel::i18nRecursivelyValidated();
        if (isset($i18nRecursivelyValidatedMap['attributes'][get_class($this)])) {
            $attributes = $i18nRecursivelyValidatedMap['attributes'][get_class($this)];
            foreach ($attributes as $translationAttribute => $validatorMethod) {
                $recursivelyTranslatableAttributes[$translationAttribute] = $validatorMethod;
            }
        }

        if (isset($i18nRecursivelyValidatedMap['relations'][get_class($this)])) {
            $relations = $i18nRecursivelyValidatedMap['relations'][get_class($this)];
            foreach ($relations as $translationRelation => $validatorMethod) {
                $recursivelyTranslatableAttributes[$translationRelation] = $validatorMethod;
            }
        }

        return $recursivelyTranslatableAttributes;

    }

    /**
     * A currently translatable attribute is an attribute that is to be translated AND has some source contents to translate.
     * @return array
     */
    public function getCurrentlyTranslatableAttributes()
    {
        $currentlyTranslatableAttributes = array();
        $translatableAttributes = $this->getTranslatableAttributes();

        foreach ($translatableAttributes as $translationAttribute => $sourceLanguageContentAttribute) {

            // We need to be careful with potential errors here since it prevents models from being instantiated

            // For debugging only TODO: Remove
            /*
            if (isset($this->$sourceLanguageContentAttribute)) {
                $debug = get_class($this) . "->getCurrentlyTranslatableAttributes() \$this->$sourceLanguageContentAttribute: " . json_encode($this->$sourceLanguageContentAttribute);
            } else {
                $debug = "Not set: \$this->$sourceLanguageContentAttribute";
            }
            //codecept_debug($debug);
            Yii::log($debug, "qa-state", __METHOD__);
            */

            // Ideally we'd like to be able to use validate against the source language content attribute
            // but that causes recursion as long as we use this method as part of the validation logic
            $valid = isset($this->$sourceLanguageContentAttribute) && !is_null($this->$sourceLanguageContentAttribute) && !(is_array($this->$sourceLanguageContentAttribute) && empty($this->$sourceLanguageContentAttribute));
            if ($valid) {
                $currentlyTranslatableAttributes[] = $translationAttribute;
            }

        }

        return $currentlyTranslatableAttributes;
    }

    /**
     * Translations are required if their source content counterpart is a string with some contents
     * @return array
     */
    public function i18nRules()
    {

        Yii::log(get_class($this) . "->i18nRules()", 'flow', __METHOD__);

        // Do nothing if there are no attributes to translate at any time for this model
        $translatableAttributes = $this->getTranslatableAttributes();
        //codecept_debug(compact("translatableAttributes"));
        if (empty($translatableAttributes)) {
            return $this->zeroProgressI18nRules();
        }

        // Pick the first translatable attribute, if any
        $a = $translatableAttributes;
        $attribute = array_shift($a);

        // Get the currently translatable attributes
        $currentlyTranslatableAttributes = $this->getCurrentlyTranslatableAttributes();
        //codecept_debug(compact("currentlyTranslatableAttributes"));
        Yii::log("\$currentlyTranslatableAttributes: " . print_r($currentlyTranslatableAttributes, true), 'qa-state', __METHOD__);

        // If there currently is nothing to translate, then the translation progress should equal 0%
        if (empty($currentlyTranslatableAttributes)) {
            return $this->zeroProgressI18nRules();
        }

        $i18nRules = array();

        foreach ($this->flowSteps() as $step => $fields) {
            foreach ($fields as $field) {
                $sourceLanguageContentAttribute = str_replace('_' . $this->source_language, '', $field);
                if (!in_array($sourceLanguageContentAttribute, $currentlyTranslatableAttributes)) {
                    continue;
                }
                foreach (LanguageHelper::getCodes() as $lang) {

                    // The following fields are not itself translated, but contains translated contents, they need some special attention
                    $recursivelyTranslatableAttributes = $this->getRecursivelyTranslatableAttributes();

                    if (in_array($sourceLanguageContentAttribute, array_keys($recursivelyTranslatableAttributes))) {

                        $validatorMethod = $recursivelyTranslatableAttributes[$sourceLanguageContentAttribute];
                        $i18nRules = array_merge($i18nRules, $this->generateInlineValidatorI18nRules($sourceLanguageContentAttribute, $validatorMethod));

                    } else {

                        // This rule allows the translations to be set from the translation form and is required for i18n-attribute-messages fields since they don't get the safe-validator generated by gii like i18n-columns fields get
                        $i18nRules[] = array($sourceLanguageContentAttribute . '_' . $lang, 'safe', 'on' => "into_$lang-step_$step");
                        // This rule allows the source contents to be set from the translation form (if such functionality is to be restored in the future)
                        //$i18nRules[] = array($sourceLanguageContentAttribute . '_' . $this->source_language, 'safe', 'on' => "into_$lang-step_$step");
                        // This rule would make all the translations in the the translation form to be required before any save is performed - not relevant
                        //$i18nRules[] = array($sourceLanguageContentAttribute . '_' . $lang, 'required', 'on' => "into_$lang-step_$step");
                        // This would allow the translations to be set from a translation form that wasn't split up in steps and using the translate_into_{lang} scenario instead of the above ones
                        //$i18nRules[] = array($sourceLanguageContentAttribute . '_' . $lang, 'safe', 'on' => "translate_into_$lang");
                        // This would allow the source contents to be set from the same translation form as described above
                        //$i18nRules[] = array($sourceLanguageContentAttribute . '_' . $this->source_language, 'safe', 'on' => "translate_into_$lang");
                        // This makes this field required in order to achieve 100% progress against the scenario translate_into_{lang}
                        $i18nRules[] = array($sourceLanguageContentAttribute . '_' . $lang, 'required', 'on' => "translate_into_$lang");

                    }
                }
            }
        }

        //inspect(compact("i18nRules"));

        return $i18nRules;
    }

    /**
     * Add an always invalid status requirement for each language upon the primary key.
     * The result being that there is at least one attribute in each language scenario, and that attribute
     * does not validate, thus translation progress equals 0% as wanted.
     * @return array
     */
    public function zeroProgressI18nRules()
    {

        // Add an always invalid status requirement for each language upon the primary key
        $i18nRules = array();
        foreach (LanguageHelper::getCodes() as $language) {
            $i18nRules[] = array('id', 'compare', 'compareValue' => -1, 'on' => 'translate_into_' . $language);

            /*
            foreach ($this->flowSteps() as $step => $fields) {
                $i18nRules[] = array($attribute, 'compare', 'compareValue' => -1, 'on' => "into_$language-step_$step");
            }
            */
        }

        // The result of the above is that there is at least one attribute in each language scenario, and that attribute does not validate, thus translation progress equals 0% as wanted
        return $i18nRules;

    }


    public function generateInlineValidatorI18nRules($attribute, $inlineValidator)
    {

        // Do not create i18n validation rules for this attribute if it is not currently translatable
        $currentlyTranslatableAttributes = $this->getCurrentlyTranslatableAttributes();
        if (!in_array($attribute, $currentlyTranslatableAttributes)) {
            return array();
        }

        $inlineValidatorI18nRules = array();
        foreach (LanguageHelper::getCodes() as $language) {
            $inlineValidatorI18nRules[] = array($attribute, $inlineValidator, 'on' => 'translate_into_' . $language);

            foreach ($this->flowSteps() as $step => $fields) {
                foreach ($fields as $field) {
                    if ($field == $attribute . '_' . $this->source_language) {
                        $inlineValidatorI18nRules[] = array($attribute, $inlineValidator, 'on' => "into_$language-step_$step");
                    }
                }
            }
        }
        return $inlineValidatorI18nRules;

    }


}