<?php

/**
 * Static helper class for handling languages in the application.
 */
class LanguageHelper
{
    /**
     * Returns a list of all supported languages in the application.
     *
     * Format:
     * array('en' => 'English', ... )
     *
     * @return array data about the languages.
     * @throws CException if no languages are defined in the application config.
     */
    static public function getLanguageList()
    {
        if (!isset(Yii::app()->params['languages'])) {
            throw new CException('No languages defined in application "params" config.');
        }
        return Yii::app()->params['languages'];
    }

    /**
     * Returns a list of all supported languages in the application translated into the users language
     *
     * Format:
     * array('en' => 'English', 'ar' => 'Arabic', ... )
     *
     * @return array the translated languages list
     */
    static public function getTranslatedLanguageList()
    {
        return array(
            'en'    => Yii::t('language', 'English'),
            'ar'    => Yii::t('language', 'Arabic'),
            'bg'    => Yii::t('language', 'Bulgarian'),
            'ca'    => Yii::t('language', 'Catalan'),
            'cs'    => Yii::t('language', 'Czech'),
            'da'    => Yii::t('language', 'Danish'),
            'de'    => Yii::t('language', 'German'),
            'en_gb' => Yii::t('language', 'UK English'),
            'en_us' => Yii::t('language', 'US English'),
            'el'    => Yii::t('language', 'Greek'),
            'es'    => Yii::t('language', 'Spanish'),
            'fa'    => Yii::t('language', 'Persian'),
            'fi'    => Yii::t('language', 'Finnish'),
            'fil'   => Yii::t('language', 'Filipino'),
            'fr'    => Yii::t('language', 'French'),
            'he'    => Yii::t('language', 'Hebrew'),
            'hi'    => Yii::t('language', 'Hindi'),
            'hr'    => Yii::t('language', 'Croatian'),
            'hu'    => Yii::t('language', 'Hungarian'),
            'id'    => Yii::t('language', 'Indonesian'),
            'it'    => Yii::t('language', 'Italian'),
            'ja'    => Yii::t('language', 'Japanese'),
            'ko'    => Yii::t('language', 'Korean'),
            'lt'    => Yii::t('language', 'Lithuanian'),
            'lv'    => Yii::t('language', 'Latvian'),
            'nl'    => Yii::t('language', 'Dutch'),
            'no'    => Yii::t('language', 'Norwegian'),
            'pl'    => Yii::t('language', 'Polish'),
            'pt'    => Yii::t('language', 'Portuguese'),
            'pt_br' => Yii::t('language', 'Portuguese (Brasil)'),
            'pt_pt' => Yii::t('language', 'Portuguese (Portugal)'),
            'ro'    => Yii::t('language', 'Romanian'),
            'ru'    => Yii::t('language', 'Russian'),
            'sk'    => Yii::t('language', 'Slovak'),
            'sl'    => Yii::t('language', 'Slovene'),
            'sr'    => Yii::t('language', 'Serbian'),
            'sv'    => Yii::t('language', 'Swedish'),
            'th'    => Yii::t('language', 'Thai'),
            'tr'    => Yii::t('language', 'Turkish'),
            'uk'    => Yii::t('language', 'Ukrainian'),
            'vi'    => Yii::t('language', 'Vietnamese'),
            'zh'    => Yii::t('language', 'Chinese'),
            'zh_cn' => Yii::t('language', 'Chinese (PRC)'),
            'zh_tw' => Yii::t('language', 'Chinese (Taiwan & Hong Kong)'),
        );
    }

    /**
     * Returns a list of all language directions in the application.
     *
     * Format:
     * array('en' => 'ltr', ... )
     *
     * @return array data about the languages.
     * @throws CException if no languages are defined in the application config.
     */
    static public function getLanguageDirections()
    {
        if (!isset(Yii::app()->params['languageDirections'])) {
            throw new CException('No language directions defined in application "params" config.');
        }
        return Yii::app()->params['languageDirections'];
    }

    /**
     * Returns a list of all supported languages in the application with the direction info, i.e. "ltr" or "rtl".
     *
     * Format:
     * array('en' => array('name' => 'English', 'direction' => 'ltr'), ... )
     *
     * @return array the language list.
     * @throws CException if language direction cannot be found for a language.
     */
    static public function getLanguageListWithDirection()
    {
        $result = array();
        $languages = self::getLanguageList();
        $directions = self::getLanguageDirections();
        foreach ($languages as $code => $name) {
            if (!isset($directions[$code])) {
                throw new CException(sprintf('No language direction defined in app config for "%s".', $code));
            }
            $result[$code] = array(
                'name' => $name,
                'direction' => $directions[$code],
            );
        }
        return $result;
    }

    /**
     * Returns all language codes supported by the application.
     * The codes are a mixed bag of ISO 639-1 codes and language locales.
     *
     * @return array the list of codes.
     */
    static public function getCodes()
    {
        return array_keys(self::getLanguageList());
    }

    /**
     * Returns the language name for given language code.
     *
     * @param string $code the language code used as key in the language list in application config.
     * @return string the name of the language.
     * @throws CException if the language name cannot be found.
     */
    static public function getName($code)
    {
        $languages = self::getLanguageList();
        if (!isset($languages[$code])) {
            throw new CException(sprintf('Failed to find language name for code "%s".', $code));
        }
        return $languages[$code];
    }
}